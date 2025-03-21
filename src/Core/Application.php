<?php

namespace Titan\Core;

use Psr\Container\ContainerInterface;
use Titan\Container\Container;
use Titan\Config\ConfigRepository;
use Titan\Http\Request;
use Titan\Http\Response;
use Titan\Routing\Router;

/**
 * The Titan Application
 *
 * This is the main application container that bootstraps and runs the framework.
 * It implements PSR-11 Container Interface for dependency injection.
 */
class Application extends Container implements ContainerInterface
{
    /**
     * The Titan framework version.
     *
     * @var string
     */
    const VERSION = '1.0.0';

    /**
     * The base path of the application.
     *
     * @var string
     */
    protected string $basePath;

    /**
     * Indicates if the application has been bootstrapped.
     *
     * @var bool
     */
    protected bool $hasBeenBootstrapped = false;

    /**
     * The registered service providers.
     *
     * @var array
     */
    protected array $serviceProviders = [];

    /**
     * The application namespace.
     *
     * @var string
     */
    protected string $namespace;

    /**
     * Create a new Titan application instance.
     *
     * @param string|null $basePath
     */
    public function __construct(string $basePath = null)
    {
        parent::__construct();

        if ($basePath) {
            $this->setBasePath($basePath);
        }

        $this->registerBaseBindings();
        $this->registerCoreProviders();
    }

    /**
     * Register the basic bindings into the container.
     *
     * @return void
     */
    protected function registerBaseBindings(): void
    {
        // Register the application as a singleton in the container
        static::setInstance($this);

        $this->instance('app', $this);
        $this->instance(Container::class, $this);
        $this->instance(Application::class, $this);
        $this->instance(ContainerInterface::class, $this);
    }

    /**
     * Register core service providers.
     *
     * @return void
     */
    protected function registerCoreProviders(): void
    {
        // Register core service providers here
    }

    /**
     * Set the base path for the application.
     *
     * @param string $basePath
     * @return $this
     */
    public function setBasePath(string $basePath): self
    {
        $this->basePath = rtrim($basePath, '\/');

        $this->bindPathsInContainer();

        return $this;
    }

    /**
     * Bind all of the application paths in the container.
     *
     * @return void
     */
    protected function bindPathsInContainer(): void
    {
        $this->instance('path.base', $this->basePath());
        $this->instance('path.app', $this->appPath());
        $this->instance('path.config', $this->configPath());
        $this->instance('path.public', $this->publicPath());
        $this->instance('path.storage', $this->storagePath());
        $this->instance('path.database', $this->databasePath());
        $this->instance('path.resources', $this->resourcePath());
        $this->instance('path.bootstrap', $this->bootstrapPath());
    }

    /**
     * Get the base path of the Titan installation.
     *
     * @param string $path
     * @return string
     */
    public function basePath(string $path = ''): string
    {
        return $this->basePath.($path ? DIRECTORY_SEPARATOR.$path : '');
    }

    /**
     * Get the path to the application directory.
     *
     * @param string $path
     * @return string
     */
    public function appPath(string $path = ''): string
    {
        return $this->basePath.DIRECTORY_SEPARATOR.'app'.($path ? DIRECTORY_SEPARATOR.$path : '');
    }

    /**
     * Get the path to the configuration files.
     *
     * @param string $path
     * @return string
     */
    public function configPath(string $path = ''): string
    {
        return $this->basePath.DIRECTORY_SEPARATOR.'config'.($path ? DIRECTORY_SEPARATOR.$path : '');
    }

    /**
     * Get the path to the public directory.
     *
     * @param string $path
     * @return string
     */
    public function publicPath(string $path = ''): string
    {
        return $this->basePath.DIRECTORY_SEPARATOR.'public'.($path ? DIRECTORY_SEPARATOR.$path : '');
    }

    /**
     * Get the path to the storage directory.
     *
     * @param string $path
     * @return string
     */
    public function storagePath(string $path = ''): string
    {
        return $this->basePath.DIRECTORY_SEPARATOR.'storage'.($path ? DIRECTORY_SEPARATOR.$path : '');
    }

    /**
     * Get the path to the database directory.
     *
     * @param string $path
     * @return string
     */
    public function databasePath(string $path = ''): string
    {
        return $this->basePath.DIRECTORY_SEPARATOR.'database'.($path ? DIRECTORY_SEPARATOR.$path : '');
    }

    /**
     * Get the path to the resources directory.
     *
     * @param string $path
     * @return string
     */
    public function resourcePath(string $path = ''): string
    {
        return $this->basePath.DIRECTORY_SEPARATOR.'resources'.($path ? DIRECTORY_SEPARATOR.$path : '');
    }

    /**
     * Get the path to the bootstrap directory.
     *
     * @param string $path
     * @return string
     */
    public function bootstrapPath(string $path = ''): string
    {
        return $this->basePath.DIRECTORY_SEPARATOR.'bootstrap'.($path ? DIRECTORY_SEPARATOR.$path : '');
    }

    /**
     * Bootstrap the application.
     *
     * @return void
     */
    public function bootstrap(): void
    {
        if ($this->hasBeenBootstrapped) {
            return;
        }

        // Load environment variables
        $this->loadEnvironmentVariables();

        // Load configuration
        $this->loadConfiguration();

        // Register middleware
        $this->registerMiddleware();

        // Register routes
        $this->registerRoutes();

        $this->hasBeenBootstrapped = true;
    }

    /**
     * Load the environment variables.
     *
     * @return void
     */
    protected function loadEnvironmentVariables(): void
    {
        // Load environment variables using PHPDotEnv
    }

    /**
     * Load the configuration items.
     *
     * @return void
     */
    protected function loadConfiguration(): void
    {
        // Load configuration files
    }

    /**
     * Register the middleware.
     *
     * @return void
     */
    protected function registerMiddleware(): void
    {
        // Register middleware
    }

    /**
     * Register the routes.
     *
     * @return void
     */
    protected function registerRoutes(): void
    {
        // Register routes
    }

    /**
     * Run the application.
     *
     * @return void
     */
    public function run(): Response
    {
        if (!$this->hasBeenBootstrapped) {
            $this->bootstrap();
        }

        // Create a request from the current HTTP request
        $request = Request::capture();

        // Resolve the router
        $router = $this->make(Router::class);

        // Route the request and get a response
        $response = $router->dispatch($request);

        // Send the response
        $response->send();

        return $response;
    }

    /**
     * Get the version number of the application.
     *
     * @return string
     */
    public function version(): string
    {
        return static::VERSION;
    }
}
