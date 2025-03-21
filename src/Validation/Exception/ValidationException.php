<?php

namespace Titan\Validation\Exception;

use Exception;

/**
 * Exception thrown when validation fails.
 */
class ValidationException extends Exception
{
    /**
     * The validation errors.
     *
     * @var array
     */
    protected array $errors;

    /**
     * Create a new validation exception instance.
     *
     * @param array $errors
     * @param string $message
     * @param int $code
     * @param Exception|null $previous
     */
    public function __construct(array $errors, string $message = 'The given data was invalid.', int $code = 422, Exception $previous = null)
    {
        parent::__construct($message, $code, $previous);

        $this->errors = $errors;
    }

    /**
     * Get the validation errors.
     *
     * @return array
     */
    public function errors(): array
    {
        return $this->errors;
    }
}
