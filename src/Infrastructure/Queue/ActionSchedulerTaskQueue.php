<?php

declare(strict_types=1);

namespace SineFine\PromImport\Infrastructure\Queue;

use SineFine\PromImport\Domain\Queue\TaskQueueInterface;

class ActionSchedulerTaskQueue implements TaskQueueInterface
{
    public function enqueue(string $hook, array $args = [], int $delay = 0, ?string $uniqueKey = null): string|int
    {
        $timestamp = time() + max(0, $delay);

        // Action Scheduler available
        if (function_exists('as_schedule_single_action')) {
            // Prevent duplicates if unique key provided
            if ($uniqueKey && function_exists('as_has_scheduled_action')) {
                if (as_has_scheduled_action($hook, $args)) {
                    return $uniqueKey; // treat as already scheduled
                }
            }
            return (int) as_schedule_single_action($timestamp, $hook, [$args], 'spss12-prom-import');
        }

        // Fallback to WP-Cron
        if ($uniqueKey) {
            $next = wp_next_scheduled($hook, [$args]);
            if ($next) {
                return $uniqueKey;
            }
        }

        return (int) wp_schedule_single_event($timestamp, $hook, [$args]);
    }
}
