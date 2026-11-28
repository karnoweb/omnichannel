<?php

declare(strict_types=1);

namespace Karnoweb\Omnichannel\ValueObjects;

use Illuminate\Contracts\Support\Arrayable;
use Karnoweb\Omnichannel\Enums\MediaType;

/**
 * @implements Arrayable<string, mixed>
 */
final readonly class PublicationContent implements Arrayable
{
    /**
     * @param  list<MediaItem>  $media
     * @param  list<string>  $hashtags
     * @param  array<string, mixed>  $extra
     */
    public function __construct(
        public string $caption = '',
        public array $media = [],
        public array $hashtags = [],
        public ?string $title = null,
        public array $extra = [],
    ) {}

    /**
     * @param  array<string, mixed>  $data
     */
    public static function fromArray(array $data): self
    {
        $media = [];

        foreach ((array) ($data['media'] ?? []) as $item) {
            if (is_array($item)) {
                $media[] = MediaItem::fromArray($item);
            }
        }

        return new self(
            caption: (string) ($data['caption'] ?? ''),
            media: $media,
            hashtags: self::normalizeHashtags((array) ($data['hashtags'] ?? [])),
            title: isset($data['title']) && $data['title'] !== '' ? (string) $data['title'] : null,
            extra: (array) ($data['extra'] ?? []),
        );
    }

    public static function empty(): self
    {
        return new self;
    }

    /**
     * @param  array<int|string, mixed>  $hashtags
     * @return list<string>
     */
    public static function normalizeHashtags(array $hashtags): array
    {
        $result = [];
        $seen = [];

        foreach ($hashtags as $tag) {
            if (! is_string($tag) && ! is_numeric($tag)) {
                continue;
            }

            $clean = trim(ltrim(trim((string) $tag), '#'));

            if ($clean === '') {
                continue;
            }

            $key = mb_strtolower($clean);

            if (isset($seen[$key])) {
                continue;
            }

            $seen[$key] = true;
            $result[] = $clean;
        }

        return $result;
    }

    /**
     * @return list<MediaItem>
     */
    public function mediaOfType(MediaType $type): array
    {
        return array_values(array_filter(
            $this->media,
            fn (MediaItem $item): bool => $item->type === $type
        ));
    }

    /**
     * @return list<MediaItem>
     */
    public function images(): array
    {
        return $this->mediaOfType(MediaType::Image);
    }

    /**
     * @return list<MediaItem>
     */
    public function videos(): array
    {
        return $this->mediaOfType(MediaType::Video);
    }

    public function firstMedia(): ?MediaItem
    {
        return $this->media[0] ?? null;
    }

    public function mediaCount(): int
    {
        return count($this->media);
    }

    public function hasMedia(): bool
    {
        return $this->media !== [];
    }

    public function captionWithHashtags(): string
    {
        if ($this->hashtags === []) {
            return $this->caption;
        }

        $missing = array_values(array_filter(
            $this->hashtags,
            fn (string $tag): bool => ! preg_match('/#'.preg_quote($tag, '/').'\b/iu', $this->caption)
        ));

        if ($missing === []) {
            return $this->caption;
        }

        $tags = implode(' ', array_map(static fn (string $tag): string => '#'.$tag, $missing));

        return trim($this->caption) === '' ? $tags : trim($this->caption)."\n\n".$tags;
    }

    public function totalHashtagCount(): int
    {
        preg_match_all('/#[\p{L}\p{N}_]+/u', $this->captionWithHashtags(), $matches);

        return count($matches[0]);
    }

    public function totalMentionCount(): int
    {
        preg_match_all('/@[\p{L}\p{N}_.]+/u', $this->captionWithHashtags(), $matches);

        return count($matches[0]);
    }

    public function extra(string $key, mixed $default = null): mixed
    {
        return data_get($this->extra, $key, $default);
    }

    /**
     * @param  list<MediaItem>  $media
     */
    public function withMedia(array $media): self
    {
        return new self($this->caption, $media, $this->hashtags, $this->title, $this->extra);
    }

    public function withCaption(string $caption): self
    {
        return new self($caption, $this->media, $this->hashtags, $this->title, $this->extra);
    }

    /**
     * @return array<string, mixed>
     */
    public function toArray(): array
    {
        return [
            'caption' => $this->caption,
            'media' => array_map(static fn (MediaItem $item): array => $item->toArray(), $this->media),
            'hashtags' => $this->hashtags,
            'title' => $this->title,
            'extra' => $this->extra,
        ];
    }
}
