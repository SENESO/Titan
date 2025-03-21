<?php

namespace Titan\Routing;

use Closure;
use Exception;
use ReflectionClass;
use ReflectionMethod;
use Titan\Container\Container;
use Titan\Http\Request;
use Titan\Http\Response;
use Titan\Middleware\MiddlewareInterface;
use Titan\Routing\Exception\RouteNotFoundException;

/**
 * Class Router
 *
 * Manages routes, URLs, and how URLs are dispatched to controllers.
 */
class Router
{
    /**
     * The container instance.
     *
     * @var Container
     */
    protected Container $container;

    /**
     * All registered routes.
     *
     * @var array
     */
    protected array $routes = [];

    /**
     * All registered routes by method and URI pattern.
     *
     * @var array
     */
    protected array $routesByMethod = [];

    /**
     * The route group attributes.
     *
     * @var array
     */
    protected array $groupStack = [];

    /**
     * Create a new router instance.
     *
     * @param Container $container
     */
    public function __construct(Container $container)
    {
        $this->container = $container;
    }

    /**
     * Register a new GET route.
     *
     * @param string $uri
     * @param mixed $action
     * @return Route
     */
    public function get(string $uri, $action): Route
    {
        return $this->addRoute(['GET', 'HEAD'], $uri, $action);
    }

    /**
     * Register a new POST route.
     *
     * @param string $uri
     * @param mixed $action
     * @return Route
     */
    public function post(string $uri, $action): Route
    {
        return $this->addRoute(['POST'], $uri, $action);
    }

    /**
     * Register a new PUT route.
     *
     * @param string $uri
     * @param mixed $action
     * @return Route
     */
    public function put(string $uri, $action): Route
    {
        return $this->addRoute(['PUT'], $uri, $action);
    }

    /**
     * Register a new PATCH route.
     *
     * @param string $uri
     * @param mixed $action
     * @return Route
     */
    public function patch(string $uri, $action): Route
    {
        return $this->addRoute(['PATCH'], $uri, $action);
    }

    /**
     * Register a new DELETE route.
     *
     * @param string $uri
     * @param mixed $action
     * @return Route
     */
    public function delete(string $uri, $action): Route
    {
        return $this->addRoute(['DELETE'], $uri, $action);
    }

    /**
     * Register a new OPTIONS route.
     *
     * @param string $uri
     * @param mixed $action
     * @return Route
     */
    public function options(string $uri, $action): Route
    {
        return $this->addRoute(['OPTIONS'], $uri, $action);
    }

    /**
     * Register a new route for any HTTP verb.
     *
     * @param string $uri
     * @param mixed $action
     * @return Route
     */
    public function any(string $uri, $action): Route
    {
        return $this->addRoute(['GET', 'HEAD', 'POST', 'PUT', 'PATCH', 'DELETE', 'OPTIONS'], $uri, $action);
    }

    /**
     * Register a new route for specific HTTP verbs.
     *
     * @param array $methods
     * @param string $uri
     * @param mixed $action
     * @return Route
     */
    public function match(array $methods, string $uri, $action): Route
    {
        return $this->addRoute($methods, $uri, $action);
    }

    /**
     * Create a route group with shared attributes.
     *
     * @param array $attributes
     * @param Closure $callback
     * @return void
     */
    public function group(array $attributes, Closure $callback): void
    {
        $this->updateGroupStack($attributes);

        $callback($this);

        array_pop($this->groupStack);
    }

    /**
     * Update the group stack with the given attributes.
     *
     * @param array $attributes
     * @return void
     */
    protected function updateGroupStack(array $attributes): void
    {
        $attributes = $this->mergeWithLastGroup($attributes);

        $this->groupStack[] = $attributes;
    }

    /**
     * Merge the given attributes with the last group attributes.
     *
     * @param array $attributes
     * @return array
     */
    protected function mergeWithLastGroup(array $attributes): array
    {
        if (empty($this->groupStack)) {
            return $attributes;
        }

        $lastGroup = end($this->groupStack);

        // Merge namespace
        if (isset($lastGroup['namespace']) && isset($attributes['namespace'])) {
            $attributes['namespace'] = $lastGroup['namespace'] . '\\' . $attributes['namespace'];
        } elseif (isset($lastGroup['namespace'])) {
            $attributes['namespace'] = $lastGroup['namespace'];
        }

        // Merge prefix
        if (isset($lastGroup['prefix']) && isset($attributes['prefix'])) {
            $attributes['prefix'] = $lastGroup['prefix'] . '/' . ltrim($attributes['prefix'], '/');
        } elseif (isset($lastGroup['prefix'])) {
            $attributes['prefix'] = $lastGroup['prefix'];
        }

        // Merge middleware
        if (isset($lastGroup['middleware']) && isset($attributes['middleware'])) {
            $attributes['middleware'] = array_merge(
                (array) $lastGroup['middleware'],
                (array) $attributes['middleware']
            );
        } elseif (isset($lastGroup['middleware'])) {
            $attributes['middleware'] = $lastGroup['middleware'];
        }

        return $attributes;
    }

    /**
     * Add a route to the routing table.
     *
     * @param array $methods
     * @param string $uri
     * @param mixed $action
     * @return Route
     */
    protected function addRoute(array $methods, string $uri, $action): Route
    {
        // Get the current group attributes
        $group = empty($this->groupStack) ? [] : end($this->groupStack);

        // Process route prefix
        if (isset($group['prefix'])) {
            $uri = $group['prefix'] . '/' . ltrim($uri, '/');
        }

        // Create the route
        $route = new Route($methods, $uri, $action);

        // Add middleware from group
        if (isset($group['middleware'])) {
            $route->middleware((array) $group['middleware']);
        }

        // Add namespace from group
        if (isset($group['namespace']) && is_string($action)) {
            $route->setActionNamespace($group['namespace']);
        }

        // Add the route to the routing table
        $this->routes[] = $route;

        // Add the route to the lookup table by method
        foreach ($methods as $method) {
            $this->routesByMethod[$method][$uri] = $route;
        }

        return $route;
    }

    /**
     * Dispatch the request to the appropriate route.
     *
     * @param Request $request
     * @return Response
     *
     * @throws RouteNotFoundException
     */
    public function dispatch(Request $request): Response
    {
        $method = $request->method();
        $path = $request->path();

        // First, try to find an exact match
        if (isset($this->routesByMethod[$method][$path])) {
            return $this->runRoute($request, $this->routesByMethod[$method][$path]);
        }

        // If no exact match, check each route for a pattern match
        foreach ($this->routes as $route) {
            if (!in_array($method, $route->getMethods())) {
                continue;
            }

            $pattern = $this->compileRoutePattern($route->getUri());
            if (preg_match($pattern, $path, $matches)) {
                // Extract route parameters
                $params = [];
                $paramNames = [];

                preg_match_all('/{([^}]+)}/', $route->getUri(), $paramNames);

                if (isset($paramNames[1]) && !empty($paramNames[1])) {
                    array_shift($matches); // Remove the full match

                    foreach ($paramNames[1] as $key => $name) {
                        $params[$name] = $matches[$key] ?? null;
                    }
                }

                // Set route parameters to the request
                $request->setRouteParams($params);

                return $this->runRoute($request, $route);
            }
        }

        throw new RouteNotFoundException("No route found for {$method} {$path}");
    }

    /**
     * Compile a route pattern from a URI.
     *
     * @param string $uri
     * @return string
     */
    protected function compileRoutePattern(string $uri): string
    {
        // Replace parameters like {id} with regex capture groups
        $pattern = preg_replace('/\{([^}]+)\}/', '([^/]+)', $uri);

        // Add start and end delimiters
        return '#^' . $pattern . '$#';
    }

    /**
     * Run a given route and return the response.
     *
     * @param Request $request
     * @param Route $route
     * @return Response
     */
    protected function runRoute(Request $request, Route $route): Response
    {
        // Run route middleware
        $response = $this->runRouteMiddleware($request, $route);
        if ($response) {
            return $response;
        }

        // Execute the route action
        return $this->executeRouteAction($request, $route);
    }

    /**
     * Run the given route's middleware stack.
     *
     * @param Request $request
     * @param Route $route
     * @return Response|null
     */
    protected function runRouteMiddleware(Request $request, Route $route): ?Response
    {
        foreach ($route->getMiddleware() as $middleware) {
            if (is_string($middleware)) {
                $middleware = $this->container->make($middleware);
            }

            if ($middleware instanceof MiddlewareInterface) {
                // Middleware can return a response to short-circuit the routing process
                $response = $middleware->handle($request);

                if ($response instanceof Response) {
                    return $response;
                }
            }
        }

        return null;
    }

    /**
     * Execute the route action and return the response.
     *
     * @param Request $request
     * @param Route $route
     * @return Response
     */
    protected function executeRouteAction(Request $request, Route $route): Response
    {
        $action = $route->getAction();

        // Handle Closure actions
        if ($action instanceof Closure) {
            $response = $action($request, ...$request->route());

            if (!$response instanceof Response) {
                $response = new Response((string) $response);
            }

            return $response;
        }

        // Handle class@method actions (Controller actions)
        if (is_string($action)) {
            // If there's no @ symbol, assume the method is 'handle'
            if (strpos($action, '@') === false) {
                $action .= '@handle';
            }

            // Get controller and method
            list($controller, $method) = explode('@', $action, 2);

            // Check if controller has namespace
            if ($route->getActionNamespace() && strpos($controller, '\\') !== 0) {
                $controller = $route->getActionNamespace() . '\\' . $controller;
            }

            // Resolve controller from container
            $controllerInstance = $this->container->make($controller);

            // Get method parameters
            $reflector = new ReflectionMethod($controllerInstance, $method);
            $parameters = $reflector->getParameters();

            // Resolve method dependencies
            $dependencies = [];
            foreach ($parameters as $parameter) {
                // Inject request if it's the first parameter
                if ($parameter->getPosition() === 0 &&
                    $parameter->getType() &&
                    $parameter->getType()->getName() === Request::class) {
                    $dependencies[] = $request;
                } else {
                    // Check if it's a route parameter
                    $paramName = $parameter->getName();
                    if (array_key_exists($paramName, $request->route())) {
                        $dependencies[] = $request->route($paramName);
                    } else {
                        // Try to resolve from container
                        try {
                            if ($parameter->getType() && !$parameter->getType()->isBuiltin()) {
                                $dependencies[] = $this->container->make($parameter->getType()->getName());
                            } elseif ($parameter->isDefaultValueAvailable()) {
                                $dependencies[] = $parameter->getDefaultValue();
                            } else {
                                $dependencies[] = null;
                            }
                        } catch (Exception $e) {
                            if ($parameter->isDefaultValueAvailable()) {
                                $dependencies[] = $parameter->getDefaultValue();
                            } else {
                                throw $e;
                            }
                        }
                    }
                }
            }

            // Call the controller method with dependencies
            $response = $controllerInstance->{$method}(...$dependencies);

            if (!$response instanceof Response) {
                $response = new Response((string) $response);
            }

            return $response;
        }

        // Handle array actions [Controller::class, 'method']
        if (is_array($action) && count($action) === 2) {
            list($controller, $method) = $action;

            // Resolve controller from container if it's a string
            if (is_string($controller)) {
                $controller = $this->container->make($controller);
            }

            // Call the controller method
            $response = $controller->{$method}($request, ...$request->route());

            if (!$response instanceof Response) {
                $response = new Response((string) $response);
            }

            return $response;
        }

        throw new Exception('Invalid route action.');
    }

    /**
     * Get all registered routes.
     *
     * @return array
     */
    public function getRoutes(): array
    {
        return $this->routes;
    }
}
