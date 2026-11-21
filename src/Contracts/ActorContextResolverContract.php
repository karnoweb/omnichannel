<?php

declare(strict_types=1);

namespace Karnoweb\Omnichannel\Contracts;

interface ActorContextResolverContract
{
    public function userId(): int|string|null;
}
