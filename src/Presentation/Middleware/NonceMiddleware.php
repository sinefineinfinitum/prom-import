<?php

declare(strict_types=1);

namespace SineFine\PromImport\Presentation\Middleware;

class NonceMiddleware implements MiddlewareInterface
{
	public function __construct(
		private string $action = 'prom_importer_nonce'
	) {}

	public function handle(): void
	{
		$nonce = isset($_REQUEST['nonce']) && is_string($_REQUEST['nonce'])
			? sanitize_text_field(wp_unslash($_REQUEST['nonce']))
			: '';

		if (!wp_verify_nonce($nonce, $this->action)) {
			wp_send_json_error([
				'message' => esc_html(__('Security check failed', 'spss12-import-prom-woo'))
			], 403);
		}
	}
}
