<?php

declare(strict_types=1);

namespace Karnoweb\Omnichannel\Contracts;

use Karnoweb\Omnichannel\Models\Publication;
use Karnoweb\Omnichannel\Results\ReconciliationResult;

interface ReconcilableChannelDriver extends ChannelDriver
{
    public function reconcilePublication(Publication $publication, string $operation): ReconciliationResult;
}
