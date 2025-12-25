<?php

namespace SineFine\PromImport\Application\Import;

use JetBrains\PhpStorm\NoReturn;
use SimpleXMLElement;
use SineFine\PromImport\Infrastructure\Http\WpHttpClient;
use WP_Error;

class XmlService
{
	public function __construct(private WpHttpClient $httpClient)
	{}

	public function getXml(): SimpleXMLElement|bool
	{
		$domain_url = self::getUrl();
		$response   = $this->httpClient->get( $domain_url );

		$this->validateResponse( $response );
		$responseBody = wp_remote_retrieve_body( $response );
		$xml          = simplexml_load_string( $responseBody );

		$this->validateXml( $xml );
		return $xml;
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

	private function renderInvalidResponse(string $responserText): void
	{
		echo '<div class="error notice"><p>'
		     . esc_html($responserText)
		     . '</p></div>';
		wp_die();
	}

	public static function getUrl(): mixed
	{
		$domain_url = get_option('prom_domain_url_input');
		if ( empty( $domain_url ) ) {
			echo '<div class="error notice"><p>'
			     . esc_html( __( 'Please configure the domain URL in settings first.', 'spss12-import-prom-woo' ) )
			     . '</p></div>';
			wp_die();
		}

		return $domain_url;
	}


	private function validateXml( mixed $xml): void
	{
		if ( ! $xml instanceof SimpleXMLElement ) {
			echo '<div class="error notice"><p>'
			     . esc_html( __( 'Failed to retrieve products data', 'spss12-import-prom-woo' ) )
			     . '</p></div>';
			wp_die();
		}
	}
}