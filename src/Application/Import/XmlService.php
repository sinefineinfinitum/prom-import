<?php

namespace SineFine\PromImport\Application\Import;

use SimpleXMLElement;
use SineFine\PromImport\Domain\Feed\Feed;
use SineFine\PromImport\Domain\Feed\FeedRepositoryInterface;
use SineFine\PromImport\Infrastructure\Http\WpHttpClient;
use WP_Error;

class XmlService
{
	public function __construct(
		private WpHttpClient $httpClient,
		private FeedRepositoryInterface $feedRepository
	) {}

	public function sanitizeUrlAndSaveXml( $url ): WP_Error|string
	{
		$response   = $this->httpClient->get( $url );
		$this->validateResponse( $response );

		$responseBody = wp_remote_retrieve_body( $response );
		$xml = simplexml_load_string( $responseBody );
		$this->validateXml( $xml );

		$feed = new Feed(
			time(),
			(string)parse_url($url, PHP_URL_HOST ),
			$responseBody
		);

		$this->feedRepository->save($feed);
		return esc_url_raw( $url );
	}

	public function getXml(): SimpleXMLElement|bool
	{
		$latestFeed = $this->feedRepository->getLatest();
		$xml = simplexml_load_string( $latestFeed->content() );
		$this->validateXml( $xml );

		return $xml;
	}
	public static function getUrl(): mixed
	{
		$domain_url = get_option('prom_domain_url_input');
		if ( empty( $domain_url ) ) {
			self::renderInvalidResponse(__( 'Please configure the domain URL in settings first.', 'spss12-import-prom-woo' ) );
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
		if ( $response['response']['code'] != 200 ) {
			$this->renderInvalidResponse(__('Failed to read xml. Make sure website URL is set correctly.', 'spss12-import-prom-woo'));
		}
	}

	public static function renderInvalidResponse(string $responseText): void
	{
		echo '<div class="error notice"><p>'
		     . esc_html($responseText)
		     . '</p></div>';
		wp_die();
	}

	private function validateXml( mixed $xml): void
	{
		if ( ! $xml instanceof SimpleXMLElement ) {
			$this->renderInvalidResponse(__( 'Failed to retrieve products data', 'spss12-import-prom-woo' ) );
		}
	}
}