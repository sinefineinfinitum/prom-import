<?php

namespace SineFine\PromImport\Infrastructure\Persistence;

use SineFine\PromImport\Domain\Category\Category;
use SineFine\PromImport\Domain\Category\CategoryRepositoryInterface;
use WP_Term;

class CategoryRepository implements CategoryRepositoryInterface
{
    public function getCategoryById(int $id): ?Category
    {
        $wooCategory = get_term_by( 'id', $id, 'product_cat');
        return $wooCategory instanceof WP_Term ? new Category($wooCategory->term_id, $wooCategory->name) : null;
    }
}
