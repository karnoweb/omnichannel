<?php

declare(strict_types=1);

namespace Karnoweb\Omnichannel\Contracts;

interface BranchContextResolverContract
{
    public function branchId(): int|string|null;
}
