<?php

declare(strict_types=1);

namespace Karnoweb\Omnichannel;

use Illuminate\Support\Traits\Macroable;
use Karnoweb\Omnichannel\Services\ChannelManager;
use Karnoweb\Omnichannel\Services\PublicationLogger;
use Karnoweb\Omnichannel\Services\PublishingService;
use Karnoweb\Omnichannel\Support\OmnichannelContext;
use Karnoweb\Omnichannel\Support\ResolvesConfiguredModels;

class Omnichannel
{
    use Macroable;
    use ResolvesConfiguredModels;

    public function __construct(
        private readonly ChannelManager $drivers,
        private readonly PublishingService $publishing,
        private readonly PublicationLogger $logger,
        private readonly OmnichannelContext $context,
    ) {}

    public function config(string $key, mixed $default = null): mixed
    {
        return config("omnichannel.{$key}", $default);
    }

    public function drivers(): ChannelManager
    {
        return $this->drivers;
    }

    public function publishing(): PublishingService
    {
        return $this->publishing;
    }

    public function logger(): PublicationLogger
    {
        return $this->logger;
    }

    public function context(): OmnichannelContext
    {
        return $this->context;
    }
}
