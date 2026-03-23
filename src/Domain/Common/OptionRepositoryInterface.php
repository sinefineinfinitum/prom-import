<?php

namespace SineFine\PromImport\Domain\Common;

interface OptionRepositoryInterface
{
    public function addOption(string $name, string $value): void;
    public function getOption(string $name, mixed $default = false): mixed;
    public function updateOption(string $name, mixed $value): void;
    public function deleteOption(string $name): void;
}
