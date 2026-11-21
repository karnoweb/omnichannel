<?php

declare(strict_types=1);

namespace Karnoweb\Omnichannel\Channels\Instagram\Transformers;

use Karnoweb\Omnichannel\Contracts\ContentTransformer;
use Karnoweb\Omnichannel\Enums\PublicationType;
use Karnoweb\Omnichannel\Models\Publication;
use RuntimeException;

final class InstagramPostTransformer implements ContentTransformer
{
    public function supports(PublicationType $type): bool
    {
        return $type === PublicationType::Post;
    }

    /**
     * @return array<string, mixed>
     */
    public function transform(Publication $publication): array
    {
        $content = $publication->content();
        $media = $content->firstMedia();

        if ($media === null) {
            throw new RuntimeException('An Instagram post requires one media item.');
        }

        $payload = ['caption' => $content->captionWithHashtags()];

        if ($media->isVideo()) {
            $payload['media_type'] = 'VIDEO';
            $payload['video_url'] = $media->url;

            if ($media->thumbnailUrl !== null) {
                $payload['cover_url'] = $media->thumbnailUrl;
            }
        } else {
            $payload['image_url'] = $media->url;

            if ($media->altText !== null && $media->altText !== '') {
                $payload['alt_text'] = $media->altText;
            }
        }

        return array_filter($payload, static fn (mixed $value): bool => $value !== '');
    }
}
