<?php

declare(strict_types=1);

namespace Karnoweb\Omnichannel\Channels\Basalam;

use Karnoweb\Omnichannel\Models\ChannelConnection;
use Karnoweb\Omnichannel\Services\CategoryMappingResolver;
use Karnoweb\Omnichannel\Support\OmnichannelContext;

final class BasalamCategoryMapper
{
    public function __construct(
        private readonly ?CategoryMappingResolver $mappings = null,
        private readonly ?OmnichannelContext $context = null,
    ) {}

    public function resolve(
        string $internalCategory,
        ?ChannelConnection $connection = null,
        int|string|null $explicitOverride = null,
    ): ?int {
        if (is_numeric($explicitOverride)) {
            return (int) $explicitOverride;
        }

        if ($connection !== null && $this->mappings !== null) {
            $branchId = $this->context?->branchId() ?? $connection->branch_id;
            $mapped = $this->mappings->resolveExternalCategoryId($internalCategory, $connection, $branchId);
            if ($mapped !== null) {
                return $mapped;
            }
        }

        if ($connection !== null) {
            $perConnection = data_get($connection->metadata, 'category_map.' . $internalCategory);

            if (is_numeric($perConnection)) {
                return (int) $perConnection;
            }
        }

        /** @var array<string, mixed> $map */
        $map = config('omnichannel.channels.basalam.category_map', []);

        if (isset($map[$internalCategory]) && is_numeric($map[$internalCategory])) {
            return (int) $map[$internalCategory];
        }

        $default = config('omnichannel.channels.basalam.defaults.category_id');

        return is_numeric($default) ? (int) $default : null;
    }

    /**
     * @return list<string>
     */
    public function mappedCategories(?ChannelConnection $connection = null): array
    {
        /** @var array<string, mixed> $global */
        $global = config('omnichannel.channels.basalam.category_map', []);

        /** @var array<string, mixed> $perConnection */
        $perConnection = (array) data_get($connection?->metadata, 'category_map', []);

        $mapped = [];

        foreach ([...$global, ...$perConnection] as $name => $id) {
            if (is_numeric($id)) {
                $mapped[] = (string) $name;
            }
        }

        return array_values(array_unique($mapped));
    }
}
