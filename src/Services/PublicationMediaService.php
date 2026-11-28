<?php

declare(strict_types=1);

namespace Karnoweb\Omnichannel\Services;

use Karnoweb\Omnichannel\Contracts\PublicationMediaStorageContract;
use Karnoweb\Omnichannel\Contracts\PublicationMediaUrlResolverContract;
use Karnoweb\Omnichannel\Enums\MediaAssetStatus;
use Karnoweb\Omnichannel\Exceptions\BranchAccessDeniedException;
use Karnoweb\Omnichannel\Models\Publication;
use Karnoweb\Omnichannel\Models\PublicationMediaAsset;
use Karnoweb\Omnichannel\Support\OmnichannelContext;
use Karnoweb\Omnichannel\Support\OmnichannelTables;
use Karnoweb\Omnichannel\ValueObjects\MediaItem;
use RuntimeException;

final class PublicationMediaService
{
    public function __construct(
        private readonly PublicationMediaStorageContract $storage,
        private readonly PublicationMediaUrlResolverContract $urls,
        private readonly OmnichannelContext $context,
    ) {}

    public function upload(
        string $originalName,
        string $mimeType,
        mixed $contents,
        int|string|null $actorId = null,
    ): PublicationMediaAsset {
        $branchId = $this->context->branchId();
        if ($branchId === null) {
            throw new BranchAccessDeniedException;
        }
        $asset = $this->storage->store($branchId, $originalName, $mimeType, $contents, $actorId);

        return $this->storage->markReady($asset);
    }

    public function assertUsable(PublicationMediaAsset $asset): void
    {
        $this->context->assertBranchAccess($asset->branch_id);
        if ($asset->status !== MediaAssetStatus::Ready) {
            throw new RuntimeException('Media asset is not ready for publication.');
        }
    }

    public function attachToPublication(Publication $publication, PublicationMediaAsset $asset, int $sortOrder = 0, ?string $role = null): void
    {
        $this->assertUsable($asset);
        $this->context->assertBranchAccess($publication->branch_id);
        $publication->mediaAssets()->syncWithoutDetaching([
            $asset->getKey() => ['sort_order' => $sortOrder, 'role' => $role],
        ]);
    }

    public function toMediaItem(PublicationMediaAsset $asset): MediaItem
    {
        $this->assertUsable($asset);
        $url = $this->urls->resolve($asset);

        return new MediaItem(
            type: $asset->media_type,
            url: $url,
            altText: $asset->original_name,
            width: $asset->width,
            height: $asset->height,
            durationSeconds: $asset->duration_seconds !== null ? (float) $asset->duration_seconds : null,
            sizeBytes: $asset->size_bytes,
            mimeType: $asset->mime_type,
            assetId: (int) $asset->getKey(),
            checksum: $asset->checksum,
        );
    }

    /**
     * @return list<int>
     */
    public function pruneTemporary(bool $dryRun = true): array
    {
        $hours = (int) config('omnichannel.publication_media.temporary_ttl_hours', 24);
        $cutoff = now()->subHours(max(1, $hours));
        $pivot = OmnichannelTables::name('publication_media');
        $candidates = PublicationMediaAsset::query()
            ->where('status', MediaAssetStatus::Temporary)
            ->where('created_at', '<', $cutoff)
            ->whereDoesntHave('publications')
            ->get();
        $deleted = [];
        foreach ($candidates as $asset) {
            if ($dryRun) {
                $deleted[] = (int) $asset->getKey();

                continue;
            }
            $this->storage->deleteFile($asset);
            $asset->delete();
            $deleted[] = (int) $asset->getKey();
        }

        return $deleted;
    }
}
