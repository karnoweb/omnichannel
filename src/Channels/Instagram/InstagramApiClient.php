<?php

declare(strict_types=1);

namespace Karnoweb\Omnichannel\Channels\Instagram;

use Karnoweb\Omnichannel\Http\ApiResponse;
use Karnoweb\Omnichannel\Http\BaseApiClient;

final class InstagramApiClient extends BaseApiClient
{
    public const STATUS_IN_PROGRESS = 'IN_PROGRESS';

    public const STATUS_FINISHED = 'FINISHED';

    public const STATUS_PUBLISHED = 'PUBLISHED';

    public const STATUS_ERROR = 'ERROR';

    public const STATUS_EXPIRED = 'EXPIRED';

    /**
     * @param  array<string, mixed>  $payload
     */
    public function createMediaContainer(string $igUserId, array $payload, string $token): ApiResponse
    {
        return $this->post($this->endpoint($igUserId, 'media'), $payload, $token);
    }

    public function publishMedia(string $igUserId, string $creationId, string $token): ApiResponse
    {
        return $this->post(
            $this->endpoint($igUserId, 'media_publish'),
            ['creation_id' => $creationId],
            $token,
        );
    }

    public function getContainerStatus(string $containerId, string $token): ApiResponse
    {
        return $this->get(
            $this->endpoint($containerId),
            ['fields' => 'status_code,status,id'],
            $token,
        );
    }

    public function getMedia(string $mediaId, string $token): ApiResponse
    {
        return $this->get(
            $this->endpoint($mediaId),
            ['fields' => 'id,permalink,media_type,media_product_type,caption,timestamp'],
            $token,
        );
    }

    public function me(string $token): ApiResponse
    {
        return $this->get(
            $this->endpoint('me'),
            ['fields' => 'user_id,username,account_type,name,profile_picture_url'],
            $token,
        );
    }

    public function contentPublishingLimit(string $igUserId, string $token): ApiResponse
    {
        return $this->get(
            $this->endpoint($igUserId, 'content_publishing_limit'),
            ['fields' => 'config,quota_usage'],
            $token,
        );
    }

    /**
     * @param  array<string, mixed>  $payload
     */
    public function updateMedia(string $mediaId, array $payload, string $token): ApiResponse
    {
        return $this->post($this->endpoint($mediaId), $payload, $token);
    }

    public function endpoint(string $path, ?string $edge = null): string
    {
        $base = rtrim((string) config('omnichannel.channels.instagram.graph_url'), '/');
        $version = trim((string) config('omnichannel.channels.instagram.api_version'), '/');

        $url = "{$base}/{$version}/".ltrim($path, '/');

        return $edge !== null ? $url.'/'.$edge : $url;
    }
}
