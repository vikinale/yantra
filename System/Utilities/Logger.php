<?php
namespace System\Utilities;

use System\Database\Database;

/**
 * Simple Logger utility for Yantra
 *
 * Features:
 *  - levels: emergency, alert, critical, error, warning, notice, info, debug
 *  - multiple adapters (File, Syslog, Null)
 *  - context interpolation: "Order {id} saved" with ['id'=>123]
 *  - safe file writes with flock
 *
 * Usage:
 *   // init default file logger (called automatically when first used)
 *   Log::init(); // writes to ./storage/logs/yantra.log
 *
 *   // or configure:
 *   Log::init(new FileLoggerAdapter('/path/to/logs', 'yantra.log', FileLoggerAdapter::ROTATE_DAILY));
 *
 *   Log::info('User logged in', ['id' => 5]);
 *   log('warning', 'Quota {used}/{limit}', ['used'=>90,'limit'=>100]); // via helper
 */

class Log
{
    public const LEVELS = [
        'emergency' => 0,
        'alert'     => 1,
        'critical'  => 2,
        'error'     => 3,
        'warning'   => 4,
        'notice'    => 5,
        'info'      => 6,
        'debug'     => 7,
    ];

    /** @var LoggerAdapterInterface|null */
    protected static ?LoggerAdapterInterface $adapter = null;

    /** Minimum level to log (default debug -> log everything) */
    protected static string $minLevel = 'debug';

    public static function init(LoggerAdapterInterface $adapter = null, string $minLevel = 'debug'): void
    {
        if ($adapter !== null) {
            self::$adapter = $adapter;
        } elseif (self::$adapter === null) {
            // default: file logger under ./storage/logs
            $logDir = getcwd() . DIRECTORY_SEPARATOR . 'storage' . DIRECTORY_SEPARATOR . 'logs';
            self::$adapter = new FileLoggerAdapter($logDir, 'yantra.log', FileLoggerAdapter::ROTATE_DAILY);
        }
        if ($minLevel !== null) self::$minLevel = $minLevel;
    }

    protected static function adapter(): LoggerAdapterInterface
    {
        if (self::$adapter === null) self::init();
        return self::$adapter;
    }

    protected static function shouldLog(string $level): bool
    {
        $min = self::LEVELS[self::$minLevel] ?? 7;
        $lvl = self::LEVELS[$level] ?? 7;
        return $lvl <= $min;
    }

    public static function emergency(string $message, array $context = []): void { self::log('emergency', $message, $context); }
    public static function alert(string $message, array $context = []): void     { self::log('alert', $message, $context); }
    public static function critical(string $message, array $context = []): void  { self::log('critical', $message, $context); }
    public static function error(string $message, array $context = []): void     { self::log('error', $message, $context); }
    public static function warning(string $message, array $context = []): void   { self::log('warning', $message, $context); }
    public static function notice(string $message, array $context = []): void    { self::log('notice', $message, $context); }
    public static function info(string $message, array $context = []): void      { self::log('info', $message, $context); }
    public static function debug(string $message, array $context = []): void     { self::log('debug', $message, $context); }

    /**
     * Core log method
     */
    public static function log(string $level, string $message, array $context = []): void
    {
        $level = strtolower($level);
        if (!isset(self::LEVELS[$level])) $level = 'info';
        // check threshold: only log if level <= threshold numeric (lower is more urgent)
        $minN = self::LEVELS[self::$minLevel] ?? self::LEVELS['debug'];
        $lvlN = self::LEVELS[$level];
        if ($lvlN > $minN) {
            // skip (level is lower priority than threshold)
            return;
        }

        $ts = (new \DateTime('now', new \DateTimeZone(date_default_timezone_get() ?: 'UTC')))->format('Y-m-d H:i:sP');
        $pid = getmypid();
        $interpolated = self::interpolate($message, $context);
        $record = sprintf("[%s] %s.%s: %s %s\n", $ts, 'yantra', strtoupper($level), $interpolated, self::contextToString($context));
        try {
            self::adapter()->write($level, $record);
        } catch (\Throwable $e) {
            // last-resort: attempt syslog
            @openlog('yantra', LOG_PID, LOG_USER);
            @syslog(LOG_ERR, "Logger write failed: " . $e->getMessage() . " — Original: " . $interpolated);
            @closelog();
        }
    }

    /**
     * Interpolate {placeholders} in message using context array
     */
    protected static function interpolate(string $message, array $context = []): string
    {
        if (strpos($message, '{') === false) return $message;
        $replace = [];
        foreach ($context as $k => $v) {
            // convert scalars to string safely
            if (is_object($v) && method_exists($v, '__toString')) {
                $val = (string)$v;
            } elseif (is_scalar($v) || $v === null) {
                $val = (string)$v;
            } else {
                // for arrays / objects show JSON snippet (short)
                $val = @json_encode($v, JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES);
            }
            $replace['{' . $k . '}'] = $val;
        }
        return strtr($message, $replace);
    }

    protected static function contextToString(array $context): string
    {
        if (empty($context)) return '';
        try {
            return json_encode($context, JSON_UNESCAPED_SLASHES|JSON_UNESCAPED_UNICODE);
        } catch (\Throwable $e) {
            return '';
        }
    }

    public static function setAdapter(LoggerAdapterInterface $adapter): void
    {
        self::$adapter = $adapter;
    }

    public static function setMinLevel(string $level): void
    {
        if (isset(self::LEVELS[$level])) self::$minLevel = $level;
    }
}

/* -------------------------
 * Adapter contract
 * ------------------------- */
interface LoggerAdapterInterface
{
    /**
     * Write a pre-formatted record. Implementation receives the level and the formatted string (including newline).
     * Implementations should handle concurrent safe writes.
     *
     * @param string $level lower-case level
     * @param string $record formatted line
     */
    public function write(string $level, string $record): void;
}

/* -------------------------
 * FileLoggerAdapter
 *  - supports rotation: none, daily, size-based (simple)
 * ------------------------- */
class FileLoggerAdapter implements LoggerAdapterInterface
{
    public const ROTATE_NONE = 0;
    public const ROTATE_DAILY = 1;
    public const ROTATE_SIZE = 2;

    protected string $baseDir;
    protected string $fileName;
    protected int $rotateMode;
    protected int $maxSizeBytes;
    protected $fileHandle = null;

    /**
     * @param string $baseDir directory where logs are stored
     * @param string $fileName base filename (e.g., 'yantra.log')
     * @param int $rotateMode ROTATE_NONE|ROTATE_DAILY|ROTATE_SIZE
     * @param int $maxSizeBytes used when ROTATE_SIZE (e.g., 5*1024*1024)
     */
    public function __construct(string $baseDir, string $fileName = 'yantra.log', int $rotateMode = self::ROTATE_DAILY, int $maxSizeBytes = 5242880)
    {
        $this->baseDir = rtrim($baseDir, DIRECTORY_SEPARATOR);
        $this->fileName = $fileName;
        $this->rotateMode = $rotateMode;
        $this->maxSizeBytes = $maxSizeBytes;
        if (!is_dir($this->baseDir)) {
            @mkdir($this->baseDir, 0777, true);
        }
    }

    protected function currentPath(): string
    {
        if ($this->rotateMode === self::ROTATE_DAILY) {
            $date = date('Ymd');
            $name = preg_replace('/\.log$/', '', $this->fileName) . "_{$date}.log";
            return $this->baseDir . DIRECTORY_SEPARATOR . $name;
        }
        return $this->baseDir . DIRECTORY_SEPARATOR . $this->fileName;
    }

    /**
     * Writes record safely using file locking. Rotates by size if requested.
     */
    public function write(string $level, string $record): void
    {
        $path = $this->currentPath();

        // ensure parent dir exists
        $dir = dirname($path);
        if (!is_dir($dir)) @mkdir($dir, 0777, true);

        $fp = @fopen($path, 'ab');
        if (!$fp) {
            throw new \RuntimeException("Unable to open log file: $path");
        }

        $ok = flock($fp, LOCK_EX);
        if (!$ok) {
            // fallback to non-blocking write
            fwrite($fp, $record);
            fclose($fp);
            return;
        }

        fwrite($fp, $record);
        fflush($fp);

        // size rotation check (best-effort)
        if ($this->rotateMode === self::ROTATE_SIZE) {
            clearstatcache(true, $path);
            $size = filesize($path) ?: 0;
            if ($size > $this->maxSizeBytes) {
                // rotate: rename with timestamp
                $ts = date('Ymd_His');
                $rot = $path . '.' . $ts;
                @rename($path, $rot);
                // create new file
                // (next write will recreate)
            }
        }

        flock($fp, LOCK_UN);
        fclose($fp);
    }
}

/* -------------------------
 * Syslog adapter (writes to system syslog)
 * ------------------------- */
class SyslogLoggerAdapter implements LoggerAdapterInterface
{
    protected string $ident;
    protected int $facility;

    public function __construct(string $ident = 'yantra', int $facility = LOG_USER)
    {
        $this->ident = $ident;
        $this->facility = $facility;
    }

    public function write(string $level, string $record): void
    {
        // map level to syslog priority
        $map = [
            'emergency' => LOG_EMERG,
            'alert'     => LOG_ALERT,
            'critical'  => LOG_CRIT,
            'error'     => LOG_ERR,
            'warning'   => LOG_WARNING,
            'notice'    => LOG_NOTICE,
            'info'      => LOG_INFO,
            'debug'     => LOG_DEBUG,
        ];
        $pri = $map[$level] ?? LOG_INFO;
        openlog($this->ident, LOG_PID, $this->facility);
        syslog($pri, $record);
        closelog();
    }
}

/* -------------------------
 * Null logger (no-op)
 * ------------------------- */
class NullLoggerAdapter implements LoggerAdapterInterface
{
    public function write(string $level, string $record): void { /* no-op */ }
}

class DatabaseLoggerAdapter implements LoggerAdapterInterface
{
    protected string $table;
    protected \PDO $pdo;
    protected ?\PDOStatement $stmt = null;
    protected bool $autoCreateTable;

    /**
     * @param string $table DB table name (default 'yt_logs')
     * @param bool $autoCreateTable attempt to create table if missing
     * @param \PDO|null $pdo optional PDO instance; if null, Database::getInstance()->getPDO() is used
     */
    public function __construct(string $table = 'yt_logs', bool $autoCreateTable = false, ?\PDO $pdo = null)
    {
        $this->table = $table;
        $this->pdo = $pdo ?? Database::getInstance()->getPDO();
        $this->autoCreateTable = $autoCreateTable;

        // Try to prepare statement; if table doesn't exist and autoCreateTable true, attempt to create it
        try {
            $this->prepareStatement();
        } catch (\PDOException $e) {
            if ($this->autoCreateTable) {
                $this->tryCreateTable();
                $this->prepareStatement(); // try again
            } else {
                throw $e;
            }
        }
    }

    protected function prepareStatement(): void
    {
        $sql = "INSERT INTO `{$this->table}` (`level`, `message`, `context`, `meta`, `created_at`)
                VALUES (:level, :message, :context, :meta, :created_at)";
        $this->stmt = $this->pdo->prepare($sql);
    }

    protected function tryCreateTable(): void
    {
        $sql = <<<SQL
        CREATE TABLE IF NOT EXISTS `{$this->table}` (
        `id` BIGINT UNSIGNED NOT NULL AUTO_INCREMENT PRIMARY KEY,
        `level` VARCHAR(20) NOT NULL,
        `message` TEXT NOT NULL,
        `context` JSON NULL,
        `meta` JSON NULL,
        `created_at` DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
        INDEX (`level`),
        INDEX (`created_at`)
        ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
        SQL;
        $this->pdo->exec($sql);
    }

    /**
     * Write log to DB. This function is synchronous and should be used carefully in high-throughput apps.
     *
     * @param string $level
     * @param string $record formatted message string (includes timestamp)
     */
    public function write(string $level, string $record): void
    {
        // Extract message text and context JSON if possible:
        // The logger writes a formatted record; we want to store message + context separately.
        // We'll store full record as message if parsing fails.
        $message = $record;
        $contextJson = null;
        $metaJson = null;

        // Attempt to detect an appended JSON context (Log::contextToString produces JSON at end).
        // Our File logger formatted: "[ts] yantra.LEVEL: interpolated_message {json}\n"
        // We'll try to parse last JSON block in the record.
        $lastBrace = strrpos($record, '{');
        if ($lastBrace !== false) {
            $possible = trim(substr($record, $lastBrace));
            // ensure it ends with } or }\n
            if (str_starts_with($possible, '{')) {
                // try decode
                $decoded = @json_decode($possible, true);
                if (json_last_error() === JSON_ERROR_NONE && is_array($decoded)) {
                    $contextJson = $decoded;
                    // message is record up to $lastBrace
                    $message = rtrim(substr($record, 0, $lastBrace));
                }
            }
        }

        // fallback wrapping for context/meta into JSON strings for DB
        try {
            $this->stmt->execute([
                ':level' => substr($level, 0, 20),
                ':message' => $message,
                ':context' => $contextJson ? json_encode($contextJson, JSON_UNESCAPED_UNICODE|JSON_UNESCAPED_SLASHES) : null,
                ':meta' => $metaJson ? json_encode($metaJson, JSON_UNESCAPED_UNICODE|JSON_UNESCAPED_SLASHES) : null,
                ':created_at' => (new \DateTime())->format('Y-m-d H:i:s'),
            ]);
        } catch (\PDOException $e) {
            // If DB insert fails (e.g., DB down), fallback to file logger to avoid losing logs
            try {
                $fallbackDir = getcwd() . DIRECTORY_SEPARATOR . 'storage' . DIRECTORY_SEPARATOR . 'logs';
                $fallback = new FileLoggerAdapter($fallbackDir, 'yantra_fallback.log', FileLoggerAdapter::ROTATE_DAILY);
                $fallback->write('error', "[DBLogger fallback] failed insert: " . $e->getMessage() . " — original: " . $record);
            } catch (\Throwable $_) {
                // swallow any further exceptions
            }
        }
    }
}
