<?php
namespace Core\Middleware;

use System\Utilities\Auth;
use System\Utilities\SessionStore;

/**
 * AuthMiddleware
 *
 * Protects routes. Example usage in your router:
 *   (new AuthMiddleware())->handle($request, function() { ... });
 *
 * Options:
 *  - guard: 'session'|'token'
 *  - optional: bool (if true, allows anonymous)
 *  - ability: callback to check permissions
 */
class AuthMiddleware
{
    protected array $options;

    public function __construct(array $options = [])
    {
        $this->options = array_merge([
            'guard' => 'session',
            'optional' => false,
        ], $options);
    }

    /**
     * $next is the callable that runs controller
     */
    public function handle($request, callable $next)
    {
        $guard = $this->options['guard'] ?? 'session';
        if ($guard === 'session') {
            SessionStore::init();
            if (Auth::check()) {
                return $next();
            }
            if ($this->options['optional']) {
                return $next();
            }
            // Redirect or return 401
            http_response_code(302);
            header('Location: /login');
            exit;
        }

        if ($guard === 'token') {
            $authHeader = $request->getHeader('Authorization') ?? $request->getHeader('HTTP_AUTHORIZATION');
            if ($authHeader && preg_match('/Bearer\s+(.*)$/i', $authHeader, $m)) {
                $token = trim($m[1]);
                $user = Auth::userFromToken($token);
                if ($user) {
                    // optionally attach user to request attributes
                    if (method_exists($request, 'set')) $request->set('auth_user', $user);
                    return $next();
                }
            }
            if ($this->options['optional']) {
                return $next();
            }
            http_response_code(401);
            echo json_encode(['error' => 'Unauthorized']);
            exit;
        }

        // unknown guard -> allow
        return $next();
    }
}
