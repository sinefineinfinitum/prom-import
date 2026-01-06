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

	public function renderNoticeResponse(string $responseText): void
	{
		add_settings_error( 'prom_domain_url_input', sanitize_title($responseText), $responseText, 'notice-warning' );
		$this->hooks->addAction(
			'spss12_admin_notices',
			function ( string $notice ) {
				echo "<div class='notice notice-warning'><p>" . esc_html__( $notice, 'spss12-import-prom-woo' ) . "</p></div>";
			}
		);
		do_action( 'spss12_admin_notices', $responseText );
		$this->logger->error( $responseText );
	}

}