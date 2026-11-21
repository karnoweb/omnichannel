<?php

declare(strict_types=1);

namespace Karnoweb\Omnichannel\Exceptions;

use RuntimeException;

final class ProductNotFoundException extends RuntimeException
{
    public function __construct(public readonly string $productId)
    {
        parent::__construct("No publishable product found with id [{$productId}].");
    }
}
