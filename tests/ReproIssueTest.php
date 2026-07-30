<?php

declare(strict_types=1);

namespace SineFine\PromImport\Tests;

use PHPUnit\Framework\TestCase;
use SineFine\PromImport\Application\Import\XmlParser;

class ReproIssueTest extends TestCase
{
    public function test_parseProducts_with_string_ids(): void
    {
        $parser = new XmlParser();
        $xmlContent = <<<XML
<?xml version="1.0" encoding="UTF-8"?>
<yml_catalog date="2026-07-27 20:40">
    <shop>
        <offers>
            <offer id="gen_1001" available="true">
                <name>Test Product</name>
                <price>100</price>
                <currencyId>UAH</currencyId>
                <categoryId>1</categoryId>
            </offer>
        </offers>
    </shop>
</yml_catalog>
XML;
        $root = $parser->load($xmlContent);
        $this->assertNotFalse($root);
        
        $products = $parser->parseProducts($root);
        
        // В текущей реализации это упадет или вернет пустой массив, 
        // так как "gen_1001" превратится в 0.
        $this->assertCount(1, $products, 'Should parse one product even with string ID');
        $this->assertSame('gen_1001', (string)$products[0]->sku->value());
    }
}
