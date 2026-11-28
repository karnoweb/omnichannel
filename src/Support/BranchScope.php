<?php

declare(strict_types=1);

namespace Karnoweb\Omnichannel\Support;

use Illuminate\Database\Eloquent\Builder;
use Karnoweb\Omnichannel\Exceptions\BranchAccessDeniedException;

trait BranchScope
{
    /**
     * @param  Builder<static>  $query
     */
    public function scopeForBranch(Builder $query, int|string|null $branchId = null): Builder
    {
        $context = resolve(OmnichannelContext::class);
        if (func_num_args() < 2 && $context->isSystemContext()) {
            return $query;
        }

        $resolved = func_num_args() >= 2 ? $branchId : $context->branchId();
        if ($resolved === null && func_num_args() < 2) {
            throw new BranchAccessDeniedException;
        }
        $context->assertBranchAccess($resolved);

        $column = $query->qualifyColumn('branch_id');

        if ($resolved === null) {
            return $query->whereNull($column);
        }

        return $query->where($column, $resolved);
    }
}
