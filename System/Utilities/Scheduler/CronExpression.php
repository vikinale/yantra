<?php
namespace System\Utilities\Scheduler;

use DateTime;
use DateTimeImmutable;
use DateTimeInterface;
use DateTimeZone;
use Exception;

/*
 * CronExpression
 *
 * Minimal cron parser (5 fields: minute hour day month weekday).
 * Supports: * , - / (lists, ranges, steps, wildcard)
 *
 * Important: compute next run AFTER a given DateTime (exclusive)
 */
class CronExpression
{
    protected array $fields = []; // minute,hour,day,month,weekday

    public function __construct(string $expression)
    {
        $parts = preg_split('/\s+/', trim($expression));
        if (count($parts) !== 5) {
            throw new \InvalidArgumentException("Cron expression must have 5 fields (min hour day month weekday)");
        }
        $this->fields = [
            'minute' => $this->parseField($parts[0], 0, 59),
            'hour' => $this->parseField($parts[1], 0, 23),
            'day' => $this->parseField($parts[2], 1, 31),
            'month' => $this->parseField($parts[3], 1, 12),
            'weekday' => $this->parseField($parts[4], 0, 6), // 0=Sun .. 6=Sat
        ];
    }

    protected function parseField(string $expr, int $min, int $max): array
    {
        // returns sorted unique ints allowed
        $expr = trim($expr);
        if ($expr === '*' || $expr === '?') {
            return range($min, $max);
        }

        $result = [];
        foreach (explode(',', $expr) as $part) {
            // handle step
            if (strpos($part, '/') !== false) {
                [$rangePart, $step] = explode('/', $part, 2);
                $step = (int)$step;
            } else {
                $rangePart = $part;
                $step = 1;
            }

            if ($rangePart === '*' || $rangePart === '') {
                $start = $min;
                $end = $max;
            } elseif (strpos($rangePart, '-') !== false) {
                [$start, $end] = explode('-', $rangePart, 2);
                $start = (int)$start;
                $end = (int)$end;
            } else {
                $start = (int)$rangePart;
                $end = (int)$rangePart;
            }

            // normalize bounds
            $start = max($min, $start);
            $end = min($max, $end);

            if ($step <= 1) {
                for ($i = $start; $i <= $end; $i++) $result[] = $i;
            } else {
                for ($i = $start; $i <= $end; $i += $step) $result[] = $i;
            }
        }

        $result = array_values(array_unique($result));
        sort($result, SORT_NUMERIC);
        return $result;
    }

    /**
     * Get next run date (exclusive): strictly after $after
     *
     * @param DateTimeInterface|null $after (default now)
     * @param string|null $timezone name like 'UTC' or 'Asia/Kolkata'
     * @return DateTimeImmutable|null next run date or null if not found within reasonable limit
     */
    public function getNextRunDate(?DateTimeInterface $after = null, ?string $timezone = null): ?DateTimeImmutable
    {
        $after = $after ? DateTimeImmutable::createFromFormat(DateTime::ATOM, $after->format(DateTime::ATOM)) : new DateTimeImmutable('now');
        if ($timezone) {
            $after = $after->setTimezone(new DateTimeZone($timezone));
        }
        // Start searching from next minute
        $cursor = $after->modify('+1 minute')->setTime((int)$after->format('H'), (int)$after->format('i'), 0);

        // safety limit: search up to 5 years ahead to avoid infinite loops
        $limit = $cursor->modify('+5 years');

        while ($cursor <= $limit) {
            $m = (int)$cursor->format('i');
            $h = (int)$cursor->format('G'); // 0-23
            $d = (int)$cursor->format('j'); // 1-31
            $mon = (int)$cursor->format('n'); // 1-12
            $w = (int)$cursor->format('w'); // 0-6

            if (in_array($m, $this->fields['minute'], true)
                && in_array($h, $this->fields['hour'], true)
                && in_array($d, $this->fields['day'], true)
                && in_array($mon, $this->fields['month'], true)
                && in_array($w, $this->fields['weekday'], true)
            ) {
                return $cursor;
            }

            // advance cursor smartly: increase by 1 minute (simple but safe)
            $cursor = $cursor->modify('+1 minute');
        }

        return null;
    }
}
