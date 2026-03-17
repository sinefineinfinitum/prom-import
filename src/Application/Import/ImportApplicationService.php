<?php

declare(strict_types=1);

namespace SineFine\PromImport\Application\Import;

use SineFine\PromImport\Domain\Import\Import;
use SineFine\PromImport\Domain\Import\ImportRepositoryInterface;
use SineFine\PromImport\Domain\Common\XmlParserInterface;
use DateTime;

class ImportApplicationService
{
    public function __construct(
        private ImportRepositoryInterface $importRepository,
        private XmlService $xmlService,
        private XmlParserInterface $xmlParser,
        private ImportService $importService
    ) {
    }

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
        return $this->importRepository->delete($id);
    }

    public function getImportCategories(int $id): array
    {
        $import = $this->importRepository->findById($id);
        if (!$import) {
            return [];
        }

        try {
            $xmlContent = $this->xmlService->downloadXmlContent($import->getUrl());
            $xml = simplexml_load_string($xmlContent);
            return $this->xmlParser->parseCategories($xml);
        } catch (\Throwable $e) {
            return [];
        }
    }

    public function updateImportMapping(int $id, array $mapping): bool
    {
        $import = $this->importRepository->findById($id);
        if (!$import) {
            return false;
        }

        $import->setCategoryMapping($mapping);
        $import->setUpdatedAt(new DateTime());
        $this->importRepository->save($import);

        return true;
    }

    public function runImport(int $id): array
    {
        $import = $this->importRepository->findById($id);
        if (!$import) {
            throw new \RuntimeException('Import not found');
        }

        $xmlContent = $this->xmlService->downloadXmlContent($import->getUrl());
        $xml = simplexml_load_string($xmlContent);
        $products = $this->xmlService->getProductsFromXml($xml);
        
        $importedCount = 0;
        $mapping = $import->getCategoryMapping() ?? [];

        // We need a temporary way to use the import's mapping during this run.
        // Since ImportService uses CategoryMappingRepositoryInterface, which is usually a singleton or global.
        // But here we want to use specific mapping for this import.
        
        foreach ($products as $productDto) {
            $postId = $this->importService->importProductFromDto($productDto);
            if (!is_wp_error($postId)) {
                $importedCount++;
                
                // Handle category mapping
                $externalCatId = $productDto->category ? (int)$productDto->category->id : 0;
                if ($externalCatId > 0 && isset($mapping[$externalCatId])) {
                    $wooTermId = (int)$mapping[$externalCatId];
                    if ($wooTermId > 0) {
                        wp_set_object_terms($postId, [$wooTermId], 'product_cat');
                    }
                }
            }
        }

        $import->setUpdatedAt(new DateTime());
        $this->importRepository->save($import);

        return [
            'success' => true,
            'imported_count' => $importedCount,
            'total_count' => count($products)
        ];
    }
}
