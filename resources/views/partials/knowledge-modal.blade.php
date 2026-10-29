{{-- Anee Knowledgebase, read in a full window (2026-10-30, the owner: "when opened, show a full modal that contains the
     nutrient knowledge"). Opened by any [data-kb-open="<shelf>"] (The Stash card); the guides come from
     stash.knowledge (App\Support\Knowledge) as the public site's blocks and are drawn here: the guides of the shelf on the
     side (chips on a phone), the one picked in the middle. A link to another guide of the shelf opens it here; any other
     link opens the site in a new tab. Prefix kb-. --}}
@once
<div class="kb" id="kbModal" role="dialog" aria-modal="true" aria-labelledby="kbTitle" hidden data-url="{{ url('/app/stash/knowledge') }}/">
    <div class="kb-veil" data-kb-x></div>
    <div class="kb-box">
        <header class="kb-head">
            <img src="{{ asset('images/anee/avatar-160.jpg') }}" alt="" width="44" height="44">
            <div class="kb-head-t"><small>Anee Knowledgebase</small><b id="kbTitle">Guides</b></div>
            <button type="button" class="kb-x" data-kb-x aria-label="Close"><svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.4" aria-hidden="true"><path stroke-linecap="round" d="M6 6l12 12M18 6L6 18"/></svg></button>
        </header>
        <div class="kb-main">
            <nav class="kb-nav" data-kb-nav aria-label="Guides on this shelf"></nav>
            <div class="kb-scroll" data-kb-scroll><article class="kb-art" data-kb-art></article></div>
        </div>
    </div>
</div>

@push('head')
<style>
    .kb { --kb-e: cubic-bezier(.22,1,.36,1); --kb-ink: #1a2e10; --kb-text: #334127; --kb-muted: #5f6f52; --kb-line: #e3ecd8; --kb-soft: #f4f8ee; --kb-card: #fff; --kb-acc: #3f7a22;
        position: fixed; inset: 0; z-index: 470; display: grid; place-items: center; padding: 1rem; }
    html.dark .kb { --kb-ink: #eef5e8; --kb-text: #d6e2cc; --kb-muted: #b1c0a5; --kb-line: #2c3b22; --kb-soft: #1b2416; --kb-card: #151c11; --kb-acc: #a9d982; }
    .kb[hidden] { display: none; }
    .kb-veil { position: absolute; inset: 0; background: rgb(10 16 6 / .55); backdrop-filter: blur(3px); opacity: 0; transition: opacity .3s var(--kb-e); }
    .kb.is-on .kb-veil { opacity: 1; }
    .kb-box { position: relative; display: grid; grid-template-rows: auto minmax(0, 1fr); width: min(100%, 72rem); height: min(100%, 52rem); border-radius: 1.4rem; overflow: hidden; background: var(--kb-soft);
        box-shadow: 0 40px 90px -30px rgb(0 0 0 / .65); opacity: 0; transform: translateY(18px) scale(.985); transition: opacity .32s var(--kb-e), transform .32s var(--kb-e); }
    .kb.is-on .kb-box { opacity: 1; transform: none; }
    .kb-head { display: flex; align-items: center; gap: .75rem; padding: .8rem 1rem; color: #f1f7e9; background: linear-gradient(120deg, #5b8a35, #3f6b24 55%, #2d5016); }
    .kb-head img { flex: none; width: 2.6rem; height: 2.6rem; border-radius: 999px; object-fit: cover; box-shadow: 0 0 0 2px rgb(255 255 255 / .35); }
    .kb-head-t { flex: 1; min-width: 0; }
    .kb-head-t small { display: block; font-size: .7rem; font-weight: 800; letter-spacing: .08em; text-transform: uppercase; color: rgb(241 247 233 / .8); }
    .kb-head-t b { display: block; font-family: var(--font-heading); font-size: 1.15rem; }
    .kb-x { flex: none; display: grid; place-items: center; width: 2.4rem; height: 2.4rem; border-radius: 999px; color: #fff; background: rgb(255 255 255 / .14); transition: background-color .25s; }
    .kb-x:hover { background: rgb(255 255 255 / .26); }
    .kb-x svg { width: 1.1rem; height: 1.1rem; }
    .kb-main { display: grid; grid-template-columns: 16rem minmax(0, 1fr); min-height: 0; }
    .kb-nav { min-height: 0; overflow: auto; padding: .9rem .7rem; border-right: 1px solid var(--kb-line); background: var(--kb-card); }
    .kb-g { margin-bottom: .75rem; }
    .kb-g > b { display: block; padding: 0 .5rem .3rem; font-size: .68rem; font-weight: 900; letter-spacing: .08em; text-transform: uppercase; color: var(--kb-muted); }
    .kb-i { display: flex; align-items: center; gap: .55rem; width: 100%; padding: .45rem .5rem; border-radius: .7rem; text-align: left; font-size: .86rem; font-weight: 700; color: var(--kb-text);
        transition: background-color .25s var(--kb-e), color .25s var(--kb-e); }
    .kb-i:hover { background: var(--kb-soft); }
    .kb-i.is-on { color: #fff; background: var(--kb-acc); }
    html.dark .kb-i.is-on { color: #10160c; }
    .kb-i i { flex: none; display: grid; place-items: center; width: 1.9rem; height: 1.9rem; border-radius: .55rem; font-style: normal; font-size: .76rem; font-weight: 900; color: var(--kb-acc); background: var(--kb-soft); box-shadow: inset 0 0 0 1px var(--kb-line); }
    .kb-i.is-on i { color: var(--kb-acc); background: #fff; box-shadow: none; }
    .kb-scroll { min-height: 0; overflow: auto; scroll-behavior: smooth; }
    .kb-art { max-width: 46rem; margin: 0 auto; padding: 1.4rem 1.4rem 3rem; color: var(--kb-text); }
    .kb-art.is-in { animation: kbIn .35s var(--kb-e) both; }
    @keyframes kbIn { from { opacity: 0; transform: translateY(10px); } }
    .kb-chip { display: inline-block; padding: .2rem .65rem; border-radius: 999px; font-size: .7rem; font-weight: 900; letter-spacing: .06em; text-transform: uppercase; color: var(--kb-acc); background: var(--kb-card); box-shadow: inset 0 0 0 1px var(--kb-line); }
    .kb-art h1 { margin-top: .6rem; font-family: var(--font-heading); font-weight: 800; font-size: clamp(1.5rem, 3.4vw, 2.1rem); line-height: 1.15; color: var(--kb-ink); }
    .kb-lead { margin-top: .7rem; font-size: 1.02rem; line-height: 1.65; color: var(--kb-text); }
    .kb-hero { margin-top: 1rem; border-radius: 1rem; overflow: hidden; background: var(--kb-card); }
    .kb-hero img { display: block; width: 100%; max-height: 20rem; object-fit: cover; }
    .kb-hero small { display: block; padding: .35rem .7rem; font-size: .7rem; color: var(--kb-muted); }
    .kb-art h2 { margin-top: 1.8rem; font-family: var(--font-heading); font-weight: 800; font-size: 1.3rem; color: var(--kb-ink); }
    .kb-art h3 { margin-top: 1.2rem; font-family: var(--font-heading); font-weight: 800; font-size: 1.05rem; color: var(--kb-ink); }
    .kb-art p { margin-top: .7rem; font-size: .96rem; line-height: 1.7; }
    .kb-art a { font-weight: 700; color: var(--kb-acc); text-decoration: underline; text-underline-offset: 3px; }
    .kb-art strong { color: var(--kb-ink); }
    .kb-art ul, .kb-art ol { margin: .7rem 0 0; padding-left: 1.2rem; display: grid; gap: .35rem; font-size: .94rem; line-height: 1.6; }
    .kb-art ul { list-style: disc; } .kb-art ol { list-style: decimal; }
    .kb-tbl { margin-top: .9rem; overflow-x: auto; border-radius: .9rem; background: var(--kb-card); box-shadow: inset 0 0 0 1px var(--kb-line); }
    .kb-tbl table { width: 100%; border-collapse: collapse; font-size: .84rem; }
    .kb-tbl th { padding: .55rem .7rem; text-align: left; font-weight: 800; color: var(--kb-ink); background: var(--kb-soft); }
    .kb-tbl td { padding: .55rem .7rem; vertical-align: top; border-top: 1px solid var(--kb-line); line-height: 1.5; }
    .kb-tbl caption { caption-side: bottom; padding: .4rem .7rem; text-align: left; font-size: .72rem; color: var(--kb-muted); }
    .kb-call { margin-top: 1rem; padding: .8rem .95rem; border-radius: .9rem; background: var(--kb-card); box-shadow: inset 4px 0 0 #3f7a22, inset 0 0 0 1px var(--kb-line); }
    .kb-call.is-warn { box-shadow: inset 4px 0 0 #d97706, inset 0 0 0 1px var(--kb-line); }
    .kb-call b { display: block; color: var(--kb-ink); }
    .kb-call p { margin-top: .3rem; font-size: .9rem; }
    .kb-faq details { margin-top: .5rem; padding: .65rem .85rem; border-radius: .8rem; background: var(--kb-card); box-shadow: inset 0 0 0 1px var(--kb-line); }
    .kb-faq summary { cursor: pointer; font-weight: 800; color: var(--kb-ink); }
    .kb-faq p { margin-top: .4rem; font-size: .9rem; }
    .kb-links { display: flex; flex-wrap: wrap; gap: .4rem; margin-top: .7rem; }
    .kb-links a { padding: .35rem .75rem; border-radius: 999px; font-size: .82rem; text-decoration: none; background: var(--kb-card); box-shadow: inset 0 0 0 1px var(--kb-line); }
    .kb-pager { display: flex; justify-content: space-between; gap: .6rem; margin-top: 2rem; padding-top: 1rem; border-top: 1px solid var(--kb-line); }
    .kb-pager button { max-width: 48%; padding: .55rem .85rem; border-radius: .8rem; text-align: left; font-size: .84rem; font-weight: 800; color: var(--kb-ink); background: var(--kb-card); box-shadow: inset 0 0 0 1px var(--kb-line); transition: transform .25s var(--kb-e); }
    .kb-pager button:hover { transform: translateY(-1px); }
    .kb-pager small { display: block; font-size: .7rem; font-weight: 700; color: var(--kb-muted); }
    .kb-load { display: grid; place-items: center; height: 12rem; color: var(--kb-muted); }
    html.kb-lock { overflow: hidden; }
    @media (max-width: 767.98px) {
        .kb { padding: 0; }
        .kb-box { width: 100%; height: 100%; border-radius: 0; }
        .kb-main { grid-template-columns: minmax(0, 1fr); grid-template-rows: auto minmax(0, 1fr); }
        .kb-nav { display: flex; gap: .4rem; overflow-x: auto; overflow-y: hidden; padding: .55rem .7rem; border-right: 0; border-bottom: 1px solid var(--kb-line); scrollbar-width: none; }
        .kb-g { display: contents; }
        .kb-g > b { display: none; }
        .kb-i { flex: none; width: auto; padding: .35rem .65rem .35rem .4rem; border-radius: 999px; box-shadow: inset 0 0 0 1px var(--kb-line); white-space: nowrap; }
        .kb-i i { width: 1.6rem; height: 1.6rem; border-radius: 999px; }
        .kb-art { padding: 1rem 1rem 3rem; }
    }
    @media (prefers-reduced-motion: reduce) { .kb-veil, .kb-box, .kb-i, .kb-x { transition: none; } .kb-art.is-in { animation: none; } .kb-scroll { scroll-behavior: auto; } }
</style>
@endpush

@push('scripts')
<script>
(() => {
    const M = document.getElementById('kbModal');
    if (!M) return;
    document.body.appendChild(M);
    const $ = (s) => M.querySelector(s);
    const esc = (s) => String(s ?? '').replace(/[&<>"']/g, (c) => ({ '&': '&amp;', '<': '&lt;', '>': '&gt;', '"': '&quot;', "'": '&#39;' }[c]));
    const calm = matchMedia('(prefers-reduced-motion: reduce)').matches;
    let SHELF = null, DATA = null, AT = 0;
    // The short name of a guide: its title before the colon ("Nitrogen in Plants").
    const short = (t) => String(t || '').split(':')[0].trim();
    const SYM = { nitrogen: 'N', phosphorus: 'P', potassium: 'K', calcium: 'Ca', magnesium: 'Mg', sulfur: 'S', zinc: 'Zn', boron: 'B', iron: 'Fe', manganese: 'Mn', copper: 'Cu', molybdenum: 'Mo', chlorine: 'Cl', nickel: 'Ni', silicon: 'Si' };
    const symOf = (slug) => SYM[String(slug).split('-')[0]] || '✦';
    // Inline words: [label](url) links and **bold**; a guide of this shelf opens here, anything else on the site in a new tab.
    const inline = (t) => esc(t).replace(/\*\*(.+?)\*\*/g, '<strong>$1</strong>').replace(/\[([^\]]+)\]\(([^)\s]+)\)/g, (m, label, url) => {
        const mine = SHELF && url.startsWith('/' + SHELF + '/') ? url.split('/')[2].split('#')[0] : null;
        return mine && DATA && DATA.pages.some((p) => p.slug === mine) ? `<a href="#" data-kb-go="${esc(mine)}">${label}</a>` : `<a href="${url}" target="_blank" rel="noopener">${label}</a>`;
    });
    const paras = (t) => String(t || '').split(/\n\s*\n/).filter((p) => p.trim()).map((p) => `<p>${inline(p.trim())}</p>`).join('');
    const block = (b) => {
        switch (b.type) {
            case 'heading': return b.level == 3 ? `<h3>${esc(b.text)}</h3>` : `<h2>${esc(b.text)}</h2>`;
            case 'text': return paras(b.text);
            case 'list': { const tag = b.ordered ? 'ol' : 'ul'; return `<${tag}>${(b.items || []).map((i) => `<li>${inline(i)}</li>`).join('')}</${tag}>`; }
            case 'steps': return `<ol>${(b.items || []).map((i) => `<li><strong>${esc(i.title)}</strong> ${inline(i.text)}</li>`).join('')}</ol>`;
            case 'table': { const [h, ...rows] = b.rows || []; return `<div class="kb-tbl"><table>${b.caption ? `<caption>${esc(b.caption)}</caption>` : ''}<thead><tr>${(h || []).map((c) => `<th>${inline(c)}</th>`).join('')}</tr></thead><tbody>${rows.map((r) => `<tr>${r.map((c) => `<td>${inline(c)}</td>`).join('')}</tr>`).join('')}</tbody></table></div>`; }
            case 'callout': return `<div class="kb-call ${b.tone === 'warn' ? 'is-warn' : ''}"><b>${esc(b.title)}</b>${paras(b.text)}</div>`;
            case 'image': return `<figure class="kb-hero"><img src="${esc(b.src)}" alt="${esc(b.alt)}" loading="lazy">${b.caption ? `<small>${esc(b.caption)}</small>` : ''}</figure>`;
            case 'quote': return `<div class="kb-call"><p>${inline(b.text)}</p>${b.cite ? `<small>${esc(b.cite)}</small>` : ''}</div>`;
            case 'faq': return `<div class="kb-faq">${(b.items || []).map((i) => `<details><summary>${esc(i.q)}</summary><p>${inline(i.a)}</p></details>`).join('')}</div>`;
            case 'links': return `<h3>${esc(b.title || 'Related pages')}</h3><div class="kb-links">${(b.items || []).map((i) => inline(`[${i.label}](${i.url})`)).join('')}</div>`;
            default: return '';
        }
    };
    const paintNav = () => {
        const groups = {};
        DATA.pages.forEach((p, i) => { (groups[p.category || 'Guides'] ||= []).push([p, i]); });
        $('[data-kb-nav]').innerHTML = Object.entries(groups).map(([g, list]) => `<div class="kb-g"><b>${esc(g)}</b>${list.map(([p, i]) => `<button type="button" class="kb-i ${i === AT ? 'is-on' : ''}" data-kb-i="${i}" aria-current="${i === AT}"><i aria-hidden="true">${esc(symOf(p.slug))}</i>${esc(short(p.title))}</button>`).join('')}</div>`).join('');
    };
    const show = (i) => {
        const p = DATA.pages[i];
        if (!p) return;
        AT = i;
        const hero = p.hero && p.hero.src && !String(p.hero.src).startsWith('commons:') ? `<figure class="kb-hero"><img src="${esc(p.hero.src)}" alt="${esc(p.hero.alt || '')}">${p.hero.credit ? `<small>Photo: ${esc(p.hero.credit)}</small>` : ''}</figure>` : '';
        const prev = DATA.pages[i - 1], next = DATA.pages[i + 1];
        const art = $('[data-kb-art]');
        art.innerHTML = `<span class="kb-chip">${esc(p.category || DATA.label)}</span><h1>${esc(p.title)}</h1><p class="kb-lead">${inline(p.excerpt || '')}</p>${hero}${(p.blocks || []).map(block).join('')}
            <div class="kb-pager">${prev ? `<button type="button" data-kb-i="${i - 1}"><small>‹ Before</small>${esc(short(prev.title))}</button>` : '<span></span>'}${next ? `<button type="button" data-kb-i="${i + 1}"><small>Next ›</small>${esc(short(next.title))}</button>` : ''}</div>`;
        if (!calm) { art.classList.remove('is-in'); void art.offsetWidth; art.classList.add('is-in'); }
        $('[data-kb-scroll]').scrollTop = 0;
        paintNav();
        const on = $('.kb-i.is-on');
        if (on && on.scrollIntoView) on.scrollIntoView({ block: 'nearest', inline: 'nearest', behavior: calm ? 'auto' : 'smooth' });
        try { localStorage.setItem('anee.kb.' + SHELF, p.slug); } catch (_) {}
    };
    const open = async (shelf) => {
        SHELF = shelf;
        M.hidden = false;
        document.documentElement.classList.add('kb-lock');
        requestAnimationFrame(() => requestAnimationFrame(() => M.classList.add('is-on')));
        if (window.registerOverlay) window.registerOverlay('kb', close);
        if (!DATA || DATA.shelf !== shelf) {
            $('[data-kb-art]').innerHTML = '<div class="kb-load">Opening the guides…</div>';
            $('[data-kb-nav]').innerHTML = '';
            try {
                const r = await window.api(M.dataset.url + encodeURIComponent(shelf));
                DATA = r.data;
            } catch (err) {
                $('[data-kb-art]').innerHTML = `<div class="kb-load">${esc(err.message || 'The guides could not open. Please try again.')}</div>`;
                return;
            }
        }
        $('#kbTitle').textContent = DATA.label;
        let last = null;
        try { last = localStorage.getItem('anee.kb.' + shelf); } catch (_) {}
        const at = Math.max(0, DATA.pages.findIndex((p) => p.slug === last));
        show(at);
    };
    const close = () => {
        M.classList.remove('is-on');
        document.documentElement.classList.remove('kb-lock');
        if (window.unregisterOverlay) window.unregisterOverlay('kb');
        setTimeout(() => { if (!M.classList.contains('is-on')) M.hidden = true; }, calm ? 0 : 320);
    };
    document.addEventListener('click', (e) => {
        const o = e.target.closest('[data-kb-open]');
        if (o) { e.preventDefault(); open(o.dataset.kbOpen); }
    });
    M.addEventListener('click', (e) => {
        if (e.target.closest('[data-kb-x]')) { close(); return; }
        const i = e.target.closest('[data-kb-i]');
        if (i) { show(Number(i.dataset.kbI)); return; }
        const g = e.target.closest('[data-kb-go]');
        if (g) { e.preventDefault(); const k = DATA.pages.findIndex((p) => p.slug === g.dataset.kbGo); if (k >= 0) show(k); }
    });
    document.addEventListener('keydown', (e) => { if (e.key === 'Escape' && !M.hidden) close(); });
})();
</script>
@endpush
@endonce
