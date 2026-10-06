<?php

namespace App\Http\Controllers;

use App\Services\PageWriter;
use App\Support\SeoKeywords;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;

/**
 * "Write with Anee" for the mother app's page builder (2026-10-01).
 *
 * The builder's server asks here, a server calling a server with the token
 * the two apps share (X-Anee-Token, mother.media_token); no session, no
 * CSRF. A page takes a minute or two to write, longer than any gateway
 * holds a request, so it is a job: start answers at once with its id and the
 * writing happens after the response (as_writer_jobs); state is asked until
 * the page is there. The page comes back as fields for the builder to load:
 * nothing is saved here, the editor saves when they are happy with it.
 */
class MotherWriterController extends Controller
{
    public function start(Request $request)
    {
        if ($refused = $this->refuse($request)) {
            return $refused;
        }
        $in = $request->validate([
            'section' => 'required|string|in:crops,pests,diseases,weeds,problems,blog,features,questions',
            'topic' => 'required|string|max:300',
            'focusKeyword' => 'nullable|string|max:120',
            'keywords' => 'nullable|array|max:20',
            'keywords.*' => 'string|max:190',
            'lang' => 'nullable|in:en,tl',
            'notes' => 'nullable|string|max:2000',
            'research' => 'nullable|boolean',
            'current' => 'nullable|array',
            'pageId' => 'nullable|integer',
            'admin' => 'nullable|string|max:80',
        ]);
        // The keywords chosen, plus the ones nearest the topic when few were.
        $keywords = array_values(array_filter((array) ($in['keywords'] ?? [])));
        if (count($keywords) < 5) {
            $keywords = array_values(array_unique(array_merge($keywords, SeoKeywords::candidates($in['topic'] . ' ' . ($in['focusKeyword'] ?? ''), 15)
                ->map(fn ($k) => $k->keyword . ' (' . number_format((int) $k->volume) . ' searches)')->all())));
        }
        $brief = [
            'section' => $in['section'],
            'topic' => $in['topic'],
            'focusKeyword' => $in['focusKeyword'] ?? null,
            'keywords' => $keywords,
            'lang' => $in['lang'] ?? 'en',
            'notes' => $in['notes'] ?? null,
            'research' => (bool) ($in['research'] ?? true),
            'current' => $in['current'] ?? null,
            'country' => 'PH',
        ];
        $id = DB::table('as_writer_jobs')->insertGetId([
            'pageId' => $in['pageId'] ?? null,
            'brief' => json_encode($brief, JSON_UNESCAPED_UNICODE),
            'status' => 'pending',
            'phase' => 'start',
            'try' => 1,
            'createdBy' => Str::limit((string) ($in['admin'] ?? ''), 78, ''),
            'created_at' => now(),
            'updated_at' => now(),
        ]);

        $work = function () use ($id, $brief) {
            $beat = fn (string $phase, int $try = 1) => DB::table('as_writer_jobs')->where('id', $id)
                ->update(['phase' => $phase, 'try' => $try, 'updated_at' => now()]);
            try {
                $done = app(PageWriter::class)->write($brief, $beat);
                \App\Support\AiUsage::record('page-writer', 0, 0, $id, \App\Models\AiSetting::current(), $done['result'], 0);
                DB::table('as_writer_jobs')->where('id', $id)->update($done['ok']
                    ? ['status' => 'ready', 'result' => json_encode($done['page'], JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES), 'updated_at' => now()]
                    : ['status' => 'failed', 'error' => Str::limit((string) $done['error'], 480, ''), 'updated_at' => now()]);
            } catch (\Throwable $e) {
                report($e);
                DB::table('as_writer_jobs')->where('id', $id)->update(['status' => 'failed', 'error' => Str::limit($e->getMessage(), 480, ''), 'updated_at' => now()]);
            }
        };
        if (function_exists('fastcgi_finish_request')) {
            ignore_user_abort(true);
            @set_time_limit(0);
            response()->json(['success' => true, 'message' => 'Anee is writing.', 'data' => ['id' => $id]])->send();
            fastcgi_finish_request();
            $work();
            exit;
        }
        @set_time_limit(600);
        $work();

        return $this->state($request, $id);
    }

    public function state(Request $request, int $id)
    {
        if ($refused = $this->refuse($request)) {
            return $refused;
        }
        $j = DB::table('as_writer_jobs')->where('id', $id)->first();
        if (! $j) {
            return response()->json(['success' => false, 'message' => 'That writing job is gone.'], 404);
        }
        if ($j->status === 'pending') {
            $quiet = now()->diffInSeconds(\Illuminate\Support\Carbon::parse($j->updated_at), true);
            if ($quiet > \App\Services\AiClient::TIMEOUT_SEARCHED + 90) {
                DB::table('as_writer_jobs')->where('id', $id)->update(['status' => 'failed', 'error' => 'The writing was interrupted. Please try again.']);

                return response()->json(['success' => false, 'message' => 'The writing was interrupted. Please try again.', 'data' => ['status' => 'failed']]);
            }

            return response()->json(['success' => true, 'data' => ['id' => $id, 'status' => 'pending', 'phase' => $j->phase ?: 'start', 'try' => (int) $j->try, 'beatAgo' => (int) $quiet]]);
        }
        if ($j->status === 'failed') {
            return response()->json(['success' => false, 'message' => $j->error ?: 'Anee could not write that page.', 'data' => ['status' => 'failed']]);
        }

        return response()->json(['success' => true, 'data' => ['id' => $id, 'status' => 'ready', 'page' => json_decode((string) $j->result, true)]]);
    }

    /** The keywords nearest a topic, for the builder's picker. */
    public function keywords(Request $request)
    {
        if ($refused = $this->refuse($request)) {
            return $refused;
        }

        return response()->json(['success' => true, 'data' => ['keywords' => SeoKeywords::candidates((string) $request->query('text', ''), 24)
            ->map(fn ($k) => ['keyword' => $k->keyword, 'volume' => (int) $k->volume, 'used' => (int) $k->usedCount, 'difficulty' => $k->seoDifficulty])->values()]]);
    }

    private function refuse(Request $request)
    {
        $token = (string) config('mother.media_token');
        if ($token === '' || ! hash_equals($token, (string) $request->header('X-Anee-Token'))) {
            return response()->json(['success' => false, 'message' => 'Not allowed.'], 403);
        }

        return null;
    }
}
