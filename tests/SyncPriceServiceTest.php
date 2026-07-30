<?php

declare(strict_types=1);

namespace SineFine\PromImport\Tests;

use PHPUnit\Framework\TestCase;
use Psr\Log\LoggerInterface;
use SineFine\PromImport\Application\Sync\SyncPriceService;
use SineFine\PromImport\Application\Import\XmlService;
use SineFine\PromImport\Domain\Exception\ImportNotFoundException;
use SineFine\PromImport\Domain\Import\Import;
use SineFine\PromImport\Domain\Import\ImportRepositoryInterface;
use SineFine\PromImport\Domain\Product\ProductRepositoryInterface;
use SineFine\PromImport\Application\Import\Dto\ProductDto;
use SineFine\PromImport\Domain\Product\ValueObject\Price;
use SineFine\PromImport\Domain\Product\ValueObject\Sku;
use SimpleXMLElement;
use Throwable;

class SyncPriceServiceTest extends TestCase
{
    private $importRepository;
    private $xmlService;
    private $productRepository;
    private SyncPriceService $service;

    protected function setUp(): void
    {
        $this->importRepository = $this->createMock(ImportRepositoryInterface::class);
        $this->xmlService = $this->createMock(XmlService::class);
        $this->productRepository = $this->createMock(ProductRepositoryInterface::class);
        
        $this->service = new SyncPriceService(
            $this->importRepository,
            $this->xmlService,
            $this->productRepository,
            $this->createMock(LoggerInterface::class),
        );
    }

    public function test_syncPrices_success(): void
    {
        $importId = 123;
        $importUrl = 'http://example.com/feed.xml';
        $import = new Import($importId, 'Test Import', $importUrl);
        
        $this->importRepository->expects($this->once())
            ->method('findById')
            ->with($importId)
            ->willReturn($import);
            
        $xml = new SimpleXMLElement('<yml_catalog></yml_catalog>');
        $this->xmlService->expects($this->once())
            ->method('getXmlFromUrl')
            ->with($importUrl)
            ->willReturn($xml);
            
        $productDto = ProductDto::create(
            Sku::create(101),
            'Product 1',
            'Description',
            Price::create(100.0, 'UAH'),
            null,
            [],
            ''
        );
        
        $this->xmlService->expects($this->once())
            ->method('getProductsFromXml')
            ->with($xml)
            ->willReturn([$productDto]);
            
        $this->productRepository->expects($this->once())
            ->method('updateProductPrice')
            ->with($productDto)
            ->willReturn(500); // postId

        $this->importRepository->expects($this->once())
            ->method('save')
            ->with($this->callback(fn($imp) => $imp->getUpdatedAt() !== null));

        $result = $this->service->syncPrices($importId);

        $this->assertTrue($result['success']);
        $this->assertSame(1, $result['updated']);
        $this->assertSame(1, $result['total']);
        $this->assertSame(0, $result['errors']);
    }

    public function test_syncPrices_throws_exception_if_import_not_found(): void
    {
        $this->importRepository->method('findById')->willReturn(null);
        
        $this->expectException(ImportNotFoundException::class);
        
        $this->service->syncPrices(999);
    }

    public function test_syncPrices_counts_errors(): void
    {
        $importId = 123;
        $import = new Import($importId, 'Test Import', 'http://example.com/feed.xml');
        $this->importRepository->method('findById')->willReturn($import);
        
        $xml = new SimpleXMLElement('<yml_catalog></yml_catalog>');
        $this->xmlService->method('getXmlFromUrl')->willReturn($xml);
        
        $productDto = ProductDto::create(Sku::create(101), 'P1', '', Price::create(10, 'UAH'), null, [], '');
        $this->xmlService->method('getProductsFromXml')->willReturn([$productDto]);
        
        $this->productRepository->method('updateProductPrice')
            ->willThrowException(new \Exception('Update failed'));

        $result = $this->service->syncPrices($importId);

        $this->assertTrue($result['success']);
        $this->assertSame(0, $result['updated']);
        $this->assertSame(1, $result['total']);
        $this->assertSame(1, $result['errors']);
    }
}
