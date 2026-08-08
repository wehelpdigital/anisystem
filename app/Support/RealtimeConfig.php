<?php

namespace App\Support;

/**
 * The realtime endpoints the browser needs, resolved per request.
 *
 * These used to reach the client only through VITE_* variables, which Vite
 * bakes into the bundle at build time. That has two failure modes in a hosted
 * deploy: changing a key needs a full rebuild, and a value written as a
 * "${PUSHER_APP_KEY}" reference — the form Laravel's .env expands but a
 * dashboard variable does not — ships as that literal string. Echo is then
 * constructed with a nonsense key, so it looks configured while never
 * connecting. Reading the values here instead means the running container's
 * own variables decide, and a bad one is treated as absent.
 */
class RealtimeConfig
{
    /**
     * A value that is still an unexpanded ${VAR} reference was never resolved,
     * so it is not a setting — treat it as missing rather than passing a
     * literal "${PUSHER_APP_KEY}" to the client.
     */
    public static function clean($value): ?string
    {
        $value = trim((string) $value);

        if ($value === '' || preg_match('/^\$\{[A-Za-z_][A-Za-z0-9_]*\}$/', $value)) {
            return null;
        }

        return $value;
    }

    public static function pusherKey(): ?string
    {
        return self::clean(config('broadcasting.connections.pusher.key'));
    }

    public static function pusherCluster(): ?string
    {
        return self::clean(config('broadcasting.connections.pusher.options.cluster')) ?? 'mt1';
    }

    public static function livekitUrl(): ?string
    {
        return self::clean(config('services.livekit.url'));
    }

    /** Whether push-based realtime can work at all on this deployment. */
    public static function enabled(): bool
    {
        return self::pusherKey() !== null;
    }
}
