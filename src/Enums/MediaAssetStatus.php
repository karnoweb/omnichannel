<?php

declare(strict_types=1);

namespace Karnoweb\Omnichannel\Enums;

enum MediaAssetStatus: string
{
    case Temporary = 'temporary';
    case Processing = 'processing';
    case Ready = 'ready';
    case Failed = 'failed';

    public function isUsable(): bool
    {
        return $this === self::Ready;
    }
}
