<?php

declare(strict_types=1);

namespace Karnoweb\Omnichannel\Channels\Messaging;

use Karnoweb\Omnichannel\Http\ApiResponse;
use Karnoweb\Omnichannel\Http\BaseApiClient;

class BotApiClient extends BaseApiClient
{
    public function __construct(private readonly string $baseUrl) {}

    public function getMe(string $token): ApiResponse
    {
        return $this->post($this->endpoint($token, 'getMe'));
    }

    /**
     * @param  array<string, mixed>  $payload
     */
    public function sendMessage(string $token, array $payload): ApiResponse
    {
        return $this->post($this->endpoint($token, 'sendMessage'), $payload);
    }

    /**
     * @param  array<string, mixed>  $payload
     */
    public function sendPhoto(string $token, array $payload): ApiResponse
    {
        return $this->post($this->endpoint($token, 'sendPhoto'), $payload);
    }

    /**
     * @param  array<string, mixed>  $payload
     */
    public function sendVideo(string $token, array $payload): ApiResponse
    {
        return $this->post($this->endpoint($token, 'sendVideo'), $payload);
    }

    /**
     * @param  array<string, mixed>  $payload
     */
    public function sendDocument(string $token, array $payload): ApiResponse
    {
        return $this->post($this->endpoint($token, 'sendDocument'), $payload);
    }

    /**
     * @param  array<string, mixed>  $payload
     */
    public function sendMediaGroup(string $token, array $payload): ApiResponse
    {
        return $this->post($this->endpoint($token, 'sendMediaGroup'), $payload);
    }

    /**
     * @param  array<string, mixed>  $payload
     */
    public function editMessageText(string $token, array $payload): ApiResponse
    {
        return $this->post($this->endpoint($token, 'editMessageText'), $payload);
    }

    /**
     * @param  array<string, mixed>  $payload
     */
    public function editMessageCaption(string $token, array $payload): ApiResponse
    {
        return $this->post($this->endpoint($token, 'editMessageCaption'), $payload);
    }

    /**
     * @param  array<string, mixed>  $payload
     */
    public function deleteMessage(string $token, array $payload): ApiResponse
    {
        return $this->post($this->endpoint($token, 'deleteMessage'), $payload);
    }

    /**
     * @param  array<string, mixed>  $payload
     */
    public function pinChatMessage(string $token, array $payload): ApiResponse
    {
        return $this->post($this->endpoint($token, 'pinChatMessage'), $payload);
    }

    public function endpoint(string $token, string $method): string
    {
        return rtrim($this->baseUrl, '/')."/bot{$token}/{$method}";
    }
}
