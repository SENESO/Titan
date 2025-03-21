<?php

namespace Titan\Core;

use Closure;
use Titan\Container\Container;

/**
 * Abstract ServiceProvider
 *
 * All service providers extend this class. Service providers are responsible
 * for bootstrapping services and registering bindings in the container.
 */
abstract class ServiceProvider
{
    /**
     * The application instance.
     *
     * @var Application
     */
    protected Application $app;

    /**
     * Indicates if loading of the provider is deferred.
     *
     * @var bool
     */
    protected bool $defer = false;

    /**
     * The paths that should be published.
     *
     * @var array
     */
    protected static array $publishes = [];

    /**
     * The paths that should be published by group.
     *
     * @var array
     */
    protected static array $publishGroups = [];

    /**
     * Create a new service provider instance.
     *
     * @param Application $app
     */
    public function __construct(Application $app)
    {
        $this->app = $app;
    }

    /**
     * Register any application services.
     *
     * @return void
     */
    abstract public function register(): void;

    /**
     * Bootstrap any application services.
     *
     * @return void
     */
    public function boot(): void
    {
        // Default implementation does nothing
    }

    /**
     * Register a binding with the container.
     *
     * @param string $abstract
     * @param Closure|string|null $concrete
     * @param bool $shared
     * @return void
     */
    protected function bind(string $abstract, $concrete = null, bool $shared = false): void
    {
        $this->app->bind($abstract, $concrete, $shared);
    }

    /**
     * Register a shared binding in the container.
     *
     * @param string $abstract
     * @param Closure|string|null $concrete
     * @return void
     */
    protected function singleton(string $abstract, $concrete = null): void
    {
        $this->app->singleton($abstract, $concrete);
    }

    /**
     * Register an existing instance as shared in the container.
     *
     * @param string $abstract
     * @param mixed $instance
     * @return mixed
     */
    protected function instance(string $abstract, $instance)
    {
        return $this->app->instance($abstract, $instance);
    }

    /**
     * Get the services provided by the provider.
     *
     * @return array
     */
    public function provides(): array
    {
        return [];
    }

    /**
     * Get the events that trigger this service provider to register.
     *
     * @return array
     */
    public function when(): array
    {
        return [];
    }

    /**
     * Determine if the provider is deferred.
     *
     * @return bool
     */
    public function isDeferred(): bool
    {
        return $this->defer;
    }

    /**
     * Get a list of files that should be compiled for this service provider.
     *
     * @return array
     */
    public function compiles(): array
    {
        return [];
    }

    /**
     * Register paths to be published by the publish command.
     *
     * @param array $paths
     * @param string|null $group
     * @return void
     */
    protected static function publishes(array $paths, ?string $group = null): void
    {
        static::$publishes[static::class] = static::$publishes[static::class] ?? [];
        static::$publishes[static::class] = array_merge(static::$publishes[static::class], $paths);

        if ($group) {
            static::$publishGroups[$group] = static::$publishGroups[$group] ?? [];
            static::$publishGroups[$group] = array_merge(static::$publishGroups[$group], $paths);
        }
    }

    /**
     * Get the paths to publish.
     *
     * @param string|null $provider
     * @param string|null $group
     * @return array
     */
    public static function pathsToPublish(?string $provider = null, ?string $group = null): array
    {
        if ($provider && $group) {
            if (empty(static::$publishes[$provider]) || empty(static::$publishGroups[$group])) {
                return [];
            }

            return array_intersect_key(static::$publishes[$provider], static::$publishGroups[$group]);
        }

        if ($provider) {
            return static::$publishes[$provider] ?? [];
        }

        if ($group) {
            return static::$publishGroups[$group] ?? [];
        }

        $paths = [];

        foreach (static::$publishes as $class => $publish) {
            $paths = array_merge($paths, $publish);
        }

        return $paths;
    }

    /**
     * Get the service provider class name without the namespace prefix.
     *
     * @return string
     */
    public function getName(): string
    {
        $className = get_class($this);

        return substr($className, strrpos($className, '\\') + 1);
    }
}
