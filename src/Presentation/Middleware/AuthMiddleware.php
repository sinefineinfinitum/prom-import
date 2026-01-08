<?php

declare(strict_types=1);

namespace SineFine\PromImport\Presentation\Middleware;

class AuthMiddleware implements MiddlewareInterface
{
	public function handle(): void
	{
		if (!current_user_can('manage_options')) {
			wp_send_json_error([
				'message' => esc_html(__('You do not have sufficient permissions to access this page.', 'spss12-import-prom-woo'))
			], 403);
		}
	}
}
