{{-- ============================================================
     Schedule share sheet — opened by #shareScheduleBtn.
     Options: copy/external link, Facebook, native share, and
     "send to a co-farmer" via community messaging. Non-members who
     open the public link are prompted to register on that page.
     Expects: $schedule (with a shareToken).
     ============================================================ --}}
@php
    $publicUrl = route('share.schedule', $schedule->shareToken);
    $shareTitle = $schedule->title . ' — cropping plan on anee.io';
@endphp
<div class="sheet hidden" id="shareScheduleSheet" style="--sheet-width:30rem">
    <div class="sheet-handle"></div>
    <div class="sheet-header">
        <h3 class="sheet-title">Share this schedule</h3>
        <button data-sheet-close class="btn-ghost p-2 rounded-full" aria-label="Close">✕</button>
    </div>
    <div class="sheet-body space-y-4">

        {{-- External link --}}
        <div>
            <label class="form-label">Public link</label>
            <div class="flex items-center gap-2">
                <input type="text" id="shareLinkInput" class="form-input grow" readonly value="{{ $publicUrl }}"
                    style="font-size:.82rem;" onclick="this.select()">
                <button type="button" id="shareCopyBtn" class="btn btn-white btn-sm shrink-0">Copy</button>
            </div>
            <p class="form-hint">Anyone with the link can view the plan. People who aren't members yet are invited to register when they open it.</p>
        </div>

        {{-- Social --}}
        <div>
            <label class="form-label">Post to social</label>
            <div class="grid grid-cols-2 gap-2">
                <button type="button" id="shareFbBtn" class="btn btn-white flex items-center justify-center gap-2">
                    <svg class="w-5 h-5" viewBox="0 0 24 24" fill="#1877f2"><path d="M24 12.07C24 5.4 18.63 0 12 0S0 5.4 0 12.07C0 18.1 4.39 23.1 10.13 24v-8.44H7.08v-3.49h3.05V9.41c0-3.02 1.79-4.69 4.53-4.69 1.31 0 2.68.24 2.68.24v2.97h-1.51c-1.49 0-1.96.93-1.96 1.89v2.25h3.33l-.53 3.49h-2.8V24C19.61 23.1 24 18.1 24 12.07z"/></svg>
                    Facebook
                </button>
                <button type="button" id="shareNativeBtn" class="btn btn-white flex items-center justify-center gap-2">
                    <svg class="w-5 h-5" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" d="M8.684 13.342C8.886 12.938 9 12.482 9 12c0-.482-.114-.938-.316-1.342m0 2.684a3 3 0 110-2.684m0 2.684l6.632 3.316m-6.632-6l6.632-3.316m0 0a3 3 0 105.367-2.684 3 3 0 00-5.367 2.684zm0 9.316a3 3 0 105.368 2.684 3 3 0 00-5.368-2.684z"/></svg>
                    More…
                </button>
            </div>
        </div>

        {{-- Community PM --}}
        <div>
            <label class="form-label">Send to a co-farmer</label>
            <div id="shareCofarmers" class="space-y-1 max-h-56 overflow-y-auto rounded-xl border border-gray-100 p-1">
                <p class="text-sm text-gray-400 px-2 py-3 text-center" id="shareCofarmersHint">Loading…</p>
            </div>
        </div>
    </div>
</div>

@push('scripts')
<script>
(function scheduleShareSheet() {
    const PUBLIC_URL = @json($publicUrl);
    const SHARE_TITLE = @json($shareTitle);
    const COFARMERS_URL = @json(route('community.cofarmers.list'));
    const SEND_URL = @json(url('/app/community/messages')); // + /{userId}
    const esc = window.escapeHtml || ((s) => String(s ?? ''));
    const $ = (id) => document.getElementById(id);

    document.getElementById('shareScheduleBtn')?.addEventListener('click', () => {
        window.openSheet('shareScheduleSheet');
        loadCofarmers();
    });

    // --- Copy link
    $('shareCopyBtn')?.addEventListener('click', async () => {
        const input = $('shareLinkInput');
        try {
            await navigator.clipboard.writeText(PUBLIC_URL);
        } catch (_) {
            input.select(); document.execCommand('copy');
        }
        window.toast('Link copied.');
    });

    // --- Facebook sharer (scrapes the public page's OG tags for a thumbnail)
    $('shareFbBtn')?.addEventListener('click', () => {
        const u = 'https://www.facebook.com/sharer/sharer.php?u=' + encodeURIComponent(PUBLIC_URL);
        window.open(u, '_blank', 'noopener,width=680,height=640');
    });

    // --- Native share (Messenger, WhatsApp, Viber… on supported devices)
    $('shareNativeBtn')?.addEventListener('click', async () => {
        if (navigator.share) {
            try { await navigator.share({ title: SHARE_TITLE, url: PUBLIC_URL }); } catch (_) { /* cancelled */ }
        } else {
            const u = 'https://twitter.com/intent/tweet?text=' + encodeURIComponent(SHARE_TITLE) + '&url=' + encodeURIComponent(PUBLIC_URL);
            window.open(u, '_blank', 'noopener,width=600,height=520');
        }
    });

    // --- Co-farmer list → send the link as a message
    let cofarmersLoaded = false;
    async function loadCofarmers() {
        if (cofarmersLoaded) return;
        const box = $('shareCofarmers');
        try {
            const res = await window.api(COFARMERS_URL);
            const items = (res.data && res.data.items) || [];
            cofarmersLoaded = true;
            if (!items.length) {
                box.innerHTML = '<p class="text-sm text-gray-400 px-2 py-3 text-center">No co-farmers yet. Connect with members in the Community.</p>';
                return;
            }
            box.innerHTML = items.map((u) => {
                const av = u.avatar
                    ? `<img src="${esc(u.avatar)}" alt="" class="w-8 h-8 rounded-full object-cover shrink-0">`
                    : `<span class="w-8 h-8 rounded-full bg-brand-100 text-brand-700 text-xs font-bold flex items-center justify-center shrink-0">${esc(u.initials || '?')}</span>`;
                const btn = u.allowMessages
                    ? `<button type="button" class="btn btn-white btn-sm shrink-0" data-share-send="${u.id}">Send</button>`
                    : `<span class="text-xs text-gray-400 shrink-0">Messages off</span>`;
                return `<div class="flex items-center gap-2 px-2 py-1.5 rounded-lg hover:bg-gray-50" data-cf="${u.id}">
                    ${av}<span class="grow min-w-0 truncate text-sm font-semibold text-gray-800">${esc(u.name)}</span>${btn}</div>`;
            }).join('');
        } catch (err) {
            box.innerHTML = `<p class="text-sm text-red-500 px-2 py-3 text-center">${esc(err.message)}</p>`;
        }
    }

    $('shareCofarmers')?.addEventListener('click', async (e) => {
        const send = e.target.closest('[data-share-send]');
        if (!send) return;
        const userId = send.getAttribute('data-share-send');
        send.disabled = true;
        const original = send.textContent;
        send.textContent = 'Sending…';
        try {
            await window.api(SEND_URL + '/' + userId, {
                method: 'POST',
                body: { body: SHARE_TITLE + '\n' + PUBLIC_URL },
            });
            send.textContent = 'Sent ✓';
            window.toast('Shared with your co-farmer.');
        } catch (err) {
            send.disabled = false;
            send.textContent = original;
            window.toast(err.message, 'error');
        }
    });
})();
</script>
@endpush
