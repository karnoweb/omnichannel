<?php

declare(strict_types=1);

namespace Karnoweb\Omnichannel\Channels\Messaging;

use Karnoweb\Omnichannel\Enums\PublicationType;
use Karnoweb\Omnichannel\Results\ValidationResult;
use Karnoweb\Omnichannel\ValueObjects\MediaItem;
use Karnoweb\Omnichannel\ValueObjects\PublicationContent;

final readonly class MessengerLimits
{
    /**
     * @param  array<string, mixed>  $limits
     */
    public function __construct(
        private array $limits,
        private string $codePrefix,
        private bool $fetchesMediaFromUrl = true,
    ) {}

    public function validate(PublicationType $type, PublicationContent $content): ValidationResult
    {
        return match ($type) {
            PublicationType::Message => $this->validateMessage($content),
            PublicationType::Album => $this->validateAlbum($content),
            default => ValidationResult::error(
                "{$this->codePrefix}.type.unsupported",
                trans('omnichannel::omnichannel.validation.type_unsupported', ['type' => $type->label()]),
            ),
        };
    }

    private function validateMessage(PublicationContent $content): ValidationResult
    {
        $media = $content->firstMedia();

        $result = ValidationResult::make()
            ->errorIf(
                $content->mediaCount() > 1,
                "{$this->codePrefix}.message.single_media",
                trans('omnichannel::omnichannel.validation.message_single_media'),
            )
            ->errorIf(
                $media === null && trim($content->captionWithHashtags()) === '',
                "{$this->codePrefix}.message.empty",
                trans('omnichannel::omnichannel.validation.message_empty'),
            );

        $result = $result->merge($this->validateText($content, hasMedia: $media !== null));

        return $media === null ? $result : $result->merge($this->validateMedia($media, 0));
    }

    private function validateAlbum(PublicationContent $content): ValidationResult
    {
        $min = (int) ($this->limits['album_min_items'] ?? 2);
        $max = (int) ($this->limits['album_max_items'] ?? 10);
        $count = $content->mediaCount();

        $result = ValidationResult::make()
            ->errorIf(
                $count < $min,
                "{$this->codePrefix}.album.too_few",
                trans('omnichannel::omnichannel.validation.album_too_few', ['min' => $min]),
                ['count' => $count],
            )
            ->errorIf(
                $count > $max,
                "{$this->codePrefix}.album.too_many",
                trans('omnichannel::omnichannel.validation.album_too_many', ['max' => $max]),
                ['count' => $count],
            )
            ->merge($this->validateText($content, hasMedia: true));

        foreach ($content->media as $index => $item) {
            $result = $result->merge($this->validateMedia($item, $index));
        }

        return $result;
    }

    private function validateText(PublicationContent $content, bool $hasMedia): ValidationResult
    {
        $max = $hasMedia
            ? ($this->limits['caption_max_chars'] ?? null)
            : ($this->limits['text_max_chars'] ?? null);

        if ($max === null) {
            return ValidationResult::make();
        }

        $length = mb_strlen($content->captionWithHashtags());

        return ValidationResult::make()->errorIf(
            $length > (int) $max,
            "{$this->codePrefix}.text.too_long",
            $hasMedia
                ? trans('omnichannel::omnichannel.validation.caption_too_long', ['max' => $max])
                : trans('omnichannel::omnichannel.validation.text_too_long', ['max' => $max]),
            ['length' => $length],
        );
    }

    private function validateMedia(MediaItem $item, int $index): ValidationResult
    {
        /** @var array<string, mixed> $spec */
        $spec = $this->limits[$item->type->value] ?? [];

        if ($spec === []) {
            return ValidationResult::error(
                "{$this->codePrefix}.media.{$index}.type_unsupported",
                trans('omnichannel::omnichannel.validation.media_type_unsupported', ['type' => $item->type->label()]),
            );
        }

        /** @var list<string> $extensions */
        $extensions = $spec['extensions'] ?? [];

        $result = ValidationResult::make()
            ->errorIf(
                $extensions !== [] && $item->extension() !== null
                    && ! in_array($item->extension(), $extensions, true),
                "{$this->codePrefix}.media.{$index}.format",
                trans('omnichannel::omnichannel.validation.media_format', [
                    'formats' => mb_strtoupper(implode(', ', $extensions)),
                ]),
                ['extension' => $item->extension()],
            );

        if ($this->fetchesMediaFromUrl) {
            $result = $result->errorIf(
                ! self::isPubliclyFetchable($item->url),
                "{$this->codePrefix}.media.{$index}.url_not_public",
                trans('omnichannel::omnichannel.validation.url_not_public', ['url' => $item->url]),
            );
        }

        $maxBytes = $spec['max_bytes_by_url'] ?? $spec['max_bytes'] ?? null;

        if ($maxBytes !== null && $item->sizeBytes !== null) {
            $result = $result->errorIf(
                $item->sizeBytes > (int) $maxBytes,
                "{$this->codePrefix}.media.{$index}.too_large",
                trans('omnichannel::omnichannel.validation.file_too_large', [
                    'max' => self::humanBytes((int) $maxBytes),
                ]),
                ['bytes' => $item->sizeBytes],
            );
        }

        return $result;
    }

    public static function isPubliclyFetchable(string $url): bool
    {
        $host = parse_url($url, PHP_URL_HOST);
        $scheme = parse_url($url, PHP_URL_SCHEME);

        if (! is_string($host) || ! is_string($scheme)) {
            return false;
        }

        if (! in_array(strtolower($scheme), ['http', 'https'], true)) {
            return false;
        }

        if (in_array(strtolower($host), ['localhost', '127.0.0.1', '::1', '0.0.0.0'], true)) {
            return false;
        }

        if (filter_var($host, FILTER_VALIDATE_IP) !== false) {
            return filter_var(
                $host,
                FILTER_VALIDATE_IP,
                FILTER_FLAG_NO_PRIV_RANGE | FILTER_FLAG_NO_RES_RANGE
            ) !== false;
        }

        return str_contains($host, '.');
    }

    public static function humanBytes(int $bytes): string
    {
        return $bytes >= 1048576
            ? round($bytes / 1048576).' MB'
            : round($bytes / 1024).' KB';
    }
}
