{{-- THE COMMUNITY'S DESKTOP RAIL — the slot.

     Every community page keeps a column beside its content on a wide
     screen (plaza-css: .plaza-shell / .plaza-side, from 1024px). What goes
     in it is chosen per page — see plaza-rail-cards for the cards — and is
     fetched AFTER the page, and only where the column is actually shown:
     a phone has no column, so it never asks for the cards, and never pays
     for the queries behind them. The fetch is one request to
     community.rail with the card names; the answer is the cards' HTML.

     Expects: $rail — the cards, in order (plaza-rail-cards lists them). --}}
@php $rail = $rail ?? ['chats', 'discussions', 'blog']; @endphp
<div class="plaza-rail-slot" data-rail="{{ implode(',', $rail) }}" data-rail-url="{{ route('community.rail') }}" aria-busy="true"></div>

@once
    @push('scripts')
    <script>
    /* The rail arrives once the page has: fetched only where the column is
       on screen, painted with a short rise, and never re-asked on resize —
       a desk that narrows keeps what it had. */
    (() => {
        const slot = document.querySelector('.plaza-rail-slot');
        if (!slot) return;
        let asked = false;
        const wide = window.matchMedia('(min-width: 1024px)');
        const bindCovers = (root) => {
            /* A broken-image icon is the one outcome nobody should see: the
               blog's covers try the mother site's copy, then the quiet 🌾. */
            root.querySelectorAll('img[data-cover]').forEach((img) => {
                img.addEventListener('error', function onErr() {
                    const alt = img.getAttribute('data-cover-alt');
                    if (alt && img.src !== alt) { img.src = alt; return; }
                    img.removeEventListener('error', onErr);
                    const holder = img.parentElement;
                    if (holder) holder.textContent = '🌾';
                });
            });
        };
        const ask = async () => {
            if (asked || !wide.matches) return;
            asked = true;
            try {
                const res = await fetch(`${slot.dataset.railUrl}?cards=${encodeURIComponent(slot.dataset.rail)}`, {
                    headers: { Accept: 'text/html', 'X-Requested-With': 'XMLHttpRequest' }, credentials: 'same-origin',
                });
                if (!res.ok) throw new Error(String(res.status));
                slot.innerHTML = await res.text();
                bindCovers(slot);
                slot.classList.add('is-in');
            } catch (_) {
                // A rail that could not be fetched is simply not there.
            } finally {
                slot.removeAttribute('aria-busy');
            }
        };
        ask();
        wide.addEventListener('change', ask);
        /* "See all" on the chats card is the messenger's own launcher: the
           dock lists every conversation, and it is already on the page. */
        document.addEventListener('click', (e) => {
            const b = e.target.closest('[data-rail-chats-all]');
            if (!b) return;
            e.preventDefault();
            document.getElementById('msgrLauncher')?.click();
        });
    })();
    </script>
    @endpush
@endonce
