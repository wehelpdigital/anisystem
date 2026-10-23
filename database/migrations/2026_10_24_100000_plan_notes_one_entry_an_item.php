<?php

use App\Models\AsProtocolVersion;
use App\Support\ProtocolNotes;
use Illuminate\Database\Migrations\Migration;

/**
 * A plan's recommendations that went into Rules & notes as one long entry
 * (2026-10-23 to 24) become one entry an item, each heading its own, so they
 * fold one by one (2026-10-24, the owner: the Okra protocol's notes were
 * "still not expandable contractable per item"). Only the entries the plan
 * port wrote (they open with "From the ... Analysis" or "From the Land Prep
 * Planner"); the farmer's own notes are left as written. A title the farmer
 * gave such an entry stays on its opening lines.
 */
return new class extends Migration
{
    public function up(): void
    {
        AsProtocolVersion::query()->where('deleteStatus', 1)->whereNotNull('notes')->orderBy('id')->chunkById(100, function ($rows) {
            foreach ($rows as $v) {
                $entries = ProtocolNotes::entries($v);
                $out = [];
                $changed = false;
                foreach ($entries as $e) {
                    $parts = $e['kind'] === 'text' && preg_match('#^\s*<p><em>From the #', (string) $e['html']) ? ProtocolNotes::split((string) $e['html']) : [];
                    if (count($parts) < 2) {
                        $out[] = $e;

                        continue;
                    }
                    $changed = true;
                    foreach ($parts as $k => $s) {
                        $out[] = ['id' => $e['id'] . ($k ? '_' . $k : ''), 'kind' => 'text', 'title' => mb_substr($s['title'] !== '' ? $s['title'] : ($k === 0 ? $e['title'] : ''), 0, 160), 'html' => $s['html']];
                    }
                }
                if ($changed) {
                    ProtocolNotes::put($v, $out);
                    $v->save();
                }
            }
        });
    }

    public function down(): void
    {
        // The entries stay split; `rules` holds the same words either way.
    }
};
