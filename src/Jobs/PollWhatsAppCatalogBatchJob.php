<?php

declare(strict_types=1);

namespace Karnoweb\Omnichannel\Jobs;

use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Queue\Queueable;
use Karnoweb\Omnichannel\Models\Publication;
use Karnoweb\Omnichannel\Services\WhatsAppCatalogReconciliationService;
use Karnoweb\Omnichannel\Support\OmnichannelContext;

final class PollWhatsAppCatalogBatchJob implements ShouldQueue
{
    use Queueable;

    public function __construct(public readonly Publication $publication) {}

    public function handle(WhatsAppCatalogReconciliationService $service, OmnichannelContext $context): void
    {
        $run = fn () => $service->finalizeWhenReady($this->publication->fresh() ?? $this->publication);

        $branchId = $this->publication->branch_id;
        $branchId === null ? $context->runAsSystem($run) : $context->runForBranch($branchId, $run);
    }
}