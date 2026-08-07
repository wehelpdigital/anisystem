{{-- Live composer preview. A field opts in with data-preview="#previewId";
     that target must contain a .cp-body. As you type, 📍[Place] tokens show as
     a bold place, @[Name](id) as @Name, and #tags — the way they'll post.
     Guarded singleton. --}}
<script>
(function () {
    if (window.__plazaComposerPreview) return;
    window.__plazaComposerPreview = true;
    const esc = (s) => (window.escapeHtml ? window.escapeHtml(s) : String(s == null ? '' : s));
    // Show the preview only when there's a token worth previewing.
    const HAS_TOKEN = /📍\s*\[|@\[[^\]]+\]\(\d+\)|(?:^|\s)#[\p{L}0-9_]/u;

    function toHtml(text) {
        let html = esc(text);
        html = html.replace(/📍\s*\[([^\]]{1,90})\]/gu, (_, l) => '<strong class="cp-loc">📍 ' + l + '</strong>');
        html = html.replace(/@\[([^\]]{1,80})\]\(\d+\)/g, (_, n) => '<strong class="cp-tag">@' + n + '</strong>');
        html = html.replace(/(^|\s)#([\p{L}0-9_]{1,50})/gu, (_, sp, t) => sp + '<strong class="cp-tag">#' + t + '</strong>');
        return html.replace(/\r?\n/g, '<br>');
    }
    function update(field) {
        const box = document.querySelector(field.getAttribute('data-preview'));
        if (!box) return;
        const body = box.querySelector('.cp-body');
        const text = field.value || '';
        if (!body || !text.trim() || !HAS_TOKEN.test(text)) { box.style.display = 'none'; if (body) body.innerHTML = ''; return; }
        body.innerHTML = toHtml(text);
        box.style.display = '';
    }
    document.addEventListener('input', (e) => {
        const t = e.target;
        if (t && t.matches && t.matches('[data-preview]')) update(t);
    });
})();
</script>
<style>
    .cp-preview { margin-top: .5rem; border: 1px solid var(--color-gray-100); background: var(--color-gray-50);
        border-radius: .6rem; padding: .5rem .65rem; }
    .cp-preview .cp-label { display: block; font-size: .65rem; font-weight: 800; letter-spacing: .04em;
        text-transform: uppercase; color: var(--color-gray-400); margin-bottom: .15rem; }
    .cp-preview .cp-body { font-size: .875rem; color: var(--color-gray-700); line-height: 1.5; word-break: break-word; }
    .cp-preview .cp-loc { font-weight: 700; color: #b45309; }
    html.dark .cp-preview .cp-loc { color: #eec155; }
    .cp-preview .cp-tag { font-weight: 700; color: var(--color-brand-700); }
</style>
