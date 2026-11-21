<?php

declare(strict_types=1);

namespace Karnoweb\Omnichannel\DTOs;

use Illuminate\Contracts\Support\Arrayable;
use Karnoweb\Omnichannel\Enums\MediaType;
use Karnoweb\Omnichannel\ValueObjects\MediaItem;
use Karnoweb\Omnichannel\ValueObjects\PublicationContent;

/**
 * @implements Arrayable<string, mixed>
 */
final readonly class PublishableProduct implements Arrayable
{
    /**
     * @param list<MediaItem> $images
     * @param list<MediaItem> $videos
     * @param list<string>    $tags
     */
    public function __construct(
        public string $id,
        public string $name,
        public string $description,
        public int $price,
        public int $stock,
        public string $category,
        public array $images = [],
        public array $videos = [],
        public array $tags = [],
        public ?string $sku = null,
        public string $currency = 'IRR',
        public ?string $brand = null,
        public ?string $link = null,
    ) {}

    /**
     * @param array<string, mixed> $data
     */
    public static function fromArray(array $data): self
    {
        return new self(
            id: (string) ($data['id'] ?? ''),
            name: (string) ($data['name'] ?? ''),
            description: (string) ($data['description'] ?? ''),
            price: (int) ($data['price'] ?? 0),
            stock: (int) ($data['stock'] ?? 0),
            category: (string) ($data['category'] ?? ''),
            images: self::mediaList($data['images'] ?? [], MediaType::Image),
            videos: self::mediaList($data['videos'] ?? [], MediaType::Video),
            tags: array_values(array_map('strval', (array) ($data['tags'] ?? []))),
            sku: isset($data['sku']) ? (string) $data['sku'] : null,
            currency: (string) ($data['currency'] ?? 'IRR'),
            brand: isset($data['brand']) && $data['brand'] !== '' ? (string) $data['brand'] : null,
            link: isset($data['link']) && $data['link'] !== '' ? (string) $data['link'] : null,
        );
    }

    /**
     * @param array<int, mixed> $items
     *
     * @return list<MediaItem>
     */
    private static function mediaList(mixed $items, MediaType $type): array
    {
        $result = [];

        foreach ((array) $items as $item) {
            if (is_string($item)) {
                $result[] = new MediaItem(type: $type, url: $item);

                continue;
            }

            if (is_array($item)) {
                $result[] = MediaItem::fromArray([...$item, 'type' => $item['type'] ?? $type->value]);
            }
        }

        return $result;
    }

    /**
     * @return list<MediaItem>
     */
    public function allMedia(): array
    {
        return [...$this->images, ...$this->videos];
    }

    public function hasVideo(): bool
    {
        return $this->videos !== [];
    }

    public function primaryImage(): ?MediaItem
    {
        return $this->images[0] ?? null;
    }

    public function formattedPrice(): string
    {
        return number_format($this->price) . ' ' . $this->currency;
    }

    public function toDefaultContent(): PublicationContent
    {
        $extra = array_filter([
            'price' => $this->price,
            'currency' => $this->currency,
            'stock' => $this->stock,
            'category' => $this->category !== '' ? $this->category : null,
            'sku' => $this->sku,
            'whatsapp' => array_filter([
                'brand' => $this->brand,
                'link' => $this->link,
            ], static fn (mixed $v): bool => $v !== null && $v !== ''),
        ], static fn (mixed $v): bool => $v !== null && $v !== []);

        return new PublicationContent(
            caption: trim($this->name . ($this->description !== '' ? "\n\n" . $this->description : '')),
            media: $this->allMedia(),
            hashtags: $this->tags,
            title: $this->name,
            extra: $extra,
        );
    }

    /**
     * @return array<string, mixed>
     */
    public function toArray(): array
    {
        return [
            'id' => $this->id,
            'name' => $this->name,
            'description' => $this->description,
            'price' => $this->price,
            'currency' => $this->currency,
            'stock' => $this->stock,
            'category' => $this->category,
            'sku' => $this->sku,
            'brand' => $this->brand,
            'link' => $this->link,
            'tags' => $this->tags,
            'images' => array_map(static fn (MediaItem $m): array => $m->toArray(), $this->images),
            'videos' => array_map(static fn (MediaItem $m): array => $m->toArray(), $this->videos),
        ];
    }
}
