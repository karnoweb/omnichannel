<?php

declare(strict_types=1);

namespace Karnoweb\Omnichannel\Services;

use Karnoweb\Omnichannel\Contracts\PublicationMediaUrlResolverContract;
use Karnoweb\Omnichannel\Models\PublicationMediaAsset;

final class DefaultPublicationMediaUrlResolver implements PublicationMediaUrlResolverContract
{
    public function resolve(PublicationMediaAsset $asset, ?int $expiresMinutes = null): string
    {
        return $this->assetUri($asset);
    }

    public function assetUri(PublicationMediaAsset $asset): string
    {
        return 'omc-asset:'.$asset->getKey();
    }
}