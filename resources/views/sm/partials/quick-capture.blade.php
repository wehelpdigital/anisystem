{{--
    Quick Capture — snap one or more photos, add an optional rich-text note,
    pick a schedule, then either save them to that schedule's notebook or hand
    the first photo to the AI Technician. Included on the schedules dashboard.
    Expects: $allSchedules (id, title).
--}}
@push('head')
<style>
    .qc-overlay { position: fixed; inset: 0; z-index: 60; background: rgba(17,24,39,.55); display: flex; align-items: flex-end; justify-content: center; padding: 0; }
    @media (min-width: 640px) { .qc-overlay { align-items: center; padding: 1rem; } }
    .qc-modal { background: var(--color-white, #fff); width: 100%; max-width: 34rem; max-height: 92vh; display: flex; flex-direction: column;
        border-radius: 1rem 1rem 0 0; overflow: hidden; animation: qc-rise .22s ease; }
    @media (min-width: 640px) { .qc-modal { border-radius: 1rem; } }
    @keyframes qc-rise { from { transform: translateY(24px); opacity: .6; } to { transform: none; opacity: 1; } }
    .qc-head { display: flex; align-items: center; justify-content: space-between; gap: .75rem; padding: 1rem 1.25rem; border-bottom: 1px solid #eef0f3; }
    .qc-body { padding: 1.25rem; overflow-y: auto; }
    .qc-foot { display: flex; gap: .5rem; padding: 1rem 1.25rem; border-top: 1px solid #eef0f3; }
    .qc-grid { display: grid; grid-template-columns: repeat(3, 1fr); gap: .5rem; }
    .qc-thumb { position: relative; aspect-ratio: 1; border-radius: .6rem; overflow: hidden; background: #f3f4f6; }
    .qc-thumb img { width: 100%; height: 100%; object-fit: cover; }
    .qc-thumb button { position: absolute; top: .25rem; right: .25rem; width: 1.5rem; height: 1.5rem; border-radius: 999px;
        background: rgba(17,24,39,.7); color: #fff; display: flex; align-items: center; justify-content: center; font-size: 14px; line-height: 1; }
    .qc-add { display: flex; flex-direction: column; align-items: center; justify-content: center; gap: .35rem; aspect-ratio: 1;
        border: 1.5px dashed #cbd5e1; border-radius: .6rem; color: #64748b; font-size: 12px; font-weight: 600; cursor: pointer; background: #fafafa; }
    .qc-add:hover { border-color: #94a3b8; color: #475569; }
    .qc-target { display: flex; align-items: flex-start; gap: .6rem; padding: .75rem; border: 1.5px solid #e5e7eb; border-radius: .7rem; cursor: pointer; }
    .qc-target.is-on { border-color: #4a7c2a; background: #f3f8ec; }
    .qc-target input { margin-top: .2rem; }
    .qc-editor-wrap .ql-container { min-height: 6rem; border-bottom-left-radius: .6rem; border-bottom-right-radius: .6rem; }
    .qc-editor-wrap .ql-toolbar { border-top-left-radius: .6rem; border-top-right-radius: .6rem; }
    html.dark .qc-modal { background: var(--color-gray-800, #1f2430); color: var(--color-gray-100); }
    html.dark .qc-head, html.dark .qc-foot { border-color: rgba(255,255,255,.08); }
</style>
@endpush

<div id="quickCaptureModal" class="qc-overlay hidden" aria-hidden="true">
    <div class="qc-modal" role="dialog" aria-modal="true" aria-label="Quick capture">
        <div class="qc-head">
            <h3 class="font-bold text-gray-900" id="qcTitle">Quick Capture</h3>
            <button type="button" id="qcClose" class="btn-ghost p-2 rounded-full" aria-label="Close">
                <svg class="w-5 h-5" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" d="M6 6l12 12M18 6L6 18"/></svg>
            </button>
        </div>

        {{-- STEP 1 — capture --}}
        <div data-qc-step="capture">
            <div class="qc-body">
                <p class="text-sm text-gray-600 mb-3">Snap your crop, a pest, the soil — anything worth remembering. Add as many as you like.</p>
                <div class="qc-grid" id="qcPreviews">
                    <button type="button" class="qc-add" id="qcAddPhoto">
                        <svg class="w-6 h-6" fill="none" stroke="currentColor" stroke-width="1.8" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" d="M12 5v14m-7-7h14"/></svg>
                        Add photo
                    </button>
                </div>
                <input type="file" id="qcFile" accept="image/*" capture="environment" class="hidden">
            </div>
            <div class="qc-foot">
                <button type="button" class="btn btn-ghost" data-qc-cancel>Cancel</button>
                <button type="button" class="btn btn-primary ml-auto" id="qcContinue" disabled>Continue</button>
            </div>
        </div>

        {{-- STEP 2 — details --}}
        <div data-qc-step="details" class="hidden">
            <div class="qc-body space-y-4">
                <div>
                    <label class="form-label">Add a note <span class="text-gray-400 font-normal">(optional)</span></label>
                    <div class="qc-editor-wrap"><div id="qcEditor"></div></div>
                </div>
                <div>
                    <label class="form-label" for="qcSchedule">Connect to schedule</label>
                    <select id="qcSchedule" class="form-input">
                        @foreach ($allSchedules as $s)
                            <option value="{{ $s->id }}">{{ $s->title }}</option>
                        @endforeach
                    </select>
                </div>
                <div>
                    <span class="form-label">What should we do with it?</span>
                    <div class="grid gap-2 mt-1.5">
                        <label class="qc-target is-on" data-qc-target-row>
                            <input type="radio" name="qcTarget" value="note" checked>
                            <span>
                                <span class="block font-semibold text-gray-900">Save to notes</span>
                                <span class="block text-xs text-gray-500">Keep the photos in this schedule's notebook.</span>
                            </span>
                        </label>
                        <label class="qc-target" data-qc-target-row>
                            <input type="radio" name="qcTarget" value="ai">
                            <span>
                                <span class="block font-semibold text-gray-900">Ask the AI Technician</span>
                                <span class="block text-xs text-gray-500">Get advice on the first photo (uses AI Credits).</span>
                            </span>
                        </label>
                    </div>
                </div>
            </div>
            <div class="qc-foot">
                <button type="button" class="btn btn-ghost" data-qc-back>Back</button>
                <button type="button" class="btn btn-primary ml-auto" id="qcConfirm">Confirm</button>
            </div>
        </div>

        {{-- STEP 3 — result --}}
        <div data-qc-step="result" class="hidden">
            <div class="qc-body">
                <div id="qcResult" class="text-sm text-gray-700"></div>
            </div>
            <div class="qc-foot">
                <a id="qcResultLink" href="#" class="btn btn-white hidden">Open</a>
                <button type="button" class="btn btn-primary ml-auto" data-qc-cancel>Done</button>
            </div>
        </div>
    </div>
</div>

@push('scripts')
<script>
document.addEventListener('DOMContentLoaded', () => {
    const modal = document.getElementById('quickCaptureModal');
    if (!modal) return;
    const $ = (id) => document.getElementById(id);
    const CSRF = document.querySelector('meta[name=csrf-token]')?.content || '';
    const NOTES_URL = @json(route('quick-capture.notes'));
    const AI_PHOTO_URL = @json(route('ai.photo'));
    const AI_ASK_URL = @json(route('ai.ask'));

    let files = [];          // captured File objects, in order
    let quill = null;

    /* ---- Quill, lazy-loaded from CDN (open-source, no paid tier) ---- */
    function loadQuill() {
        if (window.Quill) return Promise.resolve();
        return new Promise((resolve, reject) => {
            const css = document.createElement('link');
            css.rel = 'stylesheet'; css.href = 'https://cdn.jsdelivr.net/npm/quill@2.0.3/dist/quill.snow.css';
            document.head.appendChild(css);
            const s = document.createElement('script');
            s.src = 'https://cdn.jsdelivr.net/npm/quill@2.0.3/dist/quill.min.js';
            s.onload = resolve; s.onerror = reject; document.head.appendChild(s);
        });
    }
    async function ensureQuill() {
        await loadQuill();
        if (!quill) {
            quill = new Quill('#qcEditor', {
                theme: 'snow', placeholder: 'What did you notice?',
                modules: { toolbar: [['bold', 'italic', 'underline'], [{ list: 'ordered' }, { list: 'bullet' }], ['link', 'clean']] },
            });
        }
        return quill;
    }

    function showStep(step) {
        modal.querySelectorAll('[data-qc-step]').forEach((el) => {
            el.classList.toggle('hidden', el.getAttribute('data-qc-step') !== step);
        });
    }

    function open() {
        files = [];
        renderPreviews();
        if (quill) quill.setText('');
        showStep('capture');
        modal.classList.remove('hidden');
        document.body.style.overflow = 'hidden';
    }
    function close() {
        modal.classList.add('hidden');
        document.body.style.overflow = '';
    }

    function renderPreviews() {
        const grid = $('qcPreviews');
        grid.querySelectorAll('[data-qc-thumb]').forEach((n) => n.remove());
        const addBtn = $('qcAddPhoto');
        files.forEach((file, i) => {
            const div = document.createElement('div');
            div.className = 'qc-thumb'; div.setAttribute('data-qc-thumb', i);
            const img = document.createElement('img');
            img.src = URL.createObjectURL(file); img.alt = 'Captured photo';
            const rm = document.createElement('button');
            rm.type = 'button'; rm.innerHTML = '&times;'; rm.setAttribute('aria-label', 'Remove photo');
            rm.addEventListener('click', () => { files.splice(i, 1); renderPreviews(); });
            div.appendChild(img); div.appendChild(rm);
            grid.insertBefore(div, addBtn);
        });
        $('qcContinue').disabled = files.length === 0;
    }

    /* ---- entry points ---- */
    $('quickCaptureBtn')?.addEventListener('click', open);
    $('quickCaptureFab')?.addEventListener('click', open);
    $('qcClose').addEventListener('click', close);
    modal.querySelectorAll('[data-qc-cancel]').forEach((b) => b.addEventListener('click', close));
    modal.addEventListener('click', (e) => { if (e.target === modal) close(); });

    /* ---- capture step ---- */
    $('qcAddPhoto').addEventListener('click', () => $('qcFile').click());
    $('qcFile').addEventListener('change', (e) => {
        const f = e.target.files && e.target.files[0];
        if (f) { files.push(f); renderPreviews(); }
        e.target.value = '';   // let the same shot be re-taken
    });
    $('qcContinue').addEventListener('click', async () => {
        showStep('details');
        try { await ensureQuill(); } catch (_) { /* editor optional */ }
    });

    /* ---- details step ---- */
    modal.querySelectorAll('[data-qc-back]').forEach((b) => b.addEventListener('click', () => showStep('capture')));
    modal.querySelectorAll('[data-qc-target-row]').forEach((row) => {
        row.addEventListener('click', () => {
            modal.querySelectorAll('[data-qc-target-row]').forEach((r) => r.classList.remove('is-on'));
            row.classList.add('is-on');
        });
    });

    function noteHtml() { return quill ? quill.root.innerHTML : ''; }
    function noteText() { return quill ? quill.getText().trim() : ''; }

    $('qcConfirm').addEventListener('click', async () => {
        if (!files.length) { toast('Capture a photo first.', 'error'); showStep('capture'); return; }
        const scheduleId = $('qcSchedule').value;
        const target = modal.querySelector('input[name=qcTarget]:checked')?.value || 'note';
        const btn = $('qcConfirm');
        btn.disabled = true; const orig = btn.textContent; btn.textContent = 'Working…';
        try {
            if (target === 'note') {
                await saveNotes(scheduleId);
            } else {
                await askAi(scheduleId);
            }
        } catch (err) {
            toast(err.message || 'Something went wrong.', 'error');
        } finally {
            btn.disabled = false; btn.textContent = orig;
        }
    });

    async function saveNotes(scheduleId) {
        const fd = new FormData();
        fd.append('scheduleId', scheduleId);
        const html = noteHtml();
        if (html && html !== '<p><br></p>') fd.append('note', html);
        files.forEach((f) => fd.append('images[]', f));
        const res = await fetch(NOTES_URL, {
            method: 'POST',
            headers: { 'X-CSRF-TOKEN': CSRF, Accept: 'application/json' },
            body: fd,
        });
        const data = await res.json().catch(() => ({}));
        if (!res.ok || !data.success) throw new Error(data.message || 'Could not save.');
        $('qcResult').innerHTML = `<div class="flex items-center gap-2 text-brand-700 font-semibold mb-1">
            <svg class="w-5 h-5" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" d="M5 13l4 4L19 7"/></svg>
            ${data.message}</div><p class="text-gray-500 text-sm">Find them anytime in this schedule's Notes module.</p>`;
        const link = $('qcResultLink');
        link.href = data.notesUrl; link.classList.remove('hidden'); link.textContent = 'Open notes';
        $('qcTitle').textContent = 'Saved';
        showStep('result');
        toast(data.message);
    }

    async function askAi(scheduleId) {
        // Upload the first photo, then ask the AI about it.
        const fd = new FormData();
        fd.append('image', files[0]);
        fd.append('scheduleId', scheduleId);
        const up = await fetch(AI_PHOTO_URL, {
            method: 'POST',
            headers: { 'X-CSRF-TOKEN': CSRF, Accept: 'application/json' },
            body: fd,
        });
        const upData = await up.json().catch(() => ({}));
        if (!up.ok || !upData.success) throw new Error(upData.message || 'Photo upload failed.');
        const imagePath = upData.data?.path || upData.data?.imagePath || upData.path;

        const message = noteText() || 'Please take a look at this photo of my crop and tell me what you notice and what I should do.';
        const ask = await fetch(AI_ASK_URL, {
            method: 'POST',
            headers: { 'X-CSRF-TOKEN': CSRF, 'Content-Type': 'application/json', Accept: 'application/json' },
            body: JSON.stringify({ message, imagePath, scheduleId }),
        });
        const askData = await ask.json().catch(() => ({}));
        if (!ask.ok || !askData.success) throw new Error(askData.message || 'The AI Technician could not answer right now.');

        const reply = askData.data?.answer?.content || 'Answer received.';
        $('qcResult').innerHTML = `<div class="font-semibold text-gray-900 mb-2">AI Technician says:</div>
            <div class="text-sm text-gray-700 whitespace-pre-line">${escapeHtml(reply)}</div>
            ${files.length > 1 ? '<p class="text-xs text-gray-400 mt-2">Only the first photo was sent to the AI.</p>' : ''}`;
        const link = $('qcResultLink');
        link.href = @json(url('/app/sm-activities')) + '?id=' + scheduleId + '&module=ai';
        link.classList.remove('hidden'); link.textContent = 'Open AI Technician';
        $('qcTitle').textContent = 'AI Technician';
        showStep('result');
    }

    function escapeHtml(s) {
        const d = document.createElement('div'); d.textContent = String(s ?? ''); return d.innerHTML;
    }
});
</script>
@endpush
