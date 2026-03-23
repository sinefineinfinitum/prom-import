<?php

declare(strict_types=1);

namespace SineFine\PromImport\Infrastructure\Admin;

use SineFine\PromImport\Application\Import\XmlService;
use SineFine\PromImport\Presentation\AdminController;

class MenuPage
{
	private const PARENT_SLUG_FOR_HIDDEN_PAGE = 'options.php';
	public function __construct(
		private AdminController $adminController
	){}
    public function register(): void
    {
	    if (is_admin()) {
            add_menu_page(
                esc_html(__('Imports', 'spss12-import-prom-woo')),
                esc_html(__('Product Imports', 'spss12-import-prom-woo')),
                'manage_options',
                'spss12-import-prom-woo',
                [$this->adminController, 'imports_page'],
	            'dashicons-products',
	            11
            );

            add_submenu_page(
                self::PARENT_SLUG_FOR_HIDDEN_PAGE,
                esc_html(__('Edit Import', 'spss12-import-prom-woo')),
                esc_html(__('Edit Import', 'spss12-import-prom-woo')),
                'manage_options',
                'prom-edit-import',
                [$this->adminController, 'edit_import_page']
            );

		    add_submenu_page(
			    self::PARENT_SLUG_FOR_HIDDEN_PAGE,
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
