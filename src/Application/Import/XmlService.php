<?php

declare(strict_types=1);

namespace SineFine\PromImport\Application\Import;

use Exception;
use InvalidArgumentException;
use Psr\Log\LoggerInterface;
use RuntimeException;
use SimpleXMLElement;
use SineFine\PromImport\Application\Import\Dto\FeedDto;
use SineFine\PromImport\Domain\Exception\DownloadException;
use SineFine\PromImport\Domain\Exception\InvalidXmlException;
use SineFine\PromImport\Domain\Feed\FeedRepositoryInterface;
use SineFine\PromImport\Infrastructure\Http\WpHttpClient;
use SineFine\PromImport\Presentation\AdminNotificationService;

class XmlService
{
	public function __construct(
		private WpHttpClient $httpClient,
		private FeedRepositoryInterface $feedRepository,
		private XmlParserInterface $xmlParser,
		private AdminNotificationService $notificationService,
		private LoggerInterface $logger,
	) {}

	public function sanitizeUrlAndSaveXml( string $url ): string
	{
		$oldValue = get_option( 'prom_domain_url_input' );

		try {
			if ( ! filter_var( $url, FILTER_VALIDATE_URL ) ) {
				throw new InvalidArgumentException( 'Invalid URL provided' );
			}

			$responseBody = $this->downloadXmlContent( $url );
			$this->xmlParser->validateFormat( $responseBody );

			$feedDto = FeedDto::create( $url, $responseBody );
			$this->feedRepository->save( $feedDto );

			return esc_url_raw( $url );

		} catch ( Exception $e ) {
			$this->notificationService->renderNoticeResponse( $e->getMessage(), 'notice-error' );
			return is_string( $oldValue ) ? $oldValue : '';
		}
	}

	/**
	 * @throws DownloadException
	 */
	private function downloadXmlContent( string $url ): string
	{
		$response = $this->httpClient->get( $url );

		if ( is_wp_error( $response ) ) {
			$message = $response->get_error_message();
			$this->logger->error( 'Failed to fetch XML from {url}: {error}', [
				'url' => $url,
				'error' => $message
			] );
			throw new DownloadException( $message );
		}

		$code = wp_remote_retrieve_response_code( $response );
		if ( $code !== 200 ) {
			throw new DownloadException( sprintf( 'Failed to fetch XML. HTTP Code: %d', $code ) );
		}

		return wp_remote_retrieve_body( $response );
	}

	/**
	 * @throws InvalidXmlException
	 */
	public function getXml(): SimpleXMLElement
	{
		$latestFeed = $this->feedRepository->getLatest();
		if ( ! $latestFeed ) {
			throw new RuntimeException( 'Feed not found' );
		}

		$xml = simplexml_load_string( $latestFeed->content() );
		if ( ! $xml instanceof SimpleXMLElement ) {
			throw new InvalidXmlException( 'Invalid XML' );
		}

		return $xml;
	}

	public function getUrl(): string
	{
		$url = get_option( 'prom_domain_url_input' );
		if ( empty( $url ) ) {
			throw new RuntimeException( 'XML URL is not configured' );
		}
		if ( ! is_string($url) || ! filter_var( $url, FILTER_VALIDATE_URL ) ) {
			throw new InvalidArgumentException( 'Invalid XML URL provided' );
		}

		return $url;
	}
}
