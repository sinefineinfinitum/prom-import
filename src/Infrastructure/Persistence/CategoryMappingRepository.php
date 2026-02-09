<?php

declare(strict_types=1);

namespace SineFine\PromImport\Infrastructure\Persistence;

use SineFine\PromImport\Domain\Category\Category;
use SineFine\PromImport\Domain\Category\CategoryMappingRepositoryInterface;
use SineFine\PromImport\Domain\Common\OptionRepositoryInterface;
use WP_Term;

class CategoryMappingRepository implements CategoryMappingRepositoryInterface
{
    public function __construct(
      private OptionRepositoryInterface $optionRepository,
    ){
    }

	/**
	 * @return array<int, string>
	 */
	public function getCategoryMapping(): array
	{
		$categoryMapping = $this->optionRepository->getOption( Category::CATEGORY_MAPPING_OPTION, [] );
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
        $this->optionRepository->updateOption( Category::CATEGORY_MAPPING_OPTION, $categoryMapping);
	}

	/**
	 * @param int $externalId
	 *
	 * @return WP_Term|false|null
	 */
	public function mapping(int $externalId): WP_Term|false|null
	{
		$categoryMapping = $this->getCategoryMapping();
		return $externalId && array_key_exists($externalId, $categoryMapping)
			? get_term_by( 'id', (int)$categoryMapping[$externalId], 'product_cat')
			: null;
	}
}