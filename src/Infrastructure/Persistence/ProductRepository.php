<?php

namespace SineFine\PromImport\Infrastructure\Persistence;

use SineFine\PromImport\Application\Import\Dto\ProductDto;
use WP_Error;
use WP_Query;

class ProductRepository
{
	/**
     * Find product post ID by stored meta value '_sku'.
     */
    public function findIdBySkuId(int $scuId): int|false
    {
        $query = new WP_Query([
            'post_type'              => 'product',
            'post_status'            => 'any',
            'meta_query'             => [[
                'key'     => '_sku',
                'value'   => $scuId,
                'compare' => '=',
                'type'    => 'NUMERIC',
            ]],
            'fields'                 => 'ids',
            'posts_per_page'         => 1,
            'orderby'                => 'ID',
            'order'                  => 'DESC',
            'no_found_rows'          => true,
            'update_post_meta_cache' => false,
            'update_post_term_cache' => false,
            'suppress_filters'       => true,
        ]);

        $postId = $query->have_posts() ? (int) $query->posts[0] : 0;
        wp_reset_postdata();

        return $postId > 0 ? $postId : false;
    }

    /**
     * Create or update WooCommerce product from ProductDto.
     * Returns post ID or WP_Error.
     */
    public function upsertFromDto(ProductDto $dto): int|WP_Error
    {
        $scu_id = $dto->sku->value();
        $existing = $this->findIdBySkuId($scu_id);

        $postArr = [
            'post_type'    => 'product',
            'post_title'   => sanitize_text_field($dto->title),
            'post_status'  => 'publish',
            'post_author'  => get_current_user_id(),
            'post_excerpt' => wp_kses_post($dto->description),
            'post_content' => wp_kses_post($dto->description),
        ];

        if ($existing) {
            $postArr['ID'] = $existing;
            $postId = wp_update_post($postArr, true);
        } else {
            $postId = wp_insert_post($postArr, true);
        }

        if (is_wp_error($postId)) {
            return $postId;
        }

        // Store source id
        update_post_meta($postId, '_sku', $scu_id );

        // Set product type
        wp_set_object_terms($postId, 'simple', 'product_type');

        // Woo product data
        if (function_exists('wc_get_product')) {
            $product = wc_get_product($postId);
            if ($product) {
                $product->set_virtual(true);
                $product->set_downloadable(true);
                $amount = $dto->price->amount();
                if ($amount > 0) {
                    $product->set_price($amount);
                    $product->set_regular_price($amount);
                }
                $product->save();
            }
        }

        // Featured image: use first available media url
        if (! empty($dto->mediaUrls)) {
            $first = reset($dto->mediaUrls);
            if (is_string($first) && $first !== '') {
                $this->assignFeatureImageToProduct($first, (int) $postId, $dto->title);
            }
        }

        return (int) $postId;
    }

    public function assignFeatureImageToProduct(string $url, int $postId, string $title = ''): void
    {
        $attachmentId = media_sideload_image($url, $postId, $title, 'id');
        if (! is_wp_error($attachmentId) && is_numeric($attachmentId)) {
            set_post_thumbnail($postId, (int) $attachmentId);
        }
    }

	public function addImageToProductGallery(string $url, int $postId, string $title = ''): void
	{
		$attachmentId = media_sideload_image($url, $postId, $title, 'id');
		if ( is_null( get_post_meta( $postId, "_product_image_gallery" ) ) ) {
			add_post_meta( $postId, "_product_image_gallery", $attachmentId );
		} else {
			$images_meta = get_post_meta( $postId, "_product_image_gallery", true );
			update_post_meta( $postId, "_product_image_gallery", $images_meta . "," . $attachmentId );
		}
	}
}
