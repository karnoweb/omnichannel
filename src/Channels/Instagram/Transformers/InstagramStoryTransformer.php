<?php

declare(strict_types=1);

namespace Karnoweb\Omnichannel\Channels\Instagram\Transformers;

use Karnoweb\Omnichannel\Contracts\ContentTransformer;
use Karnoweb\Omnichannel\Enums\PublicationType;
use Karnoweb\Omnichannel\Models\Publication;
use RuntimeException;

final class InstagramStoryTransformer implements ContentTransformer
{
    public function supports(PublicationType $type): bool
    {
        return $type === PublicationType::Story;
    }

    /**
     * @return array<string, mixed>
     */
    public function transform(Publication $publication): array
    {
        $media = $publication->content()->firstMedia();

        if ($media === null) {
            throw new RuntimeException('An Instagram story requires one media item.');
        }

        $payload = ['media_type' => 'STORIES'];

        if ($media->isVideo()) {
            $payload['video_url'] = $media->url;
        } else {
            $payload['image_url'] = $media->url;
        }

        return $payload;
    }
}
