<?php

namespace SineFine\PromImport\Application\Import\Dto;

use SineFine\PromImport\Infrastructure\WP\Functions;

class FeedDto
{
	public function __construct(
		public int $timestamp,
		public string $domain,
		public string $content = '',
	) {}

	public static function create(string $url, string $content): self
	{
		return new self(
			time(),
			(string) Functions::parseUrl($url, PHP_URL_HOST),
			$content
		);
	}
}
