<?php

namespace App\Support;

use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Str;

/**
 * Where an upload goes.
 *
 * This app's container loses its filesystem on every deploy, so a photo saved
 * on Tuesday was gone by Thursday and the note pointing at it showed nothing.
 * When the mother app is configured, files are handed to it and only the path
 * comes back. Without that configuration everything behaves exactly as it did
 * — local disk — so development and any un-migrated install keep working.
 *
 * A path that lives over there is marked with a prefix, because the URL for it
 * is not this app's URL. {@see MediaStore::url()} is the one place that knows
 * the difference.
 */
class MediaStore
{
    /** Marks a path as belonging to the mother app rather than this disk. */
    public const REMOTE_PREFIX = 'mm:';

    public static function enabled(): bool
    {
        return filled(config('mother.url')) && filled(config('mother.media_token'));
    }

    /**
     * Keep raw bytes (a canvas export, a composed map picture).
     *
     * @return string|null the stored path, or null when it could not be kept
     */
    public static function putBinary(
        string $binary,
        string $folder,
        string $ext = 'png',
        $scope = null,
        string $namePrefix = ''
    ): ?string {
        if (self::enabled()) {
            $remote = self::send([
                'image' => 'data:image/' . ($ext === 'jpg' ? 'jpeg' : $ext) . ';base64,' . base64_encode($binary),
                'folder' => $folder,
                'scope' => (string) $scope,
                // The app reads meaning out of these names later — a map, a
                // team drawing — so the prefix travels with the file.
                'prefix' => $namePrefix,
            ]);
            if ($remote !== null) {
                return self::REMOTE_PREFIX . $remote;
            }
            // Falling through to the local disk is deliberate: a file kept
            // somewhere beats an upload the user has to redo, and the note
            // still points at something until the deploy that clears it.
        }

        $path = self::localPath($folder, $scope, $ext, $namePrefix);
        Storage::disk('public')->put($path, $binary);

        return $path;
    }

    /** Keep a file the browser uploaded. */
    public static function putFile(UploadedFile $file, string $folder, $scope = null): ?string
    {
        if (self::enabled()) {
            $remote = self::sendFile($file, $folder, $scope);
            if ($remote !== null) {
                return self::REMOTE_PREFIX . $remote;
            }
        }

        return $file->store(self::localFolder($folder, $scope), 'public') ?: null;
    }

    /** Forget a file — a superseded drawing, a removed attachment. */
    public static function delete(?string $path): void
    {
        if (blank($path)) {
            return;
        }

        if (Str::startsWith($path, self::REMOTE_PREFIX)) {
            if (! self::enabled()) {
                return;
            }
            try {
                Http::withToken(config('mother.media_token'))
                    ->acceptJson()
                    ->timeout(15)
                    ->delete(self::endpoint(), ['path' => self::strip($path)]);
            } catch (\Throwable $e) {
                Log::warning('Could not remove remote media', ['path' => $path, 'error' => $e->getMessage()]);
            }

            return;
        }

        Storage::disk('public')->delete($path);
    }

    /**
     * The address a browser should fetch this from. Every caller that used to
     * reach for Storage::url() should come here instead, because half the
     * paths in the database now belong to another host.
     */
    public static function url(?string $path): ?string
    {
        if (blank($path)) {
            return null;
        }

        return Str::startsWith($path, self::REMOTE_PREFIX)
            ? rtrim((string) config('mother.url'), '/') . '/storage/' . ltrim(self::strip($path), '/')
            : Storage::disk('public')->url($path);
    }

    /** True for a path the mother app holds. */
    public static function isRemote(?string $path): bool
    {
        return $path !== null && Str::startsWith($path, self::REMOTE_PREFIX);
    }

    /** The path without its marker, as the mother app knows it. */
    public static function strip(string $path): string
    {
        return Str::after($path, self::REMOTE_PREFIX);
    }

    // ------------------------------------------------------------------

    /** @return string|null the remote path, or null if the call did not succeed */
    private static function send(array $payload): ?string
    {
        try {
            $res = Http::withToken(config('mother.media_token'))
                ->acceptJson()
                ->timeout(30)
                ->post(self::endpoint(), $payload);

            return $res->successful() ? ($res->json('data.path') ?: null) : self::logged($res);
        } catch (\Throwable $e) {
            Log::warning('Media upload to the mother app failed', ['error' => $e->getMessage()]);

            return null;
        }
    }

    private static function sendFile(UploadedFile $file, string $folder, $scope): ?string
    {
        try {
            $res = Http::withToken(config('mother.media_token'))
                ->acceptJson()
                ->timeout(60)
                ->attach('file', file_get_contents($file->getRealPath()), $file->getClientOriginalName() ?: 'upload')
                ->post(self::endpoint(), ['folder' => $folder, 'scope' => (string) $scope]);

            return $res->successful() ? ($res->json('data.path') ?: null) : self::logged($res);
        } catch (\Throwable $e) {
            Log::warning('Media upload to the mother app failed', ['error' => $e->getMessage()]);

            return null;
        }
    }

    private static function logged($res): ?string
    {
        Log::warning('Mother app refused a media upload', [
            'status' => $res->status(), 'body' => Str::limit((string) $res->body(), 300),
        ]);

        return null;
    }

    private static function endpoint(): string
    {
        return rtrim((string) config('mother.url'), '/') . '/api/anisystem/media';
    }

    private static function localFolder(string $folder, $scope): string
    {
        return 'anisystem/' . $folder . '/' . (preg_replace('~[^0-9]~', '', (string) $scope) ?: 'shared');
    }

    private static function localPath(string $folder, $scope, string $ext, string $namePrefix = ''): string
    {
        return self::localFolder($folder, $scope) . '/' . $namePrefix . Str::random(24) . '.' . $ext;
    }
}
