<?php

declare(strict_types=1);

namespace SineFine\PromImport\Application\Import\Dto;

use JsonSerializable;

class CategoryDto implements JsonSerializable
{
    public function __construct(
        private int $id,
        private string $name
    ) {
    }

    public static function create(int $id, string $name): self
    {
        return new self($id, $name);
    }

    public function id(): int
    {
        return $this->id; 
    }
    public function name(): string
    {
        return $this->name; 
    }

    /**
     * @return array{id: int, name: string}
     */
    public function jsonSerialize(): array
    {
        return ['id' => $this->id, 'name' => $this->name];
    }
}
