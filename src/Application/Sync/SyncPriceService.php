<?php

namespace SineFine\PromImport\Application\Sync;

use DateTime;
use Exception;
use SineFine\PromImport\Application\Import\XmlService;
use SineFine\PromImport\Domain\Import\ImportRepositoryInterface;
use SineFine\PromImport\Domain\Product\ProductRepositoryInterface;

class SyncPriceService
{
    public function __construct(
        private ImportRepositoryInterface $importRepository,
        private XmlService $xmlService,
        private ProductRepositoryInterface $productRepository,
    ) {
    }

    /**
     * Sync prices for all products in the import
     *
     * @param  int $id
     * @return array{success: bool, updated: int, total: int, errors: int}
     * @throws Exception
     */
    public function syncPrices(int $id): array
    {
        $import = $this->importRepository->findById($id);
        if (!$import) {
            throw new Exception('Import not found');
        }

        $xml = $this->xmlService->getXmlFromUrl($import->getUrl());
        $products = $this->xmlService->getProductsFromXml($xml);

        $updated = 0;
        $errors = 0;
        $total = count($products);

        foreach ($products as $productDto) {
            try {
                $result = $this->productRepository->updateProductPrice($productDto);
                if ($result !== false && !is_wp_error($result)) {
                    $updated++;
                }
            } catch (Exception) {
                $errors++;
            }
        }

        $import->setUpdatedAt(new DateTime());
        $this->importRepository->save($import);

        return [
        'success' => true,
        'updated' => $updated,
        'total' => $total,
        'errors' => $errors,
        ];
    }
}
