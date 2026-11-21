<?php

declare(strict_types=1);

namespace Karnoweb\Omnichannel\Services;

use Karnoweb\Omnichannel\Contracts\SupportsMarketplaceTaxonomy;
use Karnoweb\Omnichannel\Models\ChannelConnection;
use Karnoweb\Omnichannel\Models\ExternalCategory;
use Karnoweb\Omnichannel\Services\ChannelManager;

final class MarketplaceTaxonomyService
{
    public function __construct(private readonly ChannelManager $channels) {}

    public function syncConnection(ChannelConnection $connection, bool $dryRun = false): int
    {
        $driver = $this->channels->driverForConnection($connection);
        if (! $driver instanceof SupportsMarketplaceTaxonomy) {
            return 0;
        }
        $rows = $driver->fetchExternalCategories($connection);
        if ($dryRun || $rows === []) {
            return count($rows);
        }
        $count = 0;
        foreach ($rows as $row) {
            ExternalCategory::query()->updateOrCreate(
                [
                    'channel_id' => $connection->channel_id,
                    'scope_key' => (string) ($row['scope_key'] ?? 'global'),
                    'external_id' => (string) ($row['external_id'] ?? ''),
                ],
                [
                    'external_parent_id' => isset($row['external_parent_id']) ? (string) $row['external_parent_id'] : null,
                    'name' => (string) ($row['name'] ?? 'Unknown'),
                    'path' => isset($row['path']) ? (string) $row['path'] : null,
                    'is_leaf' => (bool) ($row['is_leaf'] ?? true),
                    'is_active' => (bool) ($row['is_active'] ?? true),
                    'metadata' => is_array($row['metadata'] ?? null) ? $row['metadata'] : [],
                    'last_synced_at' => now(),
                ],
            );
            $count++;
        }

        return $count;
    }
}