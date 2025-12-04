<?php

namespace SineFine\PromImport\Presentation\Ajax;

use SineFine\PromImport\Application\Import\ImportService;
use SineFine\PromImport\Application\Import\Dto\ProductDto;
use SineFine\PromImport\Application\Import\ValueObject\Sku;
use SineFine\PromImport\Application\Import\ValueObject\Price;

class ImportController
{
    public function __construct(private ImportService $service)
    {}

    public function import(): void
    {
        // Capability check
        if (! current_user_can('manage_options')) {
            wp_send_json_error(['message' => __('Insufficient permissions', 'prom-import')]);
        }

        // Nonce check
        $nonce = isset($_REQUEST['nonce']) ? sanitize_text_field(wp_unslash($_REQUEST['nonce'])) : '';
        if (! wp_verify_nonce($nonce, 'prom_importer_nonce')) {
            wp_send_json_error(['message' => __('Security check failed', 'prom-import')]);
        }

        // Collect and sanitize input
        $sku_id = isset($_POST['product_id']) ? (int) $_POST['product_id'] : 0;
        if ($sku_id <= 0) {
            wp_send_json_error(['message' => __('Invalid Product ID', 'prom-import')]);
        }

        $title       = isset($_POST['product_title']) ? sanitize_text_field(wp_unslash($_POST['product_title'])) : '';
        $description = isset($_POST['product_description']) ? wp_kses_post(wp_unslash($_POST['product_description'])) : '';
        $priceVal    = isset($_POST['product_price']) ? (float) wp_unslash($_POST['product_price']) : 0.0;
        $category    = isset($_POST['product_category']) ? sanitize_text_field(wp_unslash($_POST['product_category'])) : '';
        $media       = isset($_POST['product_featured_media']) && json_decode(wp_unslash($_POST['product_featured_media']), true)
            ? (array) json_decode(wp_unslash($_POST['product_featured_media']), true)
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
            'message' => __('Successfully imported', 'prom-import'),
            'url'     => get_edit_post_link($result, ''),
        ]);
    }
}
