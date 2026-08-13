{{-- "How are we doing?"

     Shown at most once to anyone who has not answered, and never again once
     they have. It waits for the app to be worth judging — a few days in, and
     a few seconds into the visit — and it takes "not now" for an answer twice
     before it stops asking altogether.

     Included from the app layout; the controller decides whether it is even
     rendered (see ReviewController::shouldAsk). --}}
@if (! empty($askForReview))
<div class="rv-wrap" id="reviewPrompt" hidden aria-hidden="true">
    <div class="rv-backdrop" data-rv-close></div>
    <div class="rv-card" role="dialog" aria-modal="true" aria-labelledby="rvTitle">
        <span class="rv-shine" aria-hidden="true"></span>
        <button type="button" class="rv-x" data-rv-close aria-label="Not now">✕</button>

        <div class="rv-body" id="rvStep1">
            <span class="rv-mark" aria-hidden="true">🌾</span>
            <h3 class="rv-title" id="rvTitle">How is AniSystem treating you?</h3>
            <p class="rv-sub">A moment of your time helps us build the right things. Tap a star.</p>
            <div class="rv-stars" id="rvStars" role="radiogroup" aria-label="Rating">
                @for ($i = 1; $i <= 5; $i++)
                    <button type="button" class="rv-star" data-rv-star="{{ $i }}" role="radio" aria-checked="false" aria-label="{{ $i }} star{{ $i === 1 ? '' : 's' }}">
                        <svg viewBox="0 0 24 24" fill="currentColor"><path d="M12 2.6l2.9 5.9 6.5.95-4.7 4.6 1.1 6.45L12 17.45 6.2 20.5l1.1-6.45-4.7-4.6 6.5-.95L12 2.6z"/></svg>
                    </button>
                @endfor
            </div>
            <textarea id="rvText" class="rv-text" rows="3" maxlength="1500" placeholder="Anything you would change? (optional)"></textarea>
            <div class="rv-acts">
                <button type="button" class="rv-later" data-rv-close>Not now</button>
                <button type="button" class="rv-send" id="rvSend" disabled>Send</button>
            </div>
        </div>

        <div class="rv-body rv-done" id="rvStep2" hidden>
            <span class="rv-mark" aria-hidden="true">🙏</span>
            <h3 class="rv-title">Thank you</h3>
            <p class="rv-sub">That goes straight to the people building this. We will not ask again.</p>
            <div class="rv-acts"><button type="button" class="rv-send" data-rv-close>Close</button></div>
        </div>
    </div>
</div>

<style>
    .rv-wrap { position: fixed; inset: 0; z-index: 245; display: flex; align-items: flex-end; justify-content: center; }
    @media (min-width: 640px) { .rv-wrap { align-items: center; } }
    .rv-wrap[hidden] { display: none; }
    .rv-backdrop { position: absolute; inset: 0; background: rgb(3 7 18 / .55); animation: rvFade .25s ease both; }
    @keyframes rvFade { from { opacity: 0; } }
    .rv-card { position: relative; width: min(30rem, 100%); border-radius: 1.2rem 1.2rem 0 0; overflow: hidden;
        background: var(--color-white, #fff); box-shadow: 0 -20px 60px -25px rgb(0 0 0 / .6);
        animation: rvUp .42s cubic-bezier(.22,1,.36,1) both; padding-bottom: env(safe-area-inset-bottom, 0px); }
    @media (min-width: 640px) { .rv-card { border-radius: 1.2rem; animation-name: rvPop; } }
    @keyframes rvUp { from { transform: translateY(100%); } }
    @keyframes rvPop { from { transform: scale(.94); opacity: 0; } }
    /* One slow sweep of light so the card is noticed, then it settles. */
    .rv-shine { position: absolute; inset: 0; pointer-events: none;
        background: linear-gradient(100deg, transparent 30%, rgb(168 204 126 / .28) 50%, transparent 70%);
        background-size: 250% 100%; animation: rvShine 2.6s ease-out 2; }
    @keyframes rvShine { from { background-position: 140% 0; } to { background-position: -40% 0; } }
    /* Above .rv-body: both are positioned, and without this the body —
       later in the DOM — paints over the corner and swallows every tap
       on the ✕ while "Not now" inside the body keeps working. */
    .rv-x { position: absolute; top: .6rem; right: .7rem; width: 2rem; height: 2rem; border-radius: 999px; z-index: 2;
        color: #9ca3af; display: inline-flex; align-items: center; justify-content: center; cursor: pointer; }
    .rv-x:hover { background: var(--color-gray-100, #f3f4f6); }
    .rv-body { position: relative; padding: 1.4rem 1.3rem 1.2rem; text-align: center; }
    .rv-mark { font-size: 2rem; line-height: 1; }
    .rv-title { font-size: 1.15rem; font-weight: 800; color: var(--color-gray-900, #111827); margin-top: .4rem; }
    .rv-sub { font-size: .85rem; color: var(--color-gray-500, #6b7280); margin-top: .25rem; line-height: 1.5; }
    .rv-stars { display: flex; justify-content: center; gap: .3rem; margin: .9rem 0 .2rem; }
    .rv-star { width: 2.6rem; height: 2.6rem; border-radius: 999px; color: #d1d5db; cursor: pointer;
        display: inline-flex; align-items: center; justify-content: center;
        transition: transform .2s cubic-bezier(.22,1,.36,1), color .2s ease; }
    .rv-star svg { width: 1.9rem; height: 1.9rem; }
    .rv-star:hover { transform: scale(1.12); color: #fbbf24; }
    .rv-star.is-on { color: #f59e0b; }
    .rv-star.is-on svg { filter: drop-shadow(0 2px 6px rgb(245 158 11 / .45)); }
    .rv-text { width: 100%; margin-top: .7rem; border: 1px solid var(--color-gray-200, #e5e7eb); border-radius: .75rem;
        padding: .6rem .7rem; font-size: .88rem; resize: vertical; background: var(--color-white, #fff);
        color: var(--color-gray-800, #1f2937); }
    .rv-acts { display: flex; align-items: center; justify-content: center; gap: .6rem; margin-top: .9rem; }
    .rv-later { padding: .6rem 1rem; border-radius: .8rem; font-size: .85rem; font-weight: 700; color: #6b7280; cursor: pointer; }
    .rv-later:hover { background: var(--color-gray-100, #f3f4f6); }
    .rv-send { padding: .6rem 1.4rem; border-radius: .8rem; font-size: .88rem; font-weight: 800;
        background: #4a7c2a; color: #fff; cursor: pointer; }
    .rv-send:disabled { opacity: .45; cursor: not-allowed; }
    .rv-send:not(:disabled):hover { background: #3d6823; }
    html.dark .rv-card { background: #151b12; }
    html.dark .rv-title { color: #e8efe1; }
    html.dark .rv-text { background: #1c2416; border-color: #2b3a1c; color: #e8efe1; }
    @media (prefers-reduced-motion: reduce) {
        .rv-card, .rv-backdrop, .rv-shine, .rv-star { animation: none; transition: none; }
    }
</style>

<script>
(() => {
    const wrap = document.getElementById('reviewPrompt');
    if (!wrap) return;
    const SEEN_KEY = 'reviewAskedAt';
    const CSRF = document.querySelector('meta[name=csrf-token]')?.content || '';
    let rating = 0;

    // Not the moment the page opens: a prompt that lands mid-tap is a prompt
    // that gets dismissed without being read.
    const DELAY = 9000;
    // And not twice in one week, whatever the server thinks — the server only
    // knows about answers, not about the times we asked and were ignored.
    try {
        const last = parseInt(localStorage.getItem(SEEN_KEY) || '0', 10);
        if (last && Date.now() - last < 7 * 864e5) return;
    } catch (_) { /* private mode: ask anyway */ }

    setTimeout(() => {
        // Never over an open sheet, a lightbox or the drawing pad.
        if (document.querySelector('.sheet.is-open, .note-lb.is-open, .draw-modal.show')) return;
        wrap.hidden = false;
        wrap.setAttribute('aria-hidden', 'false');
        try { localStorage.setItem(SEEN_KEY, String(Date.now())); } catch (_) { /* fine */ }
        window.registerOverlay?.('reviewPrompt', close);
    }, DELAY);

    function close() {
        wrap.hidden = true;
        wrap.setAttribute('aria-hidden', 'true');
        window.unregisterOverlay?.('reviewPrompt');
    }

    function paint() {
        wrap.querySelectorAll('.rv-star').forEach((b) => {
            const on = parseInt(b.getAttribute('data-rv-star'), 10) <= rating;
            b.classList.toggle('is-on', on);
            b.setAttribute('aria-checked', on ? 'true' : 'false');
        });
        document.getElementById('rvSend').disabled = rating < 1;
    }

    wrap.addEventListener('click', async (e) => {
        const star = e.target.closest('.rv-star');
        if (star) { rating = parseInt(star.getAttribute('data-rv-star'), 10); paint(); return; }

        if (e.target.closest('[data-rv-close]')) {
            // A dismissal is worth recording: two of them and we stop asking.
            fetch(@json(route('review.dismiss')), {
                method: 'POST',
                headers: { 'X-CSRF-TOKEN': CSRF, Accept: 'application/json' },
                credentials: 'same-origin',
            }).catch(() => {});
            close();
            return;
        }

        if (e.target.closest('#rvSend')) {
            const btn = e.target.closest('#rvSend');
            btn.disabled = true;
            const body = new FormData();
            body.append('rating', String(rating));
            body.append('review', document.getElementById('rvText').value.trim());
            try {
                const res = await fetch(@json(route('review.store')), {
                    method: 'POST',
                    headers: { 'X-CSRF-TOKEN': CSRF, Accept: 'application/json' },
                    body, credentials: 'same-origin',
                });
                if (!res.ok) throw new Error('Could not send that.');
                document.getElementById('rvStep1').hidden = true;
                document.getElementById('rvStep2').hidden = false;
            } catch (err) {
                btn.disabled = false;
                window.toast?.('Could not send that just now.', 'error');
            }
        }
    });
})();
</script>
@endif
