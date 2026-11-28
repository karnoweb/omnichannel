<?php

declare(strict_types=1);

namespace Karnoweb\Omnichannel\Services;

use Karnoweb\Omnichannel\Contracts\CatalogSyncTriggerContract;
use Karnoweb\Omnichannel\Models\Publication;

final class DefaultCatalogSyncTrigger implements CatalogSyncTriggerContract
{
    public function __construct(private readonly CatalogSyncService $sync) {}

    public function recordChange(Publication $publication, array $snapshot): void
    {
        $this->sync->recordProductChange($publication, $snapshot);
    }
}
