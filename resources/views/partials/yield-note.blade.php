{{-- About these yield figures (2026-10-15, the owner): a small folded card
     that says where a variety's average and top t/ha come from, and why a
     farm's harvest can differ. On the public variety finders, the variety
     pages that show a yield, and anything in the app that wants it.
     Needs nothing; $compact = true makes it smaller for tight spots, and
     $flush = true drops its top margin where the spot gives its own.
     Opens and closes on a tap, and remembers that per visitor
     (localStorage, key anee-yield-note). Styles and script come once a page. --}}
@php
    $compact = $compact ?? false;
    $flush = $flush ?? false;
    $ynId = 'yn-' . \Illuminate\Support\Str::lower(\Illuminate\Support\Str::random(8));
@endphp
@once
<style>
    .yn { --yn-bg: #fbf8ee; --yn-line: #ece2c2; --yn-ink: #14210c; --yn-text: #4b5563; --yn-mark: #8a6a00; --yn-mark-bg: #fdf1c4; --yn-hover: #f7f1de;
        margin-top: 1rem; border-radius: 1.1rem; line-height: 1.4; background: var(--yn-bg); border: 1px solid var(--yn-line); box-shadow: 0 14px 30px -28px rgb(20 33 12 / .55); }
    html.dark .yn { --yn-bg: #17190f; --yn-line: #343117; --yn-ink: #ece6cf; --yn-text: #c9c3ab; --yn-mark: #e7c869; --yn-mark-bg: #2f2b12; --yn-hover: #1d1f13; }
    .yn-h { display: flex; align-items: center; gap: .65rem; width: 100%; padding: .8rem 1rem; border: 0; border-radius: inherit; background: none; text-align: left; cursor: pointer;
        font: inherit; font-size: .92rem; font-weight: 800; color: var(--yn-ink); transition: background-color .28s cubic-bezier(.22,1,.36,1); }
    .yn-h:hover { background: var(--yn-hover); }
    .yn-h:focus-visible { outline: 3px solid rgb(134 181 86 / .55); outline-offset: 2px; }
    .yn-mark { flex: none; display: grid; place-items: center; width: 1.7rem; height: 1.7rem; border-radius: 999px; color: var(--yn-mark); background: var(--yn-mark-bg); }
    .yn-mark svg { width: 1rem; height: 1rem; }
    .yn-t { flex: 1; min-width: 0; }
    .yn-t small { display: block; margin-top: .1rem; font-size: .76rem; font-weight: 700; color: var(--yn-text); }
    .yn-chev { flex: none; width: 1.1rem; height: 1.1rem; color: var(--yn-mark); transition: transform .28s cubic-bezier(.22,1,.36,1); }
    .yn.is-open .yn-chev { transform: rotate(180deg); }
    .yn-fold { display: grid; grid-template-rows: 0fr; visibility: hidden;
        transition: grid-template-rows .28s cubic-bezier(.22,1,.36,1), visibility 0s linear .28s; }
    .yn.is-open .yn-fold { grid-template-rows: 1fr; visibility: visible; transition: grid-template-rows .28s cubic-bezier(.22,1,.36,1), visibility 0s linear 0s; }
    .yn-in { overflow: hidden; min-height: 0; }
    .yn-body { padding: 0 1rem .95rem 3.35rem; opacity: 0; transform: translateY(-.25rem);
        transition: opacity .28s cubic-bezier(.22,1,.36,1), transform .28s cubic-bezier(.22,1,.36,1); }
    .yn.is-open .yn-body { opacity: 1; transform: none; }
    .yn-body p { max-width: 74ch; margin: 0; font-size: .86rem; line-height: 1.55; color: var(--yn-text); }
    .yn-body p + p { margin-top: .5rem; }
    .yn-body b { color: var(--yn-ink); font-weight: 800; }
    .yn.is-compact { margin-top: .75rem; border-radius: .9rem; }
    .yn.is-flush { margin-top: 0; }
    .yn.is-compact .yn-h { padding: .6rem .8rem; gap: .55rem; font-size: .84rem; }
    .yn.is-compact .yn-mark { width: 1.45rem; height: 1.45rem; }
    .yn.is-compact .yn-mark svg { width: .85rem; height: .85rem; }
    .yn.is-compact .yn-t small { display: none; }
    .yn.is-compact .yn-body { padding: 0 .8rem .75rem 2.8rem; }
    .yn.is-compact .yn-body p { font-size: .8rem; }
    @media (max-width: 479.98px) { .yn-body { padding-left: 1rem; } .yn.is-compact .yn-body { padding-left: .8rem; } }
    /* A remembered state is put back without a slide. */
    .yn.yn-still .yn-fold, .yn.yn-still .yn-body, .yn.yn-still .yn-chev { transition: none; }
    @media (prefers-reduced-motion: reduce) { .yn-h, .yn-fold, .yn-body, .yn-chev { transition: none; } }
</style>
@endonce
<div class="yn {{ $compact ? 'is-compact' : '' }} {{ $flush ? 'is-flush' : '' }}" data-yn>
    <button type="button" class="yn-h" data-yn-toggle aria-expanded="false" aria-controls="{{ $ynId }}">
        <span class="yn-mark" aria-hidden="true"><svg fill="none" stroke="currentColor" stroke-width="2.2" viewBox="0 0 24 24"><circle cx="12" cy="12" r="9"/><path stroke-linecap="round" d="M12 11v5"/><circle cx="12" cy="7.9" r=".6" fill="currentColor"/></svg></span>
        <span class="yn-t">About these yield figures<small>Where the average and top t/ha come from</small></span>
        <svg class="yn-chev" aria-hidden="true" fill="none" stroke="currentColor" stroke-width="2.4" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" d="M6 9l6 6 6-6"/></svg>
    </button>
    <div class="yn-fold" id="{{ $ynId }}">
        <div class="yn-in">
            <div class="yn-body">
                <p>The <b>average</b> and <b>top</b> yields in tonnes per hectare come from the trials each variety went through to be registered with the NSIC, and from what the seed companies say about their own seed.</p>
                <p>What farmers harvest today can be different. Newer harvest records, the season, the place, the soil, the water and the care all move these numbers over time.</p>
                <p>So use them to compare varieties, not as a promise for your field. Ask your seller or your agriculturist how the variety did on farms near you in the last few seasons.</p>
            </div>
        </div>
    </div>
</div>
@once
<script>
(() => {
    /* The yield note (partials/yield-note): one tap opens or folds it, and
       the visitor's choice comes back on the next page. */
    if (window.aneeYieldNote) return;
    const KEY = 'anee-yield-note';
    const read = () => { try { return localStorage.getItem(KEY); } catch (_) { return null; } };
    const save = (v) => { try { localStorage.setItem(KEY, v); } catch (_) {} };
    const set = (card, open) => {
        card.classList.toggle('is-open', open);
        const b = card.querySelector('[data-yn-toggle]');
        if (b) b.setAttribute('aria-expanded', open ? 'true' : 'false');
    };
    const restore = () => {
        const open = read() === 'open';
        document.querySelectorAll('[data-yn]:not([data-yn-ready])').forEach((card) => {
            card.setAttribute('data-yn-ready', '');
            if (!open) return;
            card.classList.add('yn-still');
            set(card, true);
            requestAnimationFrame(() => requestAnimationFrame(() => card.classList.remove('yn-still')));
        });
    };
    document.addEventListener('click', (e) => {
        const b = e.target.closest && e.target.closest('[data-yn-toggle]');
        const card = b && b.closest('[data-yn]');
        if (!card) return;
        const open = !card.classList.contains('is-open');
        set(card, open);
        save(open ? 'open' : 'closed');
    });
    window.aneeYieldNote = { restore };
    restore();
    if (document.readyState === 'loading') document.addEventListener('DOMContentLoaded', restore, { once: true });
})();
</script>
@endonce
