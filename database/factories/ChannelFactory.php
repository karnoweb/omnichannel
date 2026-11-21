<?php

declare(strict_types=1);

namespace Karnoweb\Omnichannel\Database\Factories;

use Illuminate\Database\Eloquent\Factories\Factory;
use Illuminate\Support\Str;
use Karnoweb\Omnichannel\Models\Channel;
use Karnoweb\Omnichannel\Omnichannel;

/**
 * @extends Factory<Channel>
 */
class ChannelFactory extends Factory
{
    public function modelName(): string
    {
        return Omnichannel::model('channel');
    }

    /**
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        $name = fake()->unique()->word() . '-' . fake()->unique()->numberBetween(100, 99999);

        return [
            'name' => Str::title($name),
            'slug' => Str::slug($name),
            'driver' => 'demo',
            'enabled' => true,
            'sort_order' => 0,
            'metadata' => [],
        ];
    }

    public function demo(): static
    {
        return $this->state(fn (): array => ['name' => 'Demo', 'slug' => 'demo', 'driver' => 'demo', 'sort_order' => 0]);
    }

    public function instagram(): static
    {
        return $this->state(fn (): array => ['name' => 'Instagram', 'slug' => 'instagram', 'driver' => 'instagram', 'sort_order' => 1]);
    }

    public function basalam(): static
    {
        return $this->state(fn (): array => ['name' => 'Basalam', 'slug' => 'basalam', 'driver' => 'basalam', 'sort_order' => 2]);
    }

    public function whatsapp(): static
    {
        return $this->state(fn (): array => ['name' => 'WhatsApp', 'slug' => 'whatsapp', 'driver' => 'whatsapp', 'sort_order' => 3]);
    }

    public function telegram(): static
    {
        return $this->state(fn (): array => ['name' => 'Telegram', 'slug' => 'telegram', 'driver' => 'telegram', 'sort_order' => 4]);
    }

    public function eitaa(): static
    {
        return $this->state(fn (): array => ['name' => 'Eitaa', 'slug' => 'eitaa', 'driver' => 'eitaa', 'sort_order' => 5]);
    }

    public function bale(): static
    {
        return $this->state(fn (): array => ['name' => 'Bale', 'slug' => 'bale', 'driver' => 'bale', 'sort_order' => 6]);
    }

    public function rubika(): static
    {
        return $this->state(fn (): array => ['name' => 'Rubika', 'slug' => 'rubika', 'driver' => 'rubika', 'sort_order' => 7]);
    }

    public function gap(): static
    {
        return $this->state(fn (): array => ['name' => 'Gap', 'slug' => 'gap', 'driver' => 'gap', 'sort_order' => 8]);
    }

    public function disabled(): static
    {
        return $this->state(fn (): array => ['enabled' => false]);
    }
}
