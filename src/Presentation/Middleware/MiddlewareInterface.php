<?php

declare(strict_types=1);

namespace SineFine\PromImport\Presentation\Middleware;

interface MiddlewareInterface
{
	/**
	 * Handle the request and pass to next middleware or controller.
	 * Should call wp_send_json_error() or wp_die() if validation fails.
	 */
	public function handle(): void;
}
