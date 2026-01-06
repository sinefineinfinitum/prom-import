<?php

declare(strict_types=1);

namespace SineFine\PromImport\Application\Import;

use Psr\Log\LoggerInterface;
use SimpleXMLElement;
use SineFine\PromImport\Application\Import\Dto\FeedDto;
use SineFine\PromImport\Domain\Feed\FeedRepositoryInterface;
use SineFine\PromImport\Infrastructure\Http\WpHttpClient;
use SineFine\PromImport\Presentation\AdminNotificationService;
use WP_Error;

class XmlService
{
	public function __construct(
		private WpHttpClient $httpClient,
		private FeedRepositoryInterface $feedRepository,
		private AdminNotificationService $notificationService,
		private LoggerInterface $logger,
	) {}

	public function sanitizeUrlAndSaveXml( string $url ): WP_Error|string
	{
		$response = $this->httpClient->get( $url );
		if ( is_wp_error( $response ) ) {
			$this->logger->error( 'Failed to fetch XML from {url}: {error}', [
				'url' => $url,
				'error' => $response->get_error_message()
			] );
			$this->validateResponse( $response );
			return $response;
		}

		$responseBody = wp_remote_retrieve_body( $response );
		$xml          = simplexml_load_string( $responseBody );
		if ( $xml instanceof SimpleXMLElement ) {
			$feedDto = new FeedDto(
				time(),
				(string) parse_url( $url, PHP_URL_HOST ),
				$responseBody
			);

			$this->feedRepository->save( $feedDto );
		} else {
			$this->notificationService->renderNoticeResponse( 'Failed to retrieve products data' );
		}

		return esc_url_raw( $url );
	}

	public function getXml(): SimpleXMLElement|bool
	{
		$latestFeed = $this->feedRepository->getLatest();
		if ( ! $latestFeed ) {
			$this->notificationService->renderNoticeResponse( 'Last file with feed not found' );

			return new SimpleXMLElement('<item></item>');
		} else {
			return simplexml_load_string( $latestFeed->content() );
		}
	}
	public function getUrl(): mixed
	{
		$domain_url = get_option('prom_domain_url_input');
		if ( empty( $domain_url ) ) {
			$this->notificationService->renderNoticeResponse( 'Please configure the xml URL in settings first.');
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
				$this->notificationService->renderNoticeResponse('Request timeout. The remote server is taking too long to respond.');
			} else {
				$this->notificationService->renderNoticeResponse($response->get_error_message() );
			}
		}
		else if ( $response['response']['code'] != 200 ) {
			$this->notificationService->renderNoticeResponse('Failed to read xml. Make sure website URL is set correctly.');
		}
	}
}