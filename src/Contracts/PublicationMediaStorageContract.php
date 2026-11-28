<?php

declare(strict_types=1);

namespace Karnoweb\Omnichannel\Contracts;

use Karnoweb\Omnichannel\Models\PublicationMediaAsset;

interface PublicationMediaStorageContract
{
    /**
     * @param  resource|string  $contents
     */
    public function store(
        int|string $branchId,
        string $originalName,
        string $mimeType,
        mixed $contents,
        int|string|null $actorId = null,
    ): PublicationMediaAsset;

    public function read(PublicationMediaAsset $asset): ?string;

    public function deleteFile(PublicationMediaAsset $asset): bool;

    public function markReady(PublicationMediaAsset $asset): PublicationMediaAsset;
}
