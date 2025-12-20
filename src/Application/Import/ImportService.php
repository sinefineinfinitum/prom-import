<?php

declare(strict_types=1);

namespace SineFine\PromImport\Application\Import;

use SineFine\PromImport\Application\Import\Dto\ProductDto;
use SineFine\PromImport\Domain\Category\CategoryMappingRepositoryInterface;
use SineFine\PromImport\Domain\Product\Product;
use SineFine\PromImport\Domain\Product\ProductRepositoryInterface;
use WP_Error;

class ImportService
{
    public function __construct(
        private ProductRepositoryInterface $repository,
	    private CategoryMappingRepositoryInterface $mappingRepository,
    ) {
    }

	/**
	 * Import or update a single product using DTO.
	 * Returns created/updated post ID or WP_Error
	 *
	 * @param ProductDto $dto
	 *
	 * @return int|WP_Error
	 */
    public function importProductFromDto(ProductDto $dto): int|WP_Error
    {
        if (trim($dto->title) === '') {
            return new WP_Error('has no title', esc_html(__('Post has no title', 'spss12-import-prom-woo')));
        }

        // Map DTO to Domain entity
        $product = new Product(
            $dto->sku,
            $dto->title,
            $dto->description,
            $dto->price,
            $dto->category,
            $dto->tags,
            $dto->mediaUrls,
            $dto->link
        );

        $postId = $this->repository->save($product);
        if (is_wp_error($postId)) {
            return $postId;
        }

        // Add gallery images (featured image is handled inside repository using the first URL)
        if (! empty($dto->mediaUrls)) {
            $first = $dto->mediaUrls[0] ?? null;
            foreach ($dto->mediaUrls as $url) {
                if ($first !== null && $url === $first) {
                    continue; // already set as featured in repository
                }
                $this->repository->addImageToProductGallery($url, (int) $postId, $dto->title);
            }
        }

        return (int) $postId;
    }

	public function addCategoryForProduct(int $productId, int $externalCategoryId): int|WP_Error
	{
		$categoryId = $this->mappingRepository->mapping($externalCategoryId);
		if (empty($categoryId)) {
			return 0;
		}
		if (term_exists($categoryId->term_id, 'product_cat')) {
			wp_set_object_terms($productId, [$categoryId->term_id], 'product_cat');
			return $categoryId->term_id;
		} else {
			return new WP_Error('No such category', esc_html(__('No such category', 'spss12-import-prom-woo')));
		}
	}
}
