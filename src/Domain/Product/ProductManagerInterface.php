<?php

declare( strict_types=1 );

namespace SineFine\PromImport\Domain\Product;

use SineFine\PromImport\Application\Import\Dto\ProductDto;
use WP_Error;

interface ProductManagerInterface
{
    public function createProductFromDto( ProductDto $dto ): int|WP_Error;

    public function addCategoryToProduct( int $productId, int $categoryId ): int|WP_Error;

    public function addImagesToProductGallery( ProductDto $dto, int $postId ): void;
}
