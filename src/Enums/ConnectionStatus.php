<?php

declare(strict_types=1);

namespace Karnoweb\Omnichannel\Enums;

enum ConnectionStatus: string
{
    case Pending = 'pending';
    case Active = 'active';
    case Expired = 'expired';
    case Revoked = 'revoked';
    case Disabled = 'disabled';

    public function label(): string
    {
        return trans('omnichannel::omnichannel.connection_status.' . $this->value);
    }

    public function color(): string
    {
        return match ($this) {
            self::Pending => 'badge-warning',
            self::Active => 'badge-success',
            self::Expired => 'badge-error',
            self::Revoked => 'badge-error',
            self::Disabled => 'badge-neutral',
        };
    }

    public function isUsable(): bool
    {
        return $this === self::Active;
    }
}
