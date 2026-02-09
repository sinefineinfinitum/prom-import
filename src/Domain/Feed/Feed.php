<?php

declare(strict_types=1);

namespace SineFine\PromImport\Domain\Feed;

use SineFine\PromImport\Application\Import\Dto\FeedDto;

class Feed {

    public const XML_FEEDS_DIRECTORY = 'feeds';
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
    public static function fromDto(FeedDto $feedDto): self
    {
        return new self(
            $feedDto->timestamp,
            $feedDto->domain,
            $feedDto->content
        );
    }
}
