<?php

declare(strict_types=1);

namespace Karnoweb\Omnichannel\Channels\WhatsApp;

use Karnoweb\Omnichannel\Http\ApiResponse;
use Karnoweb\Omnichannel\Http\BaseApiClient;

final class WhatsAppCatalogClient extends BaseApiClient
{
    public const ITEM_TYPE_PRODUCT = 'PRODUCT_ITEM';

    /**
     * @param  'CREATE'|'UPDATE'|'DELETE'  $method
     * @param  array<string, mixed>  $data
     */
    public function applyItem(
        string $catalogId,
        string $method,
        array $data,
        string $token,
    ): ApiResponse {
        return $this->post(
            $this->endpoint($catalogId, 'items_batch'),
            [
                'item_type' => self::ITEM_TYPE_PRODUCT,
                'requests' => json_encode([[
                    'method' => $method,
                    'data' => $data,
                ]], JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES),
            ],
            $token,
        );
    }

    public function getCatalog(string $catalogId, string $token): ApiResponse
    {
        return $this->get(
            $this->endpoint($catalogId),
            ['fields' => 'id,name,product_count,vertical'],
            $token,
        );
    }

    public function checkBatchRequestStatus(string $catalogId, string $handle, string $token): ApiResponse
    {
        return $this->get(
            $this->endpoint($catalogId, 'check_batch_request_status'),
            ['handle' => $handle],
            $token,
            retrySafe: true,
        );
    }

    public function findByRetailerId(string $catalogId, string $retailerId, string $token): ApiResponse
    {
        return $this->get(
            $this->endpoint($catalogId, 'products'),
            [
                'fields' => 'id,retailer_id,name,availability,price,url',
                'filter' => json_encode([
                    'retailer_id' => ['eq' => $retailerId],
                ], JSON_UNESCAPED_UNICODE),
            ],
            $token,
        );
    }

    public function endpoint(string $path, ?string $edge = null): string
    {
        $base = rtrim((string) config('omnichannel.channels.whatsapp.graph_url'), '/');
        $version = trim((string) config('omnichannel.channels.whatsapp.api_version'), '/');

        $url = "{$base}/{$version}/".ltrim($path, '/');

        return $edge !== null ? $url.'/'.$edge : $url;
    }
}
