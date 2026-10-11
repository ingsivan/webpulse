<?php

namespace App\Services\Audit;

/**
 * What PageAnalyzer extracted from one HTML document.
 */
class PageAnalysis
{
    /**
     * @param  list<AuditFinding>  $findings
     * @param  list<string>  $links  Absolute http(s) URLs, without fragments.
     * @param  list<string>  $images  Absolute http(s) image URLs.
     */
    public function __construct(
        public readonly array $findings,
        public readonly array $links,
        public readonly array $images,
    ) {}
}
