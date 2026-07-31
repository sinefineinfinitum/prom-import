<?php

declare(strict_types=1);

namespace SineFine\PromImport\Application\Import;

use Psr\Log\LoggerInterface;
use SineFine\PromImport\Application\Import\Dto\ProductDto;
use SineFine\PromImport\Domain\Category\CategoryMapping;
use SineFine\PromImport\Domain\Common\OptionRepositoryInterface;
use SineFine\PromImport\Domain\Import\Import;
use SineFine\PromImport\Domain\Import\ImportRepositoryInterface;
use SineFine\PromImport\Domain\Queue\TaskQueueInterface;
use SineFine\PromImport\Infrastructure\Persistence\ImportProgressRepository;
use DateTime;

class ImportService
{
    private const OPTION_PREFIX = 'spss12_import_';
    private const CHECKER_HOOK = 'spss12-import-prom-woo_import_checker';

    public function __construct(
        private ImportRepositoryInterface $importRepository,
        private OptionRepositoryInterface $optionRepository,
        private LoggerInterface $logger,
        private ImportProgressRepository $progressRepository,
        private TaskQueueInterface $taskQueue,
        private XmlService $xmlService,
        private ImportBatchService $importBatchService,
    ) {
    }

    /**
     * @return Import[]
     */
    public function getAllImports(): array
    {
        return $this->importRepository->findAll();
    }

    public function createImport(string $name, string $url): int
    {
        $import = new Import(null, $name, $url);
        return $this->importRepository->save($import);
    }

    public function updateImport(int $id, string $name, string $url): bool
    {
        $import = $this->importRepository->findById($id);
        if (!$import) {
            return false;
        }

        $import->setName($name);
        $import->setUrl($url);
        $import->setUpdatedAt(new DateTime());

        $this->importRepository->save($import);
        return true;
    }

    public function deleteImport(int $id): bool
    {
        $this->optionRepository->deleteOption(self::OPTION_PREFIX . $id . '_status');
        $this->optionRepository->deleteOption(self::OPTION_PREFIX . $id . '_total');
        $this->optionRepository->deleteOption(self::OPTION_PREFIX . $id . '_imported');
        $this->optionRepository->deleteOption(self::OPTION_PREFIX . $id . '_offset');

        return $this->importRepository->delete($id);
    }

    public function getProgressRepository(): ImportProgressRepository
    {
        return $this->progressRepository;
    }

    public function getTaskQueue(): TaskQueueInterface
    {
        return $this->taskQueue;
    }

    /**
     * @return array<int, mixed>
     */
    public function getImportCategories(int $id): array
    {
        $import = $this->importRepository->findById($id);
        if (!$import) {
            return [];
        }
        return $import->getCategoryMapping()?->getMapping() ?? [];
    }

    /**
     * @param  int               $id
     * @param  array<int, mixed> $mapping
     * @return bool
     */
    public function updateImportMapping(int $id, array $mapping): bool
    {
        $import = $this->importRepository->findById($id);
        if (!$import) {
            return false;
        }

        $import->setCategoryMapping(new CategoryMapping($mapping));
        $import->setUpdatedAt(new DateTime());
        $this->importRepository->save($import);

        return true;
    }

    /**
     * @return array{success: bool, import_id: int, job_id: int|false}
     */
    public function runImport(int $id): array
    {
        $import = $this->importRepository->findById($id);
        if (!$import) {
            return [
                'success' => false,
                'import_id' => $id,
                'job_id' => false,
            ];
        }

        $this->optionRepository->updateOption(self::OPTION_PREFIX . $id . '_status', 'pending');
        $this->optionRepository->deleteOption(self::OPTION_PREFIX . $id . '_total');
        $this->optionRepository->deleteOption(self::OPTION_PREFIX . $id . '_imported');
        $this->optionRepository->deleteOption(self::OPTION_PREFIX . $id . '_offset');

        $xml = $this->xmlService->getXmlFromUrl($import->getUrl());
        $productDtos = $this->xmlService->getProductsFromXml($xml);
        $totalCount = count($productDtos);

        $this->progressRepository->create($id, $totalCount);

        $serialized = array_map(fn(ProductDto $dto) => $dto->toArray(), $productDtos);

        $mapping = $import->getCategoryMapping()?->getMapping() ?? [];

        $jobId = $this->importBatchService->import($id, $serialized, $mapping);

        $this->scheduleChecker();

        return [
            'success' => true,
            'import_id' => $id,
            'job_id' => $jobId,
        ];
    }

    public function runImportChecker(): void
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

        $this->scheduleChecker();
    }

    private function scheduleChecker(): void
    {
        if (!wp_next_scheduled(self::CHECKER_HOOK)) {
            wp_schedule_event(time(), 'spss12_import_checker', self::CHECKER_HOOK);
        }
    }
}
