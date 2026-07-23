@extends(request()->boolean('partial') ? 'layouts.partial' : 'layouts.app')

@section('title', 'Documentation â€” ' . $schedule->title)
@section('page-title', 'Documentation')
@section('page-subtitle', $schedule->title)
@section('back', route('sm.hub', ['id' => $schedule->id]))

@php
    $protocol = $schedule->protocol;
    $hasProtocolFile = $protocol && $protocol->protocolFile;
@endphp

@section('content')
@include('sm.partials.module-header', ['schedule' => $schedule, 'module' => 'documentation'])

<div x-data="{ tab: 'protocol' }">

    {{-- Sub-tab pills (horizontal scroll on mobile) --}}
    <div class="scroll-chips mb-4">
        <button type="button" class="chip shrink-0" :class="tab === 'protocol' && 'is-selected'" @click="tab = 'protocol'">
            Protocol
        </button>
        <button type="button" class="chip shrink-0" :class="tab === 'intro' && 'is-selected'"
            @click="tab = 'intro'; document.dispatchEvent(new CustomEvent('doc-intro-shown'))">
            Introduction
        </button>
        <button type="button" class="chip shrink-0" :class="tab === 'attachments' && 'is-selected'" @click="tab = 'attachments'">
            Attachments
            <span class="badge badge-gray" id="docAttachmentsCount">{{ $schedule->attachments->count() }}</span>
        </button>
        <button type="button" class="chip shrink-0" :class="tab === 'rules' && 'is-selected'" @click="tab = 'rules'">
            Critical Rules
            <span class="badge badge-gray" id="docRulesCount">{{ $schedule->criticalRules->count() }}</span>
        </button>
    </div>

    {{-- ============================== 1) PROTOCOL ============================== --}}
    <div x-show="tab === 'protocol'">
        <div class="card p-4 sm:p-6">
            <h2 class="font-bold text-gray-900 text-lg">Protocol Document</h2>
            <p class="text-sm text-gray-500 mt-1 mb-4">Write the season protocol as text, attach a document, or both. It prints on the worker presentation and export.</p>

            <div class="mb-4">
                <label class="form-label" for="protocolContent">Protocol Text</label>
                <textarea id="protocolContent" class="form-textarea" rows="10" placeholder="Write or paste the protocol hereâ€¦">{{ $protocol->protocolContent ?? '' }}</textarea>
            </div>

            <div class="mb-4">
                <label class="form-label" for="protocolFile">Protocol File</label>
                <div id="protocolCurrentFile" class="{{ $hasProtocolFile ? '' : 'hidden' }} mb-2">
                    <a href="{{ route('sm.protocol.download', ['scheduleId' => $schedule->id]) }}"
                        class="badge badge-green !text-sm !py-1.5 !px-3 hover:bg-brand-200 transition" download>
                        <svg class="w-4 h-4" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" d="M4 16v2a2 2 0 002 2h12a2 2 0 002-2v-2M12 4v12m0 0l-4-4m4 4l4-4"/></svg>
                        <span id="protocolCurrentFileName">{{ $protocol->protocolFileOriginalName ?? 'protocol' }}</span>
                    </a>
                </div>
                <input type="file" id="protocolFile" accept=".pdf,.doc,.docx,.txt"
                    class="block w-full text-sm text-gray-600 file:mr-3 file:rounded-lg file:border-0 file:bg-brand-50 file:text-brand-700 file:font-semibold file:px-4 file:py-2.5 file:cursor-pointer cursor-pointer">
                <p class="form-hint">PDF, DOC, DOCX or TXT â€” max 10 MB. Uploading a new file replaces the current one.</p>
            </div>

            <button type="button" class="btn btn-primary w-full sm:w-auto" id="protocolSaveBtn">Save Protocol</button>
        </div>
    </div>

    {{-- ============================ 2) INTRODUCTION ============================ --}}
    <div x-show="tab === 'intro'" x-cloak>
        <div class="card p-4 sm:p-6">
            <h2 class="font-bold text-gray-900 text-lg">Introduction</h2>
            <p class="text-sm text-gray-500 mt-1 mb-4">
                Rich-text introduction shown to workers above the activity timeline.
                @if ($activeVersion)
                    Stored on the active version <span class="badge badge-green">{{ $activeVersion->versionName }}</span>
                @endif
            </p>

            @if ($activeVersion)
                <div id="introIdle">
                    <div id="introPreview" class="rounded-xl border border-gray-200 bg-gray-50 p-4 text-sm text-gray-800 overflow-x-auto">
                        @if (filled($activeVersion->globalActivityNote))
                            {!! $activeVersion->globalActivityNote !!}
                        @else
                            <p class="text-gray-400 italic">No introduction yet.</p>
                        @endif
                    </div>
                    <div class="flex flex-wrap gap-2 mt-3">
                        <button type="button" class="btn btn-primary" id="introEditBtn">Edit Introduction</button>
                        <button type="button" class="btn btn-danger-outline {{ filled($activeVersion->globalActivityNote) ? '' : 'hidden' }}" id="introClearBtn">Clear</button>
                    </div>
                </div>
                <div id="introEditWrap" class="hidden">
                    <div class="rounded-xl border border-gray-200 overflow-hidden bg-white">
                        <div id="introEditor" style="min-height: 14rem;"></div>
                    </div>
                    <div class="flex flex-wrap gap-2 mt-3 justify-end">
                        <button type="button" class="btn btn-ghost" id="introCancelBtn">Cancel</button>
                        <button type="button" class="btn btn-primary" id="introSaveBtn">Save Introduction</button>
                    </div>
                </div>
            @else
                <p class="text-sm text-gray-500">No activity version exists for this schedule yet â€” the Introduction becomes available once the schedule has its first version (created automatically with your first activity).</p>
            @endif
        </div>
    </div>

    {{-- ============================ 3) ATTACHMENTS ============================ --}}
    <div x-show="tab === 'attachments'" x-cloak>
        <button type="button" class="btn btn-primary w-full mb-4 hidden md:inline-flex" data-attachment-upload>
            <svg class="w-5 h-5" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" d="M4 16v2a2 2 0 002 2h12a2 2 0 002-2v-2M12 16V4m0 0L8 8m4-4l4 4"/></svg>
            Upload Attachment
        </button>

        <div id="attachmentsGrid" data-animate-list style="display:grid; grid-template-columns:repeat(auto-fill, minmax(160px, 1fr)); gap:0.75rem;">
            @foreach ($schedule->attachments as $a)
                @php $ext = strtoupper(pathinfo($a->filename, PATHINFO_EXTENSION) ?: 'FILE'); @endphp
                <div class="card overflow-hidden attachment-card" data-id="{{ $a->id }}">
                    <div class="h-[130px] bg-gray-100 flex items-center justify-center overflow-hidden">
                        @if ($a->isImage() && $a->getPublicUrl())
                            <img src="{{ $a->getPublicUrl() }}" alt="{{ $a->filename }}" class="w-full h-full object-cover" loading="lazy">
                        @else
                            <div class="flex flex-col items-center gap-1.5 text-gray-400">
                                <svg class="w-9 h-9" fill="none" stroke="currentColor" stroke-width="1.5" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" d="M9 12h6m-6 4h6m2 5H7a2 2 0 01-2-2V5a2 2 0 012-2h5.586a1 1 0 01.707.293l5.414 5.414a1 1 0 01.293.707V19a2 2 0 01-2 2z"/></svg>
                                <span class="badge badge-gray uppercase">{{ $ext }}</span>
                            </div>
                        @endif
                    </div>
                    <div class="p-3">
                        <p class="text-sm font-semibold text-gray-900 truncate js-filename" title="{{ $a->filename }}">{{ $a->filename }}</p>
                        <p class="text-xs text-gray-400 mt-0.5">{{ number_format($a->fileSize / 1024, 1) }} KB</p>
                        @if (filled($a->description))
                            <p class="text-xs text-gray-500 mt-1 line-clamp-3 js-desc">{{ $a->description }}</p>
                        @else
                            <p class="text-xs text-gray-400 italic mt-1 js-desc">No description.</p>
                        @endif
                        <div class="flex gap-1 mt-2">
                            <button type="button" class="btn btn-sm btn-ghost js-edit" aria-label="Edit description">
                                <svg class="w-4 h-4" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" d="M11 5H6a2 2 0 00-2 2v11a2 2 0 002 2h11a2 2 0 002-2v-5m-1.414-9.414a2 2 0 112.828 2.828L11.828 15H9v-2.828l8.586-8.586z"/></svg>
                            </button>
                            <button type="button" class="btn btn-sm btn-ghost text-red-600 js-delete" aria-label="Delete attachment">
                                <svg class="w-4 h-4" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" d="M19 7l-.867 12.142A2 2 0 0116.138 21H7.862a2 2 0 01-1.995-1.858L5 7m5 4v6m4-6v6m1-10V4a1 1 0 00-1-1h-4a1 1 0 00-1 1v3M4 7h16"/></svg>
                            </button>
                        </div>
                    </div>
                </div>
            @endforeach
        </div>

        <div class="card p-8 text-center {{ $schedule->attachments->isEmpty() ? '' : 'hidden' }} mt-3" id="attachmentsEmpty">
            <svg class="w-12 h-12 mx-auto text-gray-300" fill="none" stroke="currentColor" stroke-width="1.5" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" d="M2.25 15.75l5.159-5.159a2.25 2.25 0 013.182 0l5.159 5.159m-1.5-1.5l1.409-1.409a2.25 2.25 0 013.182 0l2.909 2.909M3.75 21h16.5A1.5 1.5 0 0021.75 19.5V4.5A1.5 1.5 0 0020.25 3H3.75A1.5 1.5 0 002.25 4.5v15A1.5 1.5 0 003.75 21zM8.25 8.25h.008v.008H8.25V8.25z"/></svg>
            <p class="font-semibold text-gray-700 mt-3">No attachments yet</p>
            <p class="text-sm text-gray-500 mt-1">Upload reference photos or PDFs â€” mixing charts, labels, field maps.</p>
            <button type="button" class="btn btn-primary mt-4" data-attachment-upload>Upload Attachment</button>
        </div>
    </div>

    {{-- =========================== 4) CRITICAL RULES =========================== --}}
    <div x-show="tab === 'rules'" x-cloak>
        <button type="button" class="btn btn-primary w-full mb-4 hidden md:inline-flex" data-rule-add>
            <svg class="w-5 h-5" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" d="M12 4v16m8-8H4"/></svg>
            Add Critical Rule
        </button>

        <div id="rulesList" class="space-y-2" data-animate-list>
            @foreach ($schedule->criticalRules as $rule)
                <div class="rule-row border-l-4 border-red-400 bg-red-50 rounded-xl p-3 flex items-start gap-2" draggable="true" data-id="{{ $rule->id }}">
                    <div class="rule-handle hidden md:flex items-center text-red-300 cursor-grab pt-1 shrink-0" title="Drag to reorder">
                        <svg class="w-5 h-5" fill="currentColor" viewBox="0 0 24 24"><circle cx="9" cy="6" r="1.5"/><circle cx="15" cy="6" r="1.5"/><circle cx="9" cy="12" r="1.5"/><circle cx="15" cy="12" r="1.5"/><circle cx="9" cy="18" r="1.5"/><circle cx="15" cy="18" r="1.5"/></svg>
                    </div>
                    <div class="flex flex-col gap-1 md:hidden shrink-0">
                        <button type="button" class="p-1.5 rounded-lg bg-white/80 border border-red-100 text-gray-500 js-up" aria-label="Move up">
                            <svg class="w-4 h-4" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" d="M5 15l7-7 7 7"/></svg>
                        </button>
                        <button type="button" class="p-1.5 rounded-lg bg-white/80 border border-red-100 text-gray-500 js-down" aria-label="Move down">
                            <svg class="w-4 h-4" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" d="M19 9l-7 7-7-7"/></svg>
                        </button>
                    </div>
                    <p class="grow min-w-0 text-sm text-gray-800 whitespace-pre-wrap pt-1 js-text">{{ $rule->ruleText }}</p>
                    <div class="flex gap-1 shrink-0">
                        <button type="button" class="btn btn-sm btn-ghost js-edit" aria-label="Edit rule">
                            <svg class="w-4 h-4" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" d="M11 5H6a2 2 0 00-2 2v11a2 2 0 002 2h11a2 2 0 002-2v-5m-1.414-9.414a2 2 0 112.828 2.828L11.828 15H9v-2.828l8.586-8.586z"/></svg>
                        </button>
                        <button type="button" class="btn btn-sm btn-ghost text-red-600 js-delete" aria-label="Delete rule">
                            <svg class="w-4 h-4" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" d="M19 7l-.867 12.142A2 2 0 0116.138 21H7.862a2 2 0 01-1.995-1.858L5 7m5 4v6m4-6v6m1-10V4a1 1 0 00-1-1h-4a1 1 0 00-1 1v3M4 7h16"/></svg>
                        </button>
                    </div>
                </div>
            @endforeach
        </div>

        <div class="card p-8 text-center {{ $schedule->criticalRules->isEmpty() ? '' : 'hidden' }} mt-3" id="rulesEmpty">
            <svg class="w-12 h-12 mx-auto text-gray-300" fill="none" stroke="currentColor" stroke-width="1.5" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" d="M12 9v3.75m-9.303 3.376c-.866 1.5.217 3.374 1.948 3.374h14.71c1.73 0 2.813-1.874 1.948-3.374L13.949 3.378c-.866-1.5-3.032-1.5-3.898 0L2.697 16.126zM12 15.75h.007v.008H12v-.008z"/></svg>
            <p class="font-semibold text-gray-700 mt-3">No critical rules yet</p>
            <p class="text-sm text-gray-500 mt-1">Season-long reminders that print at the top of every worker document.</p>
            <button type="button" class="btn btn-primary mt-4" data-rule-add>Add Critical Rule</button>
        </div>
    </div>

    {{-- Context-aware mobile FABs --}}
    <button type="button" x-show="tab === 'attachments'" x-cloak data-attachment-upload aria-label="Upload attachment"
        class="fixed bottom-24 right-4 z-30 w-14 h-14 rounded-full btn-primary shadow-lg md:hidden flex items-center justify-center bg-brand-600 text-white hover:bg-brand-700 active:bg-brand-800">
        <svg class="w-6 h-6" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" d="M4 16v2a2 2 0 002 2h12a2 2 0 002-2v-2M12 16V4m0 0L8 8m4-4l4 4"/></svg>
    </button>
    <button type="button" x-show="tab === 'rules'" x-cloak data-rule-add aria-label="Add critical rule"
        class="fixed bottom-24 right-4 z-30 w-14 h-14 rounded-full btn-primary shadow-lg md:hidden flex items-center justify-center bg-brand-600 text-white hover:bg-brand-700 active:bg-brand-800">
        <svg class="w-7 h-7" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" d="M12 4v16m8-8H4"/></svg>
    </button>
</div>
@endsection

@push('sheets')
{{-- Upload attachment --}}
<div class="sheet hidden" id="attachmentUploadSheet" style="--sheet-width:32rem">
    <div class="sheet-handle"></div>
    <div class="sheet-header">
        <h3 class="sheet-title">Upload Attachment</h3>
        <button type="button" data-sheet-close class="btn-ghost p-2 rounded-full" aria-label="Close">âœ•</button>
    </div>
    <div class="sheet-body">
        <div class="mb-4">
            <label class="form-label" for="attachmentFile">File <span class="text-red-500">*</span></label>
            <input type="file" id="attachmentFile" accept="image/jpeg,image/png,image/gif,image/webp,application/pdf"
                class="block w-full text-sm text-gray-600 file:mr-3 file:rounded-lg file:border-0 file:bg-brand-50 file:text-brand-700 file:font-semibold file:px-4 file:py-2.5 file:cursor-pointer cursor-pointer">
            <p class="form-hint">JPG, PNG, GIF, WebP or PDF â€” max 10 MB.</p>
        </div>
        <div class="mb-2">
            <label class="form-label" for="attachmentDescription">Description</label>
            <textarea id="attachmentDescription" class="form-textarea" rows="3" maxlength="5000" placeholder="What is this? e.g. Foliar mixing chart for DAS 20â€“35â€¦"></textarea>
        </div>
        <div class="hidden mt-3" id="attachmentProgressWrap">
            <div class="h-2 bg-gray-200 rounded-full overflow-hidden">
                <div id="attachmentProgressBar" class="h-full bg-brand-600 rounded-full transition-all duration-150" style="width:0%"></div>
            </div>
            <p class="text-xs text-gray-500 mt-1" id="attachmentProgressLabel">Uploadingâ€¦ 0%</p>
        </div>
    </div>
    <div class="sheet-footer">
        <button type="button" class="btn btn-ghost" data-sheet-close>Cancel</button>
        <button type="button" class="btn btn-primary" id="attachmentUploadBtn">Upload</button>
    </div>
</div>

{{-- Edit attachment description --}}
<div class="sheet hidden" id="attachmentEditSheet" style="--sheet-width:28rem">
    <div class="sheet-handle"></div>
    <div class="sheet-header">
        <h3 class="sheet-title">Edit Description</h3>
        <button type="button" data-sheet-close class="btn-ghost p-2 rounded-full" aria-label="Close">âœ•</button>
    </div>
    <div class="sheet-body">
        <input type="hidden" id="attachmentEditId" value="">
        <p class="text-sm font-semibold text-gray-700 truncate mb-2" id="attachmentEditFilename"></p>
        <textarea id="attachmentEditDescription" class="form-textarea" rows="4" maxlength="5000" placeholder="Describe this attachmentâ€¦"></textarea>
        <p class="form-hint">The file itself can't be changed â€” delete and re-upload to replace it.</p>
    </div>
    <div class="sheet-footer">
        <button type="button" class="btn btn-ghost" data-sheet-close>Cancel</button>
        <button type="button" class="btn btn-primary" id="attachmentEditSaveBtn">Save</button>
    </div>
</div>

{{-- Add / edit critical rule --}}
<div class="sheet hidden" id="ruleSheet" style="--sheet-width:32rem">
    <div class="sheet-handle"></div>
    <div class="sheet-header">
        <h3 class="sheet-title" id="ruleSheetTitle">Add Critical Rule</h3>
        <button type="button" data-sheet-close class="btn-ghost p-2 rounded-full" aria-label="Close">âœ•</button>
    </div>
    <div class="sheet-body">
        <input type="hidden" id="ruleEditId" value="">
        <label class="form-label" for="ruleText">Rule <span class="text-red-500">*</span></label>
        <textarea id="ruleText" class="form-textarea" rows="4" maxlength="2000" placeholder="e.g. NEVER spray when winds exceed 15 km/h."></textarea>
        <p class="form-hint">Max 2000 characters. Printed prominently on every worker document.</p>
    </div>
    <div class="sheet-footer">
        <button type="button" class="btn btn-ghost" data-sheet-close>Cancel</button>
        <button type="button" class="btn btn-primary" id="ruleSaveBtn">Save Rule</button>
    </div>
</div>
@endpush

@push('scripts')
<script>
(function () {
    const SCHEDULE_ID = @json($schedule->id);
    const CSRF = document.querySelector('meta[name="csrf-token"]')?.content || '';
    const ACTIVE_VERSION_ID = @json($activeVersion?->id);
    const URLS = {
        protocolSave: @json(route('sm.protocol.save')) + '?scheduleId=' + SCHEDULE_ID,
        globalNote: @json(route('sm.activity-versions.global-note')) + '?scheduleId=' + SCHEDULE_ID + '&id=' + ACTIVE_VERSION_ID,
        attachmentStore: @json(route('sm.attachments.store')) + '?scheduleId=' + SCHEDULE_ID,
        attachmentUpdate: (id) => @json(route('sm.attachments.update')) + '?scheduleId=' + SCHEDULE_ID + '&id=' + id,
        attachmentDestroy: (id) => @json(route('sm.attachments.destroy')) + '?scheduleId=' + SCHEDULE_ID + '&id=' + id,
        ruleStore: @json(route('sm.critical-rules.store')) + '?scheduleId=' + SCHEDULE_ID,
        ruleUpdate: (id) => @json(route('sm.critical-rules.update')) + '?scheduleId=' + SCHEDULE_ID + '&id=' + id,
        ruleDestroy: (id) => @json(route('sm.critical-rules.destroy')) + '?scheduleId=' + SCHEDULE_ID + '&id=' + id,
        ruleReorder: @json(route('sm.critical-rules.reorder')) + '?scheduleId=' + SCHEDULE_ID,
    };

    /* ================================================================= */
    /* 1) Protocol                                                       */
    /* ================================================================= */
    document.getElementById('protocolSaveBtn').addEventListener('click', async () => {
        const btn = document.getElementById('protocolSaveBtn');
        const fileInput = document.getElementById('protocolFile');
        const fd = new FormData();
        fd.append('protocolContent', document.getElementById('protocolContent').value);
        if (fileInput.files.length) {
            const f = fileInput.files[0];
            if (f.size > 10 * 1024 * 1024) { toast('Protocol file is too large â€” max 10 MB.', 'error'); return; }
            fd.append('protocolFile', f);
        }
        btn.disabled = true;
        try {
            const res = await api(URLS.protocolSave, { method: 'POST', body: fd });
            if (res.data && res.data.protocolFile) {
                document.getElementById('protocolCurrentFile').classList.remove('hidden');
                document.getElementById('protocolCurrentFileName').textContent = res.data.protocolFileOriginalName || 'protocol';
            }
            fileInput.value = '';
            toast(res.message);
        } catch (e) {
            toast(e.message, 'error');
        } finally {
            btn.disabled = false;
        }
    });

    /* ================================================================= */
    /* 2) Introduction (Quill 2, lazy-loaded from CDN)                   */
    /* ================================================================= */
    let quill = null;
    let quillLoading = null;
    let INTRO_HTML = @json($activeVersion?->globalActivityNote ?? '');

    function loadQuillAssets() {
        if (window.Quill) return Promise.resolve();
        if (quillLoading) return quillLoading;
        quillLoading = new Promise((resolve, reject) => {
            const css = document.createElement('link');
            css.rel = 'stylesheet';
            css.href = 'https://cdn.jsdelivr.net/npm/quill@2.0.3/dist/quill.snow.css';
            document.head.appendChild(css);
            const s = document.createElement('script');
            s.src = 'https://cdn.jsdelivr.net/npm/quill@2.0.3/dist/quill.min.js';
            s.onload = () => resolve();
            s.onerror = () => { quillLoading = null; reject(new Error('Could not load the editor. Check your connection and try again.')); };
            document.head.appendChild(s);
        });
        return quillLoading;
    }

    /* Preload assets the first time the Introduction tab is shown. */
    document.addEventListener('doc-intro-shown', () => { loadQuillAssets().catch(() => {}); }, { once: true });

    const introIdle = document.getElementById('introIdle');
    const introEditWrap = document.getElementById('introEditWrap');
    const introPreview = document.getElementById('introPreview');
    const introClearBtn = document.getElementById('introClearBtn');

    function refreshIntroPreview() {
        if (INTRO_HTML && INTRO_HTML.trim() !== '') {
            introPreview.innerHTML = INTRO_HTML;
            introClearBtn.classList.remove('hidden');
        } else {
            introPreview.innerHTML = '<p class="text-gray-400 italic">No introduction yet.</p>';
            introClearBtn.classList.add('hidden');
        }
    }

    async function saveIntro(html) {
        const res = await api(URLS.globalNote, { method: 'POST', body: { globalActivityNote: html } });
        INTRO_HTML = (res.data && res.data.globalActivityNote) || '';
        refreshIntroPreview();
        return res;
    }

    if (ACTIVE_VERSION_ID) {
        document.getElementById('introEditBtn').addEventListener('click', async () => {
            const btn = document.getElementById('introEditBtn');
            btn.disabled = true;
            try {
                await loadQuillAssets();
                if (!quill) {
                    quill = new Quill('#introEditor', {
                        theme: 'snow',
                        placeholder: 'Write the introductionâ€¦',
                        modules: {
                            toolbar: [
                                [{ header: [1, 2, 3, false] }],
                                ['bold', 'italic', 'underline'],
                                [{ list: 'ordered' }, { list: 'bullet' }],
                                ['link'],
                                ['clean'],
                            ],
                        },
                    });
                }
                quill.setContents([]);
                if (INTRO_HTML) quill.clipboard.dangerouslyPasteHTML(INTRO_HTML);
                introIdle.classList.add('hidden');
                introEditWrap.classList.remove('hidden');
            } catch (e) {
                toast(e.message, 'error');
            } finally {
                btn.disabled = false;
            }
        });

        document.getElementById('introCancelBtn').addEventListener('click', () => {
            introEditWrap.classList.add('hidden');
            introIdle.classList.remove('hidden');
        });

        document.getElementById('introSaveBtn').addEventListener('click', async () => {
            const btn = document.getElementById('introSaveBtn');
            const html = quill && quill.getText().trim() !== '' ? quill.root.innerHTML : '';
            btn.disabled = true;
            try {
                const res = await saveIntro(html);
                introEditWrap.classList.add('hidden');
                introIdle.classList.remove('hidden');
                toast(res.message);
            } catch (e) {
                toast(e.message, 'error');
            } finally {
                btn.disabled = false;
            }
        });

        introClearBtn.addEventListener('click', async () => {
            const ok = await confirmAction({
                title: 'Clear introduction?',
                message: 'The rich-text introduction for the active version will be removed.',
                confirmText: 'Clear',
            });
            if (!ok) return;
            try {
                const res = await saveIntro('');
                toast(res.message);
            } catch (e) {
                toast(e.message, 'error');
            }
        });
    }

    /* ================================================================= */
    /* 3) Attachments                                                    */
    /* ================================================================= */
    @php
        $attachmentsSeed = $schedule->attachments->mapWithKeys(fn ($aa) => [$aa->id => [
            'id' => $aa->id,
            'filename' => $aa->filename,
            'fileSize' => (int) $aa->fileSize,
            'description' => $aa->description,
            'url' => $aa->getPublicUrl(),
            'isImage' => $aa->isImage(),
        ]]);
    @endphp
    const ATTACHMENTS = @json($attachmentsSeed->isEmpty() ? new stdClass() : $attachmentsSeed);

    const grid = document.getElementById('attachmentsGrid');
    const attachmentsEmpty = document.getElementById('attachmentsEmpty');
    const attachmentsCountEl = document.getElementById('docAttachmentsCount');

    function refreshAttachmentsState() {
        const n = grid.querySelectorAll('.attachment-card').length;
        attachmentsEmpty.classList.toggle('hidden', n > 0);
        attachmentsCountEl.textContent = n;
    }

    function renderAttachmentCard(a) {
        const ext = ((a.filename || '').split('.').pop() || 'FILE').toUpperCase();
        const el = document.createElement('div');
        el.className = 'card overflow-hidden attachment-card';
        el.dataset.id = a.id;
        const thumb = a.isImage && a.url
            ? `<img src="${escapeHtml(a.url)}" alt="${escapeHtml(a.filename)}" class="w-full h-full object-cover" loading="lazy">`
            : `<div class="flex flex-col items-center gap-1.5 text-gray-400">
                    <svg class="w-9 h-9" fill="none" stroke="currentColor" stroke-width="1.5" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" d="M9 12h6m-6 4h6m2 5H7a2 2 0 01-2-2V5a2 2 0 012-2h5.586a1 1 0 01.707.293l5.414 5.414a1 1 0 01.293.707V19a2 2 0 01-2 2z"/></svg>
                    <span class="badge badge-gray uppercase">${escapeHtml(ext)}</span>
               </div>`;
        const desc = a.description
            ? `<p class="text-xs text-gray-500 mt-1 line-clamp-3 js-desc">${escapeHtml(a.description)}</p>`
            : `<p class="text-xs text-gray-400 italic mt-1 js-desc">No description.</p>`;
        el.innerHTML = `
            <div class="h-[130px] bg-gray-100 flex items-center justify-center overflow-hidden">${thumb}</div>
            <div class="p-3">
                <p class="text-sm font-semibold text-gray-900 truncate js-filename" title="${escapeHtml(a.filename)}">${escapeHtml(a.filename)}</p>
                <p class="text-xs text-gray-400 mt-0.5">${fmtNumber(a.fileSize / 1024, 1)} KB</p>
                ${desc}
                <div class="flex gap-1 mt-2">
                    <button type="button" class="btn btn-sm btn-ghost js-edit" aria-label="Edit description">
                        <svg class="w-4 h-4" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" d="M11 5H6a2 2 0 00-2 2v11a2 2 0 002 2h11a2 2 0 002-2v-5m-1.414-9.414a2 2 0 112.828 2.828L11.828 15H9v-2.828l8.586-8.586z"/></svg>
                    </button>
                    <button type="button" class="btn btn-sm btn-ghost text-red-600 js-delete" aria-label="Delete attachment">
                        <svg class="w-4 h-4" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" d="M19 7l-.867 12.142A2 2 0 0116.138 21H7.862a2 2 0 01-1.995-1.858L5 7m5 4v6m4-6v6m1-10V4a1 1 0 00-1-1h-4a1 1 0 00-1 1v3M4 7h16"/></svg>
                    </button>
                </div>
            </div>`;
        return el;
    }

    /* --- upload sheet --- */
    const progressWrap = document.getElementById('attachmentProgressWrap');
    const progressBar = document.getElementById('attachmentProgressBar');
    const progressLabel = document.getElementById('attachmentProgressLabel');
    let uploading = false;

    document.querySelectorAll('[data-attachment-upload]').forEach((btn) => {
        btn.addEventListener('click', () => {
            document.getElementById('attachmentFile').value = '';
            document.getElementById('attachmentDescription').value = '';
            progressWrap.classList.add('hidden');
            progressBar.style.width = '0%';
            openSheet('attachmentUploadSheet');
        });
    });

    document.getElementById('attachmentUploadBtn').addEventListener('click', () => {
        if (uploading) return;
        const fileInput = document.getElementById('attachmentFile');
        if (!fileInput.files.length) { toast('Pick an image (or PDF) to upload.', 'error'); return; }
        const file = fileInput.files[0];
        if (file.size > 10 * 1024 * 1024) { toast('File is too large â€” max 10 MB.', 'error'); return; }

        const fd = new FormData();
        fd.append('file', file);
        fd.append('description', document.getElementById('attachmentDescription').value);

        const btn = document.getElementById('attachmentUploadBtn');
        uploading = true;
        btn.disabled = true;
        progressWrap.classList.remove('hidden');
        progressBar.style.width = '0%';
        progressLabel.textContent = 'Uploadingâ€¦ 0%';

        const xhr = new XMLHttpRequest();
        xhr.open('POST', URLS.attachmentStore);
        xhr.setRequestHeader('X-CSRF-TOKEN', CSRF);
        xhr.setRequestHeader('X-Requested-With', 'XMLHttpRequest');
        xhr.setRequestHeader('Accept', 'application/json');

        xhr.upload.onprogress = (e) => {
            if (!e.lengthComputable) return;
            const pct = Math.round((e.loaded / e.total) * 100);
            progressBar.style.width = pct + '%';
            progressLabel.textContent = pct >= 100 ? 'Processingâ€¦' : 'Uploadingâ€¦ ' + pct + '%';
        };

        const fail = (msg) => {
            uploading = false;
            btn.disabled = false;
            progressWrap.classList.add('hidden');
            toast(msg, 'error');
        };

        xhr.onload = () => {
            let json = null;
            try { json = JSON.parse(xhr.responseText); } catch (e) { /* non-JSON */ }
            if (xhr.status >= 200 && xhr.status < 300 && json && json.success) {
                uploading = false;
                btn.disabled = false;
                ATTACHMENTS[json.data.id] = json.data;
                grid.prepend(renderAttachmentCard(json.data));
                refreshAttachmentsState();
                closeSheet('attachmentUploadSheet');
                toast(json.message);
            } else {
                const msg = (json && (json.message === 'Validation failed.' && json.errors
                    ? Object.values(json.errors).flat()[0]
                    : json.message)) || 'Upload failed (' + xhr.status + ').';
                fail(msg);
            }
        };
        xhr.onerror = () => fail('Upload failed â€” network error.');
        xhr.send(fd);
    });

    /* --- edit description / delete --- */
    grid.addEventListener('click', async (e) => {
        const card = e.target.closest('.attachment-card');
        if (!card) return;
        const id = card.dataset.id;
        const a = ATTACHMENTS[id];

        if (e.target.closest('.js-edit')) {
            document.getElementById('attachmentEditId').value = id;
            document.getElementById('attachmentEditFilename').textContent = a ? a.filename : '';
            document.getElementById('attachmentEditDescription').value = a ? (a.description || '') : '';
            openSheet('attachmentEditSheet');
            return;
        }
        if (e.target.closest('.js-delete')) {
            const ok = await confirmAction({
                title: 'Delete attachment?',
                message: '"' + (a ? a.filename : 'This file') + '" will be removed from the schedule.',
                detail: 'The file itself stays on disk until a future cleanup.',
                confirmText: 'Delete',
            });
            if (!ok) return;
            try {
                const res = await api(URLS.attachmentDestroy(id), { method: 'DELETE' });
                delete ATTACHMENTS[id];
                card.remove();
                refreshAttachmentsState();
                toast(res.message);
            } catch (err) {
                toast(err.message, 'error');
            }
        }
    });

    document.getElementById('attachmentEditSaveBtn').addEventListener('click', async () => {
        const id = document.getElementById('attachmentEditId').value;
        if (!id) return;
        const btn = document.getElementById('attachmentEditSaveBtn');
        btn.disabled = true;
        try {
            const res = await api(URLS.attachmentUpdate(id), {
                method: 'PUT',
                body: { description: document.getElementById('attachmentEditDescription').value.trim() || null },
            });
            ATTACHMENTS[id] = res.data;
            const card = grid.querySelector('.attachment-card[data-id="' + id + '"]');
            if (card) card.replaceWith(renderAttachmentCard(res.data));
            closeSheet('attachmentEditSheet');
            toast(res.message);
        } catch (e) {
            toast(e.message, 'error');
        } finally {
            btn.disabled = false;
        }
    });

    /* ================================================================= */
    /* 4) Critical rules                                                 */
    /* ================================================================= */
    const rulesList = document.getElementById('rulesList');
    const rulesEmpty = document.getElementById('rulesEmpty');
    const rulesCountEl = document.getElementById('docRulesCount');

    function refreshRulesState() {
        const n = rulesList.querySelectorAll('.rule-row').length;
        rulesEmpty.classList.toggle('hidden', n > 0);
        rulesCountEl.textContent = n;
    }

    function renderRuleRow(rule) {
        const el = document.createElement('div');
        el.className = 'rule-row border-l-4 border-red-400 bg-red-50 rounded-xl p-3 flex items-start gap-2';
        el.draggable = true;
        el.dataset.id = rule.id;
        el.innerHTML = `
            <div class="rule-handle hidden md:flex items-center text-red-300 cursor-grab pt-1 shrink-0" title="Drag to reorder">
                <svg class="w-5 h-5" fill="currentColor" viewBox="0 0 24 24"><circle cx="9" cy="6" r="1.5"/><circle cx="15" cy="6" r="1.5"/><circle cx="9" cy="12" r="1.5"/><circle cx="15" cy="12" r="1.5"/><circle cx="9" cy="18" r="1.5"/><circle cx="15" cy="18" r="1.5"/></svg>
            </div>
            <div class="flex flex-col gap-1 md:hidden shrink-0">
                <button type="button" class="p-1.5 rounded-lg bg-white/80 border border-red-100 text-gray-500 js-up" aria-label="Move up">
                    <svg class="w-4 h-4" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" d="M5 15l7-7 7 7"/></svg>
                </button>
                <button type="button" class="p-1.5 rounded-lg bg-white/80 border border-red-100 text-gray-500 js-down" aria-label="Move down">
                    <svg class="w-4 h-4" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" d="M19 9l-7 7-7-7"/></svg>
                </button>
            </div>
            <p class="grow min-w-0 text-sm text-gray-800 whitespace-pre-wrap pt-1 js-text">${escapeHtml(rule.ruleText)}</p>
            <div class="flex gap-1 shrink-0">
                <button type="button" class="btn btn-sm btn-ghost js-edit" aria-label="Edit rule">
                    <svg class="w-4 h-4" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" d="M11 5H6a2 2 0 00-2 2v11a2 2 0 002 2h11a2 2 0 002-2v-5m-1.414-9.414a2 2 0 112.828 2.828L11.828 15H9v-2.828l8.586-8.586z"/></svg>
                </button>
                <button type="button" class="btn btn-sm btn-ghost text-red-600 js-delete" aria-label="Delete rule">
                    <svg class="w-4 h-4" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" d="M19 7l-.867 12.142A2 2 0 0116.138 21H7.862a2 2 0 01-1.995-1.858L5 7m5 4v6m4-6v6m1-10V4a1 1 0 00-1-1h-4a1 1 0 00-1 1v3M4 7h16"/></svg>
                </button>
            </div>`;
        return el;
    }

    /* --- persist order silently (toast only on error) --- */
    async function persistRuleOrder() {
        const items = [...rulesList.querySelectorAll('.rule-row')].map((r, i) => ({
            id: parseInt(r.dataset.id, 10),
            sortOrder: i + 1,
        }));
        if (!items.length) return;
        try {
            await api(URLS.ruleReorder, { method: 'POST', body: { items } });
        } catch (e) {
            toast('Failed to save order: ' + e.message, 'error');
        }
    }

    /* --- HTML5 drag & drop (desktop) --- */
    let dragEl = null;
    let dragMoved = false;
    rulesList.addEventListener('dragstart', (e) => {
        const row = e.target.closest('.rule-row');
        if (!row) return;
        dragEl = row;
        dragMoved = false;
        row.classList.add('opacity-50');
        e.dataTransfer.effectAllowed = 'move';
        try { e.dataTransfer.setData('text/plain', row.dataset.id); } catch (err) { /* IE quirk */ }
    });
    rulesList.addEventListener('dragover', (e) => {
        if (!dragEl) return;
        e.preventDefault();
        e.dataTransfer.dropEffect = 'move';
        const row = e.target.closest('.rule-row');
        if (!row || row === dragEl) return;
        const rect = row.getBoundingClientRect();
        const before = e.clientY < rect.top + rect.height / 2;
        rulesList.insertBefore(dragEl, before ? row : row.nextSibling);
        dragMoved = true;
    });
    rulesList.addEventListener('drop', (e) => { if (dragEl) e.preventDefault(); });
    rulesList.addEventListener('dragend', () => {
        if (!dragEl) return;
        dragEl.classList.remove('opacity-50');
        dragEl = null;
        if (dragMoved) persistRuleOrder();
    });

    /* --- sheet open helpers --- */
    function openRuleSheet(id = null, text = '') {
        document.getElementById('ruleEditId').value = id || '';
        document.getElementById('ruleSheetTitle').textContent = id ? 'Edit Critical Rule' : 'Add Critical Rule';
        document.getElementById('ruleText').value = text;
        openSheet('ruleSheet');
    }
    document.querySelectorAll('[data-rule-add]').forEach((btn) => {
        btn.addEventListener('click', () => openRuleSheet(null));
    });

    /* --- save (store / update) --- */
    document.getElementById('ruleSaveBtn').addEventListener('click', async () => {
        const id = document.getElementById('ruleEditId').value;
        const text = document.getElementById('ruleText').value.trim();
        if (!text) { toast('Enter the rule text.', 'error'); return; }

        const btn = document.getElementById('ruleSaveBtn');
        btn.disabled = true;
        try {
            const res = await api(id ? URLS.ruleUpdate(id) : URLS.ruleStore, {
                method: id ? 'PUT' : 'POST',
                body: { ruleText: text },
            });
            if (id) {
                const row = rulesList.querySelector('.rule-row[data-id="' + id + '"]');
                if (row) row.querySelector('.js-text').textContent = res.data.ruleText;
            } else {
                rulesList.appendChild(renderRuleRow(res.data));
            }
            refreshRulesState();
            closeSheet('ruleSheet');
            toast(res.message);
        } catch (e) {
            toast(e.message, 'error');
        } finally {
            btn.disabled = false;
        }
    });

    /* --- row actions: arrows / edit / delete --- */
    rulesList.addEventListener('click', async (e) => {
        const row = e.target.closest('.rule-row');
        if (!row) return;
        const id = row.dataset.id;

        if (e.target.closest('.js-up')) {
            const prev = row.previousElementSibling;
            if (prev) { rulesList.insertBefore(row, prev); persistRuleOrder(); }
            return;
        }
        if (e.target.closest('.js-down')) {
            const next = row.nextElementSibling;
            if (next) { rulesList.insertBefore(next, row); persistRuleOrder(); }
            return;
        }
        if (e.target.closest('.js-edit')) {
            openRuleSheet(id, row.querySelector('.js-text').textContent);
            return;
        }
        if (e.target.closest('.js-delete')) {
            const ok = await confirmAction({
                title: 'Delete critical rule?',
                message: 'This reminder will be removed from every worker document.',
                confirmText: 'Delete',
            });
            if (!ok) return;
            try {
                const res = await api(URLS.ruleDestroy(id), { method: 'DELETE' });
                row.remove();
                refreshRulesState();
                persistRuleOrder();
                toast(res.message);
            } catch (err) {
                toast(err.message, 'error');
            }
        }
    });
})();
</script>
@endpush
