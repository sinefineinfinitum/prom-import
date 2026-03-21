<?php

declare(strict_types=1);

namespace SineFine\PromImport\Tests;

use PHPUnit\Framework\TestCase;
use SineFine\PromImport\Application\Import\Dto\ProductDto;
use SineFine\PromImport\Application\Import\ImportService;
use SineFine\PromImport\Application\Import\ProductManager;
use SineFine\PromImport\Application\Import\XmlService;
use SineFine\PromImport\Domain\Exception\DownloadException;
use SineFine\PromImport\Domain\Import\Import;
use SineFine\PromImport\Domain\Import\ImportRepositoryInterface;
use SineFine\PromImport\Domain\Product\ValueObject\Price;
use SineFine\PromImport\Domain\Product\ValueObject\Sku;

class ImportServiceTest extends TestCase
{
    private $repository;
    private $xmlService;
    private $productManager;
    private ImportService $service;

    protected function setUp(): void
    {
        $this->repository = $this->createMock(ImportRepositoryInterface::class);
        $this->xmlService = $this->createMock(XmlService::class);
        $this->productManager = $this->createMock(ProductManager::class);
        $this->service = new ImportService(
            $this->repository,
            $this->xmlService,
        );
    }

    public function test_getAllImports_returns_array_from_repository(): void
    {
        $imports = [
            new Import(1, 'Import 1', 'http://url1.com'),
            new Import(2, 'Import 2', 'http://url2.com'),
        ];

        $this->repository->expects($this->once())
            ->method('findAll')
            ->willReturn($imports);

        $result = $this->service->getAllImports();

        $this->assertCount(2, $result);
        $this->assertSame($imports, $result);
    }

    public function test_createImport_saves_new_import(): void
    {
        $this->repository->expects($this->once())
            ->method('save')
            ->with($this->callback(function (Import $import) {
                return $import->getId() === null &&
                       $import->getName() === 'New Import' &&
                       $import->getUrl() === 'http://new.com';
            }))
            ->willReturn(123);

        $id = $this->service->createImport('New Import', 'http://new.com');

        $this->assertSame(123, $id);
    }

    public function test_updateImport_updates_existing_import(): void
    {
        $existingImport = new Import(1, 'Old Name', 'http://old.com');

        $this->repository->expects($this->once())
            ->method('findById')
            ->with(1)
            ->willReturn($existingImport);

        $this->repository->expects($this->once())
            ->method('save')
            ->with($this->callback(function (Import $import) {
                return $import->getId() === 1 &&
                       $import->getName() === 'Updated Name' &&
                       $import->getUrl() === 'http://updated.com' &&
                       $import->getUpdatedAt() !== null;
            }))
            ->willReturn(1);

        $success = $this->service->updateImport(1, 'Updated Name', 'http://updated.com');

        $this->assertTrue($success);
    }

    public function test_updateImport_returns_false_if_not_found(): void
    {
        $this->repository->expects($this->once())
            ->method('findById')
            ->with(999)
            ->willReturn(null);

        $this->repository->expects($this->never())
            ->method('save');

        $success = $this->service->updateImport(999, 'Name', 'http://url.com');

        $this->assertFalse($success);
    }

    public function test_deleteImport_calls_repository_delete(): void
    {
        $this->repository->expects($this->once())
            ->method('delete')
            ->with(1)
            ->willReturn(true);

        $success = $this->service->deleteImport(1);

        $this->assertTrue($success);
    }

	/**
	 * @throws DownloadException
	 */
	public function test_runImport_executes_import_logic(): void
    {
        $import = new Import(1, 'Test', 'http://test.com');
        $this->repository->method('findById')->willReturn($import);
        $this->xmlService->method('downloadXmlContent')->willReturn('<xml></xml>');
        
        $productDto = new ProductDto(
            new Sku(1),
            'Title',
            'Desc',
            new Price(10)
        );
        $this->xmlService->method('getProductsFromXml')->willReturn([$productDto]);
        $this->productManager->method( 'createProductFromDto' )->willReturn(456);

        $result = $this->service->runImport(1);

        $this->assertTrue($result['success']);
        $this->assertSame(1, $result['import_id']);
    }
}
