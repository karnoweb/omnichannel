<?php

declare(strict_types=1);

namespace Karnoweb\Omnichannel\Exceptions;

use RuntimeException;

final class BranchAccessDeniedException extends RuntimeException
{
    public function __construct()
    {
        parent::__construct('The current branch context cannot access this Omnichannel resource.');
    }
}
