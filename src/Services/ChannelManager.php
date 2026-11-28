<?php

declare(strict_types=1);

namespace Karnoweb\Omnichannel\Services;

use Illuminate\Contracts\Container\Container;
use Karnoweb\Omnichannel\Contracts\ChannelDriver;
use Karnoweb\Omnichannel\Exceptions\DriverNotFoundException;
use Karnoweb\Omnichannel\Models\Channel;
use Karnoweb\Omnichannel\Models\ChannelConnection;
use Karnoweb\Omnichannel\Models\Publication;

final class ChannelManager
{
    /** @var array<string, ChannelDriver> */
    private array $resolved = [];

    /** @var array<string, callable(Container): ChannelDriver> */
    private array $extensions = [];

    public function __construct(private readonly Container $container) {}

    /**
     * @param  callable(Container): ChannelDriver  $factory
     */
    public function extend(string $key, callable $factory): self
    {
        $this->extensions[$key] = $factory;
        unset($this->resolved[$key]);

        return $this;
    }

    /**
     * @throws DriverNotFoundException
     */
    public function driver(string $key): ChannelDriver
    {
        if (isset($this->resolved[$key])) {
            return $this->resolved[$key];
        }

        if (isset($this->extensions[$key])) {
            return $this->resolved[$key] = ($this->extensions[$key])($this->container);
        }

        /** @var class-string<ChannelDriver>|null $class */
        $class = config("omnichannel.drivers.{$key}");

        if ($class === null || ! class_exists($class)) {
            throw new DriverNotFoundException($key);
        }

        /** @var ChannelDriver $driver */
        $driver = $this->container->make($class);

        return $this->resolved[$key] = $driver;
    }

    public function driverFor(Channel $channel): ChannelDriver
    {
        return $this->driver($channel->driver);
    }

    public function driverForConnection(ChannelConnection $connection): ChannelDriver
    {
        return $this->driverFor($connection->channel);
    }

    public function driverForPublication(Publication $publication): ChannelDriver
    {
        return $this->driverFor($publication->channel);
    }

    public function has(string $key): bool
    {
        return isset($this->extensions[$key]) || config("omnichannel.drivers.{$key}") !== null;
    }

    /**
     * @return list<string>
     */
    public function registered(): array
    {
        /** @var array<string, class-string<ChannelDriver>> $configured */
        $configured = config('omnichannel.drivers', []);

        return array_values(array_unique([
            ...array_keys($configured),
            ...array_keys($this->extensions),
        ]));
    }

    /**
     * @return array<string, ChannelDriver>
     */
    public function all(): array
    {
        $drivers = [];

        foreach ($this->registered() as $key) {
            $drivers[$key] = $this->driver($key);
        }

        return $drivers;
    }

    public function flush(): void
    {
        $this->resolved = [];
    }
}
