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
    .ai-rule { border: 0; height: 0; margin: .85em 0;
        border-top: 1px dashed rgb(107 159 61 / .38); }
    html.dark .ai-rule { border-top-color: rgb(168 204 126 / .3); }

    /* A face is drawn at two and a half em and takes up one character.
       An inline picture that size makes its whole line two and a half times
       as tall, and the paragraph opens a canyon above it — which is what a
       face mid-sentence used to do. So the LINE measures this anchor, one
       character wide and one line tall, and the drawing hangs off it out of
       flow: overflowing upward into leading that was already there, and a
       hair below the baseline, touching neither the line above nor the one
       below. Two em read; two and a half carries across a phone held at
       arm's length in the sun, which is where these are looked at. */
    .anee-emo { position: relative; display: inline-block;
        width: 2.6em; height: 1.3em; vertical-align: -.22em; }
    .anee-emo img { position: absolute; left: 50%; bottom: -.5em;
        width: 2.5em; height: 2.5em; transform: translateX(-50%);
        max-width: none; }

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
<script>
(function () {
    if (window.aneeEmoji) return;

    /* The same names the PHP knows, from the same place. */
    const FACES = @json(array_keys(\App\Support\AneeEmoji::FACES));
    const BASE = @json(asset('images/anee/emoji'));
    const known = new Set(FACES);

    /**
     * Swap :anee-name: for the picture, in a string that is ALREADY escaped.
     *
     * Escaping first and putting markup back afterwards is how every renderer
     * in this app handles a model's output; this is the last step of that, so
     * a shortcode naming something unknown is simply left as text.
     */
    window.aneeEmoji = function (html) {
        return String(html == null ? '' : html).replace(
            /:anee-([a-z]{2,14}):/g,
            (all, name) => (known.has(name)
                ? '<span class="anee-emo"><img src="' + BASE + '/' + name + '.png" alt="" loading="lazy"></span>'
                : all)
        );
    };
})();
</script>
@endonce
