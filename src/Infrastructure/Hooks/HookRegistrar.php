<?php

declare(strict_types=1);

namespace SineFine\PromImport\Infrastructure\Hooks;

class HookRegistrar {

	public function addAction(string $hook_name, callable $callback, int $priority = 10, int $accepted_args = 1): void
	{
		add_action($hook_name, $callback, $priority = 10, $accepted_args = 1);
	}
}