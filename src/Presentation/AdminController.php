<?php

declare(strict_types=1);

namespace SineFine\PromImport\Presentation;

use SineFine\PromImport\Application\Import\XmlParser;
use SineFine\PromImport\Infrastructure\Http\WpHttpClient;
use SineFine\PromImport\Infrastructure\Persistence\CategoryMappingRepository;
use SineFine\PromImport\Infrastructure\Persistence\ProductRepository;

class AdminController extends BaseController {

    private WpHttpClient $httpClient;
    private XmlParser $xmlParser;
    private ProductRepository $productRepository;
    private CategoryMappingRepository $categoryMappingRepository;

    public function __construct() {
        $this->httpClient        = new WpHttpClient();
        $this->xmlParser         = new XmlParser();
        $this->productRepository = new ProductRepository();
        $this->categoryMappingRepository = new CategoryMappingRepository();
    }

    public function prom_categories_importer(): void
    {
        $this->checkUserPermission();
        $domain_url = self::getUrl();
        $response   = $this->httpClient->get( $domain_url );

        $this->validateResponse( $response );
        $responseBody = wp_remote_retrieve_body( $response );
        $xml          = simplexml_load_string( $responseBody );

        $this->validateXml( $xml );
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
        $domain_url = self::getUrl();
        $response = $this->httpClient->get($domain_url);

        $this->validateResponse($response);
        $response_body = wp_remote_retrieve_body( $response );
        $xml           = simplexml_load_string( $response_body );

        $this->validateXml( $xml );

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

    public static function getUrl(): mixed
    {
        $domain_url = get_option('prom_domain_url_input');
        if ( empty( $domain_url ) ) {
            echo '<div class="error notice"><p>'
                 . esc_html( __( 'Please configure the domain URL in settings first.', 'spss12-import-prom-woo' ) )
                 . '</p></div>';
            wp_die();
        }

        return $domain_url;
    }
}