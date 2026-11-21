<?php

declare(strict_types=1);

namespace Karnoweb\Omnichannel\Contracts;

use Karnoweb\Omnichannel\ValueObjects\PublicationContent;

interface PublicationSourceResolverContract
{
    public function logicalType(): string;

    public function resolveSnapshot(
        string $referenceType,
        int|string $referenceId,
        int|string $branchId,
    ): ?PublicationContent;

    public function resolveTitle(
        string $referenceType,
        int|string $referenceId,
        int|string $branchId,
    ): ?string;
}