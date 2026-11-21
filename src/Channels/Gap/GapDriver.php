<?php

declare(strict_types=1);

namespace Karnoweb\Omnichannel\Channels\Gap;

use Illuminate\Support\Facades\Cache;
use Karnoweb\Omnichannel\Channels\Messaging\MessengerLimits;
use Karnoweb\Omnichannel\Contracts\ChannelDriver;
use Karnoweb\Omnichannel\Contracts\MediaBytesResolverContract;
use Karnoweb\Omnichannel\Enums\Capability;
use Karnoweb\Omnichannel\Enums\FailureClass;
use Karnoweb\Omnichannel\Enums\MediaType;
use Karnoweb\Omnichannel\Http\ApiResponse;
use Karnoweb\Omnichannel\Models\ChannelConnection;
use Karnoweb\Omnichannel\Models\Publication;
use Karnoweb\Omnichannel\Results\ConnectionTestResult;
use Karnoweb\Omnichannel\Results\DeleteResult;
use Karnoweb\Omnichannel\Results\PublishResult;
use Karnoweb\Omnichannel\Results\ValidationResult;
use Karnoweb\Omnichannel\ValueObjects\ChannelCapabilities;
use Karnoweb\Omnichannel\ValueObjects\MediaItem;

final class GapDriver implements ChannelDriver
{
    private const SENT_CACHE_HOURS = 24;

    public function __construct(
        private readonly GapApiClient $api,
        private readonly GapErrorMapper $errors,
        private readonly MediaBytesResolverContract $mediaBytes,
    ) {}

    public function key(): string
    {
        return 'gap';
    }

    public function capabilities(): ChannelCapabilities
    {
        return ChannelCapabilities::make(
            supported: [
                Capability::Text,
                Capability::Image,
                Capability::Video,
                Capability::Message,
                Capability::Update,
                Capability::Delete,
                Capability::Scheduling,
                Capability::Hashtags,
            ],
            unsupportedReasons: [
                Capability::Album->value => 'Gap has no media-group method; each item would be a separate message.',
                Capability::Post->value => 'Gap has no feed post; use a message instead.',
                Capability::Carousel->value => 'Gap has no carousel.',
                Capability::Reel->value => 'Gap has no reel format.',
                Capability::Story->value => 'Gap has no story format in its bot API.',
                Capability::Product->value => 'Gap is a messenger, not a marketplace.',
                Capability::PriceAndStock->value => 'Gap has no commerce fields; price and stock are written into the text.',
                Capability::AltText->value => 'Gap has no alt-text field on media.',
                Capability::Thumbnail->value => 'Gap has no cover parameter for video messages.',
                Capability::Pin->value => 'Pinning is not part of the documented Gap bot API.',
                Capability::SilentDelivery->value => 'Gap has no silent-delivery flag on sendMessage.',
                Capability::Deactivate->value => 'Gap has no archive operation for a sent message.',
                Capability::NativeScheduling->value => 'The Gap bot API cannot schedule a message. Scheduling is handled by this package.',
            ],
            limits: $this->limits(),
            updateFields: ['caption', 'hashtags'],
        );
    }

    public function validate(Publication $publication): ValidationResult
    {
        if (! $this->capabilities()->supportsType($publication->type)) {
            return ValidationResult::error(
                'gap.type.unsupported',
                trans('omnichannel::omnichannel.validation.type_unsupported', ['type' => $publication->type->label()]),
            );
        }

        return (new MessengerLimits($this->limits(), 'gap', fetchesMediaFromUrl: false))
            ->validate($publication->type, $publication->content());
    }

    public function testConnection(ChannelConnection $connection): ConnectionTestResult
    {
        $token = $this->token($connection);
        $chatId = $this->chatId($connection);

        if ($token === null) {
            return ConnectionTestResult::failure(
                FailureClass::Authentication,
                'This Gap connection has no bot token stored. Reconnect it.',
                'gap.auth.missing_token',
            );
        }

        if ($chatId === null) {
            return ConnectionTestResult::failure(
                FailureClass::Validation,
                'This Gap connection has no chat id. Set the destination chat before publishing.',
                'gap.auth.missing_chat_id',
            );
        }

        $response = $this->api->sendAction($token, $chatId, 'typing');

        if ($this->rejected($response)) {
            return ConnectionTestResult::failure(
                ($response->isTransportFailure() || $response->status === 408 || ($response->status !== null && $response->status >= 500)) ? FailureClass::UnknownOutcome : $this->errors->classify($response),
                $this->errors->technicalMessage($response),
                $this->errors->errorCodeString($response),
            );
        }

        return ConnectionTestResult::success(
            accountId: $chatId,
            accountName: $connection->external_account_name ?? $connection->name,
            account: ['chat_id' => $chatId],
        );
    }

    public function publish(Publication $publication): PublishResult
    {
        $validation = $this->validate($publication);

        if ($validation->fails()) {
            return PublishResult::failure(
                FailureClass::Validation,
                $validation->summary(),
                $validation->errorCodes()[0] ?? 'gap.validation.failed',
                ['validation' => $validation->toArray()],
            );
        }

        $connection = $publication->connection;
        $token = $this->token($connection);
        $chatId = $this->chatId($connection);

        if ($token === null || $chatId === null) {
            return PublishResult::failure(
                FailureClass::Authentication,
                'This Gap connection is missing its bot token or chat id. Reconnect it.',
                'gap.auth.incomplete_connection',
            );
        }

        if ($replay = $this->replay($publication)) {
            return $replay;
        }

        $content = $publication->content();
        $media = $content->firstMedia();
        $text = $content->captionWithHashtags();

        if ($media === null) {
            $response = $this->api->sendMessage($token, [
                'chat_id' => $chatId,
                'type' => 'text',
                'data' => $text,
            ]);
        } else {
            $path = $this->uploadMedia($publication, $media, $token);

            if ($path instanceof PublishResult) {
                return $path;
            }

            $response = $this->api->sendMessage($token, [
                'chat_id' => $chatId,
                'type' => $media->isVideo() ? 'video' : 'image',
                'data' => $path,
                'desc' => $text,
            ]);
        }

        if ($this->rejected($response)) {
            return $this->failureFrom($response);
        }

        $messageId = $response->string('id')
            ?? $response->string('message_id')
            ?? $response->string('data.id');

        if ($messageId === null) {
            return PublishResult::failure(
                FailureClass::UnknownOutcome,
                'Gap accepted the message but returned no message id.',
                'gap.publish.no_message_id',
            );
        }

        $publication->rememberPersistentArtifact('publish', 'external_id', $messageId);
        Cache::put(
            $this->sentCacheKey($publication),
            $messageId,
            now()->addHours(self::SENT_CACHE_HOURS),
        );

        return PublishResult::success($messageId, null, ['chat_id' => $chatId]);
    }

    public function update(Publication $publication): PublishResult
    {
        $token = $this->token($publication->connection);
        $chatId = $this->chatId($publication->connection);

        if ($token === null || $chatId === null || $publication->external_id === null) {
            return PublishResult::failure(
                FailureClass::Authentication,
                'This Gap connection cannot be used to edit a message.',
                'gap.auth.incomplete_connection',
            );
        }

        $response = $this->api->editMessage($token, [
            'chat_id' => $chatId,
            'message_id' => $publication->external_id,
            'data' => $publication->content()->captionWithHashtags(),
        ]);

        if ($this->rejected($response)) {
            return $this->failureFrom($response, ['stage' => 'edit']);
        }

        return PublishResult::success(
            $publication->external_id,
            $publication->external_url,
            ['updated_fields' => ['data']],
        );
    }

    public function delete(Publication $publication): DeleteResult
    {
        $token = $this->token($publication->connection);
        $chatId = $this->chatId($publication->connection);

        if ($token === null || $chatId === null || $publication->external_id === null) {
            return DeleteResult::failure(
                FailureClass::Authentication,
                'This Gap connection cannot be used to delete a message.',
                'gap.auth.incomplete_connection',
            );
        }

        $response = $this->api->deleteMessage($token, [
            'chat_id' => $chatId,
            'message_id' => $publication->external_id,
        ]);

        if ($this->rejected($response)) {
            return DeleteResult::failure(
                ($response->isTransportFailure() || $response->status === 408 || ($response->status !== null && $response->status >= 500)) ? FailureClass::UnknownOutcome : $this->errors->classify($response),
                $this->errors->technicalMessage($response),
                $this->errors->errorCodeString($response),
            );
        }

        Cache::forget($this->sentCacheKey($publication));
        $publication->forgetPersistentArtifact('publish', 'external_id');

        return DeleteResult::success(['deleted_message_id' => $publication->external_id]);
    }

    private function uploadMedia(Publication $publication, MediaItem $item, string $token): string|PublishResult
    {
        $cacheKey = 'omnichannel:v2:gap:'.$publication->connection_id.':'.$publication->id.':upload:'.$publication->idempotency_key;
        $cached = Cache::get($cacheKey) ?? $publication->persistentArtifact('upload', 'file');

        if (is_string($cached)) {
            return $cached;
        }

        $bytes = $this->mediaBytes->fetch($item->url);

        if ($bytes === null) {
            return PublishResult::failure(
                FailureClass::Validation,
                "Could not download media from [{$item->url}] to upload to Gap.",
                'gap.upload.download_failed',
            );
        }

        $type = $item->type === MediaType::Image ? 'image' : 'video';
        $response = $this->api->uploadMedia($token, $type, $bytes, $item->filename());

        if ($this->rejected($response)) {
            return $this->failureFrom($response, ['stage' => 'upload']);
        }

        $path = $response->string('path') ?? $response->string('data');

        if ($path === null) {
            return PublishResult::failure(
                FailureClass::UnknownOutcome,
                'Gap accepted the upload but returned no file path.',
                'gap.upload.no_path',
            );
        }

        $publication->rememberPersistentArtifact('upload', 'file', $path);
        Cache::put($cacheKey, $path, now()->addHours(self::SENT_CACHE_HOURS));

        return $path;
    }

    private function rejected(ApiResponse $response): bool
    {
        return $response->failed() || $response->get('error') !== null;
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
        return 'omnichannel:v2:gap:'.$publication->connection_id.':'.$publication->id.':publish:'.$publication->idempotency_key;
    }

    private function token(ChannelConnection $connection): ?string
    {
        $token = $connection->credential('bot_token') ?? $connection->credential('access_token');

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
        $limits = config('omnichannel.channels.gap.limits', []);

        return $limits;
    }
}
