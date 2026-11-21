<?php

declare(strict_types=1);

namespace Karnoweb\Omnichannel\Channels\Instagram\Transformers;

use Karnoweb\Omnichannel\Contracts\ContentTransformer;
use Karnoweb\Omnichannel\Enums\PublicationType;
use Karnoweb\Omnichannel\Models\Publication;
use Karnoweb\Omnichannel\ValueObjects\MediaItem;

final class InstagramCarouselTransformer implements ContentTransformer
{
    public function supports(PublicationType $type): bool
    {
        return $type === PublicationType::Carousel;
    }

    /**
     * @return array<string, mixed>
     */
    public function transform(Publication $publication): array
    {
        /** @var list<string> $childIds */
        $childIds = (array) data_get($publication->metadata, 'instagram.carousel_children', []);

        return $this->parentPayload($publication, $childIds);
    }

    /**
     * @param list<string> $childContainerIds
     *
     * @return array<string, mixed>
     */
    public function parentPayload(Publication $publication, array $childContainerIds): array
    {
        return [
            'media_type' => 'CAROUSEL',
            'children' => implode(',', $childContainerIds),
            'caption' => $publication->content()->captionWithHashtags(),
        ];
    }

    /**
     * @return array<string, mixed>
     */
    public function childPayload(MediaItem $item): array
    {
        $payload = ['is_carousel_item' => true];

        if ($item->isVideo()) {
            $payload['media_type'] = 'VIDEO';
            $payload['video_url'] = $item->url;
        } else {
            $payload['image_url'] = $item->url;

            if ($item->altText !== null && $item->altText !== '') {
                $payload['alt_text'] = $item->altText;
            }
        }

        return $payload;
    }

    /**
     * @return list<array<string, mixed>>
     */
    public function childPayloads(Publication $publication): array
    {
        return array_map(
            fn (MediaItem $item): array => $this->childPayload($item),
            $publication->content()->media,
        );
    }
}
