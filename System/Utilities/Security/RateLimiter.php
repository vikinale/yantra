<?php
namespace System\Utilities\Security;

use DateTimeImmutable;
use Exception;

/* -----------------------------
 * RateLimiter - simple token-bucket / fixed window limiter
 * ----------------------------- */

/**
 * RateLimiterStoreInterface - implement via Redis / DB for distributed limits
 */
interface RateLimiterStoreInterface
{
    public function increment(string $key, int $ttlSeconds = 60): int;
    public function get(string $key): int;
    public function reset(string $key): void;
}

/**
 * Simple in-memory store (single-process)
 */
class InMemoryRateLimiterStore implements RateLimiterStoreInterface
{
    protected array $store = [];
    protected array $expires = [];

    public function increment(string $key, int $ttlSeconds = 60): int
    {
        $now = time();
        if (!isset($this->expires[$key]) || $this->expires[$key] < $now) {
            $this->store[$key] = 0;
            $this->expires[$key] = $now + $ttlSeconds;
        }
        $this->store[$key]++;
        return $this->store[$key];
    }

    public function get(string $key): int
    {
        $now = time();
        if (!isset($this->expires[$key]) || $this->expires[$key] < $now) return 0;
        return $this->store[$key] ?? 0;
    }

    public function reset(string $key): void
    {
        unset($this->store[$key], $this->expires[$key]);
    }
}

/**
 * RateLimiter - convenience wrapper
 */
class RateLimiter
{
    protected RateLimiterStoreInterface $store;
    protected int $limit;
    protected int $window;

    public function __construct(RateLimiterStoreInterface $store, int $limit = 60, int $window = 60)
    {
        $this->store = $store;
        $this->limit = $limit;
        $this->window = $window;
    }

    /**
     * Returns [allowed:boolean, remaining:int, current:int]
     */
    public function consume(string $key): array
    {
        $count = $this->store->increment($key, $this->window);
        $allowed = $count <= $this->limit;
        $remaining = $allowed ? ($this->limit - $count) : 0;
        return [$allowed, $remaining, $count];
    }

    public function remaining(string $key): int
    {
        $count = $this->store->get($key);
        return max(0, $this->limit - $count);
    }
}