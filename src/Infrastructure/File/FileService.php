<?php

namespace SineFine\PromImport\Infrastructure\File;

use RuntimeException;
use SineFine\PromImport\Domain\Common\FileServiceInterface;
use WP_Filesystem_Base;

class FileService implements FileServiceInterface
{
    private WP_Filesystem_Base $fs;

    public function __construct()
    {
        if (!function_exists('WP_Filesystem')) {
            require_once ABSPATH . 'wp-admin/includes/file.php';
        }

        WP_Filesystem();

        global $wp_filesystem;

        if (!$wp_filesystem) {
            throw new RuntimeException('WP_Filesystem is not initialized');
        }

        $this->fs = $wp_filesystem;
    }

    public function mkdir(string $path, int $mode = 0777): void
    {
        if (!$this->fs->is_dir($path)) {
            $this->fs->mkdir($path, $mode);
        }
    }

    public function rmdir(string $path): void
    {
        if ($this->fs->is_dir($path)) {
            $this->fs->delete($path, true);
        }
    }

    public function unlink(string $path): void
    {
        if ($this->fs->exists($path)) {
            $this->fs->delete($path);
        }

    }

    public function createFile(string $path, string $content): void
    {
        if (!$this->fs->exists($path)) {
            $this->fs->put_contents($path, $content, FS_CHMOD_FILE);
        }

    }

    public function writeFile(string $path, string $content): void
    {
        $this->fs->put_contents($path, $content, FS_CHMOD_FILE);
    }

    public function isWritable(string $path): bool
    {
        return $this->fs->exists($path) && $this->fs->is_writable($path);
    }
    public function isExist(string $path): bool
    {
        return $this->fs->exists($path);
    }
}
