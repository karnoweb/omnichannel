<?php

declare(strict_types=1);

namespace Karnoweb\Omnichannel\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Str;
use Karnoweb\Omnichannel\Support\OmnichannelTables;

abstract class BaseModel extends Model
{
    /** @var list<string> */
    protected $guarded = ['id'];

    public function getTable(): string
    {
        $key = $this->table ?? str_replace('\\', '', Str::snake(Str::plural(class_basename($this))));

        return OmnichannelTables::name($key);
    }
}
