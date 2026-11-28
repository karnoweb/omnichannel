<?php

declare(strict_types=1);

namespace Karnoweb\Omnichannel\Tests\Feature;

use Illuminate\Support\Facades\Artisan;
use Karnoweb\Omnichannel\Models\Channel;
use Karnoweb\Omnichannel\Models\ChannelConnection;
use Karnoweb\Omnichannel\Tests\TestCase;

final class OmnichannelDoctorSanitizationTest extends TestCase
{
    protected function defineEnvironment($app): void
    {
        $app['config']->set('app.key', 'base64:'.base64_encode(str_repeat('d', 32)));
    }

    protected function defineDatabaseMigrations(): void
    {
        $this->loadMigrationsFrom(__DIR__.'/../../database/migrations_squashed');
    }

    public function test_doctor_json_output_does_not_echo_connection_secrets(): void
    {
        $channel = Channel::query()->create(['name' => 'Sec', 'slug' => 'sec', 'driver' => 'demo']);
        ChannelConnection::query()->create([
            'branch_id' => 1,
            'channel_id' => $channel->id,
            'name' => 'C',
            'credentials' => ['access_token' => 'super-secret-token-xyz'],
            'status' => 'active',
        ]);

        $exit = Artisan::call('omnichannel:doctor', ['--json' => true]);
        $output = Artisan::output();

        self::assertSame(0, $exit);
        self::assertStringNotContainsString('super-secret-token-xyz', $output);
        self::assertStringNotContainsString('access_token', $output);
    }
}
