<?php
namespace System\Utilities\Scheduler;

use DateTime;
use DateTimeImmutable;
use DateTimeInterface;
use DateTimeZone;
use Exception;

/*
 * Scheduler - main API to register/manage jobs
 */
class Scheduler
{
    protected SchedulerStoreInterface $store;
    protected string $timezone;

    public function __construct(SchedulerStoreInterface $store = null, string $timezone = 'UTC')
    {
        $this->store = $store ?? new InMemoryStore();
        $this->timezone = $timezone;
    }

    public function register(string $name, string $expression, string $command, array $meta = [], bool $enabled = true): ScheduledJob
    {
        $job = new ScheduledJob([
            'name' => $name,
            'expression' => $expression,
            'command' => $command,
            'enabled' => $enabled,
            'meta' => $meta,
        ]);

        // compute next_run_at immediately
        $cron = new CronExpression($expression);
        $next = $cron->getNextRunDate(null, $this->timezone);
        $job->next_run_at = $next ? $next->format(DATE_ATOM) : null;

        return $this->store->insertJob($job);
    }

    public function update(ScheduledJob $job): ScheduledJob
    {
        // recompute next if expression changed or not set
        $cron = new CronExpression($job->expression);
        $after = $job->last_run_at ? new DateTimeImmutable($job->last_run_at) : null;
        $next = $cron->getNextRunDate($after, $this->timezone);
        $job->next_run_at = $next ? $next->format(DATE_ATOM) : null;
        return $this->store->updateJob($job);
    }

    public function remove(int $id): void
    {
        $this->store->deleteJob($id);
    }

    public function all(): array
    {
        return $this->store->allJobs();
    }

    public function findDueJobs(?DateTimeInterface $now = null): array
    {
        $now = $now ? DateTimeImmutable::createFromFormat(DateTime::ATOM, $now->format(DateTime::ATOM)) : new DateTimeImmutable('now', new DateTimeZone($this->timezone));
        $due = [];
        foreach ($this->store->allJobs() as $job) {
            if (!$job->enabled) continue;
            if ($job->next_run_at === null) continue;
            try {
                $nr = new DateTimeImmutable($job->next_run_at);
            } catch (Exception $e) {
                continue;
            }
            if ($nr <= $now) $due[] = $job;
        }
        return $due;
    }
}
