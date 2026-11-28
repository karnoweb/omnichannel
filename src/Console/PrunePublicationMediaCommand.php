<?php

declare(strict_types=1);

namespace Karnoweb\Omnichannel\Console;

use Illuminate\Console\Command;
use Karnoweb\Omnichannel\Services\PublicationMediaService;

final class PrunePublicationMediaCommand extends Command
{
    protected $signature = 'omnichannel:prune-media {--execute : Delete matching temporary assets instead of dry-run}';

    protected $description = 'Prune unused temporary publication media assets';

    public function handle(PublicationMediaService $media): int
    {
        $dryRun = ! $this->option('execute');
        $ids = $media->pruneTemporary($dryRun);
        $mode = $dryRun ? 'would prune' : 'pruned';
        $this->info(sprintf('%s %d temporary media asset(s).', ucfirst($mode), count($ids)));

        return self::SUCCESS;
    }
}
