@once
{{-- Two things every chat in this app needed and none of them had.

     A clip in a bubble is played in a box the width of the bubble, which on a
     phone is a postage stamp — so any video inside a message grows an expand
     button that opens it full screen. And a quoted reply is a dead end unless
     it takes you to what it is quoting, so tapping a quote walks the thread
     back and lights the message up.

     Delegated and observed rather than bound: every chat here paints its
     bubbles from JS as messages arrive, so anything bound at load would only
     ever meet the first screenful. One file, included by the messenger, the
     Collab Room chat and the discussion room, because the behaviour is the
     same behaviour in all three. --}}
<div class="cvw hidden" id="chatVideoWrap" role="dialog" aria-modal="true" aria-label="Video">
    <button type="button" class="cvw-close" id="cvwClose" aria-label="Close video">✕</button>
    <video id="cvwPlayer" controls playsinline preload="metadata"></video>
</div>

<style>
    /* --- the expand affordance, laid over the bubble's own player --- */
    .cv-holder { position: relative; display: block; }
    .cv-expand { position: absolute; right: .4rem; top: .4rem; z-index: 2;
        display: inline-flex; align-items: center; justify-content: center;
        width: 1.9rem; height: 1.9rem; border-radius: .5rem; border: 0; cursor: pointer;
        background: rgb(0 0 0 / .55); color: #fff;
        transition: background-color .28s cubic-bezier(.22,1,.36,1); }
    .cv-expand:hover { background: rgb(0 0 0 / .75); }
    .cv-expand svg { width: 1rem; height: 1rem; }

    /* --- the full-screen player --- */
    .cvw { position: fixed; inset: 0; z-index: 300; display: flex; align-items: center; justify-content: center;
        background: rgb(0 0 0 / .92); padding: env(safe-area-inset-top) 1rem env(safe-area-inset-bottom);
        animation: cvwIn .28s cubic-bezier(.22,1,.36,1); }
    .cvw.hidden { display: none; }
    .cvw video { max-width: min(100%, 68rem); max-height: 86dvh; width: auto; border-radius: .5rem;
        background: #000; outline: none; }
    .cvw-close { position: absolute; top: calc(env(safe-area-inset-top) + .75rem); right: .9rem;
        width: 2.5rem; height: 2.5rem; border-radius: 999px; border: 0; cursor: pointer;
        background: rgb(255 255 255 / .16); color: #fff; font-size: 1.1rem; font-weight: 700; }
    .cvw-close:hover { background: rgb(255 255 255 / .28); }
    @keyframes cvwIn { from { opacity: 0; } }

    /* --- the message a quote points at, when you arrive --- */
    .cv-found { animation: cvFound 1.6s cubic-bezier(.22,1,.36,1); }
    @keyframes cvFound {
        0%, 100% { box-shadow: 0 0 0 0 rgb(107 159 61 / 0); }
        18%, 62% { box-shadow: 0 0 0 3px rgb(107 159 61 / .55); }
    }
    .cv-quote-link { cursor: pointer; }

    @media (prefers-reduced-motion: reduce) {
        .cvw { animation: none; }
        .cv-expand { transition: none; }
        /* The highlight still has to be seen — it just stops pulsing. */
        .cv-found { animation: none; box-shadow: 0 0 0 3px rgb(107 159 61 / .55); }
    }
</style>

<script>
(function chatMedia() {
    if (window.__chatMediaBooted) return;
    window.__chatMediaBooted = true;

    const wrap = document.getElementById('chatVideoWrap');
    const player = document.getElementById('cvwPlayer');

    /* ---------------- expand any clip in any bubble ---------------- */
    const EXPAND_ICON = '<svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.2">'
        + '<path stroke-linecap="round" stroke-linejoin="round" d="M8 3H3v5M16 3h5v5M8 21H3v-5M16 21h5v-5"/></svg>';

    function dress(video) {
        if (!video || video.dataset.cvDressed) return;
        // The full-screen player is the app's own; a browser that has taken
        // the video into ITS full screen would fight us for it.
        if (video.closest('.cvw')) return;
        /* Some videos are not clips in a bubble. The story studio's preview
           is a surface being edited — wrapping it in a span breaks the frame
           the editor measures against — and a story already fills the screen,
           so a button offering to make it bigger is noise. */
        if (video.hasAttribute('data-cv-skip') || video.closest('[data-cv-skip]')) return;
        video.dataset.cvDressed = '1';

        const holder = document.createElement('span');
        holder.className = 'cv-holder';
        video.parentNode.insertBefore(holder, video);
        holder.appendChild(video);

        const btn = document.createElement('button');
        btn.type = 'button';
        btn.className = 'cv-expand';
        btn.setAttribute('aria-label', 'Play full screen');
        btn.title = 'Play full screen';
        btn.innerHTML = EXPAND_ICON;
        btn.addEventListener('click', (e) => {
            e.preventDefault();
            e.stopPropagation();
            const src = video.currentSrc || video.src || video.querySelector('source')?.src;
            if (!src) return;
            player.src = src;
            const poster = video.getAttribute('poster');
            if (poster) player.setAttribute('poster', poster); else player.removeAttribute('poster');
            // Carry on from where they were rather than starting again.
            player.currentTime = video.currentTime || 0;
            video.pause();
            wrap.classList.remove('hidden');
            document.documentElement.classList.add('overlay-open');
            player.play().catch(() => {});
        });
        holder.appendChild(btn);
    }

    function close() {
        player.pause();
        player.removeAttribute('src');
        player.load();
        wrap.classList.add('hidden');
        document.documentElement.classList.remove('overlay-open');
    }
    document.getElementById('cvwClose')?.addEventListener('click', close);
    wrap?.addEventListener('click', (e) => { if (e.target === wrap) close(); });
    document.addEventListener('keydown', (e) => {
        if (e.key === 'Escape' && !wrap.classList.contains('hidden')) close();
    });

    /* ---------------- a quote takes you to what it quotes ---------------- */
    function findMessage(id) {
        return document.querySelector('[data-msg-id="' + id + '"]')
            || document.querySelector('[data-message-id="' + id + '"]')
            || document.getElementById('msg-' + id);
    }

    document.addEventListener('click', (e) => {
        const quote = e.target.closest('[data-reply-to]');
        if (!quote) return;
        const target = findMessage(quote.getAttribute('data-reply-to'));
        if (!target) {
            // Older than the window the chat is holding: say so rather than
            // doing nothing, which reads as a broken tap.
            window.toast?.('That message is further back than this chat has loaded.');
            return;
        }
        target.scrollIntoView({ behavior: 'smooth', block: 'center' });
        target.classList.remove('cv-found');
        // Restart the highlight even if it is already wearing one.
        void target.offsetWidth;
        target.classList.add('cv-found');
        setTimeout(() => target.classList.remove('cv-found'), 1800);
    });

    /* ---------------- keep up with bubbles that arrive later ------------- */
    const sweep = (root) => {
        (root || document).querySelectorAll('video').forEach(dress);
        // A quote that knows its source becomes a link to it.
        (root || document).querySelectorAll('[data-reply-to]').forEach((q) => q.classList.add('cv-quote-link'));
    };
    sweep(document);

    const obs = new MutationObserver((records) => {
        for (const rec of records) {
            for (const node of rec.addedNodes) {
                if (node.nodeType !== 1) continue;
                if (node.tagName === 'VIDEO') { dress(node); continue; }
                sweep(node);
            }
        }
    });
    obs.observe(document.body, { childList: true, subtree: true });
})();
</script>
@endonce
