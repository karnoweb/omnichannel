<?php

declare(strict_types=1);

namespace Karnoweb\Omnichannel\Enums;

enum PublicationSourceType: string
{
    case Product = 'product';
    case FreeForm = 'free_form';
    case Reference = 'reference';
}