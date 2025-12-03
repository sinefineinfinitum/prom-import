<?php

namespace SineFine\PromImport\Infrastructure\Hooks;

class HookRegistrar {

	public function addAction($hook_name, $callback, $priority = 10, $accepted_args = 1): void
	{
		add_action($hook_name, $callback, $priority = 10, $accepted_args = 1);
	}
}