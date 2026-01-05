<?php

namespace SineFine\PromImport\Tests\Fake;


use SineFine\PromImport\Domain\Category\CategoryMappingRepositoryInterface;
use WP_Term;

class FakeCategoryMappingRepository implements CategoryMappingRepositoryInterface
{
	public function __construct(private array $mapping = [])
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
		return $externalId && array_key_exists($externalId,$this->mapping)
			? $this->mapping[$externalId]
			: null;
	}
}
