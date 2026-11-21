<?php

declare(strict_types=1);

namespace Karnoweb\Omnichannel\Contracts;

use Karnoweb\Omnichannel\DTOs\PublishableProduct;
use Karnoweb\Omnichannel\Exceptions\ProductNotFoundException;

interface ProductRepository
{
    /**
     * @return list<PublishableProduct>
     */
    public function all(int $limit = 100): array;

    /**
     * @return list<PublishableProduct>
     */
    public function search(string $term = '', int $limit = 50): array;

    public function find(string $id): ?PublishableProduct;

    /**
     * @throws ProductNotFoundException
     */
    public function findOrFail(string $id): PublishableProduct;

    /**
     * @return array<string, string>
     */
    public function options(): array;
}
