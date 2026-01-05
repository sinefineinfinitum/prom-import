<?php

namespace SineFine\PromImport\Application\Import\Dto;

class FeedDto
{
	public int $timestamp;
	public string $domain;
	public string $content;

	public function __construct(
		int $timestamp,
		string $domain,
		string $content = '',
	) {
		$this->timestamp = $timestamp;
		$this->domain = $domain;
		$this->content = $content;
	}
}
