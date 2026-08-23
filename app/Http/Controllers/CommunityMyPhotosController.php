<?php

namespace App\Http\Controllers;

use App\Models\CommunityGroupPost;
use App\Models\CommunityGroupReply;
use App\Models\CommunityWallComment;
use App\Models\CommunityWallPost;
use App\Support\MediaStore;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Str;

/**
 * The pictures this account already has in the community.
 *
 * A member attaching a photo to a comment has three honest sources: the
 * camera, the phone's own files, and what they have already put here. The
 * third had no door — every picture they had posted was one they could only
 * find by scrolling back to it and could not attach anywhere.
 *
 * Answers in the shape the media picker sheet already draws, so the sheet
 * that lists a season's gallery lists this too without knowing the
 * difference. Nothing is copied: a pick hands back the stored path, and the
 * comment points at the same file the original post does.
 */
class CommunityMyPhotosController extends Controller
{
    /** One screenful per request, the same page size the season picker uses. */
    private const PAGE = 40;

    /** Past this, the list is history rather than a picker. */
    private const CEILING = 240;

    public function index(Request $request)
    {
        $meId = (int) Auth::id();
        $q = trim((string) $request->query('q', ''));
        $page = max(1, (int) $request->query('page', 1));

        $items = collect()
            ->concat($this->fromWallPosts($meId))
            ->concat($this->fromWallComments($meId))
            ->concat($this->fromGroupPosts($meId))
            ->concat($this->fromGroupReplies($meId))
            ->concat($this->fromProfile())
            // The same picture posted twice is one picture to choose from.
            ->unique('path')
            // And a picture whose file is not there any more is not one at
            // all: offering it means the member picks it and the comment
            // endpoint — which runs the same check — refuses them.
            ->filter(fn ($m) => \App\Support\GalleryPick::path($m['path']) !== null)
            ->filter(fn ($m) => $q === '' || stripos($m['title'] . ' ' . $m['source'], $q) !== false)
            ->sortByDesc('sortKey')
            ->values()
            ->take(self::CEILING);

        $slice = $items->slice(($page - 1) * self::PAGE, self::PAGE)->values();

        return response()->json(['success' => true, 'data' => [
            'items' => $slice->map(fn ($m) => [
                'kind' => 'image',
                'type' => 'image',
                'path' => $m['path'],
                'poster' => null,
                'url' => MediaStore::url($m['path']),
                'posterUrl' => null,
                'title' => $m['title'],
                'source' => $m['source'],
                'when' => $m['when'],
            ])->all(),
            'more' => $items->count() > $page * self::PAGE,
        ]]);
    }

    /* ------------------------------------------------------------------ *
     * Each shelf, in the one shape the sheet reads.
     * ------------------------------------------------------------------ */

    private function row(?string $path, ?string $body, string $source, $when): ?array
    {
        if (blank($path)) {
            return null;
        }

        return [
            'path' => $path,
            // The words it was posted with are what a member remembers it by;
            // the file name is what the app remembers it by, and only one of
            // those is worth showing.
            'title' => Str::limit(trim(strip_tags((string) $body)), 48) ?: 'Photo',
            'source' => $source,
            'when' => $when ? $when->timezone('Asia/Manila')->format('M j, Y') : null,
            'sortKey' => $when ? $when->timestamp : 0,
        ];
    }

    private function fromWallPosts(int $meId)
    {
        return CommunityWallPost::where('deleteStatus', 1)
            ->where('authorUserId', $meId)
            ->whereNotNull('imagePath')
            ->orderByDesc('id')->limit(self::CEILING)
            ->get(['id', 'body', 'imagePath', 'created_at'])
            ->map(fn ($p) => $this->row($p->imagePath, $p->body, 'Your wall', $p->created_at))
            ->filter();
    }

    private function fromWallComments(int $meId)
    {
        return CommunityWallComment::where('deleteStatus', 1)
            ->where('userId', $meId)
            ->whereNotNull('imagePath')
            ->orderByDesc('id')->limit(self::CEILING)
            ->get(['id', 'body', 'imagePath', 'created_at'])
            ->map(fn ($c) => $this->row($c->imagePath, $c->body, 'A comment you left', $c->created_at))
            ->filter();
    }

    private function fromGroupPosts(int $meId)
    {
        return CommunityGroupPost::where('deleteStatus', 1)
            ->where('userId', $meId)
            ->whereNotNull('imagePath')
            ->orderByDesc('id')->limit(self::CEILING)
            ->get(['id', 'title', 'body', 'imagePath', 'created_at'])
            ->map(fn ($p) => $this->row($p->imagePath, $p->title ?: $p->body, 'A topic you started', $p->created_at))
            ->filter();
    }

    private function fromGroupReplies(int $meId)
    {
        return CommunityGroupReply::where('deleteStatus', 1)
            ->where('userId', $meId)
            ->whereNotNull('imagePath')
            ->orderByDesc('id')->limit(self::CEILING)
            ->get(['id', 'body', 'imagePath', 'created_at'])
            ->map(fn ($r) => $this->row($r->imagePath, $r->body, 'An answer you wrote', $r->created_at))
            ->filter();
    }

    /** The two pictures of themselves an account keeps. */
    private function fromProfile()
    {
        $me = Auth::user();

        return collect([
            $this->row($me->avatarPath ?? null, 'Profile photo', 'Your account', $me->updated_at),
            $this->row($me->coverPath ?? null, 'Cover photo', 'Your account', $me->updated_at),
        ])->filter();
    }
}
