<?php

use Titan\Core\Application;
use Titan\Http\Response;
use Titan\View\View;

if (!function_exists('app')) {
    /**
     * Get the available container instance.
     *
     * @param string|null $abstract
     * @param array $parameters
     * @return mixed|\Titan\Core\Application
     */
    function app($abstract = null, array $parameters = [])
    {
        if (is_null($abstract)) {
            return Application::getInstance();
        }

        return Application::getInstance()->make($abstract, $parameters);
    }
}

if (!function_exists('config')) {
    /**
     * Get / set the specified configuration value.
     *
     * If an array is passed as the key, we will assume you want to set an array of values.
     *
     * @param string|array|null $key
     * @param mixed $default
     * @return mixed|\Titan\Config\ConfigRepository
     */
    function config($key = null, $default = null)
    {
        if (is_null($key)) {
            return app('config');
        }

        if (is_array($key)) {
            foreach ($key as $innerKey => $innerValue) {
                config()->set($innerKey, $innerValue);
            }

            return null;
        }

        return app('config')->get($key, $default);
    }
}

if (!function_exists('view')) {
    /**
     * Get the evaluated view contents for the given view.
     *
     * @param string|null $view
     * @param array $data
     * @param array $mergeData
     * @return \Titan\View\View|\Titan\View\ViewFactory
     */
    function view($view = null, $data = [], $mergeData = [])
    {
        $factory = app('view');

        if (func_num_args() === 0) {
            return $factory;
        }

        return $factory->make($view, $data, $mergeData);
    }
}

if (!function_exists('redirect')) {
    /**
     * Get a redirect response to the given URL.
     *
     * @param string|null $url
     * @param int $status
     * @param array $headers
     * @return \Titan\Http\Response|\Titan\Http\RedirectResponse
     */
    function redirect($url = null, $status = 302, $headers = [])
    {
        if (is_null($url)) {
            return app('redirect');
        }

        return app('redirect')->to($url, $status, $headers);
    }
}

if (!function_exists('response')) {
    /**
     * Return a new response from the application.
     *
     * @param string|array|null $content
     * @param int $status
     * @param array $headers
     * @return \Titan\Http\Response
     */
    function response($content = '', $status = 200, array $headers = [])
    {
        $factory = app('response');

        if (func_num_args() === 0) {
            return $factory;
        }

        return $factory->make($content, $status, $headers);
    }
}

if (!function_exists('abort')) {
    /**
     * Throw an HttpException with the given data.
     *
     * @param int $code
     * @param string $message
     * @param array $headers
     * @return void
     *
     * @throws \Titan\Http\Exception\HttpException
     */
    function abort($code, $message = '', array $headers = [])
    {
        app('exceptions')->abort($code, $message, $headers);
    }
}

if (!function_exists('env')) {
    /**
     * Gets the value of an environment variable.
     *
     * @param string $key
     * @param mixed $default
     * @return mixed
     */
    function env($key, $default = null)
    {
        $value = getenv($key);

        if ($value === false) {
            return $default;
        }

        switch (strtolower($value)) {
            case 'true':
            case '(true)':
                return true;
            case 'false':
            case '(false)':
                return false;
            case 'empty':
            case '(empty)':
                return '';
            case 'null':
            case '(null)':
                return null;
        }

        if (strlen($value) > 1 && $value[0] === '"' && $value[strlen($value) - 1] === '"') {
            return substr($value, 1, -1);
        }

        return $value;
    }
}

if (!function_exists('asset')) {
    /**
     * Generate an asset path for the application.
     *
     * @param string $path
     * @param bool $secure
     * @return string
     */
    function asset($path, $secure = null)
    {
        return app('url')->asset($path, $secure);
    }
}

if (!function_exists('url')) {
    /**
     * Generate a url for the application.
     *
     * @param string|null $path
     * @param array $parameters
     * @param bool|null $secure
     * @return string
     */
    function url($path = null, $parameters = [], $secure = null)
    {
        if (is_null($path)) {
            return app('url');
        }

        return app('url')->to($path, $parameters, $secure);
    }
}

if (!function_exists('route')) {
    /**
     * Generate the URL to a named route.
     *
     * @param string $name
     * @param array $parameters
     * @param bool $absolute
     * @return string
     */
    function route($name, $parameters = [], $absolute = true)
    {
        return app('url')->route($name, $parameters, $absolute);
    }
}

if (!function_exists('session')) {
    /**
     * Get / set the specified session value.
     *
     * If an array is passed as the key, we will assume you want to set an array of values.
     *
     * @param string|array|null $key
     * @param mixed $default
     * @return mixed|\Titan\Session\SessionManager|\Titan\Session\Store
     */
    function session($key = null, $default = null)
    {
        if (is_null($key)) {
            return app('session');
        }

        if (is_array($key)) {
            return app('session')->put($key);
        }

        return app('session')->get($key, $default);
    }
}

if (!function_exists('cookie')) {
    /**
     * Create a new cookie instance.
     *
     * @param string|null $name
     * @param string|null $value
     * @param int $minutes
     * @param string|null $path
     * @param string|null $domain
     * @param bool|null $secure
     * @param bool $httpOnly
     * @param bool $raw
     * @param string|null $sameSite
     * @return mixed
     */
    function cookie($name = null, $value = null, $minutes = 0, $path = null, $domain = null, $secure = null, $httpOnly = true, $raw = false, $sameSite = null)
    {
        $cookie = app('cookie');

        if (is_null($name)) {
            return $cookie;
        }

        return $cookie->make($name, $value, $minutes, $path, $domain, $secure, $httpOnly, $raw, $sameSite);
    }
}

if (!function_exists('auth')) {
    /**
     * Get the available auth instance.
     *
     * @param string|null $guard
     * @return mixed|\Titan\Auth\AuthManager|\Titan\Auth\Guard
     */
    function auth($guard = null)
    {
        if (is_null($guard)) {
            return app('auth');
        }

        return app('auth')->guard($guard);
    }
}

if (!function_exists('bcrypt')) {
    /**
     * Hash the given value against the bcrypt algorithm.
     *
     * @param string $value
     * @param array $options
     * @return string
     */
    function bcrypt($value, $options = [])
    {
        return app('hash')->make($value, $options);
    }
}

if (!function_exists('now')) {
    /**
     * Create a new DateTime instance for the current time.
     *
     * @param \DateTimeZone|string|null $tz
     * @return \DateTimeImmutable
     */
    function now($tz = null)
    {
        return new \DateTimeImmutable('now', $tz ? new \DateTimeZone($tz) : null);
    }
}

if (!function_exists('base_path')) {
    /**
     * Get the path to the base of the installation.
     *
     * @param string $path
     * @return string
     */
    function base_path($path = '')
    {
        return app()->basePath() . ($path ? DIRECTORY_SEPARATOR . $path : $path);
    }
}

if (!function_exists('app_path')) {
    /**
     * Get the path to the application folder.
     *
     * @param string $path
     * @return string
     */
    function app_path($path = '')
    {
        return app()->appPath() . ($path ? DIRECTORY_SEPARATOR . $path : $path);
    }
}

if (!function_exists('config_path')) {
    /**
     * Get the path to the config folder.
     *
     * @param string $path
     * @return string
     */
    function config_path($path = '')
    {
        return app()->configPath() . ($path ? DIRECTORY_SEPARATOR . $path : $path);
    }
}

if (!function_exists('public_path')) {
    /**
     * Get the path to the public folder.
     *
     * @param string $path
     * @return string
     */
    function public_path($path = '')
    {
        return app()->publicPath() . ($path ? DIRECTORY_SEPARATOR . $path : $path);
    }
}

if (!function_exists('storage_path')) {
    /**
     * Get the path to the storage folder.
     *
     * @param string $path
     * @return string
     */
    function storage_path($path = '')
    {
        return app()->storagePath() . ($path ? DIRECTORY_SEPARATOR . $path : $path);
    }
}

if (!function_exists('database_path')) {
    /**
     * Get the path to the database folder.
     *
     * @param string $path
     * @return string
     */
    function database_path($path = '')
    {
        return app()->databasePath() . ($path ? DIRECTORY_SEPARATOR . $path : $path);
    }
}

if (!function_exists('resource_path')) {
    /**
     * Get the path to the resources folder.
     *
     * @param string $path
     * @return string
     */
    function resource_path($path = '')
    {
        return app()->resourcePath() . ($path ? DIRECTORY_SEPARATOR . $path : $path);
    }
}

if (!function_exists('old')) {
    /**
     * Retrieve an old input item.
     *
     * @param string|null $key
     * @param mixed $default
     * @return mixed
     */
    function old($key = null, $default = null)
    {
        return app('request')->old($key, $default);
    }
}

if (!function_exists('csrf_token')) {
    /**
     * Get the CSRF token value.
     *
     * @return string
     *
     * @throws \RuntimeException
     */
    function csrf_token()
    {
        $session = app('session');

        if (isset($session)) {
            return $session->token();
        }

        throw new RuntimeException('Application session store not set.');
    }
}

if (!function_exists('csrf_field')) {
    /**
     * Generate a CSRF token form field.
     *
     * @return string
     */
    function csrf_field()
    {
        return '<input type="hidden" name="_token" value="' . csrf_token() . '">';
    }
}

if (!function_exists('method_field')) {
    /**
     * Generate a form field to spoof the HTTP verb used by forms.
     *
     * @param string $method
     * @return string
     */
    function method_field($method)
    {
        return '<input type="hidden" name="_method" value="' . $method . '">';
    }
}
