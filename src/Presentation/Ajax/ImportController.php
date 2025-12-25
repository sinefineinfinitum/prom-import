<?php

declare(strict_types=1);

namespace SineFine\PromImport\Presentation\Ajax;

use SineFine\PromImport\Application\Import\ImportService;
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
