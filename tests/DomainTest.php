<?php

declare(strict_types=1);

namespace SineFine\PromImport\Tests;

use PHPUnit\Framework\TestCase;
use SineFine\PromImport\Application\Import\Dto\FeedDto;
use SineFine\PromImport\Application\Import\Dto\ProductDto;
use SineFine\PromImport\Domain\Category\Category;
use SineFine\PromImport\Domain\Exception\DomainException;
use SineFine\PromImport\Domain\Exception\InvalidImportException;
use SineFine\PromImport\Domain\Feed\Feed;
use SineFine\PromImport\Domain\Product\Product;
use SineFine\PromImport\Domain\Product\ValueObject\Price;
use SineFine\PromImport\Domain\Product\ValueObject\Sku;

class DomainTest extends TestCase
{
    public function test_category_trims_name_and_returns_values(): void
    {
        $category = new Category(123, "  Electronics  ");
        $this->assertSame(123, $category->id());
        $this->assertSame("Electronics", $category->name());
    }

    public function test_product_trims_title_and_returns_values(): void
    {
        $sku = new Sku(123);
        $price = new Price(100.0, "UAH");
        $category = new Category(1, "Cat");
        $mediaUrls = ["https://example.com/image.jpg"];
        $link = "https://example.com/product";

        $product = new Product(
            $sku,
            "  Product Title  ",
            "Description",
            $price,
            $category,
            $mediaUrls,
            $link
        );

        $this->assertSame($sku, $product->sku());
        $this->assertSame("Product Title", $product->title());
        $this->assertSame("Description", $product->description());
        $this->assertSame($price, $product->price());
        $this->assertSame($category, $product->category());
        $this->assertSame($mediaUrls, $product->mediaUrls());
        $this->assertSame($link, $product->link());
    }

    public function test_product_create_from_dto(): void
    {
        $dto = new ProductDto(
            new Sku(123),
            "Title",
            "Desc",
            new Price(10.0, "UAH"),
            null,
            ["url"],
            "link"
        );

        $product = Product::createFromDto($dto);

        $this->assertSame("Title", $product->title());
        $this->assertSame(123, $product->sku()->value());
        $this->assertNull($product->category());
    }

    public function test_feed_returns_values_and_filename(): void
    {
        $feed = new Feed(1600000000, "example.com", "<xml/>");
        $this->assertSame(1600000000, $feed->timestamp());
        $this->assertSame("example.com", $feed->domain());
        $this->assertSame("<xml/>", $feed->content());
        $this->assertSame("example.com_1600000000.xml", $feed->filename());
    }

    public function test_feed_from_dto(): void
    {
        $dto = new FeedDto(
            1600000000,
            "example.com",
            "<xml/>"
        );

        $feed = Feed::fromDto($dto);

        $this->assertSame(1600000000, $feed->timestamp());
        $this->assertSame("example.com", $feed->domain());
        $this->assertSame("<xml/>", $feed->content());
    }

    public function test_domain_exception_returns_user_message_and_empty_context(): void
    {
        $exception = new class("Error message") extends DomainException {};
        
        $this->assertSame("Error message", $exception->getUserMessage());
        $this->assertSame([], $exception->getContext());
    }

    public function test_invalid_import_exception_factory(): void
    {
        // Mocking WP functions might be needed if they are not available in test environment
        // But usually there are fakes or bootstrap handles it.
        if (!function_exists('__')) {
            function __($text, $domain) { return $text; }
        }
        if (!function_exists('esc_html')) {
            function esc_html($text) { return $text; }
        }

        $exception = InvalidImportException::importFromDto("Field missing");
        
        $this->assertStringContainsString("Invalid product data: ", $exception->getMessage());
        $this->assertStringContainsString("Field missing", $exception->getMessage());
    }
}
