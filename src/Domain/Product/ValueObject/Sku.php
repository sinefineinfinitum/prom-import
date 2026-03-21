<?php

declare(strict_types=1);

namespace SineFine\PromImport\Domain\Product\ValueObject;

use InvalidArgumentException;
use JsonSerializable;

class Sku implements JsonSerializable
{
    private int $value;

    public function __construct(int $value)
    {
        if ($value <= 0) {
            throw new InvalidArgumentException('ProductId must be positive integer');
        }
        $this->value = $value;
    }

    public function value(): int
    {
        return $this->value;
    }

	public static function create(int $value): self
	{
		return new self($value);
	}

	/**
	 * @return array{value: int}
	 */
	public function jsonSerialize(): array
	{
		return ['value' => $this->value];
	}
}
