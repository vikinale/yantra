<?php
namespace System\Utilities;

use System\Database\Database;
use Throwable;
use RuntimeException;

/**
 * BatchWatcher - polls a child batch until it's finished.
 *
 * Behavior:
 *  - If the child batch is still running, the watcher *releases* itself with
 *    exponential backoff + random jitter. This avoids throwing exceptions,
 *    keeps the worker clean, and allows the watcher to control the exact delay.
 *
 * Expected payload (data):
 *  - child_batch_id (required)
 *  - parent_batch_id (optional) -> if present, the watcher will call Batch::markJobSuccess(parent) when child completes
 *  - base_delay (int seconds) default 5
 *  - max_delay (int seconds) default 3600
 */
class BatchWatcher implements QueueJobInterface
{
    public function handle(array $data): void
    {
        $childId = $data['child_batch_id'] ?? null;
        $parentId = $data['parent_batch_id'] ?? null;
        $base = isset($data['base_delay']) ? max(1, (int)$data['base_delay']): 5;
        $maxDelay = isset($data['max_delay']) ? max(1, (int)$data['max_delay']): 3600;
        $attempts = isset($data['attempts']) ? (int)$data['attempts'] : 0;

        if (!$childId) throw new RuntimeException("BatchWatcher: missing child_batch_id");

        $pdo = Database::getInstance()->getPDO();
        $stmt = $pdo->prepare("SELECT `status` FROM `yt_batches` WHERE `batch_id` = :id LIMIT 1");
        $stmt->execute([':id' => $childId]);
        $row = $stmt->fetch(\PDO::FETCH_ASSOC);

        if (!$row) {
            // child not yet registered; schedule retry with small delay
            $this->reschedule($data, $attempts, $base, $maxDelay);
            return;
        }

        $status = $row['status'] ?? 'pending';

        if ($status === 'pending' || $status === 'running') {
            // not finished -> reschedule with exponential backoff + jitter
            $this->reschedule($data, $attempts, $base, $maxDelay);
            return;
        }

        // If child completed (either completed or failed) then notify the parent (if any)
        if ($parentId) {
            if ($status === 'completed') {
                Batch::markJobSuccess((string)$parentId);
            } else {
                // if child finished with status 'failed' treat parent watcher as failed job
                Batch::markJobFailed((string)$parentId);
            }
        }

        // watcher done
        return;
    }

    protected function reschedule(array $data, int $attempts, int $base, int $maxDelay): void
    {
        $attempts++;
        // exponential backoff with jitter
        $backoff = (int) (min($maxDelay, ($base * (int)pow(2, max(0, $attempts - 1)))));
        // jitter up to 30% of backoff
        $jitterMax = (int) max(1, floor($backoff * 0.3));
        try {
            $jitter = random_int(0, $jitterMax);
        } catch (Throwable $_) {
            $jitter = mt_rand(0, $jitterMax);
        }
        $delay = $backoff + $jitter;

        // Update attempts and use Queue adapter to release with computed delay.
        $data['attempts'] = $attempts;

        // When called from worker we should have access to the payload (including internal _db_id if DB adapter)
        // It's safer to ask the Queue adapter to requeue using the provided payload structure:
        Queue::adapter()->release($data, $delay);
        // Do not throw — release() puts job back with requested delay.
    }
}
