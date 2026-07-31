<?php

declare(strict_types=1);

namespace SineFine\PromImport\Domain\Queue;

interface TaskQueueInterface
{
    /**
     * @param string               $hook
     * @param array<string, mixed> $args
     * @param int                  $delay
     * @param string               $group
     */
    public function enqueue(string $hook, array $args, int $delay = 0, string $group = ''): int|string|false;

    /**
     * @param string               $hook
     * @param array<string, mixed> $args
     * @param int                  $interval
     */
    public function scheduleRecurring(string $hook, array $args, int $interval): bool;
}
