<?php

declare(strict_types=1);

namespace SineFine\PromImport\Domain\Product\ValueObject;

use JsonSerializable;

class Price implements JsonSerializable
{
    public const DEFAULT_CURRENCY = 'UAH';
    private float $amount;
    private string $currency;

    public function __construct(float $amount, string $currency = self::DEFAULT_CURRENCY)
    {
        $this->amount = max(0.0, $amount);
        $this->currency = $currency !== '' ? $currency : self::DEFAULT_CURRENCY;
    }

    public function amount(): float
    {
        return $this->amount;
    }

    public function currency(): string
    {
        return $this->currency;
    }

    public static function create(float $amount, string $currency = 'UAH'): self
    {
        return new self($amount, $currency);
    }

    /**
     * @return array{amount: float, currency: string}
     */
    public function jsonSerialize(): array
    {
        return ['amount' => $this->amount, 'currency' => $this->currency];
    }
}
