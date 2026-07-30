<?php

declare(strict_types=1);

namespace SineFine\PromImport\Tests;

use PHPUnit\Framework\TestCase;
use SineFine\PromImport\Domain\Exception\InvalidSkuException;
use SineFine\PromImport\Domain\Product\ValueObject\Price;
use SineFine\PromImport\Domain\Product\ValueObject\Sku;

class ValueObjectTest extends TestCase
{
    public function test_sku_must_not_be_empty(): void
    {
        $this->expectException(InvalidSkuException::class);
        new Sku('');
    }

    public function test_sku_value_returns_string(): void
    {
        $sku = new Sku(123);
        $this->assertSame('123', $sku->value());
    }

    public function test_price_normalizes_amount_and_currency(): void
    {
        $p = new Price(-10, '');
        $this->assertSame(0.0, $p->amount());
        $this->assertSame('UAH', $p->currency());

        $p2 = new Price(10.5, 'USD');
        $this->assertSame(10.5, $p2->amount());
        $this->assertSame('USD', $p2->currency());
    }
}
