<?php

declare(strict_types=1);

namespace Karnoweb\Omnichannel\ValueObjects;

use InvalidArgumentException;
use Karnoweb\Omnichannel\Enums\MediaType;

final readonly class MediaItem
{
    public function __construct(
        public MediaType $type,
        public string $url,
        public ?string $altText = null,
        public ?string $thumbnailUrl = null,
        public ?int $width = null,
        public ?int $height = null,
        public ?float $durationSeconds = null,
        public ?int $sizeBytes = null,
        public ?string $mimeType = null,
        public ?int $assetId = null,
        public ?string $checksum = null,
    ) {
        if (trim($url) === '') {
            throw new InvalidArgumentException('Media URL cannot be empty.');
        }
    }

    /**
     * @param  array<string, mixed>  $data
     */
    public static function fromArray(array $data): self
    {
        $url = (string) ($data['url'] ?? '');

        $type = isset($data['type'])
            ? MediaType::from((string) $data['type'])
            : (MediaType::guessFromUrl($url) ?? throw new InvalidArgumentException(
                "Cannot determine media type for URL [{$url}]."
            ));

        return new self(
            type: $type,
            url: $url,
            altText: isset($data['alt_text']) ? (string) $data['alt_text'] : null,
            thumbnailUrl: isset($data['thumbnail_url']) ? (string) $data['thumbnail_url'] : null,
            width: isset($data['width']) ? (int) $data['width'] : null,
            height: isset($data['height']) ? (int) $data['height'] : null,
            durationSeconds: isset($data['duration_seconds']) ? (float) $data['duration_seconds'] : null,
            sizeBytes: isset($data['size_bytes']) ? (int) $data['size_bytes'] : null,
            mimeType: isset($data['mime_type']) ? (string) $data['mime_type'] : null,
            assetId: isset($data['asset_id']) ? (int) $data['asset_id'] : null,
            checksum: isset($data['checksum']) ? (string) $data['checksum'] : null,
        );
    }

    public function isImage(): bool
    {
        return $this->type === MediaType::Image;
    }

    public function isVideo(): bool
    {
        return $this->type === MediaType::Video;
    }

    public function aspectRatio(): ?float
    {
        if ($this->width === null || $this->height === null || $this->height === 0) {
            return null;
        }

        return $this->width / $this->height;
    }

    public function extension(): ?string
    {
        $path = parse_url($this->url, PHP_URL_PATH);

        if (! is_string($path)) {
            return null;
        }

        $extension = strtolower(pathinfo($path, PATHINFO_EXTENSION));

        return $extension === '' ? null : $extension;
    }

    public function filename(): string
    {
        $path = parse_url($this->url, PHP_URL_PATH);

        return is_string($path) ? basename($path) : 'media';
    }

    public function withAltText(?string $altText): self
    {
        return new self(
            $this->type,
            $this->url,
            $altText,
            $this->thumbnailUrl,
            $this->width,
            $this->height,
            $this->durationSeconds,
            $this->sizeBytes,
            $this->mimeType,
            $this->assetId,
            $this->checksum,
        );
    }

    /**
     * @return array<string, mixed>
     */
    public function toArray(): array
    {
        return array_filter([
            'type' => $this->type->value,
            'url' => $this->url,
            'alt_text' => $this->altText,
            'thumbnail_url' => $this->thumbnailUrl,
            'width' => $this->width,
            'height' => $this->height,
            'duration_seconds' => $this->durationSeconds,
            'size_bytes' => $this->sizeBytes,
            'mime_type' => $this->mimeType,
            'asset_id' => $this->assetId,
            'checksum' => $this->checksum,
        ], static fn (mixed $value): bool => $value !== null);
    }
}
