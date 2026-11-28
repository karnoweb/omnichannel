<?php

declare(strict_types=1);

namespace Karnoweb\Omnichannel\Data;

use DateTimeInterface;
use Karnoweb\Omnichannel\Enums\PublicationBatchDispatchMode;
use Karnoweb\Omnichannel\Enums\PublicationSourceType;
use Karnoweb\Omnichannel\ValueObjects\PublicationContent;

final readonly class CreatePublicationBatchData
{
    /**
     * @param  list<PublicationTargetData>  $targets
     */
    public function __construct(
        public PublicationSourceType $sourceType,
        public PublicationContent $baseContent,
        public array $targets,
        public PublicationBatchDispatchMode $dispatchMode = PublicationBatchDispatchMode::Draft,
        public ?string $title = null,
        public ?string $productId = null,
        public ?string $sourceReferenceType = null,
        public int|string|null $sourceReferenceId = null,
        public ?string $sourceTitle = null,
        public ?DateTimeInterface $scheduledAt = null,
        public ?string $intentId = null,
        public int|string|null $actorId = null,
    ) {}
}
