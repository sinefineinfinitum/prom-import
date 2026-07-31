<?php

declare(strict_types=1);

namespace SineFine\PromImport\Tests;

use PHPUnit\Framework\TestCase;
use SineFine\PromImport\Infrastructure\Queue\ActionSchedulerTaskQueue;

class ActionSchedulerTaskQueueTest extends TestCase
{
    private ActionSchedulerTaskQueue $queue;

    protected function setUp(): void
    {
        global $as_enqueued_actions;
        $as_enqueued_actions = [];

        $this->queue = new ActionSchedulerTaskQueue();
    }

    public function test_enqueue_calls_as_enqueue_async_action(): void
    {
        global $as_enqueued_actions;
        $as_enqueued_actions = [];

        $result = $this->queue->enqueue('test_hook', ['key' => 'value']);

        $this->assertNotFalse($result);
        $this->assertIsInt($result);
    }

    public function test_enqueue_with_delay_and_group(): void
    {
        $result = $this->queue->enqueue('test_hook', ['key' => 'value'], 30, 'test_group');

        $this->assertNotFalse($result);
    }

    public function test_enqueue_with_delay_schedules_single_action_in_future(): void
    {
        global $as_scheduled_single_actions;
        $as_scheduled_single_actions = [];

        $before = time();
        $this->queue->enqueue('test_hook', ['key' => 'value'], 30, 'test_group');

        $this->assertNotEmpty(
            $as_scheduled_single_actions,
            'A delayed task must be scheduled via as_schedule_single_action at a future timestamp'
        );

        $scheduled = $as_scheduled_single_actions[0];
        $this->assertSame('test_hook', $scheduled['hook']);
        $this->assertSame(['key' => 'value'], $scheduled['args']);
        $this->assertSame('test_group', $scheduled['group']);
        $this->assertGreaterThanOrEqual($before + 30, $scheduled['timestamp']);
    }

    public function test_enqueue_deduplicates_identical_batch(): void
    {
        global $as_enqueued_actions;
        $as_enqueued_actions = [];

        $this->queue->enqueue('test_hook', ['key' => 'value'], 0, 'test_group');
        $this->queue->enqueue('test_hook', ['key' => 'value'], 0, 'test_group');

        $this->assertCount(
            1,
            $as_enqueued_actions,
            'Enqueueing the same batch twice must not create a duplicate task'
        );
    }

    public function test_enqueue_delay_zero_does_not_imply_no_deduplication(): void
    {
        global $as_enqueued_actions;
        $as_enqueued_actions = [];

        $this->queue->enqueue('test_hook', ['key' => 'value'], 0, 'test_group');

        $this->assertCount(1, $as_enqueued_actions);
        $this->assertTrue(
            $as_enqueued_actions[0]['unique'],
            'Deduplication must not be tied to the delay value (FR-007)'
        );
    }

    public function test_scheduleRecurring_returns_true(): void
    {
        $result = $this->queue->scheduleRecurring('test_cron', [], 900);

        $this->assertTrue($result);
    }
}
