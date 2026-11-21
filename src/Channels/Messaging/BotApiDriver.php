<?php

declare(strict_types=1);

namespace Karnoweb\Omnichannel\Channels\Messaging;

use Illuminate\Support\Facades\Cache;
use Karnoweb\Omnichannel\Contracts\ChannelDriver;
use Karnoweb\Omnichannel\Enums\Capability;
use Karnoweb\Omnichannel\Enums\FailureClass;
use Karnoweb\Omnichannel\Enums\PublicationType;
use Karnoweb\Omnichannel\Http\ApiResponse;
use Karnoweb\Omnichannel\Models\ChannelConnection;
use Karnoweb\Omnichannel\Models\Publication;
use Karnoweb\Omnichannel\Results\ConnectionTestResult;
use Karnoweb\Omnichannel\Results\DeleteResult;
use Karnoweb\Omnichannel\Results\PublishResult;
use Karnoweb\Omnichannel\Results\ValidationResult;
use Karnoweb\Omnichannel\ValueObjects\ChannelCapabilities;
use Karnoweb\Omnichannel\ValueObjects\MediaItem;
use Karnoweb\Omnichannel\ValueObjects\PublicationContent;

abstract class BotApiDriver implements ChannelDriver
{
    protected const SENT_CACHE_HOURS = 24;

    public function __construct(
        protected readonly BotApiClient $api,
        protected readonly BotApiErrorMapper $errors,
    ) {}

    abstract public function key(): string;

    abstract public function capabilities(): ChannelCapabilities;

    abstract protected function messageUrl(ChannelConnection $connection, string $messageId): ?string;

    protected function config(?string $key = null, mixed $default = null): mixed
    {
        $path = 'omnichannel.channels.'.$this->key();

        return config($key === null ? $path : $path.'.'.$key, $default);
    }

    /**
     * @return array<string, mixed>
     */
    protected function limits(): array
    {
        /** @var array<string, mixed> $limits */
        $limits = $this->config('limits', []);

        return $limits;
    }

    protected function validator(): MessengerLimits
    {
        return new MessengerLimits($this->limits(), $this->key());
    }

    protected function supportsParseMode(): bool
    {
        return true;
    }

    protected function parseMode(): string
    {
        $mode = $this->config('parse_mode', 'HTML');

        return is_string($mode) ? $mode : 'HTML';
    }

    public function validate(Publication $publication): ValidationResult
    {
        if (! $this->capabilities()->supportsType($publication->type)) {
            return ValidationResult::error(
                $this->key().'.type.unsupported',
                trans('omnichannel::omnichannel.validation.type_unsupported', ['type' => $publication->type->label()]),
            );
        }

        return $this->validator()->validate($publication->type, $publication->content());
    }

    public function testConnection(ChannelConnection $connection): ConnectionTestResult
    {
        $token = $this->token($connection);

        if ($token === null) {
            return ConnectionTestResult::failure(
                FailureClass::Authentication,
                'This connection has no bot token stored. Reconnect it.',
                $this->key().'.auth.missing_token',
            );
        }

        $response = $this->api->getMe($token);

        if ($response->failed() || $response->get('ok') !== true) {
            return ConnectionTestResult::failure(
                ($response->isTransportFailure() || $response->status === 408 || ($response->status !== null && $response->status >= 500)) ? FailureClass::UnknownOutcome : $this->errors->classify($response),
                $this->errors->technicalMessage($response),
                $this->errors->errorCodeString($response),
            );
        }

        $username = $response->string('result.username');

        return ConnectionTestResult::success(
            accountId: $connection->external_account_id ?? $response->string('result.id'),
            accountName: $connection->external_account_name ?? ($username !== null ? '@'.$username : null),
            account: (array) $response->get('result', []),
        );
    }

    public function publish(Publication $publication): PublishResult
    {
        $validation = $this->validate($publication);

        if ($validation->fails()) {
            return PublishResult::failure(
                FailureClass::Validation,
                $validation->summary(),
                $validation->errorCodes()[0] ?? $this->key().'.validation.failed',
                ['validation' => $validation->toArray()],
            );
        }

        $connection = $publication->connection;
        $token = $this->token($connection);
        $chatId = $this->chatId($connection);

        if ($token === null || $chatId === null) {
            return PublishResult::failure(
                FailureClass::Authentication,
                'This connection is missing its bot token or its chat id. Reconnect it.',
                $this->key().'.auth.incomplete_connection',
            );
        }

        if ($replay = $this->replay($publication, $connection)) {
            return $replay;
        }

        $response = $publication->type === PublicationType::Album
            ? $this->sendAlbum($publication, $chatId, $token)
            : $this->sendSingle($publication, $chatId, $token);

        if ($response->failed() || $response->get('ok') !== true) {
            return $this->failureFrom($response);
        }

        $messageIds = $this->extractMessageIds($response);
        $messageId = $messageIds[0] ?? null;

        if ($messageId === null) {
            return PublishResult::failure(
                FailureClass::UnknownOutcome,
                'The channel accepted the message but returned no message id.',
                $this->key().'.publish.no_message_id',
            );
        }

        $this->remember($publication, $messageId, $messageIds);
        $this->pinIfRequested($publication, $chatId, $messageId, $token);

        return PublishResult::success(
            $messageId,
            $this->messageUrl($connection, $messageId),
            ['chat_id' => $chatId, 'type' => $publication->type->value, 'external_ids' => $messageIds],
        );
    }

    public function update(Publication $publication): PublishResult
    {
        $connection = $publication->connection;
        $token = $this->token($connection);
        $chatId = $this->chatId($connection);

        if ($token === null || $chatId === null || $publication->external_id === null) {
            return PublishResult::failure(
                FailureClass::Authentication,
                'This connection cannot be used to edit a message.',
                $this->key().'.auth.incomplete_connection',
            );
        }

        if ($publication->type === PublicationType::Album) {
            return PublishResult::failure(
                FailureClass::Unsupported,
                'A grouped album cannot be edited after sending.',
                $this->key().'.update.album_unsupported',
            );
        }

        $content = $publication->content();
        $hasMedia = $content->hasMedia();

        $payload = array_filter([
            'chat_id' => $chatId,
            'message_id' => $publication->external_id,
            $hasMedia ? 'caption' : 'text' => $content->captionWithHashtags(),
            'parse_mode' => $this->supportsParseMode() ? $this->parseMode() : null,
        ], static fn (mixed $value): bool => $value !== null);

        $response = $hasMedia
            ? $this->api->editMessageCaption($token, $payload)
            : $this->api->editMessageText($token, $payload);

        if ($response->failed() || $response->get('ok') !== true) {
            return $this->failureFrom($response, ['stage' => 'edit']);
        }

        return PublishResult::success(
            $publication->external_id,
            $publication->external_url,
            ['updated_fields' => [$hasMedia ? 'caption' : 'text']],
        );
    }

    public function delete(Publication $publication): DeleteResult
    {
        if (! $this->capabilities()->supports(Capability::Delete)) {
            return DeleteResult::unsupported(
                (string) $this->capabilities()->reasonFor(Capability::Delete)
            );
        }

        $connection = $publication->connection;
        $token = $this->token($connection);
        $chatId = $this->chatId($connection);

        if ($token === null || $chatId === null || $publication->external_id === null) {
            return DeleteResult::failure(
                FailureClass::Authentication,
                'This connection cannot be used to delete a message.',
                $this->key().'.auth.incomplete_connection',
            );
        }

        $ids = $publication->persistentArtifact('delete', 'remaining_external_ids')
            ?? $publication->persistentArtifact('publish', 'external_ids');
        if (! is_array($ids) || $ids === []) {
            $ids = [$publication->external_id];
        }
        $ids = array_values(array_unique(array_filter(array_map('strval', $ids))));
        $deleted = [];

        foreach ($ids as $messageId) {
            $response = $this->api->deleteMessage($token, [
                'chat_id' => $chatId,
                'message_id' => $messageId,
            ]);

            if ($response->failed() || $response->get('ok') !== true) {
                $remaining = array_values(array_diff($ids, $deleted));
                $publication->rememberPersistentArtifact('delete', 'remaining_external_ids', $remaining);
                $failureClass = ($response->isTransportFailure() || $response->status === 408 || ($response->status !== null && $response->status >= 500))
                    ? FailureClass::UnknownOutcome
                    : $this->errors->classify($response);

                return DeleteResult::failure(
                    $failureClass,
                    $this->errors->technicalMessage($response),
                    $this->errors->errorCodeString($response),
                    ['deleted_message_ids' => $deleted, 'remaining_message_ids' => $remaining],
                );
            }

            $deleted[] = $messageId;
            $remaining = array_values(array_diff($ids, $deleted));
            $publication->rememberPersistentArtifact('delete', 'remaining_external_ids', $remaining);
        }

        Cache::forget($this->sentCacheKey($publication));
        $publication->forgetPersistentArtifact('publish', 'external_id');
        $publication->forgetPersistentArtifact('publish', 'external_ids');
        $publication->forgetPersistentArtifact('delete', 'remaining_external_ids');

        return DeleteResult::success([
            'deleted_message_id' => $publication->external_id,
            'deleted_message_ids' => $deleted,
        ]);
    }

    protected function sendSingle(Publication $publication, string $chatId, string $token): ApiResponse
    {
        $content = $publication->content();
        $media = $content->firstMedia();
        $text = $content->captionWithHashtags();

        $base = array_filter([
            'chat_id' => $chatId,
            'parse_mode' => $this->supportsParseMode() ? $this->parseMode() : null,
            'disable_notification' => $this->wantsSilentDelivery($publication) ?: null,
        ], static fn (mixed $value): bool => $value !== null);

        if ($media === null) {
            return $this->api->sendMessage($token, [...$base, 'text' => $text]);
        }

        $payload = [...$base, 'caption' => $text];

        return $media->isVideo()
            ? $this->api->sendVideo($token, [...$payload, 'video' => $media->url])
            : $this->api->sendPhoto($token, [...$payload, 'photo' => $media->url]);
    }

    protected function sendAlbum(Publication $publication, string $chatId, string $token): ApiResponse
    {
        $content = $publication->content();
        $media = [];

        foreach ($content->media as $index => $item) {
            $entry = [
                'type' => $item->isVideo() ? 'video' : 'photo',
                'media' => $item->url,
            ];

            if ($index === 0) {
                $entry['caption'] = $content->captionWithHashtags();

                if ($this->supportsParseMode()) {
                    $entry['parse_mode'] = $this->parseMode();
                }
            }

            $media[] = $entry;
        }

        return $this->api->sendMediaGroup($token, array_filter([
            'chat_id' => $chatId,
            'media' => $media,
            'disable_notification' => $this->wantsSilentDelivery($publication) ?: null,
        ], static fn (mixed $value): bool => $value !== null));
    }

    protected function pinIfRequested(
        Publication $publication,
        string $chatId,
        string $messageId,
        string $token,
    ): void {
        $wantsPin = (bool) $publication->content()->extra($this->key().'.pin', false);

        if (! $wantsPin || ! $this->capabilities()->supports(Capability::Pin)) {
            return;
        }

        $this->api->pinChatMessage($token, [
            'chat_id' => $chatId,
            'message_id' => $messageId,
        ]);
    }

    protected function wantsSilentDelivery(Publication $publication): bool
    {
        return $this->capabilities()->supports(Capability::SilentDelivery)
            && (bool) $publication->content()->extra($this->key().'.silent', false);
    }

    protected function extractMessageIds(ApiResponse $response): array
    {
        $result = $response->get('result');

        if (is_array($result) && array_is_list($result)) {
            return array_values(array_unique(array_filter(array_map(
                static fn (mixed $message): ?string => is_array($message) && isset($message['message_id'])
                    ? (string) $message['message_id']
                    : null,
                $result,
            ))));
        }

        $messageId = $response->string('result.message_id');

        return $messageId === null ? [] : [$messageId];
    }

    protected function replay(Publication $publication, ChannelConnection $connection): ?PublishResult
    {
        $storedIds = Cache::get($this->sentCacheKey($publication))
            ?? $publication->persistentArtifact('publish', 'external_ids');
        $messageIds = is_array($storedIds) ? array_values(array_filter(array_map('strval', $storedIds))) : [];
        $messageId = $messageIds[0] ?? (is_string($storedIds) ? $storedIds : $publication->persistentArtifact('publish', 'external_id'));

        if (! is_string($messageId)) {
            return null;
        }

        return PublishResult::success(
            $messageId,
            $this->messageUrl($connection, $messageId),
            ['idempotent_replay' => true, 'external_ids' => $messageIds !== [] ? $messageIds : [$messageId]],
        );
    }

    protected function remember(Publication $publication, string $messageId, array $messageIds = []): void
    {
        $messageIds = $messageIds !== [] ? array_values(array_unique($messageIds)) : [$messageId];
        $publication->rememberPersistentArtifact('publish', 'external_ids', $messageIds);
        $publication->rememberPersistentArtifact('publish', 'external_id', $messageId);
        Cache::put(
            $this->sentCacheKey($publication),
            $messageIds,
            now()->addHours(static::SENT_CACHE_HOURS),
        );
    }

    protected function sentCacheKey(Publication $publication): string
    {
        return 'omnichannel:v2:'.$this->key().':'.$publication->connection_id.':'.$publication->id.':publish:'.$publication->idempotency_key;
    }

    /**
     * @param  array<string, mixed>  $metadata
     */
    protected function failureFrom(ApiResponse $response, array $metadata = []): PublishResult
    {
        $retryAfter = $this->errors->retryAfterSeconds($response);

        return PublishResult::failure(
            ($response->isTransportFailure() || $response->status === 408 || ($response->status !== null && $response->status >= 500)) ? FailureClass::UnknownOutcome : $this->errors->classify($response),
            $this->errors->technicalMessage($response),
            $this->errors->errorCodeString($response),
            array_filter([
                ...$metadata,
                'retry_after' => $retryAfter,
                'response' => $response->toLogArray(),
            ], static fn (mixed $value): bool => $value !== null),
        );
    }

    protected function token(ChannelConnection $connection): ?string
    {
        $token = $connection->credential('bot_token') ?? $connection->credential('access_token');

        return is_string($token) && $token !== '' ? $token : null;
    }

    protected function chatId(ChannelConnection $connection): ?string
    {
        $chatId = $connection->credential('chat_id')
            ?? data_get($connection->metadata, 'chat_id')
            ?? $connection->external_account_id;

        return is_scalar($chatId) && (string) $chatId !== '' ? (string) $chatId : null;
    }

    /**
     * @param  array<string, string>  $extra
     * @return array<string, string>
     */
    protected function feedCapabilitiesUnsupported(string $platform, array $extra = []): array
    {
        return [
            Capability::Post->value => "{$platform} has no feed post; use a message instead.",
            Capability::Carousel->value => "{$platform} has no carousel; use an album instead.",
            Capability::Reel->value => "{$platform} has no reel format.",
            Capability::Story->value => "{$platform} has no story format in its bot API.",
            Capability::Product->value => "{$platform} is a messenger, not a marketplace.",
            Capability::PriceAndStock->value => "{$platform} has no commerce fields; price and stock are written into the message text.",
            Capability::AltText->value => "{$platform} has no alt-text field on media.",
            Capability::Deactivate->value => "{$platform} has no archive or deactivate operation for a sent message.",
            ...$extra,
        ];
    }

    protected function renderText(PublicationContent $content): string
    {
        return $content->captionWithHashtags();
    }

    /**
     * @return list<MediaItem>
     */
    protected function mediaOf(Publication $publication): array
    {
        return $publication->content()->media;
    }
}
