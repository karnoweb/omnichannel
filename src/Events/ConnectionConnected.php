<?php

declare(strict_types=1);

namespace Karnoweb\Omnichannel\Events;

use Karnoweb\Omnichannel\Models\ChannelConnection;

final readonly class ConnectionConnected
{
    public function __construct(public ChannelConnection $connection) {}
}
