{{-- Anee saying hello, where a line of prose used to be.

     Every empty chat in the app opens with her name and then had a sentence
     under it explaining that she answers questions about crops — which is the
     one thing nobody needed telling, since they had just opened a thing called
     the AI Technician. Ten seconds of her is worth more than the sentence and
     costs nothing until somebody presses it.

     No title, no duration, no player: a still with one dot in the corner. Tap
     to start, tap to stop, and when it ends it goes back to the still.

     Included by all four chats — the homepage module, the in-season module,
     the floating technician and the Collab Room's tab — so the styles and the
     one handler are written once and the markup can be cloned as a string by
     the surfaces that rebuild their welcome in JavaScript. --}}
@once
<style>
    /* The width is a variable because on a short window the welcome around
       it has nowhere to go: partials/ai-fit spends this before it lets the
       first screen open with a scrollbar. */
    .anee-hello-film { position: relative; width: 100%; max-width: var(--anee-film, 22rem);
        margin: .55rem auto .2rem; border-radius: .9rem; overflow: hidden;
        aspect-ratio: 16 / 9; background: #0d1408;
        box-shadow: 0 10px 26px -18px rgb(0 0 0 / .8); }
    .anee-hello-film video { width: 100%; height: 100%; display: block; object-fit: cover; }
    /* The whole still is the button; the dot sits in the corner so her face
       is never the thing being covered up. */
    .anee-hello-play { position: absolute; inset: 0; display: flex;
        align-items: flex-end; justify-content: flex-start; padding: .55rem;
        border: 0; cursor: pointer; background: rgb(9 14 6 / .14);
        transition: background .28s cubic-bezier(.22, 1, .36, 1); }
    .anee-hello-play:hover { background: rgb(9 14 6 / .05); }
    .anee-hello-play span { display: flex; align-items: center; justify-content: center;
        width: 2.3rem; height: 2.3rem; border-radius: 999px;
        background: var(--color-brand-600); color: #fff;
        box-shadow: 0 6px 18px -6px rgb(0 0 0 / .6), 0 0 0 .28rem rgb(255 255 255 / .22);
        transition: transform .28s cubic-bezier(.22, 1, .36, 1),
                    opacity .28s cubic-bezier(.22, 1, .36, 1); }
    .anee-hello-play:hover span { transform: scale(1.06); }
    .anee-hello-play svg { width: 1rem; height: 1rem; margin-left: .1rem; }
    /* Playing: the tint and the dot go, the button stays — with no player
       underneath, this press is the only pause there is. */
    .anee-hello-film.is-playing .anee-hello-play { background: transparent; }
    .anee-hello-film.is-playing .anee-hello-play span { opacity: 0; transform: scale(.86); }
    @media (prefers-reduced-motion: reduce) {
        .anee-hello-play, .anee-hello-play span { transition: none; }
    }
</style>
<script>
    /* Delegated, and once for the page.
     *
     * Two of these chats rebuild their welcome by writing a string into the
     * thread, so a handler bound to the element at load would be lost the
     * first time somebody started a new session. */
    (() => {
        if (window.__aneeHelloWired) return;
        window.__aneeHelloWired = true;
        document.addEventListener('click', (e) => {
            const btn = e.target.closest('.anee-hello-play');
            if (!btn) return;
            const film = btn.closest('.anee-hello-film');
            const vid = film?.querySelector('video');
            if (!vid) return;
            if (!vid.paused) { vid.pause(); return; }
            film.classList.add('is-playing');
            vid.play?.().catch(() => film.classList.remove('is-playing'));
        });
        document.addEventListener('play', (e) => {
            e.target.closest?.('.anee-hello-film')?.classList.add('is-playing');
        }, true);
        document.addEventListener('pause', (e) => {
            e.target.closest?.('.anee-hello-film')?.classList.remove('is-playing');
        }, true);
        /* Ended: back to the still it started on. currentTime = 0 leaves the
           FIRST frame up, which is not the frame the card is built around;
           load() drops the decoded film and the poster returns. */
        document.addEventListener('ended', (e) => {
            const film = e.target.closest?.('.anee-hello-film');
            if (!film) return;
            film.classList.remove('is-playing');
            e.target.load();
        }, true);
    })();
</script>
@endonce

<div class="anee-hello-film">
    <video playsinline preload="none"
           poster="{{ asset('videos/anee-hello-poster.webp') }}"
           aria-label="A hello from your technician">
        <source src="{{ asset('videos/anee-hello.mp4') }}" type="video/mp4">
    </video>
    <button type="button" class="anee-hello-play" aria-label="Play the hello">
        <span><svg viewBox="0 0 24 24" fill="currentColor" aria-hidden="true"><path d="M8 5.14v13.72L19 12 8 5.14z"/></svg></span>
    </button>
</div>
