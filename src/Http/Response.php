<?php

namespace Titan\Http;

/**
 * Class Response
 *
 * Represents an HTTP response with fluent interface for setting
 * headers, content, and status code.
 */
class Response
{
    /**
     * HTTP Response status codes.
     */
    const HTTP_OK = 200;
    const HTTP_CREATED = 201;
    const HTTP_ACCEPTED = 202;
    const HTTP_NO_CONTENT = 204;
    const HTTP_MOVED_PERMANENTLY = 301;
    const HTTP_FOUND = 302;
    const HTTP_SEE_OTHER = 303;
    const HTTP_NOT_MODIFIED = 304;
    const HTTP_TEMPORARY_REDIRECT = 307;
    const HTTP_PERMANENT_REDIRECT = 308;
    const HTTP_BAD_REQUEST = 400;
    const HTTP_UNAUTHORIZED = 401;
    const HTTP_PAYMENT_REQUIRED = 402;
    const HTTP_FORBIDDEN = 403;
    const HTTP_NOT_FOUND = 404;
    const HTTP_METHOD_NOT_ALLOWED = 405;
    const HTTP_NOT_ACCEPTABLE = 406;
    const HTTP_CONFLICT = 409;
    const HTTP_GONE = 410;
    const HTTP_UNPROCESSABLE_ENTITY = 422;
    const HTTP_TOO_MANY_REQUESTS = 429;
    const HTTP_INTERNAL_SERVER_ERROR = 500;
    const HTTP_SERVICE_UNAVAILABLE = 503;

    /**
     * The response content.
     *
     * @var string
     */
    protected string $content = '';

    /**
     * The response status code.
     *
     * @var int
     */
    protected int $statusCode = self::HTTP_OK;

    /**
     * The response headers.
     *
     * @var array
     */
    protected array $headers = [];

    /**
     * The response cookies.
     *
     * @var array
     */
    protected array $cookies = [];

    /**
     * The response charset.
     *
     * @var string
     */
    protected string $charset = 'UTF-8';

    /**
     * The response content type.
     *
     * @var string
     */
    protected string $contentType = 'text/html';

    /**
     * Create a new response instance.
     *
     * @param string $content
     * @param int $status
     * @param array $headers
     */
    public function __construct(string $content = '', int $status = self::HTTP_OK, array $headers = [])
    {
        $this->setContent($content);
        $this->setStatusCode($status);

        foreach ($headers as $key => $values) {
            $this->headers[$key] = (array) $values;
        }
    }

    /**
     * Create a new response instance for a view.
     *
     * @param string $content
     * @param int $status
     * @param array $headers
     * @return static
     */
    public static function make(string $content = '', int $status = self::HTTP_OK, array $headers = []): self
    {
        return new static($content, $status, $headers);
    }

    /**
     * Create a new JSON response instance.
     *
     * @param mixed $data
     * @param int $status
     * @param array $headers
     * @param int $options
     * @return static
     */
    public static function json($data = [], int $status = self::HTTP_OK, array $headers = [], int $options = 0): self
    {
        $json = json_encode($data, $options);

        if (JSON_ERROR_NONE !== json_last_error()) {
            throw new \InvalidArgumentException(json_last_error_msg());
        }

        $response = new static($json, $status, $headers);
        $response->header('Content-Type', 'application/json');

        return $response;
    }

    /**
     * Create a new JSONP response instance.
     *
     * @param string $callback
     * @param mixed $data
     * @param int $status
     * @param array $headers
     * @param int $options
     * @return static
     */
    public static function jsonp(string $callback, $data = [], int $status = self::HTTP_OK, array $headers = [], int $options = 0): self
    {
        $json = json_encode($data, $options);

        if (JSON_ERROR_NONE !== json_last_error()) {
            throw new \InvalidArgumentException(json_last_error_msg());
        }

        $response = new static($callback . '(' . $json . ');', $status, $headers);
        $response->header('Content-Type', 'application/javascript');

        return $response;
    }

    /**
     * Create a new download response instance.
     *
     * @param string $file
     * @param string|null $name
     * @param array $headers
     * @return static
     */
    public static function download(string $file, ?string $name = null, array $headers = []): self
    {
        $response = new static(file_get_contents($file), self::HTTP_OK, $headers);

        $disposition = $response->headers['Content-Disposition'] ?? [];

        if (!$name) {
            $name = basename($file);
        }

        $disposition[] = 'attachment; filename="' . $name . '"';

        $response->header('Content-Disposition', $disposition);
        $response->header('Content-Type', mime_content_type($file));
        $response->header('Content-Length', filesize($file));

        return $response;
    }

    /**
     * Set the response content.
     *
     * @param string $content
     * @return $this
     */
    public function setContent(string $content): self
    {
        $this->content = $content;

        return $this;
    }

    /**
     * Get the response content.
     *
     * @return string
     */
    public function getContent(): string
    {
        return $this->content;
    }

    /**
     * Set the response status code.
     *
     * @param int $code
     * @return $this
     */
    public function setStatusCode(int $code): self
    {
        $this->statusCode = $code;

        return $this;
    }

    /**
     * Get the response status code.
     *
     * @return int
     */
    public function getStatusCode(): int
    {
        return $this->statusCode;
    }

    /**
     * Set a response header.
     *
     * @param string $key
     * @param string|array $values
     * @param bool $replace
     * @return $this
     */
    public function header(string $key, $values, bool $replace = true): self
    {
        $values = (array) $values;

        if ($replace || !isset($this->headers[$key])) {
            $this->headers[$key] = $values;
        } else {
            $this->headers[$key] = array_merge($this->headers[$key], $values);
        }

        return $this;
    }

    /**
     * Add multiple headers at once.
     *
     * @param array $headers
     * @return $this
     */
    public function withHeaders(array $headers): self
    {
        foreach ($headers as $key => $value) {
            $this->header($key, $value);
        }

        return $this;
    }

    /**
     * Get the response headers.
     *
     * @return array
     */
    public function getHeaders(): array
    {
        return $this->headers;
    }

    /**
     * Set a cookie.
     *
     * @param string $name
     * @param string $value
     * @param int $expire
     * @param string $path
     * @param string $domain
     * @param bool $secure
     * @param bool $httpOnly
     * @param string|null $sameSite
     * @return $this
     */
    public function cookie(
        string $name,
        string $value,
        int $expire = 0,
        string $path = '/',
        string $domain = '',
        bool $secure = false,
        bool $httpOnly = true,
        ?string $sameSite = null
    ): self {
        $this->cookies[$name] = [
            'value' => $value,
            'expire' => $expire,
            'path' => $path,
            'domain' => $domain,
            'secure' => $secure,
            'httpOnly' => $httpOnly,
            'sameSite' => $sameSite,
        ];

        return $this;
    }

    /**
     * Return a redirect response.
     *
     * @param string $url
     * @param int $status
     * @param array $headers
     * @return static
     */
    public static function redirect(string $url, int $status = self::HTTP_FOUND, array $headers = []): self
    {
        $response = new static('', $status, $headers);
        $response->header('Location', $url);

        return $response;
    }

    /**
     * Return a not found response.
     *
     * @param string $message
     * @param array $headers
     * @return static
     */
    public static function notFound(string $message = 'Not Found', array $headers = []): self
    {
        return new static($message, self::HTTP_NOT_FOUND, $headers);
    }

    /**
     * Return a no content response.
     *
     * @param array $headers
     * @return static
     */
    public static function noContent(array $headers = []): self
    {
        return new static('', self::HTTP_NO_CONTENT, $headers);
    }

    /**
     * Set the content type.
     *
     * @param string $contentType
     * @return $this
     */
    public function setContentType(string $contentType): self
    {
        $this->contentType = $contentType;

        return $this;
    }

    /**
     * Set the charset.
     *
     * @param string $charset
     * @return $this
     */
    public function setCharset(string $charset): self
    {
        $this->charset = $charset;

        return $this;
    }

    /**
     * Prepare the response to be sent.
     *
     * @return $this
     */
    protected function prepare(): self
    {
        if (!isset($this->headers['Content-Type'])) {
            $this->header('Content-Type', $this->contentType . '; charset=' . $this->charset);
        }

        return $this;
    }

    /**
     * Send the response.
     *
     * @return $this
     */
    public function send(): self
    {
        $this->prepare();

        // Send the status code
        http_response_code($this->statusCode);

        // Send the headers
        foreach ($this->headers as $name => $values) {
            foreach ($values as $value) {
                header("$name: $value", false);
            }
        }

        // Send the cookies
        foreach ($this->cookies as $name => $cookie) {
            setcookie(
                $name,
                $cookie['value'],
                [
                    'expires' => $cookie['expire'],
                    'path' => $cookie['path'],
                    'domain' => $cookie['domain'],
                    'secure' => $cookie['secure'],
                    'httponly' => $cookie['httpOnly'],
                    'samesite' => $cookie['sameSite'],
                ]
            );
        }

        // Send the content
        echo $this->content;

        return $this;
    }

    /**
     * Convert the response to a string.
     *
     * @return string
     */
    public function __toString(): string
    {
        return $this->getContent();
    }
}
