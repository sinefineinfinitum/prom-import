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
		$nonce = isset($_REQUEST['nonce']) ? sanitize_text_field(wp_unslash($_REQUEST['nonce'])) : '';
		if (! wp_verify_nonce($nonce, $actionName)) {
			wp_send_json_error(['message' => esc_html(__('Security check failed', 'spss12-import-prom-woo'))]);
		}
	}

	protected function validateResponse(array|WP_Error $response): void
	{
		if ( is_wp_error( $response ) ) {
			if ( $response->get_error_code() === 'timeout' ) {
				echo '<div class="error notice"><p>'
				     . esc_html( __( 'Request timeout. The remote server is taking too long to respond.', 'spss12-import-prom-woo' ) )
				     . '</p></div>';
			} else {
				echo '<div class="error notice"><p>'
				     . esc_html( $response->get_error_message() )
				     . '</p></div>';
			}
			wp_die();
		}
		if ( $response['response']['code'] != 200 ) {
			echo '<div class="error notice"><p>'
			     . esc_html(__('Failed to fetch products. Make sure website URL is set correctly.', 'spss12-import-prom-woo'))
			     . '</p></div>';
			wp_die();
		}
	}

	protected function validateXml( mixed $xml): void
	{
		if ( ! $xml instanceof SimpleXMLElement ) {
			echo '<div class="error notice"><p>'
			     . esc_html( __( 'Failed to retrieve products data', 'spss12-import-prom-woo' ) )
			     . '</p></div>';
			wp_die();
		}
	}


	protected function render(string $template, array $vars = []): void
	{
		extract($vars, EXTR_SKIP);
		require __DIR__ . "/../../templates/$template.php";
	}
}