<?php

declare(strict_types=1);

namespace Karnoweb\Omnichannel\Channels\Basalam;

use Carbon\CarbonImmutable;
use Illuminate\Support\Str;
use Karnoweb\Omnichannel\Enums\ConnectionStatus;
use Karnoweb\Omnichannel\Http\ApiResponse;
use Karnoweb\Omnichannel\Http\BaseApiClient;
use Karnoweb\Omnichannel\Models\ChannelConnection;
use Karnoweb\Omnichannel\Services\ConnectionTokenRefreshLock;
use RuntimeException;

final class BasalamAuthenticator extends BaseApiClient
{
    public const STATE_SESSION_KEY = 'basalam.oauth.state';

    public function __construct(private readonly BasalamApiClient $api) {}

    public function authorizationUrl(string $state, int|string|null $connectionId = null): string
    {
        $this->assertOauthConfigured();
        $config = $this->config();

        $query = [
            'client_id' => $config['client_id'],
            'redirect_uri' => $config['redirect_uri'],
            'response_type' => 'code',
            'scope' => implode(' ', $config['scopes']),
            'state' => $connectionId !== null ? $state.':'.$connectionId : $state,
        ];

        return rtrim((string) $config['auth_url'], '/').'/oauth/authorize?'.http_build_query($query);
    }

    public static function generateState(): string
    {
        return Str::random(40);
    }

    public function exchangeCodeForToken(string $code): ApiResponse
    {
        $this->assertOauthConfigured();
        $config = $this->config();

        return $this->send(fn ($request): ApiResponse => ApiResponse::fromResponse(
            $request->asForm()->post(rtrim((string) $config['auth_url'], '/').'/oauth/token', [
                'grant_type' => 'authorization_code',
                'client_id' => $config['client_id'],
                'client_secret' => $config['client_secret'],
                'redirect_uri' => $config['redirect_uri'],
                'code' => $code,
            ])
        ));
    }

    public function refreshToken(string $refreshToken): ApiResponse
    {
        $config = $this->config();

        return $this->send(fn ($request): ApiResponse => ApiResponse::fromResponse(
            $request->asForm()->post(rtrim((string) $config['auth_url'], '/').'/oauth/token', [
                'grant_type' => 'refresh_token',
                'client_id' => $config['client_id'],
                'client_secret' => $config['client_secret'],
                'refresh_token' => $refreshToken,
            ])
        ));
    }

    /**
     * @return array{connection: ChannelConnection}|array{error: string, code: string}
     */
    public function connectWithPersonalToken(ChannelConnection $connection, string $token): array
    {
        $profile = $this->api->me($token);

        if ($profile->failed()) {
            return [
                'error' => $this->errorMessage($profile),
                'code' => 'basalam.auth.token_rejected',
            ];
        }

        $vendorId = $this->extractVendorId($profile);

        if ($vendorId === null) {
            return [
                'error' => 'This Basalam account has no vendor (shop) attached, so it cannot publish products.',
                'code' => 'basalam.auth.no_vendor',
            ];
        }

        $connection->mergeCredentials([
            'access_token' => $token,
            'auth_method' => 'personal_access_token',
        ]);

        $connection->forceFill([
            'external_account_id' => (string) $vendorId,
            'external_account_name' => $profile->string('vendor.title')
                ?? $profile->string('name')
                ?? $connection->external_account_name,
            'status' => ConnectionStatus::Active,
            'status_message' => null,
            'connected_at' => now(),
            'expires_at' => null,
            'metadata' => [
                ...($connection->metadata ?? []),
                'vendor_id' => $vendorId,
                'user_id' => $profile->int('id'),
                'auth_method' => 'personal_access_token',
            ],
        ])->save();

        return ['connection' => $connection];
    }

    /**
     * @return array{connection: ChannelConnection}|array{error: string, code: string}
     */
    public function completeConnection(ChannelConnection $connection, string $code): array
    {
        $response = $this->exchangeCodeForToken($code);

        if ($response->failed()) {
            return [
                'error' => $this->errorMessage($response),
                'code' => 'basalam.oauth.code_exchange_failed',
            ];
        }

        $token = $response->string('access_token');

        if ($token === null) {
            return [
                'error' => 'Basalam did not return an access token for this authorization code.',
                'code' => 'basalam.oauth.no_token',
            ];
        }

        $profile = $this->api->me($token);
        $vendorId = $this->extractVendorId($profile);

        if ($vendorId === null) {
            return [
                'error' => 'This Basalam account has no vendor (shop) attached, so it cannot publish products.',
                'code' => 'basalam.auth.no_vendor',
            ];
        }

        $expiresIn = $response->int('expires_in');

        $connection->mergeCredentials([
            'access_token' => $token,
            'refresh_token' => $response->string('refresh_token'),
            'auth_method' => 'authorization_code',
        ]);

        $connection->forceFill([
            'external_account_id' => (string) $vendorId,
            'external_account_name' => $profile->string('vendor.title') ?? $connection->external_account_name,
            'status' => ConnectionStatus::Active,
            'status_message' => null,
            'connected_at' => now(),
            'expires_at' => $expiresIn !== null ? CarbonImmutable::now()->addSeconds($expiresIn) : null,
            'metadata' => [
                ...($connection->metadata ?? []),
                'vendor_id' => $vendorId,
                'user_id' => $profile->int('id'),
                'auth_method' => 'authorization_code',
                'scopes' => $this->config()['scopes'],
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
        $refreshToken = $connection->credential('refresh_token');

        if (! is_string($refreshToken) || $refreshToken === '') {
            return false;
        }

        $response = $this->refreshToken($refreshToken);

        if ($response->failed()) {
            $connection->forceFill([
                'status' => ConnectionStatus::Expired,
                'status_message' => $this->errorMessage($response),
            ])->save();

            return false;
        }

        $token = $response->string('access_token');

        if ($token === null) {
            return false;
        }

        $connection->mergeCredentials([
            'access_token' => $token,
            'refresh_token' => $response->string('refresh_token') ?? $refreshToken,
        ]);

        $expiresIn = $response->int('expires_in');

        $connection->forceFill([
            'expires_at' => $expiresIn !== null ? CarbonImmutable::now()->addSeconds($expiresIn) : null,
            'status' => ConnectionStatus::Active,
            'status_message' => null,
        ])->save();

        return true;
    }

    public function isOauthConfigured(): bool
    {
        $config = $this->config();

        return is_string($config['client_id']) && $config['client_id'] !== ''
            && is_string($config['client_secret']) && $config['client_secret'] !== ''
            && is_string($config['redirect_uri']) && $config['redirect_uri'] !== '';
    }

    private function extractVendorId(ApiResponse $profile): ?int
    {
        foreach (['vendor.id', 'vendor_id', 'user.vendor.id'] as $path) {
            $value = $profile->get($path);

            if (is_numeric($value)) {
                return (int) $value;
            }
        }

        return null;
    }

    private function assertOauthConfigured(): void
    {
        if (! $this->isOauthConfigured()) {
            throw new RuntimeException(
                'Basalam OAuth is not configured. Set BASALAM_CLIENT_ID, BASALAM_CLIENT_SECRET and BASALAM_REDIRECT_URI, or connect with a Personal Access Token instead.'
            );
        }
    }

    private function errorMessage(ApiResponse $response): string
    {
        return $response->transportError
            ?? $response->string('message')
            ?? $response->string('error_description')
            ?? $response->string('error')
            ?? 'Basalam returned HTTP '.($response->status ?? 0).'.';
    }

    /**
     * @return array<string, mixed>
     */
    private function config(): array
    {
        /** @var array<string, mixed> $config */
        $config = config('omnichannel.channels.basalam', []);

        return $config;
    }
}
