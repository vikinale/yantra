<?php
namespace System\Utilities;

use System\Database\Database;
use PDO;
use Throwable;
use RuntimeException;

/**
 * Batch manager for queue jobs with:
 *  - then callback receives batch summary (configurable)
 *  - support for nested batches via a child-watcher job
 */
class Batch
{
    protected string $batchId;
    protected array $jobs;
    protected array $options;
    protected PDO $pdo;

    public function __construct(array $jobs, array $options = [])
    {
        $this->batchId = $options['batch_id'] ?? $this->generateId();
        $this->jobs = array_values($jobs);
        $this->options = $options;
        $this->pdo = Database::getInstance()->getPDO();
    }

    public static function create(array $jobs, array $options = []): Batch
    {
        return new self($jobs, $options);
    }

    protected function generateId(): string
    {
        return bin2hex(random_bytes(16));
    }

    /**
     * Dispatch the batch to the queue.
     *
     * Options:
     *  - queue (string) default 'default'
     *  - then (string|callable|array) job to run when batch completes.
     *        If provided as array you can control 'include_summary' boolean and 'data' array:
     *        e.g. ['job' => 'App\Jobs\AllDone@handle', 'data' => ['x'=>1], 'include_summary' => true]
     *  - metadata => arbitrary payload saved with the batch
     *  - max_tries => default per job
     */
    public function dispatch(string $queue = 'default'): string
    {
        $total = count($this->jobs);
        $thenJob = $this->options['then'] ?? null;
        $payloadMeta = $this->options['metadata'] ?? null;
        $maxTriesDefault = $this->options['max_tries'] ?? 3;

        $sql = "INSERT INTO `yt_batches` (`batch_id`,`total_jobs`,`pending`,`failed`,`status`,`payload`,`then_job`,`created_at`) VALUES (:id,:total,:pending,0,'running',:payload,:then,NOW())";
        $stmt = $this->pdo->prepare($sql);
        $stmt->execute([
            ':id' => $this->batchId,
            ':total' => $total,
            ':pending' => $total,
            ':payload' => $payloadMeta ? json_encode($payloadMeta, JSON_UNESCAPED_UNICODE|JSON_UNESCAPED_SLASHES) : null,
            ':then' => $thenJob ? (is_string($thenJob) ? $thenJob : json_encode($thenJob, JSON_UNESCAPED_UNICODE|JSON_UNESCAPED_SLASHES)) : null
        ]);

        foreach ($this->jobs as $entry) {
            // Allow nested batch shorthand:
            if (is_array($entry) && isset($entry['nested_batch']) && is_array($entry['nested_batch'])) {
                $childSpec = $entry['nested_batch'];
                $childJobs = $childSpec['jobs'] ?? [];
                $childOptions = $childSpec['options'] ?? [];
                $childBatch = Batch::create($childJobs, $childOptions);
                $childBatchId = $childBatch->dispatch($queue);

                $watcherPayload = [
                    'job' => BatchChildWatcher::class,
                    'data' => ['child_batch_id' => $childBatchId, 'parent_batch_id' => $this->batchId],
                    'attempts' => 0,
                    'max_tries' => $entry['max_tries'] ?? $maxTriesDefault,
                    'available_at' => time() + ($entry['delay'] ?? 0),
                    'created_at' => time(),
                    'batch_id' => $this->batchId, // important: associate this watcher with parent batch
                ];
                Queue::adapter()->push($queue, $watcherPayload);
                continue;
            }

            // Normal job handling
            $job = null;
            $data = [];
            $maxTries = $maxTriesDefault;
            $delay = 0;

            if (is_array($entry) && isset($entry['job'])) {
                $job = $entry['job'];
                $data = $entry['data'] ?? [];
                $maxTries = $entry['max_tries'] ?? $maxTries;
                $delay = $entry['delay'] ?? 0;
            } else {
                $job = $entry;
            }

            $payload = [
                'job' => $job,
                'data' => $data,
                'attempts' => 0,
                'max_tries' => $maxTries,
                'available_at' => time() + max(0, (int)$delay),
                'created_at' => time(),
                'batch_id' => $this->batchId
            ];

            // If job is an array with explicit id/name you may include them in payload to help markJobFailed
            // e.g. $payload['job_id'] = $entry['id'] ?? null; $payload['job_name'] = is_string($job) ? $job : get_class($job);

            Queue::adapter()->push($queue, $payload);
        }

        // SMART MODE: append BatchWatcher as the last job so it runs only after all other jobs
        // The BatchWatcher will build the summary and dispatch 'then' job (or call webhook/notify)
        $watcherPayload = [
            'job' => \System\Utilities\BatchWatcher::class, // you should create this class (see notes below)
            'data' => [
                'batch_id' => $this->batchId,
                // pass any watcher options you like: webhook, notify callable name, etc.
                'watcher_options' => $this->options['watcher_options'] ?? []
            ],
            'attempts' => 0,
            'max_tries' => $this->options['watcher_max_tries'] ?? 3,
            'available_at' => time(),
            'created_at' => time(),
            'batch_id' => $this->batchId,
        ];
        Queue::adapter()->push($queue, $watcherPayload);

        return $this->batchId;
    }


    public function id(): string { return $this->batchId; }

    public function refresh(): array
    {
        $stmt = $this->pdo->prepare("SELECT `batch_id`,`total_jobs`,`pending`,`failed`,`status`,`payload`,`then_job`,`created_at`,`completed_at` FROM `yt_batches` WHERE `batch_id` = :id LIMIT 1");
        $stmt->execute([':id' => $this->batchId]);
        $row = $stmt->fetch(PDO::FETCH_ASSOC);
        return $row ?: [];
    }
            
    public static function markJobFailed(string $batchId, array $failedPayload = null, ?string $errorMessage = null, ?\Throwable $throwable = null): void
    {
        $pdo = Database::getInstance()->getPDO();
        $pdo->beginTransaction();
        try {
            // increment failed, decrement pending
            $upd = $pdo->prepare("UPDATE `yt_batches` SET `failed` = `failed` + 1, `pending` = GREATEST(0, `pending` - 1) WHERE `batch_id` = :id");
            $upd->execute([':id' => $batchId]);

            // store failed payload details for later inspection & reporting (new table schema)
            if ($failedPayload !== null) {
                // attempt to extract job_id and job_name if provided in payload
                $jobId = 0;
                $jobName = '';
                if (isset($failedPayload['job_id'])) {
                    $jobId = (int)$failedPayload['job_id'];
                }
                if (isset($failedPayload['job_name'])) {
                    $jobName = (string)$failedPayload['job_name'];
                } elseif (isset($failedPayload['job']) && is_string($failedPayload['job'])) {
                    $jobName = (string)$failedPayload['job'];
                } elseif (isset($failedPayload['job']) && is_object($failedPayload['job'])) {
                    $jobName = get_class($failedPayload['job']);
                }

                $jsonPayload = json_encode($failedPayload, JSON_UNESCAPED_UNICODE|JSON_UNESCAPED_SLASHES);

                $trace = $throwable ? $throwable->getTraceAsString() : null;

                $ins = $pdo->prepare("
                    INSERT INTO `yt_batch_failed` (`batch_id`, `job_id`, `job_name`, `payload`, `error_message`, `error_trace`, `failed_at`)
                    VALUES (:bid, :job_id, :job_name, :payload, :err, :trace, NOW())
                ");
                $ins->execute([
                    ':bid' => $batchId,
                    ':job_id' => $jobId,
                    ':job_name' => $jobName,
                    ':payload' => $jsonPayload,
                    ':err' => $errorMessage,
                    ':trace' => $trace
                ]);
            }

            // check pending count
            $sel = $pdo->prepare("SELECT `pending` FROM `yt_batches` WHERE `batch_id` = :id LIMIT 1");
            $sel->execute([':id' => $batchId]);
            $row = $sel->fetch(PDO::FETCH_ASSOC);
            if ($row && (int)$row['pending'] <= 0) {
                // mark failed/completed depending on failed count
                // if there are any failed count > 0 we'll set status to 'failed' for clarity;
                // however the watcher (BatchWatcher) will handle final summary/then dispatch.
                $upd2 = $pdo->prepare("UPDATE `yt_batches` SET `status` = 'failed', `completed_at` = NOW() WHERE `batch_id` = :id");
                $upd2->execute([':id' => $batchId]);
            }

            $pdo->commit();
        } catch (Throwable $e) {
            try { $pdo->rollBack(); } catch (Throwable $_) {}
            Log::error("Batch::markJobFailed DB error: " . $e->getMessage(), ['batch' => $batchId]);
        }
    }

    public static function markJobSuccess(string $batchId): void
    {
        $pdo = Database::getInstance()->getPDO();
        $pdo->beginTransaction();
        try {
            $upd = $pdo->prepare("UPDATE `yt_batches` SET `pending` = GREATEST(0, `pending` - 1) WHERE `batch_id` = :id");
            $upd->execute([':id' => $batchId]);

            $sel = $pdo->prepare("SELECT `pending`,`failed`,`then_job`,`total_jobs`,`payload`,`created_at` FROM `yt_batches` WHERE `batch_id` = :id LIMIT 1");
            $sel->execute([':id' => $batchId]);
            $row = $sel->fetch(PDO::FETCH_ASSOC);

            if ($row) {
                $pending = (int)$row['pending'];
                $failed = (int)$row['failed'];
                $thenJob = $row['then_job'] ?? null;
                $total = (int)$row['total_jobs'];
                $payloadMeta = $row['payload'] ? json_decode($row['payload'], true) : null;
                $createdAt = $row['created_at'] ?? null;

                if ($pending <= 0) {
                    // collect failed payloads for summary from new table schema
                    $failedRows = [];
                    $fr = $pdo->prepare("SELECT `id`,`job_id`,`job_name`,`payload`,`error_message`,`error_trace`,`failed_at` FROM `yt_batch_failed` WHERE `batch_id` = :id ORDER BY `id` ASC");
                    $fr->execute([':id' => $batchId]);
                    while ($frow = $fr->fetch(PDO::FETCH_ASSOC)) {
                        $decodedPayload = null;
                        try {
                            $decodedPayload = $frow['payload'] !== null ? json_decode($frow['payload'], true) : null;
                        } catch (Throwable $_) {
                            $decodedPayload = $frow['payload'];
                        }
                        $failedRows[] = [
                            'id' => (int)$frow['id'],
                            'job_id' => (int)$frow['job_id'],
                            'job_name' => $frow['job_name'],
                            'payload' => $decodedPayload,
                            'error' => $frow['error_message'],
                            'error_trace' => $frow['error_trace'],
                            'failed_at' => $frow['failed_at']
                        ];
                    }

                    // mark completed (we consider completed even if some jobs failed)
                    $stmt = $pdo->prepare("UPDATE `yt_batches` SET `status` = 'completed', `completed_at` = NOW() WHERE `batch_id` = :id");
                    $stmt->execute([':id' => $batchId]);

                    // build summary - include failed payloads
                    $summary = [
                        'batch_id' => $batchId,
                        'total_jobs' => $total,
                        'failed' => $failed,
                        'status' => 'completed',
                        'payload' => $payloadMeta,
                        'created_at' => $createdAt,
                        'completed_at' => (new \DateTime())->format('Y-m-d H:i:s'),
                        'failed_jobs' => $failedRows,
                    ];

                    // dispatch 'then' job if present; support the same formats as before
                    if ($thenJob) {
                        $decoded = @json_decode($thenJob, true);
                        $jobSpec = $decoded !== null ? $decoded : $thenJob;

                        if (is_array($jobSpec) && isset($jobSpec['job'])) {
                            $data = $jobSpec['data'] ?? [];
                            if (!empty($jobSpec['include_summary'])) {
                                $data['batch'] = $summary;
                            }
                            Queue::push($jobSpec['job'], $data, 'default', $jobSpec['max_tries'] ?? 3);
                        } elseif (is_string($jobSpec)) {
                            Queue::push($jobSpec, ['batch' => $summary], 'default', 3);
                        } elseif (is_callable($jobSpec)) {
                            Queue::push($jobSpec, ['batch' => $summary], 'default', 3);
                        } else {
                            Queue::push($jobSpec, ['batch' => $summary], 'default', 3);
                        }
                    }
                }
            }

            $pdo->commit();
        } catch (Throwable $e) {
            try { $pdo->rollBack(); } catch (Throwable $_) {}
            Log::error("Batch::markJobSuccess DB error: " . $e->getMessage(), ['batch' => $batchId]);
        }
    }

}

/**
 * Worker job used to wait for a child batch to complete.
 * When the child batch is not yet finished, this job throws -> worker will requeue (and backoff) it.
 *
 * Usage payload:
 *  - data: ['child_batch_id' => '...', 'parent_batch_id' => '...']
 */
class BatchChildWatcher implements \System\Utilities\QueueJobInterface
{
    /**
     * Handle: checks child batch status. If not finished, throw to requeue.
     * If finished successfully (completed or failed), return (job done).
     */
    public function handle(array $data): void
    {
        $childId = $data['child_batch_id'] ?? null;
        $parentId = $data['parent_batch_id'] ?? null;
        if (!$childId) throw new \RuntimeException("BatchChildWatcher missing child_batch_id");

        // check batch status
        $pdo = Database::getInstance()->getPDO();
        $stmt = $pdo->prepare("SELECT `status` FROM `yt_batches` WHERE `batch_id` = :id LIMIT 1");
        $stmt->execute([':id' => $childId]);
        $row = $stmt->fetch(PDO::FETCH_ASSOC);

        if (!$row) {
            // child not found - treat as failed/completed? we'll throw to retry (or mark parent failed optionally)
            throw new \RuntimeException("Child batch {$childId} not found yet");
        }

        $status = $row['status'] ?? 'pending';
        if ($status === 'pending' || $status === 'running') {
            // still running — throw so worker will requeue (Queue worker will re-enqueue with backoff)
            throw new \RuntimeException("Child batch {$childId} not finished yet");
        }

        // if child finished (completed or failed), we consider watcher done.
        // Optionally: you can mark parent batch job success here so parent pending decrements once child completes.
        if ($parentId) {
            // mark parent's single job as success (this watcher counts as one job in parent)
            Batch::markJobSuccess((string)$parentId);
        }

        // done
        return;
    }
}