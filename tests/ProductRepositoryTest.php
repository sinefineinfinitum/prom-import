<?php

declare(strict_types=1);

namespace SineFine\PromImport\Tests;

use PHPUnit\Framework\TestCase;
use SineFine\PromImport\Domain\Product\ImageAttachable;
use SineFine\PromImport\Domain\Product\ValueObject\Sku;
use SineFine\PromImport\Infrastructure\Persistence\ProductRepository;

class ProductRepositoryTest extends TestCase
{
    private function createRepo(): ProductRepository
    {
        return new ProductRepository($this->createMock(ImageAttachable::class));
    }

    public function test_findIdBySkuId_returns_id_when_matching_numeric_sku(): void
    {
        $GLOBALS['_test_wp_query_result'] = [123];
        $repo = $this->createRepo();
        $result = $repo->findIdBySkuId('123');
        $this->assertSame(123, $result);
    }

    public function test_findIdBySkuId_returns_id_when_matching_string_sku(): void
    {
        $GLOBALS['_test_wp_query_result'] = [456];
        $repo = $this->createRepo();
        $result = $repo->findIdBySkuId('gen_100');
        $this->assertSame(456, $result);
    }

    public function test_findIdBySkuId_returns_false_when_no_match(): void
    {
        $GLOBALS['_test_wp_query_result'] = [];
        $repo = $this->createRepo();
        $result = $repo->findIdBySkuId('nonexistent');
        $this->assertFalse($result);
    }

    public function test_findIdBySkuId_always_uses_char_type(): void
    {
        $GLOBALS['_test_wp_query_result'] = [];
        $repo = $this->createRepo();
        $repo->findIdBySkuId('123');
        $this->assertSame('CHAR', $GLOBALS['_test_wp_query_args']['meta_query'][0]['type']);
    }

    public function test_findIdBySku_returns_id_via_sku_value_object(): void
    {
        $GLOBALS['_test_wp_query_result'] = [789];
        $repo = $this->createRepo();
        $result = $repo->findIdBySku(new Sku('sku_001'));
        $this->assertSame(789, $result);
    }
}
