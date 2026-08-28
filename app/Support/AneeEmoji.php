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
     * The ones she is told about, grouped by the moment they belong to.
     *
     * A flat list of names is why she wore two faces. Counted across every
     * answer on this install: smile eight times, thinking five, eight others
     * once each — ten of fifty-six. A model handed a flat list picks from the
     * top of it and keeps picking, and no instruction to "vary" beats the
     * shape of the list itself.
     *
     * Grouped, the choice is a different one: find the moment — she is
     * pleased, she is worried, she got something wrong — and the faces for it
     * are the only ones in front of her. The heading is what she reads; the
     * names are what she writes.
     */
    public const GROUPS = [
        'Hello, and goodbye' => ['wave', 'salute'],
        'Pleased, impressed, celebrating with them' => [
            'happy', 'grin', 'cheer', 'delighted', 'starstruck', 'amazed',
            'laughing', 'thumbsup', 'love', 'heart', 'flower',
        ],
        'Warm and ordinary' => ['smile', 'wink', 'calm', 'relieved', 'leaf', 'content'],
        'Working something out' => ['thinking', 'idea', 'unsure', 'doubtful', 'whisper'],
        'Yes, no, or one of the two' => ['yes', 'no', 'choose'],
        'Bad news, worry, or bad luck of theirs' => [
            'concerned', 'worried', 'alarmed', 'shocked', 'sad', 'teary',
            'wince', 'weary', 'glum',
        ],
        'A mistake of your own' => ['oops', 'facepalm', 'blushing'],
        'Grave, and meant to be' => ['serious'],
    ];

    /** Every face she is told about, flattened — for anything that needs the list. */
    public const CORE = [
        'wave', 'salute',
        'happy', 'grin', 'cheer', 'delighted', 'starstruck', 'amazed',
        'laughing', 'thumbsup', 'love', 'heart', 'flower',
        'smile', 'wink', 'calm', 'relieved', 'leaf', 'content',
        'thinking', 'idea', 'unsure', 'doubtful', 'whisper',
        'yes', 'no', 'choose',
        'concerned', 'worried', 'alarmed', 'shocked', 'sad', 'teary',
        'wince', 'weary', 'glum',
        'oops', 'facepalm', 'blushing',
        'serious',
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
        $block = '';
        foreach (self::GROUPS as $heading => $names) {
            $block .= '  ' . $heading . ":\n";
            foreach ($names as $name) {
                $block .= '    :anee-' . $name . ': -- ' . self::FACES[$name] . "\n";
            }
        }

        return "--- Your face, and how a reply looks ---\n"
            . "You have a face and you may show it. Writing :anee-NAME: puts a\n"
            . "small picture of yourself wearing that expression into the reply.\n"
            . "Find the moment first, then take the face from it:\n"
            . $block
            . "\n"
            . "How to wear them:\n"
            . "  - Two or three in a reply that has two or three beats to it: the\n"
            . "    reaction at the top, the turn in the middle, the warning at the\n"
            . "    end. One is plenty for a short answer. None at all is fine for a\n"
            . "    grave one.\n"
            . "  - Never the same face twice in one reply, and do not open every\n"
            . "    reply with the one you opened the last one with. :anee-smile: is\n"
            . "    not a default -- it is what you wear when nothing stronger fits.\n"
            . "  - Match the face to what you are actually saying. Celebrating a\n"
            . "    harvest is :anee-cheer: or :anee-starstruck:, not a polite smile;\n"
            . "    bad news is :anee-concerned: or :anee-sad:; your own mistake is\n"
            . "    :anee-oops:.\n"
            . "  - Ordinary emoji are welcome too where they carry meaning rather\n"
            . "    than decorate: a crop, the weather, a pest, water, money.\n"
            . "\n"
            . "Lay a reply out so it can be read on a phone in a field:\n"
            . "  - Short paragraphs with a blank line between them.\n"
            . "  - **Bold** the thing that matters most -- a rate, a date, a warning.\n"
            . "  - Bullets or numbered steps when there is more than one thing to do.\n"
            . "  - A line of three dashes (---) on its own draws a divider, for when\n"
            . "    the answer turns from what is wrong to what to do about it.\n"
            . "\n"
            . "The one rule that does not bend: a face is never INSTEAD of saying\n"
            . "the thing. Bad news is still said plainly and early, a diagnosis is\n"
            . "still a diagnosis, and a reply about a loss or a mistake of your own\n"
            . "is better with a plain word than a picture.";
    }
}
