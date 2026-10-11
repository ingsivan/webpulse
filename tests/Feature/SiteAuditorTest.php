<?php

namespace Tests\Feature;

use App\Enums\AuditStatus;
use App\Models\Site;
use App\Services\Audit\SiteAuditor;
use Illuminate\Foundation\Testing\LazilyRefreshDatabase;
use Illuminate\Http\Client\ConnectionException;
use Illuminate\Support\Facades\Http;
use Tests\TestCase;

class SiteAuditorTest extends TestCase
{
    use LazilyRefreshDatabase;

    public function test_a_healthy_site_gets_perfect_scores(): void
    {
        $this->freezeSecond();
        Http::preventStrayRequests();
        $this->fakeDns(['example.com' => ['93.184.216.34']]);
        Http::fake([
            'https://example.com/' => Http::response($this->page('<a href="/about">About</a><img src="/logo.png" alt="Logo">')),
            'https://example.com/about' => Http::response('ok'),
            'https://example.com/logo.png' => Http::response('', 200, ['Content-Length' => '20480']),
        ]);
        $site = Site::factory()->create(['url' => 'https://example.com/']);

        $audit = app(SiteAuditor::class)->audit($site);

        $this->assertSame(AuditStatus::Completed, $audit->status);
        $this->assertSame(200, $audit->http_status);
        $this->assertSame(1, $audit->pages_crawled);
        $this->assertSame([100, 100, 100, 100], [$audit->performance_score, $audit->seo_score, $audit->links_score, $audit->overall_score]);
        $this->assertCount(0, $audit->issues);
        $this->assertTrue($site->fresh()->last_checked_at->equalTo(now()));
    }

    public function test_reports_broken_links_broken_images_and_heavy_images(): void
    {
        Http::preventStrayRequests();
        $this->fakeDns(['example.com' => ['93.184.216.34'], 'gone.example.org' => ['93.184.216.35']]);
        Http::fake([
            'https://example.com/' => Http::response($this->page(<<<'HTML'
                <a href="/missing">Missing</a>
                <a href="https://gone.example.org/">Gone</a>
                <img src="/big.jpg" alt="Big">
                <img src="/404.jpg" alt="Lost">
                HTML)),
            'https://example.com/missing' => Http::response('', 404),
            'https://gone.example.org/' => Http::failedConnection(),
            'https://example.com/big.jpg' => Http::response('', 200, ['Content-Length' => (string) (800 * 1024)]),
            'https://example.com/404.jpg' => Http::response('', 404),
        ]);
        $site = Site::factory()->create(['url' => 'https://example.com/']);

        $audit = app(SiteAuditor::class)->audit($site);

        $this->assertEqualsCanonicalizing(
            ['broken_link', 'unreachable_link', 'broken_image', 'heavy_image'],
            $audit->issues->pluck('code')->all(),
        );
        $this->assertSame(['bytes' => 800 * 1024], $audit->issues->firstWhere('code', 'heavy_image')->details);
        $this->assertSame(90, $audit->performance_score);
        $this->assertSame(40, $audit->links_score);
        $this->assertSame(77, $audit->overall_score);
    }

    public function test_does_not_follow_links_to_internal_addresses(): void
    {
        Http::preventStrayRequests();
        $this->fakeDns(['example.com' => ['93.184.216.34']]);
        Http::fake([
            'https://example.com/' => Http::response($this->page('<a href="http://169.254.169.254/latest/meta-data/">x</a>')),
        ]);
        $site = Site::factory()->create(['url' => 'https://example.com/']);

        $audit = app(SiteAuditor::class)->audit($site);

        $this->assertSame(['unreachable_link'], $audit->issues->pluck('code')->all());
        Http::assertSentCount(1);
    }

    public function test_limits_how_many_links_are_checked(): void
    {
        config(['webpulse.max_links_checked' => 2]);
        Http::preventStrayRequests();
        $this->fakeDns(['example.com' => ['93.184.216.34']]);
        Http::fake([
            'https://example.com/' => Http::response($this->page('<a href="/a">a</a><a href="/b">b</a><a href="/c">c</a>')),
            'https://example.com/*' => Http::response('ok'),
        ]);
        $site = Site::factory()->create(['url' => 'https://example.com/']);

        app(SiteAuditor::class)->audit($site);

        Http::assertSentCount(3);
    }

    public function test_an_error_page_is_reported_without_analyzing_it(): void
    {
        Http::preventStrayRequests();
        $this->fakeDns(['example.com' => ['93.184.216.34']]);
        Http::fake(['https://example.com/' => Http::response('<html></html>', 503)]);
        $site = Site::factory()->create(['url' => 'https://example.com/']);

        $audit = app(SiteAuditor::class)->audit($site);

        $this->assertSame(AuditStatus::Completed, $audit->status);
        $this->assertSame(503, $audit->http_status);
        $this->assertSame(['page_unavailable'], $audit->issues->pluck('code')->all());
        $this->assertSame(75, $audit->performance_score);
    }

    public function test_marks_the_audit_failed_when_the_site_cannot_be_reached(): void
    {
        Http::preventStrayRequests();
        $this->fakeDns(['example.com' => ['93.184.216.34']]);
        Http::fake(['https://example.com/' => fn () => throw new ConnectionException('Connection refused')]);
        $site = Site::factory()->create(['url' => 'https://example.com/']);

        $audit = app(SiteAuditor::class)->audit($site);

        $this->assertSame(AuditStatus::Failed, $audit->status);
        $this->assertSame('Connection refused', $audit->error_message);
        $this->assertNull($audit->overall_score);
        $this->assertNull($site->fresh()->last_checked_at);
    }

    public function test_marks_the_audit_failed_for_a_site_on_an_internal_address(): void
    {
        Http::preventStrayRequests();
        $site = Site::factory()->create(['url' => 'http://127.0.0.1:6379/']);

        $audit = app(SiteAuditor::class)->audit($site);

        $this->assertSame(AuditStatus::Failed, $audit->status);
        $this->assertStringContainsString('dirección interna', $audit->error_message);
        Http::assertNothingSent();
    }

    private function page(string $body): string
    {
        return <<<HTML
            <!doctype html>
            <html lang="es">
            <head>
                <title>Example</title>
                <meta name="description" content="Una página de ejemplo con una descripción del largo correcto para SEO.">
                <meta property="og:title" content="Example">
                <link rel="canonical" href="https://example.com/">
            </head>
            <body><h1>Example</h1>{$body}</body>
            </html>
            HTML;
    }
}
