<?php

declare(strict_types=1);

namespace Karnoweb\Omnichannel\Contracts;

interface InternalCategoryResolverContract
{
    public function existsForBranch(string $internalCategoryId, int|string $branchId): bool;
}