<?php

declare(strict_types=1);

namespace Karnoweb\Omnichannel\Services;

use Karnoweb\Omnichannel\ValueObjects\PublicationContent;

final class PublicationContentMerger
{
    public function merge(PublicationContent $base, ?PublicationContent $override): PublicationContent
    {
        if ($override === null) {
            return $base;
        }

        $baseArray = $base->toArray();
        $overrideArray = $override->toArray();
        foreach ($overrideArray as $key => $value) {
            $baseArray[$key] = $value;
        }

        return PublicationContent::fromArray($baseArray);
    }
}
