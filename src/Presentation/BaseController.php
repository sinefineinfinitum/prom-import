<?php

declare(strict_types=1);

namespace SineFine\PromImport\Presentation;

use SineFine\PromImport\Presentation\Middleware\MiddlewareInterface;

class BaseController
{
	/** @var MiddlewareInterface[] */
	private array $middlewares = [];

	/**
	 * Run all registered middlewares before executing controller action
	 */
	private function runMiddlewares(): void
	{
		foreach ($this->middlewares as $middleware) {
			$middleware->handle();
		}
	}

	/**
	 * @param MiddlewareInterface[] $middlewares
	 */
	public function setMiddlewares(array $middlewares): void
	{
		$this->middlewares = $middlewares;
	}

	public function __invoke(): void
	{
		$this->runMiddlewares();
	}

	/**
	 * @param string $template
	 * @param array<string, mixed> $vars
	 */
	protected function render(string $template, array $vars = []): void
	{
		extract($vars, EXTR_SKIP);
		require __DIR__ . "/../../templates/$template.php";
	}
}