<?php

declare(strict_types=1);

namespace SineFine\PromImport\Tests;

use PHPUnit\Framework\TestCase;
use SineFine\PromImport\Application\Import\Dto\CategoryDto;
use SineFine\PromImport\Application\Import\Dto\ProductDto;
use SineFine\PromImport\Application\Import\ImportService;
use SineFine\PromImport\Domain\Product\Product;
use SineFine\PromImport\Domain\Product\ValueObject\Price;
use SineFine\PromImport\Domain\Product\ValueObject\Sku;
use SineFine\PromImport\Tests\Fake\FakeCategoryMappingRepository;
use SineFine\PromImport\Tests\Fake\FakeProductRepository;

class ImportServiceTest extends TestCase
{
    public function test_import_returns_error_when_title_is_empty(): void
    {
        $repo = new FakeProductRepository(123);
        $mapping = new FakeCategoryMappingRepository();
        $service = new ImportService($repo, $mapping);

        $dto = new ProductDto(new Sku(1), '', 'desc', new Price(10));
        $res = $service->importProductFromDto($dto);
        $this->assertTrue(is_wp_error($res));
        $this->assertSame('has no title', $res->code);
    }

    public function test_import_saves_product_and_adds_gallery_images_skipping_first(): void
    {
        $repo = new FakeProductRepository(42);
        $mapping = new FakeCategoryMappingRepository();
        $service = new ImportService($repo, $mapping);

        $dto = new ProductDto(
            new Sku(10),
            'Title',
            'Desc',
            new Price(9.99, 'USD'),
            new CategoryDto(5, 'Cat'),
            ['t1'],
            ['https://img/1.jpg', 'https://img/2.jpg', 'https://img/3.jpg'],
            'https://example/item/10'
        );

        $postId = $service->importProductFromDto($dto);
        $this->assertSame(42, $postId);
        // save called once with Product
        $this->assertCount(1, $repo->savedProducts);
        $this->assertInstanceOf(Product::class, $repo->savedProducts[0]);
        // gallery added for all except first
        $this->assertSame([
            ['https://img/2.jpg', 42, 'Title'],
            ['https://img/3.jpg', 42, 'Title'],
        ], $repo->galleryImages);
        // featured not directly tested here (handled inside repository)
    }

    public function test_addCategoryForProduct_returns_zero_when_no_mapping(): void
    {
        $repo = new FakeProductRepository(1);
        $mapping = new FakeCategoryMappingRepository([]);
        $service = new ImportService($repo, $mapping);

        $this->assertSame(0, $service->addCategoryForProduct(10, 999));
    }

    public function test_addCategoryForProduct_returns_zero_when_term_not_exists(): void
    {
        $repo = new FakeProductRepository(1);
        $mapping = new FakeCategoryMappingRepository([777 => 777]);
        $service = new ImportService($repo, $mapping);

        $res = $service->addCategoryForProduct(10, 2);
        $this->assertTrue($res === 0);
    }
}


