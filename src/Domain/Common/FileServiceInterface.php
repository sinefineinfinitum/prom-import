<?php

namespace SineFine\PromImport\Domain\Common;

interface FileServiceInterface
{
    public function mkdir(string $path, int $mode = 0777): void;
    public function rmdir(string $path): void;
    public function unlink(string $path): void;
    public function createFile(string $path, string $content): void;
    public function writeFile(string $path, string $content): void;
    public function isWritable(string $path): bool;
    public function isExist(string $path): bool;
}