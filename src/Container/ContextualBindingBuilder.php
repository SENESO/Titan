<?php

namespace Titan\Container;

use Closure;

/**
 * Class ContextualBindingBuilder
 *
 * Provides a fluent interface for defining contextual bindings.
 */
class ContextualBindingBuilder
{
    /**
     * The container instance.
     *
     * @var Container
     */
    protected Container $container;

    /**
     * The concrete instance.
     *
     * @var string[]
     */
    protected array $concrete;

    /**
     * The abstract target.
     *
     * @var string
     */
    protected string $needs;

    /**
     * Create a new contextual binding builder.
     *
     * @param Container $container
     * @param array $concrete
     */
    public function __construct(Container $container, array $concrete)
    {
        $this->concrete = $concrete;
        $this->container = $container;
    }

    /**
     * Define the abstract target that depends on the context.
     *
     * @param string $abstract
     * @return $this
     */
    public function needs(string $abstract): self
    {
        $this->needs = $abstract;

        return $this;
    }

    /**
     * Define the implementation for the contextual binding.
     *
     * @param Closure|string|array $implementation
     * @return void
     */
    public function give($implementation): void
    {
        foreach ($this->concrete as $concrete) {
            $this->container->addContextualBinding($concrete, $this->needs, $implementation);
        }
    }

    /**
     * Define tagged services to be used as the implementation for the contextual binding.
     *
     * @param string $tag
     * @return void
     */
    public function giveTagged(string $tag): void
    {
        $this->give(function ($container) use ($tag) {
            $taggedServices = $container->tagged($tag);

            return is_array($taggedServices) ? $taggedServices : iterator_to_array($taggedServices);
        });
    }
}
