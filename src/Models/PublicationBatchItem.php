<?php

declare(strict_types=1);

namespace Karnoweb\Omnichannel\Models;

use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Karnoweb\Omnichannel\Enums\PublicationType;
use Karnoweb\Omnichannel\Omnichannel;

/**
 * @property PublicationType $publication_type
 */
class PublicationBatchItem extends BaseModel
{
    protected $table = 'publication_batch_items';

    protected $casts = [
        'publication_type' => PublicationType::class,
        'scheduled_at' => 'immutable_datetime',
    ];

    public function batch(): BelongsTo
    {
        return $this->belongsTo(PublicationBatch::class, 'batch_id');
    }

    public function publication(): BelongsTo
    {
        return $this->belongsTo(Omnichannel::model('publication'));
    }

    public function connection(): BelongsTo
    {
        return $this->belongsTo(Omnichannel::model('connection'));
    }
}