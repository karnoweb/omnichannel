<?php

declare(strict_types=1);

namespace Karnoweb\Omnichannel\Services;

final class CatalogSyncFingerprint
{
    /**
     * @param  array<string, mixed>  $snapshot
     * @return array{price_hash: ?int, stock_hash: ?int, revision: string, canonical: array<string, mixed>}
     */
    public static function fromSnapshot(array $snapshot): array
    {
        $canonical = self::canonicalSnapshot($snapshot);

        return [
            'price_hash' => array_key_exists('price', $canonical)
                ? crc32('price:'.json_encode($canonical['price'], JSON_THROW_ON_ERROR))
                : null,
            'stock_hash' => array_key_exists('stock', $canonical)
                ? crc32('stock:'.json_encode($canonical['stock'], JSON_THROW_ON_ERROR))
                : null,
            'revision' => hash('sha256', json_encode($canonical, JSON_THROW_ON_ERROR)),
            'canonical' => $canonical,
        ];
    }

    /**
     * @param  array<string, mixed>  $snapshot
     * @return array<string, mixed>
     */
    public static function canonicalSnapshot(array $snapshot): array
    {
        $canonical = [];
        foreach (['currency', 'price', 'stock'] as $key) {
            if (array_key_exists($key, $snapshot)) {
                $canonical[$key] = $snapshot[$key];
            }
        }

        return $canonical;
    }
}
