<?php

declare(strict_types=1);

namespace SineFine\PromImport\Domain\Product;

use SineFine\PromImport\Domain\Product\ValueObject\Sku;
use WP_Error;

interface ProductRepositoryInterface
{
    /** Returns WP post ID or false if not found */
    public function findIdBySku(Sku $sku): int|false;

	public function findIdBySkuId(int $scuId): int|false;


	/** Persist product and return WP post ID or WP_Error */
    public function save(Product $product): int|WP_Error;

    public function assignFeatureImageToProduct(string $url, int $postId, string $title = ''): void;

    public function addImageToProductGallery(string $url, int $postId, string $title = ''): void;
}
