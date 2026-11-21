<?php

declare(strict_types=1);

namespace Karnoweb\Omnichannel\Enums;

enum PublicationStatus: string
{
    case Draft = 'draft';
    case Scheduled = 'scheduled';
    case Publishing = 'publishing';
    case Published = 'published';
    case Failed = 'failed';
    case UnknownOutcome = 'unknown_outcome';
    case Cancelled = 'cancelled';
    case Deleted = 'deleted';

    /**
     * @return list<self>
     */
    public function allowedTransitions(): array
    {
        return match ($this) {
            self::Draft => [self::Scheduled, self::Publishing, self::Failed, self::Cancelled],
            self::Scheduled => [self::Publishing, self::Draft, self::Failed, self::Cancelled],
            self::Publishing => [self::Published, self::Failed, self::UnknownOutcome],
            self::Failed => [self::Publishing, self::Scheduled, self::Cancelled],
            self::UnknownOutcome => [self::Failed],
            self::Published => [self::Deleted],
            self::Cancelled, self::Deleted => [],
        };
    }

    public function canTransitionTo(self $target): bool
    {
        return in_array($target, $this->allowedTransitions(), true);
    }

    public function isTerminal(): bool
    {
        return $this->allowedTransitions() === [];
    }

    public function isInFlight(): bool
    {
        return $this === self::Publishing;
    }

    public function label(): string
    {
        return trans('omnichannel::omnichannel.status.'.$this->value);
    }

    public function color(): string
    {
        return match ($this) {
            self::Draft => 'badge-ghost',
            self::Scheduled => 'badge-info',
            self::Publishing => 'badge-warning',
            self::Published => 'badge-success',
            self::Failed => 'badge-error',
            self::UnknownOutcome => 'badge-warning',
            self::Cancelled => 'badge-neutral',
            self::Deleted => 'badge-neutral',
        };
    }

    public function icon(): string
    {
        return match ($this) {
            self::Draft => 'o-pencil-square',
            self::Scheduled => 'o-clock',
            self::Publishing => 'o-arrow-path',
            self::Published => 'o-check-circle',
            self::Failed => 'o-exclamation-triangle',
            self::UnknownOutcome => 'o-question-mark-circle',
            self::Cancelled => 'o-x-circle',
            self::Deleted => 'o-trash',
        };
    }

    /**
     * @return list<self>
     */
    public static function all(): array
    {
        return self::cases();
    }
}
