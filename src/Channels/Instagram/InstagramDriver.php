<?php

declare(strict_types=1);

namespace Karnoweb\Omnichannel\Channels\Instagram;

use Carbon\CarbonImmutable;
use Illuminate\Support\Facades\Cache;
use Karnoweb\Omnichannel\Channels\Instagram\Transformers\InstagramTransformer;
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

final class InstagramDriver implements ChannelDriver
{
    private const CONTAINER_CACHE_HOURS = 20;

    public function __construct(
        private readonly InstagramApiClient $api,
        private readonly InstagramTransformer $transformer,
        private readonly InstagramMediaValidator $validator,
        private readonly InstagramErrorMapper $errors,
    ) {}

    public function key(): string
    {
        return 'instagram';
    }

    public function capabilities(): ChannelCapabilities
    {
        return ChannelCapabilities::make(
            supported: [
                Capability::Text,
                Capability::Image,
                Capability::Video,
                Capability::Post,
                Capability::Carousel,
                Capability::Reel,
                Capability::Story,
                Capability::Update,
                Capability::Scheduling,
                Capability::Hashtags,
                Capability::AltText,
                Capability::Thumbnail,
            ],
            unsupportedReasons: [
                Capability::Delete->value => 'DELETE /<IG_MEDIA_ID> is available only to apps using Facebook Login for Business. This integration uses Instagram Login, which does not require a linked Facebook Page but cannot delete media.',
                Capability::Product->value => 'Instagram is a media channel; marketplace product listings are not part of the Content Publishing API.',
                Capability::PriceAndStock->value => 'Price and stock are not fields of the Instagram Content Publishing API.',
                Capability::Deactivate->value => 'The Instagram API has no archive or deactivate operation for published media.',
                Capability::Message->value => 'Instagram direct messaging needs the instagram_business_manage_messages scope and is a conversation feature, not a publishing one.',
                Capability::Album->value => 'Instagram groups multiple media as a carousel, which this driver supports; there is no separate album type.',
                Capability::Pin->value => 'Pinning a post to the profile grid is not exposed by the Content Publishing API.',
                Capability::SilentDelivery->value => 'A feed post triggers no per-recipient notification, so there is nothing to silence.',
                Capability::NativeScheduling->value => 'The Content Publishing API publishes immediately. Scheduling is handled by this package.',
            ],
            limits: (array) config('omnichannel.channels.instagram.limits', []),
            updateFields: ['caption', 'hashtags'],
            clearUpdateFields: ['caption', 'hashtags'],
        );
    }

    public function validate(Publication $publication): ValidationResult
    {
        if (! $this->capabilities()->supportsType($publication->type)) {
            return ValidationResult::error(
                'instagram.type.unsupported',
                trans('omnichannel::omnichannel.validation.type_unsupported', ['type' => $publication->type->label()]),
            );
        }

        return $this->validator->validate($publication->type, $publication->content());
    }

    public function testConnection(ChannelConnection $connection): ConnectionTestResult
    {
        $token = $this->token($connection);

        if ($token === null) {
            return ConnectionTestResult::failure(
                FailureClass::Authentication,
                'This Instagram connection has no access token. Reconnect the account.',
                'instagram.auth.missing_token',
            );
        }

        $response = $this->api->me($token);

        if ($response->failed()) {
            return ConnectionTestResult::failure(
                ($response->isTransportFailure() || $response->status === 408 || ($response->status !== null && $response->status >= 500)) ? FailureClass::UnknownOutcome : $this->errors->classify($response),
                $this->errors->technicalMessage($response),
                $this->errors->errorCodeString($response),
            );
        }

        $username = $response->string('username');

        return ConnectionTestResult::success(
            accountId: $response->string('user_id'),
            accountName: $username !== null ? '@'.$username : null,
            expiresAt: $connection->expires_at !== null
                ? CarbonImmutable::parse($connection->expires_at)
                : null,
            account: $response->data,
        );
    }

    public function publish(Publication $publication): PublishResult
    {
        $storedExternalId = $publication->persistentArtifact('publish', 'external_id');
        if (is_string($storedExternalId) && $storedExternalId !== '') {
            return PublishResult::success($storedExternalId, null, ['idempotent_replay' => true]);
        }

        $validation = $this->validate($publication);

        if ($validation->fails()) {
            return PublishResult::failure(
                FailureClass::Validation,
                $validation->summary(),
                $validation->errorCodes()[0] ?? 'instagram.validation.failed',
                ['validation' => $validation->toArray()],
            );
        }

        $connection = $publication->connection;
        $token = $this->token($connection);
        $igUserId = $connection->external_account_id;

        if ($token === null || $igUserId === null) {
            return PublishResult::failure(
                FailureClass::Authentication,
                'This Instagram connection is missing its access token or account id. Reconnect it.',
                'instagram.auth.incomplete_connection',
            );
        }

        $container = $publication->type === PublicationType::Carousel
            ? $this->createCarouselContainer($publication, $igUserId, $token)
            : $this->createSingleContainer($publication, $igUserId, $token);

        if ($container instanceof PublishResult) {
            return $container;
        }

        return $this->publishContainer($publication, $igUserId, $container, $token);
    }

    public function update(Publication $publication): PublishResult
    {
        $token = $this->token($publication->connection);

        if ($token === null || $publication->external_id === null) {
            return PublishResult::failure(
                FailureClass::Authentication,
                'This Instagram connection cannot be used to update media.',
                'instagram.auth.incomplete_connection',
            );
        }

        if ($publication->type === PublicationType::Story) {
            return PublishResult::failure(
                FailureClass::Unsupported,
                'Instagram stories cannot be edited after publishing.',
                'instagram.update.story_unsupported',
            );
        }

        $response = $this->api->updateMedia(
            $publication->external_id,
            ['caption' => $publication->content()->captionWithHashtags()],
            $token,
        );

        if ($response->failed()) {
            return $this->failureFrom($response);
        }

        return PublishResult::success(
            $publication->external_id,
            $publication->external_url,
            ['updated_fields' => ['caption']],
        );
    }

    public function delete(Publication $publication): DeleteResult
    {
        return DeleteResult::unsupported(
            (string) $this->capabilities()->reasonFor(Capability::Delete)
        );
    }

    private function createSingleContainer(
        Publication $publication,
        string $igUserId,
        string $token,
    ): string|PublishResult {
        if ($cached = $this->cachedContainer($publication, 'main')) {
            return $cached;
        }

        $payload = $this->transformer->transform($publication);
        $response = $this->api->createMediaContainer($igUserId, $payload, $token);

        if ($response->failed()) {
            return $this->failureFrom($response, ['stage' => 'create_container']);
        }

        $containerId = $response->string('id');

        if ($containerId === null) {
            return PublishResult::failure(
                FailureClass::UnknownOutcome,
                'Instagram accepted the container request but returned no container id.',
                'instagram.container.no_id',
            );
        }

        $this->cacheContainer($publication, 'main', $containerId);

        return $containerId;
    }

    private function createCarouselContainer(
        Publication $publication,
        string $igUserId,
        string $token,
    ): string|PublishResult {
        if ($cached = $this->cachedContainer($publication, 'parent')) {
            return $cached;
        }

        $childIds = [];

        foreach ($this->transformer->carousel()->childPayloads($publication) as $index => $childPayload) {
            $cachedChild = $this->cachedContainer($publication, "child.{$index}");

            if ($cachedChild !== null) {
                $childIds[] = $cachedChild;

                continue;
            }

            $response = $this->api->createMediaContainer($igUserId, $childPayload, $token);

            if ($response->failed()) {
                return $this->failureFrom($response, ['stage' => 'create_carousel_child', 'index' => $index]);
            }

            $childId = $response->string('id');

            if ($childId === null) {
                return PublishResult::failure(
                    FailureClass::UnknownOutcome,
                    "Instagram returned no container id for carousel item {$index}.",
                    'instagram.container.no_id',
                );
            }

            $ready = $this->awaitContainer($childId, $token);

            if ($ready instanceof PublishResult) {
                return $ready;
            }

            $this->cacheContainer($publication, "child.{$index}", $childId);
            $childIds[] = $childId;
        }

        $parentPayload = $this->transformer->carousel()->parentPayload($publication, $childIds);
        $response = $this->api->createMediaContainer($igUserId, $parentPayload, $token);

        if ($response->failed()) {
            return $this->failureFrom($response, ['stage' => 'create_carousel_parent']);
        }

        $parentId = $response->string('id');

        if ($parentId === null) {
            return PublishResult::failure(
                FailureClass::UnknownOutcome,
                'Instagram returned no container id for the carousel.',
                'instagram.container.no_id',
            );
        }

        $this->cacheContainer($publication, 'parent', $parentId);

        return $parentId;
    }

    private function publishContainer(
        Publication $publication,
        string $igUserId,
        string $containerId,
        string $token,
    ): PublishResult {
        $ready = $this->awaitContainer($containerId, $token);

        if ($ready instanceof PublishResult) {
            return $ready;
        }

        $response = $this->api->publishMedia($igUserId, $containerId, $token);

        if ($response->failed()) {
            return $this->failureFrom($response, ['stage' => 'media_publish', 'container_id' => $containerId]);
        }

        $mediaId = $response->string('id');

        if ($mediaId === null) {
            return PublishResult::failure(
                FailureClass::UnknownOutcome,
                'Instagram accepted the publish request but returned no media id.',
                'instagram.publish.no_id',
            );
        }

        $publication->rememberPersistentArtifact('publish', 'external_id', $mediaId);

        $this->forgetContainers($publication);

        $permalink = $this->api->getMedia($mediaId, $token)->string('permalink');

        return PublishResult::success($mediaId, $permalink, [
            'container_id' => $containerId,
            'api_version' => config('omnichannel.channels.instagram.api_version'),
        ]);
    }

    private function awaitContainer(string $containerId, string $token): true|PublishResult
    {
        $attempts = (int) config('omnichannel.channels.instagram.container_poll.attempts', 30);
        $interval = (int) config('omnichannel.channels.instagram.container_poll.interval_seconds', 5);

        for ($attempt = 1; $attempt <= $attempts; $attempt++) {
            $response = $this->api->getContainerStatus($containerId, $token);

            if ($response->failed()) {
                return $this->failureFrom($response, ['stage' => 'poll_container']);
            }

            $status = $response->string('status_code');

            if ($status === InstagramApiClient::STATUS_FINISHED
                || $status === InstagramApiClient::STATUS_PUBLISHED) {
                return true;
            }

            if ($status === InstagramApiClient::STATUS_ERROR) {
                return PublishResult::failure(
                    FailureClass::Validation,
                    $response->string('status')
                        ?? 'Instagram could not process this media. Check the format and dimensions.',
                    'instagram.container.error',
                );
            }

            if ($status === InstagramApiClient::STATUS_EXPIRED) {
                return PublishResult::failure(
                    FailureClass::Validation,
                    'The media container expired before it could be published. Containers are valid for 24 hours.',
                    'instagram.container.expired',
                );
            }

            if ($attempt < $attempts && $interval > 0) {
                sleep($interval);
            }
        }

        return PublishResult::failure(
            FailureClass::Transient,
            'Instagram is still processing this media. The next attempt will resume from the same upload.',
            'instagram.container.still_processing',
        );
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

    private function token(ChannelConnection $connection): ?string
    {
        $token = $connection->credential('access_token');

        return is_string($token) && $token !== '' ? $token : null;
    }

    private function containerCacheKey(Publication $publication, string $slot): string
    {
        return "omnichannel:v2:instagram:{$publication->connection_id}:{$publication->id}:publish:container:{$publication->idempotency_key}:{$slot}";
    }

    private function cachedContainer(Publication $publication, string $slot): ?string
    {
        $value = Cache::get($this->containerCacheKey($publication, $slot))
            ?? $publication->persistentArtifact('publish.container', $slot);

        return is_string($value) ? $value : null;
    }

    private function cacheContainer(Publication $publication, string $slot, string $containerId): void
    {
        $publication->rememberPersistentArtifact('publish.container', $slot, $containerId);
        Cache::put(
            $this->containerCacheKey($publication, $slot),
            $containerId,
            now()->addHours(self::CONTAINER_CACHE_HOURS),
        );
    }

    private function forgetContainers(Publication $publication): void
    {
        Cache::forget($this->containerCacheKey($publication, 'main'));
        Cache::forget($this->containerCacheKey($publication, 'parent'));
        $publication->forgetPersistentArtifact('publish.container', 'main');
        $publication->forgetPersistentArtifact('publish.container', 'parent');

        for ($i = 0; $i < 10; $i++) {
            Cache::forget($this->containerCacheKey($publication, "child.{$i}"));
            $publication->forgetPersistentArtifact('publish.container', "child.{$i}");
        }
    }
}
