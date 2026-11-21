<?php

declare(strict_types=1);

namespace Karnoweb\Omnichannel\Services;

use Illuminate\Contracts\Cache\LockTimeoutException;
use Illuminate\Support\Facades\Cache;
use Karnoweb\Omnichannel\Models\ChannelConnection;

final class ConnectionTokenRefreshLock
{
    /**
     * @param callable(ChannelConnection): bool $refresh
     */
    public function run(ChannelConnection $connection, callable $refresh): bool
    {
        $connectionId = $connection->getKey();
        $lock = Cache::lock(
            'omnichannel:token-refresh:' . $connectionId,
            max(30, (int) config('omnichannel.queue.token_refresh_lock_seconds', 180)),
        );

        try {
            return $lock->block(max(0, (int) config('omnichannel.queue.token_refresh_lock_wait_seconds', 30)), function () use ($connection, $refresh): bool {
                $previousExpiry = $connection->expires_at?->getTimestamp();
                $previousAccessToken = $connection->credential('access_token');
                $previousRefreshToken = $connection->credential('refresh_token');
                $connection->refresh();
                $currentExpiry = $connection->expires_at?->getTimestamp();
                $tokensChanged = $previousAccessToken !== $connection->credential('access_token')
                    || $previousRefreshToken !== $connection->credential('refresh_token');

                if ($tokensChanged || ($previousExpiry !== null && $currentExpiry !== null && $currentExpiry > $previousExpiry)) {
                    return true;
                }

                return $refresh($connection);
            });
        } catch (LockTimeoutException) {
            return false;
        }
    }
}
