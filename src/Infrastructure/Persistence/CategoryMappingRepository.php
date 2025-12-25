<?php

declare(strict_types=1);

namespace SineFine\PromImport\Infrastructure\Persistence;

use SineFine\PromImport\Domain\Category\CategoryMappingRepositoryInterface;
use WP_Term;

class CategoryMappingRepository implements CategoryMappingRepositoryInterface
{
	private const CATEGORY_MAPPING_NAME = 'prom_categories_input';

	/**
	 * @return array<int, string>
	 */
	public function getCategoryMapping(): array
	{
		$categoryMapping = get_option( self::CATEGORY_MAPPING_NAME, [] );
		return is_array($categoryMapping)
				? array_combine(
				array_map('intval', array_column($categoryMapping, 'id' )),
					array_column($categoryMapping, 'selected' )
				)
				:[];
	}

	/**
	 * @param array<int, array<string, int>> $categoryMapping
	 */

	public function setCategoryMapping( array $categoryMapping ): void
	{
		update_option( self::CATEGORY_MAPPING_NAME, $categoryMapping);
	}

	/**
	 * @param int $externalId
	 *
	 * @return WP_Term|false|null
	 */
	public function mapping(int $externalId): WP_Term|false|null
	{
		$categoryMapping = $this->getCategoryMapping();
		echo '<pre>';
		print_r($categoryMapping);
		echo '</pre>';
		return $externalId && $categoryMapping[$externalId]
			? get_term_by( 'id', (int)$categoryMapping[$externalId], 'product_cat')
			: null;
	}
}