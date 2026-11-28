<?php

declare(strict_types=1);

namespace Karnoweb\Omnichannel\Http;

use Illuminate\Http\Client\ConnectionException;
use Illuminate\Http\Client\PendingRequest;
use Illuminate\Support\Facades\Http;
use Karnoweb\Omnichannel\Exceptions\RateLimitExceededException;
use Karnoweb\Omnichannel\Services\ApiRequestRateLimiter;
use Karnoweb\Omnichannel\Services\ApiRequestRateLimitScope;
use Karnoweb\Omnichannel\Services\SecretRedactor;
use Throwable;

abstract class BaseApiClient
{
    /**
     * @param  array<string, mixed>  $query
     */
    protected function get(string $url, array $query = [], ?string $token = null, bool $retrySafe = false): ApiResponse
    {
        return $this->send(fn (PendingRequest $request): ApiResponse => ApiResponse::fromResponse(
            $request->get($url, $query)
        ), $token, $retrySafe);
    }

    /**
     * @param  array<string, mixed>  $payload
     */
    protected function post(string $url, array $payload = [], ?string $token = null, bool $retrySafe = false): ApiResponse
    {
        return $this->send(fn (PendingRequest $request): ApiResponse => ApiResponse::fromResponse(
            $request->post($url, $payload)
        ), $token, $retrySafe);
    }

    /**
     * @param  array<string, mixed>  $payload
     */
    protected function patch(string $url, array $payload = [], ?string $token = null): ApiResponse
    {
        return $this->send(fn (PendingRequest $request): ApiResponse => ApiResponse::fromResponse(
            $request->patch($url, $payload)
        ), $token);
    }

    /**
     * @param  array<string, mixed>  $query
     */
    protected function delete(string $url, array $query = [], ?string $token = null): ApiResponse
    {
        return $this->send(fn (PendingRequest $request): ApiResponse => ApiResponse::fromResponse(
            $request->delete($url, $query)
        ), $token);
    }

    /**
     * @param  array<string, scalar>  $fields
     */
    protected function upload(
        string $url,
        string $fieldName,
        string $contents,
        string $filename,
        array $fields = [],
        ?string $token = null,
    ): ApiResponse {
        return $this->send(function (PendingRequest $request) use ($url, $fieldName, $contents, $filename, $fields): ApiResponse {
            $pending = $request->attach($fieldName, $contents, $filename);

            return ApiResponse::fromResponse($pending->post($url, $fields));
        }, $token);
    }

    /**
     * @param  callable(PendingRequest): ApiResponse  $callback
     */
    protected function send(callable $callback, ?string $token = null, bool $retrySafe = false, ?string $endpointBucket = null): ApiResponse
    {
        try {
            return $callback($this->request($token, $retrySafe, $endpointBucket));
        } catch (RateLimitExceededException $e) {
            return ApiResponse::localRateLimited($e->retryAfterSeconds);
        } catch (ConnectionException $e) {
            return ApiResponse::transportFailure(app(SecretRedactor::class)
                ->redactString($e->getMessage(), ['token' => $token]));
        } catch (Throwable $e) {
            return ApiResponse::transportFailure(
                app(SecretRedactor::class)
                    ->redactString(class_basename($e).': '.$e->getMessage(), ['token' => $token])
            );
        }
    }

    protected function request(?string $token = null, bool $retrySafe = false, ?string $endpointBucket = null): PendingRequest
    {
        $request = Http::acceptJson()
            ->connectTimeout((int) config('omnichannel.http.connect_timeout', 10))
            ->timeout((int) config('omnichannel.http.timeout', 60))
            ->withHeaders($this->defaultHeaders());

        $scope = app(ApiRequestRateLimitScope::class);
        $context = $scope->current();
        if ($context !== null) {
            if ($endpointBucket !== null && $endpointBucket !== '') {
                $context = new RateLimitContext(
                    $context->driverKey,
                    $context->accountFingerprint,
                    $endpointBucket,
                );
            }
            $limiter = app(ApiRequestRateLimiter::class);
            $request = $request->beforeSending(function () use ($limiter, $context): void {
                $limiter->consume($context);
            });
        }

        $retries = $retrySafe ? (int) config('omnichannel.http.retries', 2) : 0;

        if ($retries > 0) {
            $request = $request->retry(
                $retries + 1,
                (int) config('omnichannel.http.retry_delay_ms', 500),
                static fn (Throwable $exception): bool => $exception instanceof ConnectionException,
                throw: false,
            );
        }

        if ($token !== null && $token !== '') {
            $request = $request->withToken($token);
        }

        return $request;
    }

    /**
     * @return array<string, string>
     */
    protected function defaultHeaders(): array
    {
        return [
            'User-Agent' => config('app.name', 'Laravel').'/1.0 (+karnoweb-omnichannel)',
        ];
    }
}
