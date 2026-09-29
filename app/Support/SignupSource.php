<?php

namespace App\Support;

use App\Models\User;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;

/**
 * Which ad a signup came from (2026-09-29).
 *
 * remember() runs on the pages an ad lands on (the landing page, home, the
 * signup form): when the address carries utm_* tags or a click id, they go
 * into the session. record() runs when an account is born and keeps them on
 * as_signup_sources, one row per account. Tags that are not there leave no
 * row: most signups come with none, and that is also an answer.
 */
class SignupSource
{
    private const KEY = 'signup.source';

    private const TAGS = ['source' => 'utm_source', 'medium' => 'utm_medium', 'campaign' => 'utm_campaign', 'content' => 'utm_content', 'term' => 'utm_term'];

    public static function remember(Request $request): void
    {
        $seen = self::fromQuery($request);
        if (! $seen) {
            return;
        }
        $held = $request->session()->get(self::KEY);
        // The same ad seen again (the landing page's form handing over to the
        // signup) keeps the page it first landed on.
        if (is_array($held) && self::same($held, $seen)) {
            return;
        }
        $request->session()->put(self::KEY, $seen);
    }

    public static function record(User $user, Request $request, string $method = 'email'): void
    {
        $s = $request->session()->pull(self::KEY) ?: self::fromQuery($request);
        if (! is_array($s)) {
            return;
        }
        try {
            DB::table('as_signup_sources')->insert([
                'userId' => $user->id,
                'source' => $s['source'] ?? null, 'medium' => $s['medium'] ?? null, 'campaign' => $s['campaign'] ?? null,
                'content' => $s['content'] ?? null, 'term' => $s['term'] ?? null,
                'clickKind' => $s['clickKind'] ?? null, 'clickId' => $s['clickId'] ?? null,
                'landing' => $s['landing'] ?? null, 'method' => $method,
                'created_at' => now(), 'updated_at' => now(),
            ]);
        } catch (\Throwable $e) {
            // Attribution is worth less than a signup: never let it cost one.
            report($e);
        }
    }

    /** The tags on this request's address, or null when it has none. */
    private static function fromQuery(Request $request): ?array
    {
        $out = [];
        foreach (self::TAGS as $field => $param) {
            $v = $request->query($param);
            if (is_string($v) && trim($v) !== '') {
                $out[$field] = mb_substr(trim($v), 0, $field === 'source' || $field === 'medium' ? 60 : 120);
            }
        }
        foreach (['fbclid', 'gclid'] as $kind) {
            $v = $request->query($kind);
            if (is_string($v) && trim($v) !== '') {
                $out['clickKind'] = $kind;
                $out['clickId'] = mb_substr(trim($v), 0, 255);
                $out['source'] ??= $kind === 'fbclid' ? 'facebook' : 'google';
                break;
            }
        }
        if (! $out) {
            return null;
        }
        $out['landing'] = mb_substr('/' . ltrim($request->path(), '/'), 0, 120);

        return $out;
    }

    private static function same(array $a, array $b): bool
    {
        foreach (['source', 'medium', 'campaign', 'content', 'term', 'clickId'] as $k) {
            if (($a[$k] ?? null) !== ($b[$k] ?? null)) {
                return false;
            }
        }

        return true;
    }
}
