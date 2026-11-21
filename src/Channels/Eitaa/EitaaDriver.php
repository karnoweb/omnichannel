<?php

declare(strict_types=1);

namespace Karnoweb\Omnichannel\Channels\Eitaa;

use Illuminate\Support\Facades\Cache;
use Karnoweb\Omnichannel\Channels\Messaging\MessengerLimits;
use Karnoweb\Omnichannel\Contracts\ChannelDriver;
use Karnoweb\Omnichannel\Contracts\MediaBytesResolverContract;
use Karnoweb\Omnichannel\Enums\Capability;
use Karnoweb\Omnichannel\Enums\FailureClass;
use Karnoweb\Omnichannel\Http\ApiResponse;
use Karnoweb\Omnichannel\Models\ChannelConnection;
use Karnoweb\Omnichannel\Models\Publication;
use Karnoweb\Omnichannel\Results\ConnectionTestResult;
use Karnoweb\Omnichannel\Results\DeleteResult;
use Karnoweb\Omnichannel\Results\PublishResult;
use Karnoweb\Omnichannel\Results\ValidationResult;
use Karnoweb\Omnichannel\ValueObjects\ChannelCapabilities;

final class EitaaDriver implements ChannelDriver
{
    private const SENT_CACHE_HOURS = 24;

    public function __construct(
        private readonly EitaaApiClient $api,
        private readonly EitaaErrorMapper $errors,
        private readonly MediaBytesResolverContract $mediaBytes,
    ) {}

    public function key(): string
    {
        return 'eitaa';
    }

    public function capabilities(): ChannelCapabilities
    {
        return ChannelCapabilities::make(
            supported: [
                Capability::Text,
                Capability::Image,
                Capability::Video,
                Capability::Message,
                Capability::Scheduling,
                Capability::NativeScheduling,
                Capability::Hashtags,
                Capability::Pin,
                Capability::SilentDelivery,
            ],
            unsupportedReasons: [
                Capability::Update->value => 'The Eitaayar gateway offers no edit method.',
                Capability::Delete->value => 'The Eitaayar gateway offers no delete method.',
                Capability::Album->value => 'The gateway sends one file per request; there is no media-group method.',
                Capability::Post->value => 'Eitaa has no feed post; use a message instead.',
                Capability::Carousel->value => 'Eitaa has no carousel.',
                Capability::Reel->value => 'Eitaa has no reel format.',
                Capability::Story->value => 'Eitaa has no story format reachable through the gateway.',
                Capability::Product->value => 'Eitaa is a messenger, not a marketplace.',
                Capability::PriceAndStock->value => 'Eitaa has no commerce fields; price and stock are written into the text.',
                Capability::AltText->value => 'Eitaa has no alt-text field on media.',
                Capability::Thumbnail->value => 'The gateway has no cover parameter for video.',
                Capability::Deactivate->value => 'Eitaa has no archive operation for a sent message.',
            ],
            limits: $this->limits(),
        );
    }

    public function validate(Publication $publication): ValidationResult
    {
        if (! $this->capabilities()->supportsType($publication->type)) {
            return ValidationResult::error(
                'eitaa.type.unsupported',
                trans('omnichannel::omnichannel.validation.type_unsupported', ['type' => $publication->type->label()]),
            );
        }

        return (new MessengerLimits($this->limits(), 'eitaa', fetchesMediaFromUrl: false))
            ->validate($publication->type, $publication->content());
    }

    public function testConnection(ChannelConnection $connection): ConnectionTestResult
    {
        $token = $this->token($connection);
        $chatId = $this->chatId($connection);

        if ($token === null) {
            return ConnectionTestResult::failure(
                FailureClass::Authentication,
                'This Eitaa connection has no gateway token stored. Reconnect it.',
                'eitaa.auth.missing_token',
            );
        }

        if ($chatId === null) {
            return ConnectionTestResult::failure(
                FailureClass::Validation,
                'This Eitaa connection has no channel id. Set the destination channel first.',
                'eitaa.auth.missing_chat_id',
            );
        }

        return ConnectionTestResult::success(
            accountId: $chatId,
            accountName: $connection->external_account_name ?? $connection->name,
            account: [
                'chat_id' => $chatId,
                'gateway' => config('omnichannel.channels.eitaa.base_url'),
                'verified' => 'configuration_only',
            ],
        );
    }

    public function publish(Publication $publication): PublishResult
    {
        $validation = $this->validate($publication);

        if ($validation->fails()) {
            return PublishResult::failure(
                FailureClass::Validation,
                $validation->summary(),
                $validation->errorCodes()[0] ?? 'eitaa.validation.failed',
                ['validation' => $validation->toArray()],
            );
        }

        $connection = $publication->connection;
        $token = $this->token($connection);
        $chatId = $this->chatId($connection);

        if ($token === null || $chatId === null) {
            return PublishResult::failure(
                FailureClass::Authentication,
                'This Eitaa connection is missing its gateway token or channel id.',
                'eitaa.auth.incomplete_connection',
            );
        }

        if ($replay = $this->replay($publication)) {
            return $replay;
        }

        $content = $publication->content();
        $media = $content->firstMedia();
        $text = $content->captionWithHashtags();

        $options = array_filter([
            'chat_id' => $chatId,
            'pin' => $this->wantsPin($publication) ? 1 : null,
            'disable_notification' => $this->wantsSilentDelivery($publication) ? 1 : null,
            'date' => $this->nativeScheduleTimestamp($publication),
        ], static fn (mixed $value): bool => $value !== null);

        if ($media === null) {
            $response = $this->api->sendMessage($token, [...$options, 'text' => $text]);
        } else {
            $bytes = $this->mediaBytes->fetch($media->url);

            if ($bytes === null) {
                return PublishResult::failure(
                    FailureClass::Validation,
                    "Could not download media from [{$media->url}] to send through the Eitaa gateway.",
                    'eitaa.upload.download_failed',
                );
            }

            $response = $this->api->sendFile(
                $token,
                [...$options, 'caption' => $text],
                $bytes,
                $media->filename(),
            );
        }

        if ($this->rejected($response)) {
            return $this->failureFrom($response);
        }

        $messageId = $response->string('result.message_id') ?? $response->string('message_id');

        if ($messageId === null) {
            return PublishResult::failure(
                FailureClass::UnknownOutcome,
                'The Eitaa gateway accepted the message but returned no message id.',
                'eitaa.publish.no_message_id',
            );
        }

        $publication->rememberPersistentArtifact('publish', 'external_id', $messageId);
        Cache::put(
            $this->sentCacheKey($publication),
            $messageId,
            now()->addHours(self::SENT_CACHE_HOURS),
        );

        return PublishResult::success($messageId, null, array_filter([
            'chat_id' => $chatId,
            'gateway' => config('omnichannel.channels.eitaa.base_url'),
            'scheduled_by_platform' => isset($options['date']) ?: null,
        ], static fn (mixed $value): bool => $value !== null));
    }

    public function update(Publication $publication): PublishResult
    {
        return PublishResult::failure(
            FailureClass::Unsupported,
            (string) $this->capabilities()->reasonFor(Capability::Update),
            'eitaa.update.unsupported',
        );
    }

    public function delete(Publication $publication): DeleteResult
    {
        return DeleteResult::unsupported(
            (string) $this->capabilities()->reasonFor(Capability::Delete)
        );
    }

    private function nativeScheduleTimestamp(Publication $publication): ?int
    {
        $optedIn = (bool) $publication->content()->extra('eitaa.native_schedule', false);

        if (! $optedIn || $publication->scheduled_at === null) {
            return null;
        }

        return $publication->scheduled_at->getTimestamp();
    }

    private function rejected(ApiResponse $response): bool
    {
        return $response->failed() || $response->get('ok') === false;
    }

    /**
     * @param  array<string, mixed>  $metadata
     */
    private function failureFrom(ApiResponse $response, array $metadata = []): PublishResult
    {
        return PublishResult::failure(
            ($response->isTransportFailure() || $response->status === 408 || ($response->status !== null && $response->status >= 500)) ? FailureClass::UnknownOutcome : $this->errors->classify($response),
            $this->errors->technicalMessage($response),
            $this->errors->errorCodeString($response),
            [...$metadata, 'response' => $response->toLogArray()],
        );
    }

    private function replay(Publication $publication): ?PublishResult
    {
        $messageId = Cache::get($this->sentCacheKey($publication))
            ?? $publication->persistentArtifact('publish', 'external_id');

        return is_string($messageId)
            ? PublishResult::success($messageId, null, ['idempotent_replay' => true])
            : null;
    }

    private function sentCacheKey(Publication $publication): string
    {
        return 'omnichannel:v2:eitaa:'.$publication->connection_id.':'.$publication->id.':publish:'.$publication->idempotency_key;
    }

    private function wantsPin(Publication $publication): bool
    {
        return (bool) $publication->content()->extra('eitaa.pin', false);
    }

    private function wantsSilentDelivery(Publication $publication): bool
    {
        return (bool) $publication->content()->extra('eitaa.silent', false);
    }

    private function token(ChannelConnection $connection): ?string
    {
        $token = $connection->credential('gateway_token') ?? $connection->credential('access_token');

        return is_string($token) && $token !== '' ? $token : null;
    }

    private function chatId(ChannelConnection $connection): ?string
    {
        $chatId = $connection->credential('chat_id')
            ?? data_get($connection->metadata, 'chat_id')
            ?? $connection->external_account_id;

        return is_scalar($chatId) && (string) $chatId !== '' ? (string) $chatId : null;
    }

    /**
     * @return array<string, mixed>
     */
    private function limits(): array
    {
        /** @var array<string, mixed> $limits */
        $limits = config('omnichannel.channels.eitaa.limits', []);

        return $limits;
    }
}
