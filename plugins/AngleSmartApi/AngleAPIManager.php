<?php
// Plugins/AngleSmartApi/AngleAPIManager.php
namespace Plugins\AngleSmartApi;

use OTPHP\TOTP;

class AngleAPIManager
{
    private string $clientCode;
    private string $password;
    private ?string $totpSecret;
    private string $apiKey;
    private string $baseUrl;
    private ?string $jwtToken = null;
    private ?string $refreshToken = null;
    private ?string $feedToken = null;
    private string $tokenCacheFile;
    private string $settingsFile;
    private array $settings = [];

    /**
     * $config keys (all optional):
     *  - settings_file: path to settings.json
     *  - token_cache_file
     *  - base_url
     *  - (any other single-setting override)
     */
    public function __construct(array $config = [])
    {
        // settings.json path (can be overridden)
        $this->settingsFile = $config['settings_file'] ?? __DIR__ . '/../settings.json';

        // load persistent settings (if file exists)
        $this->loadSettingsFile();

        // values precedence: $config -> settings.json -> env -> sensible default
        $this->clientCode = $config['client_code']
            ?? ($this->settings['client_code'] ?? getenv('SMARTAPI_CLIENT_CODE') ?? '');
        $this->password = $config['password']
            ?? ($this->settings['password'] ?? getenv('SMARTAPI_PASSWORD') ?? '');
        $this->totpSecret = $config['totp_secret']
            ?? ($this->settings['totp_secret'] ?? getenv('SMARTAPI_TOTP_SECRET') ?? null);
        $this->apiKey = $config['api_key']
            ?? ($this->settings['api_key'] ?? getenv('SMARTAPI_API_KEY') ?? '');
        $this->baseUrl = rtrim($config['base_url'] ?? ($this->settings['base_url'] ?? 'https://apiconnect.angelone.in'), '/');

        $this->tokenCacheFile = $config['token_cache_file']
            ?? ($this->settings['token_cache_file'] ?? sys_get_temp_dir() . '/angel_tokens.json');

        // load cached tokens (if any)
        $this->loadTokenCache();
    }

    /**
     * Login and generate JWT & refresh token
     */
    public function login(): bool
    {
        $url = $this->baseUrl . '/rest/auth/angelbroking/user/v1/loginByPassword';

        $payload = [
            'clientcode' => $this->clientCode,
            'password'   => $this->password,
            'totp'       => $this->generateTotp(), // auto-generated from secret, empty if not configured
            'state'      => $this->settings['state'] ?? 'live',
        ];
        $headers = $this->defaultHeaders();

        $response = $this->postJson($url, $payload, $headers);
        
        if (empty($response) || empty($response['status']) || empty($response['data'])) {
            return false;
        }

        $data = $response['data'];
        var_dump($data);
        $this->jwtToken     = $data['jwtToken']     ?? null;
        $this->refreshToken = $data['refreshToken'] ?? null;
        $this->feedToken    = $data['feedToken']    ?? null;

        $this->saveTokenCache();
        return (bool)$this->jwtToken;
    }

    /**
     * Generate new token using refreshToken
     */
    public function generateToken(): bool
    {
        if (empty($this->refreshToken)) {
            return false;
        }

        $url = $this->baseUrl . '/rest/auth/angelbroking/jwt/v1/generateTokens';
        $payload = ['refreshToken' => $this->refreshToken];

        $headers = array_merge($this->defaultHeaders(), [
            'Authorization: Bearer ' . ($this->jwtToken ?? ''),
        ]);

        $response = $this->postJson($url, $payload, $headers);
        if (empty($response) || empty($response['status']) || empty($response['data'])) {
            return false;
        }

        $d = $response['data'];
        $this->jwtToken     = $d['jwtToken']     ?? $this->jwtToken;
        $this->refreshToken = $d['refreshToken'] ?? $this->refreshToken;
        $this->feedToken    = $d['feedToken']    ?? $this->feedToken;

        $this->saveTokenCache();
        return true;
    }

    /**
     * Check if current JWT token is expired by decoding its payload.
     * Returns:
     *  - true  => token is expired or invalid
     *  - false => token is still valid
     */
    public function isTokenExpired(): bool
    {
        if (empty($this->jwtToken)) {
            return true; // no token at all
        }

        $parts = explode('.', $this->jwtToken);
        if (count($parts) !== 3) {
            return true; // not a valid JWT structure
        }

        $payload = json_decode(base64_decode(strtr($parts[1], '-_', '+/')), true);
        if (!is_array($payload) || empty($payload['exp'])) {
            return true; // malformed or missing exp
        }

        $expiry = (int)$payload['exp'];
        $now = time();

        // small safety margin (e.g., 30 seconds)
        return ($expiry <= $now + 30);
    }

    public function loginStatus(): array
    {
        // No token at all
        if (empty($this->jwtToken)) {
            $ok = $this->login();
            return [
                'logged_in' => $ok,
                'action'    => $ok ? 'relogged' : 'failed',
                'message'   => $ok ? 'New login successful.' : 'No token and login failed.',
            ];
        }

        // Check locally if token expired
        if ($this->isTokenExpired()) {
            // Try refresh first
            if ($this->generateToken()) {
                return [
                    'logged_in' => true,
                    'action'    => 'refreshed',
                    'message'   => 'Token expired but successfully refreshed.',
                ];
            }

            // Refresh failed — try full login
            if ($this->login()) {
                return [
                    'logged_in' => true,
                    'action'    => 'relogged',
                    'message'   => 'Token expired, refresh failed, re-login succeeded.',
                ];
            }

            return [
                'logged_in' => false,
                'action'    => 'failed',
                'message'   => 'Token expired and both refresh/login failed.',
            ];
        }

        // Token valid
        return [
            'logged_in' => true,
            'action'    => 'valid',
            'message'   => 'Token is still valid (based on local expiry check).',
        ];
    }

    /**
     * Get user profile
     */
    public function getProfile(): ?array
    {
        if (!$this->ensureAuthenticated()) return null;

        $url = $this->baseUrl . '/rest/secure/angelbroking/user/v1/getProfile';
        $headers = array_merge($this->defaultHeaders(), [
            'Authorization: Bearer ' . $this->jwtToken,
        ]);

        return $this->getJson($url, $headers);
    }

    /**
     * Get RMS (funds and margin)
     */
    public function getRMS(): ?array
    {
        if (!$this->ensureAuthenticated()) return null;

        $url = $this->baseUrl . '/rest/secure/angelbroking/user/v1/getRMS';
        $headers = array_merge($this->defaultHeaders(), [
            'Authorization: Bearer ' . $this->jwtToken,
        ]);

        return $this->getJson($url, $headers);
    }

    /**
     * Logout
     */
    public function logout(): bool
    {
        if (empty($this->jwtToken)) return false;

        $url = $this->baseUrl . '/rest/secure/angelbroking/user/v1/logout';
        $payload = ['clientcode' => $this->clientCode];
        $headers = array_merge($this->defaultHeaders(), [
            'Authorization: Bearer ' . $this->jwtToken,
        ]);

        $response = $this->postJson($url, $payload, $headers);
        if (!empty($response) && !empty($response['status']) && $response['status'] === true) {
            $this->jwtToken = $this->refreshToken = $this->feedToken = null;
            $this->saveTokenCache();
            return true;
        }
        return false;
    }

    /* ---------- Helpers ---------- */

    private function ensureAuthenticated(): bool
    {
        if (!empty($this->jwtToken)) return true;
        if (!empty($this->refreshToken) && $this->generateToken()) return true;
        return $this->login();
    }

    /**
     * Create default request headers. IP/MAC can be overridden from settings.json
     */
    private function defaultHeaders(): array
    {
        $clientLocalIP  = $this->settings['client_local_ip'] ?? ($_SERVER['SERVER_ADDR'] ?? '127.0.0.1');
        $clientPublicIP = $this->settings['client_public_ip'] ?? ($_SERVER['REMOTE_ADDR'] ?? $clientLocalIP);
        $macAddress     = $this->settings['mac_address'] ?? '00:00:00:00:00:00';

        return [
            'Content-Type: application/json',
            'Accept: application/json',
            'X-UserType: USER',
            'X-SourceID: WEB',
            'X-ClientLocalIP: ' . $clientLocalIP,
            'X-ClientPublicIP: ' . $clientPublicIP,
            'X-MACAddress: ' . $macAddress,
            'X-PrivateKey: ' . $this->apiKey,
        ];
    }

    /**
     * Generate TOTP code using OTPHP library. Returns empty string if no secret available.
     */
    private function generateTotp(): string
    {
        $secret = $this->totpSecret;
        if (empty($secret)) {
            return '';
        }

        try {
            $totp = TOTP::create($secret); // uses default 30s step and 6 digits
            return $totp->now();
        } catch (\Throwable $e) {
            // on failure, return empty so caller can decide what to do
            return '';
        }
    }

    /* ---------- HTTP helpers ---------- */

    private function postJson(string $url, array $payload, array $headers): ?array
    {
        $ch = curl_init($url);
        $body = json_encode($payload);
        $hdrs = $this->formatHeaders($headers);

        curl_setopt_array($ch, [
            CURLOPT_RETURNTRANSFER => true,
            CURLOPT_POST => true,
            CURLOPT_POSTFIELDS => $body,
            CURLOPT_HTTPHEADER => $hdrs,
            CURLOPT_TIMEOUT => 20,
        ]);

        $response = curl_exec($ch);
        $error = curl_error($ch);
        $code  = curl_getinfo($ch, CURLINFO_HTTP_CODE);
        curl_close($ch);

        if ($error || !$response) {
            return null;
        }

        $json = json_decode($response, true);
        if (json_last_error() !== JSON_ERROR_NONE) {
            return ['raw' => $response, 'http_code' => $code];
        }

        $json['http_code'] = $code;
        return $json;
    }

    private function getJson(string $url, array $headers): ?array
    {
        $ch = curl_init($url);
        $hdrs = $this->formatHeaders($headers);

        curl_setopt_array($ch, [
            CURLOPT_RETURNTRANSFER => true,
            CURLOPT_HTTPHEADER => $hdrs,
            CURLOPT_TIMEOUT => 20,
        ]);

        $response = curl_exec($ch);
        $error = curl_error($ch);
        $code  = curl_getinfo($ch, CURLINFO_HTTP_CODE);
        curl_close($ch);

        if ($error || !$response) {
            return null;
        }

        $json = json_decode($response, true);
        if (json_last_error() !== JSON_ERROR_NONE) {
            return ['raw' => $response, 'http_code' => $code];
        }

        $json['http_code'] = $code;
        return $json;
    }

    private function formatHeaders(array $headers): array
    {
        // If headers are associative, convert to "Key: Value" format
        $out = [];
        foreach ($headers as $k => $v) {
            if (is_int($k)) {
                $out[] = $v;
            } else {
                $out[] = $k . ': ' . $v;
            }
        }
        return $out;
    }

    /* ---------- Token Cache ---------- */

    private function saveTokenCache(): void
    {
        $data = [
            'jwtToken'     => $this->jwtToken,
            'refreshToken' => $this->refreshToken,
            'feedToken'    => $this->feedToken,
            'saved_at'     => time(),
        ];
        @file_put_contents($this->tokenCacheFile, json_encode($data, JSON_PRETTY_PRINT));
    }

    private function loadTokenCache(): void
    {
        if (!file_exists($this->tokenCacheFile)) return;
        $raw = @file_get_contents($this->tokenCacheFile);
        $j = json_decode($raw, true);
        if (!is_array($j)) return;
        $this->jwtToken     = $j['jwtToken'] ?? null;
        $this->refreshToken = $j['refreshToken'] ?? null;
        $this->feedToken    = $j['feedToken'] ?? null;
    }

    /* ---------- settings.json loader ---------- */

    private function loadSettingsFile(): void
    {
        if (!file_exists($this->settingsFile)) {
            $this->settings = [];
            return;
        }

        $raw = @file_get_contents($this->settingsFile);
        $j = json_decode($raw, true);
        $this->settings = is_array($j) ? $j : [];
    }

    /**
     * Optional: helper to expose feed token (useful for websocket / feed)
     */
    public function getFeedToken(): ?string
    {
        return $this->feedToken;
    }
}
