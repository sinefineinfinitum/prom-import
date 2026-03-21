<?php

declare(strict_types=1);

namespace SineFine\PromImport\Tests;

use PHPUnit\Framework\TestCase;
use Psr\Log\LoggerInterface;
use SineFine\PromImport\Application\Import\Dto\ProductDto;
use SineFine\PromImport\Application\Import\ProductManager;
use SineFine\PromImport\Domain\Product\ImageAttachable;
use SineFine\PromImport\Domain\Product\ValueObject\Price;
use SineFine\PromImport\Domain\Product\ValueObject\Sku;
use SineFine\PromImport\Tests\Fake\FakeImageService;
use SineFine\PromImport\Tests\Fake\FakeProductRepository;
use WP_Error;

class ProductManagerTest extends TestCase
{
    private LoggerInterface $logger;
	private function createService($repo, $imageService): ProductManager
	{
		$this->logger = $this->createMock(LoggerInterface::class);

		return new ProductManager($repo, $imageService, $this->logger);
	}

    public function test_import_returns_error_when_title_is_empty(): void
    {
        $repo = new FakeProductRepository(123);
	    $imageService = new FakeImageService();
        $service = $this->createService($repo, $imageService);

        $dto = new ProductDto(new Sku(1), '   ', 'desc', new Price(10));
        $res = $service->createProductFromDto($dto);
        $this->assertTrue(is_wp_error($res));
        $this->assertSame('has no title', $res->code);
    }

	public function test_import_returns_error_and_logs_when_product_not_saved(): void
	{
		$repo = new FakeProductRepository(123, true);
		$imageService = new FakeImageService();
		$service = $this->createService($repo, $imageService);

		$dto = new ProductDto(new Sku(1), 'title', 'desc', new Price(10));

        $this->logger->expects($this->once())
            ->method('error')
            ->with(
                'Failed to save product {sku}: {error}',
                ['sku' => 1, 'error' => 'error']
            );

		$res = $service->createProductFromDto($dto);
		$this->assertTrue(is_wp_error($res));
		$this->assertSame('Failed to save product', $res->code);
	}

    public function test_addCategoryToProduct_logs_error_when_no_mapping(): void
    {
        $repo = new FakeProductRepository(1);
	    $imageService = new FakeImageService();
        $service = $this->createService($repo, $imageService);

        $this->logger->expects($this->once())
            ->method('error')
            ->with(
                'Category {cat_id} does not exist in Woocommerce for product {post_id}',
                ['cat_id' => 999, 'post_id' => 10]
            );
		$error = new WP_Error('No such category', esc_html(__('No such category', 'spss12-import-prom-woo')));

        $this->assertEquals( $error, $service->addCategoryToProduct(10, 999));
    }

    public function test_addCategoryToProduct_returns_error_and_logs_when_term_not_exists(): void
    {
        $repo = new FakeProductRepository(1);
	    $imageService = new FakeImageService();
        $service = $this->createService($repo, $imageService);

        $this->logger->expects($this->once())
            ->method('error')
            ->with(
                'Category {cat_id} does not exist in Woocommerce for product {post_id}',
                ['cat_id' => 777, 'post_id' => 10]
            );

        $res = $service->addCategoryToProduct(10, 777);
        $this->assertTrue(is_wp_error($res));
        $this->assertSame('No such category', $res->code);
    }

    public function test_addImagesToProductGallery_skips_first_image_and_adds_others(): void
    {
        $repo = new FakeProductRepository(42);
        $imageService = $this->createMock( ImageAttachable::class);
        $service = $this->createService($repo, $imageService);

        $dto = new ProductDto(
            new Sku(10),
            'Title',
            'Desc',
            new Price(9.99),
            null,
            ['https://img/1.jpg', 'https://img/2.jpg', 'https://img/3.jpg']
        );

        $expectedUrls = ['https://img/2.jpg', 'https://img/3.jpg'];
        $actualUrls = [];

        $imageService->expects($this->exactly(2))
            ->method('addImageToProductGallery')
            ->willReturnCallback(function ( $url ) use (&$actualUrls) {
                $actualUrls[] = $url;
            });

        $service->addImagesToProductGallery($dto, 42);

        $this->assertSame($expectedUrls, $actualUrls);
    }
}


