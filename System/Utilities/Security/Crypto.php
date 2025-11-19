<?php
declare(strict_types=1);

namespace System\Utilities\Security;

use DateTimeImmutable;
use Exception;
use InvalidArgumentException;
use RuntimeException;

/**
 * Crypto - low-level cryptographic helpers
 */
class Crypto
{
    /**
     * Return cryptographically secure random bytes.
     *
     * @throws RuntimeException
     */
    public static function randomBytes(int $length): string
    {
        if ($length <= 0) {
            throw new InvalidArgumentException('length must be > 0');
        }

        if (!function_exists('random_bytes')) {
            throw new RuntimeException('random_bytes() is not available on this PHP build.');
        }

        return random_bytes($length);
    }

    /**
     * Generate a URL-safe Base64 string from random bytes.
     *
     * @param int $length number of random bytes (not output chars)
     */
    public static function randomBase64Url(int $length = 32): string
    {
        return self::base64UrlEncode(self::randomBytes($length));
    }

    /**
     * Base64URL-encode binary data.
     */
    public static function base64UrlEncode(string $data): string
    {
        return rtrim(strtr(base64_encode($data), '+/', '-_'), '=');
    }

    /**
     * Base64URL-decode string. Returns null on invalid input.
     */
    public static function base64UrlDecode(string $str): ?string
    {
        // Restore padding
        $remainder = strlen($str) % 4;
        if ($remainder > 0) {
            $padlen = 4 - $remainder;
            $str .= str_repeat('=', $padlen);
        }

        $decoded = base64_decode(strtr($str, '-_', '+/'), true);
        return $decoded === false ? null : $decoded;
    }

    /**
     * HMAC-SHA256 signature, returned as base64url string.
     */
    public static function hmac(string $data, string $key): string
    {
        $raw = hash_hmac('sha256', $data, $key, true);
        return self::base64UrlEncode($raw);
    }

    /**
     * Constant-time comparison; prefers built-in hash_equals.
     */
    public static function hashEquals(string $a, string $b): bool
    {
        if (function_exists('hash_equals')) {
            return hash_equals($a, $b);
        }

        $la = strlen($a);
        $lb = strlen($b);
        if ($la !== $lb) {
            return false;
        }

        $result = 0;
        for ($i = 0; $i < $la; $i++) {
            $result |= ord($a[$i]) ^ ord($b[$i]);
        }

        return $result === 0;
    }
}