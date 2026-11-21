<?php

declare(strict_types=1);

namespace Karnoweb\Omnichannel\Tests\Feature;

use Karnoweb\Omnichannel\Enums\PublicationSourceType;
use Karnoweb\Omnichannel\Enums\PublicationStatus;
use Karnoweb\Omnichannel\Enums\PublicationType;
use Karnoweb\Omnichannel\Models\Publication;
use Karnoweb\Omnichannel\Services\CatalogSyncPolicy;
use Karnoweb\Omnichannel\Tests\TestCase;

final class CatalogSyncPolicyTest extends TestCase
{
    public function test_free_form_publication_is_not_eligible(): void
    {
        config()->set('omnichannel.catalog_sync.enabled', true);
        $publication = new Publication([
            'source_type' => PublicationSourceType::FreeForm,
            'type' => PublicationType::Post,
            'status' => PublicationStatus::Published,
            'external_id' => 'x',
        ]);
        self::assertFalse(app(CatalogSyncPolicy::class)->publicationEligible($publication));
    }

    public function test_globally_disabled_catalog_sync_blocks_eligibility(): void
    {
        config()->set('omnichannel.catalog_sync.enabled', false);
        $publication = new Publication([
            'source_type' => PublicationSourceType::Product,
            'type' => PublicationType::Product,
            'status' => PublicationStatus::Published,
            'external_id' => 'x',
        ]);
        self::assertFalse(app(CatalogSyncPolicy::class)->publicationEligible($publication));
    }
}