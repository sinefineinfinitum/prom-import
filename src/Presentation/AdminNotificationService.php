<?php

namespace SineFine\PromImport\Presentation;

use Psr\Log\LoggerInterface;
use SineFine\PromImport\Application\Import\XmlService;
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
		$this->hooks->addAction(
			'sinefine_promimport_notices',
			function ( string $notice ) use ( $type ) {
				echo "<div class='notice" . esc_attr($type) . "'><p>"
				     . esc_html(__( "Error: ", 'spss12-import-prom-woo' )) . esc_html($notice)
				     . "</p></div>";
			}
		);
		do_action( 'sinefine_promimport_notices', $responseText );
		if ( str_contains($type, 'error') || str_contains($type, 'warning') ) {
			$this->logger->error( $responseText );
		}
	}

}