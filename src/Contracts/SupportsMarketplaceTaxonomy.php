<?php

declare(strict_types=1);

namespace Karnoweb\Omnichannel\Contracts;

use Karnoweb\Omnichannel\Models\ChannelConnection;

interface SupportsMarketplaceTaxonomy
{
    /**
     * @return list<array<string, mixed>>
     */
    public function fetchExternalCategories(ChannelConnection $connection, ?string $scopeKey = null): array;
}
