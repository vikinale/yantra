<?php
namespace System\Utilities\Scheduler;

/*
 * ScheduledJob - simple DTO
 */
class ScheduledJob
{
    public ?int $id;
    public string $name;
    public string $expression;
    public string $command; // arbitrary string command or callable identifier
    public bool $enabled;
    public ?string $last_run_at; // ISO8601
    public ?string $next_run_at; // ISO8601
    public int $attempts;
    public array $meta;

    public function __construct(array $data = [])
    {
        $this->id = $data['id'] ?? null;
        $this->name = $data['name'] ?? 'job';
        $this->expression = $data['expression'] ?? '* * * * *';
        $this->command = $data['command'] ?? '';
        $this->enabled = isset($data['enabled']) ? (bool)$data['enabled'] : true;
        $this->last_run_at = $data['last_run_at'] ?? null;
        $this->next_run_at = $data['next_run_at'] ?? null;
        $this->attempts = $data['attempts'] ?? 0;
        $this->meta = $data['meta'] ?? [];
    }

    public function toArray(): array
    {
        return [
            'id' => $this->id,
            'name' => $this->name,
            'expression' => $this->expression,
            'command' => $this->command,
            'enabled' => $this->enabled ? 1 : 0,
            'last_run_at' => $this->last_run_at,
            'next_run_at' => $this->next_run_at,
            'attempts' => $this->attempts,
            'meta' => json_encode($this->meta),
        ];
    }
}

/*
 * SchedulerStoreInterface - persistence abstraction
 */
interface SchedulerStoreInterface
{
    public function allJobs(): array; // returns array of ScheduledJob
    public function getJobById(int $id): ?ScheduledJob;
    public function insertJob(ScheduledJob $job): ScheduledJob;
    public function updateJob(ScheduledJob $job): ScheduledJob;
    public function deleteJob(int $id): void;
}

/*
 * InMemoryStore - lightweight store suitable for testing or single-process use
 */
class InMemoryStore implements SchedulerStoreInterface
{
    protected array $jobs = [];
    protected int $nextId = 1;

    public function allJobs(): array
    {
        return array_values($this->jobs);
    }

    public function getJobById(int $id): ?ScheduledJob
    {
        return $this->jobs[$id] ?? null;
    }

    public function insertJob(ScheduledJob $job): ScheduledJob
    {
        $id = $this->nextId++;
        $job->id = $id;
        $this->jobs[$id] = $job;
        return $job;
    }

    public function updateJob(ScheduledJob $job): ScheduledJob
    {
        if ($job->id === null) {
            return $this->insertJob($job);
        }
        $this->jobs[$job->id] = $job;
        return $job;
    }

    public function deleteJob(int $id): void
    {
        unset($this->jobs[$id]);
    }
}