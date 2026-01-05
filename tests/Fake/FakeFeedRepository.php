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

    public function save(FeedDto $feed): void
    {
		$this->latest = new Feed($feed->timestamp, $feed->domain, $feed->content);
        $this->savedFeeds[] = $this->latest;
    }

    public function setLatest(Feed $feed): void
    {
        $this->latest = $feed;
    }
}
