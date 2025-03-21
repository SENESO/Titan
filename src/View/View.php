<?php

namespace Titan\View;

use Titan\View\Exception\ViewNotFoundException;

/**
 * View class for rendering templates
 */
class View
{
    /**
     * The view path.
     *
     * @var string
     */
    protected string $path;

    /**
     * The view data.
     *
     * @var array
     */
    protected array $data;

    /**
     * The base directory for views.
     *
     * @var string
     */
    protected static string $viewsPath;

    /**
     * Create a new view instance.
     *
     * @param string $path
     * @param array $data
     */
    public function __construct(string $path, array $data = [])
    {
        $this->path = $this->normalizePath($path);
        $this->data = $data;
    }

    /**
     * Normalize the view path.
     *
     * @param string $path
     * @return string
     */
    protected function normalizePath(string $path): string
    {
        // Convert dot notation to slashes
        return str_replace('.', '/', $path);
    }

    /**
     * Render the view.
     *
     * @return string
     */
    public function render(): string
    {
        // Resolve the full path to the view file
        $viewPath = $this->getViewPath();

        // Check if the view file exists
        if (!file_exists($viewPath)) {
            throw new ViewNotFoundException("View [{$this->path}] not found.");
        }

        // Extract data to make it available to the view
        extract($this->data, EXTR_SKIP);

        // Start output buffering
        ob_start();

        // Include the view file
        include $viewPath;

        // Get the contents of the buffer and clean it
        return ob_get_clean();
    }

    /**
     * Get the full path to the view.
     *
     * @return string
     */
    protected function getViewPath(): string
    {
        return static::$viewsPath . '/' . $this->path . '.php';
    }

    /**
     * Set the base views path.
     *
     * @param string $path
     * @return void
     */
    public static function setViewsPath(string $path): void
    {
        static::$viewsPath = rtrim($path, '/');
    }

    /**
     * Get the string contents of the view.
     *
     * @return string
     */
    public function __toString(): string
    {
        try {
            return $this->render();
        } catch (\Exception $e) {
            // Convert the exception to a string to prevent uncaught exceptions in __toString
            return "Error rendering view: {$e->getMessage()}";
        }
    }

    /**
     * Create a new view instance.
     *
     * @param string $path
     * @param array $data
     * @return static
     */
    public static function make(string $path, array $data = []): self
    {
        return new static($path, $data);
    }

    /**
     * Share a piece of data across all views.
     *
     * @param string|array $key
     * @param mixed|null $value
     * @return void
     */
    public static function share($key, $value = null): void
    {
        if (is_array($key)) {
            foreach ($key as $innerKey => $innerValue) {
                static::share($innerKey, $innerValue);
            }
        } else {
            ViewFactory::shared($key, $value);
        }
    }

    /**
     * Get a piece of shared data.
     *
     * @param string $key
     * @param mixed|null $default
     * @return mixed
     */
    public static function getShared(string $key, $default = null)
    {
        return ViewFactory::getShared($key, $default);
    }

    /**
     * Get all shared data.
     *
     * @return array
     */
    public static function getAllShared(): array
    {
        return ViewFactory::getAllShared();
    }

    /**
     * Register a view composer.
     *
     * @param string|array $views
     * @param callable $callback
     * @return void
     */
    public static function composer($views, callable $callback): void
    {
        ViewFactory::composer($views, $callback);
    }

    /**
     * Register a view creator.
     *
     * @param string|array $views
     * @param callable $callback
     * @return void
     */
    public static function creator($views, callable $callback): void
    {
        ViewFactory::creator($views, $callback);
    }

    /**
     * Check if a view exists.
     *
     * @param string $path
     * @return bool
     */
    public static function exists(string $path): bool
    {
        $path = str_replace('.', '/', $path);
        return file_exists(static::$viewsPath . '/' . $path . '.php');
    }
}
