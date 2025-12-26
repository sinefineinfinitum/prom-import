<?php

declare(strict_types=1);

namespace SineFine\PromImport\Domain\Queue;

interface TaskQueueInterface
{
    /**
     * Enqueue a single action to be executed in background.
     *
     * @param string $hook   Action hook name to dispatch.
     * @param array $args    Payload to pass to the handler.
     * @param int $delay     Delay in seconds before execution (default immediate).
     * @param string|null $uniqueKey Optional idempotency key to avoid duplicates.
     * @return string|int Identifier returned by the queue backend when available.
     */
    public function enqueue(string $hook, array $args = [], int $delay = 0, ?string $uniqueKey = null): string|int;
}
