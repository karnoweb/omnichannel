<?php

declare(strict_types=1);

namespace Karnoweb\Omnichannel\Enums;

enum CatalogSyncStatus: string
{
    case Idle = 'idle';
    case Pending = 'pending';
    case Dispatching = 'dispatching';
    case Synced = 'synced';
    case Failed = 'failed';
    case Disabled = 'disabled';
}