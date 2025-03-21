<?php

namespace Titan\View;

/**
 * Factory for creating and managing views
 */
class ViewFactory
{
    /**
     * The base path to the views.
     *
     * @var string
     */
    protected string $viewPath;

    /**
     * The view data shared across all views.
     *
     * @var array
     */
    protected static array $shared = [];

    /**
     * The registered view composers.
     *
     * @var array
     */
    protected static array $composers = [];

    /**
     * The registered view creators.
     *
     * @var array
     */
    protected static array $creators = [];

    /**
     * Create a new view factory instance.
     *
     * @param string $viewPath
     */
    public function __construct(string $viewPath)
    {
        $this->viewPath = rtrim($viewPath, '/');
        View::setViewsPath($this->viewPath);
    }

    /**
     * Create a new view instance.
     *
     * @param string $view
     * @param array $data
     * @return View
     */
    public function make(string $view, array $data = []): View
    {
        // First, merge with shared data
        $data = array_merge(static::$shared, $data);

        // Call any applicable view creators
        $this->callCreators($view, $data);

        // Create the view instance
        $view = View::make($view, $data);

        // Call any applicable view composers
        $this->callComposers($view, $data);

        return $view;
    }

    /**
     * Call the view creators for a given view.
     *
     * @param string $view
     * @param array $data
     * @return void
     */
    protected function callCreators(string $view, array &$data): void
    {
        foreach (static::$creators as $pattern => $creators) {
            if ($this->viewMatchesPattern($view, $pattern)) {
                foreach ($creators as $creator) {
                    $creator($data);
                }
            }
        }
    }

    /**
     * Call the view composers for a given view.
     *
     * @param View $view
     * @param array $data
     * @return void
     */
    protected function callComposers(View $view, array $data): void
    {
        $viewName = $view->getName();

        foreach (static::$composers as $pattern => $composers) {
            if ($this->viewMatchesPattern($viewName, $pattern)) {
                foreach ($composers as $composer) {
                    $composer($view, $data);
                }
            }
        }
    }

    /**
     * Check if a view name matches a pattern.
     *
     * @param string $view
     * @param string $pattern
     * @return bool
     */
    protected function viewMatchesPattern(string $view, string $pattern): bool
    {
        // If the pattern is an exact match
        if ($pattern === $view) {
            return true;
        }

        // If the pattern has a wildcard
        if (str_contains($pattern, '*')) {
            $pattern = str_replace('*', '.*', $pattern);
            return (bool) preg_match('/^' . $pattern . '$/', $view);
        }

        return false;
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
        $views = (array) $views;

        foreach ($views as $view) {
            if (!isset(static::$creators[$view])) {
                static::$creators[$view] = [];
            }

            static::$creators[$view][] = $callback;
        }
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
        $views = (array) $views;

        foreach ($views as $view) {
            if (!isset(static::$composers[$view])) {
                static::$composers[$view] = [];
            }

            static::$composers[$view][] = $callback;
        }
    }

    /**
     * Add a piece of shared data to the view.
     *
     * @param string|array $key
     * @param mixed|null $value
     * @return mixed
     */
    public static function shared($key, $value = null)
    {
        if (is_array($key)) {
            static::$shared = array_merge(static::$shared, $key);
        } else {
            static::$shared[$key] = $value;
        }

        return $value;
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
        return static::$shared[$key] ?? $default;
    }

    /**
     * Get all shared data.
     *
     * @return array
     */
    public static function getAllShared(): array
    {
        return static::$shared;
    }

    /**
     * Check if a view exists.
     *
     * @param string $view
     * @return bool
     */
    public function exists(string $view): bool
    {
        return View::exists($view);
    }

    /**
     * Render a view.
     *
     * @param string $view
     * @param array $data
     * @return string
     */
    public function render(string $view, array $data = []): string
    {
        return $this->make($view, $data)->render();
    }
}
