<?php

declare(strict_types=1);

namespace Karnoweb\Omnichannel\Channels\Basalam;

use Illuminate\Support\Facades\Cache;
use Karnoweb\Omnichannel\Contracts\MediaBytesResolverContract;
use Karnoweb\Omnichannel\Http\ApiResponse;
use Karnoweb\Omnichannel\Models\Publication;
use Karnoweb\Omnichannel\ValueObjects\MediaItem;

final class BasalamMediaUploader
{
    private const CACHE_HOURS = 24;

    public function __construct(
        private readonly BasalamApiClient $api,
        private readonly MediaBytesResolverContract $mediaBytes,
    ) {}

    /**
     * @return array{photo_ids: list<int>, video_id: int|null}|ApiResponse
     */
    public function uploadFor(Publication $publication, string $token): array|ApiResponse
    {
        $content = $publication->content();
        $maxPhotos = (int) config('omnichannel.channels.basalam.limits.max_photos', 10);

        $photoIds = [];

        foreach (array_slice($content->images(), 0, $maxPhotos) as $index => $image) {
            $id = $this->uploadOne(
                $publication,
                "photo.{$index}",
                $image,
                BasalamApiClient::FILE_TYPE_PRODUCT_PHOTO,
                $token,
            );

            if ($id instanceof ApiResponse) {
                return $id;
            }

            $photoIds[] = $id;
        }

        $videoId = null;
        $video = $content->videos()[0] ?? null;

        if ($video !== null) {
            $result = $this->uploadOne(
                $publication,
                'video.0',
                $video,
                BasalamApiClient::FILE_TYPE_PRODUCT_VIDEO,
                $token,
            );

            if ($result instanceof ApiResponse) {
                return $result;
            }

            $videoId = $result;
        }

        return ['photo_ids' => $photoIds, 'video_id' => $videoId];
    }

    private function uploadOne(
        Publication $publication,
        string $slot,
        MediaItem $item,
        string $fileType,
        string $token,
    ): int|ApiResponse {
        $cacheKey = "omnichannel:v2:basalam:{$publication->connection_id}:{$publication->id}:upload:{$publication->idempotency_key}:{$slot}";

        $cached = Cache::get($cacheKey) ?? $publication->persistentArtifact('upload', $slot);

        if (is_int($cached)) {
            return $cached;
        }

        $bytes = $this->mediaBytes->fetch($item->url);

        if ($bytes === null) {
            return ApiResponse::transportFailure(
                "Could not download media from [{$item->url}] to upload to Basalam."
            );
        }

        $response = $this->api->uploadFile($bytes, $item->filename(), $fileType, $token);

        if ($response->failed()) {
            return $response;
        }

        $id = $response->int('id');

        if ($id === null) {
            return ApiResponse::transportFailure('Basalam accepted the upload but returned no file id.');
        }

        $publication->rememberPersistentArtifact('upload', $slot, $id);
        Cache::put($cacheKey, $id, now()->addHours(self::CACHE_HOURS));

        return $id;
    }
}
