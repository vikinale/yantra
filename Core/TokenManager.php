<?php

namespace Core;

use InvalidArgumentException;

class TokenManager
{
    private string $secretKey = '22K32T2@V!K@$'; // Replace with a strong key for production

    /**
     * Base64 URL-safe encoding without padding.
     *
     * @param string $data
     * @return string
     */
    private function base64UrlEncode(string $data): string
    {
        return str_replace(['+', '/', '='], ['-', '_', ''], base64_encode($data));
    }

    /**
     * Generate a JWT Bearer Token.
     *
     * @param array $payload The data to include in the token payload.
     * @param int $expiry The expiration time (in seconds).
     * @return string The generated JWT.
     */
    public function generateBearerToken(array $payload, int $expiry = 3600): string
    {
        // Header with algorithm (HS256) and token type (JWT)
        $header = [
            'alg' => 'HS256',
            'typ' => 'JWT'
        ];

        // Set issued at and expiration times
        $issuedAt = time();
        $expiresAt = $issuedAt + $expiry;

        // Add standard claims to the payload
        $payload = array_merge($payload, [
            'iat' => $issuedAt,        // Issued at time
            'exp' => $expiresAt,       // Expiration time
        ]);

        // Encode Header and Payload
        $base64UrlHeader = $this->base64UrlEncode(json_encode($header));
        $base64UrlPayload = $this->base64UrlEncode(json_encode($payload));

        // Create Signature (HMAC-SHA256)
        $signature = hash_hmac('sha256', $base64UrlHeader . "." . $base64UrlPayload, $this->secretKey, true);
        $base64UrlSignature = $this->base64UrlEncode($signature);

        // Return the token
        return $base64UrlHeader . "." . $base64UrlPayload . "." . $base64UrlSignature;
    }

    public function generateAPIKey(array $payload, string $expiryDateTime): string
    {
        // Convert expiry date-time to a timestamp
        $expiryTimestamp = strtotime($expiryDateTime);
        if ($expiryTimestamp === false) {
            throw new InvalidArgumentException('Invalid expiry date-time format');
        }

        // Add the expiry timestamp to the payload
        $payload['exp'] = $expiryTimestamp;

        // Encode the payload into Base64 URL-safe format
        $base64UrlPayload = $this->base64UrlEncode(json_encode($payload));

        // Create a signature (HMAC-SHA256)
        $signature = hash_hmac('sha256', $base64UrlPayload, $this->secretKey, true);
        $base64UrlSignature = $this->base64UrlEncode($signature);

        // Return the generated API key (Payload + Signature)
        return $base64UrlPayload . "." . $base64UrlSignature;
    }

    /**
     * Validate a JWT Bearer Token.
     *
     * @param string $token The JWT token to validate.
     * @return array|null The decoded payload if valid, or null if invalid.
     */
    public function validateBearerToken(string $token): ?array
    {
        // Split the token into parts
        $parts = explode('.', $token);
        if (count($parts) !== 3) {
            return null; // Invalid token format
        }

        [$base64UrlHeader, $base64UrlPayload, $base64UrlSignature] = $parts;

        // Verify signature
        $signature = hash_hmac('sha256', "$base64UrlHeader.$base64UrlPayload", $this->secretKey, true);
        $expectedSignature = $this->base64UrlEncode($signature);

        if (!hash_equals($expectedSignature, $base64UrlSignature)) {
            return null; // Invalid signature
        }

        // Decode the payload and validate expiration
        $payload = json_decode(base64_decode($base64UrlPayload), true);

        if ($payload['exp'] < time()) {
            return null; // Token has expired
        }

        return $payload;
    }
    /**
     * Validate an API key.
     *
     * @param string $apiKey The API key to validate.
     * @return array|null The decoded payload if valid, or null if invalid.
     */
    public function validateAPIKey(string $apiKey): ?array
    {
        // Split the API key into its two parts: payload and signature
        $parts = explode('.', $apiKey);
        if (count($parts) !== 2) {
            return null; // Invalid format
        }

        [$base64UrlPayload, $base64UrlSignature] = $parts;

        // Verify the signature
        $signature = hash_hmac('sha256', $base64UrlPayload, $this->secretKey, true);
        $expectedSignature = $this->base64UrlEncode($signature);

        if (!hash_equals($expectedSignature, $base64UrlSignature)) {
            return null; // Invalid signature
        }

        // Decode the payload
        $payload = json_decode($this->base64UrlDecode($base64UrlPayload), true);
        if ($payload === null) {
            return null; // Invalid payload
        }

        // Check expiration time
        if (isset($payload['exp']) && $payload['exp'] < time()) {
            return null; // API key has expired
        }

        return $payload; // Valid API key, return the decoded payload
    }

    /**
     * Base64 URL-safe decoding.
     *
     * @param string $data
     * @return string
     */
    private function base64UrlDecode(string $data): string
    {
        $remainder = strlen($data) % 4;
        if ($remainder) {
            $data .= str_repeat('=', 4 - $remainder);
        }
        return base64_decode(str_replace(['-', '_'], ['+', '/'], $data));
    }
}
