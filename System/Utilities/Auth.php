<?php
namespace System\Utilities;

use System\Database\Database;

/**
 * Auth - Simple authentication manager for Yantra
 *
 * Supports:
 *  - Session based auth (SessionStore)
 *  - Token based auth (TokenManager)
 *
 * Usage:
 *  - Auth::attempt($email, $password)  // uses DB users table (yt_users) by default
 *  - Auth::loginById($id)
 *  - Auth::logout()
 *  - Auth::user()
 *  - Auth::check()
 *  - Auth::guard('token')->userFromToken($token)
 */
class Auth
{
    protected static ?array $userCache = null;

    /**
     * Attempt login using credential fields. Returns user array on success or null.
     * Expects table yt_users with columns user_id, email, password (password hashed with password_hash()).
     *
     * @param array $credentials e.g. ['email'=>'x','password'=>'y'] or ['username'=>'x', 'password'=>'y']
     * @param bool $remember whether to regenerate session id and persist
     */
    public static function attempt(array $credentials, bool $remember = true): ?array
    {
        $user = self::findUserByCredential($credentials);
        if (!$user) return null;
        $pass = $credentials['password'] ?? '';
        // password verification — supports password_hash()
        if (!isset($user['password']) || !password_verify($pass, $user['password'])) {
            return null;
        }
        // success
        SessionStore::init();
        SessionStore::regenerate();
        SessionStore::set('auth_user_id', (int)$user['user_id']);
        if ($remember) {
            // optionally set longer cookie or remember token — simple approach here
            SessionStore::set('auth_remember', 1);
        }
        self::$userCache = $user;
        return $user;
    }

    public static function loginById(int $userId): ?array
    {
        $user = self::findUserById($userId);
        if (!$user) return null;
        SessionStore::init();
        SessionStore::regenerate();
        SessionStore::set('auth_user_id', $userId);
        self::$userCache = $user;
        return $user;
    }

    public static function logout(): void
    {
        SessionStore::init();
        SessionStore::remove('auth_user_id');
        SessionStore::remove('auth_remember');
        self::$userCache = null;
    }

    public static function id(): ?int
    {
        SessionStore::init();
        $id = SessionStore::get('auth_user_id', null);
        return $id !== null ? (int)$id : null;
    }

    /**
     * Returns user array (DB row) or null
     */
    public static function user(): ?array
    {
        if (self::$userCache !== null) return self::$userCache;
        $id = self::id();
        if ($id === null) return null;
        $u = self::findUserById($id);
        self::$userCache = $u;
        return $u;
    }

    public static function check(): bool
    {
        return self::user() !== null;
    }

    /* --------------------
     * Token guard helpers
     * -------------------- */

    /**
     * Create JWT token for user with custom claims. Returns token string.
     */
    public static function createTokenForUser(array $user, int $ttl = 3600, array $claims = []): string
    {
        $payload = array_merge($claims, [
            'sub' => (int)$user['user_id'],
            'email' => $user['email'] ?? null,
        ]);
        return TokenManager::create($payload, $ttl);
    }

    /**
     * Verify a token and return user or null.
     */
    public static function userFromToken(string $token): ?array
    {
        $payload = TokenManager::verify($token);
        if (!$payload || empty($payload['sub'])) return null;
        return self::findUserById((int)$payload['sub']);
    }

    /* --------------------
     * Helpers (DB lookups)
     * -------------------- */

    protected static function findUserById(int $id): ?array
    {
        try {
            $pdo = Database::getInstance()->getPDO();
            $stmt = $pdo->prepare("SELECT * FROM yt_users WHERE user_id = :id LIMIT 1");
            $stmt->execute([':id' => $id]);
            $row = $stmt->fetch(\PDO::FETCH_ASSOC);
            return $row ?: null;
        } catch (\Throwable $e) {
            error_log('Auth::findUserById - ' . $e->getMessage());
            return null;
        }
    }

    protected static function findUserByCredential(array $credentials): ?array
    {
        // Try email then username
        $pdo = Database::getInstance()->getPDO();
        if (!empty($credentials['email'])) {
            $stmt = $pdo->prepare("SELECT * FROM yt_users WHERE email = :email LIMIT 1");
            $stmt->execute([':email' => $credentials['email']]);
            $r = $stmt->fetch(\PDO::FETCH_ASSOC);
            if ($r) return $r;
        }
        if (!empty($credentials['username'])) {
            $stmt = $pdo->prepare("SELECT * FROM yt_users WHERE username = :username LIMIT 1");
            $stmt->execute([':username' => $credentials['username']]);
            $r = $stmt->fetch(\PDO::FETCH_ASSOC);
            if ($r) return $r;
        }
        return null;
    }
}
