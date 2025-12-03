<?php

namespace SineFine\PromImport\Application\Import\Dto;

class CategoryDto
{
    public int $id;
    public string $name;
    public function __construct(int $id, string $name)
    {
        $this->id = $id;
        $this->name = $name;
    }
}
