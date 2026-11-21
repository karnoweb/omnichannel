<?php

declare(strict_types=1);

namespace Karnoweb\Omnichannel\Enums;

enum PublicationBatchStatus: string
{
    case Draft = 'draft';
    case Scheduled = 'scheduled';
    case InProgress = 'in_progress';
    case Completed = 'completed';
    case PartiallyCompleted = 'partially_completed';
    case Failed = 'failed';
    case RequiresAttention = 'requires_attention';
    case Cancelled = 'cancelled';
}