<?php

declare(strict_types=1);

namespace Karnoweb\Omnichannel\Database\Factories;

use Illuminate\Database\Eloquent\Factories\Factory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Str;
use Karnoweb\Omnichannel\Enums\PublicationStatus;
use Karnoweb\Omnichannel\Enums\PublicationType;
use Karnoweb\Omnichannel\Models\Channel;
use Karnoweb\Omnichannel\Models\ChannelConnection;
use Karnoweb\Omnichannel\Models\Publication;
use Karnoweb\Omnichannel\Omnichannel;

/**
 * @extends Factory<Publication>
 */
class PublicationFactory extends Factory
{
    public function modelName(): string
    {
        return Omnichannel::model('publication');
    }

    /**
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        $connectionFactory = ChannelConnectionFactory::new();

        return [
            'branch_id' => static function (array $attributes): int|string|null {
                /** @var class-string<ChannelConnection> $connModel */
                $connModel = Omnichannel::model('connection');

                return $connModel::query()->find($attributes['channel_connection_id'])?->branch_id;
            },
            'created_by' => null,
            'product_id' => '1',
            'product_name' => 'Ceramic Mug',
            'channel_connection_id' => $connectionFactory,
            'channel_id' => static function (array $attributes): int {
                /** @var class-string<ChannelConnection> $connModel */
                $connModel = Omnichannel::model('connection');

                return (int) $connModel::query()->findOrFail($attributes['channel_connection_id'])->channel_id;
            },
            'type' => PublicationType::Post,
            'status' => PublicationStatus::Draft,
            'content' => [
                'caption' => 'Handcrafted stoneware mug',
                'media' => [
                    ['type' => 'image', 'url' => 'https://example.test/media/cover.jpg'],
                ],
                'hashtags' => ['Ceramic', 'Handmade'],
                'title' => 'Ceramic Mug',
                'extra' => [],
            ],
            'idempotency_key' => Str::uuid()->toString(),
            'attempts' => 0,
        ];
    }

    public function forBranch(Model|int|string|null $branch): static
    {
        $branchId = $branch instanceof Model ? $branch->getKey() : $branch;

        return $this->state(fn (): array => ['branch_id' => $branchId]);
    }

    public function onChannel(Channel $channel, ChannelConnection $connection): static
    {
        return $this->state(fn (): array => [
            'branch_id' => $connection->branch_id,
            'channel_id' => $channel->id,
            'channel_connection_id' => $connection->id,
        ]);
    }

    public function ofType(PublicationType $type): static
    {
        return $this->state(fn (): array => ['type' => $type]);
    }

    public function scheduled(?string $at = null): static
    {
        return $this->state(fn (): array => [
            'status' => PublicationStatus::Scheduled,
            'scheduled_at' => $at !== null ? now()->parse($at) : now()->addHour(),
        ]);
    }

    public function due(): static
    {
        return $this->state(fn (): array => [
            'status' => PublicationStatus::Scheduled,
            'scheduled_at' => now()->subMinute(),
        ]);
    }

    public function publishing(): static
    {
        return $this->state(fn (): array => ['status' => PublicationStatus::Publishing]);
    }

    public function published(): static
    {
        return $this->state(fn (): array => [
            'status' => PublicationStatus::Published,
            'external_id' => (string) fake()->numberBetween(10_000_000, 99_999_999),
            'external_url' => 'https://example.test/p/' . fake()->lexify('????????'),
            'published_at' => now(),
        ]);
    }

    public function failed(): static
    {
        return $this->state(fn (): array => [
            'status' => PublicationStatus::Failed,
            'failed_at' => now(),
            'failure_class' => 'transient',
            'error_code' => 'http.503',
            'error_message' => 'Service temporarily unavailable.',
            'attempts' => 1,
        ]);
    }

    public function cancelled(): static
    {
        return $this->state(fn (): array => ['status' => PublicationStatus::Cancelled]);
    }
}
