<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

/**
 * The footer pages get their full words.
 *
 * Privacy, Terms, Cookies and About were seeded on 2026-08-03 with a few
 * starter lines each, and the privacy row was later overwritten by a test of
 * the mother app's editor ("Updated content via mother CMS."). The complete
 * wording now lives in resources/views/legal/defaults/<slug>.html.
 *
 * A row is written ONLY when nobody has written it yet: missing, empty, or
 * still carrying one of the known placeholder texts below (compared as plain
 * words, with the old product names read as the new one). Anything else is
 * the owner's own writing from the mother app and is left exactly as it is.
 */
return new class extends Migration
{
    private const TITLES = [
        'privacy' => 'Privacy Policy',
        'terms' => 'Terms of Service',
        'cookies' => 'Cookie Policy',
        'about' => 'About anee.io',
    ];

    /** Words that were only ever stand-ins. */
    private const PLACEHOLDERS = [
        '<p>Updated content via mother CMS.</p>',
        '<p>Edit this page.</p>',
        // The 2026-08-03 starter bodies, as seeded.
        '<p>Your privacy matters to us. This policy explains what information anee.io collects, how we use it, and the choices you have.</p><h3>What we collect</h3><p>Account details you provide (name, email, farm location) and the data you enter about your cropping schedules. We do not sell your personal information.</p><h3>How we use it</h3><p>To run the service, personalise your dashboard and community, and improve anee.io. Community posts you choose to share are visible to other members.</p><h3>Contact</h3><p>Questions about your data? Reach us through the Support page.</p>',
        '<p>By using anee.io you agree to these terms. Please read them carefully.</p><h3>Using the service</h3><p>Use anee.io lawfully and respectfully. You are responsible for the accuracy of the data you enter and for keeping your account secure.</p><h3>Subscriptions</h3><p>Paid features require an active subscription. Prices and inclusions are shown on the subscription page.</p><h3>Community</h3><p>Be kind. Content that is abusive, misleading, or unlawful may be restricted or removed by moderators.</p>',
        '<p>anee.io uses cookies to keep you signed in and to remember your preferences.</p><h3>What cookies we use</h3><p>Essential cookies for login and security, and preference cookies (like your light/dark theme). We do not use cookies to sell your data.</p><h3>Managing cookies</h3><p>You can clear or block cookies in your browser settings, though some features may stop working if you do.</p>',
        '<p>anee.io helps Filipino farmers plan and run a productive cropping season — from lots, workers and activities to reports and a supportive community.</p><h3>Our mission</h3><p>To put practical, data-driven farm planning in every grower\'s hands, in a language and workflow that fits real life on the farm.</p><h3>The community</h3><p>Beyond the tools, anee.io is a place for co-farmers to swap tips, ask questions, and learn from the anee.io team.</p>',
    ];

    public function up(): void
    {
        if (! Schema::hasTable('as_legal_pages')) {
            return;
        }

        $stand = array_map([$this, 'words'], self::PLACEHOLDERS);
        $now = Carbon::now('Asia/Manila');
        $order = 0;

        foreach (self::TITLES as $slug => $title) {
            $order++;
            $file = resource_path('views/legal/defaults/' . $slug . '.html');
            if (! is_file($file)) {
                continue;
            }
            $body = (string) file_get_contents($file);

            $row = DB::table('as_legal_pages')->where('slug', $slug)->first();

            if (! $row) {
                DB::table('as_legal_pages')->insert([
                    'slug' => $slug,
                    'title' => $title,
                    'body' => $body,
                    'sortOrder' => $order,
                    'isPublished' => 1,
                    'deleteStatus' => 1,
                    'created_at' => $now,
                    'updated_at' => $now,
                ]);
                continue;
            }

            $words = $this->words($row->body);
            if ($words !== '' && ! in_array($words, $stand, true)) {
                continue;   // the owner's own writing
            }

            DB::table('as_legal_pages')->where('id', $row->id)->update([
                'title' => $title,
                'body' => $body,
                'updated_at' => $now,
            ]);
        }
    }

    public function down(): void
    {
        // Nothing. Putting the starter lines back over a finished policy
        // would only lose words; the owner edits these in the mother app.
    }

    /** Plain words, for comparing: no tags, one space, the new name, lower case. */
    private function words(?string $html): string
    {
        $t = html_entity_decode(strip_tags((string) $html), ENT_QUOTES | ENT_HTML5, 'UTF-8');
        $t = str_ireplace(['AniSystem by AniSenso', 'AniSystem', 'AniSenso'], 'anee.io', $t);

        return mb_strtolower(trim((string) preg_replace('/\s+/u', ' ', $t)));
    }
};
