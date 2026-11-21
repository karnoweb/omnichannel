<?php

declare(strict_types=1);

namespace Karnoweb\Omnichannel\Channels\WhatsApp;

use Karnoweb\Omnichannel\Contracts\ContentTransformer;
use Karnoweb\Omnichannel\Enums\PublicationType;
use Karnoweb\Omnichannel\Models\Publication;
use Karnoweb\Omnichannel\ValueObjects\MediaItem;

final class WhatsAppTransformer implements ContentTransformer
{
    /** @var list<string> */
    private const AVAILABILITY_VALUES = [
        'in stock',
        'out of stock',
        'available for order',
        'discontinued',
    ];

    /** @var list<string> */
    private const CONDITION_VALUES = ['new', 'refurbished', 'used'];

    public function supports(PublicationType $type): bool
    {
        return $type === PublicationType::Product;
    }

    /**
     * @return array<string, mixed>
     */
    public function transform(Publication $publication): array
    {
        $content = $publication->content();
        $limits = $this->limits();

        $images = array_map(
            static fn (MediaItem $item): array => ['url' => $item->url],
            array_slice($content->images(), 0, (int) ($limits['max_images'] ?? 21)),
        );

        $data = [
            'id' => $this->retailerId($publication),
            'title' => $this->truncate(
                $content->title ?? $publication->product_name ?? '',
                (int) ($limits['title_max_chars'] ?? 200),
            ),
            'description' => $this->truncate(
                $content->caption,
                (int) ($limits['description_max_chars'] ?? 9999),
            ),
            'availability' => $this->availability($publication),
            'condition' => $this->condition($publication),
            'brand' => $this->brand($publication),
            'price' => $this->price($publication),
            'link' => $this->link($publication),
        ];

        if ($images !== []) {
            $data['image'] = $images;
            $data['image_link'] = $images[0]['url'];
        }

        $currency = $content->extra('currency');

        if (is_string($currency) && $currency !== '') {
            $data['currency'] = mb_strtoupper($currency);
        }

        $inventory = $content->extra('stock');

        if (is_numeric($inventory)) {
            $data['inventory'] = (int) $inventory;
        }

        $category = $content->extra('category');

        if (is_string($category) && $category !== '') {
            $data['category'] = $category;
        }

        return array_filter($data, static fn (mixed $value): bool => $value !== '');
    }

    /**
     * @return array<string, mixed>
     */
    public function buildDelete(Publication $publication): array
    {
        return ['id' => $this->retailerId($publication)];
    }

    public function retailerId(Publication $publication): string
    {
        $configured = $publication->content()->extra('whatsapp.retailer_id');

        if (is_string($configured) && $configured !== '') {
            return $configured;
        }

        $prefix = (string) config('omnichannel.channels.whatsapp.retailer_id_prefix', 'sku');

        return $prefix . '-' . $publication->product_id;
    }

    private function availability(Publication $publication): string
    {
        $explicit = $publication->content()->extra('whatsapp.availability');

        if (is_string($explicit) && in_array($explicit, self::AVAILABILITY_VALUES, true)) {
            return $explicit;
        }

        $stock = $publication->content()->extra('stock');

        if (is_numeric($stock)) {
            return (int) $stock > 0 ? 'in stock' : 'out of stock';
        }

        return 'in stock';
    }

    private function condition(Publication $publication): string
    {
        $explicit = $publication->content()->extra('whatsapp.condition');

        return is_string($explicit) && in_array($explicit, self::CONDITION_VALUES, true)
            ? $explicit
            : 'new';
    }

    private function brand(Publication $publication): string
    {
        $brand = $publication->content()->extra('whatsapp.brand')
            ?? config('omnichannel.channels.whatsapp.default_brand');

        return is_string($brand) && $brand !== '' ? $brand : '';
    }

    private function price(Publication $publication): string
    {
        $content = $publication->content();
        $amount = $content->extra('price');
        $currency = $content->extra('currency', config('omnichannel.channels.whatsapp.default_currency'));

        if (! is_numeric($amount) || ! is_string($currency) || $currency === '') {
            return '';
        }

        $zeroDecimal = (array) config('omnichannel.channels.whatsapp.zero_decimal_currencies', []);
        $code = mb_strtoupper($currency);

        $formatted = in_array($code, $zeroDecimal, true)
            ? (string) (int) $amount
            : number_format((float) $amount, 2, '.', '');

        return "{$formatted} {$code}";
    }

    private function link(Publication $publication): string
    {
        $explicit = $publication->content()->extra('whatsapp.link');

        if (is_string($explicit) && $explicit !== '') {
            return $explicit;
        }

        $base = config('omnichannel.channels.whatsapp.product_link_base');

        return is_string($base) && $base !== ''
            ? rtrim($base, '/') . '/' . $publication->product_id
            : '';
    }

    private function truncate(string $value, int $max): string
    {
        return mb_strlen($value) <= $max ? $value : mb_substr($value, 0, $max - 1) . '…';
    }

    /**
     * @return array<string, mixed>
     */
    private function limits(): array
    {
        /** @var array<string, mixed> $limits */
        $limits = config('omnichannel.channels.whatsapp.limits', []);

        return $limits;
    }
}
