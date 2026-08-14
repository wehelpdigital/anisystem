{{-- What's on your mind, asked once and shared.

     Any element carrying `data-status-bubble` opens this; every element
     carrying `data-status-text` is repainted when it is saved, so the bubble
     over your profile photo and the one on the dashboard composer never
     disagree. Include once per page, for your own account only. --}}
<div id="statusModal" class="plaza-modal hidden" role="dialog" aria-modal="true" aria-label="Set your status">
    <div class="plaza-modal-backdrop" data-close-status></div>
    <div class="plaza-modal-card" style="max-width:24rem">
        <div class="plaza-modal-head">
            <p class="font-bold text-gray-900">What are you thinking now?</p>
            <button type="button" class="btn-ghost rounded-full w-8 h-8 flex items-center justify-center text-gray-400 hover:text-gray-700" data-close-status aria-label="Close">✕</button>
        </div>
        <div class="plaza-modal-body">
            <div class="flex items-center gap-2">
                <input type="text" id="statusInput" class="form-input grow" maxlength="60" placeholder="e.g. Aani na! 🌾 · Waiting for rain · Nagtatanim ng palay">
                <button type="button" class="emoji-btn js-emoji-btn shrink-0" data-target="statusInput" aria-label="Add an emoji" title="Emoji">
                    <svg class="w-5 h-5" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" d="M14.828 14.828a4 4 0 01-5.656 0M9 10h.01M15 10h.01M21 12a9 9 0 11-18 0 9 9 0 0118 0z"/></svg>
                </button>
            </div>
            <div class="flex items-center justify-between mt-1.5">
                <p class="text-xs text-gray-400">Floats as a thought bubble over your photo. Leave blank and Clear to remove it.</p>
                <span id="statusCount" class="text-xs text-gray-400 font-medium shrink-0 ml-2 tabular-nums">0/60</span>
            </div>
        </div>
        <div class="plaza-modal-foot flex items-center justify-between">
            <button type="button" id="statusClear" class="btn btn-ghost btn-sm text-red-500 hover:bg-red-50">Clear status</button>
            <div class="flex gap-2">
                <button type="button" class="btn btn-white btn-sm" data-close-status>Cancel</button>
                <button type="button" id="statusSave" class="btn btn-primary btn-sm">Save</button>
            </div>
        </div>
    </div>
</div>

@push('scripts')
<script>
(function statusBubble() {
    if (window.openStatusBubble) return;
    const modal = document.getElementById('statusModal');
    const input = document.getElementById('statusInput');
    if (!modal || !input) return;

    const EMPTY_LABEL = "💭 What's on your mind?";
    const MAXLEN = 60;                 // one tidy line; the server enforces the same
    let current = @json(auth()->user()?->statusBubble ?? '');

    const countEl = document.getElementById('statusCount');
    const updateCount = () => { if (countEl) countEl.textContent = input.value.length + '/' + MAXLEN; };
    input.addEventListener('input', updateCount);

    /* Repaint everywhere it shows. Each place says how it wants to read when
       there is nothing to say — the profile wants the invitation spelled out,
       a small bubble on the composer wants a single emoji — so the fallback
       comes off the element rather than being decided here. */
    function paint(val) {
        current = val || '';
        document.querySelectorAll('[data-status-text]').forEach((el) => {
            const empty = el.getAttribute('data-empty-label') ?? EMPTY_LABEL;
            el.textContent = current || empty;
            el.setAttribute('data-empty', current ? '0' : '1');
            const host = el.closest('.status-bubble') || el;
            host.classList.toggle('is-empty', !current);
        });
    }

    const open = () => {
        input.value = current;
        updateCount();
        modal.classList.remove('hidden');
        void modal.offsetWidth;
        modal.classList.add('is-open');
        document.body.style.overflow = 'hidden';
        // The keyboard waits until they have read the question.
        window.smFocus ? window.smFocus(input, { delay: 60 }) : null;
    };
    const close = () => {
        modal.classList.remove('is-open');
        document.body.style.overflow = '';
        setTimeout(() => modal.classList.add('hidden'), 250);
    };
    window.openStatusBubble = open;

    // Delegated, so a bubble drawn after this script still opens it.
    document.addEventListener('click', (e) => {
        if (e.target.closest('[data-status-bubble]')) { e.preventDefault(); open(); }
    });
    document.addEventListener('keydown', (e) => {
        if ((e.key === 'Enter' || e.key === ' ') && e.target.closest?.('[data-status-bubble]')) {
            e.preventDefault(); open();
        }
        if (e.key === 'Escape' && !modal.classList.contains('hidden')) close();
    });
    modal.addEventListener('click', (e) => { if (e.target.closest('[data-close-status]')) close(); });

    async function save(val) {
        try {
            const res = await fetch(@json(route('community.status.update')), {
                method: 'POST',
                headers: {
                    'X-CSRF-TOKEN': document.querySelector('meta[name=csrf-token]').content,
                    'Content-Type': 'application/json',
                    Accept: 'application/json',
                },
                body: JSON.stringify({ statusBubble: val }),
                credentials: 'same-origin',
            });
            const data = await res.json();
            if (!data.success) throw new Error(data.message || 'Could not update.');
            paint(val);
            close();
            window.toast?.(data.message);
        } catch (err) {
            window.toast?.(err.message || 'Network error — try again.', 'error');
        }
    }

    document.getElementById('statusSave')?.addEventListener('click', () => save(input.value.trim().slice(0, MAXLEN)));
    document.getElementById('statusClear')?.addEventListener('click', () => save(''));
    input.addEventListener('keydown', (e) => { if (e.key === 'Enter') { e.preventDefault(); save(input.value.trim().slice(0, MAXLEN)); } });
})();
</script>
@endpush
