<?php

namespace SineFine\PromImport\Domain\Feed;

interface FeedRepositoryInterface {
	public function getLatest(): Feed|null;
	public function save(Feed $feed): void;
}
