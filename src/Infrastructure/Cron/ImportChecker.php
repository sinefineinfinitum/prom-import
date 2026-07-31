<?php

declare(strict_types=1);

namespace SineFine\PromImport\Infrastructure\Cron;

use Psr\Log\LoggerInterface;
use SineFine\PromImport\Domain\Queue\TaskQueueInterface;
use SineFine\PromImport\Infrastructure\Persistence\ImportProgressRepository;

class ImportChecker
{
    private const CHECKER_HOOK = 'spss12-import-prom-woo_import_checker';

    public function __construct(
        private ImportProgressRepository $progressRepository,
        private TaskQueueInterface $taskQueue,
        private LoggerInterface $logger,
    ) {
    }

    public function run(): void
    {
        $stuck = $this->progressRepository->findStuck();

        foreach ($stuck as $progress) {
            $importId = (int) $progress['import_id'];

            $this->logger->warning(
                'Import {import_id} appears stalled at offset {offset}/{total}. Re-scheduling.',
                ['import_id' => $importId, 'offset' => $progress['offset'], 'total' => $progress['total']]
            );

            $this->taskQueue->enqueue(
                'spss12-import-prom-woo_queue_run_batch',
                ['import_id' => $importId, 'offset' => (int) $progress['offset']]
            );
        }

        $this->scheduleNext();
    }

    private function scheduleNext(): void
    {
        if (!wp_next_scheduled(self::CHECKER_HOOK)) {
            wp_schedule_event(time(), 'spss12_import_checker', self::CHECKER_HOOK);
        }
    }
}
