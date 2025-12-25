<?php

namespace SineFine\PromImport\Tests\Fake;

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

    public function save(Feed $feed): void
    {
        $this->latest = $feed;
        $this->savedFeeds[] = $feed;
    }

    public function setLatest(Feed $feed): void
    {
        $this->latest = $feed;
    }
}
