<?php

declare(strict_types=1);

namespace SineFine\PromImport\Tests;

use PHPUnit\Framework\TestCase;
use SineFine\PromImport\Infrastructure\Persistence\ImportProgressRepository;
use SineFine\PromImport\Tests\Fake\FakeWpdb;
use stdClass;

class ImportProgressRepositoryTest extends TestCase
{
    public function test_can_be_instantiated(): void
    {
        global $wpdb;

        $originalPrefix = $wpdb->prefix ?? 'wp_';
        $originalWpdb = $GLOBALS['wpdb'] ?? null;

        $wpdb = new stdClass();
        $wpdb->prefix = 'wp_';

        $repo = new ImportProgressRepository();

        $this->assertInstanceOf(ImportProgressRepository::class, $repo);

        if ($originalWpdb !== null) {
            $GLOBALS['wpdb'] = $originalWpdb;
        }
    }

    public function test_markRunning_does_not_overwrite_started_at(): void
    {
        $wpdb = new FakeWpdb();
        $GLOBALS['wpdb'] = $wpdb;

        $repo = new ImportProgressRepository();

        $repo->create(1, 100);
        $this->assertCount(1, $wpdb->inserts);

        $repo->markRunning(1);
        $this->assertNotEmpty($wpdb->updates, 'markRunning must issue an UPDATE');

        $updateData = $wpdb->updates[0][1];
        $this->assertArrayNotHasKey(
            'started_at',
            $updateData,
            'markRunning must preserve the original started_at from create()'
        );
    }
}
