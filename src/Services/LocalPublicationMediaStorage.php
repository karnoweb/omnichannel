<?php

declare(strict_types=1);

namespace Karnoweb\Omnichannel\Services;

use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Str;
use Karnoweb\Omnichannel\Contracts\PublicationMediaStorageContract;
use Karnoweb\Omnichannel\Enums\MediaAssetStatus;
use Karnoweb\Omnichannel\Enums\MediaType;
use Karnoweb\Omnichannel\Models\PublicationMediaAsset;
use RuntimeException;

final class LocalPublicationMediaStorage implements PublicationMediaStorageContract
{
    public function store(
        int|string $branchId,
        string $originalName,
        string $mimeType,
        mixed $contents,
        int|string|null $actorId = null,
    ): PublicationMediaAsset {
        $disk = (string) config('omnichannel.publication_media.disk', 'local');
        $directory = trim((string) config('omnichannel.publication_media.directory', 'omnichannel/media'), '/');
        $filename = Str::uuid()->toString().'.'.pathinfo($originalName, PATHINFO_EXTENSION);
        $path = $directory.'/branch-'.$branchId.'/'.$filename;
        $binary = is_resource($contents) ? stream_get_contents($contents) : (string) $contents;
        if ($binary === false || $binary === '') {
            throw new RuntimeException('Cannot store empty media payload.');
        }
        Storage::disk($disk)->put($path, $binary);
        $checksum = hash('sha256', $binary);

        return PublicationMediaAsset::query()->create([
            'branch_id' => $branchId,
            'created_by' => $actorId,
            'storage_disk' => $disk,
            'storage_path' => $path,
            'original_name' => $originalName,
            'media_type' => MediaType::guessFromMime($mimeType) ?? MediaType::Image,
            'mime_type' => $mimeType,
            'size_bytes' => strlen($binary),
            'checksum' => $checksum,
            'status' => MediaAssetStatus::Temporary,
        ]);
    }

    public function read(PublicationMediaAsset $asset): ?string
    {
        if (! Storage::disk($asset->storage_disk)->exists($asset->storage_path)) {
            return null;
        }

        return Storage::disk($asset->storage_disk)->get($asset->storage_path);
    }

    public function deleteFile(PublicationMediaAsset $asset): bool
    {
        if (! Storage::disk($asset->storage_disk)->exists($asset->storage_path)) {
            return true;
        }

        return Storage::disk($asset->storage_disk)->delete($asset->storage_path);
    }

    public function markReady(PublicationMediaAsset $asset): PublicationMediaAsset
    {
        $asset->status = MediaAssetStatus::Ready;
        $asset->save();

        return $asset;
    }
}