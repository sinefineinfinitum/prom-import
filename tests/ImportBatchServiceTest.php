<?php

declare(strict_types=1);

namespace SineFine\PromImport\Tests;

use PHPUnit\Framework\TestCase;
use Psr\Log\LoggerInterface;
use SineFine\PromImport\Application\Import\ImportBatchService;
use SineFine\PromImport\Domain\Product\ProductManagerInterface;
use SineFine\PromImport\Domain\Queue\TaskQueueInterface;
use SineFine\PromImport\Infrastructure\Persistence\ImportProgressRepository;

class ImportBatchServiceTest extends TestCase
{
    private $taskQueue;
    private $progressRepository;
    private $productManager;
    private $logger;

    protected function setUp(): void
    {
        $this->taskQueue = $this->createMock(TaskQueueInterface::class);
        $this->progressRepository = $this->createMock(ImportProgressRepository::class);
        $this->productManager = $this->createMock(ProductManagerInterface::class);
        $this->logger = $this->createMock(LoggerInterface::class);
    }

    private function createService(?int $batchSize = null, ?int $delay = null): ImportBatchService
    {
        return new ImportBatchService(
            $this->taskQueue,
            $this->progressRepository,
            $this->productManager,
            $this->logger,
            $batchSize,
            $delay,
        );
    }

    public function test_default_batch_size_is_20(): void
    {
        $service = $this->createService();
        $this->assertSame(20, $service->getBatchSize());
    }

    public function test_custom_batch_size(): void
    {
        $service = $this->createService(50);
        $this->assertSame(50, $service->getBatchSize());
    }

    public function test_default_delay_is_0(): void
    {
        $service = $this->createService();
        $this->assertSame(0, $service->getDelayBetweenBatches());
    }

    public function test_custom_delay(): void
    {
        $service = $this->createService(20, 5);
        $this->assertSame(5, $service->getDelayBetweenBatches());
    }

    public function test_setBatchSize_updates_value(): void
    {
        $service = $this->createService();
        $service->setBatchSize(100);
        $this->assertSame(100, $service->getBatchSize());
    }

    public function test_import_enqueues_correct_number_of_batches(): void
    {
        $service = $this->createService(10);

        $products = [];
        for ($i = 0; $i < 25; $i++) {
            $products[] = ['sku' => "SKU$i", 'title' => "Product $i", 'description' => '', 'price' => 10.0];
        }

        $this->taskQueue->expects($this->exactly(3))
            ->method('enqueue')
            ->willReturnCallback(
                function (string $hook, array $args, int $delay, string $group) {
                    $this->assertSame('spss12-import-prom-woo_queue_run_batch', $hook);
                    $this->assertArrayHasKey('products', $args);
                    $this->assertLessThanOrEqual(10, count($args['products']));
                    $this->assertGreaterThan(0, count($args['products']));
                    return 1;
                }
            );

        $service->import(1, $products);
    }

    public function test_import_with_less_than_batch_size_enqueues_one(): void
    {
        $service = $this->createService(20);

        $products = [['sku' => 'SKU1', 'title' => 'Product 1', 'description' => '', 'price' => 10.0]];

        $this->taskQueue->expects($this->once())
            ->method('enqueue')
            ->with(
                'spss12-import-prom-woo_queue_run_batch',
                $this->callback(
                    function ($args) {
                        return isset($args['products']) && count($args['products']) === 1;
                    }
                ),
                0,
                $this->stringContains('import_1_batch_')
            );

        $service->import(1, $products);
    }

    public function test_import_applies_delay_between_batches(): void
    {
        $service = $this->createService(10, 5);

        $products = [];
        for ($i = 0; $i < 25; $i++) {
            $products[] = ['sku' => "SKU$i", 'title' => "Product $i", 'description' => '', 'price' => 10.0];
        }

        $expectedDelays = [0, 5, 10];
        $callIndex = 0;

        $this->taskQueue->expects($this->exactly(3))
            ->method('enqueue')
            ->willReturnCallback(
                function ($hook, $args, $delay, $group) use ($expectedDelays, &$callIndex) {
                    $this->assertSame($expectedDelays[$callIndex], $delay);
                    $callIndex++;
                    return 1;
                }
            );

        $service->import(1, $products);
    }

    public function test_process_does_nothing_with_empty_products(): void
    {
        $service = $this->createService();

        $this->progressRepository->expects($this->never())
            ->method('markRunning');
        $this->progressRepository->expects($this->never())
            ->method('atomicAdvance');
        $this->progressRepository->expects($this->never())
            ->method('markCompleted');

        $service->process(['import_id' => 1, 'products' => []]);
    }

    public function test_process_does_nothing_with_zero_import_id(): void
    {
        $service = $this->createService();

        $this->progressRepository->expects($this->never())
            ->method('markRunning');

        $service->process(['import_id' => 0, 'products' => [['sku' => 'SKU1', 'title' => 'Test', 'description' => '', 'price' => 10.0]]]);
    }

    public function test_process_applies_category_mapping(): void
    {
        $service = $this->createService();

        $productData = [
            'sku' => 'SKU1',
            'title' => 'Product 1',
            'description' => 'desc',
            'price' => 10.0,
            'category' => ['id' => 123, 'name' => 'External Cat'],
            'mediaUrls' => [],
            'link' => 'http://example.com',
        ];

        $this->productManager->expects($this->once())
            ->method('createProductFromDto')
            ->willReturn(42);

        $this->productManager->expects($this->once())
            ->method('addCategoryToProduct')
            ->with(42, 7);

        $this->productManager->method('addImagesToProductGallery');

        $service->process(
            [
            'import_id' => 1,
            'products' => [$productData],
            'categoryMapping' => [123 => 7],
            ]
        );
    }

    public function test_process_skips_invalid_product_and_continues(): void
    {
        $service = $this->createService();

        $invalid = ['title' => 'Missing sku', 'description' => '', 'price' => 10.0];
        $valid = ['sku' => 'SKU1', 'title' => 'Valid', 'description' => '', 'price' => 10.0];

        $this->productManager->expects($this->once())
            ->method('createProductFromDto')
            ->willReturn(1);

        $this->productManager->method('addImagesToProductGallery');

        $this->progressRepository->expects($this->once())
            ->method('atomicAdvance')
            ->with(1, 1, 2);

        $service->process(['import_id' => 1, 'products' => [$invalid, $valid]]);
    }
}
