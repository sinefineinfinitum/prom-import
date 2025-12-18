<?php

namespace SineFine\PromImport\Presentation\Ajax;

use SineFine\PromImport\Application\Import\ImportService;
use SineFine\PromImport\Application\Import\Dto\ProductDto;
use SineFine\PromImport\Domain\Product\ValueObject\Sku;
use SineFine\PromImport\Domain\Product\ValueObject\Price;

class ImportController
{
    public function __construct(private ImportService $service)
    {}

    public function importProducts(): void
    {
        // Capability check
        if (! current_user_can('manage_options')) {
            wp_send_json_error(['message' => esc_html(__('Insufficient permissions', 'spss12-import-prom-woo'))]);
        }

        // Nonce check
        $nonce = isset($_REQUEST['nonce']) ? sanitize_text_field(wp_unslash($_REQUEST['nonce'])) : '';
        if (! wp_verify_nonce($nonce, 'prom_importer_nonce')) {
            wp_send_json_error(['message' => esc_html(__('Security check failed', 'spss12-import-prom-woo'))]);
        }

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
        $category    = isset($_POST['product_category']) ? sanitize_text_field(wp_unslash($_POST['product_category'])) : '';
        $media       = isset($_POST['product_featured_media']) && json_decode(sanitize_text_field(wp_unslash($_POST['product_featured_media'])), true)
            ? (array) json_decode(sanitize_text_field(wp_unslash(($_POST['product_featured_media']))), true)
            : [];

        $dto = new ProductDto(
            new Sku($sku_id),
            $title,
            $description,
            new Price($priceVal),
            $category !== '' ? $category : null,
            [],
            $media,
            ''
        );

        $result = $this->service->importFromDto($dto);

        if (is_wp_error($result)) {
            wp_send_json_error(['message' => $result->get_error_message()]);
        }

        wp_send_json_success([
            'message' => esc_html(__('Successfully imported', 'spss12-import-prom-woo')),
            'url'     => get_edit_post_link($result, ''),
        ]);
    }

	public function importCategories(): void
	{
		// Capability check
		if (! current_user_can('manage_options')) {
			wp_send_json_error(['message' => esc_html(__('Insufficient permissions', 'spss12-import-prom-woo'))]);
		}

		// Nonce check
		$nonce = isset($_REQUEST['nonce']) ? sanitize_text_field(wp_unslash($_REQUEST['nonce'])) : '';
		if (! wp_verify_nonce($nonce, 'prom_importer_nonce')) {
			wp_send_json_error(['message' => esc_html(__('Security check failed', 'spss12-import-prom-woo'))]);
		}

		// Collect and sanitize input
		$categories = json_decode(sanitize_text_field(wp_unslash($_REQUEST['categories'])), true);

		wp_send_json_success([
			'message' => esc_html(__('Successfully imported', 'spss12-import-prom-woo')),
		]);
	}
}
