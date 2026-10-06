<?php

namespace App\Http\Controllers;

use App\Services\NewsRoundup;
use Illuminate\Http\Request;

/**
 * The farm news roundup's cron address (2026-10-07):
 *
 *   GET /cron/news-roundup?key=...            write one if it is time
 *   GET /cron/news-roundup?key=...&force=1    write one now (the mother app's "Write one now")
 *
 * The key is the site settings shelf's news.cron_key (the mother app shows
 * the whole address). Safe to call as often as a cron likes: a roundup is
 * written only every few days (news.every_days) and only when there are
 * new stories; every call is logged in as_news_runs either way.
 *
 * The writing takes a minute or two, so the answer goes back first and the
 * work goes on after it (fastcgi_finish_request), as Write with Anee does.
 */
class NewsCronController extends Controller
{
    public function run(Request $request, NewsRoundup $news)
    {
        $key = NewsRoundup::cronKey();
        if ($key === '' || ! hash_equals($key, (string) $request->query('key', ''))) {
            return response()->json(['ok' => false, 'message' => 'Not allowed.'], 403);
        }
        $trigger = in_array($request->query('by'), ['mother', 'cron'], true) ? $request->query('by') : 'cron';
        [$runId, $why] = $news->open($trigger, $request->boolean('force'));
        if (! $runId) {
            return response()->json(['ok' => true, 'status' => 'skipped', 'reason' => $why]);
        }

        if (function_exists('fastcgi_finish_request') && ! $request->boolean('wait')) {
            ignore_user_abort(true);
            @set_time_limit(0);
            response()->json(['ok' => true, 'status' => 'started', 'run' => $runId,
                'message' => 'Reading the feeds. A new roundup appears under /blog in a minute or two when there are new stories.'])->send();
            fastcgi_finish_request();
            $news->work($runId);
            exit;
        }
        @set_time_limit(600);

        return response()->json(['ok' => true, 'run' => $runId] + $news->work($runId));
    }
}
