<?php

namespace SineFine\PromImport\Infrastructure\Container;

use Psr\Log\LoggerInterface;
use SineFine\PromImport\Application\Import\ImportService;
use SineFine\PromImport\Application\Import\XmlParser;
use SineFine\PromImport\Application\Import\XmlParserInterface;
use SineFine\PromImport\Application\Import\XmlService;
use SineFine\PromImport\Domain\Category\CategoryMappingRepositoryInterface;
use SineFine\PromImport\Domain\Feed\FeedRepositoryInterface;
use SineFine\PromImport\Domain\Product\ProductRepositoryInterface;
use SineFine\PromImport\Infrastructure\Admin\Assets;
use SineFine\PromImport\Infrastructure\Admin\MenuPage;
use SineFine\PromImport\Infrastructure\Hooks\HookRegistrar;
use SineFine\PromImport\Infrastructure\Http\WpHttpClient;
use SineFine\PromImport\Infrastructure\Logging\FileHandler;
use SineFine\PromImport\Infrastructure\Logging\HandlerInterface;
use SineFine\PromImport\Infrastructure\Logging\WpLogger;
use SineFine\PromImport\Infrastructure\Persistence\CategoryMappingRepository;
use SineFine\PromImport\Infrastructure\Persistence\FeedRepository;
use SineFine\PromImport\Infrastructure\Persistence\ProductRepository;
use SineFine\PromImport\Presentation\AdminController;
use SineFine\PromImport\Presentation\Ajax\ImportController;
use SineFine\PromImport\Presentation\Ajax\Middleware\AuthMiddleware;
use SineFine\PromImport\Presentation\Ajax\Middleware\NonceMiddleware;
use SineFine\PromImport\Presentation\SettingController;
use SineFine\PromImport\Presentation\AdminNotificationService;
use function DI\autowire;
use function DI\create;
use function DI\get;
use function DI\string;

class ContainerConfig {

	public const SPSS12_PLUGIN_DIRECTORY = 'spss12-import-prom-woo';
	/**
	 * @return array<string, mixed>
	 */
	public static function getConfig(): array
	{
		return [
			//Interfaces
			ProductRepositoryInterface::class         => autowire( ProductRepository::class )
				->constructor(
					get( LoggerInterface::class )
				),
			CategoryMappingRepositoryInterface::class => autowire( CategoryMappingRepository::class ),
			FeedRepositoryInterface::class            => autowire( FeedRepository::class ),
			LoggerInterface::class                    => autowire( WpLogger::class ),
			HandlerInterface::class                   => autowire( FileHandler::class )
				->constructor( get( 'logger.file' ) ),
			XmlParserInterface::class => autowire( XmlParser::class ),

			//Services
			HookRegistrar::class => create( HookRegistrar::class ),
			MenuPage::class      => create( MenuPage::class )
				->constructor(
					get( XmlService::class ),
					get( SettingController::class ),
					get( AdminController::class ),
				),
			Assets::class        => create( Assets::class ),
			WpHttpClient::class  => create( WpHttpClient::class ),
			XmlService::class    => autowire( XmlService::class )
				->constructor(
					get( WpHttpClient::class ),
					get( FeedRepositoryInterface::class ),
					get( XmlParserInterface::class ),
					get( AdminNotificationService::class ),
					get( LoggerInterface::class )
				),
			ImportService::class => autowire( ImportService::class )
				->constructor(
					get( ProductRepositoryInterface::class ),
					get( CategoryMappingRepositoryInterface::class ),
					get( LoggerInterface::class )
				),
			AdminNotificationService::class => autowire( AdminNotificationService::class )
				->constructor(
					get( HookRegistrar::class ),
					get( LoggerInterface::class )
				),

			// Middlewares
			AuthMiddleware::class => create( AuthMiddleware::class ),
			NonceMiddleware::class => create( NonceMiddleware::class )
				->constructor( get('nonce.action' ) ),

			// Controllers
			ImportController::class  => autowire( ImportController::class )
				->constructor(
					get( ImportService::class ),
					get( CategoryMappingRepositoryInterface::class ),
				)
				->method( 'setMiddlewares',
					[
						get( AuthMiddleware::class ),
						get( NonceMiddleware::class )
					]
				),
			SettingController::class => autowire( SettingController::class )
				->method( 'setMiddlewares',
					[ get(AuthMiddleware::class),]
				),
			AdminController::class   => autowire( AdminController::class )
				->constructor(
					get( XmlParser::class ),
					get( XmlService::class ),
					get( ProductRepositoryInterface::class ),
					get( CategoryMappingRepositoryInterface::class ),
				)
				->method( 'setMiddlewares',
					[get(AuthMiddleware::class),]
				),

			'logger.filepath'     => WP_CONTENT_DIR . '/uploads/spss12-log',
			'logger.file'     => string('{logger.filepath}/import-plugin.log'),
			'nonce.action' => 'prom_importer_nonce',
		];
	}
}
