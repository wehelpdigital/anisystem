<?php

namespace App\Models;

use Illuminate\Support\Carbon;

/**
 * A footer page -- Privacy, Terms, Cookies, About -- edited in the mother app
 * (Ani-Senso > Legal & Info Pages) and shown read-only here.
 *
 * The standard wording lives in resources/views/legal/defaults/<slug>.html.
 * It reaches the table once, by migration, and only over rows nobody has
 * written yet; after that the row is the owner's and the file is only a
 * fallback for an address whose row does not exist at all.
 */
class AsLegalPage extends BaseModel
{
    protected $table = 'as_legal_pages';

    protected $fillable = ['slug', 'title', 'body', 'sortOrder', 'isPublished', 'deleteStatus'];

    protected $casts = [
        'isPublished' => 'boolean',
        'sortOrder' => 'integer',
    ];

    /** The four core pages, in footer order: slug => title. */
    public const CORE = [
        'privacy' => 'Privacy Policy',
        'terms' => 'Terms of Service',
        'cookies' => 'Cookie Policy',
        'about' => 'About anee.io',
    ];

    /** Short names for the page switcher. */
    public const SHORT = [
        'privacy' => 'Privacy',
        'terms' => 'Terms',
        'cookies' => 'Cookies',
        'about' => 'About',
    ];

    /** The day the standard wording was last revised (shown when it stands in). */
    public const DEFAULTS_DATE = '2026-09-25';

    public function scopePublished($q)
    {
        return $q->where('isPublished', 1);
    }

    /** The standard wording for a core page, or null. */
    public static function defaultBody(string $slug): ?string
    {
        if (! isset(self::CORE[$slug])) {
            return null;
        }
        $file = resource_path('views/legal/defaults/' . $slug . '.html');

        return is_file($file) ? (string) file_get_contents($file) : null;
    }

    /**
     * An unsaved page wearing the standard wording, for a core address with
     * no row behind it (a fresh database). A row that exists but is
     * unpublished or retired is the owner's decision and is NOT stood in for.
     */
    public static function fromDefault(string $slug): ?self
    {
        $body = self::defaultBody($slug);
        if ($body === null) {
            return null;
        }
        $page = new self([
            'slug' => $slug,
            'title' => self::CORE[$slug],
            'body' => $body,
            'sortOrder' => array_search($slug, array_keys(self::CORE), true) + 1,
            'isPublished' => 1,
            'deleteStatus' => 1,
        ]);
        $page->updated_at = Carbon::parse(self::DEFAULTS_DATE, 'Asia/Manila');

        return $page;
    }
}
