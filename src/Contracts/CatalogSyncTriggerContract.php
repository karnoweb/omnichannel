<?php

declare(strict_types=1);

namespace Karnoweb\Omnichannel\Contracts;

use Karnoweb\Omnichannel\Models\Publication;

interface CatalogSyncTriggerContract
{
    /**
     * @param  array{price?: int|float|null, stock?: int|null, currency?: string|null}  $snapshot
     */
    public function recordChange(Publication $publication, array $snapshot): void;
}
