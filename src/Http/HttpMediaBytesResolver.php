<?php

declare(strict_types=1);

namespace Karnoweb\Omnichannel\Http;

use GuzzleHttp\Handler\CurlHandler;
use GuzzleHttp\Psr7\FnStream;
use GuzzleHttp\Psr7\Utils;
use Illuminate\Support\Facades\Http;
use Karnoweb\Omnichannel\Contracts\MediaBytesResolverContract;
use Karnoweb\Omnichannel\Contracts\PublicationMediaStorageContract;
use Karnoweb\Omnichannel\Models\PublicationMediaAsset;
use Karnoweb\Omnichannel\Enums\MediaAssetStatus;
use RuntimeException;
use Throwable;

final class HttpMediaBytesResolver implements MediaBytesResolverContract
{
    /** @var \Closure(string): array<int, string>|null */
    private readonly \Closure $addressResolver;

    /** @param null|callable(string): array<int, string>|null $addressResolver */
    public function __construct(?callable $addressResolver = null)
    {
        $this->addressResolver = $addressResolver === null
            ? fn (string $host): ?array => $this->resolvePublicAddresses($host)
            : \Closure::fromCallable($addressResolver);
    }

    public function fetch(string $url): ?string
    {
        if (str_starts_with($url, 'omc-asset:')) {
            return $this->fetchStoredAsset(substr($url, 10));
        }

        $target = $this->validatedTarget($url);
        if ($target === null) {
            return null;
        }

        [$host, $port, $addresses] = $target;
        $temporaryPath = tempnam(sys_get_temp_dir(), 'omc-media-');
        if ($temporaryPath === false) {
            return null;
        }

        try {
            $limit = max(1, (int) config('omnichannel.media.max_bytes', 52428800));
            if (! extension_loaded('curl')) {
                return null;
            }
            $stream = Utils::streamFor(fopen($temporaryPath, 'w+b'));
            $written = 0;
            $sink = FnStream::decorate($stream, [
                'write' => static function (string $bytes) use ($stream, $limit, &$written): int {
                    if (strlen($bytes) > $limit - $written) {
                        throw new RuntimeException('Media download exceeds configured size limit.');
                    }
                    $count = $stream->write($bytes);
                    $written += $count;

                    return $count;
                },
            ]);
            $curlResolve = [];
            foreach ($addresses as $address) {
                $curlResolve[] = sprintf('%s:%d:%s', $host, $port, str_contains($address, ':') ? '['.$address.']' : $address);
            }

            $request = Http::timeout((int) config('omnichannel.http.timeout', 60))
                ->connectTimeout((int) config('omnichannel.http.connect_timeout', 10))
                ->withOptions([
                    'allow_redirects' => false,
                    'handler' => new CurlHandler,
                    'proxy' => '',
                    'verify' => true,
                    'curl' => [CURLOPT_RESOLVE => $curlResolve],
                    'sink' => $sink,
                ]);

            $response = $request->get($url);
            if (! $response->successful() || $response->toPsrResponse()->getBody()->getSize() > $limit) {
                return null;
            }
            if (! is_file($temporaryPath) || filesize($temporaryPath) > $limit) {
                return null;
            }

            $actualSize = filesize($temporaryPath);
            if ($actualSize === false || $actualSize < 1 || $actualSize > $limit) {
                return null;
            }

            $bytes = file_get_contents($temporaryPath);
            if ($bytes === false || ! $this->isAllowedMedia($bytes)) {
                return null;
            }

            return $bytes;
        } catch (Throwable) {
            return null;
        } finally {
            if (isset($sink)) {
                $sink->close();
            }
            @unlink($temporaryPath);
        }
    }

    private function fetchStoredAsset(string $id): ?string
    {
        if (! is_numeric($id)) {
            return null;
        }
        $asset = PublicationMediaAsset::query()->find((int) $id);
        if ($asset === null || $asset->status !== MediaAssetStatus::Ready) {
            return null;
        }
        if (! app()->bound(PublicationMediaStorageContract::class)) {
            return null;
        }

        return app(PublicationMediaStorageContract::class)->read($asset);
    }

    /** @return array{string, int, array<int, string>}|null */
    private function validatedTarget(string $url): ?array
    {
        $parts = parse_url($url);
        if (! is_array($parts) || ! isset($parts['scheme'], $parts['host']) || isset($parts['user']) || isset($parts['pass'])) {
            return null;
        }

        $scheme = strtolower((string) $parts['scheme']);
        if (! in_array($scheme, ['http', 'https'], true)) {
            return null;
        }

        $host = trim((string) $parts['host'], '[]');
        $port = (int) ($parts['port'] ?? ($scheme === 'https' ? 443 : 80));
        if ($host === '' || $port < 1 || $port > 65535) {
            return null;
        }

        $addresses = ($this->addressResolver)($host);
        if (! is_array($addresses) || $addresses === []) {
            return null;
        }

        foreach ($addresses as $address) {
            if (! is_string($address) || ! $this->isPublicAddress($address)) {
                return null;
            }
        }

        return [$host, $port, array_values(array_unique($addresses))];
    }

    /** @return array<int, string>|null */
    private function resolvePublicAddresses(string $host): ?array
    {
        if (filter_var($host, FILTER_VALIDATE_IP) !== false) {
            return $this->isPublicAddress($host)
                ? [$host]
                : null;
        }

        $records = @dns_get_record($host, DNS_A | DNS_AAAA);
        if (! is_array($records) || $records === []) {
            return null;
        }

        $addresses = [];
        foreach ($records as $record) {
            $address = $record['ip'] ?? $record['ipv6'] ?? null;
            if (is_string($address)) {
                $addresses[] = $address;
            }
        }

        return $addresses === [] ? null : $addresses;
    }

    private function isPublicAddress(string $address): bool
    {
        if (filter_var($address, FILTER_VALIDATE_IP, FILTER_FLAG_NO_PRIV_RANGE | FILTER_FLAG_NO_RES_RANGE) === false) {
            return false;
        }
        $packed = inet_pton($address);
        if ($packed === false) {
            return false;
        }
        if (strlen($packed) === 4) {
            $octets = array_values(unpack('C4', $packed));

            return ! ($octets[0] === 100 && $octets[1] >= 64 && $octets[1] <= 127)
                && ! ($octets[0] === 192 && $octets[1] === 0)
                && ! ($octets[0] === 198 && in_array($octets[1], [18, 19, 51], true))
                && ! ($octets[0] === 203 && $octets[1] === 0 && $octets[2] === 113)
                && $octets[0] < 224;
        }
        // Accept only native global unicast; reject mapped and transition ranges.
        $hex = bin2hex($packed);

        return (ord($packed[0]) & 0xE0) === 0x20
            && ! str_starts_with($hex, '2001')
            && ! str_starts_with($hex, '2002')
            && ! str_starts_with($hex, '3fff');
    }

    private function isAllowedMedia(string $bytes): bool
    {
        if (! class_exists(\finfo::class)) {
            return false;
        }

        $mime = (new \finfo(FILEINFO_MIME_TYPE))->buffer($bytes);
        if (! is_string($mime)) {
            return false;
        }

        $allowed = config('omnichannel.media.allowed_mime_types', ['image/*', 'video/*']);
        foreach ((array) $allowed as $type) {
            if (is_string($type) && ($mime === $type || (str_ends_with($type, '/*') && str_starts_with($mime, substr($type, 0, -1))))) {
                return true;
            }
        }

        return false;
    }
}
