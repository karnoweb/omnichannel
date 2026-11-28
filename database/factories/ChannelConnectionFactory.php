<?php

declare(strict_types=1);

namespace Karnoweb\Omnichannel\Database\Factories;

use Illuminate\Database\Eloquent\Factories\Factory;
use Illuminate\Database\Eloquent\Model;
use Karnoweb\Omnichannel\Enums\ConnectionStatus;
use Karnoweb\Omnichannel\Models\Channel;
use Karnoweb\Omnichannel\Models\ChannelConnection;
use Karnoweb\Omnichannel\Omnichannel;

/**
 * @extends Factory<ChannelConnection>
 */
class ChannelConnectionFactory extends Factory
{
    public function modelName(): string
    {
        return Omnichannel::model('connection');
    }

    /**
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        return [
            'branch_id' => null,
            'created_by' => null,
            'channel_id' => ChannelFactory::new(),
            'name' => fake()->company(),
            'external_account_id' => (string) fake()->unique()->numberBetween(10_000, 999_999),
            'external_account_name' => '@'.fake()->unique()->userName(),
            'credentials' => ['access_token' => 'demo-token-'.fake()->uuid()],
            'metadata' => [],
            'status' => ConnectionStatus::Active,
            'connected_at' => now(),
            'expires_at' => null,
        ];
    }

    public function forBranch(Model|int|string|null $branch): static
    {
        $branchId = $branch instanceof Model ? $branch->getKey() : $branch;

        return $this->state(fn (): array => ['branch_id' => $branchId]);
    }

    public function forChannel(Channel $channel): static
    {
        return $this->state(fn (): array => ['channel_id' => $channel->id]);
    }

    public function instagram(): static
    {
        return $this->state(fn (): array => [
            'credentials' => [
                'access_token' => 'IGQV-long-lived-'.fake()->uuid(),
                'token_type' => 'bearer',
            ],
            'expires_at' => now()->addDays(60),
        ]);
    }

    public function basalam(): static
    {
        return $this->state(fn (): array => [
            'credentials' => [
                'access_token' => 'bsl-'.fake()->uuid(),
                'refresh_token' => 'bsl-refresh-'.fake()->uuid(),
            ],
            'metadata' => ['vendor_id' => fake()->numberBetween(1000, 9999)],
        ]);
    }

    public function expired(): static
    {
        return $this->state(fn (): array => [
            'status' => ConnectionStatus::Expired,
            'expires_at' => now()->subDay(),
        ]);
    }

    public function pending(): static
    {
        return $this->state(fn (): array => [
            'status' => ConnectionStatus::Pending,
            'credentials' => null,
            'connected_at' => null,
        ]);
    }
}
