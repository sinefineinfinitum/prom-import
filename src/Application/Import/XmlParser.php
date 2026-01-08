<?php

declare(strict_types=1);

namespace SineFine\PromImport\Application\Import;

use SineFine\PromImport\Application\Import\Dto\CategoryDto;
use SineFine\PromImport\Application\Import\Dto\ProductDto;
use SineFine\PromImport\Domain\Exception\InvalidXmlException;
use SineFine\PromImport\Domain\Product\ValueObject\Price;
use SineFine\PromImport\Domain\Product\ValueObject\Sku;
use SimpleXMLElement;
use XMLReader;

class XmlParser implements XmlParserInterface
{
    /**
     * @inheritDoc
     */
    public function validateFormat(string $content): void
    {
        if (empty($content)) {
            throw new InvalidXmlException('XML content is empty');
        }

        $reader = new XMLReader();
        libxml_use_internal_errors(true);
        if (!$reader->XML($content)) {
            libxml_clear_errors();
            throw new InvalidXmlException('Failed to load XML content');
        }

        while ($reader->read()) {
            if ($reader->nodeType === XMLReader::ELEMENT) {
                // Check for a basic root element or something familiar
                // For Prom/Rozetka it's usually yml_catalog
                if ($reader->name === 'yml_catalog' || $reader->name === 'shop') {
                    $reader->close();
                    return;
                }
            }
        }

        $reader->close();
        throw new InvalidXmlException('Invalid XML structure: missing root element');
    }

    /**
     * Load XML string into SimpleXMLElement.
     */
    public function load(string $xml): SimpleXMLElement|false
    {
        return simplexml_load_string($xml);
    }

    /**
     * Parse categories from <shop><categories><category>...
     * @return array<int,CategoryDto> keyed by category id
     */
    public function parseCategories(SimpleXMLElement $root): array
    {
        $result = [];
		if (!$root->shop->categories) {
			return $result;
		}
        foreach ($root->shop->categories->category as $cat) {
            $id = isset($cat['id']) ? (int) $cat['id'] : 0;
            $name = trim((string) $cat);
            $result[$id] = new CategoryDto($id, $name);
        }
        return $result;
    }

    /**
     * Parse products from <shop><offers><offer>...
     * @param array<int,CategoryDto> $categories
     * @return ProductDto[]
     */
    public function parseProducts(SimpleXMLElement $root, array $categories = []): array
    {
        $products = [];
	    if (!$root->shop->offers) {
		    return $products;
	    }

        foreach ($root->shop->offers->offer as $offer) {
            $dto = $this->mapOfferToProductDto($offer, $categories);
            if ($dto) {
                $products[] = $dto;
            }
        }
        return $products;
    }

	public function getTotalProducts(SimpleXMLElement $xml): int
	{
		return $xml->shop?->offers?->offer?->count() ? $xml->shop->offers->offer->count() : 0;
	}

    /**
     * Map a single <offer> item to ProductDto
     * @param SimpleXMLElement $offer
     * @param array<int, CategoryDto> $categories
     * @return ProductDto|null
     */
    private function mapOfferToProductDto(SimpleXMLElement $offer, array $categories = []): ?ProductDto
    {
        $id = isset($offer['id']) ? (int) $offer['id'] : 0;
        if ($id <= 0) {
            return null;
        }
        $name = trim((string) ($offer->name ?? $offer->model ?? ''));
        $url = trim((string) ($offer->url ?? ''));
        $descriptionRaw = (string) ($offer->description ?? '');
        $description = $this->sanitizeDescription($descriptionRaw);
        $priceVal = (float) ($offer->price ?? 0);
        $currency = (string) ($offer->currencyId ?? 'UAH');
        $price = Price::create($priceVal, $currency);

        $categoryDto = null;
        $catId = isset($offer->categoryId) ? (int) $offer->categoryId : 0;
        if ($catId && isset($categories[$catId])) {
            $categoryDto = $categories[$catId];
        }

        $media = [];
        if (isset($offer->picture)) {
            foreach ($offer->picture as $pic) {
                $u = esc_url_raw(trim((string) $pic));
                if ($u !== '') {
                    $media[] = $u;
                }
            }
        }

        return  ProductDto::create(
            Sku::create($id),
            $name,
            $description,
            $price,
            $categoryDto,
            $media,
            $url
        );
    }

    private function sanitizeDescription(string $html): string
    {
        if ($html === '') {
            return '';
        }
        //remove anchors, emails, and auto-linked URLs
        return preg_replace([
            '/<\/?a( [^>]*)?>/i',
            '/[^@\s]*@[^@\s]*\.[^@\s]*/',
            '/(?<!src=")(?:(https?)+[:\/]+([^\s<]+)|(www\.[^\s<]+?\.[^\s<]+))(?<![.,:])/i',
        ], ['', '', ''], $html) ?? '';
    }
}
