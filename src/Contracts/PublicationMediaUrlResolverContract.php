<?php

declare(strict_types=1);

namespace Karnoweb\Omnichannel\Contracts;

use Karnoweb\Omnichannel\Models\PublicationMediaAsset;

interface PublicationMediaUrlResolverContract
{
    public function resolve(PublicationMediaAsset $asset, ?int $expiresMinutes = null): string;

    public function assetUri(PublicationMediaAsset $asset): string;
}
