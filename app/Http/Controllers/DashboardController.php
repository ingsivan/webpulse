<?php

namespace App\Http\Controllers;

use Illuminate\Contracts\View\View;
use Illuminate\Http\Request;

class DashboardController extends Controller
{
    public function __invoke(Request $request): View
    {
        $sites = $request->user()->sites()
            ->with('latestAudit')
            ->orderBy('name')
            ->get();

        return view('dashboard', [
            'sites' => $sites,
            'intervals' => [15 => 'Cada 15 minutos', 30 => 'Cada 30 minutos', 60 => 'Cada hora', 360 => 'Cada 6 horas', 1440 => 'Una vez al día'],
        ]);
    }
}
