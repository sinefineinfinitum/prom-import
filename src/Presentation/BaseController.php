<?php

declare(strict_types=1);

namespace SineFine\PromImport\Presentation;

use SimpleXMLElement;
use WP_Error;

class BaseController {
	protected function checkUserPermission(): void
	{
		if ( ! current_user_can( 'manage_options' ) ) {
			wp_die( esc_html( __( 'You do not have sufficient permissions to access this page.', 'spss12-import-prom-woo' ) ) );
		}
	}

	protected function checkNonce(string $actionName): void
	{
		$nonce = isset($_REQUEST['nonce']) && is_string($_REQUEST['nonce'])
			? sanitize_text_field(wp_unslash($_REQUEST['nonce']))
			: '';
		if (! wp_verify_nonce($nonce, $actionName)) {
			wp_send_json_error(['message' => esc_html(__('Security check failed', 'spss12-import-prom-woo'))]);
		}
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