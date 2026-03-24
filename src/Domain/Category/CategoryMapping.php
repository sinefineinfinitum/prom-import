<?php

namespace SineFine\PromImport\Domain\Category;

class CategoryMapping
{
    /**
     * 
     *
     * @param array<int, mixed> $mapping 
     */
    public function __construct(
        private array $mapping,
    ) {
    }
    /**
     * 
     *
     * @return array<int, mixed> 
     */
    public function getMapping(): array
    {
        return $this->mapping;
    }

    /**
     * 
     *
     * @param array<int, mixed> $mapping 
     */
    public static function create(array $mapping): CategoryMapping
    {
        return new self($mapping);
    }
}
