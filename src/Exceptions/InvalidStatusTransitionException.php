<?php

declare(strict_types=1);

namespace Karnoweb\Omnichannel\Exceptions;

use DomainException;
use Karnoweb\Omnichannel\Enums\PublicationStatus;

final class InvalidStatusTransitionException extends DomainException
{
    public function __construct(
        public readonly PublicationStatus $from,
        public readonly PublicationStatus $to,
        public readonly int|string|null $publicationId = null,
    ) {
        $subject = $publicationId !== null ? "Publication #{$publicationId}" : 'Publication';

        parent::__construct("{$subject} cannot move from [{$from->value}] to [{$to->value}].");
    }
}
