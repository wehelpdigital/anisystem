{{-- A saved list's way to the Trash (2026-10-23, the owner: "i don't see an option to delete saved analysis and put it to trash").
     Every saved row of a tool carries a small bin beside its pen: window.aneeTrash.btn(id, title) draws it, and
     window.aneeTrash.run(el, url, opts) asks, deletes through the tool's own route, and slides the row away. It is a
     span with role="button" because most saved rows are buttons themselves. Included once per page. --}}
@once
<style>
    .tr-bin-btn { flex: none; width: 1.6rem; height: 1.6rem; border-radius: .45rem; display: inline-flex; align-items: center; justify-content: center;
        color: var(--color-gray-400); cursor: pointer;
        transition: color .28s cubic-bezier(.22,1,.36,1), background .28s cubic-bezier(.22,1,.36,1); }
    .tr-bin-btn svg { width: .85rem; height: .85rem; }
    .tr-bin-btn:hover, .tr-bin-btn:focus-visible { color: #dc2626; background: #fef2f2; outline: none; }
    html.dark .tr-bin-btn:hover, html.dark .tr-bin-btn:focus-visible { color: #fca5a5; background: rgb(220 38 38 / .18); }
    .tr-going { overflow: hidden; pointer-events: none;
        transition: opacity .28s cubic-bezier(.22,1,.36,1), transform .28s cubic-bezier(.22,1,.36,1), max-height .28s cubic-bezier(.22,1,.36,1) .12s,
            margin .28s cubic-bezier(.22,1,.36,1) .12s, padding .28s cubic-bezier(.22,1,.36,1) .12s; }
    .tr-going.is-gone { opacity: 0; transform: translateX(18px); max-height: 0 !important; margin-top: 0 !important; margin-bottom: 0 !important;
        padding-top: 0 !important; padding-bottom: 0 !important; border-width: 0 !important; }
    @media (prefers-reduced-motion: reduce) { .tr-bin-btn, .tr-going { transition: none; } }
</style>
<script>
(() => {
    if (window.aneeTrash) return;
    const esc = (s) => String(s ?? '').replace(/[&<>"']/g, (c) => ({ '&': '&amp;', '<': '&lt;', '>': '&gt;', '"': '&quot;', "'": '&#39;' }[c]));
    const BIN = '<svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" aria-hidden="true"><path stroke-linecap="round" stroke-linejoin="round" d="M4 7h16M10 11v6M14 11v6M5.5 7l1 12a2 2 0 002 1.8h7a2 2 0 002-1.8l1-12M9 7V4.8A.8.8 0 019.8 4h4.4a.8.8 0 01.8.8V7"/></svg>';
    window.aneeTrash = {
        /** The bin for one saved row. */
        btn: (id, title) => '<span role="button" tabindex="0" class="tr-bin-btn" data-trash="' + esc(id) + '" data-title="' + esc(title || '') + '" title="Move to the Trash" aria-label="Move ' + esc(title || 'this') + ' to the Trash">' + BIN + '</span>',
        /**
         * Ask, delete through the tool's own route, slide the row away, then onDone().
         * opts: { title, what ('analysis'), paid (credits are not returned), row (the element to remove), method ('DELETE'), onDone }
         */
        async run(el, url, opts = {}) {
            const what = opts.what || 'analysis';
            const ok = window.confirmAction
                ? await window.confirmAction({
                    title: 'Move this ' + what + ' to the Trash?',
                    message: '"' + (opts.title || el.dataset.title || 'It') + '" goes to the Trash. You can restore it there for 30 days, then it is deleted for good.',
                    detail: opts.paid ? 'The credits it used are not returned.' : '',
                    confirmText: 'Move to the Trash',
                })
                : window.confirm('Move this ' + what + ' to the Trash?');
            if (!ok) return false;
            el.style.opacity = '.5';
            try {
                const res = await window.api(url, { method: opts.method || 'DELETE' });
                const row = opts.row || el.closest('[data-trash-row]');
                if (row) {
                    row.style.maxHeight = row.offsetHeight + 'px';
                    row.classList.add('tr-going');
                    requestAnimationFrame(() => requestAnimationFrame(() => row.classList.add('is-gone')));
                    await new Promise((r) => setTimeout(r, window.matchMedia('(prefers-reduced-motion: reduce)').matches ? 0 : 420));
                    row.remove();
                }
                window.toast?.('Moved to the Trash. Restore it from the Trash for 30 days.');
                opts.onDone?.(res);
                return true;
            } catch (err) {
                el.style.opacity = '';
                window.toast?.(err.message || 'Could not move it to the Trash.', 'error');
                return false;
            }
        },
    };
    // A bin is a span: Enter and Space press it, as a button would be.
    document.addEventListener('keydown', (e) => {
        const b = e.target.closest && e.target.closest('[data-trash]');
        if (b && (e.key === 'Enter' || e.key === ' ')) { e.preventDefault(); e.stopPropagation(); b.click(); }
    }, true);
})();
</script>
@endonce
