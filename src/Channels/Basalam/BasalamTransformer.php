<?php

declare(strict_types=1);

namespace Karnoweb\Omnichannel\Channels\Basalam;

use Karnoweb\Omnichannel\Contracts\ContentTransformer;
use Karnoweb\Omnichannel\Enums\PublicationType;
use Karnoweb\Omnichannel\Models\Publication;
use RuntimeException;

final class BasalamTransformer implements ContentTransformer
{
    public function supports(PublicationType $type): bool
    {
        return $type === PublicationType::Product;
    }

    /**
     * @return array<string, mixed>
     */
    public function transform(Publication $publication): array
    {
        /** @var list<int> $photoIds */
        $photoIds = (array) data_get($publication->metadata, 'basalam.photo_ids', []);
        $videoId = data_get($publication->metadata, 'basalam.video_id');
        $categoryId = data_get($publication->metadata, 'basalam.category_id');

        if (! is_numeric($categoryId)) {
            throw new RuntimeException('A Basalam product needs a resolved category id before it can be built.');
        }

        return $this->build($publication, $photoIds, is_numeric($videoId) ? (int) $videoId : null, (int) $categoryId);
    }

    /**
     * @param  list<int>  $photoIds
     * @return array<string, mixed>
     */
    public function build(Publication $publication, array $photoIds, ?int $videoId, int $categoryId): array
    {
        $content = $publication->content();
        $limits = $this->limits();
        $defaults = $this->defaults();

        $name = $content->title ?? $publication->product_name ?? '';
        $description = $content->caption;

        $payload = [
            'name' => $this->truncate($name, (int) $limits['name_max_chars']),
            'category_id' => $categoryId,
            'status' => $this->status($publication),
            'preparation_days' => (int) ($content->extra('basalam.preparation_days') ?? $defaults['preparation_days']),
            'package_weight' => (int) ($content->extra('basalam.package_weight') ?? $defaults['package_weight']),
            'brief' => $this->truncate($this->brief($description), (int) $limits['brief_max_chars']),
            'description' => $this->truncate($description, (int) $limits['description_max_chars']),
            'keywords' => array_slice($content->hashtags, 0, 10),
        ];

        if ($photoIds !== []) {
            $payload['photo'] = $photoIds[0];
            $payload['photos'] = array_slice($photoIds, 0, (int) $limits['max_photos']);
        }

        if ($videoId !== null) {
            $payload['video'] = $videoId;
        }

        $price = $content->extra('price');

        if (is_numeric($price)) {
            $payload['primary_price'] = (int) $price;
        }

        $stock = $content->extra('stock');

        if (is_numeric($stock)) {
            $payload['stock'] = (int) $stock;
        }

        $sku = $content->extra('sku');

        if (is_string($sku) && $sku !== '') {
            $payload['sku'] = $sku;
        }

        return array_filter(
            $payload,
            static fn (mixed $value): bool => $value !== '' && $value !== []
        );
    }

    /**
     * @return array<string, mixed>
     */
    public function buildUpdate(Publication $publication): array
    {
        $content = $publication->content();
        $limits = $this->limits();

        $payload = [
            'name' => $this->truncate($content->title ?? $publication->product_name ?? '', (int) $limits['name_max_chars']),
            'brief' => $this->truncate($this->brief($content->caption), (int) $limits['brief_max_chars']),
            'description' => $this->truncate($content->caption, (int) $limits['description_max_chars']),
            'keywords' => array_slice($content->hashtags, 0, 10),
        ];

        $price = $content->extra('price');

        if (is_numeric($price)) {
            $payload['primary_price'] = (int) $price;
        }

        $stock = $content->extra('stock');

        if (is_numeric($stock)) {
            $payload['stock'] = (int) $stock;
        }

        return array_filter(
            $payload,
            static fn (mixed $value): bool => $value !== '' && $value !== []
        );
    }

    /**
     * @return array<string, mixed>
     */
    public function buildDeactivate(): array
    {
        return ['status' => (int) config('omnichannel.channels.basalam.product_status.draft')];
    }

    private function status(Publication $publication): int
    {
        $requested = $publication->content()->extra('basalam.status');

        if (is_numeric($requested)) {
            return (int) $requested;
        }

        return (int) config('omnichannel.channels.basalam.product_status.published');
    }

    private function brief(string $description): string
    {
        $firstLine = trim(strtok($description, "\n") ?: '');

        if ($firstLine === '') {
            return '';
        }

        if (preg_match('/^(.*?[.!?؟])\s/u', $firstLine.' ', $matches) === 1) {
            return trim($matches[1]);
        }

        return $firstLine;
    }

    private function truncate(string $value, int $max): string
    {
        return mb_strlen($value) <= $max ? $value : mb_substr($value, 0, $max - 1).'…';
    }

    /**
     * @return array<string, mixed>
     */
    private function limits(): array
    {
        /** @var array<string, mixed> $limits */
        $limits = config('omnichannel.channels.basalam.limits', []);

        return $limits;
    }

    /**
     * @return array<string, mixed>
     */
    private function defaults(): array
    {
        /** @var array<string, mixed> $defaults */
        $defaults = config('omnichannel.channels.basalam.defaults', []);

        return $defaults;
    }
}
