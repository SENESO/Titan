<?php

namespace Titan\Container;

use Closure;
use Exception;
use ReflectionClass;
use ReflectionParameter;
use ReflectionFunction;
use ReflectionMethod;
use Psr\Container\ContainerInterface;
use Psr\Container\NotFoundExceptionInterface;
use Titan\Container\Exception\BindingResolutionException;
use Titan\Container\Exception\EntryNotFoundException;

/**
 * The Titan Service Container
 *
 * This is a powerful dependency injection container with auto-wiring capabilities.
 * It implements the PSR-11 container interface.
 */
class Container implements ContainerInterface
{
    /**
     * The current globally available container (if any).
     *
     * @var static
     */
    protected static $instance;

    /**
     * The container's bindings.
     *
     * @var array
     */
    protected array $bindings = [];

    /**
     * The container's shared instances.
     *
     * @var array
     */
    protected array $instances = [];

    /**
     * The registered type aliases.
     *
     * @var array
     */
    protected array $aliases = [];

    /**
     * The registered rebuilders.
     *
     * @var array
     */
    protected array $rebuilders = [];

    /**
     * The extension closures for services.
     *
     * @var array
     */
    protected array $extenders = [];

    /**
     * All of the registered tags.
     *
     * @var array
     */
    protected array $tags = [];

    /**
     * The stack of concretions being built.
     *
     * @var array
     */
    protected array $buildStack = [];

    /**
     * The contextual binding map.
     *
     * @var array
     */
    public array $contextual = [];

    /**
     * All of the registered rebound callbacks.
     *
     * @var array
     */
    protected array $reboundCallbacks = [];

    /**
     * All of the global before resolving callbacks.
     *
     * @var array
     */
    protected array $globalBeforeResolvingCallbacks = [];

    /**
     * All of the global resolving callbacks.
     *
     * @var array
     */
    protected array $globalResolvingCallbacks = [];

    /**
     * All of the global after resolving callbacks.
     *
     * @var array
     */
    protected array $globalAfterResolvingCallbacks = [];

    /**
     * All of the before resolving callbacks by class type.
     *
     * @var array
     */
    protected array $beforeResolvingCallbacks = [];

    /**
     * All of the resolving callbacks by class type.
     *
     * @var array
     */
    protected array $resolvingCallbacks = [];

    /**
     * All of the after resolving callbacks by class type.
     *
     * @var array
     */
    protected array $afterResolvingCallbacks = [];

    /**
     * Define a contextual binding.
     *
     * @param string|array $concrete
     * @return ContextualBindingBuilder
     */
    public function when($concrete): ContextualBindingBuilder
    {
        $aliases = [];

        if (is_string($concrete)) {
            $concrete = [$concrete];
        }

        foreach ($concrete as $c) {
            $aliases[] = $this->getAlias($c);
        }

        return new ContextualBindingBuilder($this, $aliases);
    }

    /**
     * Determine if the given abstract type has been bound.
     *
     * @param string $abstract
     * @return bool
     */
    public function bound(string $abstract): bool
    {
        return isset($this->bindings[$abstract]) ||
               isset($this->instances[$abstract]) ||
               $this->isAlias($abstract);
    }

    /**
     * Determine if the given abstract type has been resolved.
     *
     * @param string $abstract
     * @return bool
     */
    public function resolved(string $abstract): bool
    {
        if ($this->isAlias($abstract)) {
            $abstract = $this->getAlias($abstract);
        }

        return isset($this->instances[$abstract]);
    }

    /**
     * Determine if a given type is shared.
     *
     * @param string $abstract
     * @return bool
     */
    public function isShared(string $abstract): bool
    {
        return isset($this->instances[$abstract]) ||
              (isset($this->bindings[$abstract]['shared']) &&
               $this->bindings[$abstract]['shared'] === true);
    }

    /**
     * Determine if a given string is an alias.
     *
     * @param string $name
     * @return bool
     */
    public function isAlias(string $name): bool
    {
        return isset($this->aliases[$name]);
    }

    /**
     * Register a binding with the container.
     *
     * @param string $abstract
     * @param Closure|string|null $concrete
     * @param bool $shared
     * @return void
     */
    public function bind(string $abstract, $concrete = null, bool $shared = false): void
    {
        // If no concrete type was given, we will simply set the concrete type to the
        // abstract type. This will allow concrete type to be registered as shared
        // without being forced to state their classes in both of the parameters.
        $concrete = $concrete ?? $abstract;

        // If the factory is not a Closure, it means it is just a class name which is
        // bound into this container to the abstract type and we'll just wrap it up
        // inside a Closure to make things more convenient when extending.
        if (!$concrete instanceof Closure) {
            $concrete = $this->getClosure($abstract, $concrete);
        }

        $this->bindings[$abstract] = compact('concrete', 'shared');

        // If the abstract type was already resolved in this container we'll fire the
        // rebound listener so that any objects which have already gotten resolved
        // can have their copy of the object updated via the listener callbacks.
        if ($this->resolved($abstract)) {
            $this->rebound($abstract);
        }
    }

    /**
     * Get the Closure to be used when building a type.
     *
     * @param string $abstract
     * @param string $concrete
     * @return Closure
     */
    protected function getClosure(string $abstract, string $concrete): Closure
    {
        return function ($container, $parameters = []) use ($abstract, $concrete) {
            if ($abstract == $concrete) {
                return $container->build($concrete);
            }

            return $container->resolve(
                $concrete, $parameters, $raiseEvents = false
            );
        };
    }

    /**
     * Register a binding if it hasn't already been registered.
     *
     * @param string $abstract
     * @param Closure|string|null $concrete
     * @param bool $shared
     * @return void
     */
    public function bindIf(string $abstract, $concrete = null, bool $shared = false): void
    {
        if (!$this->bound($abstract)) {
            $this->bind($abstract, $concrete, $shared);
        }
    }

    /**
     * Register a shared binding in the container.
     *
     * @param string $abstract
     * @param Closure|string|null $concrete
     * @return void
     */
    public function singleton(string $abstract, $concrete = null): void
    {
        $this->bind($abstract, $concrete, true);
    }

    /**
     * Register a shared binding if it hasn't already been registered.
     *
     * @param string $abstract
     * @param Closure|string|null $concrete
     * @return void
     */
    public function singletonIf(string $abstract, $concrete = null): void
    {
        if (!$this->bound($abstract)) {
            $this->singleton($abstract, $concrete);
        }
    }

    /**
     * Register an existing instance as shared in the container.
     *
     * @param string $abstract
     * @param mixed $instance
     * @return mixed
     */
    public function instance(string $abstract, $instance)
    {
        // First, we'll extract the alias from the abstract if it exists. If we're
        // registering a concrete instance of something that was bound to an alias,
        // we will register it to the alias rather than the abstract.
        $this->aliases[$abstract] = $abstract;

        // We'll check to determine if this type has been bound before, and if it has
        // we will fire the rebound callbacks registered with the container and it
        // can be updated with consuming classes that have gotten resolved here.
        $isBound = $this->bound($abstract);

        unset($this->aliases[$abstract]);

        // Next, we'll set the instance in our instances array so that we can quickly
        // look it up later and share it if needed. This is the fastest way to get
        // an instance without having to check if it was already shared elsewhere.
        $this->instances[$abstract] = $instance;

        if ($isBound) {
            $this->rebound($abstract);
        }

        return $instance;
    }

    /**
     * Alias a type to a different name.
     *
     * @param string $abstract
     * @param string $alias
     * @return void
     *
     * @throws \Exception
     */
    public function alias(string $abstract, string $alias): void
    {
        if ($alias === $abstract) {
            throw new Exception("[{$abstract}] is aliased to itself.");
        }

        $this->aliases[$alias] = $abstract;
    }

    /**
     * Bind a new callback to an abstract's rebinding event.
     *
     * @param string $abstract
     * @param Closure $callback
     * @return mixed
     */
    public function rebinding(string $abstract, Closure $callback)
    {
        $this->reboundCallbacks[$abstract = $this->getAlias($abstract)][] = $callback;

        if ($this->bound($abstract)) {
            return $this->make($abstract);
        }
    }

    /**
     * Refresh an instance on the given target and method.
     *
     * @param string $abstract
     * @param mixed $target
     * @param string $method
     * @return mixed
     */
    public function refresh(string $abstract, $target, string $method)
    {
        return $this->rebinding($abstract, function ($app, $instance) use ($target, $method) {
            $target->{$method}($instance);
        });
    }

    /**
     * Fire the "rebound" callbacks for the given abstract type.
     *
     * @param string $abstract
     * @return void
     */
    protected function rebound(string $abstract): void
    {
        $instance = $this->make($abstract);

        foreach ($this->getReboundCallbacks($abstract) as $callback) {
            $callback($this, $instance);
        }
    }

    /**
     * Get the rebound callbacks for a given type.
     *
     * @param string $abstract
     * @return array
     */
    protected function getReboundCallbacks(string $abstract): array
    {
        return $this->reboundCallbacks[$abstract] ?? [];
    }

    /**
     * Resolve the given type from the container.
     *
     * @param string $abstract
     * @param array $parameters
     * @return mixed
     *
     * @throws BindingResolutionException
     */
    public function make(string $abstract, array $parameters = [])
    {
        return $this->resolve($abstract, $parameters);
    }

    /**
     * Resolve the given type from the container.
     *
     * @param string $abstract
     * @param array $parameters
     * @param bool $raiseEvents
     * @return mixed
     *
     * @throws BindingResolutionException
     */
    protected function resolve(string $abstract, array $parameters = [], bool $raiseEvents = true)
    {
        $abstract = $this->getAlias($abstract);

        // First we'll fire any event handlers which handle the "before" resolving of
        // specific types. This gives some hooks the chance to add various extends
        // calls to change the resolution of objects that they're interested in.
        if ($raiseEvents) {
            $this->fireBeforeResolvingCallbacks($abstract, $parameters);
        }

        $concrete = $this->getConcrete($abstract);

        // If the type is actually resolvable, we will resolve it and pass back the
        // results. If it's not resolvable we will check if it's a class we can
        // instantiate.
        if ($this->isBuildable($concrete, $abstract)) {
            $object = $this->build($concrete, $parameters);
        } else {
            $object = $this->make($concrete, $parameters);
        }

        // If we defined any before resolving or resolving callbacks for this type,
        // we need to fire them off now. The event system will not be triggered so
        // we have to manually fire these events.
        if ($raiseEvents) {
            $this->fireResolvingCallbacks($abstract, $object);
        }

        // If the requested type is registered as a singleton we'll want to cache off
        // the instances in "memory" so we can return it later without creating an
        // entirely new instance of an object on subsequent calls for this type.
        if ($this->isShared($abstract)) {
            $this->instances[$abstract] = $object;
        }

        if ($raiseEvents) {
            $this->fireAfterResolvingCallbacks($abstract, $object);
        }

        return $object;
    }

    /**
     * Get the concrete type for a given abstract.
     *
     * @param string $abstract
     * @return mixed
     */
    protected function getConcrete(string $abstract)
    {
        // If we don't have a registered resolver or concrete for the type, we'll just
        // assume each type is a concrete name and will attempt to resolve it as is
        // since the container should be able to resolve concretes automatically.
        if (isset($this->bindings[$abstract])) {
            return $this->bindings[$abstract]['concrete'];
        }

        return $abstract;
    }

    /**
     * Determine if the given concrete is buildable.
     *
     * @param mixed $concrete
     * @param string $abstract
     * @return bool
     */
    protected function isBuildable($concrete, string $abstract): bool
    {
        return $concrete === $abstract || $concrete instanceof Closure;
    }

    /**
     * Instantiate a concrete instance of the given type.
     *
     * @param Closure|string $concrete
     * @param array $parameters
     * @return mixed
     *
     * @throws BindingResolutionException
     */
    public function build($concrete, array $parameters = [])
    {
        // If the concrete type is a Closure, we will just execute it and hand back
        // the results of the functions, which allows functions to be used as resolvers
        // for more fine-tuned resolution of these objects out of the container.
        if ($concrete instanceof Closure) {
            return $concrete($this, $parameters);
        }

        try {
            $reflector = new ReflectionClass($concrete);
        } catch (\ReflectionException $e) {
            throw new BindingResolutionException("Target class [$concrete] does not exist.", 0, $e);
        }

        // If the type is not instantiable, the developer is attempting to resolve
        // an abstract type such as an Interface or Abstract Class and there is
        // no binding registered for the abstractions, so we need to bail out.
        if (!$reflector->isInstantiable()) {
            return $this->notInstantiable($concrete);
        }

        $this->buildStack[] = $concrete;
        $constructor = $reflector->getConstructor();

        // If there are no constructors, that means there are no dependencies then
        // we can just resolve the instances without resolving any dependencies.
        // This results in the object being created faster.
        if (is_null($constructor)) {
            array_pop($this->buildStack);
            return new $concrete;
        }

        $dependencies = $constructor->getParameters();

        // If the constructor has no dependencies we can just call it and be done with
        // building this instance. We'll just pass an empty array in which will resolve
        // nothing for this constructor.
        if (empty($dependencies)) {
            array_pop($this->buildStack);
            return new $concrete;
        }

        // Once we have all the constructor's parameters we can create each of the
        // dependency instances and then use the reflection instances to make a
        // new instance of this class, injecting the created dependencies in.
        try {
            $instances = $this->resolveDependencies($dependencies, $parameters);
        } catch (BindingResolutionException $e) {
            array_pop($this->buildStack);
            throw $e;
        }

        array_pop($this->buildStack);

        return $reflector->newInstanceArgs($instances);
    }

    /**
     * Resolve all of the dependencies from the ReflectionParameters.
     *
     * @param array $dependencies
     * @param array $parameters
     * @return array
     *
     * @throws BindingResolutionException
     */
    protected function resolveDependencies(array $dependencies, array $parameters): array
    {
        $results = [];

        foreach ($dependencies as $dependency) {
            // If the dependency has an override for this particular build we'll use
            // that instead as the value. Otherwise, we will continue with this run
            // of resolutions and let reflection attempt to determine the result.
            if (array_key_exists($name = $this->getParameterName($dependency), $parameters)) {
                $results[] = $parameters[$name];
                continue;
            }

            // If the class is null, it means the dependency is a string or some other
            // primitive type which we can not resolve since it is not a class and
            // we'll just bomb out with an error since we have no context here.
            $result = is_null($class = $this->getParameterClassName($dependency))
                        ? $this->resolvePrimitive($dependency)
                        : $this->resolveClass($dependency);

            // If we got a result from resolving the class or primitive and it's not
            // null then we can add it to the results array and keep resolving
            if (!is_null($result)) {
                $results[] = $result;
            }
        }

        return $results;
    }

    /**
     * Resolve a non-class hinted primitive dependency.
     *
     * @param ReflectionParameter $parameter
     * @return mixed
     *
     * @throws BindingResolutionException
     */
    protected function resolvePrimitive(ReflectionParameter $parameter)
    {
        if ($parameter->isDefaultValueAvailable()) {
            return $parameter->getDefaultValue();
        }

        $message = "Unresolvable dependency resolving [$parameter] in class {$parameter->getDeclaringClass()->getName()}";

        throw new BindingResolutionException($message);
    }

    /**
     * Resolve a class based dependency from the container.
     *
     * @param ReflectionParameter $parameter
     * @return mixed
     *
     * @throws BindingResolutionException
     */
    protected function resolveClass(ReflectionParameter $parameter)
    {
        try {
            return $this->make($this->getParameterClassName($parameter));
        } catch (BindingResolutionException $e) {
            // If we can't resolve the class instance, try to resolve it as an optional dependency
            if ($parameter->isDefaultValueAvailable()) {
                return $parameter->getDefaultValue();
            }

            throw $e;
        }
    }

    /**
     * Get the class name of the given parameter's type, if available.
     *
     * @param ReflectionParameter $parameter
     * @return string|null
     */
    protected function getParameterClassName(ReflectionParameter $parameter): ?string
    {
        $type = $parameter->getType();

        if (!$type || $type->isBuiltin()) {
            return null;
        }

        $name = $type->getName();

        if (!is_null($class = $parameter->getDeclaringClass())) {
            if ($name === 'self') {
                return $class->getName();
            }

            if ($name === 'parent' && $parent = $class->getParentClass()) {
                return $parent->getName();
            }
        }

        return $name;
    }

    /**
     * Get the parameter name.
     *
     * @param ReflectionParameter $parameter
     * @return string
     */
    protected function getParameterName(ReflectionParameter $parameter): string
    {
        return $parameter->getName();
    }

    /**
     * Throw an exception for an unresolvable class.
     *
     * @param string $concrete
     * @return void
     *
     * @throws BindingResolutionException
     */
    protected function notInstantiable(string $concrete)
    {
        $message = "Target [$concrete] is not instantiable.";

        throw new BindingResolutionException($message);
    }

    /**
     * Register a new before resolving callback.
     *
     * @param string|Closure $abstract
     * @param Closure|null $callback
     * @return void
     */
    public function beforeResolving($abstract, Closure $callback = null): void
    {
        if (is_string($abstract)) {
            $abstract = $this->getAlias($abstract);
        }

        if ($abstract instanceof Closure && is_null($callback)) {
            $this->globalBeforeResolvingCallbacks[] = $abstract;
        } else {
            $this->beforeResolvingCallbacks[$abstract][] = $callback;
        }
    }

    /**
     * Register a new resolving callback.
     *
     * @param string|Closure $abstract
     * @param Closure|null $callback
     * @return void
     */
    public function resolving($abstract, Closure $callback = null): void
    {
        if (is_string($abstract)) {
            $abstract = $this->getAlias($abstract);
        }

        if ($abstract instanceof Closure && is_null($callback)) {
            $this->globalResolvingCallbacks[] = $abstract;
        } else {
            $this->resolvingCallbacks[$abstract][] = $callback;
        }
    }

    /**
     * Register a new after resolving callback.
     *
     * @param string|Closure $abstract
     * @param Closure|null $callback
     * @return void
     */
    public function afterResolving($abstract, Closure $callback = null): void
    {
        if (is_string($abstract)) {
            $abstract = $this->getAlias($abstract);
        }

        if ($abstract instanceof Closure && is_null($callback)) {
            $this->globalAfterResolvingCallbacks[] = $abstract;
        } else {
            $this->afterResolvingCallbacks[$abstract][] = $callback;
        }
    }

    /**
     * Fire all of the before resolving callbacks.
     *
     * @param string $abstract
     * @param array $parameters
     * @return void
     */
    protected function fireBeforeResolvingCallbacks(string $abstract, array $parameters = []): void
    {
        $this->fireCallbackArray($abstract, $parameters, $this->globalBeforeResolvingCallbacks);

        foreach ($this->beforeResolvingCallbacks as $type => $callbacks) {
            if ($type === $abstract || is_subclass_of($abstract, $type)) {
                $this->fireCallbackArray($abstract, $parameters, $callbacks);
            }
        }
    }

    /**
     * Fire all of the resolving callbacks.
     *
     * @param string $abstract
     * @param mixed $object
     * @return void
     */
    protected function fireResolvingCallbacks(string $abstract, $object): void
    {
        $this->fireCallbackArray($abstract, $object, $this->globalResolvingCallbacks);

        $this->fireCallbackArray($abstract, $object, $this->getCallbacksForType($abstract, $object, $this->resolvingCallbacks));
    }

    /**
     * Fire all of the after resolving callbacks.
     *
     * @param string $abstract
     * @param mixed $object
     * @return void
     */
    protected function fireAfterResolvingCallbacks(string $abstract, $object): void
    {
        $this->fireCallbackArray($abstract, $object, $this->globalAfterResolvingCallbacks);

        $this->fireCallbackArray($abstract, $object, $this->getCallbacksForType($abstract, $object, $this->afterResolvingCallbacks));
    }

    /**
     * Get all callbacks for a given type.
     *
     * @param string $abstract
     * @param mixed $object
     * @param array $callbacksPerType
     * @return array
     */
    protected function getCallbacksForType(string $abstract, $object, array $callbacksPerType): array
    {
        $results = [];

        foreach ($callbacksPerType as $type => $callbacks) {
            if ($type === $abstract || $object instanceof $type) {
                $results = array_merge($results, $callbacks);
            }
        }

        return $results;
    }

    /**
     * Fire an array of callbacks with an object.
     *
     * @param string $abstract
     * @param mixed $object
     * @param array $callbacks
     * @return void
     */
    protected function fireCallbackArray(string $abstract, $object, array $callbacks): void
    {
        foreach ($callbacks as $callback) {
            $callback($object, $this);
        }
    }

    /**
     * Get the container's bindings.
     *
     * @return array
     */
    public function getBindings(): array
    {
        return $this->bindings;
    }

    /**
     * Get the alias for an abstract if available.
     *
     * @param string $abstract
     * @return string
     */
    public function getAlias(string $abstract): string
    {
        return isset($this->aliases[$abstract])
                    ? $this->getAlias($this->aliases[$abstract])
                    : $abstract;
    }

    /**
     * Drop all of the stale instances and aliases.
     *
     * @param string $abstract
     * @return void
     */
    protected function dropStaleInstances(string $abstract): void
    {
        unset($this->instances[$abstract], $this->aliases[$abstract]);
    }

    /**
     * Remove a resolved instance from the instance cache.
     *
     * @param string $abstract
     * @return void
     */
    public function forgetInstance(string $abstract): void
    {
        unset($this->instances[$abstract]);
    }

    /**
     * Clear all of the instances from the container.
     *
     * @return void
     */
    public function forgetInstances(): void
    {
        $this->instances = [];
    }

    /**
     * Flush the container of all bindings and resolved instances.
     *
     * @return void
     */
    public function flush(): void
    {
        $this->aliases = [];
        $this->bindings = [];
        $this->instances = [];
        $this->rebuilders = [];
        $this->reboundCallbacks = [];
        $this->beforeResolvingCallbacks = [];
        $this->resolvingCallbacks = [];
        $this->afterResolvingCallbacks = [];
    }

    /**
     * Get the globally available instance of the container.
     *
     * @return static
     */
    public static function getInstance()
    {
        if (is_null(static::$instance)) {
            static::$instance = new static;
        }

        return static::$instance;
    }

    /**
     * Set the shared instance of the container.
     *
     * @param Container|null $container
     * @return Container|static
     */
    public static function setInstance(Container $container = null)
    {
        return static::$instance = $container;
    }

    /**
     * Determine if a given offset exists.
     *
     * @param string $key
     * @return bool
     */
    public function offsetExists($key): bool
    {
        return $this->bound($key);
    }

    /**
     * Get the value at a given offset.
     *
     * @param string $key
     * @return mixed
     */
    public function offsetGet($key)
    {
        return $this->make($key);
    }

    /**
     * Set the value at a given offset.
     *
     * @param string $key
     * @param mixed $value
     * @return void
     */
    public function offsetSet($key, $value): void
    {
        $this->bind($key, $value instanceof Closure ? $value : function () use ($value) {
            return $value;
        });
    }

    /**
     * Unset the value at a given offset.
     *
     * @param string $key
     * @return void
     */
    public function offsetUnset($key): void
    {
        unset($this->bindings[$key], $this->instances[$key], $this->resolved[$key]);
    }

    /**
     * Finds an entry of the container by its identifier and returns it.
     *
     * @param string $id Identifier of the entry to look for.
     * @return mixed Entry.
     * @throws NotFoundExceptionInterface
     */
    public function get($id)
    {
        try {
            return $this->make($id);
        } catch (Exception $e) {
            if ($this->has($id)) {
                throw $e;
            }

            throw new EntryNotFoundException("No entry was found for '{$id}' identifier");
        }
    }

    /**
     * Returns true if the container can return an entry for the given identifier.
     * Returns false otherwise.
     *
     * @param string $id Identifier of the entry to look for.
     * @return bool
     */
    public function has($id): bool
    {
        return $this->bound($id);
    }

    /**
     * Dynamically access container services.
     *
     * @param string $key
     * @return mixed
     */
    public function __get(string $key)
    {
        return $this[$key];
    }

    /**
     * Dynamically set container services.
     *
     * @param string $key
     * @param mixed $value
     * @return void
     */
    public function __set(string $key, $value): void
    {
        $this[$key] = $value;
    }

    /**
     * Add a contextual binding to the container.
     *
     * @param string $concrete
     * @param string $abstract
     * @param Closure|string|array $implementation
     * @return void
     */
    public function addContextualBinding(string $concrete, string $abstract, $implementation): void
    {
        $this->contextual[$concrete][$abstract] = $implementation;
    }

    /**
     * Get all bindings registered for a tag.
     *
     * @param string $tag
     * @return array
     */
    public function tagged(string $tag): array
    {
        $results = [];

        foreach ($this->tags[$tag] ?? [] as $abstract) {
            $results[] = $this->make($abstract);
        }

        return $results;
    }

    /**
     * Register a binding with a tag.
     *
     * @param array|string $abstracts
     * @param string|array|null $tags
     * @return void
     */
    public function tag($abstracts, $tags): void
    {
        $tags = is_array($tags) ? $tags : [$tags];

        foreach ($tags as $tag) {
            if (!isset($this->tags[$tag])) {
                $this->tags[$tag] = [];
            }

            foreach ((array) $abstracts as $abstract) {
                $this->tags[$tag][] = $abstract;
            }
        }
    }
}
