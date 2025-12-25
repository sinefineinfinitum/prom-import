<?php

namespace SineFine\PromImport\Infrastructure\Container;

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
use SineFine\PromImport\Infrastructure\Persistence\CategoryMappingRepository;
use SineFine\PromImport\Infrastructure\Persistence\FeedRepository;
use SineFine\PromImport\Infrastructure\Persistence\ProductRepository;
use SineFine\PromImport\Presentation\AdminController;
use SineFine\PromImport\Presentation\Ajax\ImportController;
use SineFine\PromImport\Presentation\SettingController;
use function DI\autowire;
use function DI\create;
use function DI\get;

class ContainerConfig {

	/**
	 * @return array<string, object>
	 */
	public static function getConfig(): array
	{
		return [
			ProductRepositoryInterface::class         => autowire( ProductRepository::class ),
			CategoryMappingRepositoryInterface::class => autowire( CategoryMappingRepository::class ),
			FeedRepositoryInterface::class            => autowire( FeedRepository::class ),

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
					get( FeedRepositoryInterface::class )
				),
			ImportService::class => autowire( ImportService::class )
				->constructor(
					get( ProductRepositoryInterface::class ),
					get( CategoryMappingRepositoryInterface::class )
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

		];
	}
}
