<?php

namespace Titan\Container\Exception;

use Exception;
use Psr\Container\NotFoundExceptionInterface;

/**
 * Exception thrown when an entry is not found in the container.
 */
class EntryNotFoundException extends Exception implements NotFoundExceptionInterface
{
    // This is a marker class for when an entry is not found in the container.
}
