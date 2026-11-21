<?php

declare(strict_types=1);

namespace Karnoweb\Omnichannel\Support;

use Illuminate\Support\Facades\DB;

final class OmnichannelEventDispatcher
{
    public static function dispatch(object $event): void
    {
        if (DB::transactionLevel() > 0) {
            DB::afterCommit(static fn () => event($event));

            return;
        }

        event($event);
    }
}
