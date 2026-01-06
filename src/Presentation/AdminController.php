<?php

declare(strict_types=1);

namespace SineFine\PromImport\Presentation;

use SineFine\PromImport\Application\Import\XmlParserInterface;
use SineFine\PromImport\Application\Import\XmlService;
use SineFine\PromImport\Domain\Category\CategoryMappingRepositoryInterface;
use SineFine\PromImport\Domain\Product\ProductRepositoryInterface;

class AdminController extends BaseController {
    public function __construct(
		private XmlParserInterface $xmlParser,
		private XmlService $xmlService,
		private ProductRepositoryInterface $productRepository,
		private CategoryMappingRepositoryInterface $categoryMappingRepository,
    ) {
    }

    public function prom_categories_importer(): void
    {
	    $this->checkUserPermission();

		$xml = $this->xmlService->getXml();
        $spssCategories = $this->xmlParser->parseCategories( $xml );

        $spssSavedCategories = $this->categoryMappingRepository->getCategoryMapping();
        $spssExistingCategories = get_categories( [
                'taxonomy'     => 'product_cat',
                'show_count'   => 1,
                'pad_counts'   => 0,
                'hierarchical' => 1,
        ] );

        $this->render(
			'categories',
	        compact( 'spssCategories', 'spssExistingCategories', 'spssSavedCategories' )
        );
    }

    public function prom_products_importer(): void
    {
        $this->checkUserPermission();

	    $xml = $this->xmlService->getXml();
        $totalPages     = 1;
        $totalProducts  = $this->xmlParser->getTotalProducts( $xml );
        $categories     = $this->xmlParser->parseCategories( $xml );
        $spssProducts   = $this->xmlParser->parseProducts( $xml, $categories );

        foreach ( $spssProducts as $product ) {
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
            compact( 'spssProducts', 'totalPages', 'totalProducts' )
        );
    }
}