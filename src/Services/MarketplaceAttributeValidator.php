<?php

declare(strict_types=1);

namespace Karnoweb\Omnichannel\Services;

use RuntimeException;

final class MarketplaceAttributeValidator
{
    /**
     * @param  array<string, mixed>|null  $attributeMappings
     * @param  array<string, mixed>  $productAttributes
     * @return array<string, mixed>
     */
    public function mapAttributes(?array $attributeMappings, array $productAttributes): array
    {
        if ($attributeMappings === null || $attributeMappings === []) {
            return [];
        }
        $mapped = [];
        foreach ($attributeMappings as $externalKey => $rule) {
            if (is_string($rule)) {
                $value = data_get($productAttributes, $rule);
            } elseif (is_array($rule) && isset($rule['source'])) {
                $value = data_get($productAttributes, (string) $rule['source']);
            } elseif (is_array($rule) && array_key_exists('default', $rule)) {
                $value = $rule['default'];
            } else {
                continue;
            }
            if ($value === null && ($rule['required'] ?? false) === true) {
                throw new RuntimeException('Required marketplace attribute ['.$externalKey.'] is missing.');
            }
            if ($value !== null) {
                $mapped[(string) $externalKey] = $value;
            }
        }

        return $mapped;
    }
}
