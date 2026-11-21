<?php

declare(strict_types=1);

namespace Karnoweb\Omnichannel\Contracts;

use Karnoweb\Omnichannel\Enums\PublicationType;
use Karnoweb\Omnichannel\Models\Publication;

interface ContentTransformer
{
    public function supports(PublicationType $type): bool;

    /**
     * @return array<string, mixed>
     */
    public function transform(Publication $publication): array;
}
