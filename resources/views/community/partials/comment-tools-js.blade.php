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
        // A pick is not a File: it arrives as {name, type, previewUrl} and its
        // picture is already on a server, so there is no object URL to make.
        const picked = isFile && typeof file.previewUrl === 'string';
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
            const url = picked ? file.previewUrl : URL.createObjectURL(file);
            if (!picked) img.dataset.objectUrl = url;
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
    /* ---------------- where a picture comes from ----------------
     * The button used to open a file dialog and nothing else, which on a
     * phone means "your camera roll" and on a laptop means "your downloads
     * folder" — and never means the photo already sitting in this app. Four
     * doors now, and the ones that cannot open for this account are not
     * drawn: a member with no season to read has no season gallery.
     *
     * The camera is always offered here. A worker's camera permission is
     * about their boss's season, not about talking to the community, and
     * applying it here would be the wrong farm's rule in the wrong place.
     */
    const CAN_GALLERY = @json(
        \App\Support\WorkerContext::canView()
        && \App\Models\AsCroppingSchedule::active()
            ->forClient(\App\Support\WorkerContext::effectiveOwnerId())
            ->exists()
    );

    const SRC_ICON = {
        camera: 'M3 9a2 2 0 012-2h.93a2 2 0 001.66-.9l.82-1.2A2 2 0 0110.07 4h3.86a2 2 0 011.66.9l.82 1.2a2 2 0 001.66.9H19a2 2 0 012 2v9a2 2 0 01-2 2H5a2 2 0 01-2-2V9zM15 13a3 3 0 11-6 0 3 3 0 016 0z',
        upload: 'M4 16v2a2 2 0 002 2h12a2 2 0 002-2v-2M12 4v12m0-12l-4 4m4-4l4 4',
        mine: 'M17 20h5v-2a4 4 0 00-3-3.87M9 20H4v-2a4 4 0 013-3.87m6-1.13a4 4 0 10-4-4 4 4 0 004 4z',
        season: 'M4 6a2 2 0 012-2h5l2 2h5a2 2 0 012 2v9a2 2 0 01-2 2H6a2 2 0 01-2-2V6zM8 14l2.5-3 2 2.5L15 10l3 4',
    };

    function sourceMenu(form) {
        const rows = [
            ['camera', 'Take a photo', 'Use the camera now'],
            ['upload', 'Upload from phone', 'Choose a file on this device'],
            ['mine', 'My photos', 'Pictures you already posted here'],
        ];
        if (CAN_GALLERY) rows.push(['season', 'Crop schedule gallery', 'Photos, drawings and maps from your seasons']);

        const menu = document.createElement('div');
        menu.className = 'attach-menu';
        menu.innerHTML = rows.map(([key, label, hint]) => `
            <button type="button" class="attach-menu-row" data-attach-src="${key}">
                <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.8" aria-hidden="true"><path stroke-linecap="round" stroke-linejoin="round" d="${SRC_ICON[key]}"/></svg>
                <span><b>${label}</b><i>${hint}</i></span>
            </button>`).join('');
        // Hung on the body, not on the form: a comment box can live inside a
        // thread modal that carries a transform, and `position: fixed` inside
        // a transformed ancestor is measured from that ancestor rather than
        // from the screen — the menu landed hundreds of pixels below the fold.
        // The form it belongs to travels on the element instead.
        menu.__form = form;
        document.body.appendChild(menu);
        // Positioned against the button that opened it, and flipped up when
        // there is no room below — a comment box often sits near the fold.
        requestAnimationFrame(() => {
            const btn = form.querySelector('.js-comment-photo');
            if (!btn) return;
            const b = btn.getBoundingClientRect();
            const m = menu.getBoundingClientRect();
            const up = b.bottom + m.height + 12 > window.innerHeight;
            menu.style.left = Math.max(8, Math.min(b.left, window.innerWidth - m.width - 8)) + 'px';
            // Clamped: near the foot of a long thread the flip can put it
            // off the top of the screen entirely.
            const top = up ? b.top - m.height - 6 : b.bottom + 6;
            menu.style.top = Math.max(8, Math.min(top, window.innerHeight - m.height - 8)) + 'px';
            menu.classList.add('is-in');
        });
        return menu;
    }

    function closeSourceMenu() {
        document.querySelectorAll('.attach-menu').forEach((m) => m.remove());
    }

    /** The camera's own input, made on demand: `capture` is what opens it. */
    function cameraInput(form) {
        let el = form.querySelector('.js-comment-camera');
        if (el) return el;
        el = document.createElement('input');
        el.type = 'file';
        el.className = 'js-comment-camera hidden';
        el.accept = 'image/*';
        el.setAttribute('capture', 'environment');
        // The chip and the submit both read .js-comment-file, so a photo
        // taken here is moved into it and nothing downstream learns a second
        // way to find a file.
        el.addEventListener('change', () => {
            const file = el.files && el.files[0];
            if (!file) return;
            const real = form.querySelector('.js-comment-file');
            try {
                const dt = new DataTransfer();
                dt.items.add(file);
                real.files = dt.files;
                real.dispatchEvent(new Event('change', { bubbles: true }));
            } catch (_) {
                window.plazaSetChip(form, file);   // older browser: chip only
            }
            el.value = '';
        });
        form.appendChild(el);
        return el;
    }

    /* A picture that is already here. The season picker sheet does the
     * listing for both sources — it takes a URL, so "my photos" is the same
     * sheet pointed somewhere else. */
    function pickExisting(form, mine) {
        if (typeof window.smPickMedia !== 'function') {
            window.toast?.('The picker is not available on this page.', 'error');
            return;
        }
        window.smPickMedia({
            endpoint: mine ? @json(route('community.my-photos')) : null,
            allSchedules: ! mine,
            kinds: 'image',
            title: mine ? 'Your photos' : 'From your seasons',
            onPick: (item) => {
                const real = form.querySelector('.js-comment-file');
                if (real) real.value = '';               // a pick replaces a file
                form.dataset.pickPath = item.path || '';
                window.plazaSetChip(form, {
                    name: item.title || 'Photo',
                    // The chip asks the same question of a pick as of a file
                    // — "is this an image?" — so it is answered in the same
                    // words a File would use.
                    type: 'image/picked',
                    previewUrl: item.url,
                });
            },
        });
    }

    document.addEventListener('click', (e) => {
        const photoBtn = e.target.closest('.js-comment-photo');
        if (photoBtn) {
            const form = photoBtn.closest('form');
            // A second tap on the same button closes it rather than redrawing.
            const open = [...document.querySelectorAll('.attach-menu')].some((m) => m.__form === form);
            closeSourceMenu();
            if (!open) sourceMenu(form);
            return;
        }

        const srcRow = e.target.closest('[data-attach-src]');
        if (srcRow) {
            const form = srcRow.closest('.attach-menu')?.__form;
            if (!form) { closeSourceMenu(); return; }
            const how = srcRow.getAttribute('data-attach-src');
            closeSourceMenu();
            if (how === 'camera') cameraInput(form).click();
            else if (how === 'upload') form.querySelector('.js-comment-file')?.click();
            else pickExisting(form, how === 'mine');
            return;
        }

        // Any other tap puts the menu away.
        if (!e.target.closest('.attach-menu')) closeSourceMenu();

        const clearBtn = e.target.closest('.js-chip-clear');
        if (clearBtn) {
            const form = clearBtn.closest('form');
            const fileInput = form.querySelector('.js-comment-file');
            if (fileInput) fileInput.value = '';
            delete form.dataset.pickPath;
            window.plazaSetChip(form, null);
        }
    });
    document.addEventListener('keydown', (e) => { if (e.key === 'Escape') closeSourceMenu(); });
    window.addEventListener('resize', closeSourceMenu);
    // A menu positioned against a button cannot follow it up the page.
    window.addEventListener('scroll', closeSourceMenu, { passive: true });
    document.addEventListener('change', (e) => {
        if (!e.target.classList || !e.target.classList.contains('js-comment-file')) return;
        // The file itself, so the chip can show the picture rather than name it.
        window.plazaSetChip(e.target.closest('form'), e.target.files[0] || null);
    });
})();
</script>
