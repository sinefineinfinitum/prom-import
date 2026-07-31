<?php

declare(strict_types=1);

namespace SineFine\PromImport\Tests;

use PHPUnit\Framework\TestCase;
use SimpleXMLElement;
use SineFine\PromImport\Application\Import\Dto\ProductDto;
use SineFine\PromImport\Application\Import\ImportBatchService;
use SineFine\PromImport\Application\Import\XmlService;
use SineFine\PromImport\Domain\Import\Import;
use SineFine\PromImport\Domain\Import\ImportRepositoryInterface;
use SineFine\PromImport\Domain\Product\ValueObject\Price;
use SineFine\PromImport\Domain\Product\ValueObject\Sku;
use SineFine\PromImport\Infrastructure\Queue\QueueManager;

class QueueManagerTest extends TestCase
{
    public function test_legacy_run_delegates_to_batch_service_for_progress(): void
    {
        $repository = $this->createMock(ImportRepositoryInterface::class);
        $xmlService = $this->createMock(XmlService::class);
        $batchService = $this->createMock(ImportBatchService::class);

        $import = new Import(1, 'Test', 'http://test.com');
        $repository->method('findById')->with(1)->willReturn($import);

        $xml = new SimpleXMLElement('<root/>');
        $xmlService->method('getXmlFromUrl')->willReturn($xml);

        $dtos = [];
        for ($i = 0; $i < 15; $i++) {
            $dtos[] = ProductDto::create(new Sku("SKU$i"), "Product $i", '', new Price(10.0));
        }
        $xmlService->method('getProductsFromXml')->willReturn($dtos);

        $batchService->expects($this->once())
            ->method('process')
            ->with(
                $this->callback(
                    function (array $args) {
                        return $args['import_id'] === 1
                        && isset($args['products'])
                        && count($args['products']) === 10
                        && is_array($args['products'][0]);
                    }
                )
            );

        $manager = new QueueManager($repository, $xmlService, $batchService);

        $manager->run(1, 0);
    }
}
