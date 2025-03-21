<?php

namespace Titan\Routing;

/**
 * Class Route
 *
 * Represents a route in the application.
 */
class Route
{
    /**
     * The HTTP methods the route responds to.
     *
     * @var array
     */
    protected array $methods;

    /**
     * The route URI.
     *
     * @var string
     */
    protected string $uri;

    /**
     * The route action.
     *
     * @var mixed
     */
    protected $action;

    /**
     * The route name.
     *
     * @var string|null
     */
    protected ?string $name = null;

    /**
     * The middleware assigned to the route.
     *
     * @var array
     */
    protected array $middleware = [];

    /**
     * The regular expression requirements.
     *
     * @var array
     */
    protected array $wheres = [];

    /**
     * The namespace of the controller.
     *
     * @var string|null
     */
    protected ?string $actionNamespace = null;

    /**
     * Create a new route instance.
     *
     * @param array $methods
     * @param string $uri
     * @param mixed $action
     */
    public function __construct(array $methods, string $uri, $action)
    {
        $this->methods = $methods;
        $this->uri = $uri;
        $this->action = $action;
    }

    /**
     * Set the name of the route.
     *
     * @param string $name
     * @return $this
     */
    public function name(string $name): self
    {
        $this->name = $name;

        return $this;
    }

    /**
     * Get the name of the route.
     *
     * @return string|null
     */
    public function getName(): ?string
    {
        return $this->name;
    }

    /**
     * Set middleware for the route.
     *
     * @param array|string $middleware
     * @return $this
     */
    public function middleware($middleware): self
    {
        $this->middleware = array_merge(
            $this->middleware,
            is_array($middleware) ? $middleware : [$middleware]
        );

        return $this;
    }

    /**
     * Get the middleware assigned to the route.
     *
     * @return array
     */
    public function getMiddleware(): array
    {
        return $this->middleware;
    }

    /**
     * Set a regular expression requirement on the route.
     *
     * @param string $name
     * @param string $expression
     * @return $this
     */
    public function where(string $name, string $expression): self
    {
        $this->wheres[$name] = $expression;

        return $this;
    }

    /**
     * Set multiple regular expression requirements on the route.
     *
     * @param array $wheres
     * @return $this
     */
    public function whereMultiple(array $wheres): self
    {
        foreach ($wheres as $name => $expression) {
            $this->where($name, $expression);
        }

        return $this;
    }

    /**
     * Get the regular expression requirements.
     *
     * @return array
     */
    public function getWheres(): array
    {
        return $this->wheres;
    }

    /**
     * Get the HTTP methods the route responds to.
     *
     * @return array
     */
    public function getMethods(): array
    {
        return $this->methods;
    }

    /**
     * Get the route URI.
     *
     * @return string
     */
    public function getUri(): string
    {
        return $this->uri;
    }

    /**
     * Get the route action.
     *
     * @return mixed
     */
    public function getAction()
    {
        return $this->action;
    }

    /**
     * Set the namespace for the controller.
     *
     * @param string $namespace
     * @return $this
     */
    public function setActionNamespace(string $namespace): self
    {
        $this->actionNamespace = $namespace;

        return $this;
    }

    /**
     * Get the namespace for the controller.
     *
     * @return string|null
     */
    public function getActionNamespace(): ?string
    {
        return $this->actionNamespace;
    }
}
