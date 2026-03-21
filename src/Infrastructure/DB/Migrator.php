<?php

namespace SineFine\PromImport\Infrastructure\DB;


class Migrator {
	public const PLUGIN_DB_PREFIX = "spss12_import_";
	private const OPTION_KEY = 'spss12_import_db_schema_version';
	private const SCHEMA_VERSION = '0.0.2';

	public static function migrate(): void {
		if ( ! function_exists( 'dbDelta' ) ) {
			require_once ABSPATH . 'wp-admin/includes/upgrade.php';
		}

		$installedVersion = get_option( self::OPTION_KEY, '0.0.0' );
		if ( $installedVersion === self::SCHEMA_VERSION ) {
			return; // Up-to-date
		}

		global $wpdb;
		$prefix = $wpdb->prefix . self::PLUGIN_DB_PREFIX;

		$queries = [
			self::getImportTable( $prefix ),

		];

		foreach ( $queries as $sql ) {
			dbDelta( $sql );
		}

		if ( $installedVersion === false ) {
			add_option( self::OPTION_KEY, self::SCHEMA_VERSION );
		} else {
			update_option( self::OPTION_KEY, self::SCHEMA_VERSION );
		}
	}

	public static function getImportTable( string $prefix ): string {
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
}
