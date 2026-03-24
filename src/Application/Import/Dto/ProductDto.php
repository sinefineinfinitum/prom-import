<?php

declare(strict_types=1);

namespace SineFine\PromImport\Application\Import\Dto;

use SineFine\PromImport\Domain\Product\ValueObject\Price;
use SineFine\PromImport\Domain\Product\ValueObject\Sku;

class ProductDto
{
    public ?int $existedId = null;
    public ?string $categoryName = null;

    /**
     * @param array<int, string> $mediaUrls
     */
    public function __construct(
		public Sku $sku,
		public string $title,
		public string $description,
		public Price $price,
		public ?CategoryDto $category = null,
		public array $mediaUrls = [],
		public string $link = ''
    ) {}

	/**
	 * @param array<int, string> $mediaUrls
	 */
	public static function create(Sku $sku, string $title, string $description, Price $price, ?CategoryDto $category = null, array $mediaUrls = [], string $link = ''): self
	{
		return new self($sku, $title, $description, $price, $category, $mediaUrls, $link);
	}
}
