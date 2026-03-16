<?php

declare(strict_types=1);

namespace SineFine\PromImport\Tests\Dto;

use PHPUnit\Framework\TestCase;
use SineFine\PromImport\Application\Import\Dto\FeedDto;

class FeedDtoTest extends TestCase
{
    public function test_create_extracts_domain_from_url(): void
    {
        $url = 'https://example.com/path/to/feed.xml';
        $content = 'some content';
        $dto = FeedDto::create($url, $content);

        $this->assertSame('example.com', $dto->domain);
        $this->assertSame($content, $dto->content);
        $this->assertGreaterThan(0, $dto->timestamp);
    }

    public function test_create_handles_invalid_url(): void
    {
        $url = 'not-a-url';
        $dto = FeedDto::create($url, '');

        // Functions::parseUrl('not-a-url', PHP_URL_HOST) likely returns null or false, 
        // which cast to string becomes an empty string.
        $this->assertSame('', $dto->domain);
    }
}
