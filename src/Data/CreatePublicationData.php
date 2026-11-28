<?php

declare(strict_types=1);

namespace Karnoweb\Omnichannel\Data;

use DateTimeInterface;
use Karnoweb\Omnichannel\Enums\PublicationSourceType;
use Karnoweb\Omnichannel\Enums\PublicationType;
use Karnoweb\Omnichannel\Models\ChannelConnection;
use Karnoweb\Omnichannel\ValueObjects\PublicationContent;

final readonly class CreatePublicationData
{
    public function __construct(
        public PublicationSourceType $sourceType,
        public ChannelConnection $connection,
        public PublicationType $type,
        public PublicationContent $content,
        public ?string $productId = null,
        public ?string $productName = null,
        public ?string $sourceTitle = null,
        public ?string $sourceReferenceType = null,
        public int|string|null $sourceReferenceId = null,
        public ?DateTimeInterface $scheduledAt = null,
        public bool $allowDuplicate = false,
        public int|string|null $actorId = null,
        public ?string $intentId = null,
    ) {}
}
