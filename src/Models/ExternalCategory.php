<?php

declare(strict_types=1);

namespace Karnoweb\Omnichannel\Models;

use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Karnoweb\Omnichannel\Omnichannel;
use Illuminate\Database\Eloquent\Relations\HasMany;

class ExternalCategory extends BaseModel
{
    protected $table = 'external_categories';

    protected $casts = [
        'is_leaf' => 'boolean',
        'is_active' => 'boolean',
        'metadata' => 'array',
        'last_synced_at' => 'immutable_datetime',
    ];

    public function channel(): BelongsTo
    {
        return $this->belongsTo(Omnichannel::model('channel'));
    }

    public function mappings(): HasMany
    {
        return $this->hasMany(CategoryMapping::class, 'external_category_id');
    }
}