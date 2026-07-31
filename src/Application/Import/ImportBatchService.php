<?php

declare(strict_types=1);

namespace SineFine\PromImport\Application\Import;

use Psr\Log\LoggerInterface;
use SineFine\PromImport\Application\Import\Dto\ProductDto;
use SineFine\PromImport\Domain\Product\ProductManagerInterface;
use SineFine\PromImport\Domain\Queue\TaskQueueInterface;
use SineFine\PromImport\Infrastructure\Persistence\ImportProgressRepository;
use Throwable;

class ImportBatchService
{
    private const DEFAULT_BATCH_SIZE = 20;
    private const DEFAULT_DELAY = 0;

    private int $batchSize;
    private int $delayBetweenBatches;

    public function __construct(
        private TaskQueueInterface $taskQueue,
        private ImportProgressRepository $progressRepository,
        private ProductManagerInterface $productManager,
        private LoggerInterface $logger,
        ?int $batchSize = null,
        ?int $delayBetweenBatches = null,
    ) {
        $this->batchSize = $batchSize ?? self::DEFAULT_BATCH_SIZE;
        $this->delayBetweenBatches = $delayBetweenBatches ?? self::DEFAULT_DELAY;
    }

    public function getBatchSize(): int
    {
        return $this->batchSize;
    }

    public function setBatchSize(int $batchSize): void
    {
        $this->batchSize = $batchSize;
    }

    public function getDelayBetweenBatches(): int
    {
        return $this->delayBetweenBatches;
    }

    public function setDelayBetweenBatches(int $delayBetweenBatches): void
    {
        $this->delayBetweenBatches = $delayBetweenBatches;
    }

    /**
     * @param int                              $importId
     * @param array<int, array<string, mixed>> $serializedDtos
     * @param array<int|string, int|string>    $categoryMapping
     */
    public function import(int $importId, array $serializedDtos, array $categoryMapping = []): int|false
    {
        $batchSize = max(1, $this->batchSize);
        $chunks = array_chunk($serializedDtos, $batchSize);
        $lastJobId = false;

        foreach ($chunks as $index => $chunk) {
            $delay = $this->delayBetweenBatches * $index;
            $group = "import_{$importId}_batch_" . ($index * $batchSize);

            $jobId = $this->taskQueue->enqueue(
                'spss12-import-prom-woo_queue_run_batch',
                [
                    'import_id' => $importId,
                    'offset' => $index * $batchSize,
                    'products' => $chunk,
                    'categoryMapping' => $categoryMapping,
                ],
                $delay,
                $group
            );

            if ($jobId !== false) {
                $lastJobId = is_int($jobId) ? $jobId : 0;
            }
        }

        return $lastJobId;
    }

    /**
     * @param array<string, mixed> $args
     */
    public function process(array $args): void
    {
        $importId = (int) ($args['import_id'] ?? 0);
        $products = $args['products'] ?? [];
        $categoryMapping = is_array($args['categoryMapping'] ?? null) ? $args['categoryMapping'] : [];

        if ($importId === 0 || !is_array($products) || empty($products)) {
            return;
        }

        $this->progressRepository->markRunning($importId);

        $imported = 0;

        foreach ($products as $productData) {
            try {
                $dto = ProductDto::fromArray($productData);
            } catch (Throwable $exception) {
                $this->logger->warning(
                    'Skipping invalid product payload: {error}',
                    ['error' => $exception->getMessage()]
                );
                continue;
            }

            $productId = $this->productManager->createProductFromDto($dto);

            if (is_wp_error($productId)) {
                continue;
            }

            $this->productManager->addImagesToProductGallery($dto, $productId);

            $externalCatId = $dto->category !== null ? (int) $dto->category->id() : 0;
            if ($externalCatId > 0 && isset($categoryMapping[$externalCatId])) {
                $wooTermId = (int) $categoryMapping[$externalCatId];
                if ($wooTermId > 0) {
                    $this->productManager->addCategoryToProduct($productId, $wooTermId);
                }
            }

            $imported++;
        }

        $this->progressRepository->atomicAdvance($importId, $imported, count($products));

        $progress = $this->progressRepository->findByImportId($importId);
        if ($progress && (int) $progress['offset'] >= (int) $progress['total']) {
            $this->progressRepository->markCompleted($importId);
        }
    }
}
