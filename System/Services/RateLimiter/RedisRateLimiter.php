<?php
namespace System\Services\RateLimiter;

use Redis;
use RuntimeException;

class RedisRateLimiter implements RateLimiterInterface
{
    protected Redis $redis;
    protected string $prefix = 'rate:';

    public function __construct(array $config = [])
    {
        if (!extension_loaded('redis')) {
            throw new RuntimeException('phpredis extension required for RedisRateLimiter');
        }
        $this->redis = new Redis();
        $host = $config['host'] ?? '127.0.0.1';
        $port = $config['port'] ?? 6379;
        $timeout = $config['timeout'] ?? 2.5;
        $this->redis->connect($host, $port, $timeout);
        if (!empty($config['auth'])) {
            $this->redis->auth($config['auth']);
        }
    }

    public function consume(string $key, int $limit, int $windowSeconds, int $tokens = 1): array
    {
        // Use Redis INCR and EXPIRE for fixed window counter
        $rkey = $this->prefix . $key;
        $lua = <<<'LUA'
local k = KEYS[1]
local incr = tonumber(ARGV[1])
local limit = tonumber(ARGV[2])
local window = tonumber(ARGV[3])

local current = redis.call('INCRBY', k, incr)
if current == incr then
  redis.call('EXPIRE', k, window)
end

local ttl = redis.call('TTL', k)
if ttl < 0 then ttl = window end

local allowed = current <= limit
local remaining = math.max(0, limit - current)
return { allowed and 1 or 0, remaining, ttl, current }
LUA;

        $res = $this->redis->eval($lua, [$rkey, $tokens, $limit, $windowSeconds], 1);
        if (!is_array($res) || count($res) < 4) {
            throw new RuntimeException('Unexpected redis response from rate-limiter');
        }

        return [
            'allowed' => (bool)$res[0],
            'remaining' => (int)$res[1],
            'reset' => (int)$res[2],
            'count' => (int)$res[3]
        ];
    }

    public function getUsage(string $key, int $windowSeconds): array
    {
        $rkey = $this->prefix . $key;
        $current = (int)$this->redis->get($rkey);
        $ttl = (int)$this->redis->ttl($rkey);
        return ['count' => $current, 'reset' => max(0, $ttl)];
    }
}
