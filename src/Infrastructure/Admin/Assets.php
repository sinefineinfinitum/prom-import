<?php

declare(strict_types=1);

namespace SineFine\PromImport\Infrastructure\Admin;

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

		wp_enqueue_script(
			'spss12-import-prom-woo-plugin-v2',
			plugin_dir_url( __FILE__ ) . '/../../../../assets/js/plugin.js',
			[ 'jquery' ],
			'1.0.3',
			[ 'in_footer' => true ]
		);

		wp_localize_script( 'spss12-import-prom-woo-plugin-v2', 'promImporterAjaxObj', [
			'ajaxurl'        => admin_url( 'admin-ajax.php' ),
			'importing_text' => esc_html( __( 'Importing...', 'spss12-import-prom-woo' ) ),
			'success_text'   => esc_html( __( 'Successfully imported!', 'spss12-import-prom-woo' ) ),
			'error_text'     => esc_html( __( 'Error importing product', 'spss12-import-prom-woo' ) ),
			'imported_text'  => esc_html( __( 'Imported', 'spss12-import-prom-woo' ) ),
			'saved_text'     => esc_html( __( 'Saved', 'spss12-import-prom-woo' ) ),
			'nonce'          => wp_create_nonce( 'prom_importer_nonce' )
		] );
	}
}
