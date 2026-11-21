<?php

declare(strict_types=1);

namespace Karnoweb\Omnichannel\Channels\WhatsApp;

use Karnoweb\Omnichannel\Channels\Instagram\InstagramErrorMapper;
use Karnoweb\Omnichannel\Channels\Messaging\MessengerLimits;
use Karnoweb\Omnichannel\Contracts\ChannelDriver;
use Karnoweb\Omnichannel\Contracts\DeactivatableChannelDriver;
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

final class WhatsAppDriver implements ChannelDriver, DeactivatableChannelDriver
{
    public function __construct(
        private readonly WhatsAppCatalogClient $api,
        private readonly WhatsAppTransformer $transformer,
        private readonly InstagramErrorMapper $errors,
    ) {}

    public function key(): string
    {
        return 'whatsapp';
    }

    public function capabilities(): ChannelCapabilities
    {
        return ChannelCapabilities::make(
            supported: [
                Capability::Text,
                Capability::Image,
                Capability::Product,
                Capability::Update,
                Capability::Delete,
                Capability::Deactivate,
                Capability::Scheduling,
                Capability::PriceAndStock,
            ],
            unsupportedReasons: [
                Capability::Message->value => 'The WhatsApp Cloud API sends messages to individual people, not to a channel: outside a 24-hour customer-service window only pre-approved template messages are delivered. This channel publishes to the business catalog instead.',
                Capability::Album->value => 'A catalog item is a product, not a grouped message.',
                Capability::Post->value => 'WhatsApp has no feed post.',
                Capability::Carousel->value => 'A catalog item carries a gallery of up to 21 images rather than a carousel.',
                Capability::Reel->value => 'WhatsApp has no reel format.',
                Capability::Story->value => 'WhatsApp Status is not exposed by the Cloud API or the Catalog API.',
                Capability::Video->value => 'Meta catalog items take images only; there is no video field on a product.',
                Capability::Hashtags->value => 'A catalog item has no hashtags; canonical hashtags are dropped.',
                Capability::AltText->value => 'The catalog image object has no alt-text field.',
                Capability::Thumbnail->value => 'The first catalog image is the thumbnail; there is no separate cover field.',
                Capability::Pin->value => 'A catalog item cannot be pinned.',
                Capability::SilentDelivery->value => 'Catalog changes are not delivered as notifications, so there is nothing to silence.',
                Capability::NativeScheduling->value => 'The Catalog API applies changes immediately. Scheduling is handled by this package.',
            ],
            limits: $this->limits(),
            updateFields: ['title', 'caption', 'media.image', 'extra.price', 'extra.stock', 'extra.currency', 'extra.category'],
            clearUpdateFields: [],
        );
    }

    public function validate(Publication $publication): ValidationResult
    {
        if ($publication->type !== PublicationType::Product) {
            return ValidationResult::error(
                'whatsapp.type.unsupported',
                trans('omnichannel::omnichannel.validation.type_unsupported', ['type' => $publication->type->label()]),
            );
        }

        $content = $publication->content();
        $limits = $this->limits();
        $title = $content->title ?? $publication->product_name ?? '';

        $result = ValidationResult::make()
            ->errorIf(
                trim($title) === '',
                'whatsapp.title.required',
                trans('omnichannel::omnichannel.validation.product_name_required'),
            )
            ->errorIf(
                trim($content->caption) === '',
                'whatsapp.description.required',
                trans('omnichannel::omnichannel.validation.product_description_required'),
            )
            ->errorIf(
                $content->images() === [],
                'whatsapp.image.required',
                trans('omnichannel::omnichannel.validation.product_photo_required'),
            )
            ->errorIf(
                count($content->images()) > (int) ($limits['max_images'] ?? 21),
                'whatsapp.image.too_many',
                trans('omnichannel::omnichannel.validation.too_many_photos', ['max' => $limits['max_images'] ?? 21]),
            )
            ->errorIf(
                ! is_numeric($content->extra('price')),
                'whatsapp.price.required',
                trans('omnichannel::omnichannel.validation.price_required'),
            )
            ->errorIf(
                $this->resolvedBrand($publication) === '',
                'whatsapp.brand.required',
                trans('omnichannel::omnichannel.validation.brand_required'),
            )
            ->errorIf(
                $this->resolvedLink($publication) === '',
                'whatsapp.link.required',
                trans('omnichannel::omnichannel.validation.product_link_required'),
            );

        foreach ($content->images() as $index => $image) {
            $result = $result->errorIf(
                ! MessengerLimits::isPubliclyFetchable($image->url),
                "whatsapp.image.{$index}.url_not_public",
                trans('omnichannel::omnichannel.validation.url_not_public', ['url' => $image->url]),
            );
        }

        $result = $result->warnIf(
            $content->videos() !== [],
            'whatsapp.video.ignored',
            trans('omnichannel::omnichannel.validation.video_ignored_by_catalog'),
        );

        return $result->warnIf(
            $content->hashtags !== [],
            'whatsapp.hashtags.ignored',
            trans('omnichannel::omnichannel.validation.hashtags_ignored'),
        );
    }

    public function testConnection(ChannelConnection $connection): ConnectionTestResult
    {
        $token = $this->token($connection);
        $catalogId = $this->catalogId($connection);

        if ($token === null || $catalogId === null) {
            return ConnectionTestResult::failure(
                FailureClass::Authentication,
                'This WhatsApp connection is missing its access token or catalog id.',
                'whatsapp.auth.incomplete_connection',
            );
        }

        $response = $this->api->getCatalog($catalogId, $token);

        if ($response->failed()) {
            return ConnectionTestResult::failure(
                ($response->isTransportFailure() || $response->status === 408 || ($response->status !== null && $response->status >= 500)) ? FailureClass::UnknownOutcome : $this->errors->classify($response),
                $this->errors->technicalMessage($response),
                $this->errors->errorCodeString($response),
            );
        }

        return ConnectionTestResult::success(
            accountId: $response->string('id') ?? $catalogId,
            accountName: $response->string('name') ?? $connection->name,
            account: $response->data,
        );
    }

    public function publish(Publication $publication): PublishResult
    {
        return $this->apply($publication, 'CREATE');
    }

    public function update(Publication $publication): PublishResult
    {
        return $this->apply($publication, 'UPDATE');
    }

    public function delete(Publication $publication): DeleteResult
    {
        foreach (['create', 'update', 'delete', 'deactivate'] as $operation) {
            if ($pending = $this->pendingBatch($publication, $operation)) {
                return DeleteResult::failure(FailureClass::UnknownOutcome, 'A WhatsApp catalog batch must be reconciled before another mutation.', 'whatsapp.batch.pending', $pending);
            }
        }
        $connection = $publication->connection;
        $token = $this->token($connection);
        $catalogId = $this->catalogId($connection);

        if ($token === null || $catalogId === null) {
            return DeleteResult::failure(
                FailureClass::Authentication,
                'This WhatsApp connection is missing its access token or catalog id.',
                'whatsapp.auth.incomplete_connection',
            );
        }

        $response = $this->api->applyItem(
            $catalogId,
            'DELETE',
            $this->transformer->buildDelete($publication),
            $token,
        );

        if ($response->failed()) {
            return DeleteResult::failure(
                ($response->isTransportFailure() || $response->status === 408 || ($response->status !== null && $response->status >= 500)) ? FailureClass::UnknownOutcome : $this->errors->classify($response),
                $this->errors->technicalMessage($response),
                $this->errors->errorCodeString($response),
            );
        }

        $pending = $this->recordPendingBatch($publication, 'delete', $response);

        return DeleteResult::failure(FailureClass::UnknownOutcome, 'WhatsApp accepted the catalog batch; completion has not been confirmed.', 'whatsapp.batch.pending', $pending);
    }

    public function deactivate(Publication $publication): PublishResult
    {
        foreach (['create', 'update', 'delete', 'deactivate'] as $operation) {
            if ($pending = $this->pendingBatch($publication, $operation)) {
                return PublishResult::failure(FailureClass::UnknownOutcome, 'A WhatsApp catalog batch must be reconciled before another mutation.', 'whatsapp.batch.pending', $pending);
            }
        }
        $connection = $publication->connection;
        $token = $this->token($connection);
        $catalogId = $this->catalogId($connection);

        if ($token === null || $catalogId === null) {
            return PublishResult::failure(
                FailureClass::Authentication,
                'This WhatsApp connection is missing its access token or catalog id.',
                'whatsapp.auth.incomplete_connection',
            );
        }

        $response = $this->api->applyItem($catalogId, 'UPDATE', [
            'id' => $this->transformer->retailerId($publication),
            'availability' => 'out of stock',
        ], $token);

        if ($response->failed()) {
            return $this->failureFrom($response, ['stage' => 'deactivate']);
        }

        $pending = $this->recordPendingBatch($publication, 'deactivate', $response);

        return PublishResult::failure(FailureClass::UnknownOutcome, 'WhatsApp accepted the catalog batch; completion has not been confirmed.', 'whatsapp.batch.pending', $pending);
    }

    /**
     * @param 'CREATE'|'UPDATE' $method
     */
    private function apply(Publication $publication, string $method): PublishResult
    {
        foreach (['create', 'update', 'delete', 'deactivate'] as $operation) {
            if ($this->pendingBatch($publication, $operation)) {
                return PublishResult::failure(FailureClass::UnknownOutcome, 'A WhatsApp catalog batch must be reconciled before another mutation.', 'whatsapp.batch.pending');
            }
        }
        if ($method === 'CREATE') {
            $storedExternalId = $publication->persistentArtifact('publish', 'external_id');
            if (is_string($storedExternalId) && $storedExternalId !== '') {
                return PublishResult::success($storedExternalId, null, ['idempotent_replay' => true]);
            }
        }

        $validation = $this->validate($publication);

        if ($validation->fails()) {
            return PublishResult::failure(
                FailureClass::Validation,
                $validation->summary(),
                $validation->errorCodes()[0] ?? 'whatsapp.validation.failed',
                ['validation' => $validation->toArray()],
            );
        }

        $connection = $publication->connection;
        $token = $this->token($connection);
        $catalogId = $this->catalogId($connection);

        if ($token === null || $catalogId === null) {
            return PublishResult::failure(
                FailureClass::Authentication,
                'This WhatsApp connection is missing its access token or catalog id.',
                'whatsapp.auth.incomplete_connection',
            );
        }

        $retailerId = $this->transformer->retailerId($publication);

        $response = $this->api->applyItem(
            $catalogId,
            $method,
            $this->transformer->transform($publication),
            $token,
        );

        if ($response->failed()) {
            return $this->failureFrom($response, ['stage' => mb_strtolower($method)]);
        }

        $pending = $this->recordPendingBatch($publication, mb_strtolower($method), $response, [
            'catalog_id' => $catalogId,
            'retailer_id' => $retailerId,
            'method' => $method,
        ]);

        return PublishResult::failure(FailureClass::UnknownOutcome, 'WhatsApp accepted the catalog batch; completion has not been confirmed.', 'whatsapp.batch.pending', $pending);
    }

    /**
     * @return array<string, mixed>|null
     */
    private function pendingBatch(Publication $publication, string $operation): ?array
    {
        $pending = $publication->persistentArtifact($operation, 'pending_batch');

        return is_array($pending) ? $pending : null;
    }

    /**
     * @param array<string, mixed> $extra @return array<string, mixed>
     */
    private function recordPendingBatch(Publication $publication, string $operation, ApiResponse $response, array $extra = []): array
    {
        $pending = [...$extra, 'handles' => $this->batchHandles($response), 'accepted_at' => now()->toIso8601String(), 'status' => 'pending', 'response' => $response->toLogArray()];
        $publication->rememberPersistentArtifact($operation, 'pending_batch', $pending);

        return $pending;
    }

    /**
     * @return list<string>
     */
    private function batchHandles(ApiResponse $response): array
    {
        $handles = $response->get('handles', []);
        if (is_string($response->get('handle'))) {
            $handles[] = $response->get('handle');
        }
        if (! is_array($handles)) {
            $handles = [];
        }

        return array_values(array_filter(array_map(static fn ($handle) => is_scalar($handle) ? (string) $handle : null, $handles)));
    }

    private function resolveProductId(string $catalogId, string $retailerId, string $token): ?string
    {
        $lookup = $this->api->findByRetailerId($catalogId, $retailerId, $token);

        if ($lookup->failed()) {
            return null;
        }

        $first = $lookup->get('data.0.id');

        return is_scalar($first) ? (string) $first : null;
    }

    private function resolvedBrand(Publication $publication): string
    {
        $brand = $publication->content()->extra('whatsapp.brand')
            ?? config('omnichannel.channels.whatsapp.default_brand');

        return is_string($brand) ? trim($brand) : '';
    }

    private function resolvedLink(Publication $publication): string
    {
        $explicit = $publication->content()->extra('whatsapp.link');

        if (is_string($explicit) && $explicit !== '') {
            return $explicit;
        }

        $base = config('omnichannel.channels.whatsapp.product_link_base');

        return is_string($base) ? trim($base) : '';
    }

    /**
     * @param array<string, mixed> $metadata
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

    private function catalogId(ChannelConnection $connection): ?string
    {
        $catalogId = $connection->credential('catalog_id')
            ?? data_get($connection->metadata, 'catalog_id')
            ?? $connection->external_account_id;

        return is_scalar($catalogId) && (string) $catalogId !== '' ? (string) $catalogId : null;
    }

    /**
     * @return array<string, mixed>
     */
    private function limits(): array
    {
        /** @var array<string, mixed> $limits */
        $limits = config('omnichannel.channels.whatsapp.limits', []);

        return $limits;
    }
}
