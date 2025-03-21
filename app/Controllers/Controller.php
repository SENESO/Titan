<?php

namespace App\Controllers;

use Titan\Http\Request;
use Titan\Http\Response;

/**
 * Base Controller
 *
 * This class serves as a base controller that all other controllers
 * in the application should extend.
 */
abstract class Controller
{
    /**
     * Create a new response instance.
     *
     * @param string $content
     * @param int $status
     * @param array $headers
     * @return Response
     */
    protected function response(string $content = '', int $status = 200, array $headers = []): Response
    {
        return new Response($content, $status, $headers);
    }

    /**
     * Create a new JSON response instance.
     *
     * @param mixed $data
     * @param int $status
     * @param array $headers
     * @param int $options
     * @return Response
     */
    protected function json($data = [], int $status = 200, array $headers = [], int $options = 0): Response
    {
        return Response::json($data, $status, $headers, $options);
    }

    /**
     * Create a new redirect response.
     *
     * @param string $url
     * @param int $status
     * @param array $headers
     * @return Response
     */
    protected function redirect(string $url, int $status = 302, array $headers = []): Response
    {
        return Response::redirect($url, $status, $headers);
    }

    /**
     * Return a view response.
     *
     * @param string $view
     * @param array $data
     * @param int $status
     * @param array $headers
     * @return Response
     */
    protected function view(string $view, array $data = [], int $status = 200, array $headers = []): Response
    {
        // In a real implementation, this would use a View engine
        // For now, we'll just return a basic response
        return new Response("View: $view with data: " . json_encode($data), $status, $headers);
    }

    /**
     * Return a 404 not found response.
     *
     * @param string $message
     * @param array $headers
     * @return Response
     */
    protected function notFound(string $message = 'Not Found', array $headers = []): Response
    {
        return Response::notFound($message, $headers);
    }

    /**
     * Return a validation error response.
     *
     * @param array $errors
     * @param int $status
     * @param array $headers
     * @return Response
     */
    protected function validationError(array $errors, int $status = 422, array $headers = []): Response
    {
        return $this->json([
            'status' => 'error',
            'message' => 'Validation failed',
            'errors' => $errors,
        ], $status, $headers);
    }
}
