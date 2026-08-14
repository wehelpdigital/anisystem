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
            {{-- A box with room in it. On one line the example of what to
                 write was cut off before it finished being an example, which
                 is the one job a placeholder has. --}}
            <textarea id="statusInput" class="form-textarea w-full" rows="2" maxlength="60"
                      placeholder="e.g. Aani na! 🌾 · Waiting for rain · Nagtatanim ng palay"></textarea>
            <div class="st-row">
                {{-- The same yellow smiley the wall composer uses. A button
                     whose whole job is emoji should not have to say so. --}}
                <button type="button" class="emoji-btn js-emoji-btn" data-target="statusInput" aria-label="Add an emoji" title="Add an emoji">
                    <svg class="w-5 h-5 text-amber-500 shrink-0" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" d="M14.828 14.828a4 4 0 01-5.656 0M9 10h.01M15 10h.01M21 12a9 9 0 11-18 0 9 9 0 0118 0z"/></svg>
                </button>
                <span id="statusCount" class="st-count tabular-nums">0/60</span>
            </div>
            <p class="text-xs text-gray-400 mt-1.5">Floats as a thought bubble over your photo. Leave blank and Clear to remove it.</p>
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

@push('head')
<style>
    /* The emoji button sits under the box rather than beside it: beside, it
       stole the width the placeholder needed. */
    .st-row { display: flex; align-items: center; gap: .5rem; margin-top: .5rem; }
    .st-row .emoji-btn { display: inline-flex; align-items: center; justify-content: center;
        width: 2.1rem; height: 2.1rem; border-radius: 999px; cursor: pointer;
        border: 1px solid var(--color-gray-200); background: var(--color-white);
        transition: border-color .28s cubic-bezier(.22,1,.36,1), background .28s cubic-bezier(.22,1,.36,1); }
    .st-row .emoji-btn:hover { border-color: #f0c877; background: #fffaf0; }
    .st-count { margin-left: auto; font-size: .72rem; font-weight: 600; color: var(--color-gray-400); }
    #statusInput { resize: none; line-height: 1.5; }
    html.dark .st-row .emoji-btn { background: #1c2416; border-color: #2b3a1c; }
    html.dark .st-row .emoji-btn:hover { background: #2a2416; border-color: #6b5a2a; }
    @media (prefers-reduced-motion: reduce) { .st-row .emoji-btn { transition: none; } }
</style>
@endpush

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
            // The profile draws it as .status-bubble, the composer as the
            // shared .status-cloud. Either is the thing that goes dashed
            // when there is nothing to say.
            const host = el.closest('.status-bubble, .status-cloud') || el;
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

    /* Bound two ways on purpose. The delegated listener catches a bubble
       drawn later; the direct ones catch the case the delegated one cannot —
       anything between the button and the document that swallows the click.
       A trigger that silently does nothing is the worst failure available
       here, because it looks exactly like a feature that was never built. */
    document.addEventListener('click', (e) => {
        if (e.target.closest('[data-status-bubble]')) { e.preventDefault(); open(); }
    }, true);
    document.querySelectorAll('[data-status-bubble]').forEach((el) => {
        el.addEventListener('click', (e) => { e.preventDefault(); e.stopPropagation(); open(); });
    });
    document.addEventListener('keydown', (e) => {
        if ((e.key === 'Enter' || e.key === ' ') && e.target.closest?.('[data-status-bubble]')) {
            e.preventDefault(); open();
        }
        if (e.key === 'Escape' && !modal.classList.contains('hidden')) close();
    });
    modal.addEventListener('click', (e) => { if (e.target.closest('[data-close-status]')) close(); });

    /* A bubble is one line wherever it is shown, so a return key in the box
       would be kept and then quietly ignored by every place that draws it.
       Enter saves instead, and any newline pasted in becomes a space. */
    const oneLine = (v) => v.replace(/\s*[\r\n]+\s*/g, ' ').trim().slice(0, MAXLEN);

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

    document.getElementById('statusSave')?.addEventListener('click', () => save(oneLine(input.value)));
    document.getElementById('statusClear')?.addEventListener('click', () => save(''));
    input.addEventListener('keydown', (e) => {
        if (e.key === 'Enter' && !e.shiftKey) { e.preventDefault(); save(oneLine(input.value)); }
    });
})();
</script>
@endpush
