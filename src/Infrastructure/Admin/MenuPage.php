<?php

declare(strict_types=1);

namespace SineFine\PromImport\Infrastructure\Admin;

use SineFine\PromImport\Application\Import\XmlService;
use SineFine\PromImport\Presentation\AdminController;
use SineFine\PromImport\Presentation\SettingController;

class MenuPage
{
	public function __construct(
		private XmlService $xmlService,
		private SettingController $settingController,
		private AdminController $adminController
	){}
    public function register(): void
    {
	    if (is_admin()) {
		    add_menu_page(
			    esc_html(__('Prom Ua Importer Catalogs Settings', 'spss12-import-prom-woo')),
			    esc_html(__('Prom Ua Importer', 'spss12-import-prom-woo')),
			    'manage_options',
			    'spss12-import-prom-woo',
			    [$this->settingController, 'prom_settings_page_content'],
			    'dashicons-products',
			    11
		    );

		    add_submenu_page(
			    'spss12-import-prom-woo',
			    esc_html(__('Categories Importer', 'spss12-import-prom-woo')),
			    esc_html(__('Categories Importer', 'spss12-import-prom-woo')),
			    'manage_options',
			    'prom-categories-importer',
			    [$this->adminController, 'prom_categories_importer']
		    );

		    add_submenu_page(
			    'spss12-import-prom-woo',
			    esc_html(__('Products Importer', 'spss12-import-prom-woo')),
			    esc_html(__('Products Importer', 'spss12-import-prom-woo')),
			    'manage_options',
			    'prom-products-importer',
			    [$this->adminController, 'prom_products_importer']
		    );
	    }
    }

	public function register_setting_url(

	): void {
	    add_settings_section(
		    'prom_importer_section',
		    esc_html(__('General Settings', 'spss12-import-prom-woo')),
		    [$this->settingController, 'importer_section_callback'],
		    'prom_importer_settings');

	    register_setting(
		    'prom_importer_group',
		    'prom_domain_url_input',
		    [
			    'type' => 'string',
			    'sanitize_callback' => [ $this->xmlService, 'sanitizeUrlAndSaveXml' ],
			    'default' => NULL,
		    ]);

	    add_settings_field(
		    'prom_domain_url_field',
		    esc_html(__('Prom.ua export XML URL', 'spss12-import-prom-woo')),
		    [$this->settingController, 'url_setting_callback' ],
		    'prom_importer_settings',
		    'prom_importer_section');
    }

	public function register_setting_categories(): void
	{
		register_setting(
			'prom_importer_group_category',
			'prom_categories_input',
			[
				'type' => 'array',
				'default' => NULL,
			]);
	}

	public function settings_errors(): void
	{
		settings_errors( 'prom_domain_url_input' );
	}
}
