<?php

declare(strict_types=1);

namespace Karnoweb\Omnichannel\Models;

use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Karnoweb\Omnichannel\Omnichannel;
use Karnoweb\Omnichannel\Support\BranchScope;

class CategoryMapping extends BaseModel
{
    use BranchScope;

    protected $table = 'category_mappings';

    protected $casts = [
        'attribute_mappings' => 'array',
        'default_attribute_values' => 'array',
        'is_active' => 'boolean',
    ];

    public function connection(): BelongsTo
    {
        return $this->belongsTo(Omnichannel::model('connection'));
    }

    public function externalCategory(): BelongsTo
    {
        return $this->belongsTo(ExternalCategory::class, 'external_category_id');
    }
}