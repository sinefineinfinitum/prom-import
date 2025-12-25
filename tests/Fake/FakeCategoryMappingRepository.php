<?php

namespace SineFine\PromImport\Tests\Fake;


use SineFine\PromImport\Domain\Category\CategoryMappingRepositoryInterface;
use WP_Term;

class FakeCategoryMappingRepository implements CategoryMappingRepositoryInterface
{
	public function __construct(private mixed $mapping = [])
	{
	}

	public function getCategoryMapping(): array
	{
		return is_array($this->mapping) ? $this->mapping : [];
	}

	public function setCategoryMapping(array $categoryMapping): void
	{
		$this->mapping = $categoryMapping;
	}

	public function mapping(int $externalId): WP_Term|false|null
	{
		return $externalId && $this->mapping[$externalId]
			? $this->mapping[$externalId]
			: null;
	}
}
