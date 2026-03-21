<?php

declare(strict_types=1);

namespace SineFine\PromImport\Infrastructure\WP;

use SineFine\PromImport\Domain\Common\FileServiceInterface;
use SineFine\PromImport\Infrastructure\Container\ContainerConfig;
use SineFine\PromImport\Infrastructure\File\FileService;

class Uninstall
{
    public function __construct(
        private ?FileServiceInterface $fileService = null,
    ) {
    }
	public function run(): void
    {
        // Lazy instantiate to avoid loading DI container on uninstall
        $this->fileService = $this->fileService ?? new FileService();

        $dir = ContainerConfig::getCommonDir();
        if ($this->fileService->isExist($dir)) {
            $this->fileService->rmdir($dir);
        }
    }
}
