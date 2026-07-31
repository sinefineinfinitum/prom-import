<?php

declare(strict_types=1);

namespace SineFine\PromImport\Infrastructure\Queue;

use SineFine\PromImport\Domain\Queue\TaskQueueInterface;

class ActionSchedulerTaskQueue implements TaskQueueInterface
{
    /**
     * @param string               $hook
     * @param array<string, mixed> $args
     * @param int                  $delay
     * @param string               $group
     */
    public function enqueue(string $hook, array $args, int $delay = 0, string $group = ''): int|string|false
    {
        $unique = $group !== '';

        if ($delay > 0) {
            // @phpstan-ignore function.notFound
            return as_schedule_single_action(time() + $delay, $hook, $args, $group, $unique);
        }

        // @phpstan-ignore function.notFound
        return as_enqueue_async_action($hook, $args, $group, $unique);
    }

    /**
     * @param string               $hook
     * @param array<string, mixed> $args
     * @param int                  $interval
     */
    public function scheduleRecurring(string $hook, array $args, int $interval): bool
    {
        // @phpstan-ignore function.notFound
        if (as_has_scheduled_action($hook, $args)) {
            return true;
        }

        // @phpstan-ignore function.notFound
        as_schedule_recurring_action(time(), $interval, $hook, $args);

        return true;
    }
}
