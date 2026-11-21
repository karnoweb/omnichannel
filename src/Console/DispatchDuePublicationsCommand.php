<?php

declare(strict_types=1);

namespace Karnoweb\Omnichannel\Console;

use Illuminate\Console\Command;
use Illuminate\Support\Facades\Cache;
use Karnoweb\Omnichannel\Jobs\PublishPublicationJob;
use Karnoweb\Omnichannel\Models\Publication;
use Karnoweb\Omnichannel\Omnichannel;
use Karnoweb\Omnichannel\Support\OmnichannelContext;

final class DispatchDuePublicationsCommand extends Command
{
    protected $signature = 'omnichannel:dispatch-due
                            {--limit=100 : Maximum publications to queue in one pass}
                            {--dry-run : List what would be queued without queueing it}';

    protected $aliases = ['publications:dispatch-due'];

    protected $description = 'Queue scheduled omnichannel publications whose publish time has arrived.';

    public function handle(OmnichannelContext $context): int
    {
        return $context->runAsSystem(fn (): int => $this->dispatchDue());
    }

    private function dispatchDue(): int
    {
        $limit = (int) $this->option('limit');
        $dryRun = (bool) $this->option('dry-run');

        /** @var class-string<Publication> $publicationModel */
        $publicationModel = Omnichannel::model('publication');

        $due = $publicationModel::query()
            ->due()
            ->with(['channel', 'connection'])
            ->orderBy('scheduled_at')
            ->limit($limit)
            ->get();

        if ($due->isEmpty()) {
            $this->components->info('No publications are due.');

            return self::SUCCESS;
        }

        foreach ($due as $publication) {
            $label = sprintf(
                '#%d %s → %s (%s)',
                $publication->id,
                $publication->product_name ?? $publication->product_id,
                $publication->channel->name,
                $publication->scheduled_at?->toDateTimeString() ?? '—',
            );

            if ($dryRun) {
                $this->line("  would queue {$label}");

                continue;
            }

            $scheduledAt = $publication->scheduled_at?->getTimestamp();
            if ($scheduledAt === null) {
                $this->components->warn("  skipped {$label}: missing scheduled time");

                continue;
            }
            $reservationKey = 'omnichannel:scheduled-dispatch:'.$publication->id.':'.$scheduledAt;
            $reservationTtl = max(60, (int) config('omnichannel.queue.scheduled_dispatch_reservation_seconds', 3600));
            if (! Cache::add($reservationKey, true, now()->addSeconds($reservationTtl))) {
                $this->components->twoColumnDetail($label, '<fg=yellow>already reserved</>');

                continue;
            }

            try {
                dispatch(new PublishPublicationJob($publication, scheduledAtTimestamp: $scheduledAt));
            } catch (\Throwable $exception) {
                Cache::forget($reservationKey);
                throw $exception;
            }
            $this->components->twoColumnDetail($label, '<fg=green>queued</>');
        }

        return self::SUCCESS;
    }
}
