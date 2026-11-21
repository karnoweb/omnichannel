<?php

declare(strict_types=1);

namespace Karnoweb\Omnichannel\Tests\Feature;

use Illuminate\Support\Facades\Http;
use Karnoweb\Omnichannel\Http\HttpMediaBytesResolver;
use Orchestra\Testbench\TestCase;

final class HttpMediaBytesResolverSecurityTest extends TestCase
{
    public function test_rejects_shared_address_space_before_request(): void
    {
        Http::fake();
        $resolver = new HttpMediaBytesResolver(fn (): array => ['100.64.0.1']);
        self::assertNull($resolver->fetch('https://media.example/image.png'));
        Http::assertNothingSent();
    }

    public function test_rejects_private_destinations_without_making_a_request(): void
    {
        Http::fake();
        $resolver = new HttpMediaBytesResolver(fn (): array => ['127.0.0.1']);

        self::assertNull($resolver->fetch('http://media.example/image.png'));
        Http::assertNothingSent();
    }

    public function test_rejects_redirects(): void
    {
        Http::fake(['media.example/*' => Http::response('', 302, ['Location' => 'http://127.0.0.1/secret'])]);
        $resolver = new HttpMediaBytesResolver(fn (): array => ['93.184.216.34']);

        self::assertNull($resolver->fetch('https://media.example/image.png'));
    }

    public function test_rejects_declared_and_actual_payloads_over_the_configured_limit(): void
    {
        config(['omnichannel.media.max_bytes' => 8]);
        Http::fake(['media.example/*' => Http::response('0123456789', 200, ['Content-Type' => 'image/png'])]);
        $resolver = new HttpMediaBytesResolver(fn (): array => ['93.184.216.34']);

        self::assertNull($resolver->fetch('https://media.example/image.png'));
    }

    public function test_rejects_non_media_content_even_when_server_claims_image(): void
    {
        Http::fake(['media.example/*' => Http::response('<html>not an image</html>', 200, ['Content-Type' => 'image/png'])]);
        $resolver = new HttpMediaBytesResolver(fn (): array => ['93.184.216.34']);

        self::assertNull($resolver->fetch('https://media.example/image.png'));
    }

    public function test_rejects_benchmarking_ipv4_range(): void
    {
        Http::fake();
        $resolver = new HttpMediaBytesResolver(fn (): array => ['198.18.0.1']);
        self::assertNull($resolver->fetch('https://media.example/image.png'));
        Http::assertNothingSent();
    }

    public function test_rejects_documentation_ipv6_addresses(): void
    {
        Http::fake();
        $resolver = new HttpMediaBytesResolver(fn (): array => ['2001:db8::1']);
        self::assertNull($resolver->fetch('https://media.example/image.png'));
        Http::assertNothingSent();
    }
}
