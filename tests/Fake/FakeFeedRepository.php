<?php

namespace SineFine\PromImport\Tests\Fake;

use SineFine\PromImport\Application\Import\Dto\FeedDto;
use SineFine\PromImport\Domain\Feed\Feed;
use SineFine\PromImport\Domain\Feed\FeedRepositoryInterface;

class FakeFeedRepository implements FeedRepositoryInterface
{
    private ?Feed $latest = null;
    public array $savedFeeds = [];

    public function getLatest(): ?Feed
    {
        return $this->latest;
    }

    public function save(FeedDto $feedDto): void
    {
		$this->latest = new Feed($feedDto->timestamp, $feedDto->domain, $feedDto->content);
        $this->savedFeeds[] = $this->latest;
    }

    public function setLatest(Feed $feed): void
    {
        $this->latest = $feed;
    }
}
