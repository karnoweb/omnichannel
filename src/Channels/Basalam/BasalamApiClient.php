<?php

declare(strict_types=1);

namespace Karnoweb\Omnichannel\Channels\Basalam;

use Karnoweb\Omnichannel\Http\ApiResponse;
use Karnoweb\Omnichannel\Http\BaseApiClient;

final class BasalamApiClient extends BaseApiClient
{
    public const FILE_TYPE_PRODUCT_PHOTO = 'product.photo';

    public const FILE_TYPE_PRODUCT_VIDEO = 'product.video';

    public function me(string $token): ApiResponse
    {
        return $this->get($this->url('/v1/users/me'), [], $token);
    }

    /**
     * @param  array<string, mixed>  $payload
     */
    public function createProduct(int|string $vendorId, array $payload, string $token): ApiResponse
    {
        return $this->post($this->url("/v1/vendors/{$vendorId}/products"), $payload, $token);
    }

    /**
     * @param  array<string, mixed>  $payload
     */
    public function updateProduct(int|string $productId, array $payload, string $token): ApiResponse
    {
        return $this->patch($this->url("/v1/products/{$productId}"), $payload, $token);
    }

    public function getProduct(int|string $productId, string $token): ApiResponse
    {
        return $this->get($this->url("/v1/products/{$productId}"), [], $token);
    }

    /**
     * @param  array<string, mixed>  $query
     */
    public function getVendorProducts(int|string $vendorId, string $token, array $query = []): ApiResponse
    {
        return $this->get($this->url("/v1/vendors/{$vendorId}/products"), $query, $token);
    }

    public function getCategories(string $token): ApiResponse
    {
        return $this->get($this->url('/v1/categories'), [], $token);
    }

    public function getCategory(int|string $categoryId, string $token): ApiResponse
    {
        return $this->get($this->url("/v1/categories/{$categoryId}"), [], $token);
    }

    public function uploadFile(
        string $contents,
        string $filename,
        string $fileType,
        string $token,
    ): ApiResponse {
        return $this->upload(
            url: $this->url('/v1/files'),
            fieldName: 'file',
            contents: $contents,
            filename: $filename,
            fields: ['file_type' => $fileType],
            token: $token,
        );
    }

    private function url(string $path): string
    {
        return rtrim((string) config('omnichannel.channels.basalam.base_url'), '/').$path;
    }
}
