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
    .anee-emo { display: inline-block; width: 2em; height: 2em;
        vertical-align: -.68em; margin: 0 .08em; }
    /* At one and a half em the face is a green smudge — you can see there is
       a picture and not what it is doing, which is the whole point of it.
       Two em is the smallest size where the expression reads. */
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
                ? '<img class="anee-emo" src="' + BASE + '/' + name + '.png" alt="" loading="lazy">'
                : all)
        );
    };
})();
</script>
@endonce
