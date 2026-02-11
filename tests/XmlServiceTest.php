<?php

declare(strict_types=1);

namespace SineFine\PromImport\Tests;

use PHPUnit\Framework\TestCase;
use Psr\Log\LoggerInterface;
use Psr\Log\NullLogger;
use RuntimeException;
use SineFine\PromImport\Application\Import\XmlParser;
use SineFine\PromImport\Application\Import\XmlService;
use SineFine\PromImport\Domain\Common\OptionRepositoryInterface;
use SineFine\PromImport\Domain\Common\XmlParserInterface;
use SineFine\PromImport\Infrastructure\Hooks\HookRegistrar;
use SineFine\PromImport\Infrastructure\Http\WpHttpClient;
use SineFine\PromImport\Infrastructure\Persistence\OptionRepository;
use SineFine\PromImport\Presentation\AdminNotificationService;
use SineFine\PromImport\Tests\Fake\FakeFeedRepository;

class XmlServiceTest extends TestCase
{
    private $httpClient;
    private $xmlParser;
    private OptionRepositoryInterface $optionRepository;
    private FakeFeedRepository $feedRepository;
    private XmlService $xmlService;
	private HookRegistrar $hooks;
	private LoggerInterface $logger;
	private AdminNotificationService $notificationService;

	protected function setUp(): void
    {
		$this->httpClient = $this->createMock(WpHttpClient::class);
        $this->xmlParser = $this->getMockBuilder(XmlParser::class)
	        ->onlyMethods([ 'validateFormat'])
	        ->getMock();

        $this->optionRepository = $this->getMockBuilder(OptionRepository::class)
                                       ->getMock();
        $this->feedRepository = new FakeFeedRepository();
		$this->hooks = new HookRegistrar();
		$this->logger = new NullLogger();
		$this->notificationService = $this->getMockBuilder(AdminNotificationService::class)
		                                  ->disableOriginalConstructor()
		                                  ->getMock();

        $this->xmlService = $this->getMockBuilder(XmlService::class)
                                 ->setConstructorArgs(
									 [
										 'httpClient' => $this->httpClient,
										 'xmlParser' => $this->xmlParser,
										 'feedRepository' => $this->feedRepository,
										 'optionRepository' => $this->optionRepository,
										 'notificationService' => $this->notificationService,
										 'logger' => $this->logger,
									 ]
                                 )
                                 ->onlyMethods([ 'downloadXmlContent', 'getUrl', 'getXml'])
                                 ->getMock();
    }
//
//    public function test_validateUrlAndSaveXml_saves_feed_on_success(): void
//    {
//        $url = 'https://example.com/feed.xml';
//        $xmlContent = '<root><item>test</item></root>';
//	    $this->httpClient->expects(self::once());
//
//
//        $this->httpClient->method('get')->willReturn([
//            'response' => ['code' => 200],
//            'body' => $xmlContent
//        ]);
//	    $this->xmlService->method('downloadXmlContent')
//		    ->with($url)
//		    ->willReturn($xmlContent);
//
//        $this->xmlParser->expects($this->once())
//            ->method('validateFormat')
//            ->with($xmlContent);
//
//
//        $result = $this->xmlService->validateUrlAndSaveXml($url);
//
//        $this->assertSame($url, $result);
////        $this->assertCount(1, $this->feedRepository->savedFeeds);
////        $this->assertSame($xmlContent, $this->feedRepository->getLatest()->content());
//    }
//
//    public function test_validateUrlAndSaveXml_handles_download_exception(): void
//    {
//        $url = 'https://example.com/feed.xml';
//        $this->httpClient->method('get')->willReturn(new WP_Error('error', 'Network error'));
//
//        $this->optionRepository->method('getOption')->willReturn('');
//
//        $result = $this->xmlService->validateUrlAndSaveXml($url);
//
//        $this->assertSame('', $result);
//        $this->assertCount(0, $this->feedRepository->savedFeeds);
//    }
//
//    public function test_validateUrlAndSaveXml_handles_invalid_xml_exception(): void
//    {
//        $url = 'https://example.com/feed.xml';
//        $this->httpClient->method('get')->willReturn([
//            'response' => ['code' => 200],
//            'body' => 'invalid'
//        ]);
//
//        $this->xmlParser->method('validateFormat')
//            ->willThrowException(new InvalidXmlException('Invalid XML'));
//
//        $this->optionRepository->method('getOption')->willReturn('');
//
//        $result = $this->xmlService->validateUrlAndSaveXml($url);
//
//        $this->assertSame('', $result);
//        $this->assertCount(0, $this->feedRepository->savedFeeds);
//    }
//
//    public function test_getXml_returns_simplexmlelement_on_success(): void
//    {
/*        $xmlContent = '<?xml version="1.0" encoding="UTF-8"?><root><item>test</item></root>';*/
//        $this->feedRepository->setLatest(new Feed(time(), 'example.com', $xmlContent));
//
//        $xml = $this->xmlService->getXml();
//
//        $this->assertInstanceOf( SimpleXMLElement::class, $xml);
//        $this->assertSame('test', (string)$xml->item);
//    }

//    public function test_getUrl_returns_option_value(): void
//    {
//        $this->optionRepository->method('getOption')
//            ->with(XmlService::URL_SETTING_OPTION)
//            ->willReturn('https://example.com');
//
//        $this->assertSame('https://example.com', $this->xmlService->getUrl());
//    }
//
//    public function test_getUrl_throws_exception_if_option_empty(): void
//    {
//        $this->optionRepository->method('getOption')
//            ->with(XmlService::URL_SETTING_OPTION)
//            ->willReturn('');
//
//        $this->expectException( RuntimeException::class);
//        $this->expectExceptionMessage('XML URL is not configured');
//
//        $this->xmlService->getUrl();
//    }
}
