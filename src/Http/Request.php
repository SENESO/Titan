<?php

namespace Titan\Http;

use Closure;

/**
 * Class Request
 *
 * Represents an HTTP request with all input data accessible through
 * a clean, efficient API.
 */
class Request
{
    /**
     * The request query parameters ($_GET).
     *
     * @var array
     */
    protected array $query = [];

    /**
     * The request post data ($_POST).
     *
     * @var array
     */
    protected array $request = [];

    /**
     * The request files ($_FILES).
     *
     * @var array
     */
    protected array $files = [];

    /**
     * The request cookies ($_COOKIE).
     *
     * @var array
     */
    protected array $cookies = [];

    /**
     * The server parameters ($_SERVER).
     *
     * @var array
     */
    protected array $server = [];

    /**
     * The request headers.
     *
     * @var array
     */
    protected array $headers = [];

    /**
     * The raw content of the request body.
     *
     * @var string|null
     */
    protected ?string $content = null;

    /**
     * The decoded JSON content.
     *
     * @var array|null
     */
    protected ?array $json = null;

    /**
     * The route parameters.
     *
     * @var array
     */
    protected array $routeParams = [];

    /**
     * Create a new HTTP request instance.
     *
     * @param array $query
     * @param array $request
     * @param array $files
     * @param array $cookies
     * @param array $server
     * @param string|null $content
     */
    public function __construct(
        array $query = [],
        array $request = [],
        array $files = [],
        array $cookies = [],
        array $server = [],
        ?string $content = null
    ) {
        $this->query = $query;
        $this->request = $request;
        $this->files = $files;
        $this->cookies = $cookies;
        $this->server = $server;
        $this->content = $content;

        // Parse headers from server variables
        $this->headers = $this->parseHeaders();
    }

    /**
     * Parse the headers from the server variables.
     *
     * @return array
     */
    protected function parseHeaders(): array
    {
        $headers = [];
        foreach ($this->server as $key => $value) {
            if (strpos($key, 'HTTP_') === 0) {
                $name = str_replace(' ', '-', ucwords(strtolower(str_replace('_', ' ', substr($key, 5)))));
                $headers[$name] = $value;
            } elseif (in_array($key, ['CONTENT_TYPE', 'CONTENT_LENGTH', 'CONTENT_MD5'])) {
                $name = str_replace(' ', '-', ucwords(strtolower(str_replace('_', ' ', $key))));
                $headers[$name] = $value;
            }
        }

        return $headers;
    }

    /**
     * Create a new request instance from the current PHP global variables.
     *
     * @return static
     */
    public static function capture(): self
    {
        return new static(
            $_GET,
            $_POST,
            $_FILES,
            $_COOKIE,
            $_SERVER,
            file_get_contents('php://input')
        );
    }

    /**
     * Get all of the input and files for the request.
     *
     * @param array|null $keys
     * @return array
     */
    public function all(?array $keys = null): array
    {
        $input = array_replace_recursive($this->query, $this->request);

        if (is_null($keys)) {
            return $input;
        }

        return array_intersect_key($input, array_flip($keys));
    }

    /**
     * Get an input value from the request.
     *
     * @param string|null $key
     * @param mixed $default
     * @return mixed
     */
    public function input(?string $key = null, $default = null)
    {
        $input = array_replace_recursive($this->query, $this->request);

        if (is_null($key)) {
            return $input;
        }

        return $input[$key] ?? $default;
    }

    /**
     * Get a query string value from the request.
     *
     * @param string|null $key
     * @param mixed $default
     * @return mixed
     */
    public function query(?string $key = null, $default = null)
    {
        if (is_null($key)) {
            return $this->query;
        }

        return $this->query[$key] ?? $default;
    }

    /**
     * Get a post value from the request.
     *
     * @param string|null $key
     * @param mixed $default
     * @return mixed
     */
    public function post(?string $key = null, $default = null)
    {
        if (is_null($key)) {
            return $this->request;
        }

        return $this->request[$key] ?? $default;
    }

    /**
     * Get a cookie from the request.
     *
     * @param string|null $key
     * @param mixed $default
     * @return mixed
     */
    public function cookie(?string $key = null, $default = null)
    {
        if (is_null($key)) {
            return $this->cookies;
        }

        return $this->cookies[$key] ?? $default;
    }

    /**
     * Get a server variable from the request.
     *
     * @param string|null $key
     * @param mixed $default
     * @return mixed
     */
    public function server(?string $key = null, $default = null)
    {
        if (is_null($key)) {
            return $this->server;
        }

        return $this->server[$key] ?? $default;
    }

    /**
     * Get a header from the request.
     *
     * @param string|null $key
     * @param mixed $default
     * @return mixed
     */
    public function header(?string $key = null, $default = null)
    {
        if (is_null($key)) {
            return $this->headers;
        }

        return $this->headers[$key] ?? $default;
    }

    /**
     * Get the request method.
     *
     * @return string
     */
    public function method(): string
    {
        return $this->server['REQUEST_METHOD'] ?? 'GET';
    }

    /**
     * Determine if the current request method matches the given method.
     *
     * @param string $method
     * @return bool
     */
    public function isMethod(string $method): bool
    {
        return strtoupper($method) === $this->method();
    }

    /**
     * Determine if the request is via AJAX.
     *
     * @return bool
     */
    public function ajax(): bool
    {
        return $this->header('X-Requested-With') === 'XMLHttpRequest';
    }

    /**
     * Determine if the request is sending JSON.
     *
     * @return bool
     */
    public function isJson(): bool
    {
        return str_contains($this->header('Content-Type', ''), 'application/json');
    }

    /**
     * Get the JSON payload for the request.
     *
     * @param string|null $key
     * @param mixed $default
     * @return mixed
     */
    public function json(?string $key = null, $default = null)
    {
        if (is_null($this->json)) {
            $this->json = json_decode($this->getContent(), true) ?? [];
        }

        if (is_null($key)) {
            return $this->json;
        }

        return $this->json[$key] ?? $default;
    }

    /**
     * Get the raw body of the request.
     *
     * @return string
     */
    public function getContent(): string
    {
        return $this->content ?? '';
    }

    /**
     * Set a route parameter.
     *
     * @param string $name
     * @param mixed $value
     * @return $this
     */
    public function setRouteParam(string $name, $value): self
    {
        $this->routeParams[$name] = $value;

        return $this;
    }

    /**
     * Set the route parameters.
     *
     * @param array $params
     * @return $this
     */
    public function setRouteParams(array $params): self
    {
        $this->routeParams = $params;

        return $this;
    }

    /**
     * Get a route parameter.
     *
     * @param string|null $name
     * @param mixed $default
     * @return mixed
     */
    public function route(?string $name = null, $default = null)
    {
        if (is_null($name)) {
            return $this->routeParams;
        }

        return $this->routeParams[$name] ?? $default;
    }

    /**
     * Get the request URI.
     *
     * @return string
     */
    public function uri(): string
    {
        return $this->server['REQUEST_URI'] ?? '/';
    }

    /**
     * Get the request path.
     *
     * @return string
     */
    public function path(): string
    {
        $pattern = trim(parse_url($this->uri(), PHP_URL_PATH), '/');

        return $pattern === '' ? '/' : '/' . $pattern;
    }

    /**
     * Get the request URL.
     *
     * @return string
     */
    public function url(): string
    {
        return rtrim(preg_replace('/\?.*/', '', $this->uri()), '/');
    }

    /**
     * Get the full URL for the request.
     *
     * @return string
     */
    public function fullUrl(): string
    {
        $query = isset($this->server['QUERY_STRING']) && $this->server['QUERY_STRING'] !== ''
            ? '?' . $this->server['QUERY_STRING']
            : '';

        $scheme = $this->secure() ? 'https' : 'http';

        return $scheme . '://' . $this->server['HTTP_HOST'] . $this->uri() . $query;
    }

    /**
     * Determine if the request is secure.
     *
     * @return bool
     */
    public function secure(): bool
    {
        $https = $this->server['HTTPS'] ?? '';

        return $https === 'on' || $https === '1' ||
               $this->server['REQUEST_SCHEME'] === 'https' ||
               $this->server['HTTP_X_FORWARDED_PROTO'] === 'https';
    }

    /**
     * Get the client IP address.
     *
     * @return string|null
     */
    public function ip(): ?string
    {
        return $this->server['HTTP_X_FORWARDED_FOR'] ?? $this->server['HTTP_CLIENT_IP'] ??
               $this->server['REMOTE_ADDR'] ?? null;
    }
}
