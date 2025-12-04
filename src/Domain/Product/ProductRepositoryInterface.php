<?php

declare(strict_types=1);

namespace SineFine\PromImport\Domain\Product;

use SineFine\PromImport\Domain\Product\ValueObject\Sku;

interface ProductRepositoryInterface
{
    /** Returns WP post ID or false if not found */
    public function findIdBySku(Sku $sku): int|false;

    /**
     * Persist product and return WP post ID or \WP_Error
     * @return int|\WP_Error
     */
    public function save(Product $product): int|\WP_Error;

    public function assignFeatureImageToProduct(string $url, int $postId, string $title = ''): void;

    public function addImageToProductGallery(string $url, int $postId, string $title = ''): void;
}
