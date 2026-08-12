<?php

use Illuminate\Foundation\Application;
use Illuminate\Foundation\Configuration\Exceptions;
use Illuminate\Foundation\Configuration\Middleware;
use Illuminate\Console\Scheduling\Schedule;
use Illuminate\Http\Request;

return Application::configure(basePath: dirname(__DIR__))
    ->withRouting(
        web: __DIR__.'/../routes/web.php',
        commands: __DIR__.'/../routes/console.php',
        channels: __DIR__.'/../routes/channels.php',
        health: '/up',
    )
    ->withMiddleware(function (Middleware $middleware): void {
        // Behind a platform load balancer (Railway, Fly, Heroku, any reverse
        // proxy) TLS terminates at the edge and PHP sees a plain HTTP request.
        // Without this, every url()/route()/asset() is generated as http:// on
        // an https:// page — browsers then block the stylesheet and JS module
        // as mixed content — and $request->ip() reports the proxy, not the
        // client. The proxy address is not knowable in advance on these
        // platforms, so all proxies are trusted; the edge is the only route in.
        $middleware->trustProxies(at: '*', headers: Request::HEADER_X_FORWARDED_FOR |
            Request::HEADER_X_FORWARDED_HOST |
            Request::HEADER_X_FORWARDED_PORT |
            Request::HEADER_X_FORWARDED_PROTO |
            Request::HEADER_X_FORWARDED_PREFIX);

        $middleware->alias([
            'subscription' => \App\Http\Middleware\EnsureSubscriptionActive::class,
            'no-cache' => \App\Http\Middleware\NoCacheHeaders::class,
        ]);
        // Runs after StartSession — drops a session whose public IP changed,
        // then refreshes the member's last-seen (online) timestamp.
        $middleware->web(append: [
            \App\Http\Middleware\BindSessionToIp::class,
            \App\Http\Middleware\EnforceSingleSession::class,
            \App\Http\Middleware\UpdateLastSeen::class,
        ]);
        $middleware->redirectGuestsTo(fn () => route('login'));
        $middleware->redirectUsersTo(fn () => route('app.dashboard'));
    })
    ->withSchedule(function (Schedule $schedule): void {
        $schedule->command('anisystem:check-subscriptions')->dailyAt('06:00');
        // Hourly, because each schedule picks its own send time; the command
        // itself decides whose hour it is and never sends the same day twice.
        $schedule->command('digests:send')->hourly();
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
