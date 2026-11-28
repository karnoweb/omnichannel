<?php

declare(strict_types=1);

namespace Karnoweb\Omnichannel\Models;

use Illuminate\Database\Eloquent\Relations\HasMany;
use Karnoweb\Omnichannel\Enums\PublicationBatchDispatchMode;
use Karnoweb\Omnichannel\Enums\PublicationBatchStatus;
use Karnoweb\Omnichannel\Enums\PublicationSourceType;
use Karnoweb\Omnichannel\Support\BranchScope;
use Karnoweb\Omnichannel\ValueObjects\PublicationContent;

/**
 * @property PublicationSourceType $source_type
 * @property PublicationBatchDispatchMode $dispatch_mode
 * @property PublicationBatchStatus $status
 */
class PublicationBatch extends BaseModel
{
    use BranchScope;

    protected $table = 'publication_batches';

    protected $casts = [
        'source_type' => PublicationSourceType::class,
        'dispatch_mode' => PublicationBatchDispatchMode::class,
        'status' => PublicationBatchStatus::class,
        'base_content' => 'array',
        'metadata' => 'array',
    ];

    public function items(): HasMany
    {
        return $this->hasMany(PublicationBatchItem::class, 'batch_id');
    }

    public function baseContent(): PublicationContent
    {
        return PublicationContent::fromArray((array) $this->base_content);
    }
}
