<?php

namespace Tests\Unit;

use App\Services\Audit\AuditFinding;
use App\Services\Audit\PageAnalyzer;
use App\Services\Audit\UrlResolver;
use PHPUnit\Framework\Attributes\DataProvider;
use PHPUnit\Framework\TestCase;

class PageAnalyzerTest extends TestCase
{
    private const PAGE_URL = 'https://example.com/blog/post';

    public function test_a_well_built_page_has_no_findings(): void
    {
        $analysis = (new PageAnalyzer)->analyze($this->page(), self::PAGE_URL);

        $this->assertSame([], $analysis->findings);
    }

    public function test_reports_missing_seo_tags(): void
    {
        $html = '<html><head></head><body><p>Hola</p><img src="/a.png"></body></html>';

        $analysis = (new PageAnalyzer)->analyze($html, self::PAGE_URL);

        $this->assertSame(
            ['missing_title', 'missing_meta_description', 'missing_h1', 'missing_lang', 'missing_og_title', 'missing_canonical', 'images_missing_alt'],
            $this->codes($analysis->findings),
        );
    }

    public function test_reports_long_title_short_description_and_multiple_h1(): void
    {
        $html = $this->page(
            title: str_repeat('a', 61),
            description: 'Muy corta',
            body: '<h1>Uno</h1><h1>Dos</h1>',
        );

        $analysis = (new PageAnalyzer)->analyze($html, self::PAGE_URL);

        $this->assertSame(['long_title', 'meta_description_length', 'multiple_h1'], $this->codes($analysis->findings));
        $this->assertSame(['length' => 61], $analysis->findings[0]->details);
        $this->assertSame(['count' => 2], $analysis->findings[2]->details);
    }

    public function test_collects_unique_absolute_links_and_images(): void
    {
        $html = $this->page(body: <<<'HTML'
            <h1>Links</h1>
            <a href="/about">About</a>
            <a href="/about#team">About again</a>
            <a href="../contact">Contact</a>
            <a href="https://other.org/page">External</a>
            <a href="#top">Top</a>
            <a href="mailto:hi@example.com">Mail</a>
            <a href="javascript:void(0)">JS</a>
            <img src="img/photo.jpg" alt="Foto">
            <img src="data:image/png;base64,AAAA" alt="">
            HTML);

        $analysis = (new PageAnalyzer)->analyze($html, self::PAGE_URL);

        $this->assertSame(
            ['https://example.com/about', 'https://example.com/contact', 'https://other.org/page'],
            $analysis->links,
        );
        $this->assertSame(['https://example.com/blog/img/photo.jpg'], $analysis->images);
    }

    public function test_respects_the_base_element_for_relative_urls(): void
    {
        $html = '<html lang="es"><head><base href="https://cdn.example.com/assets/"></head><body><img src="logo.png" alt="Logo"></body></html>';

        $analysis = (new PageAnalyzer)->analyze($html, self::PAGE_URL);

        $this->assertSame(['https://cdn.example.com/assets/logo.png'], $analysis->images);
    }

    #[DataProvider('relativeUrls')]
    public function test_resolves_relative_urls(string $reference, string $expected): void
    {
        $this->assertSame($expected, UrlResolver::resolve('https://example.com:8443/a/b/c?x=1', $reference));
    }

    /**
     * @return array<string, array{string, string}>
     */
    public static function relativeUrls(): array
    {
        return [
            'absolute' => ['http://other.org/x', 'http://other.org/x'],
            'scheme relative' => ['//cdn.org/x.js', 'https://cdn.org/x.js'],
            'root relative' => ['/d', 'https://example.com:8443/d'],
            'sibling' => ['d', 'https://example.com:8443/a/b/d'],
            'parent' => ['../d', 'https://example.com:8443/a/d'],
            'above root' => ['../../../../d', 'https://example.com:8443/d'],
            'query only' => ['?y=2', 'https://example.com:8443/a/b/c?y=2'],
            'current dir' => ['./', 'https://example.com:8443/a/b/'],
        ];
    }

    /**
     * @param  list<AuditFinding>  $findings
     * @return list<string>
     */
    private function codes(array $findings): array
    {
        return array_map(fn (AuditFinding $finding) => $finding->code, $findings);
    }

    private function page(
        string $title = 'Mi blog',
        string $description = 'Un blog sobre desarrollo web con artículos prácticos y ejemplos reales.',
        string $body = '<h1>Mi blog</h1>',
    ): string {
        return <<<HTML
            <!doctype html>
            <html lang="es">
            <head>
                <title>{$title}</title>
                <meta name="description" content="{$description}">
                <meta property="og:title" content="{$title}">
                <link rel="canonical" href="https://example.com/blog/post">
            </head>
            <body>{$body}</body>
            </html>
            HTML;
    }
}
