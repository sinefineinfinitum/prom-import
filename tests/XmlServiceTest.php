<?php

declare(strict_types=1);

namespace SineFine\PromImport\Tests;

use PHPUnit\Framework\TestCase;
use Psr\Log\LoggerInterface;
use SimpleXMLElement;
use SineFine\PromImport\Application\Import\XmlParserInterface;
use SineFine\PromImport\Application\Import\XmlService;
use SineFine\PromImport\Domain\Exception\DownloadException;
use SineFine\PromImport\Domain\Exception\InvalidXmlException;
use SineFine\PromImport\Domain\Feed\Feed;
use SineFine\PromImport\Infrastructure\Hooks\HookRegistrar;
use SineFine\PromImport\Infrastructure\Http\WpHttpClient;
use SineFine\PromImport\Presentation\AdminNotificationService;
use SineFine\PromImport\Tests\Fake\FakeFeedRepository;
use WP_Error;

class XmlServiceTest extends TestCase
{
    private $httpClient;
    private $xmlParser;
    private FakeFeedRepository $feedRepository;
    private XmlService $xmlService;
	private HookRegistrar $hooks;
	private LoggerInterface $logger;
	private AdminNotificationService $notificationService;

	protected function setUp(): void
    {
        $this->httpClient = $this->createMock(WpHttpClient::class);
        $this->xmlParser = $this->createMock(XmlParserInterface::class);
        $this->feedRepository = new FakeFeedRepository([]);
		$this->hooks = new HookRegistrar();
		$this->logger = $this->createMock(LoggerInterface::class);
		$this->notificationService = new AdminNotificationService(
			$this->hooks,
			$this->logger,
		);
        $this->xmlService = new XmlService(
			$this->httpClient,
			$this->feedRepository,
			$this->xmlParser,
			$this->notificationService,
			$this->logger
        );

        global $wp_options;
        $wp_options = [];
    }

    public function test_sanitizeUrlAndSaveXml_saves_feed_on_success(): void
    {
        $url = 'https://example.com/feed.xml';
        $xmlContent = '<root><item>test</item></root>';
        
        $this->httpClient->method('get')->willReturn([
            'response' => ['code' => 200],
            'body' => $xmlContent
        ]);

        $this->xmlParser->expects($this->once())
            ->method('validateFormat')
            ->with($xmlContent);

        $result = $this->xmlService->sanitizeUrlAndSaveXml($url);

        $this->assertSame($url, $result);
        $this->assertCount(1, $this->feedRepository->savedFeeds);
        $this->assertSame($xmlContent, $this->feedRepository->getLatest()->content());
        $this->assertSame('example.com', $this->feedRepository->getLatest()->domain());
    }

    public function test_sanitizeUrlAndSaveXml_handles_download_exception(): void
    {
        $url = 'https://example.com/feed.xml';
        $this->httpClient->method('get')->willReturn(new WP_Error('error', 'Network error'));

        $result = $this->xmlService->sanitizeUrlAndSaveXml($url);

        $this->assertSame('', $result);
        $this->assertCount(0, $this->feedRepository->savedFeeds);
    }

    public function test_sanitizeUrlAndSaveXml_handles_invalid_xml_exception(): void
    {
        $url = 'https://example.com/feed.xml';
        $this->httpClient->method('get')->willReturn([
            'response' => ['code' => 200],
            'body' => 'invalid'
        ]);

        $this->xmlParser->method('validateFormat')
            ->willThrowException(new InvalidXmlException('Invalid XML'));

        $result = $this->xmlService->sanitizeUrlAndSaveXml($url);

        $this->assertSame('', $result);
        $this->assertCount(0, $this->feedRepository->savedFeeds);
    }

    public function test_getXml_returns_simplexmlelement_on_success(): void
    {
        $xmlContent = '<root><item>test</item></root>';
        $this->feedRepository->setLatest(new Feed(time(), 'example.com', $xmlContent));

        $xml = $this->xmlService->getXml();

        $this->assertInstanceOf( SimpleXMLElement::class, $xml);
        $this->assertSame('test', (string)$xml->item);
    }

    public function test_getUrl_returns_option_value(): void
    {
        global $wp_options;
        $wp_options['prom_domain_url_input'] = 'https://example.com';

        $this->assertSame('https://example.com', $this->xmlService->getUrl());
    }

    public function test_getUrl_throws_exception_if_option_empty(): void
    {
        global $wp_options;
        $wp_options['prom_domain_url_input'] = '';

        $this->expectException(\RuntimeException::class);
        $this->expectExceptionMessage('XML URL is not configured');

        $this->xmlService->getUrl();
    }
}
