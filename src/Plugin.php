<?php

declare(strict_types=1);

namespace SineFine\PromImport;

use DI\ContainerBuilder;
use Exception;
use SineFine\PromImport\Infrastructure\Admin\Assets;
use SineFine\PromImport\Infrastructure\Admin\MenuPage;
use SineFine\PromImport\Infrastructure\Container\ContainerConfig;
use SineFine\PromImport\Infrastructure\Hooks\HookRegistrar;
use SineFine\PromImport\Infrastructure\Queue\QueueManager;
use SineFine\PromImport\Presentation\Rest\ImportRestController;
use SineFine\PromImport\Presentation\Rest\ImportRestV2Controller;

final class Plugin
{
    public const SINEFINE_PROMIMPORT_VERSION = '0.0.18';

    /**
     * @throws Exception
     */
    public function boot(): void
    {
        $builder = new ContainerBuilder();
        $builder->addDefinitions( ContainerConfig::getConfig() );
        $builder->enableCompilation(
            ContainerConfig::getCacheDir(),
            'CompiledContainer' . filter_var(self::SINEFINE_PROMIMPORT_VERSION, FILTER_SANITIZE_NUMBER_INT)
        );
        $container = $builder->build();

        $hooks       = $container->get( HookRegistrar::class );
        $menu        = $container->get( MenuPage::class );
        $assets      = $container->get( Assets::class );
        $rest        = $container->get( ImportRestController::class );
        $restV2      = $container->get( ImportRestV2Controller::class );
        $queue       = $container->get( QueueManager::class );

        // Register hooks
        $hooks->addAction('admin_menu', [$menu, 'register']);
        $hooks->addAction('admin_enqueue_scripts', [$assets, 'enqueue'], 1);
        $hooks->addAction('admin_notices', [$menu, 'settings_errors']);
        $hooks->addAction('rest_api_init', [$rest, 'register_routes']);
        $hooks->addAction('rest_api_init', [$restV2, 'register_routes']);
        $hooks->addAction('spss12-import-prom-woo_queue_run_batch', [$queue, 'run']);
    }
}
