<?php

declare(strict_types=1);

namespace Karnoweb\Omnichannel\Contracts;

use Karnoweb\Omnichannel\Models\Publication;
use Karnoweb\Omnichannel\Results\PublishResult;

/** Optional contract for channel drivers that can deactivate an external listing. */
interface DeactivatableChannelDriver
{
    public function deactivate(Publication $publication): PublishResult;
}
