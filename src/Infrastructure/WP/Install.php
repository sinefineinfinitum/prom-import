<?php

namespace SineFine\PromImport\Infrastructure\WP;

use SineFine\PromImport\Domain\Common\FileServiceInterface;
use SineFine\PromImport\Infrastructure\Container\ContainerConfig;
use SineFine\PromImport\Infrastructure\File\FileService;
use UnexpectedValueException;

class Install
{
    public function __construct(
        private ?FileServiceInterface $fileService = null,
    ) {
    }
    public function run(): void
    {
        // Lazy instantiate dependencies to avoid loading DI container on activation
        $this->fileService ??= new FileService();

        $dirs = [
            ContainerConfig::getCommonDir(),
            ContainerConfig::getLogDir(),
            ContainerConfig::getCacheDir(),
            ContainerConfig::getFeedDir(),
        ];
        foreach ($dirs as $dir) {
            if (!is_dir($dir)) {
                $this->fileService->mkdir($dir);
            }
            if (!is_dir($dir)) {
                throw new UnexpectedValueException(esc_html(__('There is no existing directory and cannot create it: ', 'spss12-import-prom-woo' )) . esc_html($dir));
            }
            if (!$this->fileService->isWritable($dir)) {
                throw new UnexpectedValueException(esc_html(__('Directory is not writable: ', 'spss12-import-prom-woo' )) . esc_html($dir));
            }
        }
    }
}
