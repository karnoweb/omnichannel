<?php

declare(strict_types=1);

namespace Karnoweb\Omnichannel\Console;

use Illuminate\Console\Command;
use Karnoweb\Omnichannel\Omnichannel;
use Karnoweb\Omnichannel\Support\OmnichannelContext;

final class InspectStuckPublicationsCommand extends Command
{
    protected $signature = 'omnichannel:inspect-stuck {--minutes=30} {--branch=} {--channel=} {--limit=100}';

    protected $description = 'Inspect stalled operations without releasing ownership or resending requests.';

    public function handle(OmnichannelContext $context): int
    {
        $minutes = filter_var($this->option('minutes'), FILTER_VALIDATE_INT);
        $limit = filter_var($this->option('limit'), FILTER_VALIDATE_INT);
        if ($minutes === false || $minutes < 1 || $limit === false || $limit < 1 || $limit > 1000) {
            $this->error('minutes must be positive; limit must be between 1 and 1000.');

            return self::INVALID;
        }

        return $context->runAsSystem(function () use ($minutes, $limit): int {
            $model = Omnichannel::model('publication');
            $query = $model::query()->stuckInFlight(now()->subMinutes($minutes));
            foreach (['branch' => 'branch_id', 'channel' => 'channel_id'] as $option => $column) {
                if ($this->option($option) !== null) {
                    $query->where($column, $this->option($option));
                }
            }
            $rows = $query->orderBy('id')->limit($limit)->get()->map(static fn ($row): array => [
                $row->id, $row->branch_id, $row->channel_id, $row->status->value,
                $row->last_attempted_at?->toIso8601String(),
                data_get($row->metadata, 'omnichannel.active_operation.type', 'publish'),
            ])->all();
            $this->table(['ID', 'Branch', 'Channel', 'Status', 'Last attempt', 'Operation'], $rows);

            return self::SUCCESS;
        });
    }
}
