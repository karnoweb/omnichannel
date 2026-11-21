<?php

declare(strict_types=1);

namespace Karnoweb\Omnichannel\Channels\Demo;

use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Str;
use Karnoweb\Omnichannel\Contracts\ChannelDriver;
use Karnoweb\Omnichannel\Enums\Capability;
use Karnoweb\Omnichannel\Enums\FailureClass;
use Karnoweb\Omnichannel\Enums\PublicationType;
use Karnoweb\Omnichannel\Models\ChannelConnection;
use Karnoweb\Omnichannel\Models\Publication;
use Karnoweb\Omnichannel\Results\ConnectionTestResult;
use Karnoweb\Omnichannel\Results\DeleteResult;
use Karnoweb\Omnichannel\Results\PublishResult;
use Karnoweb\Omnichannel\Results\ValidationResult;
use Karnoweb\Omnichannel\ValueObjects\ChannelCapabilities;

final class DemoDriver implements ChannelDriver
{
    private const IDEMPOTENCY_TTL_HOURS = 24;

    public function key(): string
    {
        return 'demo';
    }

    public function capabilities(): ChannelCapabilities
    {
        return ChannelCapabilities::make(
            supported: [
                Capability::Text,
                Capability::Image,
                Capability::Video,
                Capability::Post,
                Capability::Carousel,
                Capability::Reel,
                Capability::Story,
                Capability::Update,
                Capability::Delete,
                Capability::Scheduling,
                Capability::Hashtags,
                Capability::AltText,
                Capability::Thumbnail,
            ],
            unsupportedReasons: [
                Capability::Product->value => 'The demo channel models a social feed, not a marketplace listing.',
                Capability::PriceAndStock->value => 'The demo channel does not carry commerce fields.',
                Capability::Deactivate->value => 'The demo channel supports a real delete, so it has no need for a deactivate fallback.',
                Capability::Message->value => 'The demo channel models a social feed, not a conversation.',
                Capability::Album->value => 'The demo channel uses a carousel rather than a grouped message.',
                Capability::Pin->value => 'There is nothing to pin in a feed.',
                Capability::SilentDelivery->value => 'A feed post triggers no notification to silence.',
                Capability::NativeScheduling->value => 'Scheduling is handled by this application.',
            ],
            limits: [
                'caption_max_chars' => 2200,
                'carousel_max_children' => 10,
            ],
            updateFields: ['caption', 'hashtags', 'title', 'media.*', 'extra.*'],
            clearUpdateFields: ['*'],
        );
    }

    public function validate(Publication $publication): ValidationResult
    {
        $content = $publication->content();
        $type = $publication->type;

        $result = ValidationResult::make()
            ->errorIf(
                ! $this->capabilities()->supportsType($type),
                'demo.type.unsupported',
                trans('omnichannel::omnichannel.validation.type_unsupported', ['type' => $type->label()]),
            )
            ->errorIf(
                $type->requiresMedia() && ! $content->hasMedia(),
                'demo.media.required',
                trans('omnichannel::omnichannel.validation.media_required'),
            )
            ->errorIf(
                mb_strlen($content->captionWithHashtags()) > 2200,
                'demo.caption.too_long',
                trans('omnichannel::omnichannel.validation.caption_too_long', ['max' => 2200]),
            );

        if ($type === PublicationType::Reel && $content->videos() === []) {
            $result = $result->withError('demo.reel.video_required', trans('omnichannel::omnichannel.validation.reel_video_required'));
        }

        if ($type === PublicationType::Carousel) {
            $count = $content->mediaCount();

            $result = $result
                ->errorIf($count < 2, 'demo.carousel.too_few', trans('omnichannel::omnichannel.validation.carousel_too_few', ['min' => 2]))
                ->errorIf($count > 10, 'demo.carousel.too_many', trans('omnichannel::omnichannel.validation.carousel_too_many', ['max' => 10]));
        }

        return $result;
    }

    public function testConnection(ChannelConnection $connection): ConnectionTestResult
    {
        $this->simulateLatency();

        if ($connection->credential('access_token') === null) {
            return ConnectionTestResult::failure(
                FailureClass::Authentication,
                'The demo connection has no access token stored.',
                'demo.auth.missing_token',
            );
        }

        if ($connection->credential('force_test_failure') === true) {
            return ConnectionTestResult::failure(
                FailureClass::Authentication,
                'Forced failure requested by the connection configuration.',
                'demo.auth.forced_failure',
            );
        }

        return ConnectionTestResult::success(
            accountId: $connection->external_account_id ?? 'demo-account',
            accountName: $connection->external_account_name ?? $connection->name,
            account: [
                'id' => $connection->external_account_id,
                'username' => $connection->external_account_name,
                'platform' => 'demo',
            ],
        );
    }

    public function publish(Publication $publication): PublishResult
    {
        $validation = $this->validate($publication);

        if ($validation->fails()) {
            return PublishResult::failure(
                FailureClass::Validation,
                $validation->summary(),
                $validation->errorCodes()[0] ?? 'demo.validation.failed',
                ['validation' => $validation->toArray()],
            );
        }

        if ($replay = $this->replay($publication)) {
            return $replay;
        }

        $this->simulateLatency();

        if ($forced = $this->forcedFailure($publication)) {
            return $forced;
        }

        if ($this->shouldFailTransiently()) {
            return PublishResult::failure(
                FailureClass::Transient,
                'The demo channel was temporarily unavailable.',
                'demo.transient',
            );
        }

        $externalId = 'demo_'.Str::lower(Str::random(16));
        $externalUrl = rtrim((string) config('omnichannel.channels.demo.base_url'), '/').'/p/'.$externalId;

        $publication->rememberPersistentArtifact('publish', 'result', [
            'external_id' => $externalId,
            'external_url' => $externalUrl,
        ]);
        Cache::put(
            $this->idempotencyCacheKey($publication),
            ['external_id' => $externalId, 'external_url' => $externalUrl],
            now()->addHours(self::IDEMPOTENCY_TTL_HOURS),
        );

        return PublishResult::success($externalId, $externalUrl, [
            'type' => $publication->type->value,
            'media_count' => $publication->content()->mediaCount(),
            'idempotency_key' => $publication->idempotency_key,
        ]);
    }

    public function update(Publication $publication): PublishResult
    {
        if ($publication->external_id === null) {
            return PublishResult::failure(
                FailureClass::Validation,
                'This publication has never been published, so there is nothing to update.',
                'demo.update.not_published',
            );
        }

        $validation = $this->validate($publication);

        if ($validation->fails()) {
            return PublishResult::failure(
                FailureClass::Validation,
                $validation->summary(),
                $validation->errorCodes()[0] ?? 'demo.validation.failed',
            );
        }

        $this->simulateLatency();

        if ($forced = $this->forcedFailure($publication)) {
            return $forced;
        }

        return PublishResult::success(
            $publication->external_id,
            $publication->external_url,
            ['updated' => true],
        );
    }

    public function delete(Publication $publication): DeleteResult
    {
        if ($publication->external_id === null) {
            return DeleteResult::failure(
                FailureClass::Validation,
                'This publication has never been published, so there is nothing to delete.',
                'demo.delete.not_published',
            );
        }

        $this->simulateLatency();
        Cache::forget($this->idempotencyCacheKey($publication));

        return DeleteResult::success(['deleted_external_id' => $publication->external_id]);
    }

    private function replay(Publication $publication): ?PublishResult
    {
        /** @var array{external_id: string, external_url: string}|null $stored */
        $stored = Cache::get($this->idempotencyCacheKey($publication))
            ?? $publication->persistentArtifact('publish', 'result');

        if ($stored === null) {
            return null;
        }

        return PublishResult::success(
            $stored['external_id'],
            $stored['external_url'],
            ['idempotent_replay' => true],
        );
    }

    private function idempotencyCacheKey(Publication $publication): string
    {
        return 'omnichannel:v2:demo:'.$publication->connection_id.':'.$publication->id.':publish:'.$publication->idempotency_key;
    }

    private function forcedFailure(Publication $publication): ?PublishResult
    {
        $force = data_get($publication->metadata, 'demo.force');

        if (! is_string($force)) {
            return null;
        }

        $class = FailureClass::tryFrom($force);

        if ($class === null) {
            return null;
        }

        return PublishResult::failure(
            $class,
            "Simulated {$class->value} failure requested by the publication metadata.",
            "demo.forced.{$class->value}",
        );
    }

    private function shouldFailTransiently(): bool
    {
        $rate = (float) config('omnichannel.channels.demo.transient_failure_rate', 0.0);

        return $rate > 0.0 && (mt_rand() / mt_getrandmax()) < $rate;
    }

    private function simulateLatency(): void
    {
        $ms = (int) config('omnichannel.channels.demo.latency_ms', 0);

        if ($ms > 0) {
            usleep($ms * 1000);
        }
    }
}
