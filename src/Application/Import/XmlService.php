<?php

declare(strict_types=1);

namespace SineFine\PromImport\Application\Import;

use SimpleXMLElement;
use SineFine\PromImport\Application\Import\Dto\FeedDto;
use SineFine\PromImport\Domain\Feed\FeedRepositoryInterface;
use SineFine\PromImport\Infrastructure\Hooks\HookRegistrar;
use SineFine\PromImport\Infrastructure\Http\WpHttpClient;
use WP_Error;

class XmlService
{
	public function __construct(
		private WpHttpClient $httpClient,
		private FeedRepositoryInterface $feedRepository,
		private HookRegistrar $hooks,
	) {}

	public function sanitizeUrlAndSaveXml( string $url ): WP_Error|string
	{
		$response = $this->httpClient->get( $url );
		$this->validateResponse( $response );

		$responseBody = wp_remote_retrieve_body( $response );
		$xml          = simplexml_load_string( $responseBody );
		$this->validateXml( $xml );

		$feedDto = new FeedDto(
			time(),
			(string) parse_url( $url, PHP_URL_HOST ),
			$responseBody
		);

		$this->feedRepository->save( $feedDto );

		return esc_url_raw( $url );
	}

	public function getXml(): SimpleXMLElement|bool
	{
		$latestFeed = $this->feedRepository->getLatest();
		$xml = simplexml_load_string( $latestFeed->content() );
		$this->validateXml( $xml );

		return $xml;
	}
	public function getUrl(): mixed
	{
		$domain_url = get_option('prom_domain_url_input');
		if ( empty( $domain_url ) ) {
			$this->renderInvalidResponse(__( 'Please configure the xml URL in settings first.', 'spss12-import-prom-woo' ) );
		}

		return $domain_url;
	}

	/**
	 * @param array<string, object>|WP_Error $response
	 */
	private function validateResponse(array|WP_Error $response): void
	{
		if ( is_wp_error( $response ) ) {
			if ( $response->get_error_code() === 'timeout' ) {
				$this->renderInvalidResponse(__( 'Request timeout. The remote server is taking too long to respond.', 'spss12-import-prom-woo' ));
			} else {
				$this->renderInvalidResponse($response->get_error_message() );
			}
		}
		else if ( $response['response']['code'] != 200 ) {
			$this->renderInvalidResponse(__('Failed to read xml. Make sure website URL is set correctly.', 'spss12-import-prom-woo'));
		}
	}

	public function renderInvalidResponse(string $responseText): void
	{
		add_settings_error( 'prom_domain_url_input', sanitize_title($responseText), $responseText, 'notice-warning' );
		$this->hooks->addAction(
			'admin_notices',
			function ( string $notice ) {
				echo "<div class='notice notice-warning'><p>" . esc_html__( $notice ) . "</p></div>";
			}
		);
		do_action( 'admin_notices', $responseText );
	}

	private function validateXml( mixed $xml): void
	{
		if ( ! $xml instanceof SimpleXMLElement ) {
			$this->renderInvalidResponse(__( 'Failed to retrieve products data', 'spss12-import-prom-woo' ) );
		}
	}
}