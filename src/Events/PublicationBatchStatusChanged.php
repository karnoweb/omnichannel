<?php

declare(strict_types=1);

namespace Karnoweb\Omnichannel\Events;

use Karnoweb\Omnichannel\Enums\PublicationBatchStatus;
use Karnoweb\Omnichannel\Models\PublicationBatch;

final readonly class PublicationBatchStatusChanged
{
    public function __construct(
        public PublicationBatch $batch,
        public PublicationBatchStatus $previous,
        public PublicationBatchStatus $current,
    ) {}
}