<?php

namespace Titan\Config;

use ArrayAccess;
use Titan\Config\Exception\ConfigNotFoundException;

/**
 * Class ConfigRepository
 *
 * Configuration repository for accessing config values from
 * configuration files using dot notation.
 */
class ConfigRepository implements ArrayAccess
{
    /**
     * All of the configuration items.
     *
     * @var array
     */
    protected array $items = [];

    /**
     * Create a new configuration repository.
     *
     * @param array $items
     */
    public function __construct(array $items = [])
    {
        $this->items = $items;
    }

    /**
     * Determine if the given configuration value exists.
     *
     * @param string $key
     * @return bool
     */
    public function has(string $key): bool
    {
        return $this->get($key) !== null;
    }

    /**
     * Get a configuration value.
     *
     * @param string|null $key
     * @param mixed $default
     * @return mixed
     */
    public function get(?string $key = null, $default = null)
    {
        if (is_null($key)) {
            return $this->items;
        }

        // Handle dot notation
        $parts = explode('.', $key);
        $items = $this->items;

        foreach ($parts as $part) {
            if (!isset($items[$part])) {
                return $default;
            }

            $items = $items[$part];
        }

        return $items;
    }

    /**
     * Get a configuration value or throw an exception if it doesn't exist.
     *
     * @param string $key
     * @return mixed
     *
     * @throws ConfigNotFoundException
     */
    public function getOrFail(string $key)
    {
        $value = $this->get($key);

        if (is_null($value)) {
            throw new ConfigNotFoundException("Configuration item not found: {$key}");
        }

        return $value;
    }

    /**
     * Set a configuration value.
     *
     * @param string $key
     * @param mixed $value
     * @return void
     */
    public function set(string $key, $value): void
    {
        // Handle dot notation for setting
        $parts = explode('.', $key);
        $items = &$this->items;

        foreach ($parts as $i => $part) {
            if (count($parts) === 1) {
                break;
            }

            if ($i === count($parts) - 1) {
                break;
            }

            if (!isset($items[$part]) || !is_array($items[$part])) {
                $items[$part] = [];
            }

            $items = &$items[$part];
        }

        $items[array_pop($parts)] = $value;
    }

    /**
     * Load configuration from a file.
     *
     * @param string $file
     * @return void
     */
    public function load(string $file): void
    {
        if (file_exists($file)) {
            $config = require $file;

            if (is_array($config)) {
                $filename = pathinfo($file, PATHINFO_FILENAME);
                $this->items[$filename] = $config;
            }
        }
    }

    /**
     * Load configuration files from a directory.
     *
     * @param string $directory
     * @return void
     */
    public function loadFromDirectory(string $directory): void
    {
        if (is_dir($directory)) {
            $files = glob($directory . '/*.php');

            foreach ($files as $file) {
                $this->load($file);
            }
        }
    }

    /**
     * Determine if an item exists at an offset.
     *
     * @param mixed $key
     * @return bool
     */
    public function offsetExists($key): bool
    {
        return $this->has($key);
    }

    /**
     * Get an item at a given offset.
     *
     * @param mixed $key
     * @return mixed
     */
    public function offsetGet($key)
    {
        return $this->get($key);
    }

    /**
     * Set the item at a given offset.
     *
     * @param mixed $key
     * @param mixed $value
     * @return void
     */
    public function offsetSet($key, $value): void
    {
        $this->set($key, $value);
    }

    /**
     * Unset the item at a given offset.
     *
     * @param mixed $key
     * @return void
     */
    public function offsetUnset($key): void
    {
        $this->set($key, null);
    }

    /**
     * Get all of the configuration items.
     *
     * @return array
     */
    public function all(): array
    {
        return $this->items;
    }
}
