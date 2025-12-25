<?php

declare(strict_types=1);

namespace SineFine\PromImport\Presentation;

use SineFine\PromImport\Application\Import\XmlParser;
use SineFine\PromImport\Application\Import\XmlService;
use SineFine\PromImport\Infrastructure\Http\WpHttpClient;
use SineFine\PromImport\Infrastructure\Persistence\CategoryMappingRepository;
use SineFine\PromImport\Infrastructure\Persistence\FeedRepository;
use SineFine\PromImport\Infrastructure\Persistence\ProductRepository;

class AdminController extends BaseController {

    private WpHttpClient $httpClient;
    private XmlParser $xmlParser;
    private XmlService $xmlService;
    private ProductRepository $productRepository;
    private CategoryMappingRepository $categoryMappingRepository;
    private FeedRepository $feedRepository;

    public function __construct() {
        $this->httpClient        = new WpHttpClient();
        $this->xmlParser         = new XmlParser();
        $this->feedRepository    = new FeedRepository();
		$this->xmlService        = new XmlService($this->httpClient, $this->feedRepository);
        $this->productRepository = new ProductRepository();
        $this->categoryMappingRepository = new CategoryMappingRepository();
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