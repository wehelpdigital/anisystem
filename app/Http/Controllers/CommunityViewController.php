<?php

namespace App\Http\Controllers;

use App\Models\AsCommunityBlogPost;
use App\Models\CommunityGroup;
use App\Models\CommunityGroupPost;
use App\Models\CommunityWallPost;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;

/**
 * Counting what has been looked at.
 *
 * Every look counts, including the same person's second one — that is what a
 * view count means on every wall people already read, and it is what the
 * owner asked for. The page decides what a look IS (an item that came into
 * sight, once per page), and this only adds up.
 *
 * A share passes its views up to what it shares. Somebody watching John's
 * clip on my wall has watched John's clip; his post carries the total, and
 * mine carries what came through me. Neither number is a lie, and neither is
 * the other one's.
 */
class CommunityViewController extends Controller
{
    /** How many at once, so a scroll cannot spend the afternoon writing. */
    private const MAX_PER_CALL = 40;

    public function count(Request $request)
    {
        $data = $request->validate([
            'items' => 'required|array|max:' . self::MAX_PER_CALL,
            'items.*.kind' => 'required|string|in:post,topic,group,blog',
            'items.*.id' => 'required|integer|min:1',
        ]);

        $byKind = collect($data['items'])
            ->groupBy('kind')
            ->map(fn ($rows) => collect($rows)->pluck('id')->map(fn ($i) => (int) $i)->unique()->values()->all());

        $counted = [];

        foreach ($byKind->get('post', []) as $id) {
            $counted['post'][] = $this->bump(CommunityWallPost::class, $id);
        }
        foreach ($byKind->get('topic', []) as $id) {
            $counted['topic'][] = $this->bump(CommunityGroupPost::class, $id);
        }
        foreach ($byKind->get('group', []) as $id) {
            $counted['group'][] = $this->bump(CommunityGroup::class, $id);
        }
        foreach ($byKind->get('blog', []) as $id) {
            $counted['blog'][] = $this->bump(AsCommunityBlogPost::class, $id);
        }

        return response()->json(['success' => true, 'message' => 'Counted.', 'data' => [
            'counts' => $this->readBack($byKind),
        ]]);
    }

    /**
     * Add one, and pass it up when the thing is a share.
     *
     * increment() rather than a read-modify-write: two people looking at once
     * would otherwise each write the number they read, and one of the looks
     * would vanish.
     */
    private function bump(string $model, int $id): int
    {
        $row = $model::query()->find($id);
        if (! $row) {
            return 0;
        }

        $model::query()->whereKey($id)->increment('viewCount');

        if ($model === CommunityWallPost::class && $row->sharedPostId) {
            CommunityWallPost::query()->whereKey((int) $row->sharedPostId)->increment('viewCount');
        }

        return $id;
    }

    /** What the page should now show, so it does not have to guess. */
    private function readBack($byKind): array
    {
        $out = ['post' => [], 'topic' => [], 'group' => [], 'blog' => []];

        if ($ids = $byKind->get('post', [])) {
            $out['post'] = CommunityWallPost::query()->whereIn('id', $ids)
                ->pluck('viewCount', 'id')->map(fn ($v) => (int) $v)->all();
        }
        if ($ids = $byKind->get('topic', [])) {
            $out['topic'] = CommunityGroupPost::query()->whereIn('id', $ids)
                ->pluck('viewCount', 'id')->map(fn ($v) => (int) $v)->all();
        }
        if ($ids = $byKind->get('group', [])) {
            $out['group'] = CommunityGroup::query()->whereIn('id', $ids)
                ->pluck('viewCount', 'id')->map(fn ($v) => (int) $v)->all();
        }
        if ($ids = $byKind->get('blog', [])) {
            $out['blog'] = AsCommunityBlogPost::query()->whereIn('id', $ids)
                ->pluck('viewCount', 'id')->map(fn ($v) => (int) $v)->all();
        }

        return $out;
    }
}
