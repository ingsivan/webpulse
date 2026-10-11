<?php

namespace App\Services\Audit;

use App\Enums\IssueCategory;
use App\Enums\IssueSeverity;
use DOMDocument;
use DOMElement;
use DOMXPath;

/**
 * Reads an HTML document and reports SEO problems, links and images.
 * It makes no network requests.
 */
class PageAnalyzer
{
    private const TITLE_MAX_LENGTH = 60;

    private const DESCRIPTION_MIN_LENGTH = 50;

    private const DESCRIPTION_MAX_LENGTH = 160;

    public function analyze(string $html, string $pageUrl): PageAnalysis
    {
        $xpath = $this->load($html);
        $baseUrl = $this->baseUrl($xpath, $pageUrl);

        $findings = [
            ...$this->checkTitle($xpath, $pageUrl),
            ...$this->checkDescription($xpath, $pageUrl),
            ...$this->checkHeadings($xpath, $pageUrl),
            ...$this->checkLanguage($xpath, $pageUrl),
            ...$this->checkSocialTags($xpath, $pageUrl),
        ];

        [$images, $imageFindings] = $this->collectImages($xpath, $baseUrl, $pageUrl);

        return new PageAnalysis(
            findings: [...$findings, ...$imageFindings],
            links: $this->collectLinks($xpath, $baseUrl),
            images: $images,
        );
    }

    private function load(string $html): DOMXPath
    {
        $document = new DOMDocument;
        $previous = libxml_use_internal_errors(true);
        $document->loadHTML('<?xml encoding="UTF-8">'.$html, LIBXML_NONET | LIBXML_NOWARNING | LIBXML_NOERROR);
        libxml_clear_errors();
        libxml_use_internal_errors($previous);

        return new DOMXPath($document);
    }

    private function baseUrl(DOMXPath $xpath, string $pageUrl): string
    {
        $base = $this->attribute($xpath, '//head/base[@href]', 'href');

        return $base === null ? $pageUrl : UrlResolver::resolve($pageUrl, $base);
    }

    /**
     * @return list<AuditFinding>
     */
    private function checkTitle(DOMXPath $xpath, string $pageUrl): array
    {
        $title = trim((string) $xpath->evaluate('string(//title)'));

        if ($title === '') {
            return [$this->seo(IssueSeverity::Error, 'missing_title', 'La página no tiene etiqueta <title>.', $pageUrl)];
        }

        if (mb_strlen($title) > self::TITLE_MAX_LENGTH) {
            return [$this->seo(
                IssueSeverity::Warning,
                'long_title',
                'El título tiene más de '.self::TITLE_MAX_LENGTH.' caracteres y Google lo puede cortar.',
                $pageUrl,
                ['length' => mb_strlen($title)],
            )];
        }

        return [];
    }

    /**
     * @return list<AuditFinding>
     */
    private function checkDescription(DOMXPath $xpath, string $pageUrl): array
    {
        $description = trim((string) $this->attribute($xpath, '//meta[translate(@name, "DESCRIPTON", "descripton")="description"]', 'content'));
        $length = mb_strlen($description);

        if ($length === 0) {
            return [$this->seo(IssueSeverity::Error, 'missing_meta_description', 'La página no tiene meta description.', $pageUrl)];
        }

        if ($length < self::DESCRIPTION_MIN_LENGTH || $length > self::DESCRIPTION_MAX_LENGTH) {
            return [$this->seo(
                IssueSeverity::Warning,
                'meta_description_length',
                'La meta description debería tener entre '.self::DESCRIPTION_MIN_LENGTH.' y '.self::DESCRIPTION_MAX_LENGTH.' caracteres.',
                $pageUrl,
                ['length' => $length],
            )];
        }

        return [];
    }

    /**
     * @return list<AuditFinding>
     */
    private function checkHeadings(DOMXPath $xpath, string $pageUrl): array
    {
        $count = $xpath->query('//h1')->length;

        if ($count === 0) {
            return [$this->seo(IssueSeverity::Error, 'missing_h1', 'La página no tiene un encabezado <h1>.', $pageUrl)];
        }

        if ($count > 1) {
            return [$this->seo(IssueSeverity::Warning, 'multiple_h1', "La página tiene {$count} encabezados <h1>; lo ideal es uno.", $pageUrl, ['count' => $count])];
        }

        return [];
    }

    /**
     * @return list<AuditFinding>
     */
    private function checkLanguage(DOMXPath $xpath, string $pageUrl): array
    {
        if (trim((string) $this->attribute($xpath, '/html', 'lang')) === '') {
            return [$this->seo(IssueSeverity::Warning, 'missing_lang', 'La etiqueta <html> no declara el idioma (atributo lang).', $pageUrl)];
        }

        return [];
    }

    /**
     * @return list<AuditFinding>
     */
    private function checkSocialTags(DOMXPath $xpath, string $pageUrl): array
    {
        $findings = [];

        if ($this->attribute($xpath, '//meta[@property="og:title"]', 'content') === null) {
            $findings[] = $this->seo(IssueSeverity::Info, 'missing_og_title', 'Falta la etiqueta Open Graph og:title para compartir en redes.', $pageUrl);
        }

        if ($this->attribute($xpath, '//link[@rel="canonical"]', 'href') === null) {
            $findings[] = $this->seo(IssueSeverity::Info, 'missing_canonical', 'Falta la URL canónica (<link rel="canonical">).', $pageUrl);
        }

        return $findings;
    }

    /**
     * @return list<string>
     */
    private function collectLinks(DOMXPath $xpath, string $baseUrl): array
    {
        $links = [];

        foreach ($xpath->query('//a[@href]') as $anchor) {
            /** @var DOMElement $anchor */
            $url = $this->absoluteHttpUrl($baseUrl, $anchor->getAttribute('href'));

            if ($url !== null) {
                $links[$url] = true;
            }
        }

        return array_keys($links);
    }

    /**
     * @return array{0: list<string>, 1: list<AuditFinding>}
     */
    private function collectImages(DOMXPath $xpath, string $baseUrl, string $pageUrl): array
    {
        $images = [];
        $missingAlt = [];

        foreach ($xpath->query('//img[@src]') as $image) {
            /** @var DOMElement $image */
            $url = $this->absoluteHttpUrl($baseUrl, $image->getAttribute('src'));

            if ($url === null) {
                continue;
            }

            $images[$url] = true;

            if (! $image->hasAttribute('alt')) {
                $missingAlt[] = $url;
            }
        }

        $findings = $missingAlt === [] ? [] : [$this->seo(
            IssueSeverity::Warning,
            'images_missing_alt',
            count($missingAlt).' imagen(es) no tienen texto alternativo (alt).',
            $pageUrl,
            ['images' => array_slice(array_values(array_unique($missingAlt)), 0, 20)],
        )];

        return [array_keys($images), $findings];
    }

    private function absoluteHttpUrl(string $baseUrl, string $reference): ?string
    {
        $reference = trim($reference);

        if ($reference === '' || str_starts_with($reference, '#')) {
            return null;
        }

        $url = UrlResolver::resolve($baseUrl, $reference);

        if (! preg_match('#^https?://#i', $url)) {
            return null;
        }

        return strtok($url, '#');
    }

    private function attribute(DOMXPath $xpath, string $query, string $attribute): ?string
    {
        $node = $xpath->query($query)->item(0);

        return $node instanceof DOMElement && $node->hasAttribute($attribute)
            ? $node->getAttribute($attribute)
            : null;
    }

    /**
     * @param  array<string, mixed>|null  $details
     */
    private function seo(IssueSeverity $severity, string $code, string $message, string $url, ?array $details = null): AuditFinding
    {
        return new AuditFinding(IssueCategory::Seo, $severity, $code, $message, $url, $details);
    }
}
