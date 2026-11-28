<?php

declare(strict_types=1);

namespace Karnoweb\Omnichannel\Models;

use Illuminate\Database\Eloquent\Relations\BelongsToMany;
use Karnoweb\Omnichannel\Enums\MediaAssetStatus;
use Karnoweb\Omnichannel\Enums\MediaType;
use Karnoweb\Omnichannel\Omnichannel;
use Karnoweb\Omnichannel\Support\BranchScope;
use Karnoweb\Omnichannel\Support\OmnichannelTables;

/**
 * @property MediaAssetStatus $status
 * @property MediaType $media_type
 */
class PublicationMediaAsset extends BaseModel
{
    use BranchScope;

    protected $table = 'media_assets';

    protected $casts = [
        'status' => MediaAssetStatus::class,
        'media_type' => MediaType::class,
        'metadata' => 'array',
        'size_bytes' => 'integer',
        'width' => 'integer',
        'height' => 'integer',
        'duration_seconds' => 'integer',
    ];

    public function publications(): BelongsToMany
    {
        return $this->belongsToMany(
            Omnichannel::model('publication'),
            OmnichannelTables::name('publication_media'),
            'media_asset_id',
            'publication_id',
        )->withPivot(['sort_order', 'role']);
    }
}
