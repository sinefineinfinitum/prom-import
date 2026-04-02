<?php

declare(strict_types=1);

namespace SineFine\PromImport\Tests;

use PHPUnit\Framework\TestCase;
use SineFine\PromImport\Presentation\Rest\ImportRestV2Controller;
use SineFine\PromImport\Application\Import\ImportService;
use SineFine\PromImport\Application\Sync\SyncPriceService;
use Psr\Log\LoggerInterface;
use WP_REST_Request;
use WP_REST_Response;
use WP_Error;
use Exception;

class ImportRestV2ControllerTest extends TestCase
{
    private $importService;
    private $syncPriceService;
    private $logger;
    private ImportRestV2Controller $controller;

    protected function setUp(): void
    {
        $this->importService = $this->createMock(ImportService::class);
        $this->syncPriceService = $this->createMock(SyncPriceService::class);
        $this->logger = $this->createMock(LoggerInterface::class);
        
        $this->controller = new ImportRestV2Controller(
            $this->importService,
            $this->syncPriceService,
            $this->logger
        );
    }

    public function test_sync_prices_success(): void
    {
        $importId = 123;
        $request = new WP_REST_Request('POST', "/imports/$importId/sync-prices");
        $request->set_param('id', $importId);

        $expectedResult = [
            'success' => true,
            'updated' => 10,
            'total' => 10,
            'errors' => 0
        ];

        $this->syncPriceService->expects($this->once())
            ->method('syncPrices')
            ->with($importId)
            ->willReturn($expectedResult);

        $response = $this->controller->sync_prices($request);

        $this->assertInstanceOf(WP_REST_Response::class, $response);
        $this->assertSame(200, $response->get_status());
        $this->assertSame($expectedResult, $response->get_data());
    }

    public function test_sync_prices_error_handling(): void
    {
        $importId = 123;
        $request = new WP_REST_Request('POST', "/imports/$importId/sync-prices");
        $request->set_param('id', $importId);

        $this->syncPriceService->expects($this->once())
            ->method('syncPrices')
            ->with($importId)
            ->willThrowException(new Exception('Some error'));

        $response = $this->controller->sync_prices($request);

        $this->assertInstanceOf(WP_Error::class, $response);
        $this->assertSame('import_error', $response->get_error_code());
        $this->assertSame('Some error', $response->get_error_message());
    }
}
