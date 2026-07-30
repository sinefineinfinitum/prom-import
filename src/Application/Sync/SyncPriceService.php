<?php

namespace SineFine\PromImport\Application\Sync;

use DateTime;
use Psr\Log\LoggerInterface;
use SineFine\PromImport\Application\Import\XmlService;
use SineFine\PromImport\Domain\Exception\DomainException;
use SineFine\PromImport\Domain\Exception\ImportNotFoundException;
use SineFine\PromImport\Domain\Import\ImportRepositoryInterface;
use SineFine\PromImport\Domain\Product\ProductRepositoryInterface;
use Throwable;

class SyncPriceService
{
    public function __construct(
        private ImportRepositoryInterface $importRepository,
        private XmlService $xmlService,
        private ProductRepositoryInterface $productRepository,
        private LoggerInterface $logger,
    ) {
    }

    /**
     * Sync prices for all products in the import
     *
     * @param  int $id
     * @return array{success: bool, updated: int, total: int, errors: int}
     * @throws DomainException
     */
    public function syncPrices(int $id): array
    {
        $import = $this->importRepository->findById($id);
        if (!$import) {
            throw ImportNotFoundException::withId($id);
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
                } elseif (is_wp_error($result)) {
                    $errors++;
                    $this->logger->warning(
                        'Price sync failed for product: {error}',
                        ['error' => $result->get_error_message()]
                    );
                }
            } catch (DomainException $e) {
                $errors++;
                $this->logger->warning(
                    'Price sync domain error for SKU {sku}: {message}',
                    [
                        'sku' => $productDto->sku->value(),
                        'message' => $e->getMessage(),
                    ]
                );
            } catch (Throwable $e) {
                $errors++;
                $this->logger->error(
                    'Price sync unexpected error for SKU {sku}: {message}',
                    [
                        'sku' => $productDto->sku->value(),
                        'message' => $e->getMessage(),
                    ]
                );
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
