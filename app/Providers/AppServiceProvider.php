<?php

namespace App\Providers;

use Illuminate\Support\Facades\URL;
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
        // The public site's routes carry a {face} segment (/ph, /en). Its
        // per-request value is set by ResolveRegion; this is the floor for
        // URLs built where there is no request — mail, artisan, queued jobs.
        \Illuminate\Support\Facades\URL::defaults(['face' => 'ph']);
        // The Philippine face lives at the root (2026-09-30): a public page
        // built for 'ph' (/ph, /ph/pricing) is written without the segment
        // (/, /pricing). One rule here keeps every route('pricing') call in
        // the app as it was; 'en' keeps its /en. See routes/web.php.
        \Illuminate\Support\Facades\URL::formatPathUsing(
            fn (string $path) => preg_match('#^/ph(?=/|$)#', $path) ? (substr($path, 3) ?: '/') : $path
        );
        // Safety net for the generated URL scheme. Trusting the proxy (see
        // bootstrap/app.php) already makes request-time URLs https behind
        // Railway's edge; this additionally covers URLs built where there is no
        // request to read a scheme from — queued jobs, mail, artisan commands.
        if (str_starts_with((string) config('app.url'), 'https://')) {
            URL::forceScheme('https');
        }
    }
}
