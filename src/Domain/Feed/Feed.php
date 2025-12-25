<?php

declare(strict_types=1);

namespace SineFine\PromImport\Domain\Feed;

class Feed {
	public function __construct(
		private int $timestamp,
		private string $domain,
		private string $content = '',
	) {
	}
	public function timestamp(): int { return $this->timestamp; }
	public function domain(): string { return $this->domain; }
	public function filename(): string { return $this->domain . "_" . $this->timestamp . ".xml"; }
	public function content(): string { return $this->content; }
}
