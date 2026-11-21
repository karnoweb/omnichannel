<?php

declare(strict_types=1);

namespace Karnoweb\Omnichannel\Channels\Bale;

use Karnoweb\Omnichannel\Channels\Messaging\BotApiClient;
use Karnoweb\Omnichannel\Channels\Messaging\BotApiDriver;
use Karnoweb\Omnichannel\Channels\Messaging\BotApiErrorMapper;
use Karnoweb\Omnichannel\Enums\Capability;
use Karnoweb\Omnichannel\Models\ChannelConnection;
use Karnoweb\Omnichannel\ValueObjects\ChannelCapabilities;

final class BaleDriver extends BotApiDriver
{
    public function __construct()
    {
        parent::__construct(
            new BotApiClient((string) config('omnichannel.channels.bale.base_url')),
            new BotApiErrorMapper('bale'),
        );
    }

    public function key(): string
    {
        return 'bale';
    }

    public function capabilities(): ChannelCapabilities
    {
        return ChannelCapabilities::make(
            supported: [
                Capability::Text,
                Capability::Image,
                Capability::Video,
                Capability::Message,
                Capability::Album,
                Capability::Update,
                Capability::Delete,
                Capability::Scheduling,
                Capability::Hashtags,
                Capability::SilentDelivery,
            ],
            unsupportedReasons: $this->feedCapabilitiesUnsupported('Bale', [
                Capability::Pin->value => 'Pinning is not part of the documented Bale bot API.',
                Capability::Thumbnail->value => 'The Bale sendVideo method has no cover or thumbnail parameter.',
                Capability::NativeScheduling->value => 'The Bale bot API cannot schedule a message. Scheduling is handled by this package.',
            ]),
            limits: [
                ...$this->limits(),
                'delete_window_hours' => 48,
            ],
            updateFields: ['caption', 'hashtags'],
        );
    }

    protected function supportsParseMode(): bool
    {
        return false;
    }

    protected function messageUrl(ChannelConnection $connection, string $messageId): ?string
    {
        $username = $connection->external_account_name
            ?? data_get($connection->metadata, 'username')
            ?? $this->chatId($connection);

        if (! is_string($username) || $username === '' || is_numeric(ltrim($username, '-'))) {
            return null;
        }

        return 'https://ble.ir/'.ltrim($username, '@');
    }
}
