<?php

declare(strict_types=1);

namespace Karnoweb\Omnichannel\ValueObjects;

final readonly class CatalogListingIdentity
{
    public function __construct(
        public int $connectionId,
        public string $productId,
        public ?string $externalId = null,
    ) {}

    public function key(): string
    {
        return hash('sha256', implode('|', [
            (string) $this->connectionId,
            $this->productId,
            $this->externalId ?? '',
        ]));
    }
}