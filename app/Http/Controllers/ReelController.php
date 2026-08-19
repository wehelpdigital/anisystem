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
        ]);

        try {
            $stored = ReelEncoder::store($request->file('video'), [
                'start' => (float) ($data['start'] ?? 0),
                'duration' => (float) ($data['duration'] ?? ReelEncoder::MAX_SECONDS),
                'look' => $data['look'] ?? 'none',
                'caption' => $data['overlay'] ?? '',
                'audio' => $request->file('audio'),
                'audioPath' => $data['audioName'] ?? null,
            ]);
        } catch (\Throwable $e) {
            return $this->json(false, $e->getMessage() ?: 'That video could not be prepared.', [], 422);
        }

        $meId = (int) Auth::id();
        $post = CommunityWallPost::create([
            'wallUserId' => $meId,
            'authorUserId' => $meId,
            'body' => trim((string) ($data['caption'] ?? '')),
            'videoPath' => $stored['video'],
            'videoPoster' => $stored['poster'],
            'isReel' => true,
            'durationSec' => $stored['duration'],
            'audioTitle' => $this->audioLabel($data),
            'deleteStatus' => 1,
        ]);

        return $this->json(true, 'Reel posted.', ['postId' => (int) $post->id]);
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
