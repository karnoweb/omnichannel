<?php

declare(strict_types=1);

namespace Karnoweb\Omnichannel\Console;

use Illuminate\Console\Command;
use Karnoweb\Omnichannel\Channels\Basalam\BasalamAuthenticator;
use Karnoweb\Omnichannel\Channels\Instagram\InstagramAuthenticator;
use Karnoweb\Omnichannel\Enums\ConnectionStatus;
use Karnoweb\Omnichannel\Models\ChannelConnection;
use Karnoweb\Omnichannel\Omnichannel;
use Karnoweb\Omnichannel\Support\OmnichannelContext;

final class RefreshChannelTokensCommand extends Command
{
    protected $signature = 'omnichannel:refresh-tokens
                            {--days= : Refresh connections expiring within this many days}';

    protected $aliases = ['channels:refresh-tokens'];

    protected $description = 'Refresh omnichannel access tokens that are approaching expiry.';

    public function handle(
        InstagramAuthenticator $instagram,
        BasalamAuthenticator $basalam,
        OmnichannelContext $context,
    ): int {
        return $context->runAsSystem(fn (): int => $this->refreshTokens($instagram, $basalam));
    }

    private function refreshTokens(InstagramAuthenticator $instagram, BasalamAuthenticator $basalam): int
    {
        $days = $this->option('days') !== null
            ? (int) $this->option('days')
            : (int) config('omnichannel.channels.instagram.refresh_before_days', 10);

        /** @var class-string<ChannelConnection> $connectionModel */
        $connectionModel = Omnichannel::model('connection');

        $connections = $connectionModel::query()
            ->with('channel')
            ->where('status', ConnectionStatus::Active->value)
            ->expiringWithin($days)
            ->get();

        if ($connections->isEmpty()) {
            $this->components->info("No connections expire within {$days} days.");

            return self::SUCCESS;
        }

        $refreshed = 0;
        $failed = 0;

        foreach ($connections as $connection) {
            $ok = match ($connection->channel->driver) {
                'instagram' => $instagram->refreshConnection($connection),
                'basalam' => $basalam->refreshConnection($connection),
                default => null,
            };

            if ($ok === null) {
                continue;
            }

            $ok ? $refreshed++ : $failed++;
        }

        $this->components->info("{$refreshed} refreshed, {$failed} need reconnecting.");

        return $failed > 0 ? self::FAILURE : self::SUCCESS;
    }
}
