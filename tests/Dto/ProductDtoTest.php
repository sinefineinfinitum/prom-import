<?php

declare(strict_types=1);

namespace SineFine\PromImport\Tests\Dto;

use PHPUnit\Framework\TestCase;
use SineFine\PromImport\Application\Import\Dto\CategoryDto;
use SineFine\PromImport\Application\Import\Dto\ProductDto;
use SineFine\PromImport\Domain\Product\ValueObject\Price;
use SineFine\PromImport\Domain\Product\ValueObject\Sku;

class ProductDtoTest extends TestCase
{
    public function test_toArray_fromArray_roundtrip_preserves_currency(): void
    {
        $dto = new ProductDto(new Sku('SKU1'), 'Title', 'Desc', new Price(99.99, 'USD'));

        $restored = ProductDto::fromArray($dto->toArray());

        $this->assertSame('USD', $restored->price->currency());
    }

    public function test_toArray_fromArray_roundtrip_preserves_all_fields(): void
    {
        $dto = new ProductDto(
            new Sku('SKU1'),
            'Title',
            'Desc',
            new Price(99.99, 'USD'),
            CategoryDto::create(5, 'Cat'),
            ['http://img1.jpg'],
            'http://link.com'
        );

        $restored = ProductDto::fromArray($dto->toArray());

        $this->assertSame('SKU1', $restored->sku->value());
        $this->assertSame('Title', $restored->title);
        $this->assertSame('Desc', $restored->description);
        $this->assertSame(99.99, $restored->price->amount());
        $this->assertSame('USD', $restored->price->currency());
        $this->assertNotNull($restored->category);
        $this->assertSame('5', $restored->category->id());
        $this->assertSame('Cat', $restored->category->name());
        $this->assertSame(['http://img1.jpg'], $restored->mediaUrls);
        $this->assertSame('http://link.com', $restored->link);
    }
}
