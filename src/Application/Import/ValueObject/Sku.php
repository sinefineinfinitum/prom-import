<?php

namespace SineFine\PromImport\Application\Import\ValueObject;

class Sku
{
    private int $value;

    public function __construct(int $value)
    {
        if ($value <= 0) {
            throw new \InvalidArgumentException('ProductId must be positive integer');
        }
        $this->value = $value;
    }

    public function value(): int
    {
        return $this->value;
    }
}
