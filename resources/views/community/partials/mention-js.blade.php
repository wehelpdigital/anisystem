{{-- @mention autocomplete for community composers + comment/reply inputs.
     Guarded singleton. Attaches (via delegation) to any textarea/input marked
     data-mentionable, plus every comment/reply text input. Typing "@name"
     shows a co-farmer dropdown; picking inserts a @[Name](id) token that
     CommunityText renders as a profile link. --}}
<script>
(function () {
    if (window.__plazaMentionBound) return;
    window.__plazaMentionBound = true;

    const SEARCH_URL = @json(route('community.mention-search'));
    const FIELD_SEL = 'textarea[data-mentionable], input[data-mentionable], '
        + '.wall-comment-form input[type="text"], .post-reply-form input[type="text"]';
    const QUERY_RE = /(?:^|\s)@([\p{L}0-9_]{0,30})$/u;

    let pop = null, field = null, items = [], active = 0, atIndex = -1, timer = null, lastQuery = null;

    function ensurePop() {
        if (pop) return pop;
        pop = document.createElement('div');
        pop.className = 'mention-pop hidden';
        document.body.appendChild(pop);
        pop.addEventListener('mousedown', (e) => {
            const row = e.target.closest('.mention-item');
            if (!row) return;
            e.preventDefault();
            choose(parseInt(row.dataset.index, 10));
        });
        return pop;
    }
    function close() { if (pop) pop.classList.add('hidden'); items = []; atIndex = -1; }

    function place() {
        const r = field.getBoundingClientRect();
        ensurePop();
        pop.style.left = (window.scrollX + r.left) + 'px';
        pop.style.top = (window.scrollY + r.bottom + 4) + 'px';
        pop.style.minWidth = Math.max(200, r.width * 0.6) + 'px';
    }

    function esc(s) { const d = document.createElement('div'); d.textContent = s == null ? '' : s; return d.innerHTML; }

    function render() {
        ensurePop();
        if (!items.length) { close(); return; }
        active = Math.max(0, Math.min(active, items.length - 1));
        pop.innerHTML = items.map((it, i) => {
            if (it.type === 'location') {
                const pin = '<span class="avatar mm-loc-pin">📍</span>';
                return `<div class="mention-item ${i === active ? 'is-active' : ''}" data-index="${i}">
                    ${pin}<span class="mm-name">${esc(it.name)}</span><span class="mm-badge mm-badge-loc">Location</span>
                </div>`;
            }
            const hue = typeof it.id === 'number' ? (it.id % 8) : 0;
            const av = it.avatar
                ? `<span class="avatar overflow-hidden"><img src="${esc(it.avatar)}" alt="" class="w-full h-full object-cover"></span>`
                : `<span class="avatar av-h${hue}">${esc(it.initials || '?')}</span>`;
            return `<div class="mention-item ${i === active ? 'is-active' : ''}" data-index="${i}">
                ${av}<span class="mm-name">${esc(it.name)}</span>${it.isFriend ? '<span class="mm-badge">Co-farmer</span>' : ''}
            </div>`;
        }).join('');
        pop.classList.remove('hidden');
        place();
    }

    async function search(q) {
        if (q === lastQuery) return;
        lastQuery = q;
        try {
            const r = await fetch(SEARCH_URL + '?q=' + encodeURIComponent(q), { headers: { Accept: 'application/json' }, credentials: 'same-origin' });
            const d = await r.json();
            const users = (d.data && d.data.items) || [];
            const locs = (d.data && d.data.locations) || [];
            // Locations first so "@villasis" surfaces the place, then people.
            items = [...locs, ...users];
            active = 0;
            render();
        } catch (_) { close(); }
    }

    function detect() {
        const caret = field.selectionStart;
        const before = field.value.slice(0, caret);
        const m = before.match(QUERY_RE);
        if (!m) { close(); return; }
        atIndex = caret - m[1].length - 1;   // position of '@'
        clearTimeout(timer);
        const q = m[1];
        timer = setTimeout(() => search(q), 160);
    }

    function choose(i) {
        const it = items[i];
        if (!it || !field) return;
        const caret = field.selectionStart;
        // Locations get a clean 📍[Place] token (rendered as a bold map link);
        // people keep the @[Name](id) token needed for the profile link + notify.
        const token = it.type === 'location'
            ? '📍[' + it.name + '] '
            : '@[' + it.name + '](' + it.id + ') ';
        field.value = field.value.slice(0, atIndex) + token + field.value.slice(caret);
        const pos = atIndex + token.length;
        field.setSelectionRange(pos, pos);
        field.focus();
        close();
        field.dispatchEvent(new Event('input', { bubbles: true }));
    }

    document.addEventListener('input', (e) => {
        if (!e.target.matches || !e.target.matches(FIELD_SEL)) return;
        field = e.target;
        detect();
    });
    document.addEventListener('keydown', (e) => {
        if (!pop || pop.classList.contains('hidden') || !items.length) return;
        if (e.key === 'ArrowDown') { e.preventDefault(); active = (active + 1) % items.length; render(); }
        else if (e.key === 'ArrowUp') { e.preventDefault(); active = (active - 1 + items.length) % items.length; render(); }
        else if (e.key === 'Enter' || e.key === 'Tab') { e.preventDefault(); choose(active); }
        else if (e.key === 'Escape') { close(); }
    });
    document.addEventListener('click', (e) => {
        if (pop && !pop.contains(e.target) && e.target !== field) close();
    });
})();
</script>
