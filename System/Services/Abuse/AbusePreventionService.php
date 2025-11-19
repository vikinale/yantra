<?php
namespace System\Services\Abuse;

use Core\Services\RateLimiter\RateLimiterInterface;
use System\Database\Database;
use Psr\Log\LoggerInterface;
use DateTime;

class AbusePreventionService
{
    protected RateLimiterInterface $limiter;
    protected array $config;
    protected $db;
    protected ?LoggerInterface $logger;

    public function __construct(RateLimiterInterface $limiter, array $config = [], ?LoggerInterface $logger = null, $db = null)
    {
        $this->limiter = $limiter;
        $this->config = $config;
        $this->db = $db ?? Database::getInstance();
        $this->logger = $logger;
    }

    /**
     * Main method to check and enforce a rate rule.
     *
     * @param string $identifier e.g., "ip:1.2.3.4" or "user:123" or "api_key:abcd"
     * @param string $ruleKey config key in rules (like 'ip' or 'login_attempts')
     * @param int $tokens default 1
     * @return array ['allowed' => bool, 'remaining' => int, 'reset' => int, 'blocked' => bool]
     */
    public function check(string $identifier, string $ruleKey, int $tokens = 1): array
    {
        $rules = $this->config['rules'] ?? [];
        if (!isset($rules[$ruleKey])) {
            throw new \InvalidArgumentException("Unknown rate rule {$ruleKey}");
        }
        $rule = $rules[$ruleKey];
        $limit = (int)$rule['limit'];
        $window = (int)$rule['window'];
        $blockSeconds = (int)($rule['block'] ?? 0);

        // whitelist check
        if ($this->isWhitelisted($identifier)) {
            return ['allowed' => true, 'remaining' => PHP_INT_MAX, 'reset' => 0, 'blocked' => false];
        }

        // check block table
        $blocked = $this->isBlocked($identifier);
        if ($blocked) {
            return ['allowed' => false, 'remaining' => 0, 'reset' => $this->blockRemainingSeconds($identifier), 'blocked' => true];
        }

        $res = $this->limiter->consume($identifier, $limit, $window, $tokens);

        if (!$res['allowed']) {
            // log abuse record
            $this->logAbuse($identifier, 'rate_limit', [
                'rule' => $ruleKey,
                'count' => $res['count'] ?? null,
                'limit' => $limit,
                'window' => $window
            ]);
            // create temporary block if configured
            if ($blockSeconds > 0) {
                $this->block($identifier, "rate_limit:{$ruleKey}", $blockSeconds);
            }
            // optional admin notification
            if (!empty($this->config['admin_notify'])) {
                $this->notifyAdmin("Rate limit hit: {$identifier}", [
                    'rule' => $ruleKey,
                    'count' => $res['count'] ?? null,
                    'limit' => $limit
                ]);
            }
        }

        return ['allowed' => (bool)$res['allowed'], 'remaining' => (int)$res['remaining'], 'reset' => (int)$res['reset'], 'blocked' => false];
    }

    public function logAbuse(string $identifier, string $type, array $meta = []): int
    {
        $pdo = $this->db->getPDO();
        $stmt = $pdo->prepare("INSERT INTO yt_abuse_reports (identifier, type, meta, created_at) VALUES (:idn, :type, :meta, NOW())");
        $stmt->execute([
            ':idn' => $identifier,
            ':type' => $type,
            ':meta' => !empty($meta) ? json_encode($meta, JSON_UNESCAPED_UNICODE) : null
        ]);
        $id = (int)$pdo->lastInsertId();
        if ($this->logger) $this->logger->warning("[Abuse] {$type} for {$identifier}", $meta);
        return $id;
    }

    public function block(string $identifier, string $reason, int $seconds = 0): bool
    {
        $pdo = $this->db->getPDO();
        $expires = $seconds > 0 ? (new DateTime())->modify("+{$seconds} seconds")->format('Y-m-d H:i:s') : null;
        // upsert block
        $sql = "INSERT INTO yt_abuse_blocks (identifier, reason, expires_at, created_at)
                VALUES (:idn, :reason, :exp, NOW())
                ON DUPLICATE KEY UPDATE reason = :reason_u, expires_at = :exp_u";
        $stmt = $pdo->prepare($sql);
        $stmt->execute([
            ':idn' => $identifier,
            ':reason' => $reason,
            ':exp' => $expires,
            ':reason_u' => $reason,
            ':exp_u' => $expires
        ]);
        if ($this->logger) $this->logger->info("[Abuse] Blocked {$identifier} for {$seconds}s reason={$reason}");
        return true;
    }

    public function isBlocked(string $identifier): bool
    {
        $pdo = $this->db->getPDO();
        $stmt = $pdo->prepare("SELECT expires_at FROM yt_abuse_blocks WHERE identifier = :idn LIMIT 1");
        $stmt->execute([':idn' => $identifier]);
        $row = $stmt->fetch(\PDO::FETCH_ASSOC);
        if (!$row) return false;
        if ($row['expires_at'] === null) return true; // permanent
        $expires = strtotime($row['expires_at']);
        if ($expires <= time()) {
            // expired — remove
            $this->unblock($identifier);
            return false;
        }
        return true;
    }

    public function unblock(string $identifier): bool
    {
        $pdo = $this->db->getPDO();
        $stmt = $pdo->prepare("DELETE FROM yt_abuse_blocks WHERE identifier = :idn");
        $stmt->execute([':idn' => $identifier]);
        if ($this->logger) $this->logger->info("[Abuse] Unblocked {$identifier}");
        return true;
    }

    public function blockRemainingSeconds(string $identifier): int
    {
        $pdo = $this->db->getPDO();
        $stmt = $pdo->prepare("SELECT expires_at FROM yt_abuse_blocks WHERE identifier = :idn LIMIT 1");
        $stmt->execute([':idn' => $identifier]);
        $row = $stmt->fetch(\PDO::FETCH_ASSOC);
        if (!$row || $row['expires_at'] === null) return PHP_INT_MAX;
        $expires = strtotime($row['expires_at']);
        return max(0, $expires - time());
    }

    protected function isWhitelisted(string $identifier): bool
    {
        $pdo = $this->db->getPDO();
        $stmt = $pdo->prepare("SELECT 1 FROM yt_abuse_whitelist WHERE identifier = :idn LIMIT 1");
        $stmt->execute([':idn' => $identifier]);
        return (bool)$stmt->fetchColumn();
    }

    protected function notifyAdmin(string $subject, array $meta = [])
    {
        if (empty($this->config['admin_notify'])) return;
        // Try simple mail via PHP mail() — you can replace with MailerManager
        $to = $this->config['admin_notify'];
        $body = json_encode($meta, JSON_PRETTY_PRINT);
        @mail($to, $subject, $body);
    }
}
