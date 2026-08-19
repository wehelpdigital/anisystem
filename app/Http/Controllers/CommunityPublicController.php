<?php

namespace App\Http\Controllers;

use App\Models\CommunityWallPost;
use App\Support\MediaStore;
use Illuminate\Support\Str;

/**
 * A shared post, seen from outside.
 *
 * No session, no membership: the unguessable token IS the permission, exactly
 * as the schedule shares work. Everything here is read-only — the reactions
 * and the comment box are shown as an invitation to join rather than hidden,
 * because a page that looks dead gives a visitor no reason to sign up.
 */
class CommunityPublicController extends Controller
{
    public function post(string $token)
    {
        $post = CommunityWallPost::active()
            ->where('publicToken', $token)
            ->with(['author', 'sharedPost'])
            ->withCount(['comments as comment_count'])
            ->first();

        // A post can be un-shared by being taken down; the link must then say
        // nothing at all about who wrote it or what it said.
        if (! $post) {
            abort(404);
        }

        // The words a link preview will show. The share card gets the ORIGINAL
        // post's picture when this is a share, because that is the picture a
        // reader is being promised.
        $shown = $post->sharedPost ?: $post;
        $body = trim(strip_tags((string) ($post->body ?: $shown->body)));
        $author = $shown->author?->full_name ?: 'A farmer';

        return view('community.public-post', [
            'post' => $post,
            'shown' => $shown,
            'ogTitle' => $author . ' on AniSystem',
            'ogDescription' => $body !== ''
                ? Str::limit($body, 180)
                : 'A post from the AniSystem farming community.',
            'ogImage' => MediaStore::url($shown->imagePath ?: $shown->videoPoster) ?: asset('images/logo.png'),
        ]);
    }
}
