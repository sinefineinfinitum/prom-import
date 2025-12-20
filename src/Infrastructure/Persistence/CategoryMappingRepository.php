<?php

declare(strict_types=1);

namespace SineFine\PromImport\Infrastructure\Persistence;

use SineFine\PromImport\Domain\Category\CategoryMappingRepositoryInterface;
use WP_Term;

class CategoryMappingRepository implements CategoryMappingRepositoryInterface
{
	private const CATEGORY_MAPPING_NAME = 'prom_categories_input';
	public function getCategoryMapping(): array
	{
		return array_combine(
			array_column( get_option( self::CATEGORY_MAPPING_NAME, [] ), 'id' ),
			array_column( get_option( self::CATEGORY_MAPPING_NAME, [] ), 'selected' )
		);
	}

	public function setCategoryMapping( array $categoryMapping ): void
	{
		update_option( self::CATEGORY_MAPPING_NAME, $categoryMapping);
	}

	public function mapping(?int $externalId): WP_Term|array|false|null
	{
		$categoryMapping = $this->getCategoryMapping();
		return $externalId
			? get_term_by( 'id', (int)$categoryMapping[$externalId], 'product_cat')
			: null;
	}
}