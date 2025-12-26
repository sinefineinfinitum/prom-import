<?php

declare(strict_types=1);

namespace SineFine\PromImport\Presentation\Ajax;

use SineFine\PromImport\Application\Import\ImportService;
use SineFine\PromImport\Application\Import\ImportBatchService;
use SineFine\PromImport\Application\Import\Dto\ProductDto;
use SineFine\PromImport\Domain\Category\CategoryMappingRepositoryInterface;
use SineFine\PromImport\Domain\Product\ValueObject\Sku;
use SineFine\PromImport\Domain\Product\ValueObject\Price;
use SineFine\PromImport\Presentation\BaseController;

class ImportController extends BaseController
{
    public function __construct(
	    private ImportService $service,
	    private CategoryMappingRepositoryInterface $categoryMappingRepository,
        private ImportBatchService $batchService,
    ) {
    }

    public function importProducts(): void
    {
	    $this->checkUserPermission();
		$this->checkNonce('prom_importer_nonce');

        // Collect and sanitize input
        $sku_id = isset($_POST['product_id']) ? (int) $_POST['product_id'] : 0;
        if ($sku_id <= 0) {
            wp_send_json_error(['message' => esc_html(__('Invalid Product ID', 'spss12-import-prom-woo'))]);
        }

        $title       = isset($_POST['product_title']) ? sanitize_text_field(wp_unslash($_POST['product_title'])) : '';
        $description = isset($_POST['product_description']) ? wp_kses_post(wp_unslash($_POST['product_description'])) : '';
        // Sanitize price input before casting
        $priceVal    = isset($_POST['product_price'])
            ? (float) sanitize_text_field(wp_unslash($_POST['product_price']))
            : 0.0;
        $externalCategoryId = isset($_POST['product_category'])
	        ? sanitize_text_field(wp_unslash($_POST['product_category']))
	        : '';
        $media       = isset($_POST['product_featured_media']) && json_decode(sanitize_text_field(wp_unslash($_POST['product_featured_media'])), true)
            ? (array) json_decode(sanitize_text_field(wp_unslash(($_POST['product_featured_media']))), true)
            : [];

        $dto = new ProductDto(
            new Sku($sku_id),
            $title,
            $description,
            new Price($priceVal),
            null,
            [],
            $media,
            ''
        );

        $productId = $this->service->importProductFromDto($dto);
        if (is_wp_error($productId)) {
            wp_send_json_error(['message' => $productId->get_error_message()]);
        }

		$categoryId = $this->service->addCategoryForProduct((int) $productId, (int) $externalCategoryId);
	    if (is_wp_error($categoryId)) {
		    wp_send_json_error(['message' => $categoryId->get_error_message()]);
	    }

        wp_send_json_success([
            'message' => esc_html(__('Successfully imported', 'spss12-import-prom-woo')),
            'url'     => get_edit_post_link($productId, ''),
        ]);
    }

    /**
     * Start async batch import via Action Scheduler / WP-Cron.
     * Expects 'items' JSON array with objects: {sku, title, description, price, mediaUrls, tags, link}
     */
    public function importProductsAsync(): void
    {
        $this->checkUserPermission();
        $this->checkNonce('prom_importer_nonce');
        $raw = $_REQUEST['items'] ?? '';
        if (!$raw) {
            wp_send_json_error(['message' => esc_html(__('"items" is missing', 'spss12-import-prom-woo'))]);
        }
        $items = json_decode(sanitize_text_field(wp_unslash($raw)), true);
        if (!is_array($items)) {
            wp_send_json_error(['message' => esc_html(__('Invalid items payload', 'spss12-import-prom-woo'))]);
        }
        $dtos = [];
        foreach ($items as $item) {
            $sku = isset($item['sku']) ? (int) $item['sku'] : 0;
            if ($sku <= 0) {
                continue;
            }
            $dto = new ProductDto(
                new Sku($sku),
                isset($item['title']) ? sanitize_text_field((string)$item['title']) : '',
                isset($item['description']) ? wp_kses_post((string)$item['description']) : '',
                new Price(isset($item['price']) ? (float)$item['price'] : 0.0),
                null,
                is_array($item['tags'] ?? null) ? array_map('sanitize_text_field', $item['tags']) : [],
                is_array($item['mediaUrls'] ?? null) ? array_map('esc_url_raw', $item['mediaUrls']) : [],
                isset($item['link']) ? esc_url_raw((string)$item['link']) : ''
            );
            $dtos[] = $dto;
        }
        $batchSize = isset($_REQUEST['batch_size']) ? max(1, (int) $_REQUEST['batch_size']) : 20;
        $delay     = isset($_REQUEST['delay']) ? max(0, (int) $_REQUEST['delay']) : 0;
        $result = $this->batchService->enqueue($dtos, $batchSize, $delay);
        wp_send_json_success([
            'message' => esc_html(__('Import scheduled', 'spss12-import-prom-woo')),
            'batches' => $result['batches'],
            'first_job' => $result['first_job'],
        ]);
    }

	public function importCategories(): void
	{
		$this->checkUserPermission();
		$this->checkNonce('prom_importer_nonce');
		if (empty($_REQUEST['categories'])) {
			wp_send_json_error(['message' => esc_html(__('"сategories" is missing', 'spss12-import-prom-woo'))]);
		}

		$categories = json_decode(sanitize_text_field(wp_unslash($_REQUEST['categories'])), true);

		if (!is_array($categories)) {
			wp_send_json_error(['message' => esc_html(__('Invalid imported categories', 'spss12-import-prom-woo'))]);
		}

		foreach ($categories as $category) {
			if (! ctype_digit($category['id']) || !ctype_digit($category['selected']) ) {
				wp_send_json_error(['message' => esc_html(__('Validation error', 'spss12-import-prom-woo'))]);
			}
		}
		$this->categoryMappingRepository->setCategoryMapping($categories);

		wp_send_json_success([
			'message' => esc_html(__('Successfully imported', 'spss12-import-prom-woo')),
			'data' => get_option('prom_categories_input'),
		]);
	}
}
