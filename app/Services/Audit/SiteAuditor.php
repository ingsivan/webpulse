<?php

namespace App\Services\Audit;

use App\Enums\AuditStatus;
use App\Enums\IssueCategory;
use App\Enums\IssueSeverity;
use App\Exceptions\UnsafeUrlException;
use App\Models\Audit;
use App\Models\Site;
use Illuminate\Http\Client\ConnectionException;

/**
 * Runs a full audit of a site's start page and stores the result:
 * load time and status, SEO tags, broken links and heavy images.
 */
class SiteAuditor
{
    public function __construct(
        private SafeHttpClient $http,
        private PageAnalyzer $analyzer,
        private ScoreCalculator $scores,
    ) {}

    /**
     * Creates a pending audit and runs it right away.
     */
    public function audit(Site $site): Audit
    {
        return $this->run($this->start($site));
    }

    /**
     * Creates a pending audit that a queued job will run later.
     */
    public function start(Site $site): Audit
    {
        return $site->audits()->create(['status' => AuditStatus::Pending]);
    }

    public function run(Audit $audit): Audit
    {
        $site = $audit->site;

        $audit->update([
            'status' => AuditStatus::Running,
            'progress' => 5,
            'started_at' => now(),
        ]);

        try {
            $page = $this->http->get($site->url);
        } catch (UnsafeUrlException|ConnectionException $exception) {
            return $this->fail($audit, $exception->getMessage());
        }

        $audit->update(['progress' => 20]);

        $findings = $this->checkPerformance($page);

        if ($page->response->successful()) {
            $analysis = $this->analyzer->analyze($page->response->body(), $page->finalUrl);

            $findings = [
                ...$findings,
                ...$analysis->findings,
                ...$this->checkLinks($audit, $analysis->links),
                ...$this->checkImages($audit, $analysis->images),
            ];
        } else {
            $findings[] = new AuditFinding(
                IssueCategory::Performance,
                IssueSeverity::Error,
                'page_unavailable',
                "La página respondió con el código HTTP {$page->response->status()}.",
                $page->finalUrl,
            );
        }

        $audit->issues()->createMany(array_map(fn (AuditFinding $finding) => $finding->toArray(), $findings));

        $audit->update([
            ...$this->scores->calculate($findings),
            'status' => AuditStatus::Completed,
            'progress' => 100,
            'http_status' => $page->response->status(),
            'load_time_ms' => $page->durationMs,
            'pages_crawled' => 1,
            'finished_at' => now(),
        ]);

        $site->update(['last_checked_at' => now()]);

        return $audit;
    }

    /**
     * @return list<AuditFinding>
     */
    private function checkPerformance(FetchResult $page): array
    {
        $findings = [];

        if ($page->durationMs >= config('webpulse.very_slow_page_ms')) {
            $findings[] = $this->performance(IssueSeverity::Error, 'very_slow_page', "La página tardó {$page->durationMs} ms en responder.", $page->finalUrl, ['load_time_ms' => $page->durationMs]);
        } elseif ($page->durationMs >= config('webpulse.slow_page_ms')) {
            $findings[] = $this->performance(IssueSeverity::Warning, 'slow_page', "La página tardó {$page->durationMs} ms en responder.", $page->finalUrl, ['load_time_ms' => $page->durationMs]);
        }

        if ($page->redirects > 1) {
            $findings[] = $this->performance(IssueSeverity::Warning, 'redirect_chain', "La página pasa por {$page->redirects} redirecciones antes de cargar.", $page->finalUrl, ['redirects' => $page->redirects]);
        }

        $htmlBytes = strlen($page->response->body());

        if ($htmlBytes > config('webpulse.heavy_html_bytes')) {
            $findings[] = $this->performance(IssueSeverity::Warning, 'heavy_html', 'El HTML pesa '.$this->kilobytes($htmlBytes).' KB.', $page->finalUrl, ['bytes' => $htmlBytes]);
        }

        return $findings;
    }

    /**
     * @param  list<string>  $links
     * @return list<AuditFinding>
     */
    private function checkLinks(Audit $audit, array $links): array
    {
        $findings = [];
        $links = array_slice($links, 0, config('webpulse.max_links_checked'));

        foreach ($links as $index => $link) {
            $this->reportProgress($audit, 20, 70, $index, count($links));

            try {
                $status = $this->http->head($link)->response->status();
            } catch (UnsafeUrlException|ConnectionException) {
                $findings[] = new AuditFinding(IssueCategory::Links, IssueSeverity::Warning, 'unreachable_link', 'No se pudo abrir el enlace.', $link);

                continue;
            }

            if ($status >= 400) {
                $findings[] = new AuditFinding(IssueCategory::Links, IssueSeverity::Error, 'broken_link', "Enlace roto (HTTP {$status}).", $link, ['status' => $status]);
            }
        }

        return $findings;
    }

    /**
     * @param  list<string>  $images
     * @return list<AuditFinding>
     */
    private function checkImages(Audit $audit, array $images): array
    {
        $findings = [];
        $images = array_slice($images, 0, config('webpulse.max_images_checked'));

        foreach ($images as $index => $image) {
            $this->reportProgress($audit, 70, 95, $index, count($images));

            try {
                $response = $this->http->head($image)->response;
            } catch (UnsafeUrlException|ConnectionException) {
                $findings[] = new AuditFinding(IssueCategory::Links, IssueSeverity::Warning, 'unreachable_image', 'No se pudo cargar la imagen.', $image);

                continue;
            }

            if ($response->status() >= 400) {
                $findings[] = new AuditFinding(IssueCategory::Links, IssueSeverity::Error, 'broken_image', "Imagen rota (HTTP {$response->status()}).", $image, ['status' => $response->status()]);

                continue;
            }

            $bytes = (int) $response->header('Content-Length');

            if ($bytes > config('webpulse.heavy_image_bytes')) {
                $findings[] = $this->performance(IssueSeverity::Warning, 'heavy_image', 'La imagen pesa '.$this->kilobytes($bytes).' KB.', $image, ['bytes' => $bytes]);
            }
        }

        return $findings;
    }

    /**
     * Moves the progress bar between two percentages as items are checked.
     */
    private function reportProgress(Audit $audit, int $from, int $to, int $done, int $total): void
    {
        $audit->update(['progress' => $from + (int) floor(($to - $from) * $done / max(1, $total))]);
    }

    private function fail(Audit $audit, string $reason): Audit
    {
        $audit->update([
            'status' => AuditStatus::Failed,
            'progress' => 100,
            'error_message' => $reason,
            'finished_at' => now(),
        ]);

        return $audit;
    }

    /**
     * @param  array<string, mixed>|null  $details
     */
    private function performance(IssueSeverity $severity, string $code, string $message, string $url, ?array $details = null): AuditFinding
    {
        return new AuditFinding(IssueCategory::Performance, $severity, $code, $message, $url, $details);
    }

    private function kilobytes(int $bytes): int
    {
        return (int) round($bytes / 1024);
    }
}
