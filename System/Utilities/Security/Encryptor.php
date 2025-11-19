<?php
declare(strict_types=1);

namespace System\Utilities\Security;

use System\Helpers\SecurityHelper;
use InvalidArgumentException;
use RuntimeException;

/**
 * Encryptor - AES-256-GCM wrapper (delegates to System\Helpers\SecurityHelper).
 * Payload format: base64url(iv || tag || ciphertext)
 */
class Encryptor
{
    private const IV_LENGTH  = 12;
    private const TAG_LENGTH = 16;

    private string $key; // raw 32-byte key

    /**
     * Accept raw 32-byte key or base64url-encoded 32 bytes.
     *
     * @param string $keyRawOrBase64Url
     */
    public function __construct(string $keyRawOrBase64Url)
    {
        $key = $keyRawOrBase64Url;

        // try decode if looks like base64url
        $maybe = SecurityHelper::base64UrlDecode($keyRawOrBase64Url);
        if ($maybe !== null && strlen($maybe) === 32) {
            $key = $maybe;
        }

        if (strlen($key) !== 32) {
            throw new InvalidArgumentException('Encryptor key must be 32 bytes (raw) or base64url-encoded 32 bytes');
        }

        if (!function_exists('openssl_encrypt') || !in_array('aes-256-gcm', openssl_get_cipher_methods(), true)) {
            throw new RuntimeException('AES-256-GCM not available');
        }

        $this->key = $key;
    }

    /**
     * Encrypt plaintext and return base64url(iv|tag|ciphertext).
     */
    public function encrypt(string $plaintext, string $aad = ''): string
    {
        $iv = SecurityHelper::randomBytes(self::IV_LENGTH);
        $tag = '';
        $ciphertext = openssl_encrypt(
            $plaintext,
            'aes-256-gcm',
            $this->key,
            OPENSSL_RAW_DATA,
            $iv,
            $tag,
            $aad,
            self::TAG_LENGTH
        );

        if ($ciphertext === false || $tag === null) {
            throw new RuntimeException('Encryption failed');
        }

        return SecurityHelper::base64UrlEncode($iv . $tag . $ciphertext);
    }

    /**
     * Decrypt base64url payload -> plaintext or null on failure.
     */
    public function decrypt(string $payload, string $aad = ''): ?string
    {
        $raw = SecurityHelper::base64UrlDecode($payload);
        if ($raw === null) {
            return null;
        }

        if (strlen($raw) < (self::IV_LENGTH + self::TAG_LENGTH)) {
            return null;
        }

        $iv = substr($raw, 0, self::IV_LENGTH);
        $tag = substr($raw, self::IV_LENGTH, self::TAG_LENGTH);
        $ciphertext = substr($raw, self::IV_LENGTH + self::TAG_LENGTH);

        $plaintext = openssl_decrypt(
            $ciphertext,
            'aes-256-gcm',
            $this->key,
            OPENSSL_RAW_DATA,
            $iv,
            $tag,
            $aad
        );

        return $plaintext === false ? null : $plaintext;
    }
}
