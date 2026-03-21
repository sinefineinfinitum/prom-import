<?php

declare(strict_types=1);

namespace SineFine\PromImport\Application\Import;

use Psr\Log\LoggerInterface;
use SineFine\PromImport\Application\Import\Dto\ProductDto;
use SineFine\PromImport\Domain\Product\ImageAttachable;
use SineFine\PromImport\Domain\Product\Product;
use SineFine\PromImport\Domain\Product\ProductManagerInterface;
use SineFine\PromImport\Domain\Product\ProductRepositoryInterface;
use WP_Error;

class ProductManager implements ProductManagerInterface
{
    public function __construct(
        private ProductRepositoryInterface $repository,
		private ImageAttachable $imageService,
	    private LoggerInterface $logger,
    ) {
    }

	/**
	 * Create a single product using DTO.
	 * Returns created post ID or WP_Error
	 *
	 * @param ProductDto $dto
	 *
	 * @return int|WP_Error
	 */
    public function createProductFromDto(ProductDto $dto): int|WP_Error
    {
        if (trim($dto->title) === '') {
            return new WP_Error('has no title', esc_html(__('Post has no title', 'spss12-import-prom-woo')));
        }

	    $product = Product::createFromDto($dto);

        $postId = $this->repository->save($product);
        if (is_wp_error($postId)) {
	        $this->logger->error('Failed to save product {sku}: {error}', [
		        'sku' => $dto->sku->value(),
		        'error' => $postId->get_error_message()
	        ]);
        }

	    return $postId;
    }

	public function addCategoryToProduct(int $productId, int $categoryId): int|WP_Error
	{
		if ($categoryId === 0) {
			$this->logger->warning('No mapping found for external category {ext_id} for product {post_id}', [
				'ext_id' => $categoryId,
				'post_id' => $productId
			]);
			return 0;
		}
		if (term_exists($categoryId, 'product_cat')) {
			wp_set_object_terms($productId, [$categoryId], 'product_cat');
			return $categoryId;
		} else {
			$this->logger->error('Category {cat_id} does not exist in Woocommerce for product {post_id}', [
				'cat_id' => $categoryId,
				'post_id' => $productId
			]);
			return new WP_Error('No such category', esc_html(__('No such category', 'spss12-import-prom-woo')));
		}
	}

	public function addImagesToProductGallery( ProductDto $dto, int $postId ): void {
	// Add gallery images (featured image is handled inside repository using the first URL)
		if ( ! empty( $dto->mediaUrls ) ) {
			$first = $dto->mediaUrls[0] ?? null;
			foreach ( $dto->mediaUrls as $url ) {
				if ( $first !== null && $url === $first ) {
					continue; // already set as featured in repository
				}
				$this->imageService->addImageToProductGallery( $url, $postId, $dto->title );
			}
		}
	}
}
