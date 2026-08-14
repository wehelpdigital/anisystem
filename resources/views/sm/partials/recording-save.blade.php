{{-- What to call the thing you just recorded.

     Asked the moment the recording stops, while everyone still remembers
     what it was of. A file named "team-recording-1755203841.webm" is one
     nobody opens again, and the Team box is only useful if the things in it
     say what they are.

     Exposes window.smAskRecording({ sizeMB, onSave({title, description}) }).
     Include once per page. --}}
<div class="rs-modal hidden" id="recSaveModal" role="dialog" aria-modal="true" aria-label="Save this recording">
    <div class="rs-backdrop" data-rs-cancel></div>
    <div class="rs-card">
        <div class="rs-head">
            <span class="rs-dot" aria-hidden="true"></span>
            <p class="font-bold text-gray-900">Save this recording</p>
            <span class="rs-size" id="recSaveSize"></span>
        </div>
        <div class="rs-body space-y-3">
            <div>
                <label class="form-label" for="recSaveTitle">Title <span class="text-red-500">*</span></label>
                <input type="text" id="recSaveTitle" class="form-input" maxlength="191"
                       placeholder="e.g. Walkthrough of the flooded corner" autocomplete="off">
            </div>
            <div>
                <label class="form-label" for="recSaveDesc">Description <span class="text-gray-400 font-normal">(optional)</span></label>
                <textarea id="recSaveDesc" class="form-textarea" rows="3" maxlength="2000"
                          placeholder="What was being shown, and who was there?"></textarea>
            </div>
            <p class="form-hint">Saved to the Team box in the Gallery, where everyone on the schedule can find it.</p>
        </div>
        <div class="rs-foot">
            <button type="button" class="btn btn-ghost" data-rs-cancel>Discard</button>
            <button type="button" class="btn btn-primary ml-auto" id="recSaveGo">Save</button>
        </div>
    </div>
</div>

@push('head')
<style>
    .rs-modal { position: fixed; inset: 0; z-index: 170; display: flex; align-items: center; justify-content: center; padding: 1rem; }
    .rs-modal.hidden { display: none; }
    .rs-backdrop { position: absolute; inset: 0; background: rgb(0 0 0 / .6); opacity: 0;
        transition: opacity .28s cubic-bezier(.22,1,.36,1); }
    .rs-modal.is-open .rs-backdrop { opacity: 1; }
    .rs-card { position: relative; width: 100%; max-width: 26rem; background: var(--color-white);
        border-radius: 1rem; overflow: hidden; box-shadow: var(--shadow-card-lg);
        transform: translateY(1rem) scale(.98); opacity: 0;
        transition: transform .28s cubic-bezier(.22,1,.36,1), opacity .28s cubic-bezier(.22,1,.36,1); }
    .rs-modal.is-open .rs-card { transform: none; opacity: 1; }
    .rs-head { display: flex; align-items: center; gap: .5rem; padding: .75rem 1rem;
        border-bottom: 1px solid var(--color-gray-100); }
    .rs-dot { width: .6rem; height: .6rem; border-radius: 999px; background: #ef4444; flex: none; }
    .rs-size { margin-left: auto; font-size: .68rem; font-weight: 700; color: var(--color-gray-400); }
    .rs-body { padding: 1rem; }
    .rs-foot { display: flex; align-items: center; gap: .5rem; padding: .75rem 1rem;
        border-top: 1px solid var(--color-gray-100); }
    html.dark .rs-card { background: #151b12; }
    html.dark .rs-head, html.dark .rs-foot { border-color: #2b3a1c; }
    @media (prefers-reduced-motion: reduce) { .rs-backdrop, .rs-card { transition: none; } }
</style>
@endpush

@push('scripts')
<script>
(function recordingSave() {
    const modal = document.getElementById('recSaveModal');
    if (!modal || window.smAskRecording) return;
    const $ = (id) => document.getElementById(id);
    let cfg = null;

    function close() {
        modal.classList.remove('is-open');
        document.body.style.overflow = '';
        setTimeout(() => modal.classList.add('hidden'), 260);
        cfg = null;
    }

    window.smAskRecording = function (opts) {
        cfg = opts || {};
        $('recSaveTitle').value = '';
        $('recSaveDesc').value = '';
        $('recSaveSize').textContent = cfg.sizeMB ? cfg.sizeMB.toFixed(1) + ' MB' : '';
        modal.classList.remove('hidden');
        void modal.offsetWidth;
        modal.classList.add('is-open');
        document.body.style.overflow = 'hidden';
        // The keyboard waits: read the question, then answer it.
        window.smFocus?.($('recSaveTitle'), { delay: 260 });
        window.registerOverlay?.('recSave', close);
    };

    modal.addEventListener('click', (e) => {
        if (!e.target.closest('[data-rs-cancel]')) return;
        // Discarding loses the only copy, so it asks first.
        const go = () => close();
        if (window.confirmAction) {
            window.confirmAction({
                title: 'Discard this recording?',
                message: 'It has not been saved anywhere. This cannot be undone.',
                confirmText: 'Discard', danger: true,
            }).then((ok) => { if (ok) go(); });
        } else if (confirm('Discard this recording?')) go();
    });

    $('recSaveGo').addEventListener('click', () => {
        const title = $('recSaveTitle').value.trim();
        if (!title) { window.toast?.('Give the recording a title.', 'error'); $('recSaveTitle').focus(); return; }
        const fn = cfg?.onSave;
        const description = $('recSaveDesc').value.trim();
        close();
        fn?.({ title, description });
    });
})();
</script>
@endpush
