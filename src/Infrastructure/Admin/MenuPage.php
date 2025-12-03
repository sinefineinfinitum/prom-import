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
			    __('Prom Ua Importer Catalogs Settings', 'prom-import'),
			    __('Prom Ua Importer', 'prom-import'),
			    'manage_options',
			    'prom-importer',
			    [new AdminController(), 'prom_settings_page_content'],
			    'dashicons-products',
			    10
		    );
		    add_submenu_page(
			    'prom-importer',
			    __('Products Importer', 'prom-import'),
			    __('Products Importer', 'prom-import'),
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
		    __('General Settings', 'prom-import'),
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
		    __('Prom.ua export XML URL', 'prom-import'),
		    [new AdminController(), 'url_setting_callback' ],
		    'prom_importer_settings',
		    'prom_importer_section');
    }
}
