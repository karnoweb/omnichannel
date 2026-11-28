<?php

declare(strict_types=1);

namespace Karnoweb\Omnichannel\Channels\Basalam;

use Karnoweb\Omnichannel\Contracts\ChannelDriver;
use Karnoweb\Omnichannel\Contracts\DeactivatableChannelDriver;
use Karnoweb\Omnichannel\Contracts\SupportsMarketplaceTaxonomy;
use Karnoweb\Omnichannel\Enums\Capability;
use Karnoweb\Omnichannel\Enums\FailureClass;
use Karnoweb\Omnichannel\Enums\PublicationType;
use Karnoweb\Omnichannel\Http\ApiResponse;
use Karnoweb\Omnichannel\Models\ChannelConnection;
use Karnoweb\Omnichannel\Models\Publication;
use Karnoweb\Omnichannel\Results\ConnectionTestResult;
use Karnoweb\Omnichannel\Results\DeleteResult;
use Karnoweb\Omnichannel\Results\PublishResult;
use Karnoweb\Omnichannel\Results\ValidationResult;
use Karnoweb\Omnichannel\ValueObjects\ChannelCapabilities;
use Karnoweb\Omnichannel\ValueObjects\MediaItem;

final class BasalamDriver implements ChannelDriver, DeactivatableChannelDriver, SupportsMarketplaceTaxonomy
{
    public function __construct(
        private readonly BasalamApiClient $api,
        private readonly BasalamTransformer $transformer,
        private readonly BasalamMediaUploader $uploader,
        private readonly BasalamCategoryMapper $categories,
        private readonly BasalamErrorMapper $errors,
    ) {}

    public function key(): string
    {
        return 'basalam';
    }

    public function capabilities(): ChannelCapabilities
    {
        return ChannelCapabilities::make(
            supported: [
                Capability::Text,
                Capability::Image,
                Capability::Video,
                Capability::Product,
                Capability::Update,
                Capability::Deactivate,
                Capability::Scheduling,
                Capability::PriceAndStock,
            ],
            unsupportedReasons: [
                Capability::Delete->value => 'The Basalam public API has no product delete endpoint. Use Deactivate to move the listing to draft status, or delete it from the Basalam vendor panel.',
                Capability::Post->value => 'Basalam publishes marketplace product listings, not feed posts.',
                Capability::Carousel->value => 'Basalam has no carousel; a product carries a photo gallery instead.',
                Capability::Reel->value => 'Basalam has no reel format.',
                Capability::Story->value => 'Basalam has no story format in the product API.',
                Capability::Hashtags->value => 'Basalam has no hashtags; canonical hashtags are sent as product keywords instead.',
                Capability::AltText->value => 'The Basalam product photo schema has no alt-text field.',
                Capability::Thumbnail->value => 'A Basalam listing uses its first photo as the thumbnail; there is no separate cover field.',
                Capability::Message->value => 'Basalam publishes product listings. Its chat service is a separate API and a customer-support feature, not a publishing destination.',
                Capability::Album->value => 'A Basalam product carries a photo gallery rather than a grouped message.',
                Capability::Pin->value => 'A Basalam listing cannot be pinned through the API.',
                Capability::SilentDelivery->value => 'Publishing a listing sends no notification, so there is nothing to silence.',
                Capability::NativeScheduling->value => 'The Basalam API creates products immediately. Scheduling is handled by this package.',
            ],
            limits: (array) config('omnichannel.channels.basalam.limits', []),
            updateFields: ['title', 'caption', 'hashtags', 'extra.price', 'extra.stock'],
            clearUpdateFields: [],
        );
    }

    public function validate(Publication $publication): ValidationResult
    {
        if ($publication->type !== PublicationType::Product) {
            return ValidationResult::error(
                'basalam.type.unsupported',
                trans('omnichannel::omnichannel.validation.type_unsupported', ['type' => $publication->type->label()]),
            );
        }

        $content = $publication->content();
        $limits = (array) config('omnichannel.channels.basalam.limits', []);
        $name = $content->title ?? $publication->product_name ?? '';

        $result = ValidationResult::make()
            ->errorIf(
                trim($name) === '',
                'basalam.name.required',
                trans('omnichannel::omnichannel.validation.product_name_required'),
            )
            ->errorIf(
                $content->images() === [],
                'basalam.photo.required',
                trans('omnichannel::omnichannel.validation.product_photo_required'),
            )
            ->errorIf(
                count($content->images()) > (int) $limits['max_photos'],
                'basalam.photo.too_many',
                trans('omnichannel::omnichannel.validation.too_many_photos', ['max' => $limits['max_photos']]),
            );

        $category = $this->categories->resolve(
            (string) ($content->extra('category') ?? ''),
            $publication->connection,
            $content->extra('basalam.category_id'),
        );

        $result = $result->errorIf(
            $category === null,
            'basalam.category.unmapped',
            trans('omnichannel::omnichannel.validation.category_unmapped', [
                'category' => (string) ($content->extra('category') ?? '—'),
            ]),
        );

        foreach ($content->images() as $index => $image) {
            $result = $result->merge($this->validatePhoto($image, $index));
        }

        $price = $content->extra('price');

        return $result->errorIf(
            $price !== null && (! is_numeric($price) || (int) $price < 0),
            'basalam.price.invalid',
            trans('omnichannel::omnichannel.validation.price_invalid'),
        );
    }

    public function testConnection(ChannelConnection $connection): ConnectionTestResult
    {
        $token = $this->token($connection);

        if ($token === null) {
            return ConnectionTestResult::failure(
                FailureClass::Authentication,
                'This Basalam connection has no access token. Reconnect the shop.',
                'basalam.auth.missing_token',
            );
        }

        $response = $this->api->me($token);

        if ($response->failed()) {
            return ConnectionTestResult::failure(
                ($response->isTransportFailure() || $response->status === 408 || ($response->status !== null && $response->status >= 500)) ? FailureClass::UnknownOutcome : $this->errors->classify($response),
                $this->errors->technicalMessage($response),
                $this->errors->errorCodeString($response),
            );
        }

        $vendorId = $response->get('vendor.id');

        if (! is_numeric($vendorId)) {
            return ConnectionTestResult::failure(
                FailureClass::Permission,
                'This Basalam account has no vendor shop attached, so it cannot publish products.',
                'basalam.auth.no_vendor',
            );
        }

        return ConnectionTestResult::success(
            accountId: (string) $vendorId,
            accountName: $response->string('vendor.title') ?? $response->string('name'),
            account: $response->data,
        );
    }

    public function publish(Publication $publication): PublishResult
    {
        $storedExternalId = $publication->persistentArtifact('publish', 'external_id');
        if (is_string($storedExternalId) && $storedExternalId !== '') {
            return PublishResult::success($storedExternalId, $this->productUrl($storedExternalId), ['idempotent_replay' => true]);
        }

        $validation = $this->validate($publication);

        if ($validation->fails()) {
            return PublishResult::failure(
                FailureClass::Validation,
                $validation->summary(),
                $validation->errorCodes()[0] ?? 'basalam.validation.failed',
                ['validation' => $validation->toArray()],
            );
        }

        $connection = $publication->connection;
        $token = $this->token($connection);
        $vendorId = $this->vendorId($connection);

        if ($token === null || $vendorId === null) {
            return PublishResult::failure(
                FailureClass::Authentication,
                'This Basalam connection is missing its access token or vendor id. Reconnect it.',
                'basalam.auth.incomplete_connection',
            );
        }

        $uploaded = $this->uploader->uploadFor($publication, $token);

        if ($uploaded instanceof ApiResponse) {
            return $this->failureFrom($uploaded, ['stage' => 'upload_media']);
        }

        $content = $publication->content();

        $categoryId = $this->categories->resolve(
            (string) ($content->extra('category') ?? ''),
            $connection,
            $content->extra('basalam.category_id'),
        );

        if ($categoryId === null) {
            return PublishResult::failure(
                FailureClass::Validation,
                trans('omnichannel::omnichannel.validation.category_unmapped', [
                    'category' => (string) ($content->extra('category') ?? '—'),
                ]),
                'basalam.category.unmapped',
            );
        }

        $payload = $this->transformer->build(
            $publication,
            $uploaded['photo_ids'],
            $uploaded['video_id'],
            $categoryId,
        );

        $response = $this->api->createProduct($vendorId, $payload, $token);

        if ($response->failed()) {
            return $this->failureFrom($response, ['stage' => 'create_product']);
        }

        $productId = $response->string('id') ?? $response->string('data.id');

        if ($productId === null) {
            return PublishResult::failure(
                FailureClass::UnknownOutcome,
                'Basalam accepted the product but returned no product id.',
                'basalam.product.no_id',
            );
        }

        $externalUrl = $this->productUrl($productId);
        $publication->rememberPersistentArtifact('publish', 'external_id', $productId);

        return PublishResult::success(
            $productId,
            $externalUrl,
            [
                'vendor_id' => $vendorId,
                'category_id' => $categoryId,
                'photo_ids' => $uploaded['photo_ids'],
            ],
        );
    }

    public function update(Publication $publication): PublishResult
    {
        $token = $this->token($publication->connection);

        if ($token === null || $publication->external_id === null) {
            return PublishResult::failure(
                FailureClass::Authentication,
                'This Basalam connection cannot be used to update the product.',
                'basalam.auth.incomplete_connection',
            );
        }

        $response = $this->api->updateProduct(
            $publication->external_id,
            $this->transformer->buildUpdate($publication),
            $token,
        );

        if ($response->failed()) {
            return $this->failureFrom($response, ['stage' => 'update_product']);
        }

        return PublishResult::success(
            $publication->external_id,
            $publication->external_url ?? $this->productUrl($publication->external_id),
            ['updated' => true],
        );
    }

    public function delete(Publication $publication): DeleteResult
    {
        return DeleteResult::unsupported(
            (string) $this->capabilities()->reasonFor(Capability::Delete)
        );
    }

    public function fetchExternalCategories(ChannelConnection $connection, ?string $scopeKey = null): array
    {
        $token = $this->token($connection);
        if ($token === null) {
            return [];
        }

        $response = $this->api->getCategories($token);
        if ($response->failed()) {
            return [];
        }

        $items = $response->get('data');
        if (! is_array($items)) {
            $items = is_array($response->data) ? $response->data : [];
        }

        $rows = [];
        foreach ($items as $item) {
            if (! is_array($item)) {
                continue;
            }
            $id = $item['id'] ?? null;
            if (! is_scalar($id) || (string) $id === '') {
                continue;
            }
            $rows[] = [
                'scope_key' => $scopeKey ?? 'global',
                'external_id' => (string) $id,
                'external_parent_id' => isset($item['parent_id']) && is_scalar($item['parent_id'])
                    ? (string) $item['parent_id']
                    : null,
                'name' => (string) ($item['title'] ?? $item['name'] ?? 'Unknown'),
                'path' => isset($item['path']) && is_string($item['path']) ? $item['path'] : null,
                'is_leaf' => (bool) ($item['is_leaf'] ?? ! isset($item['children'])),
                'is_active' => (bool) ($item['is_active'] ?? true),
                'metadata' => ['source' => 'basalam.categories'],
            ];
        }

        return $rows;
    }

    public function deactivate(Publication $publication): PublishResult
    {
        $token = $this->token($publication->connection);

        if ($token === null || $publication->external_id === null) {
            return PublishResult::failure(
                FailureClass::Authentication,
                'This Basalam connection cannot be used to deactivate the product.',
                'basalam.auth.incomplete_connection',
            );
        }

        $response = $this->api->updateProduct(
            $publication->external_id,
            $this->transformer->buildDeactivate(),
            $token,
        );

        if ($response->failed()) {
            return $this->failureFrom($response, ['stage' => 'deactivate_product']);
        }

        return PublishResult::success(
            $publication->external_id,
            $publication->external_url,
            ['deactivated' => true],
        );
    }

    private function validatePhoto(MediaItem $item, int $index): ValidationResult
    {
        /** @var array<string, mixed> $spec */
        $spec = config('omnichannel.channels.basalam.limits.photo', []);

        /** @var list<string> $extensions */
        $extensions = $spec['extensions'] ?? [];

        return ValidationResult::make()
            ->errorIf(
                $item->extension() !== null && ! in_array($item->extension(), $extensions, true),
                "basalam.photo.{$index}.format",
                trans('omnichannel::omnichannel.validation.image_format', ['formats' => 'JPEG, PNG, WEBP']),
                ['extension' => $item->extension()],
            )
            ->errorIf(
                $item->sizeBytes !== null && $item->sizeBytes > (int) ($spec['max_bytes'] ?? PHP_INT_MAX),
                "basalam.photo.{$index}.too_large",
                trans('omnichannel::omnichannel.validation.file_too_large', [
                    'max' => round(((int) $spec['max_bytes']) / 1048576).' MB',
                ]),
            );
    }

    /**
     * @param  array<string, mixed>  $metadata
     */
    private function failureFrom(ApiResponse $response, array $metadata = []): PublishResult
    {
        return PublishResult::failure(
            ($response->isTransportFailure() || $response->status === 408 || ($response->status !== null && $response->status >= 500)) ? FailureClass::UnknownOutcome : $this->errors->classify($response),
            $this->errors->technicalMessage($response),
            $this->errors->errorCodeString($response),
            [...$metadata, 'response' => $response->toLogArray()],
        );
    }

    private function token(ChannelConnection $connection): ?string
    {
        $token = $connection->credential('access_token');

        return is_string($token) && $token !== '' ? $token : null;
    }

    private function vendorId(ChannelConnection $connection): ?string
    {
        $fromMetadata = data_get($connection->metadata, 'vendor_id');

        if (is_numeric($fromMetadata)) {
            return (string) $fromMetadata;
        }

        return $connection->external_account_id;
    }

    private function productUrl(string $productId): string
    {
        return "https://basalam.com/p/{$productId}";
    }
}
