<?php

namespace Titan\Middleware;

use Titan\Http\Request;
use Titan\Http\Response;

/**
 * Interface MiddlewareInterface
 *
 * Defines the contract for middleware components in the application.
 */
interface MiddlewareInterface
{
    /**
     * Handle the request.
     *
     * This method can either return null to allow the request to continue through
     * the middleware stack, or return a Response to short-circuit the stack.
     *
     * @param Request $request
     * @return Response|null
     */
    public function handle(Request $request): ?Response;
}
