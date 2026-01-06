<?php

declare(strict_types=1);

namespace SineFine\PromImport\Application\Import;

use SineFine\PromImport\Application\Import\Dto\CategoryDto;
use SineFine\PromImport\Application\Import\Dto\ProductDto;
use SineFine\PromImport\Domain\Product\ValueObject\Price;
use SineFine\PromImport\Domain\Product\ValueObject\Sku;
use SimpleXMLElement;

class XmlParser implements XmlParserInterface
{
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
        $price = new Price($priceVal, $currency);

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

        $tags = [];
        if (isset($offer->param)) {
            foreach ($offer->param as $param) {
                $paramName = (string) $param['name'];
                if (mb_strtolower($paramName) === 'tags') {
                    $tags = array_filter(array_map('trim', explode(',', (string) $param)));
                }
            }
        }

        return new ProductDto(
            new Sku($id),
            $name,
            $description,
            $price,
            $categoryDto,
            $tags,
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
