<?php

declare(strict_types=1);

namespace SineFine\PromImport\Infrastructure\Persistence;

use SineFine\PromImport\Application\Import\Dto\FeedDto;
use SineFine\PromImport\Domain\Feed\Feed;
use SineFine\PromImport\Domain\Feed\FeedRepositoryInterface;

class FeedRepository implements FeedRepositoryInterface
{
	private const UPLOADS_DIR = 'spss12';

	public function getLatest(): ?Feed
	{
		$dir = $this->getUploadsDir();
		$files = glob($dir . DIRECTORY_SEPARATOR . '*.xml');
		if (!$files) return null;

		usort($files, function($a, $b) {
			return filemtime($b) - filemtime($a);
		});
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

	public function save(FeedDto $feed): void
	{
		if (empty($feed->content)) {
			return;
		}

		$currentMd5 = md5($feed->content);
		$lastMd5    = $this->getLatest() && $this->getLatest()->content()
			? md5($this->getLatest()->content())
			: '';

		if ($currentMd5 === $lastMd5) {
			return;
		}

		$dir      = $this->getUploadsDir();
		$filePath = $dir . DIRECTORY_SEPARATOR . $feed->domain . '_' . $feed->timestamp . '.xml';

		if (file_put_contents($filePath, $feed->content) !== false) {
			$this->clearOldFeeds();
		}
	}

	public function clearOldFeeds(int $keepCount = 5): void
	{
		$dir = $this->getUploadsDir();
		$files = glob($dir . DIRECTORY_SEPARATOR . '*.xml');
		if (!$files) return;

		usort($files, function($a, $b) {
			return filemtime($b) - filemtime($a);
		});

		$toDelete = array_slice($files, $keepCount);
		foreach ($toDelete as $file) {
			@unlink($file);
		}
	}

	private function getUploadsDir(): string
	{
		$baseUploadsDir = wp_upload_dir();
		$dir            = $baseUploadsDir['basedir'] . DIRECTORY_SEPARATOR . self::UPLOADS_DIR;
		if (!is_dir($dir)) {
			wp_mkdir_p($dir);
		}
		return $dir;
	}
}
