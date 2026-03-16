<?php

namespace SineFine\PromImport\Tests\Fake;

use SineFine\PromImport\Application\Import\Dto\ProductDto;
use SineFine\PromImport\Domain\Product\Product;
use SineFine\PromImport\Domain\Product\ProductRepositoryInterface;
use SineFine\PromImport\Domain\Product\ValueObject\Sku;
use WP_Error;

class FakeProductRepository implements ProductRepositoryInterface
{
	public array $savedProducts = [];

	public function __construct(
		private int $returnId,
		private bool $shouldReturnError = false,
	) {}

	public function findIdBySku(Sku $sku): int|false
	{
		return false;
	}

	public function save(?Product $product): int|WP_Error
	{
		if( $this->shouldReturnError) {
			return new WP_Error('Failed to save product', 'error');
		}
		$this->savedProducts[] = $product;
		return $this->returnId;
	}

	public function findIdBySkuId( int $scuId ): int|false
	{
		// not needed yet
	}

	public function updateProductPrice( ProductDto $dto ): int|false|WP_Error {
		// TODO: Implement updateProductPrice() method.
	}
}