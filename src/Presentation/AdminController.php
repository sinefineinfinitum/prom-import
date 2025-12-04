<?php

namespace SineFine\PromImport\Presentation;

use SimpleXMLElement;
use SineFine\PromImport\Application\Import\Dto\ProductDto;
use SineFine\PromImport\Application\Import\XmlParser;
use SineFine\PromImport\Infrastructure\Http\WpHttpClient;
use SineFine\PromImport\Infrastructure\Persistence\ProductRepository;

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
            wp_die( esc_html(__( 'You do not have sufficient permissions to access this page.', 'prom-import' )));
        }

        return require_once( __DIR__ . "/../../templates/settings.php" );
    }

    public function prom_products_importer(): mixed
    {
        // Check user capabilities
        if ( ! current_user_can( 'manage_options' ) ) {
            wp_die( esc_html(__( 'You do not have sufficient permissions to access this page.', 'prom-import' )));
        }

        // Build current page URL without reading from superglobals to satisfy nonce verification sniff
        $current_page = menu_page_url( 'prom-products-importer', false );

        $domain_url = get_option( 'prom_domain_url_input' );
        if ( empty( $domain_url ) ) {
            echo '<div class="error notice"><p>'
                 . esc_html(__( 'Please configure the domain URL in settings first.', 'prom-import'))
                 . '</p></div>';
            wp_die();
        }

        $response = $this->httpClient->get( $domain_url );

        if ( is_wp_error( $response ) ) {
            if ( $response->get_error_code() === 'timeout' ) {
                echo '<div class="error notice"><p>'
                     . esc_html(__('Request timeout. The remote server is taking too long to respond.', 'prom-import' ))
                     . '</p></div>';
            } else {
                echo '<div class="error notice"><p>' . esc_html( $response->get_error_message() ) . '</p></div>';
            }
            wp_die();
        }

        if ( $response['response']['code'] != 200 ) {
            echo '<div class="error notice"><p>'
                 . esc_html(__('Failed to fetch products. Make sure website URL is set correctly.', 'prom-import'))
                 . '</p></div>';
            wp_die();
        }

        $response_body = wp_remote_retrieve_body( $response );
        $xml           = simplexml_load_string( $response_body );


        if ( ! $xml instanceof SimpleXMLElement ) {
            echo '<div class="error notice"><p>'
                 . esc_html(__('Failed to retrieve products data', 'prom-import' ))
                 . '</p></div>';
            wp_die();
        }

        $totalpages     = 1;
        $total_products = $this->xmlParser->getTotalProducts( $xml );
        $categories     = $this->xmlParser->parseCategories( $xml );
        $products       = $this->xmlParser->parseProducts( $xml, $categories );
        array_map(
                function ( $product ) {
                    /** @var ProductDto $product */
                    $product->existedId = $this->productRepository->findIdBySkuId( $product->sku->value() )
                            ? $this->productRepository->findIdBySkuId( $product->sku->value() )
                            : null;
                },
                $products
        );

        return require_once( __DIR__ . "/../../templates/products.php" );
    }

    function importer_section_callback(): void
    {
        echo '<p>'
             . esc_html__( 'Please enter valid Prom.ua export URL you want to import from.', 'prom-import' )
             . '</p>';
    }

    function url_setting_callback(): void
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
            <?php echo esc_html__( 'Enter Prom.ua export URL you want to import from', 'prom-import' ); ?>
        </p>
        <?php
    }
}