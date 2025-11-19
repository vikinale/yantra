<?php
namespace System\Services\RateLimiter;

use System\Database\Database;
use PDO;

class DBRateLimiter implements RateLimiterInterface
{
    protected PDO $pdo;
    protected string $table = 'yt_rate_counters';

    public function __construct(?Database $db = null)
    {
        $this->pdo = ($db ?? Database::getInstance())->getPDO();
        $this->pdo->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
        // Ensure table exists (simple schema)
        $this->pdo->exec("
            CREATE TABLE IF NOT EXISTS {$this->table} (
              `id` INT AUTO_INCREMENT PRIMARY KEY,
              `keyhash` VARCHAR(64) NOT NULL,
              `identifier` VARCHAR(255) NOT NULL,
              `count` INT NOT NULL DEFAULT 0,
              `window_start` INT NOT NULL,
              INDEX (keyhash)
            ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;
        ");
    }

    public function consume(string $key, int $limit, int $windowSeconds, int $tokens = 1): array
    {
        $now = time();
        $windowStart = (int) (floor($now / $windowSeconds) * $windowSeconds);
        $keyhash = hash('sha256', $key . ':' . $windowStart);
        // Try update existing row
        $sel = $this->pdo->prepare("SELECT id, count FROM {$this->table} WHERE keyhash = :k LIMIT 1 FOR UPDATE");
        $this->pdo->beginTransaction();
        $sel->execute([':k' => $keyhash]);
        $row = $sel->fetch(PDO::FETCH_ASSOC);
        if ($row) {
            $newCount = (int)$row['count'] + $tokens;
            $allowed = $newCount <= $limit;
            $upd = $this->pdo->prepare("UPDATE {$this->table} SET count = :c WHERE id = :id");
            $upd->execute([':c' => $newCount, ':id' => $row['id']]);
        } else {
            $allowed = $tokens <= $limit;
            $newCount = $tokens;
            $ins = $this->pdo->prepare("INSERT INTO {$this->table} (keyhash, identifier, count, window_start) VALUES (:k, :idn, :c, :ws)");
            $ins->execute([':k' => $keyhash, ':idn' => $key, ':c' => $newCount, ':ws' => $windowStart]);
        }
        $this->pdo->commit();

        $reset = ($windowStart + $windowSeconds) - $now;
        return ['allowed' => (bool)$allowed, 'remaining' => max(0, $limit - $newCount), 'reset' => $reset, 'count' => $newCount];
    }

    public function getUsage(string $key, int $windowSeconds): array
    {
        $now = time();
        $windowStart = (int) (floor($now / $windowSeconds) * $windowSeconds);
        $keyhash = hash('sha256', $key . ':' . $windowStart);
        $stmt = $this->pdo->prepare("SELECT count FROM {$this->table} WHERE keyhash = :k LIMIT 1");
        $stmt->execute([':k' => $keyhash]);
        $row = $stmt->fetch(PDO::FETCH_ASSOC);
        $count = $row ? (int)$row['count'] : 0;
        $reset = ($windowStart + $windowSeconds) - $now;
        return ['count' => $count, 'reset' => $reset];
    }
}
