<?php
declare(strict_types=1);

namespace Controllers\admin;

use System\Request;
use System\Response;
use Models\AdminModel;
use Models\MetaModel;
use Models\AdminMetaModel;
use Exception;

/**
 * YTAdmin - handles admin pages and auth flows
 *
 * Notes:
 *  - Constructor accepts model dependencies (DI-friendly). If not provided, will instantiate defaults.
 *  - Methods return void because controller base methods render/emit responses directly
 *    (keeps backward compatibility with existing renderPage/sendJson helpers).
 */
class YTAdmin extends AdminController
{
    protected AdminModel $adminModel;
    protected MetaModel $metaModel;
    protected AdminMetaModel $adminMetaModel;

    /**
     * Public routes (no auth)
     * @var string[]
     */
    protected array $publicRoutes = [
        '/admin/login',
        '/admin/forgot',
        '/admin/reset',
        '/admin/logout'
    ];

    /**
     * YTAdmin constructor.
     *
     * @param Request $request
     * @param Response $response
     * @param AdminModel|null $adminModel Optional DI
     * @param MetaModel|null $metaModel Optional DI
     * @param AdminMetaModel|null $adminMetaModel Optional DI
     */
    public function __construct(
        Request $request,
        Response $response,
        ?AdminModel $adminModel = null,
        ?MetaModel $metaModel = null,
        ?AdminMetaModel $adminMetaModel = null
    ) {
        parent::__construct($request, $response);

        // allow DI, otherwise fall back to default instances
        $this->adminModel     = $adminModel ?? new AdminModel();
        $this->metaModel      = $metaModel ?? new MetaModel();
        $this->adminMetaModel = $adminMetaModel ?? new AdminMetaModel();
    }

    /* ------------------
       GET page handlers
       ------------------ */

    /**
     * Dashboard listing
     */
    public function index(): void
    {
        // sidebar will be included by renderPage via response->add in parent
        $this->response->add('sidebar', 'admin/sidebar', []);
        $this->response->setPageProperty('title', 'Admin Dashboard');
        // lightweight stats — replace by OptionModel or a StatsService when available
        $stats = [
            'active_users' => 13482,
            'revenue'      => '₹254,820',
            'server'       => 'OK'
        ];

        $recent_posts = apply_filter('admin_recent_posts', []);

        $this->renderPage('admin/index', [
            'stats'        => $stats,
            'recent_posts' => $recent_posts
        ]);
    }

    /**
     * Admin login view
     */
    public function loginPage(): void
    {
        // if already logged in, redirect to /admin
        if ($this->isAdminLoggedIn()) {
            // prefer framework response helper if available
            try {
                $psr = $this->response->redirect(site_url('/admin'), 302);
                $this->emitPsrResponse($psr, true);
            } catch (\Throwable $e) {
                // fallback to simple header redirect if core response isn't available
                header('Location: ' . site_url('/admin'), true, 302);
            }
            return;
        }

        $this->response->setPageLayout('login');
        $this->response->setPageProperty('title', 'Admin Login');
        $this->renderPage( 'admin/login', [
            'csrf_token' => $this->csrfToken()
        ]);
    }

    /**
     * Forgot password view (fixed method name typo)
     */
    public function forgotPassword(): void
    {
        $this->response->setPageLayout('login');
        $this->response->setPageProperty('title', 'Forgot Password');
        $this->renderPage( 'admin/forgot', [
            'csrf_token' => $this->csrfToken()
        ]);
    }

    /**
     * Reset password view (GET)
     */
    public function resetPassword(): void
    {
        $this->response->setPageLayout('login');
        $this->response->setPageProperty('title', 'Reset Password');
        // token may be present on query string — controllers/views can read from request
        $this->renderPage( 'admin/reset', [
            'csrf_token' => $this->csrfToken(),
            'token'      => $this->request->getQuery('token', '')
        ]);
    }

    /**
     * Show profile page for logged-in admin
     */
    public function profilePage(): void
    {
        $this->response->add('sidebar', 'admin/sidebar', []);
        $this->response->setPageProperty('title', 'My Profile - Admin');

        $admin = $this->getAdmin();
        if (empty($admin) || empty($admin['admin_id'])) {
            // not authenticated — redirect to login
            $psr = $this->response->getCoreResponse()->redirect(site_url('/admin/login'), 302);
            $this->emitPsrResponse($psr, true);
            return;
        }

        $adminId = (int)$admin['admin_id'];
        $dbAdmin = $this->adminModel->getById($adminId);

        // Load meta
        $meta = $this->adminMetaModel->all_meta_for_admin($adminId);

        if (isset($meta['avatar'])) {
            // convert stored media id to media object/path if Request helper exists
            $meta['avatar'] = $this->request->getMedia((int)$meta['avatar']);
        }

        $this->renderPage('admin/profile', [
            'admin'      => $dbAdmin,
            'meta'       => $meta,
            'csrf_token' => $this->csrfToken()
        ]);
    }

    /* ------------------
       POST handlers
       ------------------ */

    /**
     * JSON-only endpoint to save profile fields and avatar.
     * Uses admin meta for avatar, phone and bio.
     */
    public function saveProfile(): void
    {
        if (!$this->isAdminLoggedIn()) {
            $this->response->sendJson(['status' => false, 'message' => 'Authentication required.'], 401);
            return;
        }

        // CSRF validate (header or form)
        $token = (string)$this->request->input('csrf_token', '');
        if (!$this->validateCsrf($token)) {
            $this->error('Invalid or missing CSRF token.', 400);
            return;
        }

        // Current admin
        $current = $this->getCurrentAdmin();
        if (empty($current) || empty($current['admin_id'])) {
            $this->response->sendJson(['status' => false, 'message' => 'Admin not found.'], 404);
            return;
        }
        $adminId = (int)$current['admin_id'];

        // Inputs (text fields)
        $display_name = trim((string)$this->request->input('display_name', ''));
        $username     = trim((string)$this->request->input('username', ''));
        $email        = trim((string)$this->request->input('email', ''));
        $bio          = (string)$this->request->input('bio', '');
        $phone        = (string)$this->request->input('phone', '');

        $errors = [];
        if ($username === '') $errors['username'] = 'Username is required.';
        if ($email === '' || !filter_var($email, FILTER_VALIDATE_EMAIL)) $errors['email'] = 'Valid email required.';
        if (mb_strlen($display_name) > 191) $errors['display_name'] = 'Display name too long.';
        if (mb_strlen($bio) > 2000) $errors['bio'] = 'Bio too long.';
        if ($phone !== '' && mb_strlen($phone) > 64) $errors['phone'] = 'Phone too long.';

        // Uniqueness checks (only if changed)
        try {
            $orig = $this->adminModel->getById($adminId);
            if ($orig) {
                if ($username !== $orig['username']) {
                    $exists = $this->adminModel->getByUsername($username);
                    if ($exists) $errors['username'] = 'Username already taken.';
                }
                if ($email !== $orig['email']) {
                    $r = $this->adminModel->query()->where('email', '=', $email)->getResult(\PDO::FETCH_ASSOC);
                    if ($r) $errors['email'] = 'Email already in use.';
                }
            }
        } catch (\Throwable $e) {
            error_log('Profile uniqueness check failed: ' . $e->getMessage());
        }

        if (!empty($errors)) {
            $this->response->sendJson(['status' => false, 'message' => 'Validation failed.', 'errors' => $errors], 422);
            return;
        }

        // Update core admin fields that exist: full_name, username, email, updated_at
        $update = [
            'full_name'  => $display_name !== '' ? $display_name : null,
            'username'   => $username,
            'email'      => $email,
            'updated_at' => date('Y-m-d H:i:s'),
        ];

        try {
            $this->adminModel->query('update')->data($update)->where('admin_id', '=', $adminId)->executeQuery();
        } catch (\Throwable $e) {
            error_log('Profile update failed: ' . $e->getMessage());
            $this->response->sendJson(['status' => false, 'message' => 'Failed to update profile.'], 500);
            return;
        }

        // Save bio and phone into admin meta (delete meta when empty)
        try {
            if ($bio !== '') {
                $this->adminMetaModel->update_meta('bio', $adminId, $bio);
            } else {
                $this->adminMetaModel->delete_meta('bio', $adminId);
            }
            if ($phone !== '') {
                $this->adminMetaModel->update_meta('phone', $adminId, $phone);
            } else {
                $this->adminMetaModel->delete_meta('phone', $adminId);
            }
        } catch (\Throwable $e) {
            error_log('Profile meta save error: ' . $e->getMessage());
        }

        // Handle avatar via Request::inputMedia (returns media id or null)
        $webPath = $this->request->inputMedia('avatar', null, [
            'file_name'   => "admin-$username-avatar",
            'alt'         => 'Avatar',
            'uploaded_by' => $adminId,
            'title'       => "Admin avatar $display_name",
            'caption'     => "Admin avatar $display_name"
        ]);

        try {
            // update_meta will accept null/empty to delete or set id
            $this->adminMetaModel->update_meta('avatar', $adminId, $webPath);
        } catch (\Throwable $e) {
            error_log('Failed to save avatar meta: ' . $e->getMessage());
        }

        // Fetch fresh admin + meta to return
        try {
            $freshAdmin = $this->adminModel->getById($adminId);
            $meta = $this->adminMetaModel->all_meta_for_admin($adminId);
            if (!empty($meta)) {
                $freshAdmin['meta'] = $meta;
                if (isset($meta['bio'])) $freshAdmin['bio'] = $meta['bio'];
                if (isset($meta['phone'])) $freshAdmin['phone'] = $meta['phone'];
                if (isset($meta['avatar'])) $freshAdmin['avatar'] = $meta['avatar'];
            }
            if (isset($freshAdmin['password'])) unset($freshAdmin['password']);
        } catch (\Throwable $e) {
            $freshAdmin = $current;
        }

        // Rotate CSRF token
        $this->regenerateCsrf();

        $this->response->sendJson([
            'status'  => true,
            'message' => 'Profile updated.',
            'admin'   => $freshAdmin,
            'csrf'    => $this->csrfToken()
        ], 200);
    }

    /**
     * Handle login POST (JSON response)
     */
    public function handleLogin(): void
    {
        // CSRF check
        $token = (string)$this->request->input('csrf_token', '');
        if (!$this->validateCsrf($token)) {
            $this->error('Invalid or missing CSRF token.', 400);
            return;
        }

        // Input validation
        $username = trim((string)$this->request->input('username', ''));
        $password = (string)$this->request->input('password', '');

        $errors = [];
        if ($username === '') $errors['username'] = 'Username is required.';
        if ($password === '') $errors['password'] = 'Password is required.';

        if (!empty($errors)) {
            $this->response->sendJson([
                'status'  => false,
                'message' => 'Validation failed.',
                'errors'  => $errors
            ], 422);
            return;
        }

        // Rate-limit (session based)
        $this->ensureSession();
        $_SESSION['yt_login_attempts'] = $_SESSION['yt_login_attempts'] ?? [];
        $now = time();
        $_SESSION['yt_login_attempts'] = array_filter(
            $_SESSION['yt_login_attempts'],
            fn($t) => ($t + 900) > $now
        );
        if (count($_SESSION['yt_login_attempts']) >= 100) {
            $this->error('Too many login attempts. Please try again later.', 429);
            return;
        }

        // Authenticate
        try {
            $admin = $this->adminModel->authenticate($username, $password);
        } catch (\Throwable $e) {
            error_log('Admin login error: ' . $e->getMessage());
            $this->error('Authentication error. Please try again later.', 500);
            return;
        }

        if (!$admin) {
            $_SESSION['yt_login_attempts'][] = $now;
            $this->response->sendJson([
                'status'  => false,
                'message' => 'Invalid username or password.',
                'errors'  => ['username' => '', 'password' => '']
            ], 200);
            return;
        }

        // success
        $_SESSION['yt_login_attempts'] = [];
        $this->loginAdmin($admin);

        try {
            $this->adminModel->update([
                'last_login' => date('Y-m-d H:i:s'),
                'last_ip'    => $_SERVER['REMOTE_ADDR'] ?? ''
            ], ['admin_id' => $admin['admin_id']]);
        } catch (\Throwable $e) {
            // non-fatal
        }

        $this->response->redirect(site_url('/admin'),302);
        // // Rotate CSRF token after login
        // $this->regenerateCsrf();
        // $this->response->sendJson([
        //     'status'   => true,
        //     'message'  => 'Login successful.',
        //     'redirect' => site_url('/admin'),
        //     'csrf'     => $this->csrfToken()
        // ], 200);
    }

    /**
     * JSON-only endpoint to request forgot-password reset link.
     */
    public function handleForgot(): void
    {
        $token = (string)$this->request->input('csrf_token', '');
        if (!$this->validateCsrf($token)) {
            $this->error('Invalid or missing CSRF token.', 400);
            return;
        }

        $email = trim((string)$this->request->input('email', ''));
        if ($email === '') {
            $this->response->sendJson([
                'status'  => false,
                'message' => 'Email is required.',
                'errors'  => ['email' => 'Email is required.']
            ], 422);
            return;
        }

        // per-session rate limit
        $this->ensureSession();
        $_SESSION['yt_forgot_attempts'] = $_SESSION['yt_forgot_attempts'] ?? [];
        $now = time();
        $_SESSION['yt_forgot_attempts'] = array_filter($_SESSION['yt_forgot_attempts'], fn($t) => ($t + 900) > $now);
        if (count($_SESSION['yt_forgot_attempts']) >= 10) {
            $this->error('Too many requests. Please try again later.', 429);
            return;
        }
        $_SESSION['yt_forgot_attempts'][] = $now;

        $successMessage = 'If an account with that email exists, you will receive a password reset link.';

        // Find admin by email
        try {
            $adminRecord = $this->adminModel->query()->where('email', '=', $email)->getResult(\PDO::FETCH_ASSOC);
        } catch (\Throwable $e) {
            error_log('Forgot password lookup error: ' . $e->getMessage());
            $this->response->sendJson(['status' => true, 'message' => $successMessage, 'sent' => false], 200);
            return;
        }

        if (!$adminRecord) {
            $this->response->sendJson(['status' => true, 'message' => $successMessage, 'sent' => false], 200);
            return;
        }

        // Create token and expiry (1 hour)
        try {
            $tokenBytes = random_bytes(24);
        } catch (\Exception $e) {
            $tokenBytes = openssl_random_pseudo_bytes(24) ?: bin2hex(random_bytes(16));
        }
        $token = bin2hex($tokenBytes);
        $expires = time() + 3600;

        $metaKey = $token . '_meta';
        $metaValue = json_encode(['token' => $token, 'expires' => $expires]);

        try {
            $this->metaModel->update_meta('admin_reset', (int)$adminRecord['admin_id'], $metaKey, $metaValue);
        } catch (\Throwable $e) {
            error_log('Failed to save reset token meta: ' . $e->getMessage());
            $this->response->sendJson(['status' => true, 'message' => $successMessage, 'sent' => false], 200);
            return;
        }

        $resetLink = site_url('/admin/reset?token=' . urlencode($token));
        try {
            apply_filter('admin_send_reset_email', null, $adminRecord, $resetLink);
        } catch (\Throwable $e) {
            error_log('admin_send_reset_email filter error: ' . $e->getMessage());
        }

        $this->response->sendJson(['status' => true, 'message' => $successMessage, 'sent' => true], 200);
    }

    /**
     * Perform password reset using token (JSON).
     */
    public function handleReset(): void
    {
        $csrf = (string)$this->request->input('csrf_token', '');
        if (!$this->validateCsrf($csrf)) {
            $this->error('Invalid or missing CSRF token.', 400);
            return;
        }

        $token = (string)$this->request->input('token', '');
        $password = (string)$this->request->input('password', '');
        $confirm  = (string)$this->request->input('confirm_password', '');

        $errors = [];
        if ($token === '') $errors['token'] = 'Reset token is required.';
        if ($password === '') $errors['password'] = 'Password is required.';
        if ($confirm === '') $errors['confirm_password'] = 'Please confirm password.';
        if ($password !== '' && strlen($password) < 8) $errors['password'] = 'Password must be at least 8 characters.';
        if ($password !== $confirm) $errors['confirm_password'] = 'Passwords do not match.';

        if (!empty($errors)) {
            $this->response->sendJson(['status' => false, 'message' => 'Validation failed.', 'errors' => $errors], 422);
            return;
        }

        // Instead of fetching all admins, prefer a meta lookup (but keep compatibility)
        try {
            $allAdmins = $this->adminModel->query()->executeQuery()->fetchAll(\PDO::FETCH_ASSOC);
        } catch (\Throwable $e) {
            error_log('Reset: failed to fetch admins: ' . $e->getMessage());
            $this->error('Internal error', 500);
            return;
        }

        $found = null;
        foreach ($allAdmins as $a) {
            $metaKey = $token . '_meta';
            $meta = $this->metaModel->get_meta('admin_reset', (int)$a['admin_id'], $metaKey);
            if ($meta) {
                $payload = json_decode($meta['meta_value'], true);
                if (!is_array($payload)) continue;
                if (isset($payload['token']) && hash_equals($payload['token'], $token)) {
                    $found = ['admin' => $a, 'meta' => $meta, 'payload' => $payload];
                    break;
                }
            }
        }

        if (!$found) {
            $this->response->sendJson(['status' => false, 'message' => 'Reset token is invalid or expired.'], 400);
            return;
        }

        if (($found['payload']['expires'] ?? 0) < time()) {
            try {
                $this->metaModel->delete_meta('admin_reset', (int)$found['admin']['admin_id'], $token . '_meta');
            } catch (\Throwable $e) {}
            $this->response->sendJson(['status' => false, 'message' => 'Reset token has expired.'], 400);
            return;
        }

        $adminId = (int)$found['admin']['admin_id'];
        $newHash = password_hash($password, PASSWORD_DEFAULT);

        try {
            $this->adminModel->update(['password' => $newHash], ['admin_id' => $adminId]);
        } catch (\Throwable $e) {
            error_log('Reset: failed to update password: ' . $e->getMessage());
            $this->error('Failed to update password. Please try later.', 500);
            return;
        }

        try {
            $this->metaModel->delete_meta('admin_reset', $adminId, $token . '_meta');
        } catch (\Throwable $e) {
            // non-fatal
        }

        $this->regenerateCsrf();

        $this->response->sendJson([
            'status'   => true,
            'message'  => 'Your password has been reset. You may now sign in.',
            'redirect' => site_url('/admin/login'),
            'csrf'     => $this->csrfToken()
        ], 200);
    }

    /**
     * Logout (GET or POST)
     */
    public function logout(): void
    {
        $this->logoutAdmin();

        // prefer the PSR redirect if available
        try {
            $psr = $this->response->getCoreResponse()->redirect(site_url('/admin/login'), 302);
            $this->emitPsrResponse($psr, true);
        } catch (\Throwable $e) {
            header('Location: ' . site_url('/admin/login'), true, 302);
        }
    }

    /* -------------------------
       Route registration
       ------------------------- */
    public static function runOnce(): void
    {
        global $router;
        // GET pages
        $router->addRoute('GET', '/admin', 'Controllers\admin\YTAdmin', 'index');
        $router->addRoute('GET', '/admin/login', 'Controllers\admin\YTAdmin', 'loginPage');
        $router->addRoute('GET', '/admin/forgot', 'Controllers\admin\YTAdmin', 'forgotPassword');
        $router->addRoute('GET', '/admin/reset', 'Controllers\admin\YTAdmin', 'resetPassword');
        $router->addRoute('GET', '/admin/profile', 'Controllers\admin\YTAdmin', 'profilePage');

        // POST handlers
        $router->addRoute('POST', '/admin/login', 'Controllers\admin\YTAdmin', 'handleLogin');
        $router->addRoute('POST', '/admin/forgot', 'Controllers\admin\YTAdmin', 'handleForgot');
        $router->addRoute('POST', '/admin/reset', 'Controllers\admin\YTAdmin', 'handleReset');
        $router->addRoute('POST', '/admin/profile/save', 'Controllers\admin\YTAdmin', 'saveProfile');

        // logout
        $router->addRoute('GET', '/admin/logout', 'Controllers\admin\YTAdmin', 'logout');
        $router->addRoute('POST', '/admin/logout', 'Controllers\admin\YTAdmin', 'logout');
    }
}
