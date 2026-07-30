<?php

declare(strict_types=1);

namespace SineFine\PromImport\Domain\Product\ValueObject;

use JsonSerializable;
use SineFine\PromImport\Domain\Exception\InvalidSkuException;

class Sku implements JsonSerializable
{
    private string $value;

    public function __construct(string|int $value)
    {
        $value = (string) $value;
        if ($value === '') {
            throw InvalidSkuException::emptyValue();
        }
        $this->value = $value;
    }

    public function value(): string
    {
        return $this->value;
    }

    public static function create(string|int $value): self
    {
        return new self($value);
    }

    /**
     * @return array{value: string}
     */
    public function jsonSerialize(): array
    {
        return ['value' => $this->value];
    }
}
