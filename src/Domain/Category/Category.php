<?php

declare(strict_types=1);

namespace SineFine\PromImport\Domain\Category;

class Category
{
    public const SINEFINE_PROMIMPORT_CATEGORIES_OPTION = 'sinefine_promimport_categories';
    public function __construct(
        private int $id,
        private string $name
    ) {
        $this->name = trim($this->name);
    }

    public function id(): int
    {
        return $this->id; 
    }
    public function name(): string
    {
        return $this->name; 
    }
}
