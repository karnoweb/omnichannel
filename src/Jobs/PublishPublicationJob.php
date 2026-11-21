<?php

declare(strict_types=1);

namespace Karnoweb\Omnichannel\Jobs;

use Carbon\CarbonImmutable;
use DateTimeInterface;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Queue\Queueable;
use Illuminate\Queue\Middleware\WithoutOverlapping;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Str;
use Karnoweb\Omnichannel\Enums\FailureClass;
use Karnoweb\Omnichannel\Enums\PublicationStatus;
use Karnoweb\Omnichannel\Jobs\Middleware\ThrottleChannelRequests;
use Karnoweb\Omnichannel\Models\Publication;
use Karnoweb\Omnichannel\Services\PublishingService;
use Karnoweb\Omnichannel\Services\SecretRedactor;
use Karnoweb\Omnichannel\Support\OmnichannelContext;
use Throwable;

final class PublishPublicationJob implements ShouldQueue
{
    use Queueable;

    public readonly string $attemptToken;

    public readonly int $retryDeadlineTimestamp;

    public readonly ?int $scheduledAtTimestamp;

    public function __construct(
        public readonly Publication $publication,
        ?string $attemptToken = null,
        ?int $scheduledAtTimestamp = null,
    ) {
        $this->attemptToken = $attemptToken !== null && $attemptToken !== ''
            ? $attemptToken
            : Str::uuid()->toString();
        $baseTime = max(CarbonImmutable::now()->timestamp, $publication->scheduled_at?->getTimestamp() ?? 0);
        $deadline = $baseTime + max(1, (int) config('omnichannel.queue.retry_until_minutes', 120)) * 60;
        if ($publication->exists) {
            $publication->newQuery()->whereKey($publication->getKey())
                ->whereNull('retry_deadline_timestamp')->update(['retry_deadline_timestamp' => $deadline]);
            $deadline = (int) $publication->newQuery()->whereKey($publication->getKey())->value('retry_deadline_timestamp');
        }
        $this->retryDeadlineTimestamp = $deadline;
        $this->scheduledAtTimestamp = $scheduledAtTimestamp
            ?? ($publication->scheduled_at?->getTimestamp());

        $this->onQueue((string) config('omnichannel.queue.name', 'publishing'));

        $connection = config('omnichannel.queue.connection');

        if (is_string($connection) && $connection !== '') {
            $this->onConnection($connection);
        }
    }

    public function tries(): int
    {
        return (int) config('omnichannel.queue.tries', 5);
    }

    /**
     * @return list<int>
     */
    public function backoff(): array
    {
        /** @var list<int> $backoff */
        $backoff = config('omnichannel.queue.backoff', [30, 120, 300, 900]);
        if (! is_array($backoff)) {
            $backoff = [];
        }
        $backoff = array_values(array_filter($backoff, static fn (mixed $delay): bool => is_numeric($delay) && (int) $delay >= 0));

        return $backoff !== [] ? array_map('intval', $backoff) : [30, 120, 300, 900];
    }

    public function timeout(): int
    {
        return (int) config('omnichannel.queue.timeout', 300);
    }

    public function retryUntil(): DateTimeInterface
    {
        $deadline = isset($this->retryDeadlineTimestamp)
            ? $this->retryDeadlineTimestamp
            : CarbonImmutable::now()->addMinutes(max(1, (int) config('omnichannel.queue.retry_until_minutes', 120)))->timestamp;

        return CarbonImmutable::createFromTimestamp($deadline);
    }

    /**
     * @return list<object>
     */
    public function middleware(): array
    {
        $this->publication->loadMissing('channel');

        return [
            new WithoutOverlapping('omnichannel-publication:'.$this->publication->id)
                ->releaseAfter(30)
                ->expireAfter($this->timeout() + 60),
            new ThrottleChannelRequests($this->publication->channel->driver),
        ];
    }

    public function handle(PublishingService $publishing, OmnichannelContext $context): void
    {
        $work = function () use ($publishing): void {
            $this->publication->refresh();

            if (CarbonImmutable::now()->getTimestamp() >= $this->retryUntil()->getTimestamp()) {
                return;
            }

            if (isset($this->scheduledAtTimestamp) && $this->scheduledAtTimestamp !== null) {
                $scheduledAt = $this->publication->scheduled_at;
                if ($scheduledAt === null
                    || $scheduledAt->getTimestamp() !== $this->scheduledAtTimestamp
                    || ! in_array($this->publication->status, [PublicationStatus::Scheduled, PublicationStatus::Failed], true)
                    || $scheduledAt->isFuture()) {
                    return;
                }
            }

            if (in_array($this->publication->status, [
                PublicationStatus::Cancelled,
                PublicationStatus::Deleted,
                PublicationStatus::Published,
                PublicationStatus::UnknownOutcome,
            ], true)) {
                return;
            }

            $result = $publishing->publish($this->publication, $this->attemptToken, $this->scheduledAtTimestamp ?? null);

            if ($result->successful) {
                return;
            }

            if ($result->isRetryable() && $this->attempts() < $this->tries()) {
                $delay = $result->failureClass === FailureClass::RateLimited
                    ? $this->rateLimitedDelay($result->metadata['retry_after'] ?? null)
                    : $this->backoffForAttempt();

                $this->release($delay);

                return;
            }

            Log::info('Omnichannel publication publishing stopped.', [
                'publication_id' => $this->publication->id,
                'failure_class' => $result->failureClass?->value,
                'error_code' => $result->errorCode,
                'attempts' => $this->attempts(),
            ]);
        };

        $branchId = $this->publication->branch_id;
        if ($branchId === null) {
            $context->runAsSystem($work);
        } else {
            $context->runForBranch($branchId, $work);
        }
    }

    public function failed(?Throwable $exception): void
    {
        $context = app(OmnichannelContext::class);
        $work = function () use ($exception): void {
            $this->publication->refresh();

            if (! $this->publication->status->isInFlight()
                || $this->publication->attempt_token === null
                || $this->publication->attempt_token !== $this->attemptToken) {
                return;
            }

            $message = $exception?->getMessage() ?? trans('omnichannel::omnichannel.errors.worker_stopped');

            $this->publication->markUnknownOutcome(
                'queue.job_failed',
                app(SecretRedactor::class)->redactString($message, $this->publication->connection?->credentials ?? []),
            );
        };

        $branchId = $this->publication->branch_id;
        if ($branchId === null) {
            $context->runAsSystem($work);
        } else {
            $context->runForBranch($branchId, $work);
        }
    }

    private function backoffForAttempt(): int
    {
        $backoff = $this->backoff();
        $index = min($this->attempts() - 1, count($backoff) - 1);

        $base = $backoff[max(0, $index)];
        $jitter = max(0, (int) config('omnichannel.queue.backoff_jitter_seconds', 10));

        return $base + ($jitter > 0 ? random_int(0, $jitter) : 0);
    }

    private function rateLimitedDelay(mixed $retryAfter): int
    {
        if (is_numeric($retryAfter) && (int) $retryAfter > 0) {
            $jitter = max(0, (int) config('omnichannel.queue.backoff_jitter_seconds', 10));

            return (int) $retryAfter + ($jitter > 0 ? random_int(0, $jitter) : 0);
        }

        return max($this->backoffForAttempt(), (int) config('omnichannel.queue.rate_limited_fallback_seconds', 300));
    }
}
