<?php

namespace SineFine\PromImport\Infrastructure\Container;

use Psr\Log\LoggerInterface;
use SineFine\PromImport\Application\Import\ImportService;
use SineFine\PromImport\Application\Import\ProductManager;
use SineFine\PromImport\Application\Import\XmlParser;
use SineFine\PromImport\Application\Import\XmlService;
use SineFine\PromImport\Domain\Category\CategoryRepositoryInterface;
use SineFine\PromImport\Domain\Common\FileServiceInterface;
use SineFine\PromImport\Domain\Common\OptionRepositoryInterface;
use SineFine\PromImport\Domain\Common\XmlParserInterface;
use SineFine\PromImport\Domain\Feed\Feed;
use SineFine\PromImport\Domain\Feed\FeedRepositoryInterface;
use SineFine\PromImport\Domain\Import\ImportRepositoryInterface;
use SineFine\PromImport\Domain\Product\ImageAttachable;
use SineFine\PromImport\Domain\Product\ProductManagerInterface;
use SineFine\PromImport\Domain\Product\ProductRepositoryInterface;
use SineFine\PromImport\Infrastructure\Admin\Assets;
use SineFine\PromImport\Infrastructure\Admin\MenuPage;
use SineFine\PromImport\Infrastructure\File\FileService;
use SineFine\PromImport\Infrastructure\Hooks\HookRegistrar;
use SineFine\PromImport\Infrastructure\Http\WpHttpClient;
use SineFine\PromImport\Infrastructure\Logging\FileHandler;
use SineFine\PromImport\Infrastructure\Logging\HandlerInterface;
use SineFine\PromImport\Infrastructure\Logging\WpLogger;
use SineFine\PromImport\Infrastructure\Persistence\CategoryRepository;
use SineFine\PromImport\Infrastructure\Persistence\FeedRepository;
use SineFine\PromImport\Infrastructure\Persistence\ImageProductService;
use SineFine\PromImport\Infrastructure\Persistence\ImportRepository;
use SineFine\PromImport\Infrastructure\Persistence\OptionRepository;
use SineFine\PromImport\Infrastructure\Persistence\ProductRepository;
use SineFine\PromImport\Infrastructure\Queue\QueueManager;
use SineFine\PromImport\Presentation\AdminController;
use SineFine\PromImport\Presentation\AdminNotificationService;
use SineFine\PromImport\Presentation\Middleware\AuthMiddleware;
use SineFine\PromImport\Presentation\Middleware\NonceMiddleware;
use SineFine\PromImport\Presentation\Rest\ImportRestController;
use SineFine\PromImport\Presentation\Rest\ImportRestV2Controller;
use function DI\autowire;
use function DI\create;
use function DI\get;
use function DI\string;

class ContainerConfig {

	private const CACHE_DIRECTORY = 'cache';
	private const LOG_DIRECTORY = 'log';
	/**
	 * @return array<string, mixed>
	 */
	public static function getConfig(): array
	{
		return [
			//Repositories
			ImportRepositoryInterface::class          => autowire( ImportRepository::class ),
			ProductRepositoryInterface::class         => autowire( ProductRepository::class )
				->constructor(
					get( ImageAttachable::class ),
				),
			FeedRepositoryInterface::class            => autowire( FeedRepository::class )
                ->constructor(
                    get(FileServiceInterface::class),
                ),
			OptionRepositoryInterface::class          => autowire( OptionRepository::class ),
			CategoryRepositoryInterface::class        => create( CategoryRepository::class ),

			//Services
			LoggerInterface::class                    => autowire( WpLogger::class ),
			HandlerInterface::class                   => autowire( FileHandler::class )
				->constructor(
                    get( 'logger.file' ),
                    get(FileServiceInterface::class ),
                ),
			XmlParserInterface::class => autowire( XmlParser::class ),
			ImageAttachable::class => autowire( ImageProductService::class)
				->constructor(
					get( LoggerInterface::class ),
				),
			HookRegistrar::class => create( HookRegistrar::class ),
			MenuPage::class      => create( MenuPage::class )
				->constructor(
					get( AdminController::class ),
				),
			Assets::class        => create( Assets::class )
                ->constructor(get(FileServiceInterface::class )),
			WpHttpClient::class  => create( WpHttpClient::class ),
			XmlService::class    => autowire( XmlService::class )
				->constructor(
					get( WpHttpClient::class ),
					get( FeedRepositoryInterface::class ),
					get( XmlParserInterface::class ),
					get( LoggerInterface::class )
				),
			ProductManagerInterface::class       => autowire( ProductManager::class )
				->constructor(
					get( ProductRepositoryInterface::class ),
					get( ImageAttachable::class ),
					get( LoggerInterface::class )
				),
			QueueManager::class => autowire( QueueManager::class )
				->constructor(
					get( ProductManagerInterface::class ),
					get(ImportRepositoryInterface::class ),
					get(XmlService::class),
					get(LoggerInterface::class),
				),
			ImportService::class          => autowire( ImportService::class )
                ->constructor(
                    get( ImportRepositoryInterface::class ),
                    get( XmlService::class ),
                ),
			AdminNotificationService::class => autowire( AdminNotificationService::class )
				->constructor(
					get( HookRegistrar::class ),
					get( LoggerInterface::class )
				),
			FileServiceInterface::class => autowire( FileService::class ),

			// Middlewares
			AuthMiddleware::class => create( AuthMiddleware::class ),
			NonceMiddleware::class => create( NonceMiddleware::class )
				->constructor( get('nonce.action' ) ),

			// Controllers
			AdminController::class        => autowire( AdminController::class )
				->constructor(
					get( XmlParserInterface::class ),
					get( XmlService::class ),
					get( ProductRepositoryInterface::class ),
					get( CategoryRepositoryInterface::class ),
                    get( ImportRepositoryInterface::class )
				)
				->method( 'setMiddlewares',
					[get(AuthMiddleware::class),]
				),
			ImportRestController::class => autowire( ImportRestController::class )
				->constructor(
                    get(XmlService::class ),
					get( ProductManagerInterface::class ),
					get( ProductRepositoryInterface::class ),
                    get( LoggerInterface::class )
				),
			ImportRestV2Controller::class => autowire( ImportRestV2Controller::class )
                ->constructor(
                    get( ImportService::class ),
                    get( LoggerInterface::class )
                ),

			'logger.filepath'     => DIRECTORY_SEPARATOR . SINEFINE_PROMIMPORT_PLUGIN_DIR . DIRECTORY_SEPARATOR . self::LOG_DIRECTORY,
			'logger.file'     => string('{logger.filepath}/import-plugin.log'),
			'nonce.action' => 'sinefine_promimport_nonce',
		];
	}

    public static function getCommonDir(): string
    {
        return wp_upload_dir()['basedir'] . DIRECTORY_SEPARATOR . SINEFINE_PROMIMPORT_PLUGIN_DIR;
    }

    public static function getCacheDir(): string
    {
        return self::getCommonDir() . DIRECTORY_SEPARATOR . self::CACHE_DIRECTORY;
    }

    public static function getLogDir(): string
    {
        return self::getCommonDir() . DIRECTORY_SEPARATOR . self::LOG_DIRECTORY;
    }

    public static function getFeedDir(): string
    {
        return self::getCommonDir() . DIRECTORY_SEPARATOR . Feed::XML_FEEDS_DIRECTORY;
    }
}
