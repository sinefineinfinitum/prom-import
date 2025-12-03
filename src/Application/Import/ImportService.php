<?php

namespace SineFine\PromImport\Application\Import;

use SineFine\PromImport\Infrastructure\Persistence\ProductRepository;
use WP_Error;

class ImportService
{
    public function __construct(
        private ProductRepository $repository,
    ) {
    }

    /**
     * Import a single product from provided payload.
     * Returns created post ID or WP_Error.
     */
    public function importSingle(int $skuId, array $payload = []): int|WP_Error
    {
        $existingId = $this->repository->findIdBySkuId($skuId);
        if ($existingId) {
            return $existingId;
        }

	    $preparedPostData = $this->preparePostData($payload);
	    $preparedProductData = $this->preparedProductData($payload);
	    if ( $preparedPostData['title'] === '' ) {
		    return new WP_Error(
			    'has no title',
			    __( 'Post has no title', 'prom-import' )
		    );
	    }
	    $postId = wp_insert_post( $preparedPostData, true );

	    if ( is_wp_error( $postId ) ) {
		    return $postId;
	    }

	    // Store sku id
	    update_post_meta( $postId, '_sku', $skuId );

	    // Set product type simple
	    wp_set_object_terms( $postId, 'simple', 'product_type' );

	    // Set WooCommerce product data
	    if ( function_exists( 'wc_get_product' ) ) {
		    $product = wc_get_product( $postId );
		    if ( $product ) {
			    if ( $preparedProductData['price'] > 0 ) {
				    $product->set_price( $preparedProductData['price'] );
				    $product->set_regular_price( $preparedProductData['price'] );
			    }
			    $product->save();
		    }
	    }

	    // Assign featured image and gallery
	    if ( ! empty($preparedProductData['mediaUrls'] ) ) {
		    foreach ($preparedProductData['mediaUrls'] as $key => $imageUrl) {
			    if ( $key === 0 ) {
				    $this->repository->assignFeatureImageToProduct($imageUrl, $postId, $preparedPostData['post_title']);
			    }
			    $this->repository->addImageToProductGallery($imageUrl, $postId);
		    }
	    }

	    return (int) $postId;

    }

	public function preparePostData(array $payload = []): array
	{

		$title               = (string) ( $payload['title'] ?? '' );
		$description         = (string) ( $payload['description'] ?? '' );
		return  [
			'post_type'    => 'product',
			'post_title'   => sanitize_text_field( $title ),
			'post_status'  => 'publish',
			'post_author'  => get_current_user_id(),
			'post_excerpt' => wp_kses_post( $description ),
			'post_content' => wp_kses_post( preg_replace( [
				'/<\/?a( [^>]*)?>/i',
				'/[^@\s]*@[^@\s]*\.[^@\s]*/',
				'/(?<!src=")(?:(https?)+[:\/]+([^\s<]+)|(www\.[^\s<]+?\.[^\s<]+))(?<![.,:])/i',
			], [ '', '', '' ], $description ) ),
		];
	}

	public function preparedProductData( array $payload = [] ): array {
		return [
			'price'     => isset( $payload['price'] ) ? (float) $payload['price'] : 0.0,
			'category'  => (string) ( $payload['category'] ?? '' ),
			'mediaUrls' => $payload['featured_media'] ?? [],
		];
	}
}
