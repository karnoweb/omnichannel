<?php

declare(strict_types=1);

namespace Karnoweb\Omnichannel\Channels\Telegram;

use Karnoweb\Omnichannel\Channels\Messaging\BotApiClient;
use Karnoweb\Omnichannel\Channels\Messaging\BotApiDriver;
use Karnoweb\Omnichannel\Channels\Messaging\BotApiErrorMapper;
use Karnoweb\Omnichannel\Enums\Capability;
use Karnoweb\Omnichannel\Models\ChannelConnection;
use Karnoweb\Omnichannel\ValueObjects\ChannelCapabilities;

final class TelegramDriver extends BotApiDriver
{
    public function __construct()
    {
        parent::__construct(
            new BotApiClient((string) config('omnichannel.channels.telegram.base_url')),
            new BotApiErrorMapper('telegram'),
        );
    }

    public function key(): string
    {
        return 'telegram';
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
                Capability::Pin,
                Capability::SilentDelivery,
                Capability::Thumbnail,
            ],
            unsupportedReasons: $this->feedCapabilitiesUnsupported('Telegram', [
                Capability::NativeScheduling->value => 'The Bot API cannot schedule a message. Scheduling is handled by this package.',
            ]),
            limits: $this->limits(),
            updateFields: ['caption', 'hashtags'],
        );
    }

    protected function messageUrl(ChannelConnection $connection, string $messageId): ?string
    {
        $username = $connection->external_account_name
            ?? data_get($connection->metadata, 'username')
            ?? $this->chatId($connection);

        if (! is_string($username) || $username === '' || is_numeric(ltrim($username, '-'))) {
            return null;
        }

        return 'https://t.me/'.ltrim($username, '@').'/'.$messageId;
    }
}
