<?php

namespace SineFine\PromImport\Tests\Fake;

use SineFine\PromImport\Domain\Product\Product;
use SineFine\PromImport\Domain\Product\ProductRepositoryInterface;
use SineFine\PromImport\Domain\Product\ValueObject\Sku;
use WP_Error;

class FakeProductRepository implements ProductRepositoryInterface
{
	public array $savedProducts = [];
	public array $galleryImages = [];

	public function __construct(private int $returnId)
	{
	}

	public function findIdBySku(Sku $sku): int|false
	{
		return false;
	}

	public function save(Product $product): int|WP_Error
	{
		$this->savedProducts[] = $product;
		return $this->returnId;
	}

	public function assignFeatureImageToProduct(string $url, int $postId, string $title = ''): void
	{
		// not needed yet
	}

	public function addImageToProductGallery(string $url, int $postId, string $title = ''): void
	{
		$this->galleryImages[] = [$url, $postId, $title];
	}

	public function findIdBySkuId( int $scuId ): int|false
	{
		// not needed yet
	}
}