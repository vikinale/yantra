<?php
namespace System\Utilities;

use System\Database\Database;
use PDO;
use PDOException;
use Throwable;

/**
 * BufferedDatabaseLoggerAdapter
 *
 * Buffers logs and writes them in a single multi-row insert for efficiency.
 *
 * Constructor:
 *   new BufferedDatabaseLoggerAdapter(PDO|null $pdo = null, string $table = 'yt_logs', int $batchSize = 50, bool $autoCreateTable = true)
 *
 * Important:
 * - This adapter is synchronous in the flush phase (DB insert). In web requests it reduces DB calls by batching.
 * - For very high-throughput environments prefer asynchronous ingestion (queue).
 */
class BufferedDatabaseLoggerAdapter implements LoggerAdapterInterface
{
    protected PDO $pdo;
    protected string $table;
    protected array $buffer = [];
    protected int $batchSize;
    protected bool $autoCreateTable;
    protected ?FileLoggerAdapter $fallback = null;
    protected bool $shuttingDown = false;
    protected bool $flushOnDestruct = true;

    /**
     * @param PDO|null $pdo PDO instance or null to use System\Database
     * @param string $table table name to insert logs
     * @param int $batchSize number of records to buffer before flush
     * @param bool $autoCreateTable try to create table if missing
     * @param FileLoggerAdapter|null $fallback optional fallback file logger
     */
    public function __construct(?PDO $pdo = null, string $table = 'yt_logs', int $batchSize = 50, bool $autoCreateTable = true, ?FileLoggerAdapter $fallback = null)
    {
        $this->pdo = $pdo ?? Database::getInstance()->getPDO();
        $this->table = $table;
        $this->batchSize = max(1, (int)$batchSize);
        $this->autoCreateTable = (bool)$autoCreateTable;
        $this->fallback = $fallback ?? new FileLoggerAdapter(getcwd() . DIRECTORY_SEPARATOR . 'storage' . DIRECTORY_SEPARATOR . 'logs', 'yantra_db_fallback.log', FileLoggerAdapter::ROTATE_DAILY);

        if ($this->autoCreateTable) {
            try {
                $this->ensureTable();
            } catch (Throwable $e) {
                // If creation fails, fall back silently; actual inserts will use fallback on error.
            }
        }

        // register shutdown hook to flush buffer
        register_shutdown_function([$this, 'shutdownHandler']);
    }

    /**
     * Core write method - buffers record
     *
     * @param string $level
     * @param string $record formatted string (may contain JSON context at end)
     */
    public function write(string $level, string $record): void
    {
        // parse message/context/meta similar to DatabaseLoggerAdapter
        $message = $record;
        $contextJson = null;
        $metaJson = null;

        // Attempt to extract trailing JSON context/meta (if any)
        // We search for last '{' and try to decode JSON until success
        $lastBrace = strrpos($record, '{');
        if ($lastBrace !== false) {
            $possible = trim(substr($record, $lastBrace));
            if (strpos($possible, '{') === 0) {
                $decoded = @json_decode($possible, true);
                if (json_last_error() === JSON_ERROR_NONE && is_array($decoded)) {
                    $contextJson = $decoded;
                    $message = rtrim(substr($record, 0, $lastBrace));
                }
            }
        }

        $this->buffer[] = [
            'level' => substr((string)$level, 0, 20),
            'message' => $message,
            'context' => $contextJson !== null ? $contextJson : null,
            'meta' => $metaJson !== null ? $metaJson : null,
            'created_at' => (new \DateTime())->format('Y-m-d H:i:s'),
        ];

        if (count($this->buffer) >= $this->batchSize) {
            $this->flush();
        }
    }

    /**
     * Flush buffered entries into DB (batched insert).
     * Safe to call multiple times.
     */
    public function flush(): void
    {
        if (empty($this->buffer)) return;

        // Build multi-row insert
        $rows = $this->buffer;
        $this->buffer = []; // clear early to avoid duplicate flush attempts

        $placeholders = [];
        $params = [];
        $i = 0;

        foreach ($rows as $row) {
            $i++;
            $placeholders[] = "(:level{$i}, :message{$i}, :context{$i}, :meta{$i}, :created_at{$i})";
            $params[":level{$i}"] = $row['level'];
            $params[":message{$i}"] = $row['message'];
            $params[":context{$i}"] = $row['context'] !== null ? json_encode($row['context'], JSON_UNESCAPED_UNICODE|JSON_UNESCAPED_SLASHES) : null;
            $params[":meta{$i}"] = $row['meta'] !== null ? json_encode($row['meta'], JSON_UNESCAPED_UNICODE|JSON_UNESCAPED_SLASHES) : null;
            $params[":created_at{$i}"] = $row['created_at'];
        }

        $sql = "INSERT INTO `{$this->table}` (`level`,`message`,`context`,`meta`,`created_at`) VALUES " . implode(', ', $placeholders);

        try {
            $this->pdo->beginTransaction();
            $stmt = $this->pdo->prepare($sql);
            $stmt->execute($params);
            $this->pdo->commit();
        } catch (PDOException $e) {
            // rollback and fallback to file logger for each entry
            try { $this->pdo->rollBack(); } catch (Throwable $_) {}
            foreach ($rows as $r) {
                try {
                    $line = sprintf("[%s] %s.%s: %s %s\n", $r['created_at'], 'yantra', strtoupper($r['level']), $r['message'], $r['context'] ? $r['context'] : '');
                    $this->fallback->write($r['level'], $line);
                } catch (Throwable $_) {
                    // swallow - cannot do much at this point
                }
            }
        } catch (Throwable $e) {
            // non-PDO exception fallback
            foreach ($rows as $r) {
                try {
                    $line = sprintf("[%s] %s.%s: %s %s\n", $r['created_at'], 'yantra', strtoupper($r['level']), $r['message'], $r['context'] ? $r['context'] : '');
                    $this->fallback->write($r['level'], $line);
                } catch (Throwable $_) {}
            }
        }
    }

    /**
     * Shutdown handler invoked by register_shutdown_function.
     */
    public function shutdownHandler(): void
    {
        // prevent re-entry
        if ($this->shuttingDown) return;
        $this->shuttingDown = true;
        try {
            $this->flush();
        } catch (Throwable $_) {
            // ignore
        }
    }

    /**
     * Attempt to ensure table exists (simple create)
     */
    protected function ensureTable(): void
    {
        $sql = <<<SQL
CREATE TABLE IF NOT EXISTS `{$this->table}` (
  `id` BIGINT UNSIGNED NOT NULL AUTO_INCREMENT PRIMARY KEY,
  `level` VARCHAR(20) NOT NULL,
  `message` TEXT NOT NULL,
  `context` JSON NULL,
  `meta` JSON NULL,
  `created_at` DATETIME NOT NULL,
  INDEX (`level`),
  INDEX (`created_at`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
SQL;
        $this->pdo->exec($sql);
    } 
    // No-op methods required by LoggerAdapterInterface signature in earlier file (some adapters had extra methods).
    // If your LoggerAdapterInterface doesn't require these, ignore. Otherwise keep no-ops here.
    // public function setSomething(...) { ... }

    /**
     * Manually force flush and optionally disable further auto-flush at destruct
     */
    public function close(bool $disableAutoFlush = false): void
    {
        $this->flush();
        if ($disableAutoFlush) $this->flushOnDestruct = false;
    }

    /**
     * Destructor ensures flush if not already flushed
     */
    public function __destruct()
    {
        if ($this->flushOnDestruct) {
            try { $this->shutdownHandler(); } catch (Throwable $_) {}
        }
    }
}
