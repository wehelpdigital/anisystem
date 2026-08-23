{{-- A search field that answers as you type.

     One shape for every list in the community, so a farmer learns it once:
     the magnifier on the left, a cross that only exists while there is
     something to clear, a hair of a spinner while the server is thinking,
     and a line underneath that says what came back.

     Expects: $id (the input's id), $placeholder, and optionally $value and
     $label. The page wires what a query DOES through window.plazaLiveSearch —
     this partial owns the typing, the waiting and the words. --}}
@php
    $psId = $id;
    $psValue = (string) ($value ?? '');
    $psLabel = $label ?? $placeholder;
@endphp

<div class="plaza-search" role="search">
    <svg class="ps-ic" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24" aria-hidden="true"><path stroke-linecap="round" stroke-linejoin="round" d="M21 21l-4.35-4.35M17 11a6 6 0 11-12 0 6 6 0 0112 0z"/></svg>
    <input type="search" id="{{ $psId }}" class="form-input ps-input" value="{{ $psValue }}"
           placeholder="{{ $placeholder }}" aria-label="{{ $psLabel }}"
           autocomplete="off" enterkeyhint="search" data-ps-input>
    <span class="ps-spin" data-ps-spin hidden aria-hidden="true"></span>
    <button type="button" class="ps-x" data-ps-clear aria-label="Clear the search" @unless ($psValue !== '') hidden @endunless>
        <svg fill="none" stroke="currentColor" stroke-width="2.4" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" d="M6 6l12 12M18 6L6 18"/></svg>
    </button>
</div>
<p class="ps-note" id="{{ $psId }}Note" role="status" aria-live="polite" hidden></p>

@once
@push('head')
<style>
    .plaza-search { position: relative; display: flex; align-items: center; margin-bottom: .75rem; }
    .ps-ic { position: absolute; left: .9rem; width: 1.15rem; height: 1.15rem; pointer-events: none;
        color: var(--color-gray-400); }
    .ps-input { width: 100%; padding-left: 2.75rem !important; padding-right: 2.6rem !important; }
    /* The browser's own cross sits where ours does and cannot be styled to
       match, so only one of them is drawn. */
    .ps-input::-webkit-search-cancel-button, .ps-input::-webkit-search-decoration { -webkit-appearance: none; display: none; }
    .ps-x { position: absolute; right: .55rem; width: 1.75rem; height: 1.75rem; border-radius: 999px;
        display: inline-flex; align-items: center; justify-content: center;
        color: var(--color-gray-400); background: transparent;
        transition: background .28s cubic-bezier(.22,1,.36,1), color .28s cubic-bezier(.22,1,.36,1); }
    .ps-x:hover { background: var(--color-gray-100); color: var(--color-gray-700); }
    .ps-x svg { width: .9rem; height: .9rem; }
    .ps-x[hidden] { display: none; }
    /* Waiting, said quietly: the field keeps its place and one ring turns. */
    .ps-spin { position: absolute; right: 2.4rem; width: .95rem; height: .95rem; border-radius: 999px;
        border: 2px solid var(--color-gray-200); border-top-color: var(--color-brand-500);
        animation: psSpin .7s linear infinite; }
    .ps-spin[hidden] { display: none; }
    @keyframes psSpin { to { transform: rotate(360deg); } }
    .ps-note { font-size: .78rem; font-weight: 600; color: var(--color-gray-500);
        margin: -.35rem 0 .75rem .15rem; }
    .ps-note[hidden] { display: none; }
    .ps-note b { color: var(--color-gray-800); font-weight: 800; }
    html.dark .ps-x:hover { background: rgb(255 255 255 / .07); color: #cdd8c0; }
    html.dark .ps-note { color: #a8bd93; }
    html.dark .ps-note b { color: #e8efe1; }
    @media (prefers-reduced-motion: reduce) {
        .ps-x { transition: none; }
        /* A ring that stops reads as a page that broke; slow it instead. */
        .ps-spin { animation-duration: 2.4s; }
    }
</style>
@endpush

@push('scripts')
<script>
/* The typing half of a live search, shared by every list that has one.
 *
 *   plazaLiveSearch(document.getElementById('discFind'), async (q) => { ... })
 *
 * The callback is handed the trimmed query and does the fetching and the
 * painting; everything else — waiting a beat after the last keystroke, not
 * asking the same question twice, showing the spinner, the clear cross, and
 * Escape meaning "never mind" — is the same everywhere, so it lives here.
 *
 * Runs are serialised by a token rather than cancelled: a slow answer to an
 * old query must never land on top of a newer one, which is how a search box
 * ends up showing results for half a word.
 */
window.plazaLiveSearch = function (input, run, opts) {
    if (!input || typeof run !== 'function') return;
    const wrap = input.closest('.plaza-search');
    const clear = wrap?.querySelector('[data-ps-clear]');
    const spin = wrap?.querySelector('[data-ps-spin]');
    const wait = (opts && opts.wait) || 280;
    let timer = null;
    let last = input.value.trim();
    let token = 0;

    const paint = () => { if (clear) clear.hidden = input.value === ''; };

    async function fire(force) {
        const q = input.value.trim();
        if (!force && q === last) return;
        last = q;
        const mine = ++token;
        if (spin) spin.hidden = false;
        try {
            await run(q);
        } finally {
            // Only the newest run may put the spinner away.
            if (spin && mine === token) spin.hidden = true;
        }
    }

    input.addEventListener('input', () => {
        paint();
        clearTimeout(timer);
        timer = setTimeout(fire, wait);
    });
    input.addEventListener('keydown', (e) => {
        if (e.key === 'Enter') { e.preventDefault(); clearTimeout(timer); fire(); }
        if (e.key === 'Escape' && input.value !== '') {
            e.preventDefault();
            input.value = '';
            paint();
            clearTimeout(timer);
            fire();
        }
    });
    clear?.addEventListener('click', () => {
        input.value = '';
        paint();
        clearTimeout(timer);
        fire();
        input.focus();
    });
    paint();
};
</script>
@endpush
@endonce
