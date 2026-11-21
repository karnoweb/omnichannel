<?php

declare(strict_types=1);

namespace Karnoweb\Omnichannel\Support;

use Illuminate\Support\Facades\Auth;
use Karnoweb\Omnichannel\Contracts\ActorContextResolverContract;
use Karnoweb\Omnichannel\Contracts\BranchContextResolverContract;
use Karnoweb\Omnichannel\Exceptions\BranchAccessDeniedException;

final class OmnichannelContext
{
    private int|string|null $branchOverride = null;

    private bool $hasBranchOverride = false;

    private int $systemContextDepth = 0;

    public function __construct(
        private readonly ?BranchContextResolverContract $branchResolver = null,
        private readonly ?ActorContextResolverContract $actorResolver = null,
    ) {}

    public function branchId(): int|string|null
    {
        return $this->hasBranchOverride ? $this->branchOverride : $this->branchResolver?->branchId();
    }

    public function isSystemContext(): bool
    {
        return $this->systemContextDepth > 0;
    }

    public function runForBranch(int|string $branchId, callable $callback): mixed
    {
        $previousBranch = $this->branchOverride;
        $previouslyOverridden = $this->hasBranchOverride;
        $this->branchOverride = $branchId;
        $this->hasBranchOverride = true;

        try {
            return $callback();
        } finally {
            $this->branchOverride = $previousBranch;
            $this->hasBranchOverride = $previouslyOverridden;
        }
    }

    public function runAsSystem(callable $callback): mixed
    {
        $this->systemContextDepth++;

        try {
            return $callback();
        } finally {
            $this->systemContextDepth--;
        }
    }

    public function assertBranchAccess(int|string|null $resourceBranchId): void
    {
        if ($this->isSystemContext()) {
            return;
        }

        $currentBranchId = $this->branchId();
        if ($currentBranchId === null || $resourceBranchId === null || (string) $currentBranchId !== (string) $resourceBranchId) {
            throw new BranchAccessDeniedException;
        }
    }

    public function userId(): int|string|null
    {
        if ($this->actorResolver !== null) {
            return $this->actorResolver->userId();
        }

        $identifier = Auth::id();

        return is_int($identifier) || is_string($identifier) ? $identifier : null;
    }
}
