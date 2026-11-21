<?php

declare(strict_types=1);

namespace Karnoweb\Omnichannel\Tests\Unit\Services;

use Karnoweb\Omnichannel\Services\SecretRedactor;
use Karnoweb\Omnichannel\Tests\TestCase;

final class SecretRedactorTest extends TestCase
{
    public function test_it_redacts_tokens_from_platform_urls_json_cookies_and_encoded_query_keys(): void
    {
        $redactor = new SecretRedactor;
        $token = 'audit-placeholder-token-value-123456';
        $text = implode("\n", [
            'Request failed: https://api.telegram.org/bot'.$token.'/sendMessage',
            '{"access_token":"'.$token.'","safe":"visible"}',
            'Cookie: session='.$token.'; Path=/',
            'https://example.test/?access%5Ftoken='.$token.'&page=2',
            'Authorization: Bearer '.$token,
            'Authorization: Basic '.base64_encode('user:'.$token),
            'https://api.rubika.ir/'.$token.'/sendMessage',
            'https://eitaayar.ir/api/'.$token.'/sendMessage',
            'IGQVJabcdef0123456789 EAAabcdef0123456789012345',
        ]);

        $redacted = $redactor->redactString($text);

        self::assertStringNotContainsString($token, $redacted);
        self::assertStringContainsString('"safe":"visible"', $redacted);
        self::assertStringContainsString('page=2', $redacted);
        self::assertStringNotContainsString('IGQVJabcdef', $redacted);
        self::assertStringNotContainsString('EAAabcdef', $redacted);
    }

    public function test_it_redacts_secrets_in_nested_arrays_and_trim_preview(): void
    {
        $redactor = new SecretRedactor;
        $token = 'audit-placeholder-token-value-987654';
        $nested = $redactor->redact(['outer' => ['access_token' => $token, 'message' => $token]], 0, ['token' => $token]);

        self::assertSame(SecretRedactor::MASK, $nested['outer']['access_token']);
        self::assertStringNotContainsString($token, $nested['outer']['message']);

        $large = $redactor->redactAndTrim(['message' => str_repeat('safe ', 500).'access_token='.$token], 256);
        self::assertArrayHasKey('_truncated', $large);
        $trimmed = $redactor->redactAndTrim(['message' => str_repeat('safe ', 80).'access_token='.$token], 256);
        self::assertArrayHasKey('_truncated', $trimmed);
        self::assertStringNotContainsString($token, json_encode($trimmed) ?: '');
    }
}
