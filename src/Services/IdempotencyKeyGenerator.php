<?php

declare(strict_types=1);

namespace Karnoweb\Omnichannel\Services;

use DateTimeImmutable;
use DateTimeInterface;
use DateTimeZone;
use Karnoweb\Omnichannel\Enums\PublicationType;
use Karnoweb\Omnichannel\Models\ChannelConnection;
use Karnoweb\Omnichannel\ValueObjects\PublicationContent;

final class IdempotencyKeyGenerator
{
    public function forPublication(
        ChannelConnection $connection,
        string $productId,
        PublicationType $type,
        PublicationContent $content,
        ?string $salt = null,
        int|string|null $branchId = null,
        ?DateTimeInterface $scheduledAt = null,
    ): string {
        $material = [
            'version' => 2,
            'connection' => $connection->id,
            'branch' => $branchId ?? $connection->branch_id,
            'product' => $productId,
            'type' => $type->value,
            'content' => $this->canonicalize($content->toArray()),
            'scheduled_at' => $scheduledAt === null
                ? null
                : DateTimeImmutable::createFromInterface($scheduledAt)
                    ->setTimezone(new DateTimeZone('UTC'))
                    ->format('Y-m-d\TH:i:s.u\Z'),
        ];

        if ($salt !== null) {
            $material['salt'] = $salt;
        }

        return hash('sha256', (string) json_encode($material, JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES));
    }

    public function forRepublish(
        ChannelConnection $connection,
        string $productId,
        PublicationType $type,
        PublicationContent $content,
        int|string|null $branchId = null,
        ?DateTimeInterface $scheduledAt = null,
        ?string $intentId = null,
    ): string {
        return $this->forPublication(
            $connection,
            $productId,
            $type,
            $content,
            salt: 'republish:'.($intentId ?? bin2hex(random_bytes(16))),
            branchId: $branchId,
            scheduledAt: $scheduledAt,
        );
    }

    public function forFreeFormPublication(
        ChannelConnection $connection,
        PublicationType $type,
        PublicationContent $content,
        int|string|null $branchId = null,
        ?DateTimeInterface $scheduledAt = null,
    ): string {
        $material = [
            'version' => 2,
            'source' => 'free_form',
            'connection' => $connection->id,
            'branch' => $branchId ?? $connection->branch_id,
            'type' => $type->value,
            'content' => $this->canonicalize($content->toArray()),
            'scheduled_at' => $scheduledAt === null
                ? null
                : DateTimeImmutable::createFromInterface($scheduledAt)
                    ->setTimezone(new DateTimeZone('UTC'))
                    ->format('Y-m-d\TH:i:s.u\Z'),
        ];

        return hash('sha256', (string) json_encode($material, JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES));
    }

    public function forLegacyPublication(
        ChannelConnection $connection,
        string $productId,
        PublicationType $type,
        PublicationContent $content,
    ): string {
        $material = [
            'connection' => $connection->id,
            'product' => $productId,
            'type' => $type->value,
            'content' => $this->canonicalize($content->toArray()),
        ];

        return hash('sha256', (string) json_encode($material, JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES));
    }

    /**
     * @param  array<array-key, mixed>  $data
     * @return array<array-key, mixed>
     */
    private function canonicalize(array $data): array
    {
        if (! array_is_list($data)) {
            ksort($data);
        }

        foreach ($data as $key => $value) {
            if (is_array($value)) {
                $data[$key] = $this->canonicalize($value);
            }
        }

        return $data;
    }
}
