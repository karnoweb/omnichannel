<?php

declare(strict_types=1);

namespace Karnoweb\Omnichannel\Contracts;

use Karnoweb\Omnichannel\DTOs\PublishableProduct;
use Karnoweb\Omnichannel\Exceptions\ProductNotFoundException;

interface BranchScopedProductRepository extends ProductRepository
{
    /**
     * @throws ProductNotFoundException
     */
    public function findOrFailForBranch(string $id, int|string $branchId): PublishableProduct;
}
