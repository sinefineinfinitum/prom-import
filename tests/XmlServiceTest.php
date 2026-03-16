<?php

declare(strict_types=1);

namespace SineFine\PromImport\Tests;

use InvalidArgumentException;
use PHPUnit\Framework\TestCase;
use Psr\Log\LoggerInterface;
use RuntimeException;
use SineFine\PromImport\Application\Import\XmlService;
use SineFine\PromImport\Domain\Common\OptionRepositoryInterface;
use SineFine\PromImport\Domain\Common\XmlParserInterface;
use SineFine\PromImport\Domain\Exception\DownloadException;
use SineFine\PromImport\Domain\Exception\InvalidXmlException;
use SineFine\PromImport\Domain\Feed\Feed;
use SineFine\PromImport\Infrastructure\Http\WpHttpClient;
use SineFine\PromImport\Tests\Fake\FakeFeedRepository;
use WP_Error;

class XmlServiceTest extends TestCase
{
    private WpHttpClient $httpClient;
    private XmlParserInterface $xmlParser;
    private OptionRepositoryInterface $optionRepository;
    private FakeFeedRepository $feedRepository;
    private LoggerInterface $logger;
    private XmlService $xmlService;

    protected function setUp(): void
    {
        $this->httpClient = $this->createMock(WpHttpClient::class);
        $this->xmlParser = $this->createMock(XmlParserInterface::class);
        $this->optionRepository = $this->createMock(OptionRepositoryInterface::class);
        $this->feedRepository = new FakeFeedRepository();
        $this->logger = $this->createMock(LoggerInterface::class);

        $this->xmlService = new XmlService(
            httpClient: $this->httpClient,
            feedRepository: $this->feedRepository,
            xmlParser: $this->xmlParser,
            optionRepository: $this->optionRepository,
            logger: $this->logger,
        );
    }

    public function test_downloadXmlContent_throws_exception_on_wp_error(): void
    {
        $url = 'https://example.com/feed.xml';
        $error = new WP_Error('test_error', 'Test error message');

        $this->httpClient->expects($this->once())
            ->method('get')
            ->with($url)
            ->willReturn($error);

        $this->logger->expects($this->once())
            ->method('error')
            ->with(
                'Failed to fetch XML from {url}: {error}',
                ['url' => $url, 'error' => 'Test error message']
            );

        $this->expectException(DownloadException::class);
        $this->expectExceptionMessage('Failed to fetch XML from ' . $url . ' : Test error message');

        $this->xmlService->downloadXmlContent($url);
    }

    public function test_downloadXmlContent_throws_exception_on_non_200_code(): void
    {
        $url = 'https://example.com/feed.xml';
        $response = ['response' => ['code' => 404]];

        $this->httpClient->expects($this->once())
            ->method('get')
            ->with($url)
            ->willReturn($response);

        $this->expectException(DownloadException::class);
        $this->expectExceptionMessage('Failed to fetch XML. HTTP Code:404');

        $this->xmlService->downloadXmlContent($url);
    }

    public function test_getXml_throws_exception_on_invalid_xml_content(): void
    {
        $this->feedRepository->setLatest(new Feed(time(), 'example.com', 'not xml'));

        $this->expectException(InvalidXmlException::class);
        $this->expectExceptionMessage('Invalid XML');

        $this->xmlService->getXml();
    }

    public function test_validateUrl_returns_sanitized_on_valid_url(): void
    {
        $input = 'https://example.com/path';
        $result = $this->xmlService->validateUrl($input);

        $this->assertSame('https://example.com/path', $result);
    }

    public function test_validateUrl_throws_on_invalid_url(): void
    {
        $this->expectException(InvalidArgumentException::class);
        $this->expectExceptionMessage('Invalid URL provided');
        $this->xmlService->validateUrl('not-a-url');
    }

    /**
     * @throws DownloadException
     * @throws InvalidXmlException
     */
    public function test_downloadValidateAndSaveXml_saves_feed_and_updates_option_on_success(): void
    {
        $url = 'https://example.com/feed.xml';
        $xmlContent = '<shop></shop>';

        $this->httpClient->method('get')->with($url)->willReturn([
            'response' => ['code' => 200],
            'body' => $xmlContent,
        ]);

        $this->xmlParser->expects($this->once())
            ->method('validateFormat')
            ->with($xmlContent);

        $this->optionRepository->expects($this->once())
            ->method('updateOption')
            ->with(XmlService::SINEFINE_PROMIMPORT_URL_OPTION, $url);

        $result = $url = $this->xmlService->validateUrl($url);
        $result = $this->xmlService->validateDownloadAndSaveXml($result);

        $this->assertSame($url, $result);
        $this->assertCount(1, $this->feedRepository->savedFeeds);
        $this->assertSame($xmlContent, $this->feedRepository->getLatest()?->content());
    }

    /**
     * @throws InvalidXmlException
     */
    public function test_downloadValidateAndSaveXml_throws_on_download_error(): void
    {
        $url = 'https://example.com/feed.xml';
        $this->httpClient->method('get')->with($url)->willReturn(new WP_Error('http_error', 'Network error'));

        $this->expectException(DownloadException::class);
        $this->xmlService->validateDownloadAndSaveXml($url);
    }

    /**
     * @throws DownloadException
     */
    public function test_downloadValidateAndSaveXml_throws_on_invalid_xml(): void
    {
        $url = 'https://example.com/feed.xml';
        $this->httpClient->method('get')->with($url)->willReturn([
            'response' => ['code' => 200],
            'body' => 'invalid-xml',
        ]);

        $this->xmlParser->method('validateFormat')
            ->willThrowException(new InvalidXmlException('Invalid XML'));

        $this->expectException(InvalidXmlException::class);
        $this->xmlService->validateDownloadAndSaveXml($url);
    }

    /**
     * @throws InvalidXmlException
     */
    public function test_getXml_returns_SimpleXMLElement_on_success(): void
    {
        $xmlContent = '<?xml version="1.0" encoding="UTF-8"?><root><item>test</item></root>';
        $this->feedRepository->setLatest(new Feed(time(), 'example.com', $xmlContent));

        $xml = $this->xmlService->getXml();
        $this->assertSame('test', (string) $xml->item);
    }

    /**
     * @throws InvalidXmlException
     */
    public function test_getXml_throws_when_no_feed_found(): void
    {
        $this->expectException(RuntimeException::class);
        $this->expectExceptionMessage('Feed not found');
        $this->xmlService->getXml();
    }


    public function test_getUrl_returns_configured_url(): void
    {
        $this->optionRepository->method('getOption')
            ->with(XmlService::SINEFINE_PROMIMPORT_URL_OPTION)
            ->willReturn('https://example.com');

        $this->assertSame('https://example.com', $this->xmlService->getUrl());
    }

    public function test_getUrl_throws_when_empty(): void
    {
        $this->optionRepository->method('getOption')
            ->with(XmlService::SINEFINE_PROMIMPORT_URL_OPTION)
            ->willReturn('');

        $this->expectException(RuntimeException::class);
        $this->expectExceptionMessage('XML URL is not configured');
        $this->xmlService->getUrl();
    }

    public function test_getUrl_throws_when_invalid_format(): void
    {
        $this->optionRepository->method('getOption')
            ->with(XmlService::SINEFINE_PROMIMPORT_URL_OPTION)
            ->willReturn('not-a-url');

        $this->expectException(InvalidArgumentException::class);
        $this->expectExceptionMessage('Invalid XML URL provided');
        $this->xmlService->getUrl();
    }
}
