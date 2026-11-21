<?php

declare(strict_types=1);

namespace Karnoweb\Omnichannel\Channels\Gap;

use Illuminate\Http\Client\PendingRequest;
use Karnoweb\Omnichannel\Http\ApiResponse;
use Karnoweb\Omnichannel\Http\BaseApiClient;

final class GapApiClient extends BaseApiClient
{
    /**
     * @param array<string, mixed> $payload
     */
    public function sendMessage(string $token, array $payload): ApiResponse
    {
        return $this->form($token, 'sendMessage', $payload);
    }

    /**
     * @param array<string, mixed> $payload
     */
    public function editMessage(string $token, array $payload): ApiResponse
    {
        return $this->form($token, 'editMessage', $payload);
    }

    /**
     * @param array<string, mixed> $payload
     */
    public function deleteMessage(string $token, array $payload): ApiResponse
    {
        return $this->form($token, 'deleteMessage', $payload);
    }

    public function sendAction(string $token, string $chatId, string $action): ApiResponse
    {
        return $this->form($token, 'sendAction', [
            'chat_id' => $chatId,
            'action' => $action,
        ]);
    }

    public function uploadMedia(string $token, string $type, string $contents, string $filename): ApiResponse
    {
        return $this->send(fn (PendingRequest $request): ApiResponse => ApiResponse::fromResponse(
            $request
                ->withHeaders(['token' => $token])
                ->attach($type, $contents, $filename)
                ->post($this->url('upload'))
        ));
    }

    /**
     * @param array<string, mixed> $payload
     */
    private function form(string $token, string $method, array $payload): ApiResponse
    {
        return $this->send(fn (PendingRequest $request): ApiResponse => ApiResponse::fromResponse(
            $request
                ->withHeaders(['token' => $token])
                ->asForm()
                ->post($this->url($method), $payload)
        ));
    }

    private function url(string $method): string
    {
        return rtrim((string) config('omnichannel.channels.gap.base_url'), '/') . '/' . $method;
    }
}
