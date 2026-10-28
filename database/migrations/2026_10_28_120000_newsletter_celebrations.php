<?php

use App\Models\AsNlEmail;
use App\Support\Newsletter\Renderer;
use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

/**
 * The newsletter's Philippine celebrations (2026-10-28, the owner: "prepare templates in newsletter for PH
 * celebrations and make sure you add graphics to it").
 *
 * database/newsletter/celebrations.json holds both halves:
 *  - heroes: each holiday email of the line (meta.series HOL01 to HOL26) gets its banner, a 1200 by 600
 *    picture in public/images/newsletter/celebrations, on top;
 *  - emails: eight celebrations the line did not have (Immaculate Conception, Rizal Day, Sinulog, EDSA,
 *    San Isidro and the Pahiyas, Buwan ng Wika, Kadayawan, Teachers' Day), written as Anee (Voice), each
 *    bound to its day (sendOn) with its banner, scored 0 for spam.
 * Only fills: an email that already has a banner keeps it, a celebration already in the line is not added
 * again, and one whose day has passed is left out.
 */
return new class extends Migration
{
    public function up(): void
    {
        if (! Schema::hasTable('as_nl_emails')) {
            return;
        }
        $data = json_decode((string) @file_get_contents(database_path('newsletter/celebrations.json')), true) ?: [];
        $src = fn (string $banner) => '/images/newsletter/celebrations/' . $banner . '.jpg';

        foreach ((array) ($data['heroes'] ?? []) as $series => $h) {
            $row = DB::table('as_nl_emails')->where('deleteStatus', 1)->where('meta', 'like', '%"series":"' . $series . '"%')->first();
            if (! $row) {
                continue;
            }
            $c = json_decode((string) $row->blocks, true) ?: [];
            if (! empty($c['hero']['src'])) {
                continue;
            }
            $c['hero'] = ['src' => $src($h['banner']), 'alt' => (string) $h['alt'], 'link' => 'https://anee.io/'];
            DB::table('as_nl_emails')->where('id', $row->id)->update(['blocks' => json_encode(Renderer::clean($c), JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES)]);
        }

        $today = now('Asia/Manila')->toDateString();
        $pos = (int) DB::table('as_nl_emails')->max('position');
        foreach ((array) ($data['emails'] ?? []) as $i => $e) {
            if ($e['sendOn'] < $today || DB::table('as_nl_emails')->where('meta', 'like', '%"series":"' . $e['series'] . '"%')->exists()) {
                continue;
            }
            $title = $e['series'] . ' · ' . $e['subject'];
            AsNlEmail::create([
                'priority' => 'regular', 'position' => $pos + 10 * ($i + 1), 'kind' => 'builder',
                'title' => mb_substr($title, 0, 190), 'subject' => $e['subject'], 'preheader' => $e['preheader'],
                'blocks' => Renderer::clean(['hero' => ['src' => $src($e['banner']), 'alt' => $e['alt'], 'link' => 'https://anee.io/'], 'blocks' => $e['blocks']]),
                'audience' => ['all'], 'sendOn' => $e['sendOn'], 'status' => 'queued',
                'note' => 'Holiday email for ' . date('M j', strtotime($e['sendOn'])) . '. Bound to ' . date('F j, Y', strtotime($e['sendOn'])) . '.',
                'meta' => ['series' => $e['series'], 'purpose' => 'nurture', 'theme' => 'Holiday broadcast', 'words' => (int) ($e['words'] ?? 0), 'banner' => $e['banner']],
                'createdBy' => 'Celebrations', 'deleteStatus' => 1,
            ]);
        }
    }

    public function down(): void
    {
        // Nothing to undo: the banners stay and the emails are the newsletter's own to edit or delete.
    }
};
