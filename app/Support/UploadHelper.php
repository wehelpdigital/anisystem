<?php

namespace App\Support;

use Illuminate\Http\UploadedFile;

class UploadHelper
{
    /**
     * Derive a safe storage extension for an uploaded file.
     *
     * NEVER trust getClientOriginalExtension() — the client controls the
     * filename, and Laravel's `mimes` rule validates the content-guessed type,
     * not the stored extension. A JPEG-header polyglot named "x.php" / "x.html"
     * would otherwise be written with an executable / active-content extension
     * inside a public web root (RCE / stored XSS).
     *
     * We derive the extension from the file's content-guessed MIME type and
     * accept it only if it is on the allow-list; otherwise we fall back to the
     * first allowed extension. The caller has already validated the MIME with
     * a `mimes:` / `image` rule, so the guess is trustworthy here.
     *
     * @param  array<int,string>  $allowed  lower-case extensions, e.g. ['jpg','jpeg','png','webp']
     */
    public static function safeExtension(UploadedFile $file, array $allowed, string $fallback = null): string
    {
        $fallback = $fallback ?? ($allowed[0] ?? 'bin');

        // Content-guessed extension (Symfony MIME guesser via finfo), not the client name.
        $guessed = strtolower((string) $file->guessExtension());

        // Normalise a couple of common aliases so the allow-list stays simple.
        $aliases = ['jpeg' => 'jpg'];
        $normalised = $aliases[$guessed] ?? $guessed;

        if ($guessed !== '' && (in_array($guessed, $allowed, true) || in_array($normalised, $allowed, true))) {
            return in_array($guessed, $allowed, true) ? $guessed : $normalised;
        }

        return $fallback;
    }
}
