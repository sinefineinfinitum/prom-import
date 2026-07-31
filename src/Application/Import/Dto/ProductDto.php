<?php

declare(strict_types=1);

namespace SineFine\PromImport\Application\Import\Dto;

use SineFine\PromImport\Domain\Product\ValueObject\Price;
use SineFine\PromImport\Domain\Product\ValueObject\Sku;

class ProductDto
{
    public ?int $existedId = null;
    public ?string $categoryName = null;

    /**
     * @param Sku                $sku
     * @param string             $title
     * @param string             $description
     * @param Price              $price
     * @param ?CategoryDto       $category
     * @param array<int, string> $mediaUrls
     * @param string             $link
     *  */
    public function __construct(
        public Sku $sku,
        public string $title,
        public string $description,
        public Price $price,
        public ?CategoryDto $category = null,
        public array $mediaUrls = [],
        public string $link = ''
    ) {
    }

    /**
     * @param Sku                $sku
     * @param string             $title
     * @param string             $description
     * @param Price              $price
     * @param ?CategoryDto       $category
     * @param array<int, string> $mediaUrls
     * @param string             $link
     *  */
    public static function create(Sku $sku, string $title, string $description, Price $price, ?CategoryDto $category = null, array $mediaUrls = [], string $link = ''): self
    {
        return new self($sku, $title, $description, $price, $category, $mediaUrls, $link);
    }

    /**
     * @return array<string, mixed>
     */
    public function toArray(): array
    {
        return [
            'sku' => $this->sku->value(),
            'title' => $this->title,
            'description' => $this->description,
            'price' => $this->price->amount(),
            'currency' => $this->price->currency(),
            'category' => $this->category !== null
                ? ['id' => $this->category->id(), 'name' => $this->category->name()]
                : null,
            'mediaUrls' => $this->mediaUrls,
            'link' => $this->link,
            'existedId' => $this->existedId,
            'categoryName' => $this->categoryName,
        ];
    }

    /**
     * @param array<string, mixed> $data
     */
    public static function fromArray(array $data): self
    {
        $dto = new self(
            new Sku($data['sku'] ?? ''),
            (string) ($data['title'] ?? ''),
            (string) ($data['description'] ?? ''),
            new Price((float) ($data['price'] ?? 0), (string) ($data['currency'] ?? '')),
            isset($data['category']) && is_array($data['category'])
                ? CategoryDto::create((int) $data['category']['id'], (string) ($data['category']['name'] ?? ''))
                : null,
            is_array($data['mediaUrls'] ?? null) ? $data['mediaUrls'] : [],
            (string) ($data['link'] ?? '')
        );

        if (array_key_exists('existedId', $data)) {
            $dto->existedId = $data['existedId'] !== null ? (int) $data['existedId'] : null;
        }

        if (array_key_exists('categoryName', $data)) {
            $dto->categoryName = $data['categoryName'] !== null ? (string) $data['categoryName'] : null;
        }

        return $dto;
    }
}
