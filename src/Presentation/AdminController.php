<?php

declare(strict_types=1);

namespace SineFine\PromImport\Presentation;

use Exception;
use SineFine\PromImport\Application\Import\ImportApplicationService;
use SineFine\PromImport\Application\Import\XmlService;
use SineFine\PromImport\Domain\Category\CategoryMappingRepositoryInterface;
use SineFine\PromImport\Domain\Common\XmlParserInterface;
use SineFine\PromImport\Domain\Product\ProductRepositoryInterface;

class AdminController extends BaseController {
    public function __construct(
		private XmlParserInterface $xmlParser,
		private XmlService $xmlService,
		private ProductRepositoryInterface $productRepository,
		private CategoryMappingRepositoryInterface $categoryMappingRepository,
        private ImportApplicationService $importAppService,
    ) {
    }

    public function imports_page(): void
    {
        $imports = $this->importAppService->getAllImports();
        $this->render('imports', compact('imports'));
    }

    public function categories_importer(): void
    {
		try {
            $xml = $this->xmlService->getXml();
        } catch ( Exception $exception ) {
            $message = $exception->getMessage();
            $this->render('notification', compact(['message']));
            return;
        }
        $sinefine_promimport_categories = $this->xmlParser->parseCategories( $xml );

        $sinefine_promimport_saved_categories = $this->categoryMappingRepository->getCategoryMapping();
        $sinefine_promimport_existing_categories = get_categories( [
                'taxonomy'     => 'product_cat',
                'show_count'   => 1,
                'pad_counts'   => 0,
                'hierarchical' => 1,
                'hide_empty' => false,
        ] );

        $this->render(
			'categories',
	        compact( 'sinefine_promimport_categories', 'sinefine_promimport_existing_categories', 'sinefine_promimport_saved_categories' )
        );
    }

    public function products_importer(): void
    {
        try {
            $importId = isset($_GET['import_id']) ? (int)$_GET['import_id'] : null;
            if ($importId) {
                $import = $this->importAppService->getAllImports();
                $import = array_filter($import, fn($i) => $i->getId() === $importId);
                $import = reset($import);
                if ($import) {
                    $url = $import->getUrl();
                    $xmlContent = $this->xmlService->downloadXmlContent($url);
                    $xml = simplexml_load_string($xmlContent);
                } else {
                    $xml = $this->xmlService->getXml();
                }
            } else {
                $xml = $this->xmlService->getXml();
            }
        } catch ( Exception $exception ) {
            $message = $exception->getMessage();
            $this->render('notification', compact(['message']));
            return;
        }
        $sinefine_promimport_total_pages     = 1;
        $sinefine_promimport_total_products  = $this->xmlParser->getTotalProducts( $xml );
        $categories     = $this->xmlParser->parseCategories( $xml );
        $sinefine_promimport_products   = $this->xmlParser->parseProducts( $xml, $categories );

        foreach ( $sinefine_promimport_products as $product ) {
            $existedId = $this->productRepository->findIdBySkuId( $product->sku->value() );
            $product->existedId = $existedId ?: null;
	        $product->categoryName = $product->category
	                                 && $product->category->id
	                                 && $this->categoryMappingRepository->mapping( $product->category->id )
		        ? $this->categoryMappingRepository->mapping( $product->category->id )->name
		        : "None";
        }

        $this->render(
            'products',
            compact( 'sinefine_promimport_products', 'sinefine_promimport_total_pages', 'sinefine_promimport_total_products' )
        );
    }
}