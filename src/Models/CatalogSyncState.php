<?php

declare(strict_types=1);

namespace Karnoweb\Omnichannel\Models;

use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Karnoweb\Omnichannel\Enums\CatalogSyncStatus;
use Karnoweb\Omnichannel\Omnichannel;
use Karnoweb\Omnichannel\Support\BranchScope;

/**
 * @property CatalogSyncStatus $status
 */
class CatalogSyncState extends BaseModel
{
    use BranchScope;

    protected $table = 'catalog_sync_states';

    protected $casts = [
        'status' => CatalogSyncStatus::class,
        'metadata' => 'array',
        'last_dispatched_at' => 'immutable_datetime',
        'last_synced_at' => 'immutable_datetime',
        'price_hash' => 'integer',
        'stock_hash' => 'integer',
        'attempts' => 'integer',
    ];

    public function publication(): BelongsTo
    {
        return $this->belongsTo(Omnichannel::model('publication'));
    }
}
