<?php

declare(strict_types=1);

namespace Karnoweb\Omnichannel\Facades;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Facades\Facade;
use Karnoweb\Omnichannel\Omnichannel as OmnichannelManager;
use Karnoweb\Omnichannel\Services\ChannelManager;
use Karnoweb\Omnichannel\Services\PublicationLogger;
use Karnoweb\Omnichannel\Services\PublishingService;
use Karnoweb\Omnichannel\Support\OmnichannelContext;

/**
 * @method static mixed                    config(string $key, mixed $default = null)
 * @method static ChannelManager           drivers()
 * @method static PublishingService        publishing()
 * @method static PublicationLogger        logger()
 * @method static OmnichannelContext       context()
 * @method static class-string<Model>      model(string $key)
 * @method static class-string<Model>|null optionalModel(string $key)
 * @method static Model                    newModel(string $key)
 *
 * @see OmnichannelManager
 */
final class Omnichannel extends Facade
{
    protected static function getFacadeAccessor(): string
    {
        return 'omnichannel';
    }
}
