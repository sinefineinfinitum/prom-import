<?php

declare(strict_types=1);

namespace SineFine\PromImport\Infrastructure\Admin;

use SineFine\PromImport\Plugin;

class Assets
{
    public function __construct(
    ) {
    }

	public function enqueue(): void {
		$screen = function_exists( 'get_current_screen' ) ? get_current_screen() : null;
		if ( ! $screen || ! in_array( $screen->id, [
				'prom-ua-importer_page_prom-products-importer',
				'prom-ua-importer_page_prom-categories-importer',
			] ) ) {
			return;
		}

		// Determine which file to use (dev or production)
		$assets_dir = plugin_dir_path( __FILE__ ) . '../../../assets/js/';
		$assets_url = plugin_dir_url( __FILE__ ) . '../../../assets/js/';

		// Use minified version in production, source in development
		$script_file = 'dist/plugin.min.js';
		$script_path = $assets_dir . $script_file;

		// Fallback to source if built file doesn't exist
		if ( ! file_exists( $script_path ) ) {
			$script_file = 'src/plugin.js';
		}

		// Get file modification time for cache busting
		$version = file_exists( $assets_dir . $script_file )
			? (string) filemtime( $assets_dir . $script_file )
			: Plugin::VERSION;

		wp_enqueue_script(
			'spss12-import-prom-woo-plugin',
			$assets_url . $script_file,
			[ 'jquery' ],
			$version,
			[ 'in_footer' => true ]
		);

		wp_localize_script( 'spss12-import-prom-woo-plugin', 'promImporterAjaxObj', [
			// REST API
			'rest_url'           => esc_url_raw( rest_url() ),
			'rest_nonce'         => wp_create_nonce( 'wp_rest' ),

			// Legacy AJAX (for backward compatibility)
			'ajaxurl'            => admin_url( 'admin-ajax.php' ),
			'nonce'              => wp_create_nonce( 'prom_importer_nonce' ),

			// Localized strings
			'loading_text'       => esc_html( __( 'Loading...', 'spss12-import-prom-woo' ) ),
			'importing_text'     => esc_html( __( 'Importing...', 'spss12-import-prom-woo' ) ),
			'success_text'       => esc_html( __( 'Successfully imported!', 'spss12-import-prom-woo' ) ),
			'error_text'         => esc_html( __( 'Error importing product', 'spss12-import-prom-woo' ) ),
			'imported_text'      => esc_html( __( 'Imported', 'spss12-import-prom-woo' ) ),
			'saved_text'         => esc_html( __( 'Saved', 'spss12-import-prom-woo' ) ),
			'no_categories_text' => esc_html( __( 'No categories selected', 'spss12-import-prom-woo' ) ),
		] );
	}
}
