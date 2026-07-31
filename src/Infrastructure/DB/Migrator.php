<?php

namespace SineFine\PromImport\Infrastructure\DB;


use SineFine\PromImport\Infrastructure\Persistence\OptionRepository;

class Migrator
{
    public const PLUGIN_DB_PREFIX = "spss12_import_";
    private const OPTION_KEY = 'spss12_import_db_schema_version';
    private const SCHEMA_VERSION = '0.1.0';

    public static function migrate(): void
    {
        if ( ! function_exists( 'dbDelta' ) ) {
            include_once ABSPATH . 'wp-admin/includes/upgrade.php';
        }
        $optionRepository = new OptionRepository();

        $installedVersion = $optionRepository->getOption(self::OPTION_KEY, '0.0.0' );
        if ( $installedVersion === self::SCHEMA_VERSION ) {
            return; // Up-to-date
        }

        global $wpdb;
        $prefix = $wpdb->prefix . self::PLUGIN_DB_PREFIX;

        $queries = [
            self::getImportTable( $prefix ),
            self::getProgressTable( $prefix ),
        ];

        foreach ( $queries as $sql ) {
            dbDelta( $sql );
        }

        if ( $installedVersion === false ) {
            $optionRepository->addOption( self::OPTION_KEY, self::SCHEMA_VERSION );
        } else {
            $optionRepository->updateOption( self::OPTION_KEY, self::SCHEMA_VERSION );
        }
    }

    public static function getImportTable( string $prefix ): string
    {
        return "CREATE TABLE IF NOT EXISTS " . $prefix . "imports (
                id BIGINT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
                name VARCHAR(255) NOT NULL,
                url VARCHAR(2048) NOT NULL,
                category_mapping JSON NULL,
                path VARCHAR(2048) NULL,
                updated_at DATETIME NULL,
                created_at DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP
            ) DEFAULT CHARSET=utf8 COLLATE utf8_general_ci;";
    }

    public static function getProgressTable( string $prefix ): string
    {
        return "CREATE TABLE IF NOT EXISTS " . $prefix . "progress (
                id BIGINT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
                import_id BIGINT UNSIGNED NOT NULL,
                status ENUM('pending','running','completed','failed') NOT NULL DEFAULT 'pending',
                total INT UNSIGNED NOT NULL DEFAULT 0,
                imported INT UNSIGNED NOT NULL DEFAULT 0,
                offset INT UNSIGNED NOT NULL DEFAULT 0,
                started_at DATETIME NULL,
                updated_at DATETIME NULL,
                INDEX idx_status_offset_total (status, offset, total),
                FOREIGN KEY (import_id) REFERENCES " . $prefix . "imports(id) ON DELETE CASCADE
            ) DEFAULT CHARSET=utf8 COLLATE utf8_general_ci;";
    }
}
