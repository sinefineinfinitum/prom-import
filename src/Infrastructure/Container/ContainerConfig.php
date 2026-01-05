<?php

namespace SineFine\PromImport\Infrastructure\Container;

use Psr\Log\LoggerInterface;
use SineFine\PromImport\Application\Import\ImportService;
use SineFine\PromImport\Application\Import\XmlParser;
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
use SineFine\PromImport\Presentation\SettingController;
use function DI\autowire;
use function DI\create;
use function DI\get;
use function DI\string;

class ContainerConfig {

	/**
	 * @return array<string, mixed>
	 */
	public static function getConfig(): array
	{
		return [
			ProductRepositoryInterface::class         => autowire( ProductRepository::class )
				->constructor(
					get( LoggerInterface::class )
				),
			CategoryMappingRepositoryInterface::class => autowire( CategoryMappingRepository::class ),
			FeedRepositoryInterface::class            => autowire( FeedRepository::class ),
			LoggerInterface::class                    => autowire( WpLogger::class ),
			HandlerInterface::class                   => autowire( FileHandler::class )
			->constructor( get( 'logger.file' ) ),

			HookRegistrar::class => create( HookRegistrar::class ),
			MenuPage::class      => create( MenuPage::class )
				->constructor(
					get( XmlService::class ),
					get( SettingController::class ),
					get( AdminController::class ),
				),
			Assets::class        => create( Assets::class ),
			XmlParser::class     => create( XmlParser::class ),
			WpHttpClient::class  => create( WpHttpClient::class ),
			XmlService::class    => autowire( XmlService::class )
				->constructor(
					get( WpHttpClient::class ),
					get( FeedRepositoryInterface::class ),
					get( HookRegistrar::class ),
					get( LoggerInterface::class )
				),
			ImportService::class => autowire( ImportService::class )
				->constructor(
					get( ProductRepositoryInterface::class ),
					get( CategoryMappingRepositoryInterface::class ),
					get( LoggerInterface::class )
				),

			ImportController::class  => autowire( ImportController::class )
				->constructor(
					get( ImportService::class ),
					get( CategoryMappingRepositoryInterface::class )
				),
			SettingController::class => autowire( SettingController::class ),
			AdminController::class   => autowire( AdminController::class )
				->constructor(
					get( XmlParser::class ),
					get( XmlService::class ),
					get( ProductRepositoryInterface::class ),
					get( CategoryMappingRepositoryInterface::class ),
				),

			'logger.filepath'     => '/spss12-log',
			'logger.file'     => string('{logger.filepath}/import-plugin.log'),
		];
	}
}
