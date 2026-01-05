<?php

namespace SineFine\PromImport\Infrastructure\Logging;

interface HandlerInterface {
	public const DEFAULT_FORMAT = '[spss12] %timestamp% [%level%]: %message%';

	/**
	 * @param array<string, mixed> $vars
	 */
	public function handle( array $vars ): void;
}
