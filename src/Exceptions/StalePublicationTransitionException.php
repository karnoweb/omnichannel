<?php

declare(strict_types=1);

namespace Karnoweb\Omnichannel\Exceptions;

use RuntimeException;

final class StalePublicationTransitionException extends RuntimeException
{
    public function __construct(int|string|null $publicationId)
    {
        $subject = $publicationId !== null ? "Publication #{$publicationId}" : 'Publication';

        parent::__construct("{$subject} state or attempt ownership changed before the transition could be saved.");
    }
}
