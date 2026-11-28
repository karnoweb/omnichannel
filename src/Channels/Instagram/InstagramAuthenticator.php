<?php

declare(strict_types=1);

namespace Karnoweb\Omnichannel\Channels\Instagram;

use Carbon\CarbonImmutable;
use Illuminate\Support\Str;
use Karnoweb\Omnichannel\Enums\ConnectionStatus;
use Karnoweb\Omnichannel\Http\ApiResponse;
use Karnoweb\Omnichannel\Http\BaseApiClient;
use Karnoweb\Omnichannel\Models\ChannelConnection;
use Karnoweb\Omnichannel\Services\ConnectionTokenRefreshLock;
use RuntimeException;

final class InstagramAuthenticator extends BaseApiClient
{
    public const STATE_SESSION_KEY = 'instagram.oauth.state';

    public function __construct(private readonly InstagramApiClient $api) {}

    public function authorizationUrl(string $state, int|string|null $connectionId = null): string
    {
        $config = $this->config();
        $this->assertConfigured();

        $query = [
            'client_id' => $config['client_id'],
            'redirect_uri' => $config['redirect_uri'],
            'response_type' => 'code',
            'scope' => implode(',', $config['scopes']),
            'state' => $connectionId !== null ? $state.':'.$connectionId : $state,
        ];

        return $config['oauth_authorize_url'].'?'.http_build_query($query);
    }

    public static function generateState(): string
    {
        return Str::random(40);
    }

    public function exchangeCodeForShortLivedToken(string $code): ApiResponse
    {
        $config = $this->config();
        $this->assertConfigured();

        return $this->send(fn ($request): ApiResponse => ApiResponse::fromResponse(
            $request->asForm()->post($config['oauth_token_url'], [
                'client_id' => $config['client_id'],
                'client_secret' => $config['client_secret'],
                'grant_type' => 'authorization_code',
                'redirect_uri' => $config['redirect_uri'],
                'code' => $code,
            ])
        ));
    }

    public function exchangeForLongLivedToken(string $shortLivedToken): ApiResponse
    {
        $config = $this->config();

        return $this->get($config['graph_url'].'/access_token', [
            'grant_type' => 'ig_exchange_token',
            'client_secret' => $config['client_secret'],
            'access_token' => $shortLivedToken,
        ]);
    }

    public function refreshLongLivedToken(string $longLivedToken): ApiResponse
    {
        $config = $this->config();

        return $this->get($config['graph_url'].'/refresh_access_token', [
            'grant_type' => 'ig_refresh_token',
            'access_token' => $longLivedToken,
        ]);
    }

    /**
     * @return array{connection: ChannelConnection}|array{error: string, code: string}
     */
    public function completeConnection(ChannelConnection $connection, string $code): array
    {
        $short = $this->exchangeCodeForShortLivedToken($code);

        if ($short->failed()) {
            return [
                'error' => $this->errorMessage($short),
                'code' => 'instagram.oauth.code_exchange_failed',
            ];
        }

        $shortToken = $short->string('access_token');

        if ($shortToken === null) {
            return [
                'error' => 'Instagram did not return an access token for this authorization code.',
                'code' => 'instagram.oauth.no_token',
            ];
        }

        $long = $this->exchangeForLongLivedToken($shortToken);

        if ($long->failed()) {
            return [
                'error' => $this->errorMessage($long),
                'code' => 'instagram.oauth.long_lived_exchange_failed',
            ];
        }

        $longToken = $long->string('access_token');

        if ($longToken === null) {
            return [
                'error' => 'Instagram did not return a long-lived access token.',
                'code' => 'instagram.oauth.no_long_lived_token',
            ];
        }

        $expiresIn = $long->int('expires_in')
            ?? (int) config('omnichannel.channels.instagram.token_ttl_days', 60) * 86400;

        $profile = $this->api->me($longToken);

        $connection->mergeCredentials([
            'access_token' => $longToken,
            'token_type' => $long->string('token_type') ?? 'bearer',
        ]);

        $connection->forceFill([
            'external_account_id' => $profile->string('user_id') ?? $short->string('user_id'),
            'external_account_name' => $profile->string('username') !== null
                ? '@'.$profile->string('username')
                : $connection->external_account_name,
            'status' => ConnectionStatus::Active,
            'status_message' => null,
            'connected_at' => now(),
            'expires_at' => CarbonImmutable::now()->addSeconds($expiresIn),
            'metadata' => [
                ...($connection->metadata ?? []),
                'account_type' => $profile->string('account_type'),
                'profile_picture_url' => $profile->string('profile_picture_url'),
                'scopes' => $this->config()['scopes'],
                'api_version' => config('omnichannel.channels.instagram.api_version'),
            ],
        ])->save();

        return ['connection' => $connection];
    }

    public function refreshConnection(ChannelConnection $connection): bool
    {
        return app(ConnectionTokenRefreshLock::class)->run(
            $connection,
            fn (ChannelConnection $locked): bool => $this->refreshConnectionUnlocked($locked),
        );
    }

    private function refreshConnectionUnlocked(ChannelConnection $connection): bool
    {
        $token = $connection->credential('access_token');

        if (! is_string($token) || $token === '') {
            return false;
        }

        $response = $this->refreshLongLivedToken($token);

        if ($response->failed()) {
            $connection->forceFill([
                'status' => ConnectionStatus::Expired,
                'status_message' => $this->errorMessage($response),
            ])->save();

            return false;
        }

        $newToken = $response->string('access_token');

        if ($newToken === null) {
            return false;
        }

        $expiresIn = $response->int('expires_in')
            ?? (int) config('omnichannel.channels.instagram.token_ttl_days', 60) * 86400;

        $connection->mergeCredentials(['access_token' => $newToken]);

        $connection->forceFill([
            'expires_at' => CarbonImmutable::now()->addSeconds($expiresIn),
            'status' => ConnectionStatus::Active,
            'status_message' => null,
        ])->save();

        return true;
    }

    public function isConfigured(): bool
    {
        $config = $this->config();

        return is_string($config['client_id']) && $config['client_id'] !== ''
            && is_string($config['client_secret']) && $config['client_secret'] !== ''
            && is_string($config['redirect_uri']) && $config['redirect_uri'] !== '';
    }

    private function assertConfigured(): void
    {
        if (! $this->isConfigured()) {
            throw new RuntimeException(
                'Instagram is not configured. Set INSTAGRAM_CLIENT_ID, INSTAGRAM_CLIENT_SECRET and INSTAGRAM_REDIRECT_URI in your .env.'
            );
        }
    }

    private function errorMessage(ApiResponse $response): string
    {
        return $response->transportError
            ?? $response->string('error_message')
            ?? $response->string('error.message')
            ?? 'Instagram returned HTTP '.($response->status ?? 0).'.';
    }

    /**
     * @return array<string, mixed>
     */
    private function config(): array
    {
        /** @var array<string, mixed> $config */
        $config = config('omnichannel.channels.instagram', []);

        return $config;
    }
}
