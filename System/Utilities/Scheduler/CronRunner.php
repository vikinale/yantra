<?php 
namespace System\Utilities\Scheduler;

use DateTime;
use DateTimeImmutable;
use DateTimeInterface;
use DateTimeZone;
use Exception;

/*
 * CronRunner - executes due jobs
 *
 * Run from CLI via system cron (every minute) or invoked by long-running process.
 * Execution of job 'command' is intentionally pluggable: you can interpret command
 * as one of:
 *   - shell command (exec)
 *   - PHP callable reference like "Class::method" (static) or "function_name"
 *   - a job name that you map to a callable via a resolver (see onExecute callback)
 */
class CronRunner
{
    protected Scheduler $scheduler;
    protected string $timezone;
    /** @var callable|null function(ScheduledJob $job): bool — return true on success */
    protected $onExecute;

    public function __construct(Scheduler $scheduler, string $timezone = 'UTC', callable $onExecute = null)
    {
        $this->scheduler = $scheduler;
        $this->timezone = $timezone;
        $this->onExecute = $onExecute;
    }

    /**
     * Run due jobs (non-blocking per job: execute synchronously; tune as needed)
     *
     * @return array summary of runs: [ ['job' => ScheduledJob, 'success' => bool, 'output' => string|null, 'error' => string|null], ... ]
     */
    public function runDueJobs(): array
    {
        $now = new DateTimeImmutable('now', new DateTimeZone($this->timezone));
        $due = $this->scheduler->findDueJobs($now);
        $results = [];

        foreach ($due as $job) {
            $result = ['job' => $job, 'success' => false, 'output' => null, 'error' => null];
            try {
                $success = $this->executeJob($job, $output, $error);
                $result['success'] = $success;
                $result['output'] = $output;
                $result['error'] = $error;
            } catch (\Throwable $t) {
                $result['error'] = $t->getMessage();
                $result['success'] = false;
            }

            // update job metadata and reschedule
            $job->last_run_at = $now->format(DATE_ATOM);
            $job->attempts = ($job->attempts ?? 0) + 1;

            // compute next run date based on expression and last_run_at
            try {
                $cron = new CronExpression($job->expression);
                $next = $cron->getNextRunDate($now, $this->timezone);
                $job->next_run_at = $next ? $next->format(DATE_ATOM) : null;
            } catch (\Throwable $e) {
                $job->next_run_at = null;
            }

            $this->scheduler->update($job);
            $results[] = $result;
        }

        return $results;
    }

    /**
     * Interpret and execute job. Returns success boolean. Writes $output and $error text.
     */
    protected function executeJob(ScheduledJob $job, ?string &$output = null, ?string &$error = null): bool
    {
        $output = null;
        $error = null;

        // If a custom onExecute handler is provided, call it
        if (is_callable($this->onExecute)) {
            $res = call_user_func($this->onExecute, $job);
            if (is_array($res)) {
                $output = $res['output'] ?? null;
                $error = $res['error'] ?? null;
                return (bool)($res['success'] ?? false);
            }
            return (bool)$res;
        }

        // Default behaviour: interpret command string
        $cmd = trim($job->command);
        if ($cmd === '') {
            $error = 'Empty command';
            return false;
        }

        // 1) If command is a shell command prefixed with "sh:" or "bash:" or contains spaces -> exec
        if (preg_match('#^(sh:|bash:)#', $cmd) || preg_match('/\s+/', $cmd)) {
            $shellCmd = preg_replace('#^(sh:|bash:)#', '', $cmd);
            // execute and capture output + exit code
            $descriptors = [
                1 => ['pipe', 'w'],
                2 => ['pipe', 'w'],
            ];
            $proc = proc_open($shellCmd, $descriptors, $pipes);
            if (!is_resource($proc)) {
                $error = 'Failed to launch shell command';
                return false;
            }
            $out = stream_get_contents($pipes[1]);
            $err = stream_get_contents($pipes[2]);
            fclose($pipes[1]);
            fclose($pipes[2]);
            $code = proc_close($proc);
            $output = $out;
            $error = $err ?: ($code !== 0 ? "Exit code {$code}" : null);
            return $code === 0;
        }

        // 2) If command looks like Class::method or function_name
        if (strpos($cmd, '::') !== false) {
            [$class, $method] = explode('::', $cmd, 2);
            if (class_exists($class) && method_exists($class, $method)) {
                try {
                    $res = call_user_func([$class, $method], $job);
                    $output = is_string($res) ? $res : json_encode($res);
                    return true;
                } catch (\Throwable $t) {
                    $error = $t->getMessage();
                    return false;
                }
            } else {
                $error = "Callable {$cmd} not found";
                return false;
            }
        }

        if (function_exists($cmd)) {
            try {
                $res = call_user_func($cmd, $job);
                $output = is_string($res) ? $res : json_encode($res);
                return true;
            } catch (\Throwable $t) {
                $error = $t->getMessage();
                return false;
            }
        }

        // 3) Fallback: treat as shell command
        $out = null;
        $code = null;
        exec($cmd . ' 2>&1', $lines, $code);
        $output = implode("\n", $lines);
        if ($code !== 0) {
            $error = "Exit code {$code}";
            return false;
        }
        return true;
    }
}