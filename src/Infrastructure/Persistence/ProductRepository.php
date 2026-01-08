<?php

declare(strict_types=1);

namespace SineFine\PromImport\Infrastructure\Persistence;

use Psr\Log\LoggerInterface;
use SineFine\PromImport\Application\Import\Dto\ProductDto;
use SineFine\PromImport\Domain\Product\Product;
use SineFine\PromImport\Domain\Product\ProductRepositoryInterface;
use SineFine\PromImport\Domain\Product\ValueObject\Sku;
use WP_Error;
use WP_Query;

class ProductRepository implements ProductRepositoryInterface
{
	public function __construct(
		private LoggerInterface $logger
	) {}

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
        ]);

        $postId = $query->have_posts()
	        ? (is_int($query->posts[0]) ? $query->posts[0] : $query->posts[0]->ID)
	        : 0;
        wp_reset_postdata();

        return $postId > 0 ? $postId : false;
    }

    /**
     * Domain-level API: find post ID by SKU value object.
     */
    public function findIdBySku(Sku $sku): int|false
    {
        return $this->findIdBySkuId($sku->value());
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
            if ($first) {
                $this->assignFeatureImageToProduct($first, (int) $postId, $dto->title);
            }
        }

        return (int) $postId;
    }

    /**
     * Persist Domain Product and return post ID or WP_Error
     * @return int|WP_Error
     */
    public function save(Product $product): int|WP_Error
    {
        $scu_id = $product->sku()->value();
        $existing = $this->findIdBySkuId($scu_id);

        $postArr = [
            'post_type'    => 'product',
            'post_title'   => sanitize_text_field($product->title()),
            'post_status'  => 'publish',
            'post_author'  => get_current_user_id(),
            'post_excerpt' => wp_kses_post($product->description()),
            'post_content' => wp_kses_post($product->description()),
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

        update_post_meta($postId, '_sku', $scu_id);

        wp_set_object_terms($postId, 'simple', 'product_type');

        if (function_exists('wc_get_product')) {
            $wc = wc_get_product($postId);
            if ($wc) {
                $wc->set_virtual(true);
                $wc->set_downloadable(true);
                $amount = $product->price()->amount();
                if ($amount > 0) {
                    $wc->set_price($amount);
                    $wc->set_regular_price($amount);
                }
                $wc->save();
            }
        }

        // Featured image from first media URL, if exists
        $media = $product->mediaUrls();
        if (! empty($media)) {
            $first = reset($media);
            if ($first) {
                $this->assignFeatureImageToProduct($first, (int) $postId, $product->title());
            }
        }

        return (int) $postId;
    }

    public function assignFeatureImageToProduct(string $url, int $postId, string $title = ''): void
    {
        include_once ABSPATH . 'wp-admin/includes/media.php';
        include_once ABSPATH . 'wp-admin/includes/file.php';
        include_once ABSPATH . 'wp-admin/includes/image.php';
		$attachmentId = media_sideload_image($url, $postId, $title, 'id');
        if (! is_wp_error($attachmentId) && is_numeric($attachmentId)) {
            set_post_thumbnail($postId, (int) $attachmentId);
        } elseif (is_wp_error($attachmentId)) {
	        $this->logger->error('Failed to sideload featured image from {url} for product {post_id}: {error}', [
		        'url' => $url,
		        'post_id' => $postId,
		        'error' => $attachmentId->get_error_message()
	        ]);
        }
    }

	public function addImageToProductGallery(string $url, int $postId, string $title = ''): void
	{
		include_once ABSPATH . 'wp-admin/includes/media.php';
		include_once ABSPATH . 'wp-admin/includes/file.php';
		include_once ABSPATH . 'wp-admin/includes/image.php';
		$attachmentId = media_sideload_image($url, $postId, $title, 'id');
		if (is_wp_error($attachmentId)) {
			$this->logger->error('Failed to sideload gallery image from {url} for product {post_id}: {error}', [
				'url' => $url,
				'post_id' => $postId,
				'error' => $attachmentId->get_error_message()
			]);
			return;
		}

		if ( is_null( get_post_meta( $postId, "_product_image_gallery" ) ) ) {
			add_post_meta( $postId, "_product_image_gallery", $attachmentId );
		} else {
			$images_meta = get_post_meta( $postId, "_product_image_gallery", true );
			update_post_meta( $postId, "_product_image_gallery", $images_meta . "," . $attachmentId );
		}
	}
}
