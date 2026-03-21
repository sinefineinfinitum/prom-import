<?php

declare(strict_types=1);

namespace SineFine\PromImport\Tests;

use PHPUnit\Framework\TestCase;
use SimpleXMLElement;
use SineFine\PromImport\Application\Import\Dto\CategoryDto;
use SineFine\PromImport\Application\Import\XmlParser;
use SineFine\PromImport\Domain\Exception\InvalidXmlException;

class XmlParserTest extends TestCase
{
    public function test_validateFormat_throws_exception_on_empty_content(): void
    {
        $parser = new XmlParser();
        $this->expectException(InvalidXmlException::class);
        $this->expectExceptionMessage('XML content is empty');
        $parser->validateFormat('');
    }

    public function test_validateFormat_throws_exception_on_invalid_xml(): void
    {
        $parser = new XmlParser();
        $this->expectException(InvalidXmlException::class);
        $this->expectExceptionMessage('Failed to load XML content');
        // This should cause an error during read() because it's not well-formed
        $parser->validateFormat('<<<<'); 
    }

    public function test_validateFormat_throws_exception_on_missing_root_element(): void
    {
        $parser = new XmlParser();
        $this->expectException(InvalidXmlException::class);
        $this->expectExceptionMessage('Invalid XML structure: missing root element');
        $parser->validateFormat('<?xml version="1.0" encoding="UTF-8"?><wrong_root></wrong_root>');
    }

	/**
	 * @throws InvalidXmlException
	 */
	public function test_validateFormat_passes_on_valid_root_elements(): void
    {
        $parser = new XmlParser();
        $parser->validateFormat('<?xml version="1.0" encoding="UTF-8"?><yml_catalog></yml_catalog>');
        $parser->validateFormat('<?xml version="1.0" encoding="UTF-8"?><shop></shop>');
        $this->assertTrue(true); // Should not throw exception
    }

    public function test_parseCategories_returns_empty_when_no_categories_section(): void
    {
        $parser = new XmlParser();
        $xml = new SimpleXMLElement('<shop></shop>');
        $this->assertSame([], $parser->parseCategories($xml));
    }

    public function test_parseProducts_returns_empty_when_no_offers_section(): void
    {
        $parser = new XmlParser();
        $xml = new SimpleXMLElement('<shop></shop>');
        $this->assertSame([], $parser->parseProducts($xml));
    }

    public function test_parseProducts_skips_offers_with_invalid_id(): void
    {
        $parser = new XmlParser();
        $xml = new SimpleXMLElement('
            <shop>
                <offers>
                    <offer id="0"><name>Invalid</name></offer>
                    <offer id="-1"><name>Invalid</name></offer>
                    <offer><name>No ID</name></offer>
                </offers>
            </shop>
        ');
        $this->assertSame([], $parser->parseProducts($xml));
    }

    private function sampleXml(): string
    {
        return <<<XML
<yml_catalog>
  <shop>
    <categories>
      <category id="1">Phones</category>
      <category id="2">Accessories</category>
    </categories>
    <offers>
      <offer id="1001">
        <url>https://example.com/p/1001</url>
        <price>1234.56</price>
        <currencyId>USD</currencyId>
        <categoryId>1</categoryId>
        <name>Awesome Phone</name>
        <description>
          <![CDATA[
            Buy at https://spam.example now! <a href="https://bad">link</a>
            Contact: user@example.org
          ]]>
        </description>
        <picture> https://img.example/a.jpg </picture>
        <picture>https://img.example/b.jpg</picture>
        <param name="Tags"> phone, android ,  5g </param>
      </offer>
      <offer id="1002">
        <model>Wired Headset</model>
        <price>0</price>
        <categoryId>2</categoryId>
        <picture>https://img.example/c.jpg</picture>
      </offer>
    </offers>
  </shop>
</yml_catalog>
XML;
    }

    public function test_load_parses_xml(): void
    {
        $parser = new XmlParser();
        $xml = $parser->load($this->sampleXml());
        $this->assertNotFalse($xml);
    }

    public function test_parse_categories_returns_dtos_keyed_by_id(): void
    {
        $parser = new XmlParser();
        $root = $parser->load($this->sampleXml());
        $this->assertNotFalse($root);

        $cats = $parser->parseCategories($root);
        $this->assertCount(2, $cats);
        $this->assertArrayHasKey(1, $cats);
        $this->assertArrayHasKey(2, $cats);
        $this->assertInstanceOf(CategoryDto::class, $cats[1]);
        $this->assertSame('Phones', $cats[1]->name());
    }

    public function test_parse_products_maps_fields_sanitizes_description_and_collects_media_and_tags(): void
    {
        $parser = new XmlParser();
        $root = $parser->load($this->sampleXml());
        $this->assertNotFalse($root);

        $categories = $parser->parseCategories($root);
        $products = $parser->parseProducts($root, $categories);
        $this->assertCount(2, $products);

        $p1 = $products[0];
        $this->assertSame(1001, $p1->sku->value());
        $this->assertSame('Awesome Phone', $p1->title);
        // URLs/emails/anchors removed
        $this->assertStringNotContainsString('http', $p1->description);
        $this->assertStringNotContainsString('@', $p1->description);
        $this->assertStringNotContainsString('<a', $p1->description);
        $this->assertSame('USD', $p1->price->currency());
        $this->assertNotNull($p1->category);
        $this->assertSame(1, $p1->category->id());
        $this->assertSame([
            'https://img.example/a.jpg',
            'https://img.example/b.jpg',
        ], $p1->mediaUrls);

        $p2 = $products[1];
        $this->assertSame(1002, $p2->sku->value());
        $this->assertSame('Wired Headset', $p2->title);
        $this->assertSame(0.0, $p2->price->amount());
        $this->assertSame('UAH', $p2->price->currency()); // default
    }

    public function test_get_total_products(): void
    {
        $parser = new XmlParser();
        $root = $parser->load($this->sampleXml());
        $this->assertNotFalse($root);
        $this->assertSame(2, $parser->getTotalProducts($root));
    }
}
