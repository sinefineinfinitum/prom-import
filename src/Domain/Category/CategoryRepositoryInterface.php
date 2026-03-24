<?php

namespace SineFine\PromImport\Domain\Category;

interface CategoryRepositoryInterface
{
    public function getCategoryById(int $id): ?Category;
}
