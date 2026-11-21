<?php

declare(strict_types=1);

namespace Karnoweb\Omnichannel\Tests\Feature;

use DateTimeImmutable;
use Karnoweb\Omnichannel\Enums\MediaType;
use Karnoweb\Omnichannel\Enums\PublicationType;
use Karnoweb\Omnichannel\Models\ChannelConnection;
use Karnoweb\Omnichannel\Services\IdempotencyKeyGenerator;
use Karnoweb\Omnichannel\Tests\TestCase;
use Karnoweb\Omnichannel\ValueObjects\MediaItem;
use Karnoweb\Omnichannel\ValueObjects\PublicationContent;

final class ReliableIdempotencyTest extends TestCase
{
    public function test_canonical_key_ignores_associative_order_but_preserves_media_order(): void
    {
        $generator = new IdempotencyKeyGenerator;
        $connection = $this->connection();
        $first = new PublicationContent(extra: ['a' => 1, 'b' => ['x' => 2, 'y' => 3]]);
        $reordered = new PublicationContent(extra: ['b' => ['y' => 3, 'x' => 2], 'a' => 1]);

        self::assertSame(
            $generator->forPublication($connection, 'product-1', PublicationType::Post, $first, branchId: 12),
            $generator->forPublication($connection, 'product-1', PublicationType::Post, $reordered, branchId: 12),
        );

        $albumOne = new PublicationContent(media: [
            new MediaItem(MediaType::Image, 'https://cdn.example/a.jpg'),
            new MediaItem(MediaType::Image, 'https://cdn.example/b.jpg'),
        ]);
        $albumTwo = new PublicationContent(media: array_reverse($albumOne->media));
        self::assertNotSame(
            $generator->forPublication($connection, 'product-1', PublicationType::Album, $albumOne, branchId: 12),
            $generator->forPublication($connection, 'product-1', PublicationType::Album, $albumTwo, branchId: 12),
        );
    }

    public function test_key_includes_branch_and_normalized_scheduled_time(): void
    {
        $generator = new IdempotencyKeyGenerator;
        $connection = $this->connection();
        $content = new PublicationContent(caption: 'hello');
        $firstTime = new DateTimeImmutable('2026-12-01T12:00:00+03:30');
        $sameInstant = new DateTimeImmutable('2026-12-01T08:30:00+00:00');

        $base = $generator->forPublication($connection, 'p1', PublicationType::Post, $content, branchId: 1, scheduledAt: $firstTime);
        self::assertSame($base, $generator->forPublication($connection, 'p1', PublicationType::Post, $content, branchId: 1, scheduledAt: $sameInstant));
        self::assertNotSame($base, $generator->forPublication($connection, 'p1', PublicationType::Post, $content, branchId: 2, scheduledAt: $sameInstant));
        self::assertNotSame($base, $generator->forPublication($connection, 'p1', PublicationType::Post, $content, branchId: 1, scheduledAt: null));
    }

    public function test_intent_key_is_stable_for_technical_retries_and_distinct_for_intentional_republishes(): void
    {
        $generator = new IdempotencyKeyGenerator;
        $connection = $this->connection();
        $content = new PublicationContent(caption: 'hello');

        $first = $generator->forRepublish($connection, 'p1', PublicationType::Post, $content, 12, intentId: 'request-123');
        self::assertSame($first, $generator->forRepublish($connection, 'p1', PublicationType::Post, $content, 12, intentId: 'request-123'));
        self::assertNotSame($first, $generator->forRepublish($connection, 'p1', PublicationType::Post, $content, 12, intentId: 'request-456'));
        self::assertNotSame(
            $generator->forRepublish($connection, 'p1', PublicationType::Post, $content, 12),
            $generator->forRepublish($connection, 'p1', PublicationType::Post, $content, 12),
        );
    }

    private function connection(): ChannelConnection
    {
        $connection = new ChannelConnection;
        $connection->setAttribute('id', 9);
        $connection->setAttribute('branch_id', 12);

        return $connection;
    }
}
