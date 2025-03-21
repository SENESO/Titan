<?php

namespace Titan\Container\Exception;

use Exception;
use Psr\Container\ContainerExceptionInterface;

/**
 * Exception thrown when a binding resolution fails.
 */
class BindingResolutionException extends Exception implements ContainerExceptionInterface
{
    // This is a marker class to indicate binding resolution failures.
}
