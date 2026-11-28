<?php

declare(strict_types=1);

namespace Karnoweb\Omnichannel\Services;

use Karnoweb\Omnichannel\Contracts\PublicationSourceResolverContract;

final class PublicationSourceRegistry
{
    /** @var array<string, PublicationSourceResolverContract> */
    private array $resolvers = [];

    public function register(PublicationSourceResolverContract $resolver): void
    {
        $this->resolvers[$resolver->logicalType()] = $resolver;
    }

    public function resolve(string $logicalType): ?PublicationSourceResolverContract
    {
        return $this->resolvers[$logicalType] ?? null;
    }

    /**
     * @return list<string>
     */
    public function registeredTypes(): array
    {
        return array_keys($this->resolvers);
    }
}
