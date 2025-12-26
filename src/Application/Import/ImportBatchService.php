<?php

declare(strict_types=1);

namespace SineFine\PromImport\Application\Import;

use SineFine\PromImport\Application\Import\Dto\ProductDto;
use SineFine\PromImport\Domain\Queue\TaskQueueInterface;
use SineFine\PromImport\Domain\Product\ValueObject\Price;
use SineFine\PromImport\Domain\Product\ValueObject\Sku;

class ImportBatchService
{
    public const HOOK_PROCESS_BATCH = 'spss12_prom_import_process_batch';

    public function __construct(
        private ImportService $importService,
        private TaskQueueInterface $queue
    ) {
    }

    /**
     * Enqueue DTOs to be processed in batches.
     * @param ProductDto[] $dtos
     * @param int $batchSize
     * @param int $delayBetweenBatches seconds
     * @return array{batches:int, first_job:string|int}
     */
    public function enqueue(array $dtos, int $batchSize = 20, int $delayBetweenBatches = 0): array
    {
        $batches = array_chunk($dtos, max(1, $batchSize));
        $firstJob = 0;
        $delay = 0;
        foreach ($batches as $i => $batch) {
            $payload = array_map([$this, 'dtoToArray'], $batch);
            $key = 'batch_' . md5(json_encode($payload));
            $jobId = $this->queue->enqueue(self::HOOK_PROCESS_BATCH, ['items' => $payload], $delay, $key);
            if ($i === 0) {
                $firstJob = $jobId;
            }
            $delay += max(0, $delayBetweenBatches);
        }
        return ['batches' => count($batches), 'first_job' => $firstJob];
    }

    /**
     * Scheduled handler: process one batch of items.
     *
     * @param array $args ['items' => array[]]
     */
    public function handleScheduledBatch(array $args): void
    {
        $items = $args['items'] ?? [];
        if (!is_array($items)) {
            return;
        }
        foreach ($items as $item) {
            $dto = $this->arrayToDto($item);
            $this->importService->importProductFromDto($dto);
        }
    }

    private function dtoToArray(ProductDto $dto): array
    {
        return [
            'sku' => $dto->sku->value(),
            'title' => $dto->title,
            'description' => $dto->description,
            'price' => $dto->price->amount(),
            'categoryId' => $dto->category?->id,
            'categoryName' => $dto->category?->name,
            'tags' => $dto->tags,
            'mediaUrls' => $dto->mediaUrls,
            'link' => $dto->link,
        ];
    }

    private function arrayToDto(array $data): ProductDto
    {
        $sku = new Sku((int) ($data['sku'] ?? 0));
        $price = new Price((float) ($data['price'] ?? 0));
        // For brevity, we don't reconstruct CategoryDto here (optional)
        return new ProductDto(
            $sku,
            (string) ($data['title'] ?? ''),
            (string) ($data['description'] ?? ''),
            $price,
            null,
            is_array($data['tags'] ?? null) ? $data['tags'] : [],
            is_array($data['mediaUrls'] ?? null) ? $data['mediaUrls'] : [],
            (string) ($data['link'] ?? '')
        );
    }
}
