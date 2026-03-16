<?php

namespace SineFine\PromImport\Tests\Fake;

use SineFine\PromImport\Domain\Product\ImageAttachable;

class FakeImageService implements ImageAttachable
{
	public array $galleryImages = [];

	public function assignFeatureImageToProduct(string $url, int $postId, string $title = ''): void
	{
		// not needed yet
	}

	public function addImageToProductGallery(string $url, int $postId, string $title = ''): void
	{
		$this->galleryImages[] = [$url, $postId, $title];
	}
}