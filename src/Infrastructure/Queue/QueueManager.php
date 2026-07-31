<?php

declare(strict_types=1);

namespace SineFine\PromImport\Infrastructure\Queue;

use SineFine\PromImport\Application\Import\Dto\ProductDto;
use SineFine\PromImport\Application\Import\ImportBatchService;
use SineFine\PromImport\Application\Import\XmlService;
use SineFine\PromImport\Domain\Exception\ImportNotFoundException;
use SineFine\PromImport\Domain\Import\ImportRepositoryInterface;

class QueueManager
{
    private const BATCH_SIZE = 10;

    public function __construct(
        private ImportRepositoryInterface $importRepository,
        private XmlService $xmlService,
        private ImportBatchService $importBatchService,
    ) {
    }

    /**
     * @param int                              $import_id
     * @param int                              $offset
     * @param array<int, array<string, mixed>> $products
     * @param array<int|string, int|string>    $categoryMapping
     */
    public function run(int $import_id, int $offset = 0, array $products = [], array $categoryMapping = []): void
    {
        if (!empty($products)) {
            $this->importBatchService->process(
                [
                'import_id' => $import_id,
                'products' => $products,
                'categoryMapping' => $categoryMapping,
                ]
            );
            return;
        }

        $import = $this->importRepository->findById($import_id);
        if (!$import) {
            throw ImportNotFoundException::withId($import_id);
        }

        $xml = $this->xmlService->getXmlFromUrl($import->getUrl());
        $mapping = $import->getCategoryMapping()?->getMapping() ?? [];

        $productDtos = $this->xmlService->getProductsFromXml($xml);
        $chunk = array_slice($productDtos, $offset, self::BATCH_SIZE);

        $this->importBatchService->process(
            [
            'import_id' => $import_id,
            'products' => array_map(
                static fn(ProductDto $dto) => $dto->toArray(),
                $chunk
            ),
            'categoryMapping' => $mapping,
            ]
        );
    }
}
