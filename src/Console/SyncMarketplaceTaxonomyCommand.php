<?php

declare(strict_types=1);

namespace Karnoweb\Omnichannel\Console;

use Illuminate\Console\Command;
use Karnoweb\Omnichannel\Models\ChannelConnection;
use Karnoweb\Omnichannel\Services\MarketplaceTaxonomyService;

final class SyncMarketplaceTaxonomyCommand extends Command
{
    protected $signature = 'omnichannel:sync-taxonomy {connection? : Connection id} {--dry-run : Count without persisting}';

    protected $description = 'Sync marketplace taxonomy for a connection (no-op when driver does not support taxonomy)';

    public function handle(MarketplaceTaxonomyService $taxonomy): int
    {
        $connectionId = $this->argument('connection');
        if ($connectionId === null) {
            $this->warn('No connection specified; taxonomy sync skipped.');

            return self::SUCCESS;
        }
        /** @var ChannelConnection|null $connection */
        $connection = ChannelConnection::query()->find($connectionId);
        if ($connection === null) {
            $this->error('Connection not found.');

            return self::FAILURE;
        }
        $count = $taxonomy->syncConnection($connection, (bool) $this->option('dry-run'));
        $this->info(sprintf('Taxonomy sync processed %d categor(ies).', $count));

        return self::SUCCESS;
    }
}
