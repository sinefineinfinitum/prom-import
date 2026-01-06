<?php

namespace SineFine\PromImport\Application\Import\Dto;

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
			(string) parse_url($url, PHP_URL_HOST),
			$content
		);
	}
}
