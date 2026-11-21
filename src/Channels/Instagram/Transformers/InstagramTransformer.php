<?php

declare(strict_types=1);

namespace Karnoweb\Omnichannel\Channels\Instagram\Transformers;

use Karnoweb\Omnichannel\Contracts\ContentTransformer;
use Karnoweb\Omnichannel\Enums\PublicationType;
use Karnoweb\Omnichannel\Models\Publication;
use RuntimeException;

final class InstagramTransformer implements ContentTransformer
{
    /** @var array<string, ContentTransformer> */
    private readonly array $transformers;

    public function __construct(
        InstagramPostTransformer $post,
        InstagramCarouselTransformer $carousel,
        InstagramReelTransformer $reel,
        InstagramStoryTransformer $story,
    ) {
        $this->transformers = [
            PublicationType::Post->value => $post,
            PublicationType::Carousel->value => $carousel,
            PublicationType::Reel->value => $reel,
            PublicationType::Story->value => $story,
        ];
    }

    public function supports(PublicationType $type): bool
    {
        return isset($this->transformers[$type->value]);
    }

    /**
     * @return array<string, mixed>
     */
    public function transform(Publication $publication): array
    {
        return $this->for($publication->type)->transform($publication);
    }

    public function for(PublicationType $type): ContentTransformer
    {
        return $this->transformers[$type->value]
            ?? throw new RuntimeException("No Instagram transformer registered for [{$type->value}].");
    }

    public function carousel(): InstagramCarouselTransformer
    {
        /** @var InstagramCarouselTransformer $transformer */
        $transformer = $this->transformers[PublicationType::Carousel->value];

        return $transformer;
    }
}
