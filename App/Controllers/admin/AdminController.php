<?php
declare(strict_types=1);

namespace Controllers\admin;

use System\Controllers\Controller;
use System\Request;
use System\Response;
use System\Session;
use Exception;

/**
 * AdminController
 *
 * Base controller used by admin pages and services.
 * Responsible for:
 *  - session initialization
 *  - login/logout helpers
 *  - rendering pages (header/footer/sidebar wiring)
 */
abstract class AdminController extends Controller
{
    protected Request $request;
    protected Response $response;
    protected $session;

    /** Which admin routes do NOT require auth */
    protected array $publicRoutes = ['/admin/login', '/admin/forgot', '/admin/reset'];

    /** If true, constructor will guard non-public routes */
    protected bool $autoAuthGuard = true;

    /** login route */
    protected string $loginRoute = '/admin/login';

    public function __construct(Request $request, Response $response)
    {
        do_action('init_theme','admin');
        parent::__construct($request, $response);
        $this->request = $request;
        $this->response = $response;

        // initialize session wrapper
        $this->initSession();

        // build login route relative to base path if Request provides it
        $base = method_exists($this->request, 'getBasePath') ? rtrim($this->request->getBasePath(), '/') : '';
        $this->loginRoute = $base . '/admin/login';

        // enforce auth if required
        if ($this->autoAuthGuard && !$this->isPublicRoute($this->getRequestPath())) {
            if (!$this->isAdminLoggedIn()) {
                $this->redirectToLogin();
                // exit handled by redirectToLogin
            }
        }
    }

    protected function initSession(): void
    {
        if (session_status() === PHP_SESSION_NONE) {
            @session_start();
        }

        // Try to use framework Session wrapper
        try {
            $this->session = new Session();
        } catch (\Throwable $e) {
            // fallback minimal wrapper (based on your original file)
            $this->session = new class {
                public function set($k, $v) { $_SESSION[$k] = $v; }
                public function get($k, $d = null) { return $_SESSION[$k] ?? $d; }
                public function remove($k) { unset($_SESSION[$k]); }
                public function destroy() { $_SESSION = []; if (session_status() === PHP_SESSION_ACTIVE) session_destroy(); }
                public function setFlash($k, $v) { $_SESSION['_flash'][$k] = $v; }
                public function getFlash($k) { $val = $_SESSION['_flash'][$k] ?? null; if (isset($_SESSION['_flash'][$k])) unset($_SESSION['_flash'][$k]); return $val; }
            };
        }
    }

    protected function getRequestPath(): string
    {
        // try Request
        if (method_exists($this->request, 'getPath')) {
            $p = $this->request->getPath();
            return rtrim($p, '/') ?: '/';
        }
        $uri = $_SERVER['REQUEST_URI'] ?? '/';
        $path = parse_url($uri, PHP_URL_PATH);
        $path = rtrim($path, '/') ?: '/';
        $scriptName = $_SERVER['SCRIPT_NAME'] ?? '';
        $basePath = str_replace('\\', '/', dirname($scriptName));
        if ($basePath === '/') $basePath = '';
        if (!empty($basePath) && str_starts_with($path, $basePath)) {
            $path = substr($path, strlen($basePath));
            $path = rtrim($path, '/') ?: '/';
        }
        return $path;
    }

    function yantra_validate_csrf(?string $token): bool
    {
        if (session_status() !== PHP_SESSION_ACTIVE) {
            @session_start();
        }
        return !empty($token) && !empty($_SESSION['yantra_csrf']) && hash_equals($_SESSION['yantra_csrf'], $token);
    }

    protected function isPublicRoute(string $path): bool
    {
        $path = rtrim($path, '/') ?: '/';
        foreach ($this->publicRoutes as $r) {
            if ((rtrim($r, '/') ?: '/') === $path) return true;
        }
        return false;
    }

    protected function redirectToLogin(): void
    {
        if ($this->response && method_exists($this->response, 'redirect')) {
            $this->response->redirect($this->loginRoute, 302);
        }
        else{
            header('Location: ' . $this->loginRoute);
            exit;
        }
    }

    
    // -----------------
    // Session helpers
    // -----------------

    protected function loginAdmin(array $adminData): void
    {
        $this->session->set('admin_logged_in', true);
        // remove password if present
        if (isset($adminData['password'])) unset($adminData['password']);
        $this->session->set('admin', $adminData);
        if (function_exists('session_regenerate_id')) @session_regenerate_id(true);
    }

    protected function logoutAdmin(): void
    {
        $this->session->remove('admin');
        $this->session->remove('admin_logged_in');
        $this->session->remove('_flash');
        if (method_exists($this->session, 'destroy')) $this->session->destroy();
        else {
            $_SESSION = [];
            if (session_status() === PHP_SESSION_ACTIVE) session_destroy();
        }
    }

    protected function isAdminLoggedIn(): bool
    {
        return (bool)$this->session->get('admin_logged_in', false);
    }

    /**
     * Return the currently logged-in admin as a fresh DB-backed array (or null).
     *
     * - Reads session 'admin' to get admin_id.
     * - Fetches fresh row from DB via Models\AdminModel when possible.
     * - Attaches meta (from yt_admin_meta) as ['meta'] and exposes some common meta keys
     *   (e.g. 'bio', 'phone') on the returned array for convenience.
     * - Removes sensitive fields such as 'password'.
     * - If the admin row no longer exists, logs out the session and returns null.
     *
     * @return array|null
     */
    protected function getCurrentAdmin(): ?array
    {
        // Get session-stored admin (lightweight)
        $sessAdmin = $this->getAdmin();
        if (empty($sessAdmin) || empty($sessAdmin['admin_id'])) {
            return null;
        }

        $adminId = (int)$sessAdmin['admin_id'];

        // Attempt to fetch fresh admin row from DB
        $fresh = null;
        try {
            $adminModel = new \Models\AdminModel();

            // Prefer a direct convenience method if available
            if (method_exists($adminModel, 'getById')) {
                $fresh = $adminModel->getById($adminId);
            } else {
                // Fallback to query builder (assumes query()->where()->getResult behavior)
                $res = $adminModel->query()->where('admin_id', '=', $adminId)->getResult(\PDO::FETCH_ASSOC);
                $fresh = $res ?: null;
            }
        } catch (\Throwable $e) {
            error_log('getCurrentAdmin: failed to load AdminModel: ' . $e->getMessage());
            $fresh = null;
        }

        // If admin no longer exists in DB -> clear session and return null
        if (empty($fresh)) {
            $this->logoutAdmin();
            return null;
        }

        // Remove any sensitive fields
        if (isset($fresh['password'])) {
            unset($fresh['password']);
        }

        // Attach meta (if AdminMetaModel exists)
        try {
            if (class_exists('\Models\AdminMetaModel')) {
                $metaModel = new \Models\AdminMetaModel();
                $meta = $metaModel->all_meta_for_admin($adminId);
                if (!empty($meta)) {
                    $fresh['meta'] = $meta;
                    // expose common meta keys directly for convenience
                    if (isset($meta['bio']) && !isset($fresh['bio'])) $fresh['bio'] = $meta['bio'];
                    if (isset($meta['phone']) && !isset($fresh['phone'])) $fresh['phone'] = $meta['phone'];
                    if (isset($meta['avatar']) && !isset($fresh['avatar'])) $fresh['avatar'] = $meta['avatar'];
                }
            }
        } catch (\Throwable $e) {
            // non-fatal: log and continue
            error_log('getCurrentAdmin: failed to load admin meta: ' . $e->getMessage());
        }

        // Update session copy to the fresh representation (keeps session in sync)
        try {
            $this->session->set('admin', $fresh);
        } catch (\Throwable $e) {
            // ignore session write errors (shouldn't happen)
        }

        return is_array($fresh) ? $fresh : null;
    }


    protected function getAdmin(): ?array
    {
        $admin = $this->session->get('admin');
        return is_array($admin) ? $admin : null;
    }

    protected function setFlash(string $key, $val): void
    {
        if (method_exists($this->session, 'setFlash')) $this->session->setFlash($key, $val);
        else $_SESSION['_flash'][$key] = $val;
    }

    protected function getFlash(string $key)
    {
        if (method_exists($this->session, 'getFlash')) return $this->session->getFlash($key);
        $v = $_SESSION['_flash'][$key] ?? null;
        if (isset($_SESSION['_flash'][$key])) unset($_SESSION['_flash'][$key]);
        return $v;
    }

    /**
     * Render page using Response/theme; this mirrors original behaviour
     * child controllers call $this->renderPage($title, $view, $data)
     */
    protected function renderPage(string $view, array $data = []): void
    {
        $this->response->init();
        $this->response->setPageProperty('slug', $this->request->getPath());
        $this->response->setMeta('robots', 'noindex, nofollow');
        try {
            $data['admin'] = $this->getAdmin();
            $data['flash_success'] = $this->getFlash('success');
            $data['flash_error'] = $this->getFlash('error');

            $this->response->add('header', 'admin/header', $data);
            $this->response->setPageContent($view, $data);
            $this->response->add('footer', 'admin/footer');
        } catch (Exception $e) {
            error_log("Error rendering page" . $e->getMessage());
        }
        $this->response->render();
    }
}
