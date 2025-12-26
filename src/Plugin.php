<?php

namespace SineFine\PromImport;

use DI\ContainerBuilder;
use Exception;
use SineFine\PromImport\Infrastructure\Admin\Assets;
use SineFine\PromImport\Infrastructure\Admin\MenuPage;
use SineFine\PromImport\Infrastructure\Container\ContainerConfig;
use SineFine\PromImport\Infrastructure\Hooks\HookRegistrar;
use SineFine\PromImport\Presentation\Ajax\ImportController;
use SineFine\PromImport\Application\Import\ImportBatchService;

final class Plugin
{
	/**
	 * @throws Exception
	 */
	public function boot(): void
    {
	    $builder = new ContainerBuilder();
	    $builder->addDefinitions( ContainerConfig::getConfig() );
		$container = $builder->build();

	    $hooks  = $container->get( HookRegistrar::class );
	    $menu   = $container->get( MenuPage::class );
	    $assets = $container->get( Assets::class );
	    $ajax   = $container->get( ImportController::class );
	    $batch  = $container->get( ImportBatchService::class );

        // Register hooks
        $hooks->addAction('admin_menu', [$menu, 'register']);
	    $hooks->addAction('admin_init', [$menu, 'register_setting_url' ]);
	    $hooks->addAction('admin_init', [$menu, 'register_setting_categories' ]);
	    $hooks->addAction('admin_enqueue_scripts', [$assets, 'enqueue'], 1);
	    $hooks->addAction('wp_ajax_ajax_import_product', [$ajax, 'importProducts']);
	    $hooks->addAction('wp_ajax_ajax_import_categories', [$ajax, 'importCategories']);
        $hooks->addAction('wp_ajax_ajax_import_products_async', [$ajax, 'importProductsAsync']);

        // Background processing hook for Action Scheduler / WP-Cron
        $hooks->addAction(ImportBatchService::HOOK_PROCESS_BATCH, [$batch, 'handleScheduledBatch']);
    }
}
