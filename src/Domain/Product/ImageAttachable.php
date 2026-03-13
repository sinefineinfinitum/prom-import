<?php

namespace SineFine\PromImport\Domain\Product;

interface ImageAttachable
{
	public function assignFeatureImageToProduct(string $url, int $postId, string $title = ''): void;

	public function addImageToProductGallery(string $url, int $postId, string $title = ''): void;
}