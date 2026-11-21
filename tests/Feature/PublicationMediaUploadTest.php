<?php

declare(strict_types=1);

namespace Karnoweb\Omnichannel\Tests\Feature;

use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Storage;
use Karnoweb\Omnichannel\Contracts\BranchContextResolverContract;
use Karnoweb\Omnichannel\Enums\MediaAssetStatus;
use Karnoweb\Omnichannel\Http\HttpMediaBytesResolver;
use Karnoweb\Omnichannel\Services\PublicationMediaService;
use Karnoweb\Omnichannel\Support\OmnichannelContext;
use Karnoweb\Omnichannel\Tests\TestCase;

final class PublicationMediaUploadTest extends TestCase
{
    use RefreshDatabase;

    protected function defineDatabaseMigrations(): void
    {
        $this->loadMigrationsFrom(__DIR__.'/../../database/migrations_squashed');
    }

    protected function setUp(): void
    {
        parent::setUp();
        Storage::fake('local');
        $this->setBranch(5);
    }

    public function test_uploaded_asset_is_readable_via_bytes_resolver(): void
    {
        $png = base64_decode('iVBORw0KGgoAAAANSUhEUgAAAAEAAAABCAYAAAAfFcSJAAAADUlEQVR42mP8z8BQDwAEhQGAhKmMIQAAAABJRU5ErkJggg==');
        $asset = app(PublicationMediaService::class)->upload('dot.png', 'image/png', $png);
        self::assertSame(MediaAssetStatus::Ready, $asset->status);
        $bytes = app(HttpMediaBytesResolver::class)->fetch('omc-asset:'.$asset->id);
        self::assertSame($png, $bytes);
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