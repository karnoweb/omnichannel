<?php

declare(strict_types=1);

namespace Karnoweb\Omnichannel\Channels\Instagram\Transformers;

use Karnoweb\Omnichannel\Contracts\ContentTransformer;
use Karnoweb\Omnichannel\Enums\PublicationType;
use Karnoweb\Omnichannel\Models\Publication;
use RuntimeException;

final class InstagramReelTransformer implements ContentTransformer
{
    public function supports(PublicationType $type): bool
    {
        return $type === PublicationType::Reel;
    }

    /**
     * @return array<string, mixed>
     */
    public function transform(Publication $publication): array
    {
        $content = $publication->content();
        $video = $content->videos()[0] ?? null;

        if ($video === null) {
            throw new RuntimeException('An Instagram reel requires one video.');
        }

        $payload = [
            'media_type' => 'REELS',
            'video_url' => $video->url,
            'caption' => $content->captionWithHashtags(),
            'share_to_feed' => (bool) ($content->extra('instagram.share_to_feed') ?? true),
        ];

        if ($video->thumbnailUrl !== null) {
            $payload['cover_url'] = $video->thumbnailUrl;
        }

        $thumbOffset = $content->extra('instagram.thumb_offset');

        if (is_numeric($thumbOffset)) {
            $payload['thumb_offset'] = (int) $thumbOffset;
        }

        $audioName = $content->extra('instagram.audio_name');

        if (is_string($audioName) && $audioName !== '') {
            $payload['audio_name'] = $audioName;
        }

        return $payload;
    }
}
