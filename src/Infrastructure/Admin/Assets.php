<?php

namespace SineFine\PromImport\Infrastructure\Admin;

class Assets
{
    public function __construct(
    ) {
    }
	public function enqueue(): void
    {
	    $screen = function_exists('get_current_screen') ? get_current_screen() : null;
	    if (! $screen || $screen->id !== 'prom-ua-importer_page_prom-products-importer') {
		    return;
	    }

		wp_enqueue_script(
			'wcapi-importer-plugin',
		    plugin_dir_url( __FILE__ ) . '/../../../../assets/js/plugin.js',
			['jquery'],
			'1.0.0',
			true);

	    wp_localize_script('wcapi-importer-plugin', 'promImporterAjaxObj', [
		    'ajaxurl' => admin_url('admin-ajax.php'),
		    'importing_text' => __('Importing...', 'prom-import'),
		    'success_text' => __('Successfully imported!', 'prom-import'),
		    'error_text' => __('Error importing product', 'prom-import'),
		    'imported_text' => __('Imported', 'prom-import'),
		    'nonce' => wp_create_nonce('prom_importer_nonce')
	    ]);
    }
}
