<?php

namespace App\Support;

use App\Models\User;
use Illuminate\Support\Facades\Auth;

/**
 * The little round face beside a chat message.
 *
 * The AI chats drew initials for the person typing, which is what an account
 * with no picture gets everywhere else in the app — but an account WITH one
 * was shown its initials too, so a farmer talking to the technician saw "AT"
 * where their own photo is on every other screen.
 *
 * One helper, because three chats draw it: the floating technician, the
 * module page, and the season's group chat with the AI in it. Each of them
 * needs it twice — once in Blade for the turns the server renders, once as a
 * string for the ones JavaScript adds — so it comes back as HTML rather than
 * as a set of parts each caller has to assemble the same way.
 */
class ChatFace
{
    /** The signed-in account's face, ready to drop inside a face element. */
    public static function mine(): string
    {
        return self::for(Auth::user());
    }

    /**
     * Somebody's face: their photo, or their initials.
     *
     * data-initials is what app.js puts back when the photo 404s — a picture
     * whose file has gone otherwise leaves the browser's broken-image glyph,
     * which reads as a broken screen rather than a person with no photo.
     */
    public static function for(?User $user): string
    {
        $initials = e($user?->initials ?: '?');
        $photo = $user?->avatarPath;

        if (! $photo) {
            return $initials;
        }

        return '<img data-avatar-fallback data-initials="' . $initials . '"'
            . ' src="' . e(MediaStore::url($photo)) . '" alt="">';
    }
}
