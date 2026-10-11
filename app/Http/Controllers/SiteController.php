<?php

namespace App\Http\Controllers;

use App\Http\Requests\StoreSiteRequest;
use App\Http\Requests\UpdateSiteRequest;
use App\Jobs\RunAudit;
use App\Models\Site;
use App\Services\Audit\SiteAuditor;
use App\Support\UrlNormalizer;
use Illuminate\Contracts\View\View;
use Illuminate\Http\RedirectResponse;
use Illuminate\Support\Facades\Gate;

class SiteController extends Controller
{
    /**
     * The audit history of one site, with its score over time.
     */
    public function show(Site $site): View
    {
        Gate::authorize('view', $site);

        $audits = $site->audits()->latest('id')->paginate(15);

        $history = $site->audits()
            ->whereNotNull('overall_score')
            ->latest('id')
            ->limit(30)
            ->get(['id', 'overall_score', 'created_at'])
            ->reverse()
            ->values();

        return view('sites.show', [
            'site' => $site,
            'audits' => $audits,
            'history' => $history,
        ]);
    }

    public function store(StoreSiteRequest $request, SiteAuditor $auditor): RedirectResponse
    {
        $url = $request->validated('url');

        $site = $request->user()->sites()->create([
            'url' => $url,
            'name' => $request->validated('name') ?: UrlNormalizer::displayName($url),
            'is_monitored' => true,
            'check_interval_minutes' => $request->validated('check_interval_minutes'),
        ]);

        RunAudit::dispatch($auditor->start($site));

        return to_route('dashboard')->with('status', "Agregamos {$site->name} y empezamos su primera auditoría.");
    }

    public function update(UpdateSiteRequest $request, Site $site): RedirectResponse
    {
        $site->update($request->validated());

        return to_route('dashboard')->with('status', "Guardamos los cambios de {$site->name}.");
    }

    public function destroy(Site $site): RedirectResponse
    {
        Gate::authorize('delete', $site);

        $site->delete();

        return to_route('dashboard')->with('status', "Eliminamos {$site->name}.");
    }
}
