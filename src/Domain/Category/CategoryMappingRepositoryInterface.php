<?php

declare(strict_types=1);

namespace SineFine\PromImport\Domain\Category;

use WP_Term;

interface CategoryMappingRepositoryInterface
{
	/**
	 * @return array<int, string>
	 */
	public function getCategoryMapping(): array;

	/**
	 * @param array<int, array<string, int>> $categoryMapping
	 */
	public function setCategoryMapping(array $categoryMapping): void;

	/**
	 * @param int $externalId
	 *
	 * @return WP_Term|false|null
	 */
	public function mapping(int $externalId): WP_Term|false|null;
}
