<?php

declare(strict_types=1);

namespace SineFine\PromImport\Domain\Category;

use WP_Term;

interface CategoryMappingRepositoryInterface {
	public function getCategoryMapping(): array;
	public function setCategoryMapping(array $categoryMapping): void;
	public function mapping(?int $externalId): WP_Term|array|false|null;
}
