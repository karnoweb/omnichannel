<?php

declare(strict_types=1);

namespace Karnoweb\Omnichannel\Enums;

enum PublicationBatchDispatchMode: string
{
    case Draft = 'draft';
    case Now = 'now';
    case Scheduled = 'scheduled';
}