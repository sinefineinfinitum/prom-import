<?php

namespace SineFine\PromImport\Infrastructure\Persistence;

use SineFine\PromImport\Domain\Common\OptionRepositoryInterface;

class OptionRepository implements OptionRepositoryInterface
{
    public function addOption(string $name, string $value): void
    {
        add_option($name, $value);
    }
    public function getOption(string $name , mixed $default = false): mixed
    {
        return get_option($name, $default);
    }

    public function updateOption(string $name, mixed $value): void
    {
        update_option($name, $value);
    }

    public function deleteOption(string $name): void
    {
        delete_option($name);
    }
}
