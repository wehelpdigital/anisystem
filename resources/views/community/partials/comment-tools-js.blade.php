{{-- Photo attach on comment/reply forms: .js-comment-photo opens the file
     picker, the picked .js-comment-file shows as a removable .js-comment-chip
     carrying a thumbnail of the picture. Guarded singleton shared by the wall
     and group pages; exposes window.plazaSetChip(form, fileOrName). --}}
<script>
(function () {
    if (window.__plazaCommentToolsBound) return;
    window.__plazaCommentToolsBound = true;

    // Build the exact @mention token the mention picker uses, so a prefilled
    // reply notifies that user server-side (CommunityText::mentionedUserIds).
    window.plazaMentionToken = (name, id) => (name && id) ? '@[' + name + '](' + id + ') ' : '';

    // Prefill a reply input with an @mention of the author being answered —
    // only when the field is still empty, so it never clobbers typed text.
    window.plazaPrefillMention = function (input, name, id) {
        if (!input) return;
        const token = window.plazaMentionToken(name, id);
        if (token && input.value.trim() === '') {
            input.value = token;
            try { input.setSelectionRange(token.length, token.length); } catch (_) {}
            input.dispatchEvent(new Event('input', { bubbles: true }));
        }
        input.focus();
    };

    /**
     * Show what is attached, not what it is called.
     *
     * "IMG_20260823_141233.jpg" tells a farmer nothing about the photo they
     * just picked; a thumbnail tells them whether it is the right one. Takes
     * the File itself (a bare name still works, for callers that only have
     * one) and draws the picture where the filename used to be. The chip's
     * markup is left alone everywhere: the thumbnail is made here, so the
     * four forms that carry a chip — wall, dashboard, group topic, group
     * reply — all gain it without being edited.
     */
    window.plazaSetChip = function (form, file) {
        const chip = form && form.querySelector('.js-comment-chip');
        if (!chip) return;

        const nameEl = chip.querySelector('.js-chip-name');
        const old = chip.querySelector('.js-chip-thumb');
        // An object URL holds the file in memory until it is let go.
        if (old) {
            if (old.dataset.objectUrl) URL.revokeObjectURL(old.dataset.objectUrl);
            old.remove();
        }

        const isFile = file && typeof file === 'object' && typeof file.name === 'string';
        const label = isFile ? file.name : (file || null);
        chip.classList.toggle('hidden', !label);
        if (!label) {
            if (nameEl) { nameEl.textContent = ''; nameEl.classList.remove('hidden'); }
            return;
        }

        if (isFile && /^image\//.test(file.type || '')) {
            const img = document.createElement('img');
            img.className = 'js-chip-thumb';
            img.alt = '';
            img.title = label;              // the name is still there, on hover
            const url = URL.createObjectURL(file);
            img.dataset.objectUrl = url;
            img.src = url;
            chip.insertBefore(img, chip.firstChild);
            // The picture is the label now.
            if (nameEl) { nameEl.textContent = ''; nameEl.classList.add('hidden'); }
            return;
        }

        // Anything that is not an image keeps its name.
        if (nameEl) { nameEl.textContent = label; nameEl.classList.remove('hidden'); }
    };

    // Shared "sending…" spinner + "just posted" entrance animation, so every
    // comment/reply form (wall, group, blog) gets the same feedback.
    const SVG_SPIN = '<svg class="w-4 h-4 plaza-spin" viewBox="0 0 24 24" fill="none" aria-hidden="true"><circle cx="12" cy="12" r="9" stroke="currentColor" stroke-width="2.4" stroke-opacity=".25"/><path d="M21 12a9 9 0 0 0-9-9" stroke="currentColor" stroke-width="2.4" stroke-linecap="round"/></svg>';
    (function injectCss() {
        if (document.getElementById('plaza-comment-fx')) return;
        const s = document.createElement('style');
        s.id = 'plaza-comment-fx';
        s.textContent = `
        @keyframes plazaSpin { to { transform: rotate(360deg); } }
        .plaza-spin { animation: plazaSpin .6s linear infinite; transform-origin: 50% 50%; }
        button.is-sending { opacity: .85; cursor: progress; }
        @keyframes plazaCommentIn {
            0%   { opacity: 0; transform: translateY(8px); background-color: rgba(16,185,129,.14); }
            55%  { opacity: 1; transform: none; }
            100% { background-color: transparent; }
        }
        .plaza-comment-enter { animation: plazaCommentIn .5s cubic-bezier(.22,1,.36,1); border-radius: .55rem; }
        @keyframes plazaTombstoneIn { from { opacity: 0; transform: scale(.96); } to { opacity: 1; transform: none; } }
        .tombstone-in { animation: plazaTombstoneIn .28s cubic-bezier(.22,1,.36,1); }
        @keyframes plazaCommentReveal { from { opacity: 0; transform: translateY(6px); } to { opacity: 1; transform: none; } }
        .comment-reveal { animation: plazaCommentReveal .32s cubic-bezier(.22,1,.36,1) both; }
        @media (prefers-reduced-motion: reduce) { .plaza-comment-enter, .tombstone-in, .comment-reveal { animation: none; } }`;
        document.head.appendChild(s);
    })();

    window.plazaCommentFx = {
        // Swap a form's send button to a spinner while the request is in flight.
        startSending(btn) {
            if (!btn || btn.dataset.loading) return;
            btn.dataset.loading = '1';
            btn.dataset.prevHtml = btn.innerHTML;
            btn.disabled = true;
            btn.classList.add('is-sending');
            btn.innerHTML = SVG_SPIN;
        },
        stopSending(btn) {
            if (!btn || !btn.dataset.loading) return;
            btn.innerHTML = btn.dataset.prevHtml;
            btn.disabled = false;
            btn.classList.remove('is-sending');
            delete btn.dataset.loading;
            delete btn.dataset.prevHtml;
        },
        // Flash a freshly-inserted comment/reply node in.
        animateIn(node) {
            if (!node || !node.classList) return;
            node.classList.add('plaza-comment-enter');
            node.addEventListener('animationend', () => node.classList.remove('plaza-comment-enter'), { once: true });
        },
    };
    document.addEventListener('click', (e) => {
        const photoBtn = e.target.closest('.js-comment-photo');
        if (photoBtn) { photoBtn.closest('form').querySelector('.js-comment-file')?.click(); return; }
        const clearBtn = e.target.closest('.js-chip-clear');
        if (clearBtn) {
            const form = clearBtn.closest('form');
            const fileInput = form.querySelector('.js-comment-file');
            if (fileInput) fileInput.value = '';
            window.plazaSetChip(form, null);
        }
    });
    document.addEventListener('change', (e) => {
        if (!e.target.classList || !e.target.classList.contains('js-comment-file')) return;
        // The file itself, so the chip can show the picture rather than name it.
        window.plazaSetChip(e.target.closest('form'), e.target.files[0] || null);
    });
})();
</script>
