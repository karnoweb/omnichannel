<?php

declare(strict_types=1);

namespace Karnoweb\Omnichannel\Services;

final class SecretRedactor
{
    public const MASK = '[REDACTED]';

    private const SENSITIVE_KEYS = [
        'access_token', 'refresh_token', 'client_secret', 'client_id', 'app_secret', 'authorization',
        'proxy_authorization', 'password', 'passwd', 'secret', 'token', 'bot_token', 'gateway_token',
        'api_key', 'apikey', 'private_key', 'credentials', 'code', 'code_verifier', 'session',
        'cookie', 'set_cookie', 'x_api_key', 'signature', 'appsecret_proof', 'state',
    ];

    /** @param array<array-key, mixed> $data @param array<array-key, mixed> $secrets @return array<array-key, mixed> */
    public function redact(array $data, int $depth = 0, array $secrets = []): array
    {
        if ($depth > 12) {
            return ['_truncated' => true];
        }

        $result = [];
        foreach ($data as $key => $value) {
            if (is_string($key) && $this->isSensitiveKey($key)) {
                $result[$key] = self::MASK;
            } elseif (is_array($value)) {
                $result[$key] = $this->redact($value, $depth + 1, $secrets);
            } elseif (is_string($value)) {
                $result[$key] = $this->redactString($value, $secrets);
            } else {
                $result[$key] = $value;
            }
        }

        return $result;
    }

    /** @param array<array-key, mixed> $secrets */
    public function redactString(string $value, array $secrets = []): string
    {
        $value = $this->redactUrl($value);
        $value = (string) preg_replace_callback(
            '/("(?:access_token|refresh_token|client_secret|client_id|api_key|apikey|token|password|authorization|cookie|session|code)"\s*:\s*)"[^\"]*"/i',
            static fn (array $m): string => $m[1].'"'.self::MASK.'"',
            $value,
        );
        $value = (string) preg_replace('/\b(Bearer|Basic|Token)(\s+)[^\s"\']+/i', '$1$2'.self::MASK, $value);
        $value = (string) preg_replace('/\b(Cookie|Set-Cookie)\s*:\s*[^\r\n]*/i', '$1: '.self::MASK, $value);
        $value = (string) preg_replace('/\bIGQ[A-Za-z0-9\-._]{16,}/', self::MASK, $value);
        $value = (string) preg_replace('/\bEAA[A-Za-z0-9]{16,}/', self::MASK, $value);
        $value = (string) preg_replace_callback(
            '/\b(access_token|refresh_token|client_secret|client_id|api_key|apikey|proxy_authorization|authorization|bot_token|gateway_token|token|password|passwd|secret|code_verifier|code|signature|session|cookie|state)(=|%3[dD])([^&\s"\']*)/i',
            static fn (array $m): string => $m[1].$m[2].rawurlencode(self::MASK),
            $value,
        );

        $values = [];
        array_walk_recursive($secrets, static function (mixed $secret) use (&$values): void {
            if (is_string($secret) && strlen($secret) >= 4) {
                $values[] = $secret;
            }
        });
        usort($values, static fn (string $a, string $b): int => strlen($b) <=> strlen($a));
        foreach (array_unique($values) as $secret) {
            foreach (array_unique([$secret, rawurlencode($secret), urlencode($secret)]) as $variant) {
                $value = str_replace($variant, self::MASK, $value);
            }
        }

        return $value;
    }

    private function redactUrl(string $value): string
    {
        return (string) preg_replace_callback('~https?://[^\s"\'<>]+~i', function (array $m): string {
            $parts = parse_url($m[0]);
            if (! is_array($parts)) {
                return $m[0];
            }
            $host = strtolower((string) ($parts['host'] ?? ''));
            $path = (string) ($parts['path'] ?? '');
            if (str_contains($host, 'telegram.org') || str_contains($host, 'bale.ai')) {
                $path = (string) preg_replace('~(/bot)[A-Za-z0-9_-]{12,}(?=/|$)~i', '$1'.self::MASK, $path);
            } elseif (str_contains($host, 'rubika')) {
                $path = (string) preg_replace('~^/[A-Za-z0-9_-]{24,}(?=/|$)~', '/'.self::MASK, $path);
            } elseif (str_contains($host, 'eitaayar.ir')) {
                $path = (string) preg_replace('~(/api/)[A-Za-z0-9_-]{12,}(?=/|$)~i', '$1'.self::MASK, $path);
            }
            $authority = (string) ($parts['host'] ?? '');
            if (isset($parts['port'])) {
                $authority .= ':'.$parts['port'];
            }
            $query = $this->redactQuery((string) ($parts['query'] ?? ''));

            return ($parts['scheme'] ?? 'https').'://'.$authority.$path
                .($query !== '' ? '?'.$query : '')
                .(isset($parts['fragment']) ? '#'.$parts['fragment'] : '');
        }, $value);
    }

    private function redactQuery(string $query): string
    {
        $pairs = [];
        foreach (explode('&', $query) as $pair) {
            [$key, $secret] = array_pad(explode('=', $pair, 2), 2, null);
            $pairs[] = $secret === null || ! $this->isSensitiveKey(urldecode($key))
                ? $pair : $key.'='.rawurlencode(self::MASK);
        }

        return implode('&', $pairs);
    }

    public function isSensitiveKey(string $key): bool
    {
        $normalized = strtolower(str_replace(['-', ' '], '_', $key));
        if (in_array($normalized, self::SENSITIVE_KEYS, true)) {
            return true;
        }
        foreach (self::SENSITIVE_KEYS as $sensitive) {
            if (str_contains($sensitive, '_') && str_ends_with($normalized, $sensitive)) {
                return true;
            }
        }

        return false;
    }

    /** @param array<array-key, mixed> $data @param array<array-key, mixed> $secrets @return array<array-key, mixed> */
    public function redactAndTrim(array $data, int $maxBytes = 8192, array $secrets = []): array
    {
        $maxBytes = max(64, $maxBytes);
        $redacted = $this->redact($data, 0, $secrets);
        $encoded = json_encode($redacted, JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES);
        if ($encoded !== false && strlen($encoded) <= $maxBytes) {
            return $redacted;
        }

        return ['_truncated' => true, '_original_bytes' => $encoded === false ? null : strlen($encoded),
            'preview' => $this->redactString(substr((string) $encoded, 0, min(1024, $maxBytes - 128)), $secrets)];
    }
}
