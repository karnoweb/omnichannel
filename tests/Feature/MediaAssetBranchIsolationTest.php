<?php

declare(strict_types=1);

namespace Karnoweb\Omnichannel\Tests\Feature;

use Illuminate\Foundation\Testing\RefreshDatabase;
use Karnoweb\Omnichannel\Contracts\BranchContextResolverContract;
use Karnoweb\Omnichannel\Exceptions\BranchAccessDeniedException;
use Karnoweb\Omnichannel\Models\PublicationMediaAsset;
use Karnoweb\Omnichannel\Services\PublicationMediaService;
use Karnoweb\Omnichannel\Support\OmnichannelContext;
use Karnoweb\Omnichannel\Tests\TestCase;

final class MediaAssetBranchIsolationTest extends TestCase
{
    use RefreshDatabase;

    protected function defineDatabaseMigrations(): void
    {
        $this->loadMigrationsFrom(__DIR__.'/../../database/migrations_squashed');
    }

    public function test_cross_branch_asset_cannot_be_used(): void
    {
        $asset = PublicationMediaAsset::query()->create([
            'branch_id' => 1,
            'storage_disk' => 'local',
            'storage_path' => 'x/y.png',
            'media_type' => 'image',
            'mime_type' => 'image/png',
            'size_bytes' => 4,
            'status' => 'ready',
        ]);
        $this->setBranch(2);
        $this->expectException(BranchAccessDeniedException::class);
        app(PublicationMediaService::class)->assertUsable($asset);
    }

    private function setBranch(int $branchId): void
    {
        $this->app->instance(BranchContextResolverContract::class, new class($branchId) implements BranchContextResolverContract
        {
            public function __construct(private readonly int $branchId) {}

            public function branchId(): int|string|null
            {
                return $this->branchId;
            }
        });
        $this->app->forgetInstance(OmnichannelContext::class);
    }
}