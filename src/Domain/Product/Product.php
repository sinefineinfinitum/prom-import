<?php

declare(strict_types=1);

namespace SineFine\PromImport\Domain\Product;

use SineFine\PromImport\Domain\Product\ValueObject\Price;
use SineFine\PromImport\Domain\Product\ValueObject\Sku;

class Product
{
    /**
     * @param string[] $tags
     * @param string[] $mediaUrls
     */
    public function __construct(
        private Sku $sku,
        private string $title,
        private string $description,
        private Price $price,
        private ?string $category = null,
        private array $tags = [],
        private array $mediaUrls = [],
        private string $link = ''
    ) {
        $this->title = trim($this->title);
        $this->category = ($this->category !== null && $this->category !== '') ? $this->category : null;
    }

    public function sku(): Sku { return $this->sku; }
    public function title(): string { return $this->title; }
    public function description(): string { return $this->description; }
    public function price(): Price { return $this->price; }
    public function category(): ?string { return $this->category; }
    /** @return string[] */
    public function tags(): array { return $this->tags; }
    /** @return string[] */
    public function mediaUrls(): array { return $this->mediaUrls; }
    public function link(): string { return $this->link; }
}
