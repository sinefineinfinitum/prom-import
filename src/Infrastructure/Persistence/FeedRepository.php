<?php

declare(strict_types=1);

namespace SineFine\PromImport\Infrastructure\Persistence;

use SineFine\PromImport\Application\Import\Dto\FeedDto;
use SineFine\PromImport\Domain\Common\FileServiceInterface;
use SineFine\PromImport\Domain\Feed\Feed;
use SineFine\PromImport\Domain\Feed\FeedRepositoryInterface;
use SineFine\PromImport\Infrastructure\Container\ContainerConfig;

class FeedRepository implements FeedRepositoryInterface
{
    private string $dir;

    public function __construct(
        private FileServiceInterface $fileService,
    ) {
        $this->dir = ContainerConfig::getFeedDir();
    }

	public function getLatest(): ?Feed
	{
		
		$files = glob($this->dir . DIRECTORY_SEPARATOR . '*.xml');
		if (!$files) return null;

		usort($files, fn($a, $b) => filemtime($b) - filemtime($a));
		$filePath = $files[0];
		if (!file_exists($filePath)) {
			return null;
		}

		return new Feed(
			(int)explode('_', pathinfo($filePath, PATHINFO_FILENAME))[1],
			explode('_', pathinfo($filePath, PATHINFO_FILENAME))[0],
			file_get_contents($filePath) ?: ''
		);
	}

	public function save(FeedDto $feedDto): void
	{
		$feed = Feed::fromDto($feedDto);
        $latestFeed = $this->getLatest();
        if (
            empty($feed->content())
            || (!empty($latestFeed) && !$this->isNewFeed($feed, $latestFeed))
        ) {
			return;
		}

		$filePath = $this->dir . DIRECTORY_SEPARATOR . $feed->filename();
        $this->fileService->writeFile($filePath, $feed->content());
        $this->clearOldFeeds();
	}

	public function clearOldFeeds(int $keepCount = 5): void
	{
		$files = glob($this->dir . DIRECTORY_SEPARATOR . '*.xml');
		if (!$files) return;

		usort($files, fn($a, $b) => filemtime($b) - filemtime($a));

		$toDelete = array_slice($files, $keepCount);
		foreach ($toDelete as $file) {
			$this->fileService->unlink($file);
		}
	}

    private function isNewFeed(Feed $feed, Feed $lastFeed): bool
    {
        return md5($feed->content()) !== md5($lastFeed->content());
    }
}
