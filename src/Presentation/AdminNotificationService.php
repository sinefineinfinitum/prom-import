<?php

namespace SineFine\PromImport\Presentation;

use Psr\Log\LoggerInterface;
use SineFine\PromImport\Infrastructure\Hooks\HookRegistrar;

class AdminNotificationService
{
	public function __construct(
		private HookRegistrar $hooks,
		private LoggerInterface $logger
	) {
	}

	public function renderNoticeResponse(string $responseText, string $type = 'notice-warning'): void
	{
		add_settings_error( 'prom_domain_url_input', sanitize_title($responseText), $responseText, $type );
		$this->hooks->addAction(
			'spss12_admin_notices',
			function ( string $notice ) use ( $type ) {
				echo "<div class='notice {$type}'><p>" . esc_html__( $notice, 'spss12-import-prom-woo' ) . "</p></div>";
			}
		);
		do_action( 'spss12_admin_notices', $responseText );
		if ( str_contains($type, 'error') || str_contains($type, 'warning') ) {
			$this->logger->error( $responseText );
		}
	}

}