<?php

declare(strict_types=1);

namespace SineFine\PromImport\Infrastructure\Persistence;

use DateTime;
use SineFine\PromImport\Domain\Import\Import;
use SineFine\PromImport\Domain\Import\ImportRepositoryInterface;
use SineFine\PromImport\Infrastructure\DB\Migrator;

class ImportRepository implements ImportRepositoryInterface
{
    private string $table;

    public function __construct()
    {
        global $wpdb;
        $this->table = $wpdb->prefix . Migrator::PLUGIN_DB_PREFIX . 'imports';
    }

    public function findById(int $id): ?Import
    {
        global $wpdb;
        $row = $wpdb->get_row($wpdb->prepare("SELECT * FROM {$this->table} WHERE id = %d", $id));

        if (!$row) {
            return null;
        }

        return $this->mapToEntity($row);
    }

    public function findAll(): array
    {
        global $wpdb;
        $rows = $wpdb->get_results("SELECT * FROM {$this->table} ORDER BY created_at DESC");

        return array_map([$this, 'mapToEntity'], $rows);
    }

    public function save(Import $import): int
    {
        global $wpdb;

        $data = [
            'name' => $import->getName(),
            'url' => $import->getUrl(),
            'category_mapping' => $import->getCategoryMapping() ? json_encode($import->getCategoryMapping()) : null,
            'path' => $import->getPath(),
            'updated_at' => $import->getUpdatedAt() ? $import->getUpdatedAt()->format('Y-m-d H:i:s') : null,
        ];

        if ($import->getId() === null) {
            $wpdb->insert($this->table, $data);
            return (int) $wpdb->insert_id;
        }

        $wpdb->update($this->table, $data, ['id' => $import->getId()]);
        return $import->getId();
    }

    public function delete(int $id): bool
    {
        global $wpdb;
        return (bool) $wpdb->delete($this->table, ['id' => $id]);
    }

    private function mapToEntity(object $row): Import
    {
        return new Import(
            (int) $row->id,
            $row->name,
            $row->url,
            $row->category_mapping ? json_decode($row->category_mapping, true) : null,
            $row->path,
            $row->updated_at ? new DateTime($row->updated_at) : null,
            new DateTime($row->created_at)
        );
    }
}
