<?php

declare(strict_types=1);

namespace Karnoweb\Omnichannel\Channels\Rubika;

use Karnoweb\Omnichannel\Http\ApiResponse;
use Karnoweb\Omnichannel\Http\BaseApiClient;

final class RubikaApiClient extends BaseApiClient
{
    public function getMe(string $token): ApiResponse
    {
        return $this->call($token, 'getMe');
    }

    /**
     * @param  array<string, mixed>  $payload
     */
    public function sendMessage(string $token, array $payload): ApiResponse
    {
        return $this->call($token, 'sendMessage', $payload);
    }

    /**
     * @param  array<string, mixed>  $payload
     */
    public function sendFile(string $token, array $payload): ApiResponse
    {
        return $this->call($token, 'sendFile', $payload);
    }

    public function requestSendFile(string $token, string $type): ApiResponse
    {
        return $this->call($token, 'requestSendFile', ['type' => $type]);
    }

    /**
     * @param  array<string, mixed>  $payload
     */
    public function editMessageText(string $token, array $payload): ApiResponse
    {
        return $this->call($token, 'editMessageText', $payload);
    }

    /**
     * @param  array<string, mixed>  $payload
     */
    public function deleteMessage(string $token, array $payload): ApiResponse
    {
        return $this->call($token, 'deleteMessage', $payload);
    }

    /**
     * @param  array<string, mixed>  $payload
     */
    public function getChat(string $token, array $payload): ApiResponse
    {
        return $this->call($token, 'getChat', $payload);
    }

    public function uploadTo(string $uploadUrl, string $contents, string $filename): ApiResponse
    {
        return $this->upload(
            url: $uploadUrl,
            fieldName: 'file',
            contents: $contents,
            filename: $filename,
        );
    }

    public function endpoint(string $token, string $method): string
    {
        $base = rtrim((string) config('omnichannel.channels.rubika.base_url'), '/');

        return "{$base}/{$token}/{$method}";
    }

    /**
     * @param  array<string, mixed>  $payload
     */
    private function call(string $token, string $method, array $payload = []): ApiResponse
    {
        return $this->post($this->endpoint($token, $method), $payload);
    }
}
