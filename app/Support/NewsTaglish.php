<?php

namespace App\Support;

use App\Models\AiSetting;
use App\Services\AiClient;
use App\Services\NewsRoundup;

/**
 * A NEWS STORY'S "SO WHAT" IN TAGLISH (2026-10-17, the owner: "Anong epekto
 * sayo o sa bukid mo ... it is not yet taglish. can you give the english and
 * taglish version in a mini tab in that card").
 *
 * Each story of a farm news roundup ends in a light green card titled
 * NewsRoundup::SO_WHAT: what the news means for the farm. The card carries
 * the English (`text`) and the Taglish (`textTl`); the public page and the
 * Technician's Blog show them as two tabs (partials/lang-tabs), and the news
 * email tells it in Taglish.
 *
 *  - a new roundup writes the Taglish in the same ask as the rest
 *    (NewsRoundup::prompt asks for soWhatTl with rules() below);
 *  - a story the writing left without one, an older page (news:taglish) and
 *    a news email story whose page has none are asked here: ONE Flash ask for
 *    all of them (ask()), system written like the roundup itself.
 *
 * The Taglish says what the English says, in a farmer's own words: no new
 * fact, every number and name kept. It is not a translation of the page
 * (the page stays English), so nothing calls it one.
 */
final class NewsTaglish
{
    /** The longest Taglish kept on a page (the ask says 420 characters at most). */
    public const MAX = 480;

    /** How long the page's Taglish may run (the English "so what" is two or three sentences too). */
    public const PAGE_CHARS = 420;

    /** The rules every Taglish "so what" follows, in the roundup's own ask and in ask(). */
    public static function rules(int $chars): string
    {
        return "- Taglish leaning Tagalog: the everyday words of a farmer in Nueva Ecija or Bukidnon. English only for words farmers already use (farmgate, fertilizer, harvest, presyo ng palay).\n"
            . "- Talk to the farmer directly (ka, mo, natin), like a kapitbahay who reads the news. Two or three short sentences, at most {$chars} characters.\n"
            . "- Say what the English says and nothing more: keep every number, price, date, place, crop, company and agency name exactly as given. Add no fact that is not in the story.\n"
            . "- No doses, no product brands, no emoji, no hashtags, no bold. No dashes between words (use a comma); only a Tagalog word keeps its own hyphen (mag-ani, nag-abono, pag-aani).\n";
    }

    /** Whether a block is a story's "so what" card. */
    public static function isSoWhat(array $b): bool
    {
        return ($b['type'] ?? '') === 'callout' && trim((string) ($b['title'] ?? '')) === NewsRoundup::SO_WHAT;
    }

    /**
     * A roundup's "so what" cards, keyed by block index, each with its
     * story's headline (the H3 above it) and summary (the first text after
     * the headline), so the Taglish is written knowing the story.
     *
     * @return array<int, array{title: string, text: string, means: string}>
     */
    public static function stories(array $blocks): array
    {
        $out = [];
        $title = '';
        $text = '';
        foreach ($blocks as $k => $b) {
            $type = $b['type'] ?? '';
            if ($type === 'heading') {
                $title = (int) ($b['level'] ?? 2) === 3 ? trim((string) ($b['text'] ?? '')) : '';
                $text = '';
            } elseif ($type === 'text' && $text === '') {
                $text = (string) ($b['text'] ?? '');
            } elseif (is_array($b) && self::isSoWhat($b) && trim((string) ($b['text'] ?? '')) !== '') {
                $out[$k] = ['title' => $title, 'text' => $text, 'means' => (string) $b['text']];
            }
        }

        return $out;
    }

    /**
     * Every "so what" card of a page that has no Taglish yet gets one: one
     * ask for all of them, none when every card has it. Other blocks, and
     * cards already filled, are left exactly as they are.
     *
     * @return array{blocks: array, asked: int, filled: int, result: array}
     */
    public static function fill(array $blocks, ?int $refId = null, string $kind = 'news-taglish'): array
    {
        $need = array_filter(self::stories($blocks), fn ($s, $k) => trim((string) ($blocks[$k]['textTl'] ?? '')) === '', ARRAY_FILTER_USE_BOTH);
        if (! $need) {
            return ['blocks' => $blocks, 'asked' => 0, 'filled' => 0, 'result' => []];
        }
        $res = self::ask($need, $refId, $kind, self::PAGE_CHARS);
        foreach ($res['items'] as $k => $words) {
            $blocks[$k]['textTl'] = $words;
        }

        return ['blocks' => $blocks, 'asked' => count($need), 'filled' => count($res['items']), 'result' => $res['result']];
    }

    /**
     * The stories' WHAT IT MEANS in Taglish, in one Flash ask. Returns
     * [story key => plain Taglish] (no markup; the caller escapes it) for the
     * stories it answered, and the ask's result (tokens) for the ledger.
     *
     * @param  array<int|string, array{title?: string, text?: string, means: string}>  $stories
     * @return array{items: array<int|string, string>, result: array}
     */
    public static function ask(array $stories, ?int $refId = null, string $kind = 'news-taglish', int $chars = 300): array
    {
        $ask = array_filter($stories, fn ($s) => trim(strip_tags((string) ($s['means'] ?? ''))) !== '');
        if (! $ask) {
            return ['items' => [], 'result' => []];
        }
        $plain = fn (string $s) => trim(preg_replace('/\s+/u', ' ', html_entity_decode(strip_tags(str_replace('**', '', preg_replace('~\[([^\]]+)\]\([^)]*\)~', '$1', $s) ?? $s)), ENT_QUOTES)) ?? '');
        $lines = [];
        foreach ($ask as $i => $s) {
            $lines[] = 'i=' . $i . "\nTITLE: " . $plain((string) ($s['title'] ?? '')) . "\nSTORY: " . $plain((string) ($s['text'] ?? '')) . "\nWHAT IT MEANS: " . $plain((string) $s['means']);
        }
        $prompt = "You are Anee of anee.io, telling Filipino farmers what today's farm news means for them.\n"
            . "For each story below, write its WHAT IT MEANS part again in Taglish, so a farmer understands it at once:\n"
            . self::rules($chars) . "\n"
            . implode("\n\n", $lines) . "\n\n"
            . 'Return ONLY this JSON, no fences: {"items": [{"i": 0, "tl": "..."}]}';
        $parse = function (string $t): ?array {
            $t = preg_replace('/^```(?:json)?\s*|\s*```$/i', '', trim($t));
            $a = strpos((string) $t, '{');
            $b = strrpos((string) $t, '}');
            $d = $a === false || $b === false ? null : json_decode(substr((string) $t, $a, $b - $a + 1), true);

            return is_array($d) && is_array($d['items'] ?? null) ? $d : null;
        };
        try {
            // Flash (system written, like the roundup itself: the ai-model rule).
            $settings = AiSetting::current()->forField('PH')->fast();
            $result = app(AiClient::class)->askForJson($settings, $prompt, max(2500, 1200 + 380 * count($ask)), $parse,
                ['system' => 'You are Anee, the voice of anee.io, writing for Filipino farmers. You answer only with JSON.', 'timeout' => 90, 'retryAfterTimeout' => false]);
            if ((int) ($result['tokensIn'] ?? 0) > 0) {
                AiUsage::record($kind, 0, 0, $refId ?: null, $settings, $result, 0);
            }
        } catch (\Throwable $e) {
            report($e);

            return ['items' => [], 'result' => []];
        }
        $out = [];
        foreach ((array) ($result['data']['items'] ?? []) as $it) {
            // "3" and 3 are the same key in a PHP array.
            $i = is_array($it) && is_scalar($it['i'] ?? null) ? (string) $it['i'] : '';
            $words = self::tidy((string) ($it['tl'] ?? ''));
            if ($i !== '' && isset($ask[$i]) && mb_strlen($words) >= 20) {
                $out[$i] = $words;
            }
        }

        return ['items' => $out, 'result' => $result];
    }

    /**
     * A model's Taglish made to follow the house rules: no bold or hashes, a
     * dash between words a comma, a Tagalog word's own hyphen kept
     * (ArticleStyle::clean in its Tagalog way), and no longer than MAX.
     */
    public static function tidy(string $words): string
    {
        $words = trim(str_replace(['**', '#'], '', $words));
        $words = ArticleStyle::clean($words, true);

        return self::clip($words, self::MAX);
    }

    /** At most $max characters, cut at a sentence when one ends late enough. */
    public static function clip(string $s, int $max): string
    {
        $s = trim(preg_replace('/\s+/u', ' ', $s) ?? '');
        if (mb_strlen($s) <= $max) {
            return $s;
        }
        $cut = mb_substr($s, 0, $max);
        $dot = max(mb_strrpos($cut, '. ') ?: 0, 0);

        return $dot > $max * 0.5 ? mb_substr($cut, 0, $dot + 1) : rtrim(mb_substr($cut, 0, mb_strrpos($cut, ' ') ?: $max), ' ,;') . '.';
    }
}
