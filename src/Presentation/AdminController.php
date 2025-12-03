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
		$this->httpClient = new WpHttpClient();
		$this->xmlParser = new XmlParser();
        $this->productRepository = new ProductRepository();

	}

	public function prom_settings_page_content()
	{
		if (!current_user_can('manage_options')) {
   wp_die(__('You do not have sufficient permissions to access this page.', 'prom-import'));
		}

		return require_once( __DIR__ . "/../../templates/settings.php");
	}

	public function prom_products_importer(): mixed
	{
		// Check user capabilities
		if (!current_user_can('manage_options')) {
   wp_die(__('You do not have sufficient permissions to access this page.', 'prom-import'));
		}

		// Check if page parameter exists
		if (!isset($_GET['page'])) {
   echo '<div class="error notice"><p>' . __('Invalid request', 'prom-import') . '</p></div>';
			wp_die();
		}

		$current_page = admin_url("admin.php?page=" . sanitize_text_field(wp_unslash($_GET['page'])));


//		$page_num = 1; // Default value
//
//		if (isset($_GET['page_num'])) {
//			// Verify nonce first before processing page_num
//			if (!isset($_GET['_wpnonce'])
//			    || !wp_verify_nonce(sanitize_text_field(wp_unslash($_GET['_wpnonce'])), 'wcapi_products_page')) {
//				echo '<div class="error notice"><p>'
//				     . __('Security verification failed. Please try again.', 'prom-importer')
//				     . '</p></div>';
//				return;
//			}
//
//			$page_num = intval($_GET['page_num']);
//			if ($page_num < 1) {
//				$page_num = 1;
//			}
//		}
//
//		$prev_num = $page_num > 1 ? $page_num - 1 : 1;
//		$next_num = $page_num + 1;

		$domain_url = get_option('prom_domain_url_input');
		if (empty($domain_url)) {
   echo '<div class="error notice"><p>'
        . __('Please configure the domain URL in settings first.', 'prom-import')
        . '</p></div>';
			wp_die();
		}

		$response = $this->httpClient->get($domain_url);

		if (is_wp_error($response)) {
			if ($response->get_error_code() === 'timeout') {
    echo '<div class="error notice"><p>'
         . __('Request timeout. The remote server is taking too long to respond. Please try again later.', 'prom-import')
         . '</p></div>';
			} else {
				echo '<div class="error notice"><p>' . esc_html($response->get_error_message()) . '</p></div>';
			}
			wp_die();
		}

		if ($response['response']['code'] != 200) {
   echo '<div class="error notice"><p>'
        . __('Failed to fetch products. Make sure website URL is set correctly.', 'prom-import')
        . '</p></div>';
			wp_die();
		}

		$response_body = wp_remote_retrieve_body($response);
		$xml = simplexml_load_string($response_body);


		if (!$xml instanceof SimpleXMLElement) {
   echo '<div class="error notice"><p>'
        . __('Failed to retrieve products data', 'prom-import')
        . '</p></div>';
			wp_die();
		}

		$totalpages =  1;
		$total_products = $this->xmlParser->getTotalProducts($xml);
		$categories = $this->xmlParser->parseCategories($xml);
		$products = $this->xmlParser->parseProducts($xml, $categories);
        array_map(
                function ( $product ) {
                    /** @var ProductDto $product */
                    $product->existedId = $this->productRepository->findIdBySkuId ( $product->sku->value() )
                            ?  $this->productRepository->findIdBySkuId ( $product->sku->value() )
                            : null;
                },
                $products
        );

		// Create nonce for pagination links
		//$nonce = wp_create_nonce('wcapi_products_page');

		return require_once( __DIR__ . "/../../templates/products.php");
	}
	function importer_section_callback() {
  echo '<p>'
             . esc_html__('Please enter valid Prom.ua export URL you want to import from.', 'prom-import')
             . '</p>';
	}
	function url_setting_callback() {
		?>
		<label>
            <input type='url'
                class="regular-text"
                name="prom_domain_url_input"
                value="<?php echo esc_url(get_option('prom_domain_url_input')); ?>"
                placeholder="https://prom.ua/products_feed.xml?...">
        </label>
        <p class="description">
        	<?php echo esc_html__('Enter Prom.ua export URL you want to import from', 'prom-import'); ?>
        </p>
	<?php
	}
}