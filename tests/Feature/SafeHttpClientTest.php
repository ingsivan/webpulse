<?php

namespace Tests\Feature;

use App\Exceptions\UnsafeUrlException;
use App\Services\Audit\SafeHttpClient;
use Illuminate\Http\Client\Request;
use Illuminate\Support\Facades\Http;
use PHPUnit\Framework\Attributes\DataProvider;
use Tests\TestCase;

class SafeHttpClientTest extends TestCase
{
    #[DataProvider('blockedUrls')]
    public function test_refuses_urls_that_point_inside_the_network(string $url): void
    {
        Http::preventStrayRequests();
        $this->fakeDns(['intranet.example.com' => ['10.0.0.5'], 'mixed.example.com' => ['93.184.216.34', '192.168.1.10']]);

        $this->expectException(UnsafeUrlException::class);

        app(SafeHttpClient::class)->get($url);
    }

    /**
     * @return array<string, array{string}>
     */
    public static function blockedUrls(): array
    {
        return [
            'loopback' => ['http://127.0.0.1/admin'],
            'private range' => ['http://192.168.0.1'],
            'cloud metadata' => ['http://169.254.169.254/latest/meta-data/'],
            'carrier-grade nat' => ['http://100.64.0.1'],
            'ipv6 loopback' => ['http://[::1]/'],
            'ipv6 unique local' => ['http://[fd00::1]/'],
            'ipv4-mapped ipv6' => ['http://[::ffff:127.0.0.1]/'],
            'domain resolving to private ip' => ['https://intranet.example.com'],
            'domain with one private ip' => ['https://mixed.example.com'],
            'unresolvable domain' => ['https://nope.invalid'],
            'file scheme' => ['file:///etc/passwd'],
            'ftp scheme' => ['ftp://example.com/file'],
        ];
    }

    public function test_follows_redirects_and_checks_every_hop(): void
    {
        Http::preventStrayRequests();
        $this->fakeDns(['example.com' => ['93.184.216.34'], 'www.example.com' => ['93.184.216.34']]);
        Http::fake([
            'http://example.com/' => Http::response('', 301, ['Location' => 'https://www.example.com/']),
            'https://www.example.com/' => Http::response('<html></html>', 200),
        ]);

        $result = app(SafeHttpClient::class)->get('http://example.com/');

        $this->assertSame(200, $result->response->status());
        $this->assertSame('https://www.example.com/', $result->finalUrl);
        $this->assertSame(1, $result->redirects);
    }

    public function test_refuses_a_redirect_to_an_internal_address(): void
    {
        Http::preventStrayRequests();
        $this->fakeDns(['example.com' => ['93.184.216.34']]);
        Http::fake([
            'https://example.com/' => Http::response('', 302, ['Location' => 'http://127.0.0.1:8080/secret']),
        ]);

        $this->expectException(UnsafeUrlException::class);

        app(SafeHttpClient::class)->get('https://example.com/');
    }

    public function test_stops_after_too_many_redirects(): void
    {
        Http::preventStrayRequests();
        $this->fakeDns(['example.com' => ['93.184.216.34']]);
        Http::fake([
            'https://example.com/*' => Http::response('', 302, ['Location' => '/loop']),
        ]);

        $this->expectExceptionMessage('Demasiadas redirecciones');

        app(SafeHttpClient::class)->get('https://example.com/start');
    }

    public function test_head_falls_back_to_get_when_the_server_rejects_head(): void
    {
        Http::preventStrayRequests();
        $this->fakeDns(['example.com' => ['93.184.216.34']]);
        Http::fake([
            'https://example.com/file' => fn (Request $request) => $request->method() === 'HEAD'
                ? Http::response('', 405)
                : Http::response('ok', 200),
        ]);

        $result = app(SafeHttpClient::class)->head('https://example.com/file');

        $this->assertSame(200, $result->response->status());
        Http::assertSentCount(2);
    }
}
