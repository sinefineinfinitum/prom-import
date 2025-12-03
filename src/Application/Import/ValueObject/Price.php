<?php

namespace SineFine\PromImport\Application\Import\ValueObject;

class Price
{
    private float $amount;
    private string $currency;

    public function __construct(float $amount, string $currency = 'UAH')
    {
        $this->amount = max(0.0, $amount);
        $this->currency = $currency !== '' ? $currency : 'UAH';
    }

    public function amount(): float
    {
        return $this->amount;
    }

    public function currency(): string
    {
        return $this->currency;
    }
}
