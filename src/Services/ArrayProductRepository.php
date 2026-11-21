<?php

declare(strict_types=1);

namespace Karnoweb\Omnichannel\Services;

use Karnoweb\Omnichannel\Contracts\BranchScopedProductRepository;
use Karnoweb\Omnichannel\DTOs\PublishableProduct;
use Karnoweb\Omnichannel\Exceptions\ProductNotFoundException;
use Karnoweb\Omnichannel\Support\OmnichannelContext;

final class ArrayProductRepository implements BranchScopedProductRepository
{
    /**
     * @param list<array<string, mixed>> $products
     */
    public function __construct(
        private readonly array $products = [],
        private readonly ?OmnichannelContext $context = null,
    ) {}

    /**
     * @return list<PublishableProduct>
     */
    public function all(int $limit = 100): array
    {
        return array_slice(array_values($this->indexed()), 0, $limit);
    }

    /**
     * @return list<PublishableProduct>
     */
    public function search(string $term = '', int $limit = 50): array
    {
        $needle = trim(mb_strtolower($term));
        if ($needle === '') {
            return $this->all($limit);
        }

        $matches = array_values(array_filter(
            $this->indexed(),
            static fn (PublishableProduct $product): bool => str_contains(mb_strtolower($product->name), $needle)
                || ($product->sku !== null && str_contains(mb_strtolower($product->sku), $needle))
        ));

        return array_slice($matches, 0, $limit);
    }

    public function find(string $id): ?PublishableProduct
    {
        return $this->indexed()[$id] ?? null;
    }

    public function findOrFail(string $id): PublishableProduct
    {
        return $this->find($id) ?? throw new ProductNotFoundException($id);
    }

    public function findOrFailForBranch(string $id, int|string $branchId): PublishableProduct
    {
        $row = $this->indexedRows($branchId)[$id] ?? null;
        if (! is_array($row) || (string) ($row['branch_id'] ?? '') !== (string) $branchId) {
            throw new ProductNotFoundException($id);
        }

        return PublishableProduct::fromArray($row);
    }

    /**
     * @return array<string, string>
     */
    public function options(): array
    {
        $options = [];

        foreach ($this->indexed() as $id => $product) {
            $options[$id] = $product->name;
        }

        return $options;
    }

    /**
     * @return array<string, PublishableProduct>
     */
    private function indexed(): array
    {
        $indexed = [];
        $branchId = $this->context?->branchId();
        if ($branchId === null) {
            return [];
        }

        foreach ($this->products as $row) {
            if ((string) ($row['branch_id'] ?? '') !== (string) $branchId) {
                continue;
            }
            $product = PublishableProduct::fromArray($row);
            $indexed[$product->id] = $product;
        }

        return $indexed;
    }

    /**
     * @return array<string, array<string, mixed>>
     */
    private function indexedRows(int|string $branchId): array
    {
        $rows = [];
        foreach ($this->products as $row) {
            if ((string) ($row['branch_id'] ?? '') !== (string) $branchId) {
                continue;
            }
            $product = PublishableProduct::fromArray($row);
            $rows[$product->id] = $row;
        }

        return $rows;
    }
}
