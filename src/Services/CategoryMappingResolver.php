<?php

declare(strict_types=1);

namespace Karnoweb\Omnichannel\Services;

use Karnoweb\Omnichannel\Models\CategoryMapping;
use Karnoweb\Omnichannel\Models\ChannelConnection;

final class CategoryMappingResolver
{
    public function resolveExternalCategoryId(
        string $internalCategoryId,
        ChannelConnection $connection,
        int|string $branchId,
    ): ?int {
        $mapping = CategoryMapping::withoutGlobalScopes()->newQuery()
            ->where('branch_id', $branchId)
            ->where('channel_connection_id', $connection->getKey())
            ->where('internal_category_id', $internalCategoryId)
            ->where('is_active', true)
            ->with('externalCategory')
            ->first();
        if ($mapping === null || $mapping->externalCategory === null || ! $mapping->externalCategory->is_active) {
            return null;
        }
        if (! is_numeric($mapping->externalCategory->external_id)) {
            return null;
        }

        return (int) $mapping->externalCategory->external_id;
    }

    /**
     * @return array<string, mixed>|null
     */
    public function attributeMappings(
        string $internalCategoryId,
        ChannelConnection $connection,
        int|string $branchId,
    ): ?array {
        $mapping = CategoryMapping::withoutGlobalScopes()->newQuery()
            ->where('branch_id', $branchId)
            ->where('channel_connection_id', $connection->getKey())
            ->where('internal_category_id', $internalCategoryId)
            ->where('is_active', true)
            ->first();

        return $mapping?->attribute_mappings;
    }
}