<?php

declare(strict_types=1);

namespace Karnoweb\Omnichannel\Channels\Instagram;

use Karnoweb\Omnichannel\Enums\PublicationType;
use Karnoweb\Omnichannel\Results\ValidationResult;
use Karnoweb\Omnichannel\ValueObjects\MediaItem;
use Karnoweb\Omnichannel\ValueObjects\PublicationContent;

final class InstagramMediaValidator
{
    public function validate(PublicationType $type, PublicationContent $content): ValidationResult
    {
        $result = $this->validateCaption($content);

        return match ($type) {
            PublicationType::Post => $result->merge($this->validatePost($content)),
            PublicationType::Carousel => $result->merge($this->validateCarousel($content)),
            PublicationType::Reel => $result->merge($this->validateReel($content)),
            PublicationType::Story => $result->merge($this->validateStory($content)),
            default => $result->withError(
                'instagram.type.unsupported',
                trans('omnichannel::omnichannel.validation.type_unsupported', ['type' => $type->label()]),
            ),
        };
    }

    private function validateCaption(PublicationContent $content): ValidationResult
    {
        $limits = $this->limits();
        $caption = $content->captionWithHashtags();

        return ValidationResult::make()
            ->errorIf(
                mb_strlen($caption) > $limits['caption_max_chars'],
                'instagram.caption.too_long',
                trans('omnichannel::omnichannel.validation.caption_too_long', ['max' => $limits['caption_max_chars']]),
                ['length' => mb_strlen($caption)],
            )
            ->errorIf(
                $content->totalHashtagCount() > $limits['caption_max_hashtags'],
                'instagram.caption.too_many_hashtags',
                trans('omnichannel::omnichannel.validation.too_many_hashtags', ['max' => $limits['caption_max_hashtags']]),
                ['count' => $content->totalHashtagCount()],
            )
            ->errorIf(
                $content->totalMentionCount() > $limits['caption_max_mentions'],
                'instagram.caption.too_many_mentions',
                trans('omnichannel::omnichannel.validation.too_many_mentions', ['max' => $limits['caption_max_mentions']]),
                ['count' => $content->totalMentionCount()],
            );
    }

    private function validatePost(PublicationContent $content): ValidationResult
    {
        $media = $content->firstMedia();

        if ($media === null) {
            return ValidationResult::error(
                'instagram.post.media_required',
                trans('omnichannel::omnichannel.validation.media_required'),
            );
        }

        if ($content->mediaCount() > 1) {
            return ValidationResult::error(
                'instagram.post.too_many_media',
                trans('omnichannel::omnichannel.validation.post_single_media'),
            );
        }

        return $media->isVideo()
            ? $this->validateVideoAgainst($media, 'reel', 'instagram.post')
            : $this->validateImageAgainst($media, 'image', 'instagram.post');
    }

    private function validateCarousel(PublicationContent $content): ValidationResult
    {
        $limits = $this->limits();
        $count = $content->mediaCount();

        $result = ValidationResult::make()
            ->errorIf(
                $count < $limits['carousel_min_children'],
                'instagram.carousel.too_few',
                trans('omnichannel::omnichannel.validation.carousel_too_few', ['min' => $limits['carousel_min_children']]),
                ['count' => $count],
            )
            ->errorIf(
                $count > $limits['carousel_max_children'],
                'instagram.carousel.too_many',
                trans('omnichannel::omnichannel.validation.carousel_too_many', ['max' => $limits['carousel_max_children']]),
                ['count' => $count],
            );

        foreach ($content->media as $index => $item) {
            $prefix = "instagram.carousel.item.{$index}";

            $result = $result->merge(
                $item->isVideo()
                    ? $this->validateVideoAgainst($item, 'reel', $prefix)
                    : $this->validateImageAgainst($item, 'image', $prefix)
            );
        }

        return $result->warnIf(
            $count > 1,
            'instagram.carousel.crop_notice',
            trans('omnichannel::omnichannel.validation.carousel_crop_notice'),
        );
    }

    private function validateReel(PublicationContent $content): ValidationResult
    {
        $videos = $content->videos();

        if ($videos === []) {
            return ValidationResult::error(
                'instagram.reel.video_required',
                trans('omnichannel::omnichannel.validation.reel_video_required'),
            );
        }

        if (count($videos) > 1 || $content->mediaCount() > 1) {
            return ValidationResult::error(
                'instagram.reel.single_video',
                trans('omnichannel::omnichannel.validation.reel_single_video'),
            );
        }

        return $this->validateVideoAgainst($videos[0], 'reel', 'instagram.reel');
    }

    private function validateStory(PublicationContent $content): ValidationResult
    {
        $media = $content->firstMedia();

        if ($media === null) {
            return ValidationResult::error(
                'instagram.story.media_required',
                trans('omnichannel::omnichannel.validation.media_required'),
            );
        }

        if ($content->mediaCount() > 1) {
            return ValidationResult::error(
                'instagram.story.single_media',
                trans('omnichannel::omnichannel.validation.story_single_media'),
            );
        }

        return $media->isVideo()
            ? $this->validateVideoAgainst($media, 'story_video', 'instagram.story')
            : $this->validateImageAgainst($media, 'story_image', 'instagram.story');
    }

    private function validateImageAgainst(MediaItem $item, string $limitKey, string $codePrefix): ValidationResult
    {
        /** @var array<string, mixed> $spec */
        $spec = $this->limits()[$limitKey];

        /** @var list<string> $extensions */
        $extensions = $spec['extensions'];

        $result = ValidationResult::make()
            ->errorIf(
                ! $this->isUrlPubliclyFetchable($item->url),
                "{$codePrefix}.url_not_public",
                trans('omnichannel::omnichannel.validation.url_not_public', ['url' => $item->url]),
            )
            ->errorIf(
                $item->extension() !== null && ! in_array($item->extension(), $extensions, true),
                "{$codePrefix}.format",
                trans('omnichannel::omnichannel.validation.image_format', ['formats' => 'JPEG']),
                ['extension' => $item->extension()],
            )
            ->errorIf(
                $item->mimeType !== null && ! in_array($item->mimeType, $spec['mime_types'], true),
                "{$codePrefix}.mime",
                trans('omnichannel::omnichannel.validation.image_format', ['formats' => 'JPEG']),
                ['mime_type' => $item->mimeType],
            )
            ->errorIf(
                $item->sizeBytes !== null && $item->sizeBytes > $spec['max_bytes'],
                "{$codePrefix}.too_large",
                trans('omnichannel::omnichannel.validation.file_too_large', ['max' => $this->humanBytes((int) $spec['max_bytes'])]),
                ['bytes' => $item->sizeBytes],
            );

        if (isset($spec['min_width'], $spec['max_width']) && $item->width !== null) {
            $result = $result
                ->errorIf(
                    $item->width < $spec['min_width'],
                    "{$codePrefix}.width_too_small",
                    trans('omnichannel::omnichannel.validation.width_too_small', ['min' => $spec['min_width']]),
                    ['width' => $item->width],
                )
                ->warnIf(
                    $item->width > $spec['max_width'],
                    "{$codePrefix}.width_downscaled",
                    trans('omnichannel::omnichannel.validation.width_downscaled', ['max' => $spec['max_width']]),
                    ['width' => $item->width],
                );
        }

        $ratio = $item->aspectRatio();

        if ($ratio !== null && isset($spec['min_aspect_ratio'], $spec['max_aspect_ratio'])) {
            $result = $result->errorIf(
                $ratio < $spec['min_aspect_ratio'] || $ratio > $spec['max_aspect_ratio'],
                "{$codePrefix}.aspect_ratio",
                trans('omnichannel::omnichannel.validation.aspect_ratio', [
                    'min' => '4:5',
                    'max' => '1.91:1',
                ]),
                ['ratio' => round($ratio, 3)],
            );
        }

        if ($item->altText !== null) {
            $result = $result->errorIf(
                mb_strlen($item->altText) > $this->limits()['alt_text_max_chars'],
                "{$codePrefix}.alt_text_too_long",
                trans('omnichannel::omnichannel.validation.alt_text_too_long', ['max' => $this->limits()['alt_text_max_chars']]),
            );
        }

        return $result;
    }

    private function validateVideoAgainst(MediaItem $item, string $limitKey, string $codePrefix): ValidationResult
    {
        /** @var array<string, mixed> $spec */
        $spec = $this->limits()[$limitKey];

        /** @var list<string> $extensions */
        $extensions = $spec['extensions'];

        $result = ValidationResult::make()
            ->errorIf(
                ! $this->isUrlPubliclyFetchable($item->url),
                "{$codePrefix}.url_not_public",
                trans('omnichannel::omnichannel.validation.url_not_public', ['url' => $item->url]),
            )
            ->errorIf(
                $item->extension() !== null && ! in_array($item->extension(), $extensions, true),
                "{$codePrefix}.format",
                trans('omnichannel::omnichannel.validation.video_format', ['formats' => 'MP4, MOV']),
                ['extension' => $item->extension()],
            )
            ->errorIf(
                $item->sizeBytes !== null && $item->sizeBytes > $spec['max_bytes'],
                "{$codePrefix}.too_large",
                trans('omnichannel::omnichannel.validation.file_too_large', ['max' => $this->humanBytes((int) $spec['max_bytes'])]),
                ['bytes' => $item->sizeBytes],
            );

        if ($item->durationSeconds !== null) {
            $result = $result
                ->errorIf(
                    $item->durationSeconds < $spec['min_duration_seconds'],
                    "{$codePrefix}.too_short",
                    trans('omnichannel::omnichannel.validation.video_too_short', ['min' => $spec['min_duration_seconds']]),
                    ['duration' => $item->durationSeconds],
                )
                ->errorIf(
                    $item->durationSeconds > $spec['max_duration_seconds'],
                    "{$codePrefix}.too_long",
                    trans('omnichannel::omnichannel.validation.video_too_long', [
                        'max' => $this->humanDuration((int) $spec['max_duration_seconds']),
                    ]),
                    ['duration' => $item->durationSeconds],
                );
        }

        $ratio = $item->aspectRatio();

        if ($ratio !== null && isset($spec['min_aspect_ratio'], $spec['max_aspect_ratio'])) {
            $result = $result->errorIf(
                $ratio < $spec['min_aspect_ratio'] || $ratio > $spec['max_aspect_ratio'],
                "{$codePrefix}.aspect_ratio",
                trans('omnichannel::omnichannel.validation.aspect_ratio', [
                    'min' => (string) $spec['min_aspect_ratio'] . ':1',
                    'max' => (string) $spec['max_aspect_ratio'] . ':1',
                ]),
                ['ratio' => round($ratio, 3)],
            );
        }

        if (isset($spec['max_horizontal_pixels']) && $item->width !== null) {
            $result = $result->warnIf(
                $item->width > $spec['max_horizontal_pixels'],
                "{$codePrefix}.width_downscaled",
                trans('omnichannel::omnichannel.validation.width_downscaled', ['max' => $spec['max_horizontal_pixels']]),
            );
        }

        return $result;
    }

    private function isUrlPubliclyFetchable(string $url): bool
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

    /**
     * @return array<string, mixed>
     */
    private function limits(): array
    {
        /** @var array<string, mixed> $limits */
        $limits = config('omnichannel.channels.instagram.limits', []);

        return $limits;
    }

    private function humanBytes(int $bytes): string
    {
        return $bytes >= 1048576
            ? round($bytes / 1048576) . ' MB'
            : round($bytes / 1024) . ' KB';
    }

    private function humanDuration(int $seconds): string
    {
        return $seconds >= 60
            ? intdiv($seconds, 60) . ' min'
            : $seconds . ' s';
    }
}
