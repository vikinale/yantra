<?php
namespace System\Utilities;

use System\Database\Database;
use PDO;
use Throwable;

/**
 * Queue utility for Yantra
 *
 * Adapters:
 *  - SyncQueueAdapter (default)
 *  - RedisQueueAdapter (requires ext-redis)
 *  - DatabaseQueueAdapter (uses PDO)
 *
 * API:
 *   Queue::init($adapter);
 *   Queue::push($job, $data = [], $queue = 'default');
 *   Queue::later(30, $job, $data, 'emails');
 *   Queue::work('default', ['sleep'=>3,'maxTries'=>3]);
 *
 * Job payload format stored by adapters:
 *  [
 *    'job' => <callable|string|class>,
 *    'data' => array,
 *    'attempts' => 0,
 *    'max_tries' => 3,
 *    'available_at' => timestamp (int),
 *    'created_at' => timestamp
 *  ]
 */

/* -------------------------
 * Job interface
 * ------------------------- */
interface QueueJobInterface
{
    /**
     * Handle method called by worker.
     * @param array $data - the payload data
     */
    public function handle(array $data): void;
}

/* -------------------------
 * Queue facade
 * ------------------------- */
class Queue
{
    protected static ?QueueAdapterInterface $adapter = null;

    /**
     * Init with adapter (QueueAdapterInterface)
     */
    public static function init(?QueueAdapterInterface $adapter = null): void
    {
        if ($adapter !== null) {
            self::$adapter = $adapter;
        } elseif (self::$adapter === null) {
            // default adapter: Sync (falls back to immediate execution)
            self::$adapter = new SyncQueueAdapter();
        }
    }

    public static function adapter(): QueueAdapterInterface
    {
        if (self::$adapter === null) self::init();
        return self::$adapter;
    }

    public static function push(mixed $job, array $data = [], string $queue = 'default', int $maxTries = 3): bool
    {
        $payload = [
            'job' => $job,
            'data' => $data,
            'attempts' => 0,
            'max_tries' => $maxTries,
            'available_at' => time(),
            'created_at' => time(),
        ];
        return self::adapter()->push($queue, $payload);
    }

    public static function later(int $delaySeconds, mixed $job, array $data = [], string $queue = 'default', int $maxTries = 3): bool
    {
        $payload = [
            'job' => $job,
            'data' => $data,
            'attempts' => 0,
            'max_tries' => $maxTries,
            'available_at' => time() + max(0, $delaySeconds),
            'created_at' => time(),
        ];
        return self::adapter()->push($queue, $payload);
    }

    public static function dispatch(mixed $job, array $data = [], string $queue = 'default', int $maxTries = 3): bool
    {
        return self::push($job, $data, $queue, $maxTries);
    }

    public static function size(string $queue = 'default'): int
    {
        return self::adapter()->size($queue);
    }

    /**
     * Work loop. Will keep running until $options['stopWhenEmpty'] true and queue empty.
     * Options:
     *  - sleep: seconds to sleep when no job found (default 2)
     *  - maxTries: default retry attempts when payload doesn't contain max_tries (default 3)
     *  - stopWhenEmpty: bool - stop when no jobs left (default false)
     *  - maxJobs: int - stop after processing this many jobs (optional)
     *  - memoryLimitMB: int - stop if memory usage exceeds (opt)
     */
    public static function work(string $queue = 'default', array $options = []): void
    {
        $opts = array_merge(['sleep' => 2, 'maxTries' => 3, 'stopWhenEmpty' => false, 'maxJobs' => null, 'memoryLimitMB' => null], $options);
        $processed = 0;

        while (true) {
            if ($opts['memoryLimitMB'] !== null && memory_get_usage(true) / 1024 / 1024 > (int)$opts['memoryLimitMB']) {
                Log::warning("Queue worker stopped: memory limit exceeded");
                break;
            }

            $jobPayload = self::adapter()->pop($queue);
            if ($jobPayload === null) {
                if ($opts['stopWhenEmpty']) break;
                sleep((int)$opts['sleep']);
                continue;
            }

            $processed++;
            try {
                self::processPayload($jobPayload);
            } catch (Throwable $e) {
                // processing used adapter's failure handling; log
                Log::error("Queue worker exception: " . $e->getMessage(), ['exception' => $e]);
            }

            if ($opts['maxJobs'] !== null && $processed >= (int)$opts['maxJobs']) break;
        }
    }

    protected static function processPayload(array $payload): void
    {
        // check availability
        $now = time();
        if (isset($payload['available_at']) && $payload['available_at'] > $now) {
            // re-enqueue with the same payload and slight backoff
            self::adapter()->release($payload, max(1, $payload['available_at'] - $now));
            return;
        }

        $attempts = (int)($payload['attempts'] ?? 0);
        $maxTries = (int)($payload['max_tries'] ?? 3);

        try {
            self::runJob($payload['job'], $payload['data']);
            // success -> ack (adapter may already remove from queue)
            self::adapter()->ack($payload);

            // If part of a batch, mark success.
            if (!empty($payload['batch_id'])) {
                \System\Utilities\Batch::markJobSuccess((string)$payload['batch_id']);
            }

            // If payload contained a chain (array of subsequent jobs), enqueue the next one.
            if (!empty($payload['chain']) && is_array($payload['chain'])) {
                $chain = $payload['chain'];
                // chain elements can be job or ['job'=>..., 'data'=>..., 'delay'=>0, 'max_tries'=>3]
                $next = array_shift($chain);
                // if next exists, prepare payload
                if ($next !== null) {
                    $nextJob = null;
                    $nextData = [];
                    $nextDelay = 0;
                    $nextMaxTries = $payload['max_tries'] ?? 3;

                    if (is_array($next) && isset($next['job'])) {
                        $nextJob = $next['job'];
                        $nextData = $next['data'] ?? [];
                        $nextDelay = $next['delay'] ?? 0;
                        $nextMaxTries = $next['max_tries'] ?? $nextMaxTries;
                    } else {
                        $nextJob = $next;
                    }

                    // preserve batch_id if present
                    $newPayload = [
                        'job' => $nextJob,
                        'data' => $nextData,
                        'attempts' => 0,
                        'max_tries' => $nextMaxTries,
                        'available_at' => time() + max(0, (int)$nextDelay),
                        'created_at' => time(),
                    ];
                    if (!empty($payload['batch_id'])) {
                        $newPayload['batch_id'] = $payload['batch_id'];
                    }
                    // if there are more chain items left, attach remaining chain
                    if (!empty($chain)) {
                        $newPayload['chain'] = $chain;
                    }

                    // push next job onto same queue
                    $queueName = $payload['queue'] ?? 'default';
                    self::adapter()->push($queueName, $newPayload);
                }
            }

        } 
        catch (Throwable $e) {
            $attempts++;
            Log::error("Job failed on attempt {$attempts}: " . $e->getMessage(), ['attempts' => $attempts, 'max_tries' => $maxTries, 'exception' => $e]);

            // If part of a batch, mark failed with payload + error
            if (!empty($payload['batch_id'])) {
                \System\Utilities\Batch::markJobFailed((string)$payload['batch_id'], $payload, $e->getMessage());
            }

            if ($attempts >= $maxTries) {
                self::adapter()->fail($payload, $e);
            } else {
                $payload['attempts'] = $attempts;
                $backoff = (int) (pow(2, $attempts) * 5); // existing backoff (worker) or use updated algorithm
                $payload['available_at'] = time() + $backoff;
                self::adapter()->release($payload, $backoff);
            }
        }
    }

    protected static function runJob(mixed $job, array $data): void
    {
        // job can be:
        //  - callable
        //  - "ClassName@method" string
        //  - string class name that implements QueueJobInterface
        //  - instance of QueueJobInterface
        if (is_callable($job)) {
            call_user_func($job, $data);
            return;
        }

        if (is_string($job) && strpos($job, '@') !== false) {
            [$class, $method] = explode('@', $job, 2);
            if (!class_exists($class)) throw new \RuntimeException("Job class {$class} not found");
            $instance = new $class();
            if (!method_exists($instance, $method)) throw new \RuntimeException("Method {$method} not found on job class {$class}");
            call_user_func([$instance, $method], $data);
            return;
        }

        if (is_string($job) && class_exists($job)) {
            $instance = new $job();
            if ($instance instanceof QueueJobInterface) {
                $instance->handle($data);
                return;
            }
            // if not implementing interface but has handle
            if (method_exists($instance, 'handle')) {
                $instance->handle($data);
                return;
            }
            throw new \RuntimeException("Job class {$job} has no handle() method");
        }

        if ($job instanceof QueueJobInterface) {
            $job->handle($data);
            return;
        }

        throw new \RuntimeException("Unsupported job type");
    }

    /**
     * Build and dispatch a chain of jobs.
     * $jobs array format: each element = job|string|callable OR ['job'=>..., 'data'=>..., 'delay'=>0, 'max_tries'=>3]
     * Returns true on push of first job (or false).
     */
    public static function chain(array $jobs, array $commonData = [], string $queue = 'default', int $maxTries = 3): bool
    {
        $jobs = array_values($jobs);
        if (empty($jobs)) return false;

        // first job is shifted and will be pushed with remaining chain attached
        $first = array_shift($jobs);
        $firstJob = null;
        $firstData = $commonData;
        $firstDelay = 0;
        $firstMax = $maxTries;

        if (is_array($first) && isset($first['job'])) {
            $firstJob = $first['job'];
            $firstData = array_merge($firstData, $first['data'] ?? []);
            $firstDelay = $first['delay'] ?? 0;
            $firstMax = $first['max_tries'] ?? $firstMax;
        } else {
            $firstJob = $first;
        }

        $payload = [
            'job' => $firstJob,
            'data' => $firstData,
            'attempts' => 0,
            'max_tries' => $firstMax,
            'available_at' => time() + max(0, (int)$firstDelay),
            'created_at' => time(),
        ];

        if (!empty($jobs)) {
            $payload['chain'] = $jobs;
        }

        return self::adapter()->push($queue, $payload);
    }

    /**
     * Convenience: dispatch and return immediately
     */
    public static function dispatchNow(mixed $job, array $data = []): void
    {
        self::runJob($job, $data);
    }
}

/* -------------------------
 * Adapter interface
 * ------------------------- */
interface QueueAdapterInterface
{
    /**
     * Push raw payload onto named queue
     * @param string $queue
     * @param array $payload
     * @return bool
     */
    public function push(string $queue, array $payload): bool;

    /**
     * Pop a job payload from queue (respecting available_at). Return payload array or null if none.
     */
    public function pop(string $queue): ?array;

    /**
     * Release payload back to queue (after failure or delayed)
     * @param array $payload
     * @param int $delaySeconds
     */
    public function release(array $payload, int $delaySeconds = 0): void;

    /**
     * Mark payload as acknowledged (successful)
     */
    public function ack(array $payload): void;

    /**
     * Move to failed/ dead-letter (payload + exception)
     */
    public function fail(array $payload, Throwable $exception = null): void;

    /**
     * Return approximate queue size
     */
    public function size(string $queue): int;
}

/* -------------------------
 * Sync adapter (executes job immediately)
 * ------------------------- */
class SyncQueueAdapter implements QueueAdapterInterface
{
    public function push(string $queue, array $payload): bool
    {
        // run immediately in same process
        try {
            Queue::dispatchNow($payload['job'], $payload['data'] ?? []);
            return true;
        } catch (Throwable $e) {
            // Let caller know it failed
            return false;
        }
    }

    public function pop(string $queue): ?array
    {
        return null; // never used by work loop
    }

    public function release(array $payload, int $delaySeconds = 0): void { /*noop*/ }
    public function ack(array $payload): void { /*noop*/ }
    public function fail(array $payload, Throwable $exception = null): void { /*noop*/ }
    public function size(string $queue): int { return 0; }
}

/* -------------------------
 * Redis adapter
 * ------------------------- */
class RedisQueueAdapter implements QueueAdapterInterface
{
    protected \Redis $redis;
    protected string $namespace;
    protected string $processingKeySuffix = ':processing';

    /**
     * @param \Redis $redis - instance of ext-redis Redis
     * @param string $namespace - key prefix e.g. 'yantra:queue:'
     */
    public function __construct(\Redis $redis, string $namespace = 'yantra:queue:')
    {
        $this->redis = $redis;
        $this->namespace = $namespace;
    }

    protected function queueKey(string $queue): string
    {
        return $this->namespace . $queue;
    }

    protected function delayedKey(string $queue): string
    {
        return $this->namespace . $queue . ':delayed';
    }

    public function push(string $queue, array $payload): bool
    {
        // If payload has available_at in future, put into sorted set for delayed items
        $now = time();
        $available = $payload['available_at'] ?? $now;
        if ($available > $now) {
            $this->redis->zAdd($this->delayedKey($queue), $available, json_encode($payload));
            return true;
        }

        return (bool)$this->redis->lPush($this->queueKey($queue), json_encode($payload));
    }

    public function pop(string $queue): ?array
    {
        $now = time();
        // Move due delayed items to main queue (non-atomic simple approach)
        $delKey = $this->delayedKey($queue);
        $due = $this->redis->zRangeByScore($delKey, 0, $now);
        if (!empty($due)) {
            foreach ($due as $item) {
                // push to main list
                $this->redis->lPush($this->queueKey($queue), $item);
                $this->redis->zRem($delKey, $item);
            }
        }

        $raw = $this->redis->rPop($this->queueKey($queue));
        if ($raw === false || $raw === null) return null;
        $payload = json_decode($raw, true);
        if (!is_array($payload)) return null;
        // store processing marker (simple: track by unique id if present or store payload json)
        $processingKey = $this->queueKey($queue) . $this->processingKeySuffix;
        $this->redis->hSet($processingKey, spl_object_hash((object)$payload) . ':' . microtime(true), $raw);
        return $payload;
    }

    public function release(array $payload, int $delaySeconds = 0): void
    {
        $available = time() + max(0, $delaySeconds);
        $queue = $payload['queue'] ?? 'default';
        $payload['available_at'] = $available;
        $this->redis->zAdd($this->delayedKey($queue), $available, json_encode($payload));
    }

    public function ack(array $payload): void
    {
        // best-effort: remove processing markers. Not reliable without unique ID
        // We leave as noop because we didn't store a stable id
    }

    public function fail(array $payload, Throwable $exception = null): void
    {
        // store failed payload for inspection in a "failed" list
        $key = $this->namespace . 'failed';
        $entry = ['payload' => $payload, 'exception' => (string)($exception ? $exception->getMessage() : null), 'when' => time()];
        $this->redis->lPush($key, json_encode($entry));
    }

    public function size(string $queue): int
    {
        return (int)$this->redis->lLen($this->queueKey($queue));
    }
}

/* -------------------------
 * Database adapter (simple)
 * ------------------------- */
class DatabaseQueueAdapter implements QueueAdapterInterface
{
    protected PDO $pdo;
    protected string $table;

    /**
     * @param PDO|null $pdo - optional PDO instance; if null uses System\Database
     * @param string $table - table name
     */
    public function __construct(?PDO $pdo = null, string $table = 'yt_queue')
    {
        $this->pdo = $pdo ?? Database::getInstance()->getPDO();
        $this->table = $table;
        $this->ensureTable();
    }

    protected function ensureTable(): void
    {
        $sql = "CREATE TABLE IF NOT EXISTS `{$this->table}` (
            `id` BIGINT UNSIGNED NOT NULL AUTO_INCREMENT PRIMARY KEY,
            `queue` VARCHAR(100) NOT NULL,
            `payload` JSON NOT NULL,
            `attempts` INT NOT NULL DEFAULT 0,
            `available_at` INT NOT NULL,
            `created_at` INT NOT NULL,
            `last_error` TEXT NULL,
            INDEX (`queue`),
            INDEX (`available_at`)
        ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;";
        $this->pdo->exec($sql);
    }

    public function push(string $queue, array $payload): bool
    {
        $stmt = $this->pdo->prepare("INSERT INTO `{$this->table}` (`queue`,`payload`,`attempts`,`available_at`,`created_at`) VALUES (:q,:p,:a,:av,:c)");
        return $stmt->execute([
            ':q' => $queue,
            ':p' => json_encode($payload, JSON_UNESCAPED_UNICODE|JSON_UNESCAPED_SLASHES),
            ':a' => (int)$payload['attempts'],
            ':av' => (int)$payload['available_at'],
            ':c' => (int)$payload['created_at'],
        ]);
    }

    public function pop(string $queue): ?array
    {
        // select a single available row (atomic selection via transaction)
        $this->pdo->beginTransaction();
        try {
            $now = time();
            $stmt = $this->pdo->prepare("SELECT * FROM `{$this->table}` WHERE `queue` = :q AND `available_at` <= :now ORDER BY `id` ASC LIMIT 1 FOR UPDATE");
            $stmt->execute([':q' => $queue, ':now' => $now]);
            $row = $stmt->fetch(PDO::FETCH_ASSOC);
            if (!$row) {
                $this->pdo->commit();
                return null;
            }
            // mark as locked by updating available_at to far future (or remove)
            $update = $this->pdo->prepare("UPDATE `{$this->table}` SET `available_at` = :na WHERE `id` = :id");
            $update->execute([':na' => $now + 86400, ':id' => $row['id']]);
            $this->pdo->commit();
            $payload = json_decode($row['payload'], true);
            // embed internal meta so ack/fail can find row id
            $payload['_db_id'] = (int)$row['id'];
            return $payload;
        } catch (Throwable $e) {
            $this->pdo->rollBack();
            throw $e;
        }
    }

    public function release(array $payload, int $delaySeconds = 0): void
    {
        $id = $payload['_db_id'] ?? null;
        if ($id === null) return;
        $available = time() + max(0, $delaySeconds);
        $stmt = $this->pdo->prepare("UPDATE `{$this->table}` SET `available_at` = :av, `attempts` = :a WHERE `id` = :id");
        $attempts = (int)($payload['attempts'] ?? 0);
        $stmt->execute([':av' => $available, ':a' => $attempts, ':id' => $id]);
    }

    public function ack(array $payload): void
    {
        $id = $payload['_db_id'] ?? null;
        if ($id === null) return;
        $stmt = $this->pdo->prepare("DELETE FROM `{$this->table}` WHERE `id` = :id");
        $stmt->execute([':id' => $id]);
    }

    public function fail(array $payload, Throwable $exception = null): void
    {
        $id = $payload['_db_id'] ?? null;
        if ($id === null) return;
        $stmt = $this->pdo->prepare("UPDATE `{$this->table}` SET `last_error` = :err WHERE `id` = :id");
        $stmt->execute([':err' => $exception ? $exception->getMessage() : null, ':id' => $id]);
        // optionally move to failed table or keep for inspection
    }

    public function size(string $queue): int
    {
        $stmt = $this->pdo->prepare("SELECT COUNT(*) FROM `{$this->table}` WHERE `queue` = :q AND `available_at` <= :now");
        $stmt->execute([':q' => $queue, ':now' => time()]);
        return (int)$stmt->fetchColumn();
    }
}
