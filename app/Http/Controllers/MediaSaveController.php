<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Storage;
use Symfony\Component\HttpFoundation\StreamedResponse;

/**
 * Hand a file to the browser as a download rather than as a page.
 *
 * The `download` attribute on an anchor is ignored for a cross-origin URL —
 * the browser navigates to the picture instead of saving it — and this app
 * serves media from two origins: its own public disk, and the mother site's
 * for anything written over there. So "Save" opened the photo in a tab and
 * left the person to long-press it.
 *
 * This re-serves the bytes from this origin with an attachment disposition,
 * which makes the save unconditional. It will only fetch from the two hosts
 * the app itself publishes from — an open fetcher would be a way to make the
 * server read things on the attacker's behalf.
 */
class MediaSaveController extends Controller
{
    public function __invoke(Request $request): StreamedResponse
    {
        $url = (string) $request->query('u');
        $name = $this->safeName((string) $request->query('n'), $url);

        if ($url === '' || ! filter_var($url, FILTER_VALIDATE_URL)) {
            abort(400, 'Nothing to save.');
        }
        if (! $this->allowed($url)) {
            abort(403, 'That file is not ours to hand over.');
        }

        // Local files stream off the disk; no point going out to the network
        // to fetch something already on it.
        $local = $this->localPath($url);
        if ($local !== null) {
            if (! Storage::disk('public')->exists($local)) {
                abort(404, 'That file is no longer here.');
            }

            return Storage::disk('public')->download($local, $name);
        }

        $res = Http::timeout(30)->withOptions(['stream' => true])->get($url);
        if (! $res->successful()) {
            abort(404, 'That file could not be fetched.');
        }

        $body = $res->toPsrResponse()->getBody();

        return response()->streamDownload(function () use ($body) {
            while (! $body->eof()) {
                echo $body->read(1024 * 64);
                flush();
            }
        }, $name, [
            'Content-Type' => $res->header('Content-Type') ?: 'application/octet-stream',
        ]);
    }

    /** Only the two origins this app publishes media from. */
    private function allowed(string $url): bool
    {
        $host = strtolower((string) parse_url($url, PHP_URL_HOST));
        if ($host === '') {
            return false;
        }

        $ours = array_filter([
            strtolower((string) parse_url((string) config('app.url'), PHP_URL_HOST)),
            strtolower((string) parse_url((string) config('mother.url'), PHP_URL_HOST)),
            strtolower(request()->getHost()),
        ]);

        return in_array($host, $ours, true);
    }

    /** The path under the public disk, when the URL points at our own. */
    private function localPath(string $url): ?string
    {
        $path = (string) parse_url($url, PHP_URL_PATH);
        if (! str_contains($path, '/storage/')) {
            return null;
        }
        $host = strtolower((string) parse_url($url, PHP_URL_HOST));
        if ($host !== strtolower(request()->getHost())
            && $host !== strtolower((string) parse_url((string) config('app.url'), PHP_URL_HOST))) {
            return null;
        }

        return ltrim(urldecode(explode('/storage/', $path, 2)[1]), '/');
    }

    /**
     * A filename somebody would recognise, and one that cannot escape the
     * download folder or forge a header.
     */
    private function safeName(string $wanted, string $url): string
    {
        $name = $wanted !== '' ? $wanted : urldecode(basename((string) parse_url($url, PHP_URL_PATH)));
        $name = preg_replace('~[^A-Za-z0-9._ -]~', '', $name) ?: 'download';
        $name = trim(str_replace('..', '', $name), '. ');

        return $name !== '' ? mb_substr($name, 0, 120) : 'download';
    }
}
