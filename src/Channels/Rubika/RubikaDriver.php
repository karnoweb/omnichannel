<?php

declare(strict_types=1);

namespace Karnoweb\Omnichannel\Channels\Rubika;

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

final class RubikaDriver implements ChannelDriver
{
    private const SENT_CACHE_HOURS = 24;

    private const FILE_CACHE_HOURS = 24;

    public function __construct(
        private readonly RubikaApiClient $api,
        private readonly RubikaErrorMapper $errors,
        private readonly MediaBytesResolverContract $mediaBytes,
    ) {}

    public function key(): string
    {
        return 'rubika';
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
                Capability::SilentDelivery,
            ],
            unsupportedReasons: [
                Capability::Album->value => 'The Rubika bot API has no media-group method.',
                Capability::Post->value => 'Rubika has no feed post; use a message instead.',
                Capability::Carousel->value => 'Rubika has no carousel.',
                Capability::Reel->value => 'Rubika has no reel format in its bot API.',
                Capability::Story->value => 'Rubika has no story format in its bot API.',
                Capability::Product->value => 'Rubika is a messenger, not a marketplace.',
                Capability::PriceAndStock->value => 'Rubika has no commerce fields; price and stock are written into the text.',
                Capability::AltText->value => 'Rubika has no alt-text field on media.',
                Capability::Thumbnail->value => 'The Rubika sendFile method has no cover parameter.',
                Capability::Pin->value => 'Pinning is not part of the documented Rubika bot API.',
                Capability::Deactivate->value => 'Rubika has no archive operation for a sent message.',
                Capability::NativeScheduling->value => 'The Rubika bot API cannot schedule a message. Scheduling is handled by this package.',
            ],
            limits: $this->limits(),
            updateFields: ['caption', 'hashtags'],
        );
    }

    public function validate(Publication $publication): ValidationResult
    {
        if (! $this->capabilities()->supportsType($publication->type)) {
            return ValidationResult::error(
                'rubika.type.unsupported',
                trans('omnichannel::omnichannel.validation.type_unsupported', ['type' => $publication->type->label()]),
            );
        }

        return (new MessengerLimits($this->limits(), 'rubika', fetchesMediaFromUrl: false))
            ->validate($publication->type, $publication->content());
    }

    public function testConnection(ChannelConnection $connection): ConnectionTestResult
    {
        $token = $this->token($connection);

        if ($token === null) {
            return ConnectionTestResult::failure(
                FailureClass::Authentication,
                'This Rubika connection has no bot token stored. Reconnect it.',
                'rubika.auth.missing_token',
            );
        }

        $response = $this->api->getMe($token);

        if ($this->rejected($response)) {
            return ConnectionTestResult::failure(
                ($response->isTransportFailure() || $response->status === 408 || ($response->status !== null && $response->status >= 500)) ? FailureClass::UnknownOutcome : $this->errors->classify($response),
                $this->errors->technicalMessage($response),
                $this->errors->errorCodeString($response),
            );
        }

        $username = $response->string('data.bot.username');

        return ConnectionTestResult::success(
            accountId: $connection->external_account_id ?? $response->string('data.bot.bot_id'),
            accountName: $connection->external_account_name ?? ($username !== null ? '@'.$username : null),
            account: (array) $response->get('data.bot', []),
        );
    }

    public function publish(Publication $publication): PublishResult
    {
        $validation = $this->validate($publication);

        if ($validation->fails()) {
            return PublishResult::failure(
                FailureClass::Validation,
                $validation->summary(),
                $validation->errorCodes()[0] ?? 'rubika.validation.failed',
                ['validation' => $validation->toArray()],
            );
        }

        $connection = $publication->connection;
        $token = $this->token($connection);
        $chatId = $this->chatId($connection);

        if ($token === null || $chatId === null) {
            return PublishResult::failure(
                FailureClass::Authentication,
                'This Rubika connection is missing its bot token or chat id. Reconnect it.',
                'rubika.auth.incomplete_connection',
            );
        }

        if ($replay = $this->replay($publication)) {
            return $replay;
        }

        $content = $publication->content();
        $media = $content->firstMedia();

        $base = array_filter([
            'chat_id' => $chatId,
            'text' => $content->captionWithHashtags(),
            'disable_notification' => $this->wantsSilentDelivery($publication) ?: null,
        ], static fn (mixed $value): bool => $value !== null && $value !== '');

        if ($media === null) {
            $response = $this->api->sendMessage($token, $base);
        } else {
            $fileId = $this->uploadMedia($publication, $media, $token);

            if ($fileId instanceof PublishResult) {
                return $fileId;
            }

            $response = $this->api->sendFile($token, [...$base, 'file_id' => $fileId]);
        }

        if ($this->rejected($response)) {
            return $this->failureFrom($response);
        }

        $messageId = $response->string('data.message_id');

        if ($messageId === null) {
            return PublishResult::failure(
                FailureClass::UnknownOutcome,
                'Rubika accepted the message but returned no message id.',
                'rubika.publish.no_message_id',
            );
        }

        $publication->rememberPersistentArtifact('publish', 'external_id', $messageId);
        Cache::put(
            $this->sentCacheKey($publication),
            $messageId,
            now()->addHours(self::SENT_CACHE_HOURS),
        );

        return PublishResult::success($messageId, null, [
            'chat_id' => $chatId,
        ]);
    }

    public function update(Publication $publication): PublishResult
    {
        $token = $this->token($publication->connection);
        $chatId = $this->chatId($publication->connection);

        if ($token === null || $chatId === null || $publication->external_id === null) {
            return PublishResult::failure(
                FailureClass::Authentication,
                'This Rubika connection cannot be used to edit a message.',
                'rubika.auth.incomplete_connection',
            );
        }

        $response = $this->api->editMessageText($token, [
            'chat_id' => $chatId,
            'message_id' => $publication->external_id,
            'text' => $publication->content()->captionWithHashtags(),
        ]);

        if ($this->rejected($response)) {
            return $this->failureFrom($response, ['stage' => 'edit']);
        }

        return PublishResult::success(
            $publication->external_id,
            $publication->external_url,
            ['updated_fields' => ['text']],
        );
    }

    public function delete(Publication $publication): DeleteResult
    {
        $token = $this->token($publication->connection);
        $chatId = $this->chatId($publication->connection);

        if ($token === null || $chatId === null || $publication->external_id === null) {
            return DeleteResult::failure(
                FailureClass::Authentication,
                'This Rubika connection cannot be used to delete a message.',
                'rubika.auth.incomplete_connection',
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
        $cacheKey = 'omnichannel:v2:rubika:'.$publication->connection_id.':'.$publication->id.':upload:'.$publication->idempotency_key;
        $cached = Cache::get($cacheKey) ?? $publication->persistentArtifact('upload', 'file');

        if (is_string($cached)) {
            return $cached;
        }

        $request = $this->api->requestSendFile($token, $this->fileType($item));

        if ($this->rejected($request)) {
            return $this->failureFrom($request, ['stage' => 'request_send_file']);
        }

        $uploadUrl = $request->string('data.upload_url');

        if ($uploadUrl === null) {
            return PublishResult::failure(
                FailureClass::UnknownOutcome,
                'Rubika did not return an upload URL for this file.',
                'rubika.upload.no_url',
            );
        }

        $bytes = $this->mediaBytes->fetch($item->url);

        if ($bytes === null) {
            return PublishResult::failure(
                FailureClass::Validation,
                "Could not download media from [{$item->url}] to upload to Rubika.",
                'rubika.upload.download_failed',
            );
        }

        $upload = $this->api->uploadTo($token, $uploadUrl, $bytes, $item->filename());

        if ($this->rejected($upload)) {
            return $this->failureFrom($upload, ['stage' => 'upload']);
        }

        $fileId = $upload->string('data.file_id') ?? $upload->string('file_id');

        if ($fileId === null) {
            return PublishResult::failure(
                FailureClass::UnknownOutcome,
                'Rubika accepted the upload but returned no file id.',
                'rubika.upload.no_file_id',
            );
        }

        $publication->rememberPersistentArtifact('upload', 'file', $fileId);
        Cache::put($cacheKey, $fileId, now()->addHours(self::FILE_CACHE_HOURS));

        return $fileId;
    }

    private function fileType(MediaItem $item): string
    {
        return $item->type === MediaType::Image ? 'Image' : 'File';
    }

    private function rejected(ApiResponse $response): bool
    {
        if ($response->failed()) {
            return true;
        }

        $status = $response->string('status');

        return $status !== null && mb_strtoupper($status) !== 'OK';
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
        return 'omnichannel:v2:rubika:'.$publication->connection_id.':'.$publication->id.':publish:'.$publication->idempotency_key;
    }

    private function wantsSilentDelivery(Publication $publication): bool
    {
        return (bool) $publication->content()->extra('rubika.silent', false);
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
        $limits = config('omnichannel.channels.rubika.limits', []);

        return $limits;
    }
}
