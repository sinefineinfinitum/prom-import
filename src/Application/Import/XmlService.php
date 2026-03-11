<?php

declare(strict_types=1);

namespace SineFine\PromImport\Application\Import;

use InvalidArgumentException;
use Psr\Log\LoggerInterface;
use RuntimeException;
use SimpleXMLElement;
use SineFine\PromImport\Application\Import\Dto\FeedDto;
use SineFine\PromImport\Domain\Common\OptionRepositoryInterface;
use SineFine\PromImport\Domain\Common\XmlParserInterface;
use SineFine\PromImport\Domain\Exception\DownloadException;
use SineFine\PromImport\Domain\Exception\InvalidXmlException;
use SineFine\PromImport\Domain\Feed\FeedRepositoryInterface;
use SineFine\PromImport\Infrastructure\Http\WpHttpClient;

class XmlService
{
	public const SINEFINE_PROMIMPORT_URL_OPTION = 'sinefine_promimport_url';
    public function __construct(
		private WpHttpClient $httpClient,
		private FeedRepositoryInterface $feedRepository,
		private XmlParserInterface $xmlParser,
        private OptionRepositoryInterface $optionRepository,
		private LoggerInterface $logger,
	) {}

    /**
     * @throws DownloadException
     * @throws InvalidXmlException
     * @throws InvalidArgumentException
     */
    public function validateDownloadAndSaveXml(string $url ): string
	{
        $responseBody = $this->downloadXmlContent( $url );
        $this->xmlParser->validateFormat( $responseBody );
        $this->saveXml($url, $responseBody);

        return $url;
	}

	/**
	 * Validate URL format.
	 * @throws InvalidArgumentException
	 */
	public function validateUrl(string $url): string
	{
		if ( ! filter_var( $url, FILTER_VALIDATE_URL ) ) {
			throw new InvalidArgumentException( 'Invalid URL provided' );
		}

		return esc_url_raw( $url );
	}
    
	/**
	 * Persist an XML and update plugin option with source URL.
	 */
	public function saveXml(string $url, string $responseBody): void
	{
		$feedDto = FeedDto::create( $url, $responseBody );
		$this->feedRepository->save( $feedDto );
		$this->optionRepository->updateOption(self::SINEFINE_PROMIMPORT_URL_OPTION, $url);
	}

	/**
	 * @throws DownloadException
	 */
	public function downloadXmlContent( string $url ): string
	{
		$response = $this->httpClient->get( $url );

		if ( is_wp_error( $response ) ) {
			$message = $response->get_error_message();
			$this->logger->error( 'Failed to fetch XML from {url}: {error}', [
				'url' => $url,
				'error' => $message
			] );
			throw new DownloadException( esc_html(__('Failed to fetch XML from ', 'spss12-import-prom-woo' )) . esc_html($url) . " : " .  esc_html($message));
		}

		$code = wp_remote_retrieve_response_code( $response );
		if ( $code !== 200 ) {
			throw new DownloadException( esc_html(__('Failed to fetch XML. HTTP Code:', 'spss12-import-prom-woo')) . esc_html((string)$code) );
		}

		return wp_remote_retrieve_body( $response );
	}

	/**
	 * @throws InvalidXmlException | RuntimeException
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

    /**
     * @throws InvalidArgumentException | RuntimeException
     */
	public function getUrl(): string
	{
		$url =  $this->optionRepository->getOption(self::SINEFINE_PROMIMPORT_URL_OPTION);
		if ( empty( $url ) ) {
			throw new RuntimeException( 'XML URL is not configured' );
		}
		if ( ! is_string($url) || ! filter_var( $url, FILTER_VALIDATE_URL ) ) {
			throw new InvalidArgumentException( 'Invalid XML URL provided' );
		}

		return $url;
	}
}
