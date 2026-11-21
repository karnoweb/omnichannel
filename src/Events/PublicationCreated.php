<?php

declare(strict_types=1);

namespace Karnoweb\Omnichannel\Events;

use Karnoweb\Omnichannel\Models\Publication;

final readonly class PublicationCreated
{
    public function __construct(public Publication $publication) {}
}
