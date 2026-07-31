<?php

declare(strict_types=1);

namespace SineFine\PromImport\Infrastructure\Persistence;

class ImportProgressRepository
{
    private string $table;

    public function __construct()
    {
        global $wpdb;
        $this->table = $wpdb->prefix . 'spss12_import_progress';
    }

    public function create(int $importId, int $total): int
    {
        global $wpdb;

        $wpdb->insert(
            $this->table,
            [
                'import_id' => $importId,
                'status' => 'pending',
                'total' => $total,
                'imported' => 0,
                'offset' => 0,
                'started_at' => current_time('mysql'),
                'updated_at' => current_time('mysql'),
            ]
        );

        return $wpdb->insert_id;
    }

    /**
     * @return array<string, mixed>|null
     */
    public function findById(int $id): ?array
    {
        global $wpdb;

        $row = $wpdb->get_row(
            $wpdb->prepare("SELECT * FROM %i WHERE id = %d", $this->table, $id),
            ARRAY_A
        );

        return $row ?: null;
    }

    /**
     * @return array<string, mixed>|null
     */
    public function findByImportId(int $importId): ?array
    {
        global $wpdb;

        $row = $wpdb->get_row(
            $wpdb->prepare("SELECT * FROM %i WHERE import_id = %d ORDER BY id DESC", $this->table, $importId),
            ARRAY_A
        );

        return $row ?: null;
    }

    public function atomicAdvance(int $importId, int $batchCount, int $batchSize): void
    {
        global $wpdb;

        $wpdb->query(
            $wpdb->prepare(
                "UPDATE {$this->table}
                 SET imported = imported + %d,
                     offset = offset + %d,
                     updated_at = %s
                 WHERE import_id = %d",
                $batchCount,
                $batchSize,
                current_time('mysql'),
                $importId
            )
        );
    }

    public function markRunning(int $importId): void
    {
        global $wpdb;

        $wpdb->update(
            $this->table,
            [
                'status' => 'running',
                'updated_at' => current_time('mysql'),
            ],
            ['import_id' => $importId]
        );
    }

    public function markCompleted(int $importId): void
    {
        global $wpdb;

        $wpdb->query(
            $wpdb->prepare(
                "UPDATE {$this->table}
                 SET status = 'completed',
                     updated_at = %s
                 WHERE import_id = %d AND offset >= total",
                current_time('mysql'),
                $importId
            )
        );
    }

    public function markFailed(int $importId): void
    {
        global $wpdb;

        $wpdb->update(
            $this->table,
            [
                'status' => 'failed',
                'updated_at' => current_time('mysql'),
            ],
            ['import_id' => $importId]
        );
    }

    /**
     * @return array<int, array<string, mixed>>
     */
    public function findStuck(): array
    {
        global $wpdb;

        $rows = $wpdb->get_results(
            $wpdb->prepare(
                "SELECT * FROM %i WHERE status = 'running' AND offset < total",
                $this->table
            ),
            ARRAY_A
        );

        return $rows ?: [];
    }
}
