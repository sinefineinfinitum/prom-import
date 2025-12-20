<?php

declare(strict_types=1);

namespace SineFine\PromImport\Application\Import\Dto;

use SineFine\PromImport\Domain\Product\ValueObject\Price;
use SineFine\PromImport\Domain\Product\ValueObject\Sku;

class ProductDto
{
    /**
     * @param string[] $tags
     * @param string[] $mediaUrls
     */
    public Sku $sku;
    public ?int $existedId = null;
    public string $title;
    public string $description;
    public Price $price;
    public ?CategoryDto $category;
    public ?string $categoryName = null;
    /** @var string[] */
    public array $tags;
    /** @var string[] */
    public array $mediaUrls;
    public string $link;

    /**
     * @param string[] $tags
     * @param string[] $mediaUrls
     */
    public function __construct(
        Sku $sku,
        string $title,
        string $description,
        Price $price,
        ?CategoryDto $category = null,
        array $tags = [],
        array $mediaUrls = [],
        string $link = ''
    ) {
        $this->sku   = $sku;
        $this->title = $title;
        $this->description = $description;
        $this->price = $price;
        $this->category = $category;
        $this->tags = $tags;
        $this->mediaUrls = $mediaUrls;
        $this->link = $link;
    }
}
