<?php

namespace System\Middleware;

use System\Exceptions\CsrfException;
use System\Helpers\FormHelper;
use System\Request;

/**
 * Simple CSRF middleware.
 *
 * Usage:
 *   $middleware = new CsrfMiddleware();
 *   $response = $middleware->handle($request, fn($req) => $nextHandler($req));
 *
 * The middleware throws CsrfException when invalid. Let your global exception handler
 * map CsrfException to an HTTP 400 response or redirect.
 */
class CsrfMiddleware
{
    /**
     * Handle an incoming request.
     *
     * @param Request $request
     * @param callable $next function(Request): mixed
     * @return mixed
     * @throws CsrfException
     */
    public function handle(Request $request, callable $next): mixed
    {
        // Use FormHelper::validateCsrfToken which reads from request (POST/REQUEST)
        if (!FormHelper::validateCsrfToken(null)) {
            throw new CsrfException();
        }

        return $next($request);
    }
}
