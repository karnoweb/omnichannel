<?php

declare(strict_types=1);

namespace Karnoweb\Omnichannel\Contracts;

interface MediaBytesResolverContract
{
    public function fetch(string $url): ?string;
}
