<?php

namespace SineFine\PromImport;

use DI\ContainerBuilder;
use Exception;
use SineFine\PromImport\Infrastructure\Admin\Assets;
use SineFine\PromImport\Infrastructure\Admin\MenuPage;
use SineFine\PromImport\Infrastructure\Container\ContainerConfig;
use SineFine\PromImport\Infrastructure\Hooks\HookRegistrar;
use SineFine\PromImport\Presentation\Rest\ImportRestController;

final class Plugin
{
public const VERSION = '0.0.6';
	/**
	 * @throws Exception
	 */
	public function boot(): void
    {
	    $builder = new ContainerBuilder();
	    $builder->addDefinitions( ContainerConfig::getConfig() );
	    $builder->enableCompilation(
		    PLUGINDIR
		    . DIRECTORY_SEPARATOR . ContainerConfig::SPSS12_PLUGIN_DIRECTORY
		    . DIRECTORY_SEPARATOR . 'cache',
		    'CompiledContainer' . filter_var( self::VERSION, FILTER_SANITIZE_NUMBER_INT )
	    );
		$container = $builder->build();

	    $hooks       = $container->get( HookRegistrar::class );
	    $menu        = $container->get( MenuPage::class );
	    $assets      = $container->get( Assets::class );
	    $rest        = $container->get( ImportRestController::class );

        // Register hooks
        $hooks->addAction('admin_menu', [$menu, 'register']);
	    $hooks->addAction('admin_init', [$menu, 'register_setting_url']);
	    $hooks->addAction('admin_init', [$menu, 'register_setting_categories']);
	    $hooks->addAction('admin_enqueue_scripts', [$assets, 'enqueue'], 1);
	    $hooks->addAction('admin_notices', [$menu, 'settings_errors']);
		$hooks->addAction('rest_api_init', [$rest, 'register_routes']);
    }
}
