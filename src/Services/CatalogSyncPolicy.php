<?php

declare(strict_types=1);

namespace Karnoweb\Omnichannel\Services;

use Karnoweb\Omnichannel\Enums\Capability;
use Karnoweb\Omnichannel\Enums\PublicationSourceType;
use Karnoweb\Omnichannel\Enums\PublicationStatus;
use Karnoweb\Omnichannel\Enums\PublicationType;
use Karnoweb\Omnichannel\Models\Publication;

final class CatalogSyncPolicy
{
    public function isGloballyEnabled(): bool
    {
        return (bool) config('omnichannel.catalog_sync.enabled', false);
    }

    public function publicationEligible(Publication $publication): bool
    {
        if (! $this->isGloballyEnabled()) {
            return false;
        }
        $publication->loadMissing(['connection.channel']);
        if ($publication->source_type !== PublicationSourceType::Product) {
            return false;
        }
        if ($publication->type !== PublicationType::Product) {
            return false;
        }
        if ($publication->status !== PublicationStatus::Published) {
            return false;
        }
        if ($publication->external_id === null) {
            return false;
        }
        $connection = $publication->connection;
        if ($connection === null || ! (bool) data_get($connection->metadata, 'catalog_sync_enabled', false)) {
            return false;
        }
        $driver = app(ChannelManager::class)->driverForConnection($connection);

        return $driver->capabilities()->supports(Capability::PriceAndStock);
    }
}
