<?php

declare(strict_types=1);

namespace SineFine\PromImport\Application\Import;

use SimpleXMLElement;
use SineFine\PromImport\Application\Import\Dto\CategoryDto;
use SineFine\PromImport\Application\Import\Dto\ProductDto;
use SineFine\PromImport\Domain\Exception\InvalidXmlException;

interface XmlParserInterface
{
    /**
     * @param string $content
     * @return void
     * @throws InvalidXmlException
     */
    public function validateFormat(string $content): void;

    /**
     * @param SimpleXMLElement $root
     * @return array<int, CategoryDto>
     */
    public function parseCategories(SimpleXMLElement $root): array;

    /**
     * @param SimpleXMLElement $root
     * @param array<int, CategoryDto> $categories
     * @return ProductDto[]
     */
    public function parseProducts(SimpleXMLElement $root, array $categories = []): array;

    /**
     * @param SimpleXMLElement $xml
     * @return int
     */
    public function getTotalProducts(SimpleXMLElement $xml): int;
}
