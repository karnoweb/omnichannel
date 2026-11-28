<?php

declare(strict_types=1);

namespace Karnoweb\Omnichannel\Services;

use Karnoweb\Omnichannel\Models\Publication;
use Karnoweb\Omnichannel\ValueObjects\PublicationContent;

final class CatalogSyncSnapshotApplier
{
    /**
     * @param  array{price?: int|float|null, stock?: int|null, currency?: string|null}  $snapshot
     */
    public function apply(Publication $publication, array $snapshot): void
    {
        $content = $publication->content();
        $extra = $content->extra;

        foreach (['price', 'stock', 'currency'] as $field) {
            if (array_key_exists($field, $snapshot)) {
                $extra[$field] = $snapshot[$field];
            }
        }

        $publication->setContent(new PublicationContent(
            caption: $content->caption,
            media: $content->media,
            hashtags: $content->hashtags,
            title: $content->title,
            extra: $extra,
        ));
    }

    /**
     * @param  array<string, mixed>  $snapshot
     */
    public function hasCommerceFields(array $snapshot): bool
    {
        return array_key_exists('price', $snapshot) || array_key_exists('stock', $snapshot);
    }
}
