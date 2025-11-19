<?php
namespace System\Services\RateLimiter;

interface RateLimiterInterface
{
    /**
     * Attempt to consume tokens for identifier.
     *
     * @param string $key Unique identifier (e.g., "ip:1.2.3.4" or "user:123")
     * @param int $limit allowed tokens in window
     * @param int $windowSeconds window length in seconds
     * @param int $tokens number of tokens to consume (default 1)
     * @return array ['allowed' => bool, 'remaining' => int, 'reset' => int] reset = seconds until window resets
     */
    public function consume(string $key, int $limit, int $windowSeconds, int $tokens = 1): array;

    /**
     * Get current usage for key
     */
    public function getUsage(string $key, int $windowSeconds): array;
}
