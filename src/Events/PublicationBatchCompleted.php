<?php

declare(strict_types=1);

namespace Karnoweb\Omnichannel\Events;

use Karnoweb\Omnichannel\Models\PublicationBatch;

final readonly class PublicationBatchCompleted
{
    public function __construct(public PublicationBatch $batch) {}
}
