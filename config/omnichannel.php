<?php

declare(strict_types=1);

use Karnoweb\Omnichannel\Channels\Bale\BaleDriver;
use Karnoweb\Omnichannel\Channels\Basalam\BasalamDriver;
use Karnoweb\Omnichannel\Channels\Demo\DemoDriver;
use Karnoweb\Omnichannel\Channels\Eitaa\EitaaDriver;
use Karnoweb\Omnichannel\Channels\Gap\GapDriver;
use Karnoweb\Omnichannel\Channels\Instagram\InstagramDriver;
use Karnoweb\Omnichannel\Channels\Rubika\RubikaDriver;
use Karnoweb\Omnichannel\Channels\Telegram\TelegramDriver;
use Karnoweb\Omnichannel\Channels\WhatsApp\WhatsAppDriver;
use Karnoweb\Omnichannel\Models\CatalogSyncState;
use Karnoweb\Omnichannel\Models\CategoryMapping;
use Karnoweb\Omnichannel\Models\Channel;
use Karnoweb\Omnichannel\Models\ChannelConnection;
use Karnoweb\Omnichannel\Models\ExternalCategory;
use Karnoweb\Omnichannel\Models\Publication;
use Karnoweb\Omnichannel\Models\PublicationBatch;
use Karnoweb\Omnichannel\Models\PublicationBatchItem;
use Karnoweb\Omnichannel\Models\PublicationLog;
use Karnoweb\Omnichannel\Models\PublicationMediaAsset;

return [
    'enabled' => env('OMNICHANNEL_ENABLED', true),

    /*
    |--------------------------------------------------------------------------
    | Table prefix & configurable table names
    |--------------------------------------------------------------------------
    */
    'general' => [
        'prefix' => env('OMNICHANNEL_TABLE_PREFIX', 'omc_'),
    ],

    'tables' => [
        'channels' => env('OMNICHANNEL_TABLE_CHANNELS', 'channels'),
        'connections' => env('OMNICHANNEL_TABLE_CONNECTIONS', 'connections'),
        'publications' => env('OMNICHANNEL_TABLE_PUBLICATIONS', 'publications'),
        'publication_logs' => env('OMNICHANNEL_TABLE_PUBLICATION_LOGS', 'publication_logs'),
        'media_assets' => env('OMNICHANNEL_TABLE_MEDIA_ASSETS', 'media_assets'),
        'publication_media' => env('OMNICHANNEL_TABLE_PUBLICATION_MEDIA', 'publication_media'),
        'publication_batches' => env('OMNICHANNEL_TABLE_PUBLICATION_BATCHES', 'publication_batches'),
        'publication_batch_items' => env('OMNICHANNEL_TABLE_PUBLICATION_BATCH_ITEMS', 'publication_batch_items'),
        'external_categories' => env('OMNICHANNEL_TABLE_EXTERNAL_CATEGORIES', 'external_categories'),
        'category_mappings' => env('OMNICHANNEL_TABLE_CATEGORY_MAPPINGS', 'category_mappings'),
        'catalog_sync_states' => env('OMNICHANNEL_TABLE_CATALOG_SYNC_STATES', 'catalog_sync_states'),
    ],

    /*
    |--------------------------------------------------------------------------
    | Host & domain models (soft references only — never hard FK-constrained)
    |--------------------------------------------------------------------------
    */
    'models' => [
        'user' => env('OMNICHANNEL_USER_MODEL', 'App\\Models\\User'),
        'branch' => env('OMNICHANNEL_BRANCH_MODEL', 'App\\Models\\Branch'),
        'channel' => env('OMNICHANNEL_CHANNEL_MODEL', Channel::class),
        'connection' => env('OMNICHANNEL_CONNECTION_MODEL', ChannelConnection::class),
        'publication' => env('OMNICHANNEL_PUBLICATION_MODEL', Publication::class),
        'publication_log' => env('OMNICHANNEL_PUBLICATION_LOG_MODEL', PublicationLog::class),
        'media_asset' => env('OMNICHANNEL_MEDIA_ASSET_MODEL', PublicationMediaAsset::class),
        'publication_batch' => env('OMNICHANNEL_PUBLICATION_BATCH_MODEL', PublicationBatch::class),
        'publication_batch_item' => env('OMNICHANNEL_PUBLICATION_BATCH_ITEM_MODEL', PublicationBatchItem::class),
        'external_category' => env('OMNICHANNEL_EXTERNAL_CATEGORY_MODEL', ExternalCategory::class),
        'category_mapping' => env('OMNICHANNEL_CATEGORY_MAPPING_MODEL', CategoryMapping::class),
        'catalog_sync_state' => env('OMNICHANNEL_CATALOG_SYNC_STATE_MODEL', CatalogSyncState::class),
    ],

    /*
    |--------------------------------------------------------------------------
    | Key type strategy ('int' | 'uuid' | 'ulid')
    |--------------------------------------------------------------------------
    */
    'keys' => [
        'user_key_type' => env('OMNICHANNEL_USER_KEY_TYPE', 'int'),
        'branch_key_type' => env('OMNICHANNEL_BRANCH_KEY_TYPE', 'int'),
    ],

    /*
    |--------------------------------------------------------------------------
    | Branch scoping mode (strict only; legacy inherit_global is deprecated)
    |--------------------------------------------------------------------------
    */
    'branch_mode' => env('OMNICHANNEL_BRANCH_MODE', 'strict'),

    /*
    |--------------------------------------------------------------------------
    | Morph map aliases
    |--------------------------------------------------------------------------
    */
    'morph_map' => [
        'omnichannel_channel' => env('OMNICHANNEL_CHANNEL_MODEL', Channel::class),
        'omnichannel_connection' => env('OMNICHANNEL_CONNECTION_MODEL', ChannelConnection::class),
        'omnichannel_publication' => env('OMNICHANNEL_PUBLICATION_MODEL', Publication::class),
        'omnichannel_publication_log' => env('OMNICHANNEL_PUBLICATION_LOG_MODEL', PublicationLog::class),
    ],

    /*
    |--------------------------------------------------------------------------
    | Driver registry
    |--------------------------------------------------------------------------
    */
    'drivers' => [
        'demo' => DemoDriver::class,
        'instagram' => InstagramDriver::class,
        'basalam' => BasalamDriver::class,
        'telegram' => TelegramDriver::class,
        'bale' => BaleDriver::class,
        'rubika' => RubikaDriver::class,
        'gap' => GapDriver::class,
        'eitaa' => EitaaDriver::class,
        'whatsapp' => WhatsAppDriver::class,
    ],

    /*
    |--------------------------------------------------------------------------
    | Outbound HTTP client policy
    |--------------------------------------------------------------------------
    */
    'http' => [
        'connect_timeout' => (int) env('OMNICHANNEL_HTTP_CONNECT_TIMEOUT', 10),
        'timeout' => (int) env('OMNICHANNEL_HTTP_TIMEOUT', 60),
        'retries' => (int) env('OMNICHANNEL_HTTP_RETRIES', 2),
        'retry_delay_ms' => (int) env('OMNICHANNEL_HTTP_RETRY_DELAY', 500),
    ],

    /*
    |--------------------------------------------------------------------------
    | Per-request API rate limiting (internal quotas — not official platform caps)
    |--------------------------------------------------------------------------
    */
    'api_rate_limit' => [
        'enabled' => (bool) env('OMNICHANNEL_API_RATE_LIMIT_ENABLED', true),
        'cache_store' => env('OMNICHANNEL_API_RATE_LIMIT_STORE'),
        'default_attempts' => (int) env('OMNICHANNEL_API_RATE_LIMIT_ATTEMPTS', 120),
        'default_decay_seconds' => (int) env('OMNICHANNEL_API_RATE_LIMIT_DECAY', 60),
        'warn_on_array_store' => (bool) env('OMNICHANNEL_API_RATE_LIMIT_WARN_ARRAY', true),
    ],

    'whatsapp_catalog' => [
        'auto_reconcile' => (bool) env('OMNICHANNEL_WHATSAPP_AUTO_RECONCILE', false),
        'poll_interval_seconds' => (int) env('OMNICHANNEL_WHATSAPP_POLL_INTERVAL', 120),
        'poll_max_attempts' => (int) env('OMNICHANNEL_WHATSAPP_POLL_MAX_ATTEMPTS', 30),
        'poll_deadline_minutes' => (int) env('OMNICHANNEL_WHATSAPP_POLL_DEADLINE', 120),
    ],

    'media' => [
        'max_bytes' => (int) env('OMNICHANNEL_MEDIA_MAX_BYTES', 52428800),
        'allowed_mime_types' => ['image/*', 'video/*'],
    ],

    'publication_media' => [
        'disk' => env('OMNICHANNEL_PUBLICATION_MEDIA_DISK', 'local'),
        'directory' => env('OMNICHANNEL_PUBLICATION_MEDIA_DIRECTORY', 'omnichannel/media'),
        'temporary_ttl_hours' => (int) env('OMNICHANNEL_PUBLICATION_MEDIA_TEMP_TTL', 24),
        'max_bytes' => (int) env('OMNICHANNEL_PUBLICATION_MEDIA_MAX_BYTES', 52428800),
    ],

    'publication_batch' => [
        'max_targets' => (int) env('OMNICHANNEL_PUBLICATION_BATCH_MAX_TARGETS', 20),
    ],

    'catalog_sync' => [
        'enabled' => (bool) env('OMNICHANNEL_CATALOG_SYNC_ENABLED', false),
        'queue' => env('OMNICHANNEL_CATALOG_SYNC_QUEUE', 'omnichannel-catalog-sync'),
        'debounce_seconds' => (int) env('OMNICHANNEL_CATALOG_SYNC_DEBOUNCE', 30),
        'reconcile_interval_minutes' => (int) env('OMNICHANNEL_CATALOG_SYNC_RECONCILE_INTERVAL', 15),
        'retry_window_minutes' => (int) env('OMNICHANNEL_CATALOG_SYNC_RETRY_WINDOW', 120),
        'batch_limit' => (int) env('OMNICHANNEL_CATALOG_SYNC_BATCH_LIMIT', 100),
    ],

    /*
    |--------------------------------------------------------------------------
    | Queue / retry policy
    |--------------------------------------------------------------------------
    */
    'queue' => [
        'connection' => env('OMNICHANNEL_QUEUE_CONNECTION'),
        'name' => env('OMNICHANNEL_QUEUE', 'publishing'),
        'tries' => (int) env('OMNICHANNEL_JOB_TRIES', 5),
        'backoff' => [30, 120, 300, 900],
        'backoff_jitter_seconds' => (int) env('OMNICHANNEL_JOB_BACKOFF_JITTER', 10),
        'rate_limited_fallback_seconds' => (int) env('OMNICHANNEL_RATE_LIMIT_FALLBACK', 300),
        'timeout' => (int) env('OMNICHANNEL_JOB_TIMEOUT', 300),
        'retry_until_minutes' => (int) env('OMNICHANNEL_JOB_RETRY_UNTIL', 120),
        'scheduled_dispatch_reservation_seconds' => (int) env('OMNICHANNEL_SCHEDULED_RESERVATION_SECONDS', 3600),
        'token_refresh_lock_seconds' => (int) env('OMNICHANNEL_TOKEN_REFRESH_LOCK_SECONDS', 180),
        'token_refresh_lock_wait_seconds' => (int) env('OMNICHANNEL_TOKEN_REFRESH_LOCK_WAIT_SECONDS', 30),
    ],

    /*
    |--------------------------------------------------------------------------
    | Channel configurations & documented limits
    |--------------------------------------------------------------------------
    */
    'channels' => [
        'demo' => [
            'driver' => 'demo',
            'name' => 'Demo',
            'base_url' => env('DEMO_BASE_URL', 'https://demo.local'),
            'transient_failure_rate' => (float) env('DEMO_TRANSIENT_FAILURE_RATE', 0.0),
            'latency_ms' => (int) env('DEMO_LATENCY_MS', 0),
            'rate_limit' => ['attempts' => 60, 'per_seconds' => 60],
        ],

        'instagram' => [
            'driver' => 'instagram',
            'name' => 'Instagram',
            'api_version' => env('INSTAGRAM_API_VERSION', 'v26.0'),
            'graph_url' => env('INSTAGRAM_GRAPH_URL', 'https://graph.instagram.com'),
            'oauth_authorize_url' => env('INSTAGRAM_AUTHORIZE_URL', 'https://www.instagram.com/oauth/authorize'),
            'oauth_token_url' => env('INSTAGRAM_TOKEN_URL', 'https://api.instagram.com/oauth/access_token'),
            'client_id' => env('INSTAGRAM_CLIENT_ID'),
            'client_secret' => env('INSTAGRAM_CLIENT_SECRET'),
            'redirect_uri' => env('INSTAGRAM_REDIRECT_URI'),
            'scopes' => [
                'instagram_business_basic',
                'instagram_business_content_publish',
            ],
            'token_ttl_days' => 60,
            'refresh_before_days' => (int) env('INSTAGRAM_REFRESH_BEFORE_DAYS', 10),
            'container_poll' => [
                'attempts' => (int) env('INSTAGRAM_POLL_ATTEMPTS', 30),
                'interval_seconds' => (int) env('INSTAGRAM_POLL_INTERVAL', 5),
            ],
            'rate_limit' => ['attempts' => 25, 'per_seconds' => 3600],
            'limits' => [
                'caption_max_chars' => 2200,
                'caption_max_hashtags' => 30,
                'caption_max_mentions' => 20,
                'alt_text_max_chars' => 1000,
                'carousel_min_children' => 2,
                'carousel_max_children' => 10,
                'image' => [
                    'mime_types' => ['image/jpeg'],
                    'extensions' => ['jpg', 'jpeg'],
                    'max_bytes' => 8388608,
                    'min_aspect_ratio' => 0.8,
                    'max_aspect_ratio' => 1.91,
                    'min_width' => 320,
                    'max_width' => 1440,
                ],
                'reel' => [
                    'mime_types' => ['video/mp4', 'video/quicktime'],
                    'extensions' => ['mp4', 'mov'],
                    'max_bytes' => 314572800,
                    'min_duration_seconds' => 3,
                    'max_duration_seconds' => 900,
                    'min_aspect_ratio' => 0.01,
                    'max_aspect_ratio' => 10.0,
                    'max_horizontal_pixels' => 1920,
                ],
                'story_image' => [
                    'mime_types' => ['image/jpeg'],
                    'extensions' => ['jpg', 'jpeg'],
                    'max_bytes' => 8388608,
                ],
                'story_video' => [
                    'mime_types' => ['video/mp4', 'video/quicktime'],
                    'extensions' => ['mp4', 'mov'],
                    'max_bytes' => 104857600,
                    'min_duration_seconds' => 3,
                    'max_duration_seconds' => 60,
                    'min_aspect_ratio' => 0.1,
                    'max_aspect_ratio' => 10.0,
                ],
            ],
        ],

        'basalam' => [
            'driver' => 'basalam',
            'name' => 'Basalam',
            'base_url' => env('BASALAM_BASE_URL', 'https://openapi.basalam.com'),
            'auth_url' => env('BASALAM_AUTH_URL', 'https://auth.basalam.com'),
            'client_id' => env('BASALAM_CLIENT_ID'),
            'client_secret' => env('BASALAM_CLIENT_SECRET'),
            'redirect_uri' => env('BASALAM_REDIRECT_URI'),
            'scopes' => [
                'vendor.product.read',
                'vendor.product.write',
                'vendor.profile.read',
            ],
            'rate_limit' => ['attempts' => 60, 'per_seconds' => 60],
            'product_status' => [
                'published' => (int) env('BASALAM_STATUS_PUBLISHED', 2976),
                'draft' => (int) env('BASALAM_STATUS_DRAFT', 3790),
            ],
            'defaults' => [
                'preparation_days' => (int) env('BASALAM_PREPARATION_DAYS', 3),
                'package_weight' => (int) env('BASALAM_PACKAGE_WEIGHT', 500),
                'category_id' => env('BASALAM_DEFAULT_CATEGORY_ID'),
            ],
            'category_map' => [
                'Courses' => env('BASALAM_CATEGORY_COURSES'),
                'Handicrafts' => env('BASALAM_CATEGORY_HANDICRAFTS'),
            ],
            'limits' => [
                'name_max_chars' => 120,
                'brief_max_chars' => 255,
                'description_max_chars' => 5000,
                'max_photos' => 10,
                'photo' => [
                    'mime_types' => ['image/jpeg', 'image/png', 'image/webp'],
                    'extensions' => ['jpg', 'jpeg', 'png', 'webp'],
                    'max_bytes' => 10485760,
                ],
                'video' => [
                    'mime_types' => ['video/mp4'],
                    'extensions' => ['mp4'],
                    'max_bytes' => 104857600,
                ],
            ],
        ],

        'telegram' => [
            'driver' => 'telegram',
            'name' => 'Telegram',
            'base_url' => env('TELEGRAM_BASE_URL', 'https://api.telegram.org'),
            'parse_mode' => env('TELEGRAM_PARSE_MODE', 'HTML'),
            'rate_limit' => ['attempts' => 20, 'per_seconds' => 60],
            'limits' => [
                'text_max_chars' => 4096,
                'caption_max_chars' => 1024,
                'album_min_items' => 2,
                'album_max_items' => 10,
                'image' => [
                    'mime_types' => ['image/jpeg', 'image/png', 'image/webp'],
                    'extensions' => ['jpg', 'jpeg', 'png', 'webp'],
                    'max_bytes_by_url' => 5242880,
                ],
                'video' => [
                    'mime_types' => ['video/mp4'],
                    'extensions' => ['mp4'],
                    'max_bytes_by_url' => 20971520,
                ],
            ],
        ],

        'bale' => [
            'driver' => 'bale',
            'name' => 'Bale',
            'base_url' => env('BALE_BASE_URL', 'https://tapi.bale.ai'),
            'rate_limit' => ['attempts' => 20, 'per_seconds' => 60],
            'limits' => [
                'text_max_chars' => 4096,
                'caption_max_chars' => 4096,
                'album_min_items' => 2,
                'album_max_items' => 10,
                'image' => [
                    'mime_types' => ['image/jpeg', 'image/png'],
                    'extensions' => ['jpg', 'jpeg', 'png'],
                    'max_bytes_by_url' => 5242880,
                    'max_bytes' => 10485760,
                ],
                'video' => [
                    'mime_types' => ['video/mp4'],
                    'extensions' => ['mp4'],
                    'max_bytes' => 52428800,
                ],
            ],
        ],

        'rubika' => [
            'driver' => 'rubika',
            'name' => 'Rubika',
            'base_url' => env('RUBIKA_BASE_URL', 'https://botapi.rubika.ir/v3'),
            'rate_limit' => ['attempts' => 20, 'per_seconds' => 60],
            'limits' => [
                'text_max_chars' => 4096,
                'caption_max_chars' => 4096,
                'image' => [
                    'mime_types' => ['image/jpeg', 'image/png'],
                    'extensions' => ['jpg', 'jpeg', 'png'],
                    'max_bytes' => 52428800,
                ],
                'video' => [
                    'mime_types' => ['video/mp4'],
                    'extensions' => ['mp4'],
                    'max_bytes' => 52428800,
                ],
            ],
        ],

        'gap' => [
            'driver' => 'gap',
            'name' => 'Gap',
            'base_url' => env('GAP_BASE_URL', 'https://api.gap.im'),
            'rate_limit' => ['attempts' => 20, 'per_seconds' => 60],
            'limits' => [
                'text_max_chars' => 4096,
                'caption_max_chars' => 1024,
                'image' => [
                    'mime_types' => ['image/jpeg', 'image/png'],
                    'extensions' => ['jpg', 'jpeg', 'png'],
                    'max_bytes' => 20971520,
                ],
                'video' => [
                    'mime_types' => ['video/mp4'],
                    'extensions' => ['mp4'],
                    'max_bytes' => 52428800,
                ],
            ],
        ],

        'eitaa' => [
            'driver' => 'eitaa',
            'name' => 'Eitaa',
            'base_url' => env('EITAA_BASE_URL', 'https://eitaayar.ir'),
            'gateway_is_third_party' => (bool) env('EITAA_GATEWAY_IS_THIRD_PARTY', true),
            'rate_limit' => ['attempts' => 20, 'per_seconds' => 60],
            'limits' => [
                'text_max_chars' => 4096,
                'caption_max_chars' => 1024,
                'image' => [
                    'mime_types' => ['image/jpeg', 'image/png'],
                    'extensions' => ['jpg', 'jpeg', 'png'],
                    'max_bytes' => 20971520,
                ],
                'video' => [
                    'mime_types' => ['video/mp4'],
                    'extensions' => ['mp4'],
                    'max_bytes' => 52428800,
                ],
            ],
        ],

        'whatsapp' => [
            'driver' => 'whatsapp',
            'name' => 'WhatsApp',
            'graph_url' => env('WHATSAPP_GRAPH_URL', 'https://graph.facebook.com'),
            'api_version' => env('WHATSAPP_API_VERSION', 'v26.0'),
            'client_id' => env('WHATSAPP_CLIENT_ID'),
            'client_secret' => env('WHATSAPP_CLIENT_SECRET'),
            'retailer_id_prefix' => env('WHATSAPP_RETAILER_ID_PREFIX', 'sku'),
            'default_brand' => env('WHATSAPP_DEFAULT_BRAND'),
            'default_currency' => env('WHATSAPP_DEFAULT_CURRENCY', 'IRR'),
            'product_link_base' => env('WHATSAPP_PRODUCT_LINK_BASE'),
            'zero_decimal_currencies' => ['IRR', 'IRT', 'JPY', 'KRW', 'VND', 'CLP', 'ISK'],
            'rate_limit' => ['attempts' => 60, 'per_seconds' => 3600],
            'limits' => [
                'title_max_chars' => 200,
                'description_max_chars' => 9999,
                'max_images' => 21,
                'batch_max_items' => 5000,
                'batch_max_bytes' => 29360128,
            ],
        ],
    ],
];
