<?php

namespace SineFine\PromImport\Domain\Feed;

use SineFine\PromImport\Application\Import\Dto\FeedDto;

interface FeedRepositoryInterface {
	public function getLatest(): Feed|null;
	public function save(FeedDto $feed): void;
}
