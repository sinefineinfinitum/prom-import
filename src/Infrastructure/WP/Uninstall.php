<?php

declare(strict_types=1);

namespace SineFine\PromImport\Infrastructure\WP;

use SineFine\PromImport\Application\Import\XmlService;
use SineFine\PromImport\Domain\Category\Category;

use SineFine\PromImport\Domain\Common\FileServiceInterface;
use SineFine\PromImport\Domain\Common\OptionRepositoryInterface;
use SineFine\PromImport\Infrastructure\Container\ContainerConfig;
use SineFine\PromImport\Infrastructure\File\FileService;
use SineFine\PromImport\Infrastructure\Persistence\OptionRepository;

class Uninstall
{
    public function __construct(
        private ?FileServiceInterface $fileService = null,
        private ?OptionRepositoryInterface $optionRepository = null,
    ){
    }
	public function run(): void
    {
        // Lazy instantiate to avoid loading DI container on uninstall
        $this->fileService = $this->fileService ?? new FileService();
        $this->optionRepository = $this->optionRepository ?? new OptionRepository();

        $this->optionRepository->deleteOption(XmlService::URL_SETTING_OPTION);
        $this->optionRepository->deleteOption(Category::CATEGORY_MAPPING_OPTION);

        $dir = ContainerConfig::getCommonDir();
        if ($this->fileService->isExist($dir)) {
            $this->fileService->rmdir($dir);
        }
    }
}
