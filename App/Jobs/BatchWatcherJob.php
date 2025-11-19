<?php
namespace App\Jobs;

use System\Utilities\BatchFailedStore;
use System\Database\Database;
use PDO;

class BatchWatcherJob
{
    protected int $batchId;
    protected array $options;
    protected PDO $pdo;

    /**
     * $options may contain:
     * - 'then_callbacks' => array of callables or serialized callable metadata
     * - 'webhook' => optional webhook URL to POST summary to
     * - 'notify' => optional callable for notifications
     */
    public function __construct(int $batchId, array $options = [], ?PDO $pdo)
    {
        $this->batchId = $batchId;
        $this->options = $options;
        $this->pdo = $pdo ?? Database::getInstance()->getPDO();
    }

    public function handle()
    {
        $failedStore = new BatchFailedStore($this->pdo);

        $total = $this->getBatchTotalJobs($this->batchId);
        $failedJobs = $failedStore->getFailedJobsByBatch($this->batchId);
        $failedCount = count($failedJobs);

        $summary = [
            'batch_id' => $this->batchId,
            'total' => $total,
            'failed' => $failedCount,
            'failed_jobs' => $failedJobs,
            'finished_at' => date('c'),
        ];

        // run then callbacks (if provided)
        if (!empty($this->options['then_callbacks']) && is_array($this->options['then_callbacks'])) {
            foreach ($this->options['then_callbacks'] as $cb) {
                try {
                    if (is_callable($cb)) {
                        call_user_func($cb, $summary);
                    } elseif (is_string($cb) && function_exists($cb)) {
                        call_user_func($cb, $summary);
                    } elseif (is_array($cb) && isset($cb[0], $cb[1])) {
                        call_user_func($cb, $summary);
                    } else {
                        // attempt to unserialize or resolve via container if needed
                        // by default skip unknown callback types
                    }
                } catch (\Throwable $e) {
                    error_log("BatchWatcherJob: then-callback error: " . $e->getMessage());
                }
            }
        }

        // optionally POST to webhook
        if (!empty($this->options['webhook']) && filter_var($this->options['webhook'], FILTER_VALIDATE_URL)) {
            $this->postWebhook($this->options['webhook'], $summary);
        }

        // optionally notify (callable)
        if (!empty($this->options['notify']) && is_callable($this->options['notify'])) {
            try {
                call_user_func($this->options['notify'], $summary);
            } catch (\Throwable $e) {
                error_log("BatchWatcherJob notify error: " . $e->getMessage());
            }
        }

        // watcher completes
        return $summary;
    }

    protected function getBatchTotalJobs(int $batchId): int
    {
        // Adjust to how you store batch->jobs; example query against yt_batches or yt_batch_jobs
        $stmt = $this->pdo->prepare("SELECT COUNT(*) FROM yt_batch_jobs WHERE batch_id = :bid");
        $stmt->execute([':bid' => $batchId]);
        return (int)$stmt->fetchColumn();
    }

    protected function postWebhook(string $url, array $payload)
    {
        // simple curl POST (no blocking retries)
        $ch = curl_init($url);
        curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
        curl_setopt($ch, CURLOPT_POST, true);
        curl_setopt($ch, CURLOPT_HTTPHEADER, ['Content-Type: application/json']);
        curl_setopt($ch, CURLOPT_POSTFIELDS, json_encode($payload));
        curl_setopt($ch, CURLOPT_TIMEOUT, 5);
        $res = curl_exec($ch);
        if ($res === false) {
            error_log('BatchWatcherJob webhook error: ' . curl_error($ch));
        }
        curl_close($ch);
    }
}
