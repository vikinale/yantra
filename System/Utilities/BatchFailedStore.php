<?php
namespace System\Utilities;

use PDO;
use Exception;

class BatchFailedStore
{
    protected PDO $pdo;
    protected string $table = 'yt_batch_failed';

    public function __construct(PDO $pdo)
    {
        $this->pdo = $pdo;
        $this->pdo->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
    }

    /**
     * Insert a failed job record
     *
     * @param int $batchId
     * @param int $jobId
     * @param string $jobName
     * @param array|null $payload
     * @param string|null $errorMessage
     * @param string|null $errorTrace
     * @return int inserted id
     */
    public function insertFailedJob(int $batchId, int $jobId, string $jobName = '', ?array $payload = null, ?string $errorMessage = null, ?string $errorTrace = null): int
    {
        $stmt = $this->pdo->prepare("
            INSERT INTO {$this->table} (batch_id, job_id, job_name, payload, error_message, error_trace)
            VALUES (:batch_id, :job_id, :job_name, :payload, :error_message, :error_trace)
        ");

        $jsonPayload = $payload === null ? null : json_encode($payload, JSON_UNESCAPED_UNICODE);
        $stmt->execute([
            ':batch_id' => $batchId,
            ':job_id' => $jobId,
            ':job_name' => $jobName,
            ':payload' => $jsonPayload,
            ':error_message' => $errorMessage,
            ':error_trace' => $errorTrace
        ]);

        return (int)$this->pdo->lastInsertId();
    }

    /**
     * Get failed jobs for a batch
     *
     * @param int $batchId
     * @return array
     */
    public function getFailedJobsByBatch(int $batchId): array
    {
        $stmt = $this->pdo->prepare("SELECT id, batch_id, job_id, job_name, payload, error_message, error_trace, failed_at FROM {$this->table} WHERE batch_id = :batch_id ORDER BY id ASC");
        $stmt->execute([':batch_id' => $batchId]);
        $rows = $stmt->fetchAll(PDO::FETCH_ASSOC);

        // decode JSON payload
        foreach ($rows as &$r) {
            $r['payload'] = $r['payload'] !== null ? json_decode($r['payload'], true) : null;
        }

        return $rows;
    }

    /**
     * Count failed jobs for a batch
     *
     * @param int $batchId
     * @return int
     */
    public function countFailedByBatch(int $batchId): int
    {
        $stmt = $this->pdo->prepare("SELECT COUNT(*) FROM {$this->table} WHERE batch_id = :batch_id");
        $stmt->execute([':batch_id' => $batchId]);
        return (int)$stmt->fetchColumn();
    }
}
