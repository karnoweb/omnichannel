<?php

declare(strict_types=1);

namespace Karnoweb\Omnichannel\Database\Seeders;

use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\Schema;
use Karnoweb\Omnichannel\Models\Channel;
use Karnoweb\Omnichannel\Omnichannel;
use Karnoweb\Omnichannel\Support\OmnichannelTables;

class ChannelSeeder extends Seeder
{
    public function run(): void
    {
        self::syncChannels();
    }

    public static function syncChannels(): void
    {
        if (! Schema::hasTable(OmnichannelTables::name('channels'))) {
            return;
        }

        /** @var class-string<Channel> $channelModel */
        $channelModel = Omnichannel::model('channel');

        $channels = [
            [
                'slug' => 'demo',
                'name' => 'Demo',
                'driver' => 'demo',
                'sort_order' => 0,
                'metadata' => [
                    'description' => 'A fully working channel that needs no credentials.',
                    'colour' => '#64748b',
                ],
            ],
            [
                'slug' => 'instagram',
                'name' => 'Instagram',
                'driver' => 'instagram',
                'sort_order' => 1,
                'metadata' => [
                    'description' => 'Instagram API with Instagram Login.',
                    'docs' => 'https://developers.facebook.com/docs/instagram-platform/content-publishing/',
                    'colour' => '#e1306c',
                ],
            ],
            [
                'slug' => 'basalam',
                'name' => 'Basalam',
                'driver' => 'basalam',
                'sort_order' => 2,
                'metadata' => [
                    'description' => 'Basalam SalamAPI vendor product publishing.',
                    'docs' => 'https://developers.basalam.com/docs/quick-start',
                    'colour' => '#ff5722',
                ],
            ],
            [
                'slug' => 'whatsapp',
                'name' => 'WhatsApp',
                'driver' => 'whatsapp',
                'sort_order' => 3,
                'metadata' => [
                    'description' => 'WhatsApp Business catalog via the Meta Commerce API.',
                    'docs' => 'https://developers.facebook.com/docs/marketing-api/catalog/',
                    'colour' => '#25d366',
                ],
            ],
            [
                'slug' => 'telegram',
                'name' => 'Telegram',
                'driver' => 'telegram',
                'sort_order' => 4,
                'metadata' => [
                    'description' => 'Telegram channel and group messages via the Bot API.',
                    'docs' => 'https://core.telegram.org/bots/api',
                    'colour' => '#229ed9',
                ],
            ],
            [
                'slug' => 'eitaa',
                'name' => 'Eitaa',
                'driver' => 'eitaa',
                'sort_order' => 5,
                'metadata' => [
                    'description' => 'Eitaa channel messages through a relay gateway.',
                    'docs' => 'https://eitaayar.ir',
                    'colour' => '#f9a825',
                    'third_party_gateway' => true,
                ],
            ],
            [
                'slug' => 'bale',
                'name' => 'Bale',
                'driver' => 'bale',
                'sort_order' => 6,
                'metadata' => [
                    'description' => 'Bale channel and group messages via the Bot API.',
                    'docs' => 'https://docs.bale.ai/',
                    'colour' => '#00b2a9',
                ],
            ],
            [
                'slug' => 'rubika',
                'name' => 'Rubika',
                'driver' => 'rubika',
                'sort_order' => 7,
                'metadata' => [
                    'description' => 'Rubika channel and group messages via the Bot API.',
                    'docs' => 'https://rubika.ir/botapi',
                    'colour' => '#8e44ad',
                ],
            ],
            [
                'slug' => 'gap',
                'name' => 'Gap',
                'driver' => 'gap',
                'sort_order' => 8,
                'metadata' => [
                    'description' => 'Gap chat and channel messages via the Bot API.',
                    'docs' => 'https://developer.gap.im',
                    'colour' => '#0088cc',
                ],
            ],
        ];

        foreach ($channels as $channel) {
            $channelModel::query()->updateOrCreate(
                ['slug' => $channel['slug']],
                ['enabled' => true, ...$channel],
            );
        }
    }
}
