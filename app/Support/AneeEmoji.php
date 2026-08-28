<?php

namespace App\Support;

/**
 * Anee's own faces, and the shortcode that puts one in an answer.
 *
 * Fifty-six drawings of the same technician, cut from one sheet. She writes
 * `:anee-happy:` and the renderers swap it for the picture — the same list on
 * both sides, so the PHP that renders history and the JavaScript that renders
 * a streaming reply can never disagree about which names exist.
 *
 * `:anee-` prefixed rather than a bare `:happy:` on purpose: a bare one would
 * fire on a timestamp, and it would be indistinguishable from the emoji
 * shortcodes people already type at each other.
 */
class AneeEmoji
{
    /**
     * Every face on the sheet, in the order it was cut, with what it is.
     *
     * The description is what Anee is shown, so it has to be about the FACE —
     * she is choosing from words alone and cannot see the drawing.
     */
    public const FACES = [
        'happy' => 'beaming, mouth open',
        'smile' => 'a plain, easy smile',
        'wink' => 'winking',
        'love' => 'hearts for eyes',
        'starstruck' => 'stars for eyes, delighted',
        'amazed' => 'stars for eyes, mouth open',
        'teary' => 'welling up, frightened',
        'wince' => 'one eye shut, wincing',
        'angry' => 'genuinely cross',
        'gritted' => 'teeth gritted, exasperated',
        'worried' => 'worried',
        'blank' => 'no expression at all',
        'disappointed' => 'eyes shut, disappointed',
        'unamused' => 'not amused',
        'thinking' => 'hand to chin, thinking',
        'plain' => 'neutral, listening',
        'grin' => 'eyes shut, grinning',
        'sleepy' => 'wide-eyed and only just awake',
        'idea' => 'a lightbulb, just thought of something',
        'flat' => 'flat, unimpressed',
        'blushing' => 'blushing hard',
        'shocked' => 'eyes wide, mouth open',
        'content' => 'eyes shut, content',
        'sleeping' => 'asleep',
        'shy' => 'eyes shut, shy and pleased',
        'thumbsup' => 'thumbs up',
        'unsure' => 'unsure',
        'uneasy' => 'uneasy',
        'serious' => 'serious',
        'salute' => 'saluting',
        'leaf' => 'holding up a leaf, winking',
        'flower' => 'offering a flower',
        'concerned' => 'concerned',
        'oops' => 'hand to mouth, caught out',
        'alarmed' => 'alarmed',
        'crying' => 'crying',
        'glum' => 'glum',
        'laughing' => 'laughing till she cries',
        'kiss' => 'blowing a kiss',
        'pucker' => 'pursed lips',
        'facepalm' => 'hand over face',
        'weary' => 'worn out',
        'heart' => 'holding a heart',
        'smirk' => 'smirking',
        'relieved' => 'relieved',
        'yes' => 'a green tick — yes, that is right',
        'choose' => 'a tick and a cross — one or the other',
        'no' => 'a red cross — no, not that',
        'wave' => 'waving hello',
        'doubtful' => 'doubtful',
        'meh' => 'meh',
        'calm' => 'calm',
        'sad' => 'sad',
        'whisper' => 'hand beside mouth, letting you in on something',
        'delighted' => 'eyes shut, delighted',
        'cheer' => 'cheering',
    ];

    /**
     * The ones she is told about.
     *
     * Fifty-six names in a system prompt is a paragraph nobody reads, model or
     * person, and a model given fifty-six options picks the first four. These
     * are the faces a technician actually needs in a working conversation —
     * the rest are cut, named and served, for an admin who wants them.
     */
    public const CORE = [
        'smile', 'happy', 'wink', 'thinking', 'idea', 'yes', 'no', 'thumbsup',
        'worried', 'concerned', 'oops', 'sad', 'salute', 'wave', 'leaf', 'grin',
    ];

    public static function has(string $name): bool
    {
        return isset(self::FACES[$name]);
    }

    /** The URL of one face. */
    public static function url(string $name): string
    {
        return asset('images/anee/emoji/' . $name . '.png');
    }

    /**
     * Swap every `:anee-name:` in already-escaped HTML for its picture.
     *
     * Runs on the escaped string, not the raw one: the shortcode is plain
     * text and survives escaping unchanged, so this can go last and nothing a
     * model writes can reach the page as markup.
     */
    public static function render(string $html): string
    {
        return (string) preg_replace_callback(
            '/:anee-([a-z]{2,14}):/',
            function ($m) {
                if (! self::has($m[1])) {
                    return $m[0];
                }

                return '<img class="anee-emo" src="' . e(self::url($m[1]))
                    . '" alt="' . e(self::FACES[$m[1]]) . '" loading="lazy">';
            },
            $html
        );
    }

    /** The paragraph the model is given. */
    public static function promptLine(): string
    {
        $list = [];
        foreach (self::CORE as $name) {
            $list[] = ':anee-' . $name . ': (' . self::FACES[$name] . ')';
        }

        return "--- Your face ---\n"
            . "You have a face, and you may show it. Writing :anee-NAME: puts a\n"
            . "small picture of yourself wearing that expression into the reply.\n"
            . "The ones to use:\n  " . implode("\n  ", $list) . "\n"
            . "At most ONE in a reply, and only where it belongs: at the end of a\n"
            . "greeting, a thank-you, a piece of good news or a warning. Never in\n"
            . "the middle of a diagnosis, never in a list, and never instead of\n"
            . "saying the thing. A reply that is doing serious work — a spray\n"
            . "decision, a loss, a mistake to own up to — is better with none.";
    }
}
