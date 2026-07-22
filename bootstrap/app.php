<?php

use Illuminate\Foundation\Application;
use Illuminate\Foundation\Configuration\Exceptions;
use Illuminate\Foundation\Configuration\Middleware;
use Illuminate\Console\Scheduling\Schedule;

return Application::configure(basePath: dirname(__DIR__))
    ->withRouting(
        web: __DIR__.'/../routes/web.php',
        commands: __DIR__.'/../routes/console.php',
        health: '/up',
    )
    ->withMiddleware(function (Middleware $middleware): void {
        $middleware->alias([
            'subscription' => \App\Http\Middleware\EnsureSubscriptionActive::class,
            'no-cache' => \App\Http\Middleware\NoCacheHeaders::class,
        ]);
        $middleware->redirectGuestsTo(fn () => route('login'));
        $middleware->redirectUsersTo(fn () => route('app.dashboard'));
    })
    ->withSchedule(function (Schedule $schedule): void {
        $schedule->command('anisystem:check-subscriptions')->dailyAt('06:00');
    })
    ->withExceptions(function (Exceptions $exceptions): void {
        // An expired/stale CSRF token (page left open past the session
        // lifetime, back button, restored tab) should not dead-end on the raw
        // 419 page. Bounce back to the same form with a fresh token, the
        // previous input, and a plain explanation so the user can just resubmit.
        // Laravel converts TokenMismatchException into a 419 HttpException
        // before render callbacks run, so match on the status code.
        $exceptions->render(function (\Symfony\Component\HttpKernel\Exception\HttpException $e, \Illuminate\Http\Request $request) {
            if ($e->getStatusCode() !== 419) {
                return null; // let every other HTTP error render normally
            }

            $message = 'Your session expired for security. Please try again.';

            if ($request->expectsJson() || $request->ajax()) {
                return response()->json(['success' => false, 'message' => $message], 419);
            }

            return redirect()
                ->back()
                ->withInput($request->except(['password', 'password_confirmation', '_token']))
                ->with('error', $message);
        });
    })->create();
