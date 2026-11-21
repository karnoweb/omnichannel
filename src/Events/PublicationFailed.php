<?php

declare(strict_types=1);

namespace Karnoweb\Omnichannel\Events;

use Karnoweb\Omnichannel\Models\Publication;
use Karnoweb\Omnichannel\Results\PublishResult;

final readonly class PublicationFailed
{
    public function __construct(
        public Publication $publication,
        public PublishResult $result,
    ) {}
}
