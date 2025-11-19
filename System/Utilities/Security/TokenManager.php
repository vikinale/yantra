<?php
declare(strict_types=1);

namespace System\Utilities\Security;

use DateTimeImmutable;
use InvalidArgumentException;
use RuntimeException;
use System\Helpers\SecurityHelper;

/**
 * TokenManager - single canonical token service.
 *
 * Supports:
 *  - JWT-like Bearer tokens (HS256): generateBearerToken() / validateBearerToken()
 *  - API keys (payload.signature): generateAPIKey() / validateAPIKey()
 *  - Signed tokens (payload+expiry + HMAC): createSigned() / validateSigned()
 *  - Opaque random tokens persisted to a TokenStore (one-time use): createOpaque() / validateOpaque()
 *
 * Secrets may be provided as raw string or base64url-encoded string. Secret must be at least 16 bytes.
 */
class TokenManager
{
    private string $secret; // raw secret (binary/string)
    private ?TokenStoreInterface $store;

    /**
     * @param string $secret Raw secret or base64url-encoded secret. Must be >= 16 bytes (raw).
     * @param TokenStoreInterface|null $store Optional store for opaque tokens.
     */
    public function __construct(string $secret, ?TokenStoreInterface $store = null)
    {
        // accept base64url form
        $maybe = SecurityHelper::base64UrlDecode($secret);
        if ($maybe !== null && strlen($maybe) >= 16) {
            $secret = $maybe;
        }

        if (strlen($secret) < 16) {
            throw new InvalidArgumentException('Token secret should be at least 16 bytes.');
        }

        $this->secret = $secret;
        $this->store = $store;
    }

    /* ============================
     * JWT-style Bearer (HS256)
     * ============================ */

    /**
     * Generate a simple JWT-style Bearer token (HS256).
     *
     * Note: this implementation produces a compact token with header.payload.signature.
     * It does not validate "alg" or support multiple algorithms — HS256 only.
     *
     * @param array $payload Additional claims; 'iat' and 'exp' will be set/overwritten.
     * @param int $expirySeconds Token lifetime in seconds.
     * @return string compact token
     */
    public function generateBearerToken(array $payload, int $expirySeconds = 3600): string
    {
        $header = ['alg' => 'HS256', 'typ' => 'JWT'];

        $now = time();
        $payload = array_merge($payload, [
            'iat' => $now,
            'exp' => $now + $expirySeconds,
        ]);

        $h = SecurityHelper::base64UrlEncode((string) json_encode($header));
        $p = SecurityHelper::base64UrlEncode((string) json_encode($payload));

        $sigRaw = hash_hmac('sha256', $h . '.' . $p, $this->secret, true);
        $s = SecurityHelper::base64UrlEncode($sigRaw);

        return $h . '.' . $p . '.' . $s;
    }

    /**
     * Validate a JWT-style Bearer token produced by generateBearerToken().
     *
     * @param string $token
     * @return array|null Returns decoded payload array on success, null on failure.
     */
    public function validateBearerToken(string $token): ?array
    {
        $parts = explode('.', $token);
        if (count($parts) !== 3) {
            return null;
        }

        [$h, $p, $s] = $parts;
        if ($h === '' || $p === '' || $s === '') {
            return null;
        }

        $expectedRaw = hash_hmac('sha256', $h . '.' . $p, $this->secret, true);
        $expected = SecurityHelper::base64UrlEncode($expectedRaw);

        if (!SecurityHelper::constantTimeEquals($expected, $s)) {
            return null;
        }

        $payloadJson = SecurityHelper::base64UrlDecode($p);
        if ($payloadJson === null) {
            return null;
        }

        $payload = json_decode($payloadJson, true);
        if (!is_array($payload)) {
            return null;
        }

        $exp = isset($payload['exp']) ? (int) $payload['exp'] : 0;
        if ($exp !== 0 && $exp < time()) {
            return null;
        }

        return $payload;
    }

    /* ============================
     * API Key (payload.signature)
     * ============================ */

    /**
     * Generate an API key as: base64url(json(payload)).base64url(hmac(payload))
     *
     * @param array $payload Data to embed (optionally include expiry as 'exp' timestamp)
     * @return string
     */
    public function generateAPIKey(array $payload): string
    {
        $p = SecurityHelper::base64UrlEncode((string) json_encode($payload));
        $sigRaw = hash_hmac('sha256', $p, $this->secret, true);
        $s = SecurityHelper::base64UrlEncode($sigRaw);
        return $p . '.' . $s;
    }

    /**
     * Validate API key produced by generateAPIKey().
     *
     * @param string $apiKey
     * @return array|null payload on success, null on failure
     */
    public function validateAPIKey(string $apiKey): ?array
    {
        $parts = explode('.', $apiKey);
        if (count($parts) !== 2) {
            return null;
        }

        [$p, $s] = $parts;
        if ($p === '' || $s === '') {
            return null;
        }

        $expectedRaw = hash_hmac('sha256', $p, $this->secret, true);
        $expected = SecurityHelper::base64UrlEncode($expectedRaw);

        if (!SecurityHelper::constantTimeEquals($expected, $s)) {
            return null;
        }

        $json = SecurityHelper::base64UrlDecode($p);
        if ($json === null) {
            return null;
        }

        $payload = json_decode($json, true);
        if (!is_array($payload)) {
            return null;
        }

        if (isset($payload['exp']) && (int) $payload['exp'] < time()) {
            return null;
        }

        return $payload;
    }

    /* ============================
     * Signed token (payload + expiry + HMAC) — compact
     * ============================ */

    /**
     * Create a signed token. Compact format: base64url(json(['d'=>data,'e'=>expiry])) . '.' . base64url(hmac(payload))
     *
     * @param array $data Arbitrary data to include.
     * @param int $ttlSeconds Seconds to live.
     * @return string
     */
    public function createSigned(array $data, int $ttlSeconds = 3600): string
    {
        $exp = (new DateTimeImmutable())->getTimestamp() + $ttlSeconds;
        $payload = ['d' => $data, 'e' => $exp];

        $json = json_encode($payload);
        if ($json === false) {
            throw new RuntimeException('Failed to JSON-encode token payload.');
        }

        $b = SecurityHelper::base64UrlEncode($json);
        $sig = SecurityHelper::hmac($b, $this->secret);

        return $b . '.' . $sig;
    }

    /**
     * Validate a signed token created by createSigned().
     *
     * @param string $token
     * @return array|null Returns the 'd' payload array on success, or null on failure.
     */
    public function validateSigned(string $token): ?array
    {
        if (strpos($token, '.') === false) {
            return null;
        }

        [$b, $sig] = explode('.', $token, 2) + [1 => ''];
        if ($b === '' || $sig === '') {
            return null;
        }

        $expected = SecurityHelper::hmac($b, $this->secret);
        if (!SecurityHelper::constantTimeEquals($expected, $sig)) {
            return null;
        }

        $json = SecurityHelper::base64UrlDecode($b);
        if ($json === null) {
            return null;
        }

        $payload = json_decode($json, true);
        if (!is_array($payload) || !isset($payload['e'])) {
            return null;
        }

        if ((int) $payload['e'] < (new DateTimeImmutable())->getTimestamp()) {
            return null;
        }

        return $payload['d'] ?? null;
    }

    /* ============================
     * Opaque tokens (random + store)
     * ============================ */

    /**
     * Create an opaque random token. If a TokenStore is configured, persist it as keyPrefix:token => '1' with TTL.
     *
     * @param string $keyPrefix
     * @param int $ttlSeconds
     * @return string
     */
    public function createOpaque(string $keyPrefix = 'tok', int $ttlSeconds = 3600): string
    {
        $raw = SecurityHelper::randomBytes(32);
        $token = SecurityHelper::base64UrlEncode($raw);

        if ($this->store !== null) {
            $this->store->put($keyPrefix . ':' . $token, '1', $ttlSeconds);
        }

        return $token;
    }

    /**
     * Validate opaque token by checking the TokenStore. If present, token is deleted (one-time).
     *
     * @param string $token
     * @param string $keyPrefix
     * @return bool
     */
    public function validateOpaque(string $token, string $keyPrefix = 'tok'): bool
    {
        if ($this->store === null) {
            return false;
        }

        $key = $keyPrefix . ':' . $token;
        $val = $this->store->get($key);
        if ($val !== null) {
            $this->store->delete($key);
            return true;
        }

        return false;
    }

    /* ============================================================
    *  RS256 JWT (asymmetric: private_key → sign, public_key → verify)
    * ============================================================ */

    /**
     * Generate JWT using RS256 (private key required).
     *
     * @param array  $payload
     * @param string $privateKey PEM formatted private key
     * @param int    $expirySeconds
     * @return string
     */
    public function generateRS256Token(array $payload, string $privateKey, int $expirySeconds = 3600): string
    {
        $header = ['alg' => 'RS256', 'typ' => 'JWT'];

        $now = time();
        $payload = array_merge($payload, [
            'iat' => $now,
            'exp' => $now + $expirySeconds,
        ]);

        $h = SecurityHelper::base64UrlEncode(json_encode($header));
        $p = SecurityHelper::base64UrlEncode(json_encode($payload));

        $data = $h . '.' . $p;

        $signature = '';
        $success = openssl_sign($data, $signature, $privateKey, OPENSSL_ALGO_SHA256);

        if (!$success) {
            throw new \RuntimeException("RS256 signing failed. Check private key format.");
        }

        $s = SecurityHelper::base64UrlEncode($signature);

        return $h . '.' . $p . '.' . $s;
    }


    /**
     * Validate an RS256 JWT token using a public key.
     *
     * @param string $token
     * @param string $publicKey PEM formatted public key
     * @return array|null
     */
    public function validateRS256Token(string $token, string $publicKey): ?array
    {
        $parts = explode('.', $token);
        if (count($parts) !== 3) {
            return null;
        }

        [$h, $p, $s] = $parts;

        $headerJson = SecurityHelper::base64UrlDecode($h);
        if ($headerJson === null) return null;

        $header = json_decode($headerJson, true);
        if (($header['alg'] ?? '') !== 'RS256') {
            return null;  // wrong algorithm
        }

        $signature = SecurityHelper::base64UrlDecode($s);
        if ($signature === null) return null;

        $data = $h . '.' . $p;

        $verify = openssl_verify($data, $signature, $publicKey, OPENSSL_ALGO_SHA256);
        if ($verify !== 1) {
            return null; // signature mismatch
        }

        $payloadJson = SecurityHelper::base64UrlDecode($p);
        if ($payloadJson === null) return null;

        $payload = json_decode($payloadJson, true);
        if (!is_array($payload)) return null;

        if (isset($payload['exp']) && $payload['exp'] < time()) {
            return null;
        }

        return $payload;
    }

    /* ============================
     * Helpers / store management
     * ============================ */

    public function setStore(?TokenStoreInterface $store): void
    {
        $this->store = $store;
    }

    public function getStore(): ?TokenStoreInterface
    {
        return $this->store;
    }
}

/**
 * TokenStoreInterface - minimal contract for persistent opaque token storage.
 */
interface TokenStoreInterface
{
    public function put(string $key, string $value, int $ttlSeconds = 0): void;
    public function get(string $key): ?string;
    public function delete(string $key): void;
}
