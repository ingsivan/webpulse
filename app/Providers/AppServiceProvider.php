<?php

namespace App\Providers;

use Illuminate\Cache\RateLimiting\Limit;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\RateLimiter;
use Illuminate\Support\ServiceProvider;

class AppServiceProvider extends ServiceProvider
{
    /**
     * Register any application services.
     */
    public function register(): void
    {
        //
    }

    /**
     * Bootstrap any application services.
     */
    public function boot(): void
    {
        $this->configureRateLimiting();
    }

    /**
     * Audits make outbound requests, so each visitor gets a small budget.
     */
    private function configureRateLimiting(): void
    {
        RateLimiter::for('audits', function (Request $request) {
            $key = $request->user()?->id ?? $request->ip();
            $tooMany = fn () => back()
                ->withInput()
                ->withErrors(['url' => 'Hiciste muchas auditorías seguidas. Espera un momento e inténtalo de nuevo.']);

            return [
                Limit::perMinute(3)->by("minute:{$key}")->response($tooMany),
                Limit::perDay(30)->by("day:{$key}")->response($tooMany),
            ];
        });

        RateLimiter::for('auth', function (Request $request) {
            return Limit::perMinute(5)->by(strtolower((string) $request->input('email')).'|'.$request->ip());
        });
    }
}
