<?php

declare(strict_types=1);

namespace Karnoweb\Omnichannel\Exceptions;

use InvalidArgumentException;

final class DriverNotFoundException extends InvalidArgumentException
{
    public function __construct(public readonly string $driver)
    {
        parent::__construct(
            "No omnichannel driver registered for [{$driver}]. Add it to config/omnichannel.php under 'drivers'."
        );
    }
}
