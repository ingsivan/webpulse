<?php

namespace App\Http\Controllers;

use App\Enums\AuditStatus;
use App\Models\Audit;
use Illuminate\Contracts\View\View;

class HomeController extends Controller
{
    public function __invoke(): View
    {
        $recentAudits = Audit::query()
            ->where('status', AuditStatus::Completed)
            ->whereHas('site', fn ($query) => $query->whereNull('user_id'))
            ->with('site')
            ->latest('id')
            ->limit(30)
            ->get()
            ->unique('site_id')
            ->take(6);

        return view('home', ['recentAudits' => $recentAudits]);
    }
}
