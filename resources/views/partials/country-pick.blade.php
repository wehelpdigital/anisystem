{{-- THE COUNTRY, AS A TAG.

     A tag that says the country with its flag; tapping it opens a sheet of
     every country, searchable, the Philippines first. Used at signup (the
     address the visitor came from is pre-picked) and in account settings.

     @include('partials.country-pick', [
         'id'    => 'signupCountry',   // ids: {id}, {id}Btn, {id}Flag, {id}Now, {id}Sheet, {id}Search, {id}List
         'name'  => 'country',         // the hidden input's name
         'value' => 'PH',              // preselected code; the request's own country when absent
     ])

     Fires a `country:change` CustomEvent on the hidden input with
     {code, name, flag, rules} so a form can relabel its phone and address
     fields the moment the country changes (the rules for every configured
     country ride along in window.ANEE_REGION_RULES). --}}
@php
    $cpId = $id ?? 'countryPick';
    $cpName = $name ?? 'country';
    $cpVal = \App\Support\Region::valid($value ?? null) ?: \App\Support\Region::code();
@endphp
@once
<style>
    .country-tag { display: flex; align-items: center; gap: .5rem; width: 100%;
        padding: .65rem .8rem; border-radius: .8rem; cursor: pointer; text-align: left;
        border: 1.5px solid var(--color-gray-200); background: var(--color-white);
        transition: border-color .28s cubic-bezier(.22,1,.36,1), background .28s cubic-bezier(.22,1,.36,1); }
    .country-tag:hover { border-color: var(--color-brand-300); background: var(--color-brand-50); }
    .country-tag-e { font-size: 1.25rem; line-height: 1; flex: none; }
    .country-tag-t { flex: 1 1 auto; min-width: 0; font-size: .9rem; font-weight: 700; color: var(--color-gray-900);
        overflow: hidden; text-overflow: ellipsis; white-space: nowrap; }
    .country-tag-c { width: 1rem; height: 1rem; flex: none; color: var(--color-gray-400); }
    html.dark .country-tag { background: #1c2416; border-color: #2b3a1c; }
    html.dark .country-tag-t { color: #e8efe1; }
    .country-search { position: relative; margin-bottom: .6rem; }
    .country-search svg { position: absolute; left: .8rem; top: 50%; transform: translateY(-50%);
        width: 1.05rem; height: 1.05rem; color: var(--color-gray-400); pointer-events: none; }
    .country-search .form-input { padding-left: 2.4rem; }
    .country-row { display: flex; align-items: center; gap: .65rem; width: 100%; text-align: left;
        padding: .5rem .6rem; border-radius: .7rem; cursor: pointer; }
    .country-row:hover, .country-row.is-on { background: var(--color-brand-50); }
    .country-row-e { font-size: 1.25rem; line-height: 1; flex: none; }
    .country-row-t { min-width: 0; font-size: .875rem; font-weight: 600; color: var(--color-gray-900); }
    .country-row small { margin-left: auto; font-size: .68rem; color: var(--color-gray-400); font-weight: 700; }
    .country-none { font-size: .8rem; color: var(--color-gray-400); text-align: center; padding: 1rem 0; }
    html.dark .country-row:hover, html.dark .country-row.is-on { background: #22301a; }
    html.dark .country-row-t { color: #e8efe1; }
</style>
<script>window.ANEE_REGION_RULES = @json(\App\Support\Region::formRules());</script>
@endonce

<button type="button" class="country-tag" id="{{ $cpId }}Btn" aria-haspopup="dialog" aria-controls="{{ $cpId }}Sheet">
    <span class="country-tag-e" id="{{ $cpId }}Flag">{{ \App\Support\Region::flag($cpVal) }}</span>
    <span class="country-tag-t" id="{{ $cpId }}Now">{{ \App\Support\Region::name($cpVal) }}</span>
    <svg class="country-tag-c" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.4"><path stroke-linecap="round" stroke-linejoin="round" d="M6 9l6 6 6-6"/></svg>
</button>
<input type="hidden" name="{{ $cpName }}" id="{{ $cpId }}" value="{{ $cpVal }}">

<div class="sheet hidden" id="{{ $cpId }}Sheet" style="--sheet-width:26rem" role="dialog" aria-label="Choose your country">
    <div class="sheet-handle"></div>
    <div class="sheet-header">
        <h3 class="sheet-title">Which country is the farm in?</h3>
        <button type="button" data-sheet-close class="btn-ghost p-2 rounded-full" aria-label="Close">✕</button>
    </div>
    <div class="sheet-body">
        <div class="country-search">
            <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path stroke-linecap="round" stroke-linejoin="round" d="M21 21l-4.35-4.35M17 11a6 6 0 11-12 0 6 6 0 0112 0z"/></svg>
            <input type="text" id="{{ $cpId }}Search" class="form-input" autocomplete="off" placeholder="Search for a country">
        </div>
        <div id="{{ $cpId }}List">
            @foreach (\App\Support\Region::countries() as $code => $cname)
                <button type="button" class="country-row {{ $code === $cpVal ? 'is-on' : '' }}" data-country="{{ $code }}" data-find="{{ strtolower($cname . ' ' . $code) }}">
                    <span class="country-row-e">{{ \App\Support\Region::flag($code) }}</span>
                    <span class="country-row-t">{{ $cname }}</span>
                    <small>{{ $code }}</small>
                </button>
            @endforeach
        </div>
        <p class="country-none hidden" id="{{ $cpId }}None">No country matches that.</p>
    </div>
</div>

<script>
(() => {
    const id = @json($cpId);
    const $ = (s) => document.getElementById(id + s);
    const input = document.getElementById(id);
    const btn = $('Btn'), search = $('Search'), list = $('List'), none = $('None');
    if (!btn || !input) return;
    const phone = () => !window.matchMedia('(min-width: 640px)').matches;
    btn.addEventListener('click', () => {
        if (window.openSheet) window.openSheet(id + 'Sheet');
        if (!phone()) setTimeout(() => search && search.focus(), 280);
    });
    const sift = () => {
        const q = (search.value || '').trim().toLowerCase();
        let shown = 0;
        list.querySelectorAll('.country-row').forEach((r) => { const hit = !q || (r.getAttribute('data-find') || '').includes(q); r.hidden = !hit; if (hit) shown++; });
        none.classList.toggle('hidden', shown > 0);
    };
    search && search.addEventListener('input', sift);
    list.addEventListener('click', (e) => {
        const row = e.target.closest('[data-country]');
        if (!row) return;
        const code = row.getAttribute('data-country');
        input.value = code;
        $('Flag').textContent = row.querySelector('.country-row-e').textContent;
        $('Now').textContent = row.querySelector('.country-row-t').textContent;
        list.querySelectorAll('.country-row').forEach((r) => r.classList.toggle('is-on', r === row));
        if (window.closeSheet) window.closeSheet(id + 'Sheet');
        const rules = (window.ANEE_REGION_RULES || {});
        input.dispatchEvent(new CustomEvent('country:change', { bubbles: true, detail: { code, name: $('Now').textContent, flag: $('Flag').textContent, rules: rules[code] || rules['*'] || null } }));
    });
})();
</script>
