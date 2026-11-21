<?php

declare(strict_types=1);

namespace Karnoweb\Omnichannel\Contracts;

use Karnoweb\Omnichannel\Models\ChannelConnection;
use Karnoweb\Omnichannel\Models\Publication;
use Karnoweb\Omnichannel\Results\ConnectionTestResult;
use Karnoweb\Omnichannel\Results\DeleteResult;
use Karnoweb\Omnichannel\Results\PublishResult;
use Karnoweb\Omnichannel\Results\ValidationResult;
use Karnoweb\Omnichannel\ValueObjects\ChannelCapabilities;

interface ChannelDriver
{
    public function key(): string;

    public function capabilities(): ChannelCapabilities;

    public function validate(Publication $publication): ValidationResult;

    public function testConnection(ChannelConnection $connection): ConnectionTestResult;

    public function publish(Publication $publication): PublishResult;

    public function update(Publication $publication): PublishResult;

    public function delete(Publication $publication): DeleteResult;
}
