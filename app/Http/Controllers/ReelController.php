<?php

namespace App\Http\Controllers;

use App\Models\CommunityReaction;
use App\Models\CommunityWallPost;
use App\Support\ReelEncoder;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\File;

/**
 * Reels: sixty seconds, filling the phone.
 *
 * A reel is stored as a wall post — see the migration for why — so everything
 * this controller does is make one and hand back the carousel's worth of them.
 * Reacting, commenting, saving and sharing all arrive for free through the
 * machinery a post already has.
 */
class ReelController extends Controller
{
    /** How many the carousel holds before somebody has to scroll. */
    private const RAIL = 12;

    /**
     * Post a reel.
     *
     * The phone sends what it filmed plus the decisions a farmer made about
     * it; the encoder does the work. The clip is capped at sixty seconds here
     * as well as in the editor, because the editor is a courtesy and this is
     * the rule.
     */
    public function store(Request $request)
    {
        $data = $request->validate([
            'video' => 'required|file|max:307200',
            'caption' => 'nullable|string|max:2000',
            'start' => 'nullable|numeric|min:0',
            'duration' => 'nullable|numeric|min:1|max:' . ReelEncoder::MAX_SECONDS,
            'look' => 'nullable|string|max:24',
            // A track from the library, named not pathed, or one off the phone.
            'audioName' => 'nullable|string|max:160',
            'audio' => 'nullable|file|max:20480',
            // What to burn over the picture; kept apart from the caption
            // because the caption is the post's words and this is the video's.
            'overlay' => 'nullable|string|max:120',
            // What the editor stuck on: words with a font, colour, size and
            // place. Sent as description, burned at the video's own size.
            'overlays' => 'nullable|string|max:4000',
            'sticker' => 'nullable|image|max:8192',
            'stickerAt' => 'nullable|string|max:200',
            // A cover taken in the browser, used when the server cannot make
            // one of its own.
            'poster' => 'nullable|image|max:8192',
        ]);

        try {
            $stored = ReelEncoder::store($request->file('video'), [
                'start' => (float) ($data['start'] ?? 0),
                'duration' => (float) ($data['duration'] ?? ReelEncoder::MAX_SECONDS),
                'look' => $data['look'] ?? 'none',
                'caption' => $data['overlay'] ?? '',
                'overlays' => json_decode((string) ($data['overlays'] ?? '[]'), true) ?: [],
                'audio' => $request->file('audio'),
                'audioPath' => $data['audioName'] ?? null,
            ]);
        } catch (\Throwable $e) {
            return $this->json(false, $e->getMessage() ?: 'That video could not be prepared.', [], 422);
        }

        /* A cover, whichever way one could be had.
         *
         * The encoder makes one when ffmpeg is there; the browser sends one
         * from the frame the farmer left it on. Either beats a black tile in
         * the rail, which is what a reel with no poster looks like. */
        $poster = $stored['poster'] ?? null;
        if (! $poster && $request->hasFile('poster')) {
            $poster = \App\Support\MediaStore::putFile($request->file('poster'), 'community/reels', (int) Auth::id())
                ?: null;
        }

        $meId = (int) Auth::id();
        $post = CommunityWallPost::create([
            'wallUserId' => $meId,
            'authorUserId' => $meId,
            'body' => trim((string) ($data['caption'] ?? '')),
            'videoPath' => $stored['video'],
            'videoPoster' => $poster,
            'isReel' => true,
            'durationSec' => $stored['duration'],
            'audioTitle' => $this->audioLabel($data),
            'deleteStatus' => 1,
        ]);

        /* Say when the edits were dropped. Without ffmpeg the clip is kept as
         * filmed — no trim, no 9:16, no look, no words, no music — and a
         * farmer who chose all five deserves to hear that rather than
         * discover it by watching. */
        return $this->json(
            true,
            ! empty($stored['raw'])
                ? 'Reel posted — but this server could not edit the video, so it went up as filmed.'
                : 'Reel posted.',
            ['postId' => (int) $post->id, 'raw' => ! empty($stored['raw'])]
        );
    }

    /** The carousel: newest reels, with what a tile needs to draw itself. */
    public function feed(Request $request)
    {
        $meId = (int) Auth::id();
        $reels = CommunityWallPost::active()
            ->where('isReel', 1)
            ->whereNotNull('videoPath')
            ->with('author')
            ->withCount('comments')
            ->orderByDesc('id')
            ->limit(self::RAIL)
            ->get();

        CommunityReaction::attach($reels, 'wallpost', $meId);

        return $this->json(true, 'Reels.', [
            'items' => $reels->map(fn ($r) => [
                'id' => (int) $r->id,
                'video' => \App\Support\MediaStore::url($r->videoPath),
                'poster' => \App\Support\MediaStore::url($r->videoPoster),
                'caption' => (string) $r->body,
                'audio' => $r->audioTitle,
                'seconds' => (int) $r->durationSec,
                'comments' => (int) ($r->comments_count ?? 0),
                // Yours to take down; the viewer draws a bin only for these.
                'mine' => (int) $r->authorUserId === $meId,
                'author' => [
                    'id' => (int) $r->authorUserId,
                    'name' => $r->author?->full_name ?: 'A farmer',
                    'avatar' => $r->author?->avatarPath ? \App\Support\MediaStore::url($r->author->avatarPath) : null,
                    'initials' => $r->author?->initials ?: '?',
                ],
            ])->values(),
        ]);
    }

    /**
     * The music on offer.
     *
     * Whatever the owner has put in storage/app/public/reel-music. Kept as a
     * folder rather than a table because that is how somebody actually adds a
     * song: they drop the file in. Nothing is bundled — the tracks a farm can
     * legally publish under are the owner's decision, not this code's.
     */
    public function music()
    {
        $dir = storage_path('app/public/reel-music');
        $items = [];
        if (File::isDirectory($dir)) {
            foreach (File::files($dir) as $f) {
                if (! in_array(strtolower($f->getExtension()), ['mp3', 'm4a', 'aac', 'ogg', 'wav'], true)) {
                    continue;
                }
                $items[] = [
                    'name' => $f->getFilename(),
                    // The file's own name, tidied: "morning-harvest.mp3" reads
                    // as "Morning Harvest" without anybody maintaining a list.
                    'title' => \Illuminate\Support\Str::of($f->getFilenameWithoutExtension())
                        ->replace(['-', '_'], ' ')->title()->toString(),
                    'url' => asset('storage/reel-music/' . $f->getFilename()),
                ];
            }
        }

        return $this->json(true, 'Music.', ['items' => $items]);
    }

    /**
     * Search openly-licensed music.
     *
     * Openverse indexes CC-licensed audio across sources and answers without
     * a key or an account, which is what makes it usable here: nobody has to
     * register the farm with anybody to put a tune under a reel.
     *
     * Every track carries its licence and its creator back with it, because
     * "free" here means "free under a licence", and the two are not the same
     * thing. What the reel does with that — credit in the caption, or
     * nothing — is the owner's call, but the app has to say it.
     */
    public function musicSearch(Request $request)
    {
        $q = trim((string) $request->query('q', ''));

        try {
            $res = \Illuminate\Support\Facades\Http::timeout(12)
                ->withHeaders(['User-Agent' => 'AniSystem/1.0 (farm management; reels)'])
                ->get('https://api.openverse.org/v1/audio/', array_filter([
                    'q' => $q !== '' ? $q : 'acoustic instrumental',
                    // Asked for wide and narrowed below. The API's own
                    // `license` filter is for authenticated callers — sent
                    // anonymously it answers 401, which is why this search
                    // reported itself unreachable when it was working fine.
                    // Twenty is the ceiling for a caller without a key:
                    // ask for more and the answer is 401, which reads as
                    // "unreachable" and is really "not allowed that much".
                    'page_size' => 20,
                    'peaks' => 'false',
                ]));
        } catch (\Throwable $e) {
            return $this->json(false, 'The music search could not be reached just now.', ['items' => []]);
        }

        if (! $res->successful()) {
            return $this->json(false, 'The music search could not be reached just now.', ['items' => []]);
        }

        // Only what can be used without asking anybody: no non-commercial and
        // no no-derivatives puzzles for a farmer to solve.
        $usable = ['cc0', 'by', 'by-sa', 'pdm'];

        $items = collect($res->json('results') ?? [])
            ->filter(fn ($t) => in_array(strtolower((string) ($t['license'] ?? '')), $usable, true))
            ->map(function ($t) {
                // Openverse hands back either a direct file or a page to
                // fetch it from; only a direct file is any use to an encoder.
                $url = $t['url'] ?? null;

                return $url ? [
                    'id' => (string) ($t['id'] ?? ''),
                    'title' => \Illuminate\Support\Str::limit((string) ($t['title'] ?? 'Untitled'), 60),
                    'by' => (string) ($t['creator'] ?? 'Unknown'),
                    'licence' => strtoupper((string) ($t['license'] ?? '')) . ' ' . (string) ($t['license_version'] ?? ''),
                    'seconds' => (int) round(((int) ($t['duration'] ?? 0)) / 1000),
                    'url' => $url,
                    'source' => (string) ($t['source'] ?? 'openverse'),
                ] : null;
            })
            ->filter()
            ->take(20)
            ->values()
            ->all();

        return $this->json(true, 'Music.', ['items' => $items]);
    }

    /**
     * Fetch a chosen track and keep it, so the encoder has a local file.
     *
     * The browser cannot hand a remote URL to ffmpeg, and ffmpeg should not
     * be reaching across the internet mid-encode either — a slow host would
     * hold a farmer's upload open. Pulled once, stored, then used like any
     * other file.
     */
    public function musicGrab(Request $request)
    {
        $url = (string) $request->input('url', '');
        if (! preg_match('~^https://~i', $url)) {
            return $this->json(false, 'That track cannot be fetched.', [], 422);
        }

        try {
            $res = \Illuminate\Support\Facades\Http::timeout(25)
                ->withHeaders(['User-Agent' => 'AniSystem/1.0 (farm management; reels)'])
                ->get($url);
            if (! $res->successful()) {
                throw new \RuntimeException('http ' . $res->status());
            }
            $bytes = $res->body();
        } catch (\Throwable $e) {
            return $this->json(false, 'That track could not be fetched.', [], 422);
        }

        // A tune under a minute of video does not run to tens of megabytes.
        if (strlen($bytes) > 25 * 1024 * 1024) {
            return $this->json(false, 'That track is too large.', [], 422);
        }

        $ext = match (true) {
            str_contains(strtolower($url), '.wav') => 'wav',
            str_contains(strtolower($url), '.ogg') => 'ogg',
            str_contains(strtolower($url), '.m4a') => 'm4a',
            default => 'mp3',
        };
        $name = 'openverse-' . \Illuminate\Support\Str::random(24) . '.' . $ext;
        $dir = storage_path('app/public/reel-music');
        File::ensureDirectoryExists($dir);
        File::put($dir . DIRECTORY_SEPARATOR . $name, $bytes);

        return $this->json(true, 'Track ready.', ['name' => $name]);
    }

    private function audioLabel(array $data): ?string
    {
        if (filled($data['audioName'] ?? null)) {
            return \Illuminate\Support\Str::of(pathinfo($data['audioName'], PATHINFO_FILENAME))
                ->replace(['-', '_'], ' ')->title()->limit(150)->toString();
        }

        return isset($data['audio']) ? 'Sound from the phone' : null;
    }

    private function json(bool $ok, string $message, array $data = [], int $status = 200)
    {
        return response()->json(['success' => $ok, 'message' => $message, 'data' => $data], $status);
    }
}
