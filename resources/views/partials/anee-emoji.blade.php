@once
{{-- ANEE'S FACES, on the browser's side.

     The PHP renderer draws the history and this draws the reply arriving a
     word at a time, so the two have to agree about which names exist. They
     read the same list: it is handed over below, straight from the same
     const the PHP uses.

     Kept a partial rather than copied into each of the four chat surfaces,
     because four copies of a name list is four chances for one of them to be
     a version behind. --}}
<style>
    /* Sized in em so it grows with whatever bubble it lands in, and dropped
       below the baseline so it sits with the words rather than on top of
       them. A face is punctuation here, not an illustration. */
    /* The line she can draw between two parts of an answer. Soft, and the
       app's own green rather than a browser's grey bevel — it is a breath in
       a reply, not a border between two documents. */
    /* The balance, beside the price of the next answer. */
    /* Beside the price where there is room, under it where there is not —
       and centred either way, because the line it belongs to is centred. */
    .ai-bal { font-size: .68rem; font-weight: 700; color: var(--color-gray-400);
        white-space: nowrap; text-align: center; margin-top: .1rem; }
    /* A dot between the price and the purse. Two numbers running together
       read as one number, and the gap the row was meant to give them is not
       there on every composer. */
    .ai-bal::before { content: "·"; margin: 0 .4rem; opacity: .55; }
    /* Standing in a row of switches rather than under the composer: a pill
       like its neighbours, quieter, and with nothing to press. The separator
       dot goes — it was there to part it from a line of text it is no longer
       part of. */
    .ai-bal-chip { display: inline-flex; align-items: center; gap: .3rem;
        padding: .2rem .5rem; border-radius: 999px; border: 1px solid var(--color-gray-200);
        background: var(--color-white); white-space: nowrap; margin-top: 0; }
    .ai-bal-chip::before { content: none; }
    html.dark .ai-bal-chip { background: #151b12; border-color: #2b3a1c; }
    .ai-bal b { font-weight: 900; color: var(--color-brand-700); }
    /* The words "Current credits" became a coin: same meaning, no sentence. */
    .ai-coin { width: .95rem; height: .95rem; flex: none; display: inline-block; vertical-align: -.18em; }
    html.dark .ai-bal { color: #8ea37a; }
    html.dark .ai-bal b { color: #bfe19a; }
    .ai-rule { border: 0; height: 0; margin: .85em 0;
        border-top: 1px dashed rgb(107 159 61 / .38); }
    html.dark .ai-rule { border-top-color: rgb(168 204 126 / .3); }

    /* A face is a character, and it lives in the line like one.
       It went two ways round before this. At two and a half em, in flow, it
       made its own line two and a half times as tall and opened a canyon
       above itself. Hung out of flow to close the canyon, it had to overflow
       something — and what it overflowed was the line above, which on a tight
       paragraph it landed on top of.
       Neither, now: it is an ordinary inline box at a size the line can
       actually carry. Where a face lands the line grows about a quarter of an
       em — a breath, not a canyon — and because the line GROWS, there is no
       direction left for it to overlap in. Text wraps around it the way it
       wraps around a word.
       Nineteen twentieths of an em rather than a full one: big enough to read
       across a phone at arm's length in the sun, small enough that the line
       still reads as a line of writing. */
    .anee-emo { display: inline-block; width: 1.9em; height: 1.9em;
        /* Centred on the words it sits among, not hung off the baseline.
           A fixed baseline offset is a guess that is only right at one font
           size; middle puts the picture's midline on the text's own, which
           stays true whatever the bubble is set in. */
        vertical-align: middle;
        /* Room of its own, above and below.
           A line box grows to hold what is in it and not a pixel more, so a
           face nearly two em tall in a line of one left two pixels between
           its chin and the next line of writing. Vertical margin on an
           inline-block counts toward the line box, so this is leading the
           face brings with it — the neighbouring lines move apart, they are
           not written over. */
        margin: .3em .08em; }
    .anee-emo img { display: block; width: 100%; height: 100%; max-width: none; }

    /* Who said it, when it was not you and not Anee. */
    .anee-said-by {
        display: block; font-size: 10px; font-weight: 700; letter-spacing: .05em;
        text-transform: uppercase; opacity: .72; margin-bottom: .15rem;
    }

    /* ---- room to read -------------------------------------------------
       Her answers are laid out now — paragraphs, steps, a rule between what
       is wrong and what to do — and all of that was written into bubbles
       spaced for one short sentence. The four surfaces call their bubble
       three different things; the rules name all three, which is still one
       place to change rather than four. */
    .aibubble, .ai-float-msg .b, .sai-b { line-height: 1.65; }
    .aibubble p + p, .ai-float-msg .b p + p, .sai-b p + p { margin-top: 1.05em; }
    .aibubble ul, .aibubble ol,
    .ai-float-msg .b ul, .ai-float-msg .b ol,
    .sai-b ul, .sai-b ol { margin: .85em 0; }
    /* A hairline between steps. A numbered list of three things to do in a
       field is read one line at a time, and without a rule the second and
       third run together at a glance. */
    .aibubble li + li, .ai-float-msg .b li + li, .sai-b li + li {
        margin-top: .5em; padding-top: .5em;
        border-top: 1px dashed rgb(107 159 61 / .28); }
    html.dark .aibubble li + li, html.dark .ai-float-msg .b li + li,
    html.dark .sai-b li + li { border-top-color: rgb(168 204 126 / .22); }
    /* Not in a reply of the reader's own, which is white on green and where
       a green hairline is a smudge. */
    .aimsg.me .aibubble li + li, .ai-float-msg.me .b li + li,
    .sai-msg.me .sai-b li + li { border-top-color: rgb(255 255 255 / .28); }
    .aibubble li, .ai-float-msg .b li, .sai-b li { padding-left: .15em; }
    /* At one and a half em the face is a green smudge — you can see there is
       a picture and not what it is doing, which is the whole point of it.
       Two em is the smallest size where the expression reads at all. */
</style>
@include('partials.photo-lightbox')
<script>
(function () {
    if (window.aneeEmoji) return;

    /* The same names the PHP knows, from the same place. */
    const FACES = @json(array_keys(\App\Support\AneeEmoji::FACES));
    const BASE = @json(asset('images/anee/emoji'));
    const known = new Set(FACES);

    const TOKEN = /:anee-[a-z]{2,14}:/g;

    /**
     * Move every face in one block to an edge of it — the twin of
     * AneeEmoji::arrange() in PHP.
     *
     * A face already leading the block stays leading; everything else goes to
     * the end, in the order it was written. A face mid-sentence is nearly
     * always a reaction to the clause it follows, and the end of that
     * paragraph is the nearest honest place for it.
     */
    const arrange = (inner) => {
        const found = inner.match(TOKEN);
        if (!found) return inner;

        // "Leading" means nothing but whitespace and inline opening tags
        // stands before it — <strong> counts as nothing, a word does not.
        const at = inner.search(TOKEN);
        const leads = inner.slice(0, at).replace(/<[^>]*>/g, '').trim() === '';

        let rest = inner.replace(TOKEN, '')
            .replace(/[ \t]{2,}/g, ' ')
            .replace(/\s+([,.;:!?])/g, '$1')
            // A face written inside emphasis leaves the emphasis behind with
            // nothing in it, which renders as a stray gap.
            .replace(/<(strong|b|em|i|u|span)>\s*<\/\1>/gi, '')
            .trim();

        const tokens = found.slice();
        const head = leads ? tokens.shift() : null;

        return ((head ? head + ' ' : '') + rest + (tokens.length ? ' ' + tokens.join(' ') : '')).trim();
    };

    /**
     * The same, over every block: paragraphs, list items and headings.
     *
     * With no block tags in it the string IS one block — which is how the
     * renderers that hand over a line at a time arrive here.
     */
    const toEdges = (html) => (/<(p|li|h[1-6])\b/i.test(html)
        ? html.replace(
            /(<(p|li|h[1-6])\b[^>]*>)([\s\S]*?)(<\/\2>)/gi,
            (all, open, tag, inner, close) => open + arrange(inner) + close
        )
        : arrange(html));

    /**
     * Swap :anee-name: for the picture, in a string that is ALREADY escaped.
     *
     * Escaping first and putting markup back afterwards is how every renderer
     * in this app handles a model's output; this is the last step of that, so
     * a shortcode naming something unknown is simply left as text.
     */
    /**
     * Whose line is this, when the roles cannot say.
     *
     * A conversation has a user and an assistant and nothing else, so when
     * the technician answers from the admin console their line is stored as
     * a user turn with a mark in front of it. Without this the client reads
     * `[technician] ...` in what looks like their own voice.
     *
     * Takes and returns ESCAPED html — it runs alongside the emoji pass, on
     * the same already-safe string.
     */
    const ANEE_TECHNICIAN_MARK = '[technician] ';

    window.aneeSaidBy = function (escapedHtml) {
        const s = String(escapedHtml == null ? '' : escapedHtml);
        // The mark is written before escaping and contains nothing that
        // escaping changes, so it is still itself here.
        const at = s.indexOf(ANEE_TECHNICIAN_MARK);
        // Only when it LEADS the message. Further in it is somebody quoting.
        const lead = s.slice(0, at < 0 ? 0 : at).replace(/<[^>]*>/g, '').trim();
        if (at < 0 || lead !== '') return { who: null, html: s };

        return {
            who: 'Technician',
            html: s.slice(0, at) + s.slice(at + ANEE_TECHNICIAN_MARK.length),
        };
    };

    window.aneeEmoji = function (html) {
        // To the edges first, then to pictures: arranging is done on the
        // shortcodes, which are plain text and easy to move, rather than on
        // the markup they become.
        return toEdges(String(html == null ? '' : html)).replace(
            /:anee-([a-z]{2,14}):/g,
            (all, name) => (known.has(name)
                ? '<span class="anee-emo"><img src="' + BASE + '/' + name + '.png" alt="" loading="lazy"></span>'
                : all)
        );
    };
})();
</script>
@endonce
