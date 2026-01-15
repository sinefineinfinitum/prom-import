<?php

declare(strict_types=1);

namespace SineFine\PromImport\Infrastructure\WP;

use SineFine\PromImport\Plugin;

class Uninstall
{
	public static function uninstall(): void
	{
		delete_option('prom_domain_url_input');
		delete_option('prom_categories_input');

		if (is_dir(Plugin::CACHE_DIR)) {
			self::deleteFiles(Plugin::CACHE_DIR);
			rmdir(Plugin::CACHE_DIR);
		}
	}

	private static function deleteFiles(string $dir): void
	{
		if ( ! str_ends_with( $dir, '/' ) ) {
			$dir .= '/';
		}

		$files = glob($dir . '*', GLOB_MARK);

		foreach ($files as $file) {
			unlink($file);
		}
	}
}
