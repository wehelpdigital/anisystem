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
        // Safety net for the generated URL scheme. Trusting the proxy (see
        // bootstrap/app.php) already makes request-time URLs https behind
        // Railway's edge; this additionally covers URLs built where there is no
        // request to read a scheme from — queued jobs, mail, artisan commands.
        if (str_starts_with((string) config('app.url'), 'https://')) {
            URL::forceScheme('https');
        }
    }
}
