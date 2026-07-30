<?php

declare(strict_types=1);

namespace SineFine\PromImport\Application\Import\Dto;

use JsonSerializable;

class CategoryDto implements JsonSerializable
{
    public function __construct(
        private string|int $id,
        private string $name
    ) {
        $this->id = (string) $id;
    }

    public static function create(string|int $id, string $name): self
    {
        return new self($id, $name);
    }

    public function id(): string
    {
        return $this->id; 
    }
    public function name(): string
    {
        return $this->name; 
    }

    /**
     * @return array{id: string, name: string}
     */
    public function jsonSerialize(): array
    {
        return ['id' => $this->id, 'name' => $this->name];
    }
}
