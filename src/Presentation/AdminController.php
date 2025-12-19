<?php

declare(strict_types=1);

namespace SineFine\PromImport\Presentation;

use SimpleXMLElement;
use SineFine\PromImport\Application\Import\XmlParser;
use SineFine\PromImport\Infrastructure\Http\WpHttpClient;
use SineFine\PromImport\Infrastructure\Persistence\ProductRepository;
use WP_Error;

class AdminController {

    private WpHttpClient $httpClient;
    private XmlParser $xmlParser;
    private ProductRepository $productRepository;

    public function __construct() {
        $this->httpClient        = new WpHttpClient();
        $this->xmlParser         = new XmlParser();
        $this->productRepository = new ProductRepository();

    }

    public function prom_settings_page_content(): mixed
    {
        if ( ! current_user_can( 'manage_options' ) ) {
            wp_die( esc_html(__( 'You do not have sufficient permissions to access this page.', 'spss12-import-prom-woo' )));
        }

        return require_once( __DIR__ . "/../../templates/settings.php" );
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
        $categories = $this->xmlParser->parseCategories( $xml );

        $savedCategories = array_combine(
                array_column(get_option('prom_categories_input'), 'id'),
                array_column(get_option('prom_categories_input'), 'selected'),
        );
        $existingCategories = get_categories( [
                'taxonomy'     => 'product_cat',
                'show_count'   => 1,
                'pad_counts'   => 0,
                'hierarchical' => 1,
        ] );

        $this->render( 'categories', compact( 'categories', 'existingCategories', 'savedCategories' ) );
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
        $products       = $this->xmlParser->parseProducts( $xml, $categories );

        foreach ( $products as $product ) {
            $existedId = $this->productRepository->findIdBySkuId( $product->sku->value() );
            $product->existedId = $existedId ? $existedId : null;
        }

        $this->render(
            'products',
            compact( 'products', 'categories', 'totalPages', 'totalProducts' )
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

    public function importer_section_callback(): void
    {
        echo '<p>'
             . esc_html__( 'Please enter valid Prom.ua export URL you want to import from.', 'spss12-import-prom-woo' )
             . '</p>';
    }
    public function url_setting_callback(): void
    {
        ?>
        <label>
            <input type='url'
                   class="regular-text"
                   name="prom_domain_url_input"
                   value="<?php echo esc_url( get_option( 'prom_domain_url_input' ) ); ?>"
                   placeholder="https://prom.ua/products_feed.xml?...">
        </label>
        <p class="description">
            <?php echo esc_html__( 'Enter Prom.ua export URL you want to import from', 'spss12-import-prom-woo' ); ?>
        </p>
        <?php
    }

    private function checkUserPermission(): void
    {
        if ( ! current_user_can( 'manage_options' ) ) {
            wp_die( esc_html( __( 'You do not have sufficient permissions to access this page.', 'spss12-import-prom-woo' ) ) );
        }
    }

    private function validateXml( mixed $xml): void
    {
        if ( ! $xml instanceof SimpleXMLElement ) {
            echo '<div class="error notice"><p>'
                 . esc_html( __( 'Failed to retrieve products data', 'spss12-import-prom-woo' ) )
                 . '</p></div>';
            wp_die();
        }
    }

    private function validateResponse(array|WP_Error $response): void
    {
        if ( is_wp_error( $response ) ) {
            if ( $response->get_error_code() === 'timeout' ) {
                echo '<div class="error notice"><p>'
                     . esc_html( __( 'Request timeout. The remote server is taking too long to respond.', 'spss12-import-prom-woo' ) )
                     . '</p></div>';
            } else {
                echo '<div class="error notice"><p>'
                     . esc_html( $response->get_error_message() )
                     . '</p></div>';
            }
            wp_die();
        }
        if ( $response['response']['code'] != 200 ) {
            echo '<div class="error notice"><p>'
                 . esc_html(__('Failed to fetch products. Make sure website URL is set correctly.', 'spss12-import-prom-woo'))
                 . '</p></div>';
            wp_die();
        }
    }

    private function render(string $template, array $vars = []): void
    {
        extract($vars, EXTR_SKIP);
        require __DIR__ . "/../../templates/$template.php";
    }
}