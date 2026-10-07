<?php

namespace App\Http\Controllers;

use App\Support\Stash;
use Illuminate\Http\Request;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Storage;

/**
 * The Stash (2026-10-07): partners' resources, a shelf per partner and type,
 * searched and paged as you scroll, and read in the app's own PDF viewer.
 * The file is served from this origin (ranges and all), so the viewer never
 * needs the bucket to speak CORS.
 */
class StashController extends Controller
{
    public function shelf(string $partner, string $type)
    {
        $p = Stash::partner($partner);
        $t = Stash::type($partner, $type);
        abort_unless($p && $t, 404);
        $years = DB::table('as_stash_items')->where('partner', $partner)->where('type', $type)->where('deleteStatus', 1)->whereNotNull('publishedOn')
            ->selectRaw('distinct year(publishedOn) as y')->orderByDesc('y')->pluck('y')->map(fn ($y) => (int) $y)->all();

        return view('stash.shelf', ['partnerKey' => $partner, 'typeKey' => $type, 'partner' => $p, 'type' => $t, 'years' => $years]);
    }

    public function items(Request $request, string $partner, string $type)
    {
        abort_unless(Stash::type($partner, $type), 404);
        $q = trim((string) $request->query('q', ''));
        $year = (int) $request->query('year', 0);
        $order = $request->query('order') === 'old' ? 'asc' : 'desc';
        $page = max(1, (int) $request->query('page', 1));
        $per = 18;
        $rows = DB::table('as_stash_items')->where('partner', $partner)->where('type', $type)->where('deleteStatus', 1)
            ->when($q !== '', function ($w) use ($q) {
                foreach (preg_split('/\s+/', $q) as $word) {
                    $w->where(fn ($x) => $x->where('title', 'like', '%' . $word . '%')->orWhere('blurb', 'like', '%' . $word . '%'));
                }
            })
            ->when($year > 0, fn ($w) => $w->whereYear('publishedOn', $year))
            ->orderByRaw('publishedOn is null')->orderBy('publishedOn', $order)->orderBy('sortOrder')
            ->skip(($page - 1) * $per)->take($per + 1)->get();

        return response()->json(['success' => true, 'data' => ['hasMore' => $rows->count() > $per, 'rows' => $rows->take($per)->map(fn ($r) => [
            'id' => (int) $r->id, 'title' => $r->title, 'blurb' => $r->blurb ? mb_substr($r->blurb, 0, 220) : null,
            'date' => $r->publishedOn ? Carbon::parse($r->publishedOn)->format('F Y') : null,
            'cover' => $r->coverPath ? asset(ltrim($r->coverPath, '/')) : null, 'ready' => $r->status === 'ready',
            'url' => route('stash.read', $r->id),
        ])->values()]]);
    }

    public function read(int $id)
    {
        $r = DB::table('as_stash_items')->where('id', $id)->where('deleteStatus', 1)->first();
        abort_unless($r, 404);
        $p = Stash::partner($r->partner);
        $t = Stash::type($r->partner, $r->type);

        return view('stash.read', ['item' => $r, 'partner' => $p, 'type' => $t,
            'date' => $r->publishedOn ? Carbon::parse($r->publishedOn)->format('F Y') : null]);
    }

    /** The PDF, from this origin, with byte ranges so a big issue opens at page one. */
    public function file(Request $request, int $id)
    {
        $r = DB::table('as_stash_items')->where('id', $id)->where('deleteStatus', 1)->where('status', 'ready')->first();
        abort_unless($r && $r->pdfPath, 404);
        $disk = Storage::disk('public');
        $headers = ['Content-Type' => 'application/pdf', 'Cache-Control' => 'private, max-age=86400', 'Accept-Ranges' => 'bytes',
            'Content-Disposition' => 'inline; filename="' . $r->slug . '.pdf"', 'X-Content-Type-Options' => 'nosniff'];
        if (config('filesystems.disks.public.driver') !== 's3') {
            abort_unless($disk->exists($r->pdfPath), 404);

            return response()->file($disk->path($r->pdfPath), $headers);
        }
        $cfg = config('filesystems.disks.public');
        $key = ltrim(trim((string) ($cfg['root'] ?? ''), '/') . '/' . $r->pdfPath, '/');
        $client = $disk->getClient();
        $size = (int) ($r->bytes ?: 0);
        if ($size <= 0) {
            $size = (int) $client->headObject(['Bucket' => $cfg['bucket'], 'Key' => $key])['ContentLength'];
        }
        $range = (string) $request->header('Range', '');
        if (preg_match('/^bytes=(\d*)-(\d*)$/', $range, $m) && ($m[1] !== '' || $m[2] !== '')) {
            $start = $m[1] === '' ? max(0, $size - (int) $m[2]) : (int) $m[1];
            $end = $m[1] === '' ? $size - 1 : ($m[2] === '' ? $size - 1 : min((int) $m[2], $size - 1));
            if ($start > $end || $start >= $size) {
                return response('', 416, ['Content-Range' => 'bytes */' . $size]);
            }
            $obj = $client->getObject(['Bucket' => $cfg['bucket'], 'Key' => $key, 'Range' => 'bytes=' . $start . '-' . $end]);

            return response()->stream(function () use ($obj) {
                $body = $obj['Body'];
                while (! $body->eof()) {
                    echo $body->read(262144);
                    flush();
                }
            }, 206, $headers + ['Content-Range' => 'bytes ' . $start . '-' . $end . '/' . $size, 'Content-Length' => (string) ($end - $start + 1)]);
        }
        $obj = $client->getObject(['Bucket' => $cfg['bucket'], 'Key' => $key]);

        return response()->stream(function () use ($obj) {
            $body = $obj['Body'];
            while (! $body->eof()) {
                echo $body->read(262144);
                flush();
            }
        }, 200, $headers + ['Content-Length' => (string) $size]);
    }
}
