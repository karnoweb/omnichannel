<?php

declare(strict_types=1);

namespace Karnoweb\Omnichannel\Support;

use Illuminate\Database\Eloquent\Model;
use InvalidArgumentException;

trait ResolvesConfiguredModels
{
    protected static function configuredModelsRoot(): string
    {
        return 'omnichannel';
    }

    /**
     * @return class-string<Model>
     */
    public static function model(string $key): string
    {
        $class = config(static::configuredModelsRoot().'.models.'.$key);

        if (! is_string($class) || $class === '' || ! class_exists($class)) {
            throw new InvalidArgumentException(
                sprintf('Omnichannel model [%s] is not configured or does not exist.', $key)
            );
        }

        return $class;
    }

    /**
     * @return class-string<Model>|null
     */
    public static function optionalModel(string $key): ?string
    {
        $class = config(static::configuredModelsRoot().'.models.'.$key);

        return is_string($class) && $class !== '' && class_exists($class) ? $class : null;
    }

    public static function newModel(string $key): Model
    {
        $class = static::model($key);

        return new $class;
    }
}
