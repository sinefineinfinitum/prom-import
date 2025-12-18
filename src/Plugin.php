<?php

namespace SineFine\PromImport;

use SineFine\PromImport\Infrastructure\Admin\Assets;
use SineFine\PromImport\Infrastructure\Admin\MenuPage;
use SineFine\PromImport\Infrastructure\Hooks\HookRegistrar;
use SineFine\PromImport\Infrastructure\Http\WpHttpClient;
use SineFine\PromImport\Infrastructure\Persistence\ProductRepository;
use SineFine\PromImport\Application\Import\ImportService;
use SineFine\PromImport\Application\Import\XmlParser;
use SineFine\PromImport\Presentation\Ajax\ImportController;

final class Plugin
{
    public function boot(): void
    {
        //Infrastructure
	    $hooks = new HookRegistrar();


		// Build services
        $http    = new WpHttpClient();
        $parser  = new XmlParser();
        $repo    = new ProductRepository();
        $service = new ImportService($repo);

        // Admin pages and assets (currently self-contained)
        $menu  = new MenuPage();
        $assets = new Assets();

        // AJAX controller uses new ImportService
        $ajax = new ImportController($service);

        // Register hooks
        $hooks->addAction('admin_menu', [$menu, 'register']);
	    $hooks->addAction('admin_init', [$menu, 'register_settings']);
	    $hooks->addAction('admin_enqueue_scripts', [$assets, 'enqueue'], 1);
	    $hooks->addAction('wp_ajax_ajax_import_product', [$ajax, 'importProducts']);
	    $hooks->addAction('wp_ajax_ajax_import_categories', [$ajax, 'importCategories']);
	    $hooks->addFilter(
		    'upload_mimes',
		    function ($mimes) {
			    $mimes['xml'] = 'application/xml';

			    return $mimes;
		    }
		);
    }
}
