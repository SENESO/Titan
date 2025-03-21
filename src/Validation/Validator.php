<?php

namespace Titan\Validation;

use Titan\Validation\Exception\ValidationException;

/**
 * Validator class for performing data validation
 */
class Validator
{
    /**
     * The data being validated.
     *
     * @var array
     */
    protected array $data;

    /**
     * The validation rules.
     *
     * @var array
     */
    protected array $rules;

    /**
     * The error messages.
     *
     * @var array
     */
    protected array $messages = [];

    /**
     * The custom error message attributes.
     *
     * @var array
     */
    protected array $customAttributes = [];

    /**
     * The custom rule message replacers.
     *
     * @var array
     */
    protected array $customReplacers = [];

    /**
     * The validation error messages.
     *
     * @var array
     */
    protected array $errors = [];

    /**
     * Default error messages for validation rules.
     *
     * @var array
     */
    protected array $defaultMessages = [
        'required' => 'The :attribute field is required.',
        'email' => 'The :attribute must be a valid email address.',
        'min' => 'The :attribute must be at least :min characters.',
        'max' => 'The :attribute may not be greater than :max characters.',
        'numeric' => 'The :attribute must be a number.',
        'integer' => 'The :attribute must be an integer.',
        'string' => 'The :attribute must be a string.',
        'array' => 'The :attribute must be an array.',
        'in' => 'The selected :attribute is invalid.',
        'unique' => 'The :attribute has already been taken.',
        'confirmed' => 'The :attribute confirmation does not match.',
        'alpha' => 'The :attribute may only contain letters.',
        'alpha_dash' => 'The :attribute may only contain letters, numbers, dashes and underscores.',
        'alpha_num' => 'The :attribute may only contain letters and numbers.',
        'date' => 'The :attribute is not a valid date.',
        'url' => 'The :attribute format is invalid.',
    ];

    /**
     * Available validation rules.
     *
     * @var array
     */
    protected array $availableRules = [
        'required', 'email', 'min', 'max', 'numeric', 'integer', 'string',
        'array', 'in', 'unique', 'confirmed', 'alpha', 'alpha_dash',
        'alpha_num', 'date', 'url', 'boolean', 'same', 'different',
        'regex'
    ];

    /**
     * Create a new validator instance.
     *
     * @param array $data
     * @param array $rules
     * @param array $messages
     * @param array $customAttributes
     */
    public function __construct(array $data, array $rules, array $messages = [], array $customAttributes = [])
    {
        $this->data = $data;
        $this->rules = $this->parseRules($rules);
        $this->messages = array_merge($this->defaultMessages, $messages);
        $this->customAttributes = $customAttributes;
    }

    /**
     * Parse rules into a standardized format.
     *
     * @param array $rules
     * @return array
     */
    protected function parseRules(array $rules): array
    {
        $parsedRules = [];

        foreach ($rules as $attribute => $rule) {
            $parsedRules[$attribute] = is_string($rule) ? explode('|', $rule) : $rule;
        }

        return $parsedRules;
    }

    /**
     * Validate the data.
     *
     * @return bool
     */
    public function validate(): bool
    {
        $this->errors = [];

        foreach ($this->rules as $attribute => $rules) {
            foreach ($rules as $rule) {
                $this->validateAttribute($attribute, $rule);
            }
        }

        return empty($this->errors);
    }

    /**
     * Validate a specific attribute against a rule.
     *
     * @param string $attribute
     * @param string $rule
     * @return void
     */
    protected function validateAttribute(string $attribute, string $rule): void
    {
        // Parse rule and parameters
        $parameters = [];
        if (str_contains($rule, ':')) {
            list($rule, $paramStr) = explode(':', $rule, 2);
            $parameters = explode(',', $paramStr);
        }

        // Check if rule is valid
        if (!in_array($rule, $this->availableRules)) {
            throw new \InvalidArgumentException("Unknown validation rule: {$rule}");
        }

        // Get value from data
        $value = $this->getValue($attribute);

        // Call the appropriate validation method
        $method = 'validate' . ucfirst($rule);
        if (method_exists($this, $method)) {
            $valid = $this->{$method}($attribute, $value, $parameters);
            if (!$valid) {
                $this->addError($attribute, $rule, $parameters);
            }
        }
    }

    /**
     * Get a value from the data.
     *
     * @param string $attribute
     * @return mixed
     */
    protected function getValue(string $attribute)
    {
        return $this->data[$attribute] ?? null;
    }

    /**
     * Add an error message.
     *
     * @param string $attribute
     * @param string $rule
     * @param array $parameters
     * @return void
     */
    protected function addError(string $attribute, string $rule, array $parameters = []): void
    {
        $message = $this->getMessage($attribute, $rule);
        $message = $this->replaceAttributes($message, $attribute);
        $message = $this->replaceParameters($message, $parameters, $rule);

        $this->errors[$attribute][] = $message;
    }

    /**
     * Get the error message for an attribute and rule.
     *
     * @param string $attribute
     * @param string $rule
     * @return string
     */
    protected function getMessage(string $attribute, string $rule): string
    {
        $attributeMessage = $attribute . '.' . $rule;

        if (isset($this->messages[$attributeMessage])) {
            return $this->messages[$attributeMessage];
        }

        return $this->messages[$rule] ?? $this->defaultMessages[$rule] ?? 'The :attribute is invalid.';
    }

    /**
     * Replace attribute placeholders in the message.
     *
     * @param string $message
     * @param string $attribute
     * @return string
     */
    protected function replaceAttributes(string $message, string $attribute): string
    {
        $attributeName = $this->customAttributes[$attribute] ?? str_replace('_', ' ', $attribute);

        return str_replace(':attribute', $attributeName, $message);
    }

    /**
     * Replace parameter placeholders in the message.
     *
     * @param string $message
     * @param array $parameters
     * @param string $rule
     * @return string
     */
    protected function replaceParameters(string $message, array $parameters, string $rule): string
    {
        if ($rule === 'min') {
            $message = str_replace(':min', $parameters[0], $message);
        }

        if ($rule === 'max') {
            $message = str_replace(':max', $parameters[0], $message);
        }

        if ($rule === 'in') {
            $message = str_replace(':values', implode(', ', $parameters), $message);
        }

        if (isset($this->customReplacers[$rule])) {
            $message = $this->customReplacers[$rule]($message, $parameters);
        }

        return $message;
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

    /**
     * Get the first error message for each attribute.
     *
     * @return array
     */
    public function firstErrors(): array
    {
        $errors = [];

        foreach ($this->errors as $attribute => $messages) {
            $errors[$attribute] = reset($messages);
        }

        return $errors;
    }

    /**
     * Add a custom replacer.
     *
     * @param string $rule
     * @param callable $replacer
     * @return $this
     */
    public function addReplacer(string $rule, callable $replacer): self
    {
        $this->customReplacers[$rule] = $replacer;

        return $this;
    }

    /**
     * Validate that an attribute is required.
     *
     * @param string $attribute
     * @param mixed $value
     * @param array $parameters
     * @return bool
     */
    protected function validateRequired(string $attribute, $value, array $parameters): bool
    {
        if (is_null($value)) {
            return false;
        } elseif (is_string($value) && trim($value) === '') {
            return false;
        } elseif (is_array($value) && count($value) < 1) {
            return false;
        }

        return true;
    }

    /**
     * Validate that an attribute is a valid email.
     *
     * @param string $attribute
     * @param mixed $value
     * @param array $parameters
     * @return bool
     */
    protected function validateEmail(string $attribute, $value, array $parameters): bool
    {
        if (is_null($value) || $value === '') {
            return true;
        }

        return filter_var($value, FILTER_VALIDATE_EMAIL) !== false;
    }

    /**
     * Validate the minimum length of an attribute.
     *
     * @param string $attribute
     * @param mixed $value
     * @param array $parameters
     * @return bool
     */
    protected function validateMin(string $attribute, $value, array $parameters): bool
    {
        if (is_null($value) || $value === '') {
            return true;
        }

        $min = (int) $parameters[0];

        if (is_string($value)) {
            return mb_strlen($value) >= $min;
        } elseif (is_numeric($value)) {
            return $value >= $min;
        } elseif (is_array($value)) {
            return count($value) >= $min;
        }

        return false;
    }

    /**
     * Validate the maximum length of an attribute.
     *
     * @param string $attribute
     * @param mixed $value
     * @param array $parameters
     * @return bool
     */
    protected function validateMax(string $attribute, $value, array $parameters): bool
    {
        if (is_null($value) || $value === '') {
            return true;
        }

        $max = (int) $parameters[0];

        if (is_string($value)) {
            return mb_strlen($value) <= $max;
        } elseif (is_numeric($value)) {
            return $value <= $max;
        } elseif (is_array($value)) {
            return count($value) <= $max;
        }

        return false;
    }

    /**
     * Validate that an attribute is numeric.
     *
     * @param string $attribute
     * @param mixed $value
     * @param array $parameters
     * @return bool
     */
    protected function validateNumeric(string $attribute, $value, array $parameters): bool
    {
        if (is_null($value) || $value === '') {
            return true;
        }

        return is_numeric($value);
    }

    /**
     * Validate that an attribute is an integer.
     *
     * @param string $attribute
     * @param mixed $value
     * @param array $parameters
     * @return bool
     */
    protected function validateInteger(string $attribute, $value, array $parameters): bool
    {
        if (is_null($value) || $value === '') {
            return true;
        }

        return filter_var($value, FILTER_VALIDATE_INT) !== false;
    }

    /**
     * Validate that an attribute is a string.
     *
     * @param string $attribute
     * @param mixed $value
     * @param array $parameters
     * @return bool
     */
    protected function validateString(string $attribute, $value, array $parameters): bool
    {
        if (is_null($value)) {
            return true;
        }

        return is_string($value);
    }

    /**
     * Validate that an attribute is in a list of values.
     *
     * @param string $attribute
     * @param mixed $value
     * @param array $parameters
     * @return bool
     */
    protected function validateIn(string $attribute, $value, array $parameters): bool
    {
        if (is_null($value) || $value === '') {
            return true;
        }

        return in_array($value, $parameters);
    }

    /**
     * Create a new factory instance.
     *
     * @param array $data
     * @param array $rules
     * @param array $messages
     * @param array $customAttributes
     * @return static
     */
    public static function make(array $data, array $rules, array $messages = [], array $customAttributes = []): self
    {
        return new static($data, $rules, $messages, $customAttributes);
    }

    /**
     * Validate the data and throw an exception if validation fails.
     *
     * @param array $data
     * @param array $rules
     * @param array $messages
     * @param array $customAttributes
     * @return array
     *
     * @throws ValidationException
     */
    public static function validate(array $data, array $rules, array $messages = [], array $customAttributes = []): array
    {
        $validator = static::make($data, $rules, $messages, $customAttributes);

        if (!$validator->validate()) {
            throw new ValidationException($validator->errors());
        }

        // Return only the validated data
        $validatedData = [];
        foreach (array_keys($rules) as $key) {
            if (array_key_exists($key, $data)) {
                $validatedData[$key] = $data[$key];
            }
        }

        return $validatedData;
    }
}
