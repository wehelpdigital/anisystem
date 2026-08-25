<?php

namespace App\Support;

use App\Models\CommunityGroup;
use App\Models\CommunityGroupPost;
use App\Models\CommunityGroupReply;
use App\Models\CommunityProfileVideo;
use App\Models\CommunityWallComment;
use App\Models\CommunityWallPost;
use Illuminate\Support\Facades\Route;

/**
 * The clips a member has put into the community, wherever they put them.
 *
 * A video shared on the wall, in a comment under somebody's post, as a
 * discussion topic or in an answer to one is the same thing as a video
 * uploaded to a profile: a film this person made of their farm. It was only
 * findable where it was posted — scroll far enough back down the right
 * thread — so the profile's Videos shelf and the gallery both missed most of
 * what a member had actually filmed.
 *
 * One list, read by both: SeasonMedia does this job for a season, and this is
 * the same job for the community side.
 */
class CommunityMedia
{
    /**
     * Every clip this member has shared in the community, newest first.
     *
     * Each row carries what a gallery tile needs and what a profile tile
     * needs, which is nearly the same thing: where the film is, a frame to
     * show before it plays, what it was posted as, and the way back to the
     * post it belongs to.
     *
     * @return list<array<string,mixed>>
     */
    public static function videosFor(int $userId, bool $isSelf = false): array
    {
        $out = [];

        foreach (CommunityProfileVideo::active()->where('userId', $userId)->get() as $v) {
            $out[] = self::row(
                $v->videoPath,
                $v->posterPath,
                'Video',
                'Your profile',
                $v->created_at,
                self::profileHref($userId),
                deletable: $isSelf,
                deleteId: $v->id
            );
        }

        /* A post can carry several clips now (videoPaths), and a post that
         * does may leave the old single column empty. Asking only videoPath
         * made every multi-clip post invisible on this shelf — a member who
         * filmed three clips into one post had filmed nothing, as far as
         * their own gallery could tell. Each model's clips() already speaks
         * both spellings; the query just has to let those rows through. */
        $carriesClips = function ($q) {
            $q->whereNotNull('videoPath')->where('videoPath', '!=', '');
            $q->orWhereNotNull('videoPaths');
        };

        foreach (CommunityWallPost::where('authorUserId', $userId)->where('deleteStatus', 1)
            ->where($carriesClips)->get() as $p) {
            foreach ($p->clips() as $clip) {
                $out[] = self::row(
                    $clip['video'] ?? null,
                    $clip['poster'] ?? null,
                    'Wall post',
                    'On a wall',
                    $p->created_at,
                    self::profileHref((int) $p->wallUserId) . '#wallpost-' . $p->id
                );
            }
        }

        foreach (CommunityWallComment::where('userId', $userId)->where('deleteStatus', 1)
            ->where($carriesClips)->get() as $c) {
            $post = CommunityWallPost::where('id', $c->wallPostId)->first();
            foreach ($c->clips() as $clip) {
                $out[] = self::row(
                    $clip['video'] ?? null,
                    $clip['poster'] ?? null,
                    'Comment',
                    'Under a post',
                    $c->created_at,
                    $post ? self::profileHref((int) $post->wallUserId) . '#wallpost-' . $post->id : null
                );
            }
        }

        $groupNames = [];
        foreach (CommunityGroupPost::where('userId', $userId)->where('deleteStatus', 1)
            ->where($carriesClips)->get() as $gp) {
            foreach ($gp->clips() as $clip) {
                $out[] = self::row(
                    $clip['video'] ?? null,
                    $clip['poster'] ?? null,
                    'Discussion topic',
                    self::groupName((int) $gp->groupId, $groupNames),
                    $gp->created_at,
                    self::groupHref((int) $gp->groupId, (int) $gp->id)
                );
            }
        }

        foreach (CommunityGroupReply::where('userId', $userId)->where('deleteStatus', 1)
            ->where(function ($q) {
                $q->whereNotNull('videoPath')->where('videoPath', '!=', '');
                if (\Illuminate\Support\Facades\Schema::hasColumn((new CommunityGroupReply)->getTable(), 'videoPaths')) {
                    $q->orWhereNotNull('videoPaths');
                }
            })->get() as $gr) {
            $post = CommunityGroupPost::where('id', $gr->postId)->first();
            $where = $post ? self::groupName((int) $post->groupId, $groupNames) : 'In a discussion';
            $href = $post ? self::groupHref((int) $post->groupId, (int) $post->id) : null;
            // An answer can carry several: each is its own tile, because each
            // is its own film.
            $clips = method_exists($gr, 'clips')
                ? $gr->clips()
                : [['video' => $gr->videoPath, 'poster' => $gr->videoPoster]];
            foreach ($clips as $clip) {
                $out[] = self::row($clip['video'] ?? null, $clip['poster'] ?? null, 'Answer', $where, $gr->created_at, $href);
            }
        }

        $out = array_values(array_filter($out));

        /* One tile per film.
         *
         * The same clip can be referenced from several places at once —
         * answer somebody with a video out of your gallery twice and it is
         * two postings of one file, not two films. A shelf of your videos
         * showing it three times is a shelf that has confused "what I have
         * filmed" with "what I have said".
         *
         * Which posting represents it: a profile upload if there is one,
         * because that is the copy its owner can delete and losing that
         * control to a deduplication would be a bad trade. Otherwise the most
         * recent, so the tile sits where the shelf's own order expects it.
         */
        usort($out, function ($a, $b) {
            if ($a['deletable'] !== $b['deletable']) {
                return $a['deletable'] ? -1 : 1;
            }

            return $b['ts'] <=> $a['ts'];
        });

        $byPath = [];
        foreach ($out as $row) {
            $byPath[$row['path']] ??= $row;
        }

        $out = array_values($byPath);
        usort($out, fn ($a, $b) => $b['ts'] <=> $a['ts']);

        return $out;
    }

    /**
     * One row, or null when there is nothing to point at.
     *
     * The poster is looked up when the record has none of its own: a clip
     * referenced out of the gallery never carried one, and by now a frame has
     * usually been cut for it (see VideoPoster).
     */
    private static function row(
        ?string $video,
        ?string $poster,
        string $title,
        string $source,
        $when,
        ?string $href,
        bool $deletable = false,
        $deleteId = null
    ): ?array {
        $video = trim((string) $video);
        if ($video === '') {
            return null;
        }
        $poster = $poster ?: VideoPoster::stored($video);

        return [
            'kind'      => 'video',
            'type'      => 'video',
            'path'      => $video,
            'poster'    => $poster,
            'url'       => MediaStore::url($video),
            'posterUrl' => $poster ? MediaStore::url($poster) : null,
            'title'     => $title,
            'source'    => $source,
            'href'      => $href,
            'when'      => $when ? $when->diffForHumans() : null,
            'ts'        => $when ? $when->timestamp : 0,
            'deletable' => $deletable,
            'deleteId'  => $deleteId,
        ];
    }

    private static function groupName(int $groupId, array &$cache): string
    {
        if (! array_key_exists($groupId, $cache)) {
            $cache[$groupId] = (string) (CommunityGroup::where('id', $groupId)->value('name') ?: 'A discussion');
        }

        return $cache[$groupId];
    }

    private static function groupHref(int $groupId, int $postId): ?string
    {
        return Route::has('community.groups.show')
            ? route('community.groups.show', ['id' => $groupId]) . '#post-' . $postId
            : null;
    }

    private static function profileHref(int $userId): ?string
    {
        return Route::has('community.connect.profile')
            ? route('community.connect.profile', ['userId' => $userId])
            : null;
    }
}
