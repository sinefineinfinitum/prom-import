<?php

declare(strict_types=1);

namespace SineFine\PromImport\Infrastructure\Admin;

use SineFine\PromImport\Application\Import\XmlService;
use SineFine\PromImport\Presentation\AdminController;
use SineFine\PromImport\Presentation\SettingController;

class MenuPage
{
	public function __construct(
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
			    [$this->settingController, 'settings_page_content'],
			    'dashicons-products',
			    11
		    );

            add_submenu_page(
                'spss12-import-prom-woo',
                esc_html(__('Imports', 'spss12-import-prom-woo')),
                esc_html(__('Imports', 'spss12-import-prom-woo')),
                'manage_options',
                'prom-imports',
                [$this->adminController, 'imports_page']
            );

		    add_submenu_page(
			    'spss12-import-prom-woo',
			    esc_html(__('Categories Importer', 'spss12-import-prom-woo')),
			    esc_html(__('Categories Importer', 'spss12-import-prom-woo')),
			    'manage_options',
			    'prom-categories-importer',
			    [$this->adminController, 'categories_importer']
		    );

		    add_submenu_page(
			    'spss12-import-prom-woo',
			    esc_html(__('Products Importer', 'spss12-import-prom-woo')),
			    esc_html(__('Products Importer', 'spss12-import-prom-woo')),
			    'manage_options',
			    'prom-products-importer',
			    [$this->adminController, 'products_importer']
		    );
	    }
    }

	public function settings_errors(): void
	{
		settings_errors( XmlService::SINEFINE_PROMIMPORT_URL_OPTION);
	}
}
