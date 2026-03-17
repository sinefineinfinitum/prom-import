<?php

declare(strict_types=1);

namespace SineFine\PromImport\Domain\Import;

interface ImportRepositoryInterface
{
    public function findById(int $id): ?Import;
    /**
     * @return Import[]
     */
    public function findAll(): array;
    public function save(Import $import): int;
    public function delete(int $id): bool;
}
