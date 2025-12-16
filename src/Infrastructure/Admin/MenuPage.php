<?php

declare(strict_types=1);

namespace SineFine\PromImport\Infrastructure\Admin;

use SineFine\PromImport\Presentation\AdminController;

class MenuPage
{
    public function register(): void
    {
	    if (is_admin()) {
		    add_menu_page(
			    esc_html(__('Prom Ua Importer Catalogs Settings', 'spss12-import-prom-woo')),
			    esc_html(__('Prom Ua Importer', 'spss12-import-prom-woo')),
			    'manage_options',
			    'spss12-import-prom-woo',
			    [new AdminController(), 'prom_settings_page_content'],
			    'dashicons-products',
			    10
		    );

		    add_submenu_page(
			    'spss12-import-prom-woo',
			    esc_html(__('Categories Importer', 'spss12-import-prom-woo')),
			    esc_html(__('Categories Importer', 'spss12-import-prom-woo')),
			    'manage_options',
			    'prom-categories-importer',
			    [new AdminController(), 'prom_categories_importer']
		    );

		    add_submenu_page(
			    'spss12-import-prom-woo',
			    esc_html(__('Products Importer', 'spss12-import-prom-woo')),
			    esc_html(__('Products Importer', 'spss12-import-prom-woo')),
			    'manage_options',
			    'prom-products-importer',
			    [new AdminController(), 'prom_products_importer']
		    );
	    }
    }

    public function register_settings(): void
    {
	    add_settings_section(
		    'prom_importer_section',
		    esc_html(__('General Settings', 'spss12-import-prom-woo')),
		    [new AdminController(), 'importer_section_callback'],
		    'prom_importer_settings');

	    register_setting(
		    'prom_importer_group',
		    'prom_domain_url_input',
		    [
			    'type' => 'string',
			    'sanitize_callback' => 'esc_url_raw',
			    'default' => NULL,
		    ]);

	    add_settings_field(
		    'prom_domain_url_field',
		    esc_html(__('Prom.ua export XML URL', 'spss12-import-prom-woo')),
		    [new AdminController(), 'url_setting_callback' ],
		    'prom_importer_settings',
		    'prom_importer_section');
    }
}
