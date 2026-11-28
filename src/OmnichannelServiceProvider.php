<?php

declare(strict_types=1);

namespace Karnoweb\Omnichannel;

use Illuminate\Support\ServiceProvider;
use Karnoweb\Omnichannel\Console\DispatchDuePublicationsCommand;
use Karnoweb\Omnichannel\Console\InspectStuckPublicationsCommand;
use Karnoweb\Omnichannel\Console\InspectWhatsAppCatalogBatchCommand;
use Karnoweb\Omnichannel\Console\OmnichannelDoctorCommand;
use Karnoweb\Omnichannel\Console\OmnichannelHealthCommand;
use Karnoweb\Omnichannel\Console\PruneOmnichannelArtifactsCommand;
use Karnoweb\Omnichannel\Console\PrunePublicationMediaCommand;
use Karnoweb\Omnichannel\Console\RedactSensitiveHistoryCommand;
use Karnoweb\Omnichannel\Console\RefreshChannelTokensCommand;
use Karnoweb\Omnichannel\Console\SyncMarketplaceTaxonomyCommand;
use Karnoweb\Omnichannel\Contracts\ActorContextResolverContract;
use Karnoweb\Omnichannel\Contracts\BranchContextResolverContract;
use Karnoweb\Omnichannel\Contracts\CatalogSyncTriggerContract;
use Karnoweb\Omnichannel\Contracts\MediaBytesResolverContract;
use Karnoweb\Omnichannel\Contracts\ProductRepository;
use Karnoweb\Omnichannel\Contracts\PublicationMediaStorageContract;
use Karnoweb\Omnichannel\Contracts\PublicationMediaUrlResolverContract;
use Karnoweb\Omnichannel\Http\HttpMediaBytesResolver;
use Karnoweb\Omnichannel\Services\ApiRequestRateLimiter;
use Karnoweb\Omnichannel\Services\ApiRequestRateLimitScope;
use Karnoweb\Omnichannel\Services\ArrayProductRepository;
use Karnoweb\Omnichannel\Services\CatalogSyncDispatcher;
use Karnoweb\Omnichannel\Services\CatalogSyncExecutor;
use Karnoweb\Omnichannel\Services\CatalogSyncPolicy;
use Karnoweb\Omnichannel\Services\CatalogSyncReconciliationService;
use Karnoweb\Omnichannel\Services\CatalogSyncRecoveryService;
use Karnoweb\Omnichannel\Services\CatalogSyncService;
use Karnoweb\Omnichannel\Services\CatalogSyncSnapshotApplier;
use Karnoweb\Omnichannel\Services\CategoryMappingResolver;
use Karnoweb\Omnichannel\Services\ChannelManager;
use Karnoweb\Omnichannel\Services\DefaultCatalogSyncTrigger;
use Karnoweb\Omnichannel\Services\DefaultPublicationMediaUrlResolver;
use Karnoweb\Omnichannel\Services\IdempotencyKeyGenerator;
use Karnoweb\Omnichannel\Services\LocalPublicationMediaStorage;
use Karnoweb\Omnichannel\Services\MarketplaceAttributeValidator;
use Karnoweb\Omnichannel\Services\MarketplaceTaxonomyService;
use Karnoweb\Omnichannel\Services\PublicationBatchService;
use Karnoweb\Omnichannel\Services\PublicationBatchStatusResolver;
use Karnoweb\Omnichannel\Services\PublicationContentMerger;
use Karnoweb\Omnichannel\Services\PublicationCreationService;
use Karnoweb\Omnichannel\Services\PublicationLogger;
use Karnoweb\Omnichannel\Services\PublicationMediaService;
use Karnoweb\Omnichannel\Services\PublicationRecoveryService;
use Karnoweb\Omnichannel\Services\PublicationSourceRegistry;
use Karnoweb\Omnichannel\Services\PublishingService;
use Karnoweb\Omnichannel\Services\SecretRedactor;
use Karnoweb\Omnichannel\Services\WhatsAppCatalogReconciliationService;
use Karnoweb\Omnichannel\Support\OmnichannelContext;
use Karnoweb\Omnichannel\Support\OmnichannelMorphMap;

class OmnichannelServiceProvider extends ServiceProvider
{
    public function register(): void
    {
        $this->mergeConfigFrom(__DIR__.'/../config/omnichannel.php', 'omnichannel');

        $this->app->singleton(ChannelManager::class);
        $this->app->singleton(IdempotencyKeyGenerator::class);
        $this->app->singleton(SecretRedactor::class);
        $this->app->singleton(PublicationLogger::class);
        $this->app->singleton(ApiRequestRateLimiter::class);
        $this->app->singleton(ApiRequestRateLimitScope::class);

        $this->app->singletonIf(ProductRepository::class, static fn ($app) => new ArrayProductRepository(
            [],
            $app->make(OmnichannelContext::class),
        ));
        $this->app->singletonIf(MediaBytesResolverContract::class, HttpMediaBytesResolver::class);
        $this->app->singleton(PublicationMediaStorageContract::class, LocalPublicationMediaStorage::class);
        $this->app->singleton(PublicationMediaUrlResolverContract::class, DefaultPublicationMediaUrlResolver::class);
        $this->app->singleton(PublicationMediaService::class);
        $this->app->singleton(PublicationContentMerger::class);
        $this->app->singleton(PublicationBatchStatusResolver::class);
        $this->app->singleton(PublicationBatchService::class);
        $this->app->singleton(CategoryMappingResolver::class);
        $this->app->singleton(MarketplaceTaxonomyService::class);
        $this->app->singleton(MarketplaceAttributeValidator::class);
        $this->app->singleton(CatalogSyncPolicy::class);
        $this->app->singleton(CatalogSyncDispatcher::class);
        $this->app->singleton(CatalogSyncSnapshotApplier::class);
        $this->app->singleton(CatalogSyncExecutor::class);
        $this->app->singleton(CatalogSyncService::class);
        $this->app->singleton(CatalogSyncReconciliationService::class);
        $this->app->singleton(CatalogSyncRecoveryService::class);
        $this->app->singletonIf(CatalogSyncTriggerContract::class, DefaultCatalogSyncTrigger::class);

        $this->app->singleton(OmnichannelContext::class, static fn ($app) => new OmnichannelContext(
            $app->bound(BranchContextResolverContract::class)
                ? $app->make(BranchContextResolverContract::class)
                : null,
            $app->bound(ActorContextResolverContract::class)
                ? $app->make(ActorContextResolverContract::class)
                : null,
        ));

        $this->app->singleton(PublicationSourceRegistry::class);
        $this->app->singleton(PublicationCreationService::class);
        $this->app->singleton(PublishingService::class);
        $this->app->singleton(PublicationRecoveryService::class);
        $this->app->singleton(WhatsAppCatalogReconciliationService::class);

        $this->app->singleton('omnichannel', static fn ($app) => new Omnichannel(
            $app->make(ChannelManager::class),
            $app->make(PublishingService::class),
            $app->make(PublicationLogger::class),
            $app->make(OmnichannelContext::class),
        ));
        $this->app->singleton(Omnichannel::class, static fn ($app) => $app->make('omnichannel'));
    }

    public function boot(): void
    {
        OmnichannelMorphMap::register();

        $this->loadTranslationsFrom(__DIR__.'/../lang', 'omnichannel');
        $this->loadMigrationsFrom(__DIR__.'/../database/migrations_squashed');

        if ($this->app->runningInConsole()) {
            $this->commands([
                DispatchDuePublicationsCommand::class,
                InspectWhatsAppCatalogBatchCommand::class,
                InspectStuckPublicationsCommand::class,
                OmnichannelDoctorCommand::class,
                OmnichannelHealthCommand::class,
                PruneOmnichannelArtifactsCommand::class,
                RefreshChannelTokensCommand::class,
                RedactSensitiveHistoryCommand::class,
                PrunePublicationMediaCommand::class,
                SyncMarketplaceTaxonomyCommand::class,
            ]);

            $this->publishes([
                __DIR__.'/../config/omnichannel.php' => config_path('omnichannel.php'),
            ], 'omnichannel-config');

            $this->publishes([
                __DIR__.'/../database/migrations_squashed' => database_path('migrations'),
            ], 'omnichannel-migrations');

            $this->publishes([
                __DIR__.'/../lang' => lang_path('vendor/omnichannel'),
            ], 'omnichannel-lang');

            $this->publishes([
                __DIR__.'/../database/seeders' => database_path('seeders'),
            ], 'omnichannel-seeders');
        }
    }
}
