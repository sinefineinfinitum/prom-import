<?php

declare(strict_types=1);

namespace SineFine\PromImport\Infrastructure\Queue;

use Psr\Log\LoggerInterface;
use SineFine\PromImport\Application\Import\ProductManager;
use SineFine\PromImport\Application\Import\XmlService;
use SineFine\PromImport\Domain\Exception\ImportNotFoundException;
use SineFine\PromImport\Domain\Exception\InvalidXmlException;
use SineFine\PromImport\Domain\Import\ImportRepositoryInterface;

class QueueManager
{
    public function __construct(
        private ProductManager $productManager,
        private ImportRepositoryInterface $importRepository,
        private XmlService $xmlService,
        private LoggerInterface $logger,
    ) {
    }

    /**
     * @param int $import_id
     *
     * @throws ImportNotFoundException
     * @throws InvalidXmlException
     */
    public function run( int $import_id ): void
    {
        $import = $this->importRepository->findById( $import_id );
        if ( ! $import ) {
            throw ImportNotFoundException::withId($import_id);
        }

        $xml  = $this->xmlService->getXmlFromUrl( $import->getUrl() );
        $mapping = $import->getCategoryMapping()?->getMapping() ?? [];

        $productDtos = $this->xmlService->getProductsFromXml( $xml );
        $importedCount = 0;
        foreach ( $productDtos as $productDto ) {
            $productId = $this->productManager->createProductFromDto( $productDto );
            if ( is_wp_error( $productId ) ) {
                $this->logger->error( $productId->get_error_message() );
                continue;
            }
            $this->productManager->addImagesToProductGallery( $productDto, $productId );
            $importedCount ++;

            // Handle category mapping
            $externalCatId = (int) $productDto->category?->id();
            if ( $externalCatId > 0 && isset( $mapping[ $externalCatId ] ) ) {
                $wooTermId = (int) $mapping[ $externalCatId ];
                if ( $wooTermId > 0 ) {
                    $this->productManager->addCategoryToProduct( $productId, $wooTermId );
                }
            }
        }
        $count = count( $productDtos );
        $this->logger->info(
            'Imported {imported_count} from {count} products',
            [ 'imported_count' => $importedCount, 'count' => $count ]
        );
    }
}
