<?php

declare(strict_types=1);

namespace Karnoweb\Omnichannel\Console;

use Illuminate\Console\Command;
use Karnoweb\Omnichannel\Enums\PublicationStatus;
use Karnoweb\Omnichannel\Omnichannel;
use Karnoweb\Omnichannel\Support\OmnichannelContext;

final class OmnichannelHealthCommand extends Command
{
    protected $signature = 'omnichannel:health {--json : Emit machine-readable output}';

    protected $description = 'Summarize operational publication and connection metrics.';

    public function handle(OmnichannelContext $context): int
    {
        return $context->runAsSystem(function (): int {
            /** @var class-string<\Karnoweb\Omnichannel\Models\Publication> $publication */
            $publication = Omnichannel::model('publication');
            /** @var class-string<\Karnoweb\Omnichannel\Models\ChannelConnection> $connection */
            $connection = Omnichannel::model('connection');

            $metrics = [
                'pending_publish' => $publication::query()->whereIn('status', [
                    PublicationStatus::Draft->value,
                    PublicationStatus::Failed->value,
                ])->count(),
                'scheduled' => $publication::query()->where('status', PublicationStatus::Scheduled->value)->count(),
                'overdue_scheduled' => $publication::query()
                    ->where('status', PublicationStatus::Scheduled->value)
                    ->where('scheduled_at', '<=', now())
                    ->count(),
                'stuck_in_flight' => $publication::query()->stuckInFlight(now()->subMinutes(30))->count(),
                'unknown_outcome' => $publication::query()->where('status', PublicationStatus::UnknownOutcome->value)->count(),
                'expired_connections' => $connection::query()->where('expires_at', '<=', now())->count(),
            ];

            if ((bool) $this->option('json')) {
                $this->line(json_encode($metrics, JSON_THROW_ON_ERROR));

                return self::SUCCESS;
            }

            foreach ($metrics as $key => $value) {
                $this->line(sprintf('%s: %d', $key, $value));
            }

            return self::SUCCESS;
        });
    }
}