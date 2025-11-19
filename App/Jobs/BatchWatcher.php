<?php
namespace App\Jobs;

use System\Database\Database;
use Psr\Log\LoggerInterface;
use Throwable;
use RuntimeException;
use InvalidArgumentException;

/**
 * BatchWatcher job
 *
 * Payload expected:
 *   ['batch_id' => (int), 'parent_batch_id' => (int|null)] 
 *
 * Responsibilities:
 * - Read failed job details from `yt_batch_failed` for the given batch and any nested child batches.
 * - Produce a summary and update the batch row in `yt_batches`.
 * - Optionally enqueue/trigger the `then` job (if configured in `yt_batches.then_job`).
 */
class BatchWatcher
{
    protected Database $db;
    protected ?LoggerInterface $logger;

    public function __construct(?Database $db = null, ?LoggerInterface $logger = null)
    {
        // Use passed Database instance or the project's singleton convention.
        $this->db     = $db ?? Database::getInstance();
        $this->logger = $logger;
    }

    /**
     * Entry point for the job runner. Accepts a payload array.
     *
     * @param array $payload
     * @return void
     * @throws InvalidArgumentException
     */
    public function handle(array $payload): void
    {
        $batchId = isset($payload['batch_id']) ? (int)$payload['batch_id'] : 0;
        if ($batchId <= 0) {
            throw new InvalidArgumentException('BatchWatcher requires a valid batch_id in payload.');
        }

        $pdo = $this->db->getPDO();
        // set ERRMODE to exception to surface issues
        $pdo->setAttribute(\PDO::ATTR_ERRMODE, \PDO::ERRMODE_EXCEPTION);

        try {
            $pdo->beginTransaction();

            // Load batch metadata from yt_batches (assumed table)
            $stmt = $pdo->prepare("SELECT * FROM yt_batches WHERE batch_id = :batch_id FOR UPDATE");
            $stmt->execute([':batch_id' => $batchId]);
            $batch = $stmt->fetch(\PDO::FETCH_ASSOC);

            if (!$batch) {
                // No such batch — nothing to do
                $pdo->rollBack();
                throw new RuntimeException("Batch #{$batchId} not found.");
            }

            // Collect failed job rows for this batch and nested child batches.
            // We assume yt_batch_failed has at least: failed_id, batch_id, job_name, payload, failed_at, exception
            $failStmt = $pdo->prepare("
                SELECT failed_id, batch_id, job_name, payload, exception, failed_at
                FROM yt_batch_failed
                WHERE batch_id = :batch_id
                ORDER BY failed_at ASC
            ");
            $failStmt->execute([':batch_id' => $batchId]);
            $failedRows = $failStmt->fetchAll(\PDO::FETCH_ASSOC);

            // Optionally collect failures for child batches if your system nests them and stores parent_batch_id
            // If your schema stores parent-child relationship in yt_batches.parent_batch_id, traverse it:
            $childFailures = $this->collectChildBatchFailures($pdo, $batchId);
            if (!empty($childFailures)) {
                $failedRows = array_merge($failedRows, $childFailures);
            }

            // Build a compact summary: counts + sample messages (trim long payloads)
            $failedCount = count($failedRows);
            $summary = $this->buildSummary($failedRows);

            // Update the batch row: set status, failed_count, summary, finished_at (if desired)
            $updateSql = "
                UPDATE yt_batches
                SET status = :status,
                    failed_count = :failed_count,
                    summary = :summary,
                    updated_at = NOW()
                WHERE batch_id = :batch_id
            ";
            $status = $failedCount > 0 ? 'failed' : 'completed';
            $updateStmt = $pdo->prepare($updateSql);
            $updateStmt->execute([
                ':status' => $status,
                ':failed_count' => $failedCount,
                ':summary' => json_encode($summary, JSON_UNESCAPED_UNICODE),
                ':batch_id' => $batchId,
            ]);

            // If the original batch row included a 'then_job' (callback), enqueue/dispatch it with summary
            if (!empty($batch['then_job'])) {
                $this->enqueueThenJob($pdo, $batch['then_job'], $batchId, $summary);
            }

            $pdo->commit();

            // Optionally log success
            $this->logInfo("BatchWatcher handled batch {$batchId}: status={$status}, failed_count={$failedCount}");

        } catch (Throwable $e) {
            // Rollback and log
            try { if ($pdo->inTransaction()) $pdo->rollBack(); } catch (Throwable $_) {}
            $this->logError("BatchWatcher error for batch {$batchId}: " . $e->getMessage(), $e);
            // Re-throw so job runner can mark this Watcher job failed/retry according to worker config
            throw $e;
        }
    }

    /**
     * Traverse child batches (if your schema has parent_batch_id) and collect their failed rows.
     * Adjust SQL if your nested-batch representation differs.
     *
     * @param \PDO $pdo
     * @param int $rootBatchId
     * @return array
     */
    protected function collectChildBatchFailures(\PDO $pdo, int $rootBatchId): array
    {
        // Check if yt_batches uses `parent_batch_id` (best-effort; if not present, this returns empty)
        try {
            $childStmt = $pdo->prepare("
                SELECT batch_id FROM yt_batches WHERE parent_batch_id = :parent ORDER BY batch_id ASC
            ");
            $childStmt->execute([':parent' => $rootBatchId]);
            $childIds = $childStmt->fetchAll(\PDO::FETCH_COLUMN, 0);
        } catch (Throwable $e) {
            // Schema probably does not have parent_batch_id; return nothing
            return [];
        }

        $all = [];
        if (empty($childIds)) {
            return $all;
        }

        // Prepare a single query to fetch failures for all child batch ids
        $in = implode(',', array_fill(0, count($childIds), '?'));
        $sql = "
            SELECT failed_id, batch_id, job_name, payload, exception, failed_at
            FROM yt_batch_failed
            WHERE batch_id IN ($in)
            ORDER BY failed_at ASC
        ";
        $stmt = $pdo->prepare($sql);
        // bind child ids as positional params
        $stmt->execute($childIds);
        $rows = $stmt->fetchAll(\PDO::FETCH_ASSOC);
        return $rows ?: [];
    }

    /**
     * Build a compact summary from failed rows.
     *
     * @param array $failedRows
     * @return array
     */
    protected function buildSummary(array $failedRows): array
    {
        $summary = [
            'failed_count' => count($failedRows),
            'samples' => [],
        ];

        // include up to N samples with trimmed payload/exception
        $limit = 10;
        $i = 0;
        foreach ($failedRows as $r) {
            if ($i++ >= $limit) break;
            $sample = [
                'failed_id' => $r['failed_id'] ?? null,
                'batch_id'  => $r['batch_id'] ?? null,
                'job_name'  => $r['job_name'] ?? null,
                'failed_at' => $r['failed_at'] ?? null,
                // payload might be JSON; keep trimmed string for compactness
                'payload'   => $this->safeTrim($r['payload'] ?? null, 1024),
                'exception' => $this->safeTrim($r['exception'] ?? null, 1024),
            ];
            $summary['samples'][] = $sample;
        }

        return $summary;
    }

    /**
     * Enqueue (or insert) a 'then' job which should run after the batch watcher completes.
     * This is a best-effort implementation — adapt to your job queue table or dispatcher.
     *
     * We assume a simple job queue `yt_jobs` with columns: job_name, payload (JSON), queue, attempts, created_at
     *
     * @param \PDO $pdo
     * @param string $thenJobName
     * @param int $batchId
     * @param array $summary
     * @return void
     */
    protected function enqueueThenJob(\PDO $pdo, string $thenJobName, int $batchId, array $summary): void
    {
        // Build payload for then job: pass the batch summary plus batch id
        $payload = json_encode([
            'batch_id' => $batchId,
            'summary'  => $summary,
        ], JSON_UNESCAPED_UNICODE);

        // Best-effort insert into `yt_jobs` queue table
        $insertSql = "
            INSERT INTO yt_jobs (job_name, payload, queue, attempts, created_at)
            VALUES (:job_name, :payload, :queue, 0, NOW())
        ";
        $stmt = $pdo->prepare($insertSql);
        $stmt->execute([
            ':job_name' => $thenJobName,
            ':payload'  => $payload,
            ':queue'    => 'default',
        ]);

        $this->logInfo("Enqueued then-job '{$thenJobName}' for batch {$batchId}.");
    }

    /**
     * Safe trimming helper to prevent storing huge strings in summary.
     */
    protected function safeTrim(?string $s, int $limit = 1024): ?string
    {
        if ($s === null) return null;
        $s = (string)$s;
        if (mb_strlen($s) <= $limit) return $s;
        return mb_substr($s, 0, $limit) . '…';
    }

    /**
     * Logger helpers
     */
    protected function logInfo(string $msg): void
    {
        if ($this->logger) {
            $this->logger->info($msg);
        } else {
            // fallback to error_log
            error_log("[BatchWatcher][INFO] " . $msg);
        }
    }

    protected function logError(string $msg, ?Throwable $e = null): void
    {
        if ($this->logger) {
            $this->logger->error($msg, $e ? ['exception' => $e] : []);
        } else {
            error_log("[BatchWatcher][ERROR] " . $msg . ($e ? ' | ' . $e->getMessage() : ''));
        }
    }
}
