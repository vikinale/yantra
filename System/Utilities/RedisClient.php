<?php
namespace System\Utilities;

/**
 * RedisClient - safe, feature-rich wrapper for ext-redis (\Redis)
 *
 * Features:
 *  - lazy + persistent connections
 *  - automatic reconnect on failure
 *  - prefixing support
 *  - JSON helpers: setJson/getJson
 *  - list, sorted-set, hash helpers commonly used by queue/cache code
 *  - script loading / evalSha caching
 *  - pub/sub helpers
 *  - atomic lock/unlock via Lua (see RedisLock)
 *  - health check
 *
 * Usage:
 *   $cfg = require __DIR__ . '/../../App/Config/redis.php';
 *   $rc = new RedisClient($cfg);
 *   $rc->set('foo','bar');
 *   $rc->get('foo'); // 'bar'
 */
class RedisClient
{
    protected \Redis $redis;
    protected array $config;
    protected bool $connected = false;
    protected string $prefix = '';
    protected bool $persistent = true;
    protected string $persistentId = '';
    protected array $scriptShaCache = [];
    protected int $defaultTimeout; // seconds for connect timeout

    /**
     * @param array $config keys:
     *   host, port, timeout, password, database, prefix, persistent (bool), persistent_id
     */
    public function __construct(array $config = [])
    {
        $defaults = [
            'host' => '127.0.0.1',
            'port' => 6379,
            'timeout' => 1.5,
            'password' => null,
            'database' => 0,
            'prefix' => '',
            'persistent' => true,
            'persistent_id' => null,
            'connect_retries' => 1,
            'connect_retry_interval' => 100,
        ];

        $this->config = $config + $defaults;
        $this->prefix = (string)$this->config['prefix'];
        $this->persistent = (bool)$this->config['persistent'];
        $this->persistentId = $this->config['persistent_id'] ?? ('yantra_' . md5($this->config['host'] . ':' . $this->config['port']));
        $this->defaultTimeout = (int)max(1, (int)ceil($this->config['timeout']));
        $this->redis = new \Redis();
    }

    /* ---------- Connection management ---------- */

    protected function ensureConnected(): void
    {
        if ($this->connected && $this->redis->isConnected()) return;

        $host = $this->config['host'];
        $port = (int)$this->config['port'];
        $timeout = (float)$this->config['timeout'];

        $retries = max(0, (int)$this->config['connect_retries']);
        $lastException = null;

        for ($attempt = 0; $attempt <= $retries; $attempt++) {
            try {
                if ($this->persistent) {
                    $ok = @$this->redis->pconnect($host, $port, $timeout, $this->persistentId);
                } else {
                    $ok = @$this->redis->connect($host, $port, $timeout);
                }
                if ($ok === false) {
                    throw new \RuntimeException("Redis connection failed (connect returned false)");
                }

                // authenticate if needed
                if (!empty($this->config['password'])) {
                    if (!@$this->redis->auth($this->config['password'])) {
                        // auth failed
                        throw new \RuntimeException("Redis auth failed");
                    }
                }

                // select DB if provided
                if (isset($this->config['database']) && $this->config['database'] !== null) {
                    $this->redis->select((int)$this->config['database']);
                }

                // optional key prefix
                if (!empty($this->prefix)) {
                    $this->redis->setOption(\Redis::OPT_PREFIX, $this->prefix);
                }

                // set consistent serializer to none (store strings) — we'll handle JSON manually
                if (defined('\Redis::OPT_SERIALIZER')) {
                    $this->redis->setOption(\Redis::OPT_SERIALIZER, \Redis::SERIALIZER_NONE);
                }

                $this->connected = true;
                return;
            } catch (\Throwable $e) {
                $lastException = $e;
                // wait retry interval
                if ($attempt < $retries) {
                    usleep((int)($this->config['connect_retry_interval']) * 1000);
                }
                $this->connected = false;
            }
        }

        // if here, failed
        throw new \RuntimeException("Unable to connect to Redis: " . ($lastException ? $lastException->getMessage() : 'unknown'));
    }

    /** Force reconnect (close then connect on next op) */
    public function reconnect(bool $forceClose = true): void
    {
        try {
            if ($forceClose && $this->redis->isConnected()) {
                @$this->redis->close();
            }
        } catch (\Throwable $_) {}
        $this->connected = false;
        $this->ensureConnected();
    }

    /** Return raw \Redis client (connected lazily) */
    public function getClient(): \Redis
    {
        $this->ensureConnected();
        return $this->redis;
    }

    public function isConnected(): bool
    {
        return $this->connected && $this->redis->isConnected();
    }

    /* ---------- Basic key operations ---------- */

    protected function key(string $k): string
    {
        // underlying prefix option may be set in redis options; but keep helper
        return $k;
    }

    public function set(string $key, $value, ?int $ttlSeconds = null): bool
    {
        $this->ensureConnected();
        if ($ttlSeconds === null) {
            return (bool)$this->redis->set($this->key($key), (string)$value);
        }
        return (bool)$this->redis->set($this->key($key), (string)$value, $ttlSeconds);
    }

    public function setEx(string $key, int $ttlSeconds, $value): bool
    {
        $this->ensureConnected();
        return (bool)$this->redis->setex($this->key($key), $ttlSeconds, (string)$value);
    }

    public function get(string $key, $default = null)
    {
        $this->ensureConnected();
        $v = $this->redis->get($this->key($key));
        return $v === false || $v === null ? $default : $v;
    }

    public function delete(string $key): bool
    {
        $this->ensureConnected();
        return (bool)$this->redis->del([$this->key($key)]) ? true : false;
    }

    public function exists(string $key): bool
    {
        $this->ensureConnected();
        return (bool)$this->redis->exists($this->key($key));
    }

    public function expire(string $key, int $seconds): bool
    {
        $this->ensureConnected();
        return (bool)$this->redis->expire($this->key($key), $seconds);
    }

    public function ttl(string $key): int
    {
        $this->ensureConnected();
        return (int)$this->redis->ttl($this->key($key));
    }

    /* ---------- JSON helpers ---------- */

    public function setJson(string $key, $value, ?int $ttlSeconds = null): bool
    {
        $payload = json_encode($value, JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES);
        if ($payload === false) throw new \RuntimeException("Failed to json_encode value for setJson");
        return $ttlSeconds === null ? $this->set($key, $payload) : $this->setEx($key, $ttlSeconds, $payload);
    }

    public function getJson(string $key, $default = null)
    {
        $v = $this->get($key, null);
        if ($v === null) return $default;
        $dec = json_decode($v, true);
        return $dec === null && json_last_error() !== JSON_ERROR_NONE ? $default : $dec;
    }

    /* ---------- atomic counters ---------- */

    public function incr(string $key, int $by = 1): int
    {
        $this->ensureConnected();
        if ($by === 1) return (int)$this->redis->incr($this->key($key));
        return (int)$this->redis->incrBy($this->key($key), $by);
    }

    public function decr(string $key, int $by = 1): int
    {
        $this->ensureConnected();
        if ($by === 1) return (int)$this->redis->decr($this->key($key));
        return (int)$this->redis->decrBy($this->key($key), $by);
    }

    /** SET NX PX helper (returns token on success, false on fail) */
    public function setIfNotExists(string $key, string $token, int $ttlMs): bool
    {
        $this->ensureConnected();
        // ext-redis uses options array in set for NX and PX
        // PHP 8+ Redis::set($key, $value, ['nx', 'px' => $ttl]) or older style 'NX',... adapt.
        try {
            // prefer array options if supported
            $opts = ['nx', 'px' => $ttlMs];
            $res = $this->redis->set($this->key($key), $token, $opts);
            return $res === true;
        } catch (\Throwable $_) {
            // fallback to string options for older ext versions
            $res = $this->redis->set($this->key($key), $token, $ttlMs, \Redis::NX);
            return $res === true;
        }
    }

    /* ---------- Lists ---------- */

    public function lpush(string $key, $value): int
    {
        $this->ensureConnected();
        return (int)$this->redis->lPush($this->key($key), (string)$value);
    }

    public function rpop(string $key)
    {
        $this->ensureConnected();
        $val = $this->redis->rPop($this->key($key));
        return $val === false ? null : $val;
    }

    public function brpop(string $key, int $timeout = 0)
    {
        $this->ensureConnected();
        $res = $this->redis->brpop([$this->key($key)], $timeout);
        if ($res === null || $res === false) return null;
        // returns [key, value]
        return $res[1] ?? null;
    }

    public function lrange(string $key, int $start = 0, int $stop = -1): array
    {
        $this->ensureConnected();
        return (array)$this->redis->lRange($this->key($key), $start, $stop);
    }

    /* ---------- Hashes ---------- */

    public function hset(string $key, string $field, $value): bool
    {
        $this->ensureConnected();
        return (bool)$this->redis->hSet($this->key($key), $field, (string)$value);
    }

    public function hget(string $key, string $field, $default = null)
    {
        $this->ensureConnected();
        $v = $this->redis->hGet($this->key($key), $field);
        return $v === false ? $default : $v;
    }

    public function hgetall(string $key): array
    {
        $this->ensureConnected();
        $r = $this->redis->hGetAll($this->key($key));
        return is_array($r) ? $r : [];
    }

    /* ---------- Sorted sets ---------- */

    public function zadd(string $key, float $score, $member): bool
    {
        $this->ensureConnected();
        return (bool)$this->redis->zAdd($this->key($key), $score, (string)$member);
    }

    public function zrangebyscore(string $key, float $min, float $max, int $limit = 0): array
    {
        $this->ensureConnected();
        if ($limit > 0) {
            return (array)$this->redis->zRangeByScore($this->key($key), $min, $max, ['limit' => [0, $limit]]);
        }
        return (array)$this->redis->zRangeByScore($this->key($key), $min, $max);
    }

    public function zrem(string $key, $member): int
    {
        $this->ensureConnected();
        return (int)$this->redis->zRem($this->key($key), (string)$member);
    }

    /* ---------- Pub/Sub helpers ---------- */

    public function publish(string $channel, $message): int
    {
        $this->ensureConnected();
        return (int)$this->redis->publish($channel, (string)$message);
    }

    /**
     * Simple subscribe helper: $cb receives ($redis, $channel, $message). Blocks the PHP thread.
     * Example:
     *   $client->subscribe(['chan1','chan2'], function($r,$ch,$msg){ echo $msg; });
     */
    public function subscribe(array $channels, callable $cb): void
    {
        $this->ensureConnected();
        $this->redis->subscribe($channels, function ($redis, $channel, $message) use ($cb) {
            $cb($redis, $channel, $message);
        });
    }

    /* ---------- Lua scripting / sha caching ---------- */

    /**
     * loadScript($script) -> sha
     */
    public function scriptLoad(string $script): string
    {
        $this->ensureConnected();
        $sha = $this->redis->script('load', $script);
        if ($sha) {
            $this->scriptShaCache[md5($script)] = $sha;
        }
        return (string)$sha;
    }

    /**
     * eval script safely: will load if needed
     * @param string $script
     * @param array $keys
     * @param array $args
     */
    public function evalScript(string $script, array $keys = [], array $args = [])
    {
        $this->ensureConnected();
        $sig = md5($script);
        $sha = $this->scriptShaCache[$sig] ?? null;
        try {
            if ($sha) {
                return $this->redis->evalSha($sha, array_merge($keys, $args), count($keys));
            }
            // no cached sha -> try eval then cache
            $res = $this->redis->eval($script, array_merge($keys, $args), count($keys));
            return $res;
        } catch (\RedisException $e) {
            // If NOSCRIPT, load and retry via evalSha
            if (stripos($e->getMessage(), 'NOSCRIPT') !== false) {
                $sha = $this->redis->script('load', $script);
                $this->scriptShaCache[$sig] = $sha;
                return $this->redis->evalSha($sha, array_merge($keys, $args), count($keys));
            }
            throw $e;
        }
    }

    /* ---------- Transactions / pipelines ---------- */

    /**
     * Run a Redis transaction (MULTI/EXEC) — callback receives Redis and should call commands.
     * Example:
     *   $rc->transaction(function($r){ $r->incr('a'); $r->set('b','1'); });
     */
    public function transaction(callable $cb): array
    {
        $this->ensureConnected();
        $this->redis->multi();
        $cb($this->redis);
        return (array)$this->redis->exec();
    }

    /**
     * Pipeline helper (non-transactional)
     */
    public function pipeline(callable $cb): array
    {
        $this->ensureConnected();
        $pipe = $this->redis->multi(\Redis::PIPELINE);
        $cb($this->redis);
        $res = $this->redis->exec();
        return is_array($res) ? $res : [];
    }

    /* ---------- utility / health ---------- */

    public function ping(): bool
    {
        try {
            $this->ensureConnected();
            $r = $this->redis->ping();
            // ext-redis returns +PONG or true depending on version
            return $r === true || stripos((string)$r, 'PONG') !== false;
        } catch (\Throwable $e) {
            $this->connected = false;
            return false;
        }
    }

    public function info(): array
    {
        $this->ensureConnected();
        $info = $this->redis->info();
        return is_array($info) ? $info : [];
    }

    /* ---------- magic pass-through (for convenience) ---------- */

    public function __call($name, $args)
    {
        $this->ensureConnected();
        if (method_exists($this->redis, $name)) {
            return $this->redis->{$name}(...$args);
        }
        throw new \BadMethodCallException("Method {$name} not found on Redis client.");
    }
}

/* ============================================================
 * RedisLock - simple, safe distributed lock using SET NX PX + Lua release
 * ============================================================ */
class RedisLock
{
    protected RedisClient $client;
    protected string $prefix;
    protected string $key;
    protected string $token;
    protected int $ttlMs;

    public function __construct(RedisClient $client, string $prefix = 'lock:')
    {
        $this->client = $client;
        $this->prefix = $prefix;
    }

    protected function fullKey(string $name): string
    {
        return $this->prefix . $name;
    }

    /**
     * Try acquire lock immediately. Returns token string on success, false on failure.
     */
    public function tryAcquire(string $name, int $ttlMs = 10000)
    {
        $token = bin2hex(random_bytes(10));
        $acquired = $this->client->setIfNotExists($this->fullKey($name), $token, $ttlMs);
        if ($acquired) {
            $this->key = $name;
            $this->token = $token;
            $this->ttlMs = $ttlMs;
            return $token;
        }
        return false;
    }

    /**
     * Acquire lock with wait and timeout (ms). Returns token or throws on timeout.
     */
    public function acquire(string $name, int $ttlMs = 10000, int $waitTimeoutMs = 30000, int $pollIntervalMs = 100): string
    {
        $deadline = microtime(true) + $waitTimeoutMs / 1000.0;
        while (microtime(true) < $deadline) {
            $token = $this->tryAcquire($name, $ttlMs);
            if ($token !== false) return $token;
            // jittered sleep
            usleep((int)(($pollIntervalMs + random_int(0, (int)max(1, $pollIntervalMs * 0.3))) * 1000));
        }
        throw new \RuntimeException("Timeout while acquiring lock {$name}");
    }

    /**
     * Release lock using Lua compare-and-del (safe)
     */
    public function release(string $name, string $token = null): bool
    {
        $token = $token ?? $this->token ?? null;
        if ($token === null) return false;

        $lua = <<<LUA
if redis.call("get", KEYS[1]) == ARGV[1] then
  return redis.call("del", KEYS[1])
else
  return 0
end
LUA;
        $res = $this->client->evalScript($lua, [$this->fullKey($name)], [$token]);
        return (int)$res === 1;
    }
}

/* ============================================================
 * RedisCacheAdapter - small wrapper suitable for Cache facade
 * ============================================================ */
class RedisCacheAdapter
{
    protected RedisClient $client;
    protected string $prefix;

    public function __construct(RedisClient $client, string $prefix = 'cache:')
    {
        $this->client = $client;
        $this->prefix = $prefix;
    }

    protected function key(string $k): string { return $this->prefix . $k; }

    public function get(string $key, $default = null)
    {
        return $this->client->getJson($this->key($key), $default);
    }

    public function put(string $key, $value, int $ttlSeconds = 3600): bool
    {
        return $this->client->setJson($this->key($key), $value, $ttlSeconds);
    }

    public function has(string $key): bool
    {
        return $this->client->exists($this->key($key));
    }

    public function delete(string $key): bool
    {
        return $this->client->delete($this->key($key));
    }

    public function clear(): bool
    {
        // WARNING: uses KEYS - not recommended for production on large DBs.
        $this->client->ensureConnected();
        $pattern = $this->prefix . '*';
        $keys = $this->client->keys($pattern);
        if (empty($keys)) return true;
        $this->client->del($keys);
        return true;
    }
}
