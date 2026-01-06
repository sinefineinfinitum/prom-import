<?php

declare(strict_types=1);

namespace SineFine\PromImport\Application\Import;

use SimpleXMLElement;
use SineFine\PromImport\Application\Import\Dto\CategoryDto;
use SineFine\PromImport\Application\Import\Dto\ProductDto;

interface XmlParserInterface
{
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
