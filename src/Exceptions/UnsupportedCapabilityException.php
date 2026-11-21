<?php

declare(strict_types=1);

namespace Karnoweb\Omnichannel\Exceptions;

use DomainException;
use Karnoweb\Omnichannel\Enums\Capability;

final class UnsupportedCapabilityException extends DomainException
{
    public function __construct(
        public readonly string $driver,
        public readonly Capability $capability,
        ?string $reason = null,
    ) {
        $message = "Driver [{$driver}] does not support capability [{$capability->value}].";

        parent::__construct($reason !== null ? $message . ' ' . $reason : $message);
    }
}
