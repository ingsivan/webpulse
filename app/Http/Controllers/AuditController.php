<?php

namespace App\Http\Controllers;

use App\Enums\IssueSeverity;
use App\Http\Requests\StoreAuditRequest;
use App\Jobs\RunAudit;
use App\Models\Audit;
use App\Models\Site;
use App\Services\Audit\SiteAuditor;
use App\Support\UrlNormalizer;
use Illuminate\Contracts\View\View;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\RedirectResponse;
use Illuminate\Support\Facades\Gate;

class AuditController extends Controller
{
    /**
     * Audits a URL typed on the home page. These audits are public.
     */
    public function store(StoreAuditRequest $request, SiteAuditor $auditor): RedirectResponse
    {
        $url = $request->validated('url');

        $site = Site::query()->firstOrCreate(
            ['url' => $url, 'user_id' => null],
            ['name' => UrlNormalizer::displayName($url)],
        );

        $audit = $auditor->start($site);
        RunAudit::dispatch($audit);

        return to_route('audits.show', $audit);
    }

    /**
     * Audits one of the signed-in user's sites on demand.
     */
    public function storeForSite(Site $site, SiteAuditor $auditor): RedirectResponse
    {
        Gate::authorize('update', $site);

        $audit = $auditor->start($site);
        RunAudit::dispatch($audit);

        return to_route('audits.show', $audit);
    }

    public function show(Audit $audit): View
    {
        Gate::authorize('view', $audit);

        $audit->load('site', 'issues');

        $issuesByCategory = $audit->issues
            ->sortBy(fn ($issue) => [array_search($issue->severity, IssueSeverity::cases(), true) * -1, $issue->code])
            ->groupBy(fn ($issue) => $issue->category->value);

        return view('audits.show', [
            'audit' => $audit,
            'issuesByCategory' => $issuesByCategory,
        ]);
    }

    /**
     * Polled by the audit page while the audit runs.
     */
    public function status(Audit $audit): JsonResponse
    {
        Gate::authorize('view', $audit);

        return response()->json([
            'status' => $audit->status->value,
            'progress' => $audit->progress,
            'finished' => $audit->isFinished(),
        ]);
    }

    /**
     * A print-ready report; the browser's "Save as PDF" turns it into a PDF.
     */
    public function report(Audit $audit): View
    {
        Gate::authorize('view', $audit);

        $audit->load('site', 'issues');

        return view('audits.report', [
            'audit' => $audit,
            'issuesByCategory' => $audit->issues->groupBy(fn ($issue) => $issue->category->value),
        ]);
    }
}
