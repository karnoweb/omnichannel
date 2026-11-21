<?php

declare(strict_types=1);

namespace Karnoweb\Omnichannel\Enums;

enum LogOperation: string
{
    case Publish = 'publish';
    case Update = 'update';
    case Delete = 'delete';
    case Deactivate = 'deactivate';
    case TestConnection = 'test_connection';
    case Connect = 'connect';
    case RefreshToken = 'refresh_token';
    case Validate = 'validate';
    case ManualRetry = 'manual_retry';
    case Recovery = 'recovery';

    public function label(): string
    {
        return trans('omnichannel::omnichannel.operation.'.$this->value);
    }
}
