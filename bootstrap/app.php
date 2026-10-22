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
            'admin.panel' => \App\Http\Middleware\AdminPanelOnly::class,
        ]);
        // Search engines: noindex on everything behind the login, and on the
        // public site until the mother app's switch opens it. Global, and so
        // outside the router: the guest redirect the auth middleware throws
        // (which runs ahead of anything in the web group) passes through it.
        // www.anee.io to anee.io (after the proxies are trusted, so the scheme is right).
        $middleware->append(\App\Http\Middleware\DropWww::class);
        $middleware->append(\App\Http\Middleware\RobotsHeaders::class);
        // Facebook's link readers, noted as they come (see the class).
        $middleware->append(\App\Http\Middleware\NoteLinkReaders::class);
        $middleware->web(append: [
            // Which country this request is in, before anything renders or
            // any route is generated (the public site's {face} default).
            \App\Http\Middleware\ResolveRegion::class,
            // The international version is closed for maintenance for now
            // (config('app.international_open')): its visits get that page.
            \App\Http\Middleware\PauseInternational::class,
            // One device at a time; then "keep me logged in" slides another
            // ten days from this visit; then the last-seen (online) stamp.
            // The session is no longer tied to the network it came from: a
            // phone walking from wifi to mobile data lost its session every
            // time, and the logout took the remember cookie with it.
            \App\Http\Middleware\EnforceSingleSession::class,
            \App\Http\Middleware\RefreshRememberCookie::class,
            \App\Http\Middleware\UpdateLastSeen::class,
            // Whether the worker in front of us may be at this door at all.
            // Here rather than in ninety controller actions: that shape has
            // already cost this app twelve open endpoints once.
            \App\Http\Middleware\WorkerModuleAccess::class,
            // And whether the farm's PLAN has the module at all: Workers and
            // the Inventory are the Solo Farmer plan's. Same shape, same reason.
            \App\Http\Middleware\TierModuleAccess::class,
            // And the field helpers pack on the free plan (2026-10-14): by path,
            // so a catalogue route added later is covered too.
            \App\Http\Middleware\FieldPackGate::class,
            // Watches the /app/community hallway for suspended members. By
            // path, not per-route: a door added next month is still covered.
            \App\Http\Middleware\CommunityOpen::class,
            // The Logs tab's pen: one diary line per successful write that
            // resolved a schedule. After everything, so it sees the outcome.
            \App\Http\Middleware\RecordsScheduleActivity::class,
            // The public site's share and search tags, never missing (2026-10-07).
            \App\Http\Middleware\PublicSeoTags::class,
        ]);
        $middleware->redirectGuestsTo(fn () => route('login'));
        $middleware->redirectUsersTo(fn () => route('app.dashboard'));
    })
    ->withSchedule(function (Schedule $schedule): void {
        $schedule->command('anisystem:check-subscriptions')->dailyAt('06:00');
        // Hourly, because each schedule picks its own send time; the command
        // itself decides whose hour it is and never sends the same day twice.
        $schedule->command('digests:send')->hourly();
        // The farm news roundup asks every morning; it writes only every few
        // days and only when the feeds have new stories (App\Services\NewsRoundup).
        // In the background, so a slow AI answer never holds the next minute.
        // At 5, an hour before the newsletter's 6:00 send (2026-10-17, the owner),
        // so the day's news email is made and scored before the send.
        $schedule->command('news:roundup')->dailyAt('05:00')->timezone('Asia/Manila')
            ->withoutOverlapping(180)->runInBackground();
        // "Write one now, from the scheduler": news.run_now on the settings
        // shelf makes the next minute's pass write one (forced) and clears it.
        $schedule->command('news:roundup --force')->everyMinute()
            ->when(fn () => \App\Models\AsSiteSetting::yes('news.run_now'))
            ->before(fn () => \App\Models\AsSiteSetting::put('news.run_now', '0'))
            ->withoutOverlapping(180)->runInBackground();
        // The Stash's PDFs come home a few at a time (2026-10-07); with
        // nothing pending the command ends at once.
        $schedule->command('stash:fetch')->everyFiveMinutes()->withoutOverlapping(60)->runInBackground();
        // The climate shelf (2026-10-08): ENSO once a day, and the weather
        // history a few cells an hour (every province first, then the stale
        // ones), so NPK Plus reads the season from its own tables.
        $schedule->command('climate:collect --enso')->dailyAt('08:15')->timezone('Asia/Manila')->withoutOverlapping(30)->runInBackground();
        $schedule->command('climate:collect --limit=6')->hourlyAt(23)->withoutOverlapping(50)->runInBackground();
        // The nutrient book (2026-10-18): written again only when NPK Plus's
        // need model changed (a one row check otherwise), and the kept
        // research past its time removed.
        $schedule->command('nutrients:build --if-stale')->hourlyAt(41)->withoutOverlapping(30)->runInBackground();
        // The hazard book (2026-10-18): the running year's storms, weather,
        // reported disasters and quakes, twice a month, so a town's record is
        // never older than the risk record's 45 days and no analysis searches.
        $schedule->command('hazards:refresh')->monthlyOn(6, '03:20')->timezone('Asia/Manila')->withoutOverlapping(180)->runInBackground();
        $schedule->command('hazards:refresh')->monthlyOn(21, '03:20')->timezone('Asia/Manila')->withoutOverlapping(180)->runInBackground();
        // NPK Plus's Philippine products (2026-10-12): the FPA's registered
        // fertilizer list once a month, and at first while only the hand
        // checked products are in.
        // Satellite Weather's weekly renewals (2026-10-13), a little before each pass ends.
        $schedule->command('sky:renew')->everyTenMinutes()->withoutOverlapping(10)->runInBackground();
        // Satellite Weather's batch (2026-10-12): the national grid once per 3 hour block, only while a pass runs.
        $schedule->command('sky:batch')->cron('2 0,3,6,9,12,15,18,21 * * *')->timezone('UTC')->withoutOverlapping(20)->runInBackground();
        // Anee Auto's morning (2026-10-12): re-plan, brief and mail each running season.
        $schedule->command('auto:daily')->dailyAt('05:00')->timezone('Asia/Manila')->withoutOverlapping(60)->runInBackground();
        // Global Lots (2026-10-16): the bell asks to check a lot whose answers are six months old.
        $schedule->command('lots:remind')->dailyAt('08:30')->timezone('Asia/Manila')->withoutOverlapping(30)->runInBackground();
        // The newsletter (2026-10-17): lists kept in step, timed emails at their
        // hour, and the day's campaigns once, after the send time (6:00 in the
        // morning, Philippine time, the owner) and the morning's news roundup
        // (App\Support\Newsletter\Planner). The 6:00 pass of this ten minute
        // clock is the one that sends.
        $schedule->command('newsletter:tick')->everyTenMinutes()->withoutOverlapping(30)->runInBackground();
        $schedule->command('fertilizers:fpa-sync')->monthlyOn(3, '04:30')->timezone('Asia/Manila')->withoutOverlapping(60)->runInBackground();
        $schedule->command('fertilizers:fpa-sync')->dailyAt('04:45')->timezone('Asia/Manila')->withoutOverlapping(60)->runInBackground()
            ->when(fn () => \Illuminate\Support\Facades\Schema::hasTable('as_ph_fertilizers') && ! \Illuminate\Support\Facades\DB::table('as_ph_fertilizers')->where('curated', 0)->exists());
        // The global Trash (2026-10-23): the deletes no model event saw filed
        // every ten minutes, and what has been in it 30 days deleted for good
        // once a night (App\Support\Trash).
        $schedule->command('trash:tick')->everyTenMinutes()->withoutOverlapping(15)->runInBackground();
        $schedule->command('trash:tick --purge')->dailyAt('02:40')->timezone('Asia/Manila')->withoutOverlapping(60)->runInBackground();
        // Proof the scheduler runs (Laravel Cloud's toggle, 2026-10-07):
        // /deploy-check shows the last tick, written to the shared settings.
        $schedule->call(fn () => \App\Models\AsSiteSetting::put('scheduler.tick', now()->toIso8601String()))
            ->name('scheduler-heartbeat')->everyFiveMinutes();
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
