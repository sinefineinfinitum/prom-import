<?php

declare(strict_types=1);

namespace SineFine\PromImport\Infrastructure\Hooks;

if ( ! defined( 'ABSPATH' ) ) exit;
class HookRegistrar {

	public function addAction(string $hookName, callable $callback, int $priority = 10, int $acceptedArgs = 1): void
	{
		add_action($hookName, $callback, $priority = 10, $acceptedArgs = 1);
	}

	public function addFilter(string $hookName, callable $callback, int $priority = 10, int $acceptedArgs = 1): void
	{
		add_filter($hookName, $callback, $priority = 10, $acceptedArgs = 1);
	}
}