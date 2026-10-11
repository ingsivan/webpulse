<?php

namespace Tests\Unit;

use App\Support\UrlNormalizer;
use PHPUnit\Framework\Attributes\TestWith;
use PHPUnit\Framework\TestCase;

class UrlNormalizerTest extends TestCase
{
    #[TestWith(['example.com', 'https://example.com/'])]
    #[TestWith(['  HTTP://WWW.Example.com  ', 'http://www.example.com/'])]
    #[TestWith(['https://example.com:8443/Blog?page=2#top', 'https://example.com:8443/Blog?page=2'])]
    public function test_normalizes_urls(string $input, string $expected): void
    {
        $this->assertSame($expected, UrlNormalizer::normalize($input));
    }

    public function test_display_name_drops_www(): void
    {
        $this->assertSame('example.com', UrlNormalizer::displayName('https://www.example.com/'));
    }
}
