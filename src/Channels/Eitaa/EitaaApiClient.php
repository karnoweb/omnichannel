<?php

declare(strict_types=1);

namespace Karnoweb\Omnichannel\Channels\Eitaa;

use Illuminate\Http\Client\PendingRequest;
use Karnoweb\Omnichannel\Http\ApiResponse;
use Karnoweb\Omnichannel\Http\BaseApiClient;

final class EitaaApiClient extends BaseApiClient
{
    /**
     * @param  array<string, mixed>  $payload
     */
    public function sendMessage(string $token, array $payload): ApiResponse
    {
        return $this->send(fn (PendingRequest $request): ApiResponse => ApiResponse::fromResponse(
            $request->asForm()->post($this->url($token, 'sendMessage'), $payload)
        ));
    }

    /**
     * @param  array<string, scalar>  $fields
     */
    public function sendFile(string $token, array $fields, string $contents, string $filename): ApiResponse
    {
        return $this->upload(
            url: $this->url($token, 'sendFile'),
            fieldName: 'file',
            contents: $contents,
            filename: $filename,
            fields: $fields,
        );
    }

    public function url(string $token, string $method): string
    {
        $base = rtrim((string) config('omnichannel.channels.eitaa.base_url'), '/');

        return "{$base}/api/{$token}/{$method}";
    }
}
