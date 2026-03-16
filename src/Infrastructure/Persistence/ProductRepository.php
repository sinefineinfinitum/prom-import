<?php

declare(strict_types=1);

namespace SineFine\PromImport\Infrastructure\Persistence;

use SineFine\PromImport\Application\Import\Dto\ProductDto;
use SineFine\PromImport\Domain\Product\ImageAttachable;
use SineFine\PromImport\Domain\Product\Product;
use SineFine\PromImport\Domain\Product\ProductRepositoryInterface;
use SineFine\PromImport\Domain\Product\ValueObject\Sku;
use WP_Error;
use WP_Query;

class ProductRepository implements ProductRepositoryInterface
{
	public function __construct(
		private ImageAttachable $imageService,
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
	 * Persist Domain Product and return post ID or WP_Error
	 * @param Product $product
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
                $this->imageService->assignFeatureImageToProduct($first, (int) $postId, $product->title());
            }
        }

        return (int) $postId;
    }

	public function updateProductPrice(ProductDto $dto): int|false|WP_Error
	{
		$postId = $this->findIdBySku( $dto->sku );
		if ( ! $postId ) {
			return false;
		}

		if ( ! function_exists( 'wc_get_product' ) ) {
			return new WP_Error( 'no function', esc_html( __( 'No wc_get_product function', 'spss12-import-prom-woo' ) ) );
		}

		$product = wc_get_product( $postId );
		if ( ! $product ) {
			return false;
		}

		$amount = $dto->price->amount();

		if ( $amount > 0 ) {
			$product->set_price( $amount );
			$product->set_regular_price( $amount );
			$product->save();

			return $postId;
		}
		return false;
	}

}
