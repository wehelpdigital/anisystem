{{-- The crop as a tag (2026-10-08): a button that names the crop, and a
     sheet of every crop the catalogue covers, shelf by shelf, with a search.
     Shared by the pest and disease finders, the weed control helper and the
     weeds catalogue, on the public site and in the app's Field helpers.

     Expects $id (unique on the page), $crops (key => [local, English, icon,
     shelf, count or null]), $current (a key of $crops), $title, and $noun
     (what the count counts: "pests"; null for no count), and may take $all
     ([label, line]: a first row for no crop at all, key ""). Picking a crop sets
     data-crop on the button, redraws it and sends a "croppick" event from it
     (detail.crop); window.cropPickSet(button, key) redraws it from outside. --}}
@php
    $F = \App\Support\FieldCatalogue::class;
    $all = $all ?? null;
    $allRow = $all ? [$all[0], $all[1], '🌱', null, null] : null;
    $curKey = isset($crops[$current]) ? $current : ($all ? '' : array_key_first($crops));
    $cur = $curKey === '' ? $allRow : $crops[$curKey];
    $byShelf = collect($crops)->groupBy(fn ($c) => $c[3], true);
@endphp
@once
@push('head')
    <style>
        /* The tag: the crop's picture, its names, a chevron. Taller than a
           chip, so a thumb finds it, and it says it opens something. */
        .cp-tag { width: 100%; display: flex; align-items: center; gap: .7rem; margin-top: .7rem; padding: .55rem .7rem .55rem .6rem; border-radius: 1rem; text-align: left;
            background: #f6f8f3; border: 1px solid #dfe8d4; color: #14210c; cursor: pointer;
            transition: border-color .28s cubic-bezier(.22,1,.36,1), background-color .28s cubic-bezier(.22,1,.36,1), box-shadow .28s cubic-bezier(.22,1,.36,1); }
        .cp-tag:hover { border-color: #a8cc7e; background: #fff; box-shadow: 0 10px 24px -20px rgb(20 33 12 / .6); }
        .cp-tag:focus-visible { outline: 3px solid #f5c518; outline-offset: 2px; }
        .cp-ico { flex: none; width: 2.5rem; height: 2.5rem; border-radius: .8rem; display: grid; place-items: center; font-size: 1.35rem; background: #fff; border: 1px solid #e5ebdf; }
        .cp-t { min-width: 0; flex: 1; }
        .cp-t b { display: block; font-family: var(--font-heading); font-weight: 800; font-size: 1rem; line-height: 1.25; }
        .cp-t small { display: block; font-size: .78rem; color: #6b7280; line-height: 1.35; }
        .cp-chev { flex: none; display: inline-flex; align-items: center; gap: .3rem; padding: .3rem .55rem; border-radius: 999px; font-size: .72rem; font-weight: 800; color: #2d5016; background: #e4efd4; }
        .cp-chev svg { width: .8rem; height: .8rem; }
        .cp-tag.is-bump .cp-ico { animation: cpBump .42s cubic-bezier(.22,1,.36,1); }
        @keyframes cpBump { 40% { transform: scale(1.14) rotate(-6deg); } }

        /* The sheet: from the bottom on a phone, in the middle on a wide screen. */
        .cp-sheet { padding: 0; border: 0; background: transparent; max-width: none; max-height: none; width: 100%; height: 100%; margin: 0; inset: 0; overflow: hidden; }
        .cp-sheet::backdrop { background: rgb(10 18 6 / .5); animation: cpFade .28s cubic-bezier(.22,1,.36,1); }
        .cp-sheet.is-closing::backdrop { animation: cpFade .22s cubic-bezier(.22,1,.36,1) reverse forwards; }
        .cp-box { position: absolute; left: 0; right: 0; bottom: 0; max-height: 86vh; max-height: 86dvh; display: flex; flex-direction: column; border-radius: 1.4rem 1.4rem 0 0;
            background: #fff; box-shadow: 0 -20px 50px -20px rgb(10 18 6 / .5); animation: cpUp .34s cubic-bezier(.22,1,.36,1); }
        .cp-sheet.is-closing .cp-box { animation: cpUp .24s cubic-bezier(.22,1,.36,1) reverse forwards; }
        @media (min-width: 640px) {
            .cp-box { left: 50%; top: 50%; bottom: auto; right: auto; width: min(34rem, calc(100vw - 2rem)); max-height: min(44rem, 86vh); border-radius: 1.4rem;
                transform: translate(-50%, -50%); animation-name: cpPop; }
            .cp-sheet.is-closing .cp-box { animation-name: cpPop; }
        }
        @keyframes cpFade { from { opacity: 0; } }
        @keyframes cpUp { from { transform: translateY(100%); } }
        @keyframes cpPop { from { opacity: 0; transform: translate(-50%, -46%) scale(.97); } }
        .cp-box:focus { outline: none; }
        .cp-grip { width: 2.6rem; height: .3rem; margin: .55rem auto 0; border-radius: 999px; background: #d7dfcd; }
        @media (min-width: 640px) { .cp-grip { display: none; } }
        .cp-head { display: flex; align-items: center; gap: .6rem; padding: .7rem 1rem .5rem 1.15rem; }
        .cp-head h2 { flex: 1; font-family: var(--font-heading); font-weight: 800; font-size: 1.1rem; color: #14210c; }
        .cp-x { flex: none; width: 2.4rem; height: 2.4rem; border-radius: 999px; display: grid; place-items: center; color: #4b5563; background: #f3f5ef;
            transition: background-color .28s cubic-bezier(.22,1,.36,1); }
        .cp-x:hover { background: #e7ecdf; }
        .cp-x svg { width: 1.1rem; height: 1.1rem; }
        .cp-find { margin: 0 1rem .6rem; display: flex; align-items: center; gap: .5rem; padding: 0 .8rem; border-radius: .9rem; background: #f6f8f3; border: 1px solid #e1e9d7; }
        .cp-find:focus-within { border-color: #86b556; background: #fff; }
        .cp-find svg { flex: none; width: 1.05rem; height: 1.05rem; color: #6b9f3d; }
        .cp-find input { flex: 1; min-width: 0; min-height: 2.75rem; border: 0; background: transparent; font-size: 1rem; color: #14210c; outline: none; box-shadow: none; }
        .cp-list { flex: 1; overflow-y: auto; overscroll-behavior: contain; padding: 0 .6rem 1rem; }
        .cp-shelf { margin-top: .35rem; }
        .cp-shelf > b { position: sticky; top: 0; z-index: 1; display: flex; align-items: center; gap: .45rem; padding: .55rem .55rem .35rem; font-size: .7rem; font-weight: 800;
            letter-spacing: .08em; text-transform: uppercase; color: hsl(var(--g) 45% 30%); background: #fff; }
        .cp-shelf > b svg { width: 1rem; height: 1rem; }
        .cp-row { width: 100%; display: flex; align-items: center; gap: .7rem; padding: .5rem .55rem; border-radius: .9rem; text-align: left; color: #14210c;
            transition: background-color .28s cubic-bezier(.22,1,.36,1); }
        .cp-row:hover { background: #f4f8ef; }
        .cp-row .cp-ico { width: 2.3rem; height: 2.3rem; font-size: 1.2rem; }
        .cp-row .cp-t b { font-size: .95rem; }
        .cp-n { flex: none; font-size: .72rem; font-weight: 800; padding: .2rem .5rem; border-radius: 999px; color: #3d6823; background: #eef5e6; white-space: nowrap; }
        .cp-on { flex: none; width: 1.4rem; height: 1.4rem; border-radius: 999px; display: grid; place-items: center; color: #3b2f00; background: #f5c518;
            opacity: 0; transform: scale(.5); transition: opacity .28s cubic-bezier(.22,1,.36,1), transform .28s cubic-bezier(.22,1,.36,1); }
        .cp-on svg { width: .8rem; height: .8rem; }
        .cp-row[aria-selected="true"] { background: #eef5e6; }
        .cp-row[aria-selected="true"] .cp-on { opacity: 1; transform: none; }
        .cp-none { display: none; padding: 1.4rem 1rem; text-align: center; font-size: .9rem; color: #6b7280; }
        .cp-none.is-on { display: block; }
        html.cp-lock { overflow: hidden; }
        @media (prefers-reduced-motion: reduce) {
            .cp-tag, .cp-x, .cp-row, .cp-on { transition: none; }
            .cp-box, .cp-sheet::backdrop, .cp-sheet.is-closing .cp-box, .cp-sheet.is-closing::backdrop, .cp-tag.is-bump .cp-ico { animation: none; }
        }
        /* In the app at night (Field helpers). */
        html.dark .cp-tag { background: #1c2616; border-color: #2b3a1c; color: #e8efe1; }
        html.dark .cp-tag:hover { background: #22301a; }
        html.dark .cp-ico { background: #151b12; border-color: #2b3a1c; }
        html.dark .cp-t small { color: #b9caa8; }
        html.dark .cp-chev { background: #2b3a1c; color: #d5e3c5; }
        html.dark .cp-box { background: #151b12; }
        html.dark .cp-head h2, html.dark .cp-row { color: #e8efe1; }
        html.dark .cp-x { background: #1c2616; color: #d5e3c5; }
        html.dark .cp-find { background: #1c2616; border-color: #2b3a1c; }
        html.dark .cp-find input { color: #e8efe1; }
        html.dark .cp-shelf > b { background: #151b12; color: hsl(var(--g) 45% 70%); }
        html.dark .cp-row:hover, html.dark .cp-row[aria-selected="true"] { background: #1c2616; }
        html.dark .cp-n { background: #2b3a1c; color: #d5e3c5; }
    </style>
@endpush
@push('scripts')
<script>
(() => {
    /* The crop tags and their sheets: one controller for every pair on the page. */
    const lock = (on) => document.documentElement.classList.toggle('cp-lock', on);
    const reduce = () => matchMedia('(prefers-reduced-motion: reduce)').matches;
    // Redraw a tag from a crop key, as the sheet has it.
    window.cropPickSet = (tag, key) => {
        const dlg = tag && document.getElementById(tag.dataset.cpOpen);
        const row = dlg?.querySelector('.cp-row[data-crop="' + CSS.escape(key) + '"]');
        if (!row) return false;
        tag.dataset.crop = key;
        tag.querySelector('.cp-ico').textContent = row.dataset.icon || '';
        tag.querySelector('.cp-t b').textContent = row.dataset.local;
        const sm = tag.querySelector('.cp-t small');
        sm.textContent = row.dataset.en !== row.dataset.local ? row.dataset.en : '';
        sm.hidden = !sm.textContent;
        dlg.querySelectorAll('.cp-row').forEach((r) => r.setAttribute('aria-selected', String(r === row)));
        return true;
    };
    const close = (dlg) => {
        if (!dlg.open || dlg.classList.contains('is-closing')) return;
        const done = () => { dlg.classList.remove('is-closing'); dlg.close(); lock(false); };
        if (reduce()) { done(); return; }
        dlg.classList.add('is-closing');
        setTimeout(done, 230);
    };
    const filter = (dlg) => {
        const q = dlg.querySelector('.cp-find input').value.toLowerCase().trim();
        let any = false;
        dlg.querySelectorAll('.cp-shelf').forEach((sh) => {
            let n = 0;
            sh.querySelectorAll('.cp-row').forEach((r) => { const on = !q || r.dataset.q.includes(q); r.hidden = !on; if (on) n++; });
            sh.hidden = !n;
            any = any || n > 0;
        });
        dlg.querySelector('.cp-none').classList.toggle('is-on', !any);
    };
    document.addEventListener('click', (e) => {
        const tag = e.target.closest('[data-cp-open]');
        if (tag) {
            const dlg = document.getElementById(tag.dataset.cpOpen);
            if (!dlg || !dlg.showModal) return;
            dlg._tag = tag;
            dlg.querySelector('.cp-find input').value = '';
            filter(dlg);
            dlg.showModal();
            lock(true);
            // The chosen crop in view; the search box only where typing is easy (not on a phone, where it raises the keyboard).
            dlg.querySelector('.cp-row[aria-selected="true"]')?.scrollIntoView({ block: 'center' });
            if (matchMedia('(pointer: fine)').matches) dlg.querySelector('.cp-find input').focus();
            else dlg.querySelector('.cp-box').focus({ preventScroll: true });
            return;
        }
        const dlg = e.target.closest('.cp-sheet');
        if (!dlg) return;
        if (e.target === dlg || e.target.closest('.cp-x')) { close(dlg); return; }
        const row = e.target.closest('.cp-row');
        if (row && dlg._tag) {
            const t = dlg._tag, changed = t.dataset.crop !== row.dataset.crop;
            window.cropPickSet(t, row.dataset.crop);
            close(dlg);
            if (changed) {
                t.classList.remove('is-bump'); void t.offsetWidth; t.classList.add('is-bump');
                t.dispatchEvent(new CustomEvent('croppick', { bubbles: true, detail: { crop: row.dataset.crop } }));
            }
            t.focus({ preventScroll: true });
        }
    });
    document.addEventListener('input', (e) => { const dlg = e.target.closest('.cp-sheet'); if (dlg) filter(dlg); });
    document.addEventListener('cancel', (e) => { if (e.target.classList?.contains('cp-sheet')) { e.preventDefault(); close(e.target); } }, true);
})();
</script>
@endpush
@endonce
<button type="button" class="cp-tag" data-cp-open="{{ $id }}" data-crop="{{ $curKey }}" aria-haspopup="dialog">
    <span class="cp-ico" aria-hidden="true">{{ $cur[2] ?? '' }}</span>
    <span class="cp-t"><b>{{ $cur[0] ?? '' }}</b><small @if (($cur[0] ?? '') === ($cur[1] ?? '')) hidden @endif>{{ ($cur[0] ?? '') !== ($cur[1] ?? '') ? $cur[1] : '' }}</small></span>
    <span class="cp-chev">Change<svg fill="none" stroke="currentColor" stroke-width="3" viewBox="0 0 24 24" aria-hidden="true"><path stroke-linecap="round" stroke-linejoin="round" d="M19 9l-7 7-7-7"/></svg></span>
</button>
<dialog class="cp-sheet" id="{{ $id }}" aria-label="{{ $title }}">
    <div class="cp-box" tabindex="-1">
        <div class="cp-grip" aria-hidden="true"></div>
        <div class="cp-head">
            <h2>{{ $title }}</h2>
            <button type="button" class="cp-x" aria-label="Close"><svg fill="none" stroke="currentColor" stroke-width="2.4" viewBox="0 0 24 24" aria-hidden="true"><path stroke-linecap="round" d="M6 6l12 12M18 6L6 18"/></svg></button>
        </div>
        <label class="cp-find">
            <svg fill="none" stroke="currentColor" stroke-width="2.2" viewBox="0 0 24 24" aria-hidden="true"><circle cx="11" cy="11" r="6.5"/><path stroke-linecap="round" d="M20 20l-4.2-4.2"/></svg>
            <span class="sr-only">Search a crop</span>
            <input type="search" placeholder="Search a crop, like kamote or mango" autocomplete="off">
        </label>
        <div class="cp-list" role="listbox" aria-label="{{ $title }}">
            @if ($allRow)
                <div class="cp-shelf" style="--g: 98">
                    <button type="button" class="cp-row" role="option" aria-selected="{{ $curKey === '' ? 'true' : 'false' }}" data-crop=""
                            data-local="{{ $allRow[0] }}" data-en="{{ $allRow[1] }}" data-icon="{{ $allRow[2] }}" data-q="{{ mb_strtolower($allRow[0] . ' ' . $allRow[1]) }}">
                        <span class="cp-ico" aria-hidden="true">{{ $allRow[2] }}</span>
                        <span class="cp-t"><b>{{ $allRow[0] }}</b><small>{{ $allRow[1] }}</small></span>
                        <span class="cp-on" aria-hidden="true"><svg fill="none" stroke="currentColor" stroke-width="3.2" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" d="M5 13l4 4L19 7"/></svg></span>
                    </button>
                </div>
            @endif
            @foreach ($byShelf as $shelf => $list)
                @php([$sLabel, , $sHue, $sIcon] = $F::SHELVES[$shelf])
                <div class="cp-shelf" style="--g: {{ $sHue }}">
                    <b><svg fill="none" stroke="currentColor" stroke-width="1.9" stroke-linecap="round" stroke-linejoin="round" viewBox="0 0 24 24" aria-hidden="true"><path d="{{ $sIcon }}"/></svg>{{ $sLabel }}</b>
                    @foreach ($list as $k => $c)
                        <button type="button" class="cp-row" role="option" aria-selected="{{ $k === $curKey ? 'true' : 'false' }}" data-crop="{{ $k }}"
                                data-local="{{ $c[0] }}" data-en="{{ $c[1] }}" data-icon="{{ $c[2] }}" data-q="{{ mb_strtolower($c[0] . ' ' . $c[1] . ' ' . str_replace('_', ' ', $k)) }}">
                            <span class="cp-ico" aria-hidden="true">{{ $c[2] }}</span>
                            <span class="cp-t"><b>{{ $c[0] }}</b>@if ($c[0] !== $c[1])<small>{{ $c[1] }}</small>@endif</span>
                            @if ($noun && ! empty($c[4]))<span class="cp-n">{{ $c[4] }} {{ $c[4] === 1 ? rtrim($noun, 's') : $noun }}</span>@endif
                            <span class="cp-on" aria-hidden="true"><svg fill="none" stroke="currentColor" stroke-width="3.2" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" d="M5 13l4 4L19 7"/></svg></span>
                        </button>
                    @endforeach
                </div>
            @endforeach
            <p class="cp-none">No crop by that name. Try its English or Filipino name.</p>
        </div>
    </div>
</dialog>
