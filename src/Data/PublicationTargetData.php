<?php

declare(strict_types=1);

namespace Karnoweb\Omnichannel\Data;

use DateTimeInterface;
use Karnoweb\Omnichannel\Enums\PublicationType;
use Karnoweb\Omnichannel\ValueObjects\PublicationContent;

final readonly class PublicationTargetData
{
    public function __construct(
        public int $connectionId,
        public PublicationType $type,
        public ?PublicationContent $contentOverride = null,
        public ?DateTimeInterface $scheduledAt = null,
        public int $sortOrder = 0,
    ) {}
}
