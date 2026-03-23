<?php

declare(strict_types=1);

namespace SineFine\PromImport\Infrastructure\Persistence;

use DateTime;
use Exception;
use SineFine\PromImport\Domain\Category\CategoryMapping;
use SineFine\PromImport\Domain\Import\Import;
use SineFine\PromImport\Domain\Import\ImportRepositoryInterface;

class ImportRepository implements ImportRepositoryInterface
{
    private string $table;

    public function __construct()
    {
        global $wpdb;
        $this->table = $wpdb->prefix . 'spss12_import_imports';
    }

	/**
	 * @throws Exception
	 */
	public function findById(int $id): ?Import
    {
        global $wpdb;
	    $row = $wpdb->get_row(
		    $wpdb->prepare("SELECT * FROM %i WHERE id = %d", $this->table, $id),
		    ARRAY_A
	    );

        if (!$row) {
            return null;
        }

        return $this->mapToEntity($row);
    }
	/**
	* @throws Exception @return Import[]*/
    public function findAll(): array
    {
        global $wpdb;
        $rows = $wpdb->get_results(
			$wpdb->prepare( "SELECT * FROM %i ORDER BY created_at DESC", $this->table),
			ARRAY_A
        );

        return array_map([$this, 'mapToEntity'], $rows);
    }

    public function save(Import $import): int
    {
        global $wpdb;

        $data = [
            'name' => $import->getName(),
            'url' => $import->getUrl(),
            'category_mapping' => json_encode($import->getCategoryMapping()?->getMapping()),
            'path' => $import->getPath(),
            'updated_at' => $import->getUpdatedAt()?->format('Y-m-d H:i:s'),
        ];

        if ($import->getId() === null) {
            $wpdb->insert($this->table, $data);
            return $wpdb->insert_id;
        }

        $wpdb->update($this->table, $data, ['id' => $import->getId()]);
        return $import->getId();
    }

    public function delete(int $id): bool
    {
        global $wpdb;
        return (bool) $wpdb->delete($this->table, ['id' => $id]);
    }

	/**
	 * @param array{id: int, name: string, url: string, category_mapping: string, path: ?string, updated_at: ?string, created_at: string} $row
	 * @throws Exception
	 */
	private function mapToEntity(array $row): Import
    {
        $mapping = $row['category_mapping'] ? json_decode($row['category_mapping'], true) : null;
        if (is_array($mapping)) {
            $mapping = CategoryMapping::create($mapping);
        }

        return Import::create(
            (int) $row['id'],
            $row['name'],
            $row['url'],
            $mapping,
            $row['path'],
            $row['updated_at'] ? new DateTime($row['updated_at']) : null,
            new DateTime($row['created_at'])
        );
    }
}
