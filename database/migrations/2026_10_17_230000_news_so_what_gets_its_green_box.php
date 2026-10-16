<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

/**
 * What a news story means for the farmer (2026-10-17, the owner: "a light
 * green container with border radius for every part that says para sa bukid
 * mo and change para sa bukid mo to Anong epekto nito sayo ... also edit the
 * existing news").
 *
 * The news pages already written: their callout title "What it means for your
 * farm" becomes NewsRoundup::SO_WHAT (the Tech Blog draws them again, its
 * VERSION went up). The news emails not yet sent: the "Para sa bukid mo:"
 * paragraph inside each story card moves to the card's own green box
 * (story.meansTitle + story.means, drawn by the Renderer).
 */
return new class extends Migration
{
    private const TITLE = 'Anong epekto nito sayo';

    public function up(): void
    {
        if (Schema::hasTable('as_site_pages')) {
            DB::table('as_site_pages')->where('blocks', 'like', '%What it means for your farm%')->orderBy('id')->get()
                ->each(function ($p) {
                    $blocks = preg_replace('~("title"\s*:\s*)"What it means for your farm"~', '$1"' . self::TITLE . '"', (string) $p->blocks);
                    if ($blocks !== null && $blocks !== $p->blocks) {
                        DB::table('as_site_pages')->where('id', $p->id)->update(['blocks' => $blocks]);
                    }
                });
        }
        if (! Schema::hasTable('as_nl_emails')) {
            return;
        }
        DB::table('as_nl_emails')->where('blocks', 'like', '%Para sa bukid mo%')->where('status', '!=', 'sent')->orderBy('id')->get()
            ->each(function ($row) {
                $c = json_decode((string) $row->blocks, true);
                if (! is_array($c) || ! is_array($c['blocks'] ?? null)) {
                    return;
                }
                $moved = 0;
                foreach ($c['blocks'] as $i => $b) {
                    if (($b['type'] ?? '') !== 'story' || ! preg_match('~<p>\s*<(?:b|strong)>\s*Para sa bukid mo:?\s*</(?:b|strong)>\s*(.*?)</p>~is', (string) ($b['html'] ?? ''), $m)) {
                        continue;
                    }
                    $c['blocks'][$i]['html'] = trim(str_replace($m[0], '', (string) $b['html']));
                    $c['blocks'][$i]['meansTitle'] = self::TITLE;
                    $c['blocks'][$i]['means'] = '<p>' . trim($m[1]) . '</p>';
                    $moved++;
                }
                if ($moved) {
                    DB::table('as_nl_emails')->where('id', $row->id)
                        ->update(['blocks' => json_encode($c, JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES), 'updated_at' => now()]);
                }
            });
    }

    public function down(): void
    {
        // The words stay as the owner asked; nothing to undo.
    }
};
