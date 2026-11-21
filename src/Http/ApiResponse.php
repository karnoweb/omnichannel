<?php

declare(strict_types=1);

namespace Karnoweb\Omnichannel\Http;

use DateTimeImmutable;
use Illuminate\Http\Client\Response;
use Karnoweb\Omnichannel\Services\SecretRedactor;
use Throwable;

final readonly class ApiResponse
{
    /**
     * @param array<string, mixed>        $data
     * @param array<string, list<string>> $headers
     */
    private function __construct(
        public bool $successful,
        public ?int $status,
        public array $data = [],
        public array $headers = [],
        public ?string $transportError = null,
        public ?string $rawBody = null,
    ) {}

    public static function fromResponse(Response $response): self
    {
        $decoded = $response->json();

        return new self(
            successful: $response->successful(),
            status: $response->status(),
            data: is_array($decoded) ? $decoded : [],
            headers: $response->headers(),
            rawBody: is_array($decoded) ? null : $response->body(),
        );
    }

    public static function transportFailure(string $message): self
    {
        return new self(
            successful: false,
            status: null,
            transportError: $message,
        );
    }

    public static function localRateLimited(int $retryAfterSeconds): self
    {
        return new self(
            successful: false,
            status: 0,
            data: [
                'error' => 'local_rate_limit',
                'retry_after' => max(0, $retryAfterSeconds),
            ],
        );
    }

    public function isLocalRateLimited(): bool
    {
        return $this->status === 0 && $this->string('error') === 'local_rate_limit';
    }

    public function isPlatformRateLimited(): bool
    {
        return $this->status === 429;
    }

    public function failed(): bool
    {
        return ! $this->successful;
    }

    public function isTransportFailure(): bool
    {
        return $this->status === null;
    }

    public function get(string $key, mixed $default = null): mixed
    {
        return data_get($this->data, $key, $default);
    }

    public function string(string $key): ?string
    {
        $value = $this->get($key);

        return is_scalar($value) ? (string) $value : null;
    }

    public function int(string $key): ?int
    {
        $value = $this->get($key);

        return is_numeric($value) ? (int) $value : null;
    }

    public function retryAfterSeconds(): ?int
    {
        if ($this->isLocalRateLimited()) {
            $local = $this->get('retry_after');

            return is_numeric($local) ? max(0, (int) $local) : null;
        }

        $bodyValue = $this->get('parameters.retry_after');
        if (is_numeric($bodyValue) && (int) $bodyValue >= 0) {
            return (int) $bodyValue;
        }

        $header = $this->header('Retry-After');
        if ($header === null || trim($header) === '') {
            return null;
        }
        if (is_numeric($header)) {
            return max(0, (int) $header);
        }

        try {
            return max(0, (new DateTimeImmutable($header))->getTimestamp() - time());
        } catch (Throwable) {
            return null;
        }
    }

    public function header(string $name): ?string
    {
        foreach ($this->headers as $key => $values) {
            if (strcasecmp($key, $name) === 0) {
                return $values[0] ?? null;
            }
        }

        return null;
    }

    /**
     * @return array<string, mixed>
     */
    public function toLogArray(): array
    {
        $data = array_filter([
            'status' => $this->status,
            'transport_error' => $this->transportError,
            'body' => $this->data !== [] ? $this->data : null,
            'raw' => $this->rawBody !== null ? mb_substr($this->rawBody, 0, 8192) : null,
        ], static fn (mixed $v): bool => $v !== null);

        return app(SecretRedactor::class)->redactAndTrim($data, 8192, $this->data);
    }
}
