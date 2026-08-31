<?php

namespace App\Http\Controllers;

use App\Models\AsCommunityBlogPost;
use Illuminate\Http\Request;

/**
 * An article as it will look here, before anybody can read it.
 *
 * The admin app builds the technician's blog on the other side of a shared
 * database, and "what will this look like in anee.io" was a question only
 * publishing could answer. This answers it: the same article page, the same
 * stylesheet, the same sanitiser — a draft included.
 *
 * Two things keep it out of the world. The address is signed, so it cannot be
 * guessed or shared beyond the person the admin app handed it to, and the
 * page tells every crawler not to index or follow it. It also does not count
 * as a read: a preview is the writer looking at their own work.
 */
class BlogPreviewController extends Controller
{
    /**
     * The token is HMAC over the post id with the secret the two apps
     * already share for media, so no new configuration is needed and a
     * token minted by anyone without it is worthless.
     */
    public static function token(int $postId): string
    {
        return substr(hash_hmac('sha256', 'blog-preview:' . $postId, self::secret()), 0, 32);
    }

    private static function secret(): string
    {
        // Falls back to the app key so a copy without the shared token
        // configured still previews its own posts rather than refusing.
        return (string) (config('mother.media_token') ?: config('app.key'));
    }

    public function show(Request $request, int $id)
    {
        $token = (string) $request->query('t', '');
        if (! hash_equals(self::token($id), $token)) {
            abort(404);
        }

        // A draft is exactly what a preview is for, so `published()` is not
        // applied here — and the view count is left alone, because the
        // writer reading their own draft is not a reader.
        $post = AsCommunityBlogPost::active()->where('id', $id)->first();
        if (! $post) {
            abort(404);
        }

        return response()
            ->view('community.blog.preview', compact('post'))
            ->header('X-Robots-Tag', 'noindex, nofollow, noarchive');
    }
}
