<?php

declare(strict_types=1);

namespace Karnoweb\Omnichannel\Tests\Support;

use Karnoweb\Omnichannel\Http\ApiResponse;
use Karnoweb\Omnichannel\Http\BaseApiClient;

class DemoTestApiClient extends BaseApiClient
{
    public function ping(): ApiResponse
    {
        return $this->get('https://demo.local/ping');
    }
}
