<?php

declare(strict_types=1);

namespace Karnoweb\Omnichannel\Enums;

enum LogStatus: string
{
    case Started = 'started';
    case Succeeded = 'succeeded';
    case Failed = 'failed';
    case Skipped = 'skipped';

    public function label(): string
    {
        return trans('omnichannel::omnichannel.log_status.' . $this->value);
    }

    public function color(): string
    {
        return match ($this) {
            self::Started => 'badge-info',
            self::Succeeded => 'badge-success',
            self::Failed => 'badge-error',
            self::Skipped => 'badge-ghost',
        };
    }
}
