<?php

declare(strict_types=1);

namespace Karnoweb\Omnichannel\Services;

use Illuminate\Support\Facades\DB;
use Karnoweb\Omnichannel\Contracts\BranchScopedProductRepository;
use Karnoweb\Omnichannel\Contracts\ProductRepository;
use Karnoweb\Omnichannel\Data\CreatePublicationData;
use Karnoweb\Omnichannel\Enums\Capability;
use Karnoweb\Omnichannel\Enums\PublicationSourceType;
use Karnoweb\Omnichannel\Enums\PublicationStatus;
use Karnoweb\Omnichannel\Enums\PublicationType;
use Karnoweb\Omnichannel\Events\PublicationCreated;
use Karnoweb\Omnichannel\Exceptions\BranchAccessDeniedException;
use Karnoweb\Omnichannel\Exceptions\UnsupportedCapabilityException;
use Karnoweb\Omnichannel\Models\ChannelConnection;
use Karnoweb\Omnichannel\Models\Publication;
use Karnoweb\Omnichannel\Omnichannel;
use Karnoweb\Omnichannel\Support\OmnichannelContext;
use Karnoweb\Omnichannel\Support\OmnichannelEventDispatcher;
use Karnoweb\Omnichannel\ValueObjects\PublicationContent;
use RuntimeException;

final class PublicationCreationService
{
    public function __construct(
        private readonly ChannelManager $channels,
        private readonly ProductRepository $products,
        private readonly IdempotencyKeyGenerator $keys,
        private readonly OmnichannelContext $context,
        private readonly PublicationSourceRegistry $sourceRegistry,
    ) {}

    public function createFromProduct(
        string $productId,
        ChannelConnection $connection,
        PublicationType $type,
        PublicationContent $content,
        ?\DateTimeInterface $scheduledAt = null,
        bool $allowDuplicate = false,
        int|string|null $actorId = null,
        ?string $intentId = null,
    ): Publication {
        return $this->create(new CreatePublicationData(
            sourceType: PublicationSourceType::Product,
            connection: $connection,
            type: $type,
            content: $content,
            productId: $productId,
            scheduledAt: $scheduledAt,
            allowDuplicate: $allowDuplicate,
            actorId: $actorId,
            intentId: $intentId,
        ));
    }

    public function createFromContent(
        ChannelConnection $connection,
        PublicationType $type,
        PublicationContent $content,
        ?string $sourceTitle = null,
        ?\DateTimeInterface $scheduledAt = null,
        bool $allowDuplicate = false,
        int|string|null $actorId = null,
        ?string $intentId = null,
    ): Publication {
        return $this->create(new CreatePublicationData(
            sourceType: PublicationSourceType::FreeForm,
            connection: $connection,
            type: $type,
            content: $content,
            sourceTitle: $sourceTitle,
            scheduledAt: $scheduledAt,
            allowDuplicate: $allowDuplicate,
            actorId: $actorId,
            intentId: $intentId,
        ));
    }

    public function create(CreatePublicationData $data): Publication
    {
        $connection = $data->connection;
        $connection->refresh();
        $branchId = $this->context->branchId();
        $this->context->assertBranchAccess($connection->branch_id);
        if ($branchId === null) {
            throw new BranchAccessDeniedException;
        }
        $connection->loadMissing('channel');

        $driver = $this->channels->driverForConnection($connection);
        $capabilities = $driver->capabilities();

        if (! $capabilities->supportsType($data->type)) {
            throw new UnsupportedCapabilityException(
                $driver->key(),
                $data->type->requiredCapability(),
                $capabilities->reasonFor($data->type->requiredCapability()),
            );
        }

        if ($data->scheduledAt !== null && ! $capabilities->supports(Capability::Scheduling)) {
            throw new UnsupportedCapabilityException(
                $driver->key(),
                Capability::Scheduling,
                $capabilities->reasonFor(Capability::Scheduling),
            );
        }

        $this->assertSourceTypeRules($data);

        $productId = null;
        $productName = null;
        if ($data->sourceType === PublicationSourceType::Product) {
            if (! $this->products instanceof BranchScopedProductRepository) {
                throw new RuntimeException('The configured product repository must enforce branch scope for publication.');
            }
            $product = $this->products->findOrFailForBranch((string) $data->productId, $branchId);
            $productId = $product->id;
            $productName = $product->name;
        }

        $key = $this->resolveIdempotencyKey($data, $connection, $branchId, $productId);
        $legacyKey = $data->allowDuplicate || $data->sourceType !== PublicationSourceType::Product || $productId === null
            ? null
            : $this->keys->forLegacyPublication($connection, $productId, $data->type, $data->content);

        /** @var class-string<Publication> $publicationModel */
        $publicationModel = Omnichannel::model('publication');
        $resolvedActor = $data->actorId ?? $this->context->userId();

        return DB::transaction(static function () use (
            $publicationModel,
            $key,
            $branchId,
            $resolvedActor,
            $productId,
            $productName,
            $connection,
            $data,
            $legacyKey,
        ): Publication {
            if ($legacyKey !== null && $data->scheduledAt === null) {
                /** @var Publication|null $legacyPublication */
                $legacyPublication = $publicationModel::query()
                    ->where('idempotency_key', $legacyKey)
                    ->where('branch_id', $branchId)
                    ->where('channel_connection_id', $connection->id)
                    ->where('channel_id', $connection->channel_id)
                    ->whereNull('scheduled_at')->first();
                if ($legacyPublication !== null) {
                    return $legacyPublication;
                }
            }

            /** @var Publication $publication */
            $publication = $publicationModel::query()->firstOrCreate(
                ['idempotency_key' => $key],
                [
                    'branch_id' => $branchId,
                    'created_by' => $resolvedActor,
                    'source_type' => $data->sourceType,
                    'source_reference_type' => $data->sourceReferenceType,
                    'source_reference_id' => $data->sourceReferenceId !== null ? (string) $data->sourceReferenceId : null,
                    'source_title' => $data->sourceTitle ?? $productName,
                    'product_id' => $productId,
                    'product_name' => $productName,
                    'channel_id' => $connection->channel_id,
                    'channel_connection_id' => $connection->id,
                    'type' => $data->type,
                    'status' => $data->scheduledAt !== null ? PublicationStatus::Scheduled : PublicationStatus::Draft,
                    'content' => $data->content->toArray(),
                    'scheduled_at' => $data->scheduledAt,
                    'metadata' => [],
                ],
            );

            if ($publication->wasRecentlyCreated) {
                OmnichannelEventDispatcher::dispatch(new PublicationCreated($publication));
            }

            return $publication;
        });
    }

    private function assertSourceTypeRules(CreatePublicationData $data): void
    {
        if ($data->sourceType === PublicationSourceType::Product) {
            if ($data->productId === null || $data->productId === '') {
                throw new RuntimeException('Product publications require a product id.');
            }

            return;
        }

        if ($data->sourceType === PublicationSourceType::FreeForm) {
            if ($data->productId !== null) {
                throw new RuntimeException('Free-form publications cannot carry a product id.');
            }
            if ($data->type === PublicationType::Product) {
                throw new UnsupportedCapabilityException(
                    $data->connection->channel?->driver ?? 'unknown',
                    Capability::Product,
                    'Marketplace product listings require a catalog product source.',
                );
            }

            return;
        }

        if ($data->sourceReferenceType === null || $data->sourceReferenceId === null) {
            throw new RuntimeException('Reference publications require source reference metadata.');
        }
        $resolver = $this->sourceRegistry->resolve($data->sourceReferenceType);
        if ($resolver === null) {
            throw new RuntimeException('No resolver registered for reference type ['.$data->sourceReferenceType.'].');
        }
    }

    private function resolveIdempotencyKey(
        CreatePublicationData $data,
        ChannelConnection $connection,
        int|string $branchId,
        ?string $productId,
    ): string {
        if ($data->allowDuplicate && $data->intentId !== null) {
            return $this->keys->forRepublish(
                $connection,
                $productId ?? 'free-form',
                $data->type,
                $data->content,
                $branchId,
                $data->scheduledAt,
                $data->intentId,
            );
        }

        if ($data->sourceType === PublicationSourceType::FreeForm) {
            return $this->keys->forFreeFormPublication(
                $connection,
                $data->type,
                $data->content,
                $branchId,
                $data->scheduledAt,
            );
        }

        return $this->keys->forPublication(
            $connection,
            (string) $productId,
            $data->type,
            $data->content,
            branchId: $branchId,
            scheduledAt: $data->scheduledAt,
        );
    }
}