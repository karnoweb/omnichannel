<?php

declare(strict_types=1);

namespace Karnoweb\Omnichannel\Console;

use Illuminate\Console\Command;
use Karnoweb\Omnichannel\Omnichannel;
use Karnoweb\Omnichannel\Support\OmnichannelContext;

final class PruneOmnichannelArtifactsCommand extends Command
{
    protected $signature = 'omnichannel:prune-artifacts {--days=90 : Drop transient metadata older than this many days} {--apply : Persist changes (default is dry-run)}';

    protected $description = 'Prune stale omnichannel metadata artifacts without deleting publications.';

    public function handle(OmnichannelContext $context): int
    {
        $days = filter_var($this->option('days'), FILTER_VALIDATE_INT);
        if ($days === false || $days < 1) {
            $this->error('days must be a positive integer.');

            return self::INVALID;
        }

        $apply = (bool) $this->option('apply');

        return $context->runAsSystem(function () use ($days, $apply): int {
            /** @var class-string<\Karnoweb\Omnichannel\Models\Publication> $model */
            $model = Omnichannel::model('publication');
            $cutoff = now()->subDays($days);
            $query = $model::query()->where('updated_at', '<', $cutoff);
            $count = (clone $query)->count();

            if (! $apply) {
                $this->info("Dry-run: {$count} publication rows would be scanned for transient artifact cleanup.");

                return self::SUCCESS;
            }

            $pruned = 0;
            $query->orderBy('id')->chunkById(200, function ($rows) use (&$pruned): void {
                foreach ($rows as $publication) {
                    $metadata = $publication->metadata ?? [];
                    $before = json_encode($metadata);
                    data_forget($metadata, 'omnichannel.content_sync.pending_content');
                    data_forget($metadata, 'omnichannel.active_operation');
                    if (json_encode($metadata) !== $before) {
                        $publication->forceFill(['metadata' => $metadata])->save();
                        $pruned++;
                    }
                }
            });

            $this->info("Pruned transient artifacts on {$pruned} publications.");

            return self::SUCCESS;
        });
    }
}