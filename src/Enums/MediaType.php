<?php

declare(strict_types=1);

namespace Karnoweb\Omnichannel\Enums;

enum MediaType: string
{
    case Image = 'image';
    case Video = 'video';

    public function label(): string
    {
        return trans('omnichannel::omnichannel.media.'.$this->value);
    }

    public function requiredCapability(): Capability
    {
        return match ($this) {
            self::Image => Capability::Image,
            self::Video => Capability::Video,
        };
    }

    public static function guessFromMime(string $mime): ?self
    {
        if (str_starts_with($mime, 'image/')) {
            return self::Image;
        }
        if (str_starts_with($mime, 'video/')) {
            return self::Video;
        }

        return null;
    }

    public static function guessFromUrl(string $url): ?self
    {
        $path = parse_url($url, PHP_URL_PATH);

        if (! is_string($path)) {
            return null;
        }

        return match (strtolower(pathinfo($path, PATHINFO_EXTENSION))) {
            'jpg', 'jpeg', 'png', 'webp', 'gif' => self::Image,
            'mp4', 'mov', 'm4v', 'webm' => self::Video,
            default => null,
        };
    }
}
