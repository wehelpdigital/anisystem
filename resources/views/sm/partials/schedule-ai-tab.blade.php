{{-- Collab Room "AI Technician" tab: shared AI conversations for the team.
     A sessions sidebar (visible to everyone) lets the team keep several named
     threads; any member asks, the question broadcasts instantly and the answer
     when it returns. Funded by the schedule owner's credits. Sessions can be
     saved to the schedule notebook. Expects: $schedule. --}}
@php
    // Compiled partials do not inherit the page's use-imports — fully qualified.
    $saiSettings = \App\Models\AiSetting::current();
    // Precomputed: @json splits on commas (value, flags, depth) and an inline
    // array literal compiles to truncated, unparseable PHP.
    $saiPriceCard = [
        'inK' => (float) $saiSettings->creditsPerInputK,
        'outK' => (float) $saiSettings->creditsPerOutputK,
        'img' => (float) $saiSettings->creditsPerImage,
        'halfOut' => (int) $saiSettings->maxOutputTokens / 2,
    ];
    $saiPerPhoto = (float) $saiSettings->creditsPerImage;
    $saiPerPhotoTxt = rtrim(rtrim(number_format($saiPerPhoto, 2), '0'), '.');
    $saiHintIdle = '≈ 4 credits per answer' . ($saiPerPhoto > 0 ? ' · +' . $saiPerPhotoTxt . ' per photo' : '');
    // The "attach to a task" picker, rendered with the page.
    $saiTasks = \App\Models\AsScheduleActivity::query()
        ->where('croppingScheduleId', $schedule->id)
        ->orderByDesc('targetDate')
        ->limit(30)
        ->get(['id', 'activityTitle', 'targetDate']);
@endphp
<div class="sai-wrap" id="saiWrap" data-schedule="{{ $schedule->id }}">
    <div class="sai-layout">
        {{-- Sessions sidebar (team-visible) --}}
        <aside class="sai-sessions" id="saiSessions">
            <div class="sai-sessions-head">
                <span>Sessions</span>
                <button type="button" id="saiNewSession" class="sai-new" title="Start a new session">
                    <svg class="w-4 h-4" fill="none" stroke="currentColor" stroke-width="2.2" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" d="M12 5v14M5 12h14"/></svg>
                </button>
            </div>
            <div class="sai-sessions-list" id="saiSessionsList">
                <div class="sai-sess-skel" aria-hidden="true"></div>
                <div class="sai-sess-skel" aria-hidden="true"></div>
                <div class="sai-sess-skel" aria-hidden="true"></div>
            </div>
        </aside>

        <div class="sai-main">
            <div class="sai-head">
                <button type="button" id="saiSessToggle" class="sai-sess-toggle" title="Show sessions" aria-label="Show sessions">
                    <svg class="w-5 h-5" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" d="M4 6h16M4 12h16M4 18h16"/></svg>
                </button>
                <span class="sai-title">🤖 AI Technician <span class="sai-sub">· shared with your team</span></span>
                <span class="sai-spacer"></span>
                <button type="button" id="saiSaveSession" class="sai-save" title="Keep this session — as a note, or on a task" aria-haspopup="dialog">
                    <svg class="w-4 h-4" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" d="M5 5a1 1 0 011-1h9l4 4v10a1 1 0 01-1 1H6a1 1 0 01-1-1V5z"/><path stroke-linecap="round" stroke-linejoin="round" d="M8 4v4h6M8 19v-5h8v5"/></svg>
                    <span class="hidden sm:inline">Save</span>
                </button>
                <a href="{{ route('ai.credits') }}" class="sai-credits" title="Owner's AI credits fund the team">
                    <svg class="w-3 h-3" viewBox="0 0 20 20" fill="currentColor"><path d="M10 2a8 8 0 100 16 8 8 0 000-16zm.75 4.5v.63a2.5 2.5 0 01.2 4.84v.78a.75.75 0 01-1.5 0v-.75a2.6 2.6 0 01-1.83-1.1.75.75 0 011.24-.84c.24.35.63.57 1.09.57.6 0 1.05-.36 1.05-.83 0-.44-.3-.7-1.2-.95-1.13-.32-2.05-.8-2.05-2.05a2.2 2.2 0 011.5-2.03V6.5a.75.75 0 011.5 0z"/></svg>
                    <span id="saiBalance">…</span>&nbsp;<span class="hidden sm:inline">credits</span>
                </a>
            </div>
            <div class="sai-thread" id="saiThread">
                {{-- Ghost bubbles while the first load is in flight — the
                     shimmer says "coming", not a bare word on white. --}}
                <div class="sai-skel" id="saiLoading" aria-hidden="true">
                    <div class="sai-skel-row"><span class="sai-skel-face"></span><span class="sai-skel-b"></span></div>
                    <div class="sai-skel-row me"><span class="sai-skel-face"></span><span class="sai-skel-b"></span></div>
                    <div class="sai-skel-row tall"><span class="sai-skel-face"></span><span class="sai-skel-b"></span></div>
                </div>
            </div>
            <div class="sai-composer">
                {{-- A question can be about several photos: four pictures of
                     one leaf are one question, not four. Each carries its own
                     remove, because the usual correction is "not that one". --}}
                <div id="saiPhotoChips" class="sai-photochips" aria-label="Attached photos" aria-live="polite"></div>
                {{-- Says so while a photo is on the wire — a busy chip alone
                     reads as a broken thumbnail rather than work in progress. --}}
                <div id="saiAttachBusy" class="sai-busyline hidden" role="status"><span class="sp" aria-hidden="true"></span><span class="tx">Attaching photo…</span></div>
                <div class="sai-box">
                    <button type="button" class="sai-cam" id="saiAttachBtn" title="Add photos" aria-label="Add photos" aria-haspopup="dialog">
                        <svg class="w-5 h-5" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" d="M3 9a2 2 0 012-2h.93a2 2 0 001.664-.89l.812-1.22A2 2 0 0110.07 4h3.86a2 2 0 011.664.89l.812 1.22A2 2 0 0018.07 7H19a2 2 0 012 2v9a2 2 0 01-2 2H5a2 2 0 01-2-2V9z"/><path stroke-linecap="round" stroke-linejoin="round" d="M15 13a3 3 0 11-6 0 3 3 0 016 0z"/></svg>
                    </button>
                    <input type="file" id="saiPhotoFiles" accept="image/*" multiple class="hidden">
                    <input type="file" id="saiPhotoCam" accept="image/*" capture="environment" class="hidden">
                    <textarea id="saiText" rows="1" maxlength="4000" placeholder="Ask the AI — the whole team sees the reply…"></textarea>
                    <button type="button" id="saiSend" class="sai-send" aria-label="Send">
                        <svg class="w-4 h-4" fill="none" stroke="currentColor" stroke-width="2.2" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" d="M5 12h14m0 0l-6-6m6 6l-6 6"/></svg>
                    </button>
                </div>
                {{-- The bill quoted before it is run up: the owner's pool pays
                     for the whole room, so everyone sees the price — admins
                     included, who ride free but still spend the owner's. --}}
                <p class="sai-estimate" id="saiEstimate" data-idle="{{ $saiHintIdle }}">{{ $saiHintIdle }}</p>
            </div>
        </div>
    </div>
</div>

{{-- The attach chooser: every way a photo can arrive, behind one button —
     the same three doors the page composer offers, spoken in sheet language. --}}
<div class="sheet hidden" id="saiAttachSheet" style="--sheet-width:22rem">
    <div class="sheet-handle"></div>
    <div class="sheet-header">
        <h3 class="sheet-title">Add photos</h3>
        <button type="button" data-sheet-close class="btn-ghost p-2 rounded-full" aria-label="Close">✕</button>
    </div>
    <div class="sheet-body space-y-1">
        <button type="button" class="sai-opt" id="saiAttachUpload">
            <span class="ic"><svg class="w-5 h-5" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" d="M4 16l4.586-4.586a2 2 0 012.828 0L16 16m-2-2l1.586-1.586a2 2 0 012.828 0L20 14m-6-6h.01M6 20h12a2 2 0 002-2V6a2 2 0 00-2-2H6a2 2 0 00-2 2v12a2 2 0 002 2z"/></svg></span>
            <span>Upload photos<span class="sub">Pick one or several from your phone</span></span>
        </button>
        <button type="button" class="sai-opt" id="saiAttachCamera">
            <span class="ic"><svg class="w-5 h-5" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" d="M3 9a2 2 0 012-2h.93a2 2 0 001.664-.89l.812-1.22A2 2 0 0110.07 4h3.86a2 2 0 011.664.89l.812 1.22A2 2 0 0018.07 7H19a2 2 0 012 2v9a2 2 0 01-2 2H5a2 2 0 01-2-2V9z"/><path stroke-linecap="round" stroke-linejoin="round" d="M15 13a3 3 0 11-6 0 3 3 0 016 0z"/></svg></span>
            <span>Take a photo<span class="sub">Point the camera at the problem</span></span>
        </button>
        <button type="button" class="sai-opt hidden" id="saiAttachGallery">
            <span class="ic"><svg class="w-5 h-5" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" d="M4 7h3l2-3h6l2 3h3v13H4V7z"/><path stroke-linecap="round" stroke-linejoin="round" d="M8 13l2.5-2.5L14 14l2-2 2 2"/></svg></span>
            <span>From the gallery<span class="sub">A photo this season already keeps</span></span>
        </button>
    </div>
</div>
{{-- The gallery door needs the picker on the page; @@once, so a room that
     already carries it (the Photo tab does) still gets exactly one. --}}
@include('sm.partials.media-picker')

{{-- Save: which door first, then the naming sheet — nothing lands in the
     notebook unnamed. --}}
<div class="sheet hidden" id="saiSaveSheet" style="--sheet-width:22rem">
    <div class="sheet-handle"></div>
    <div class="sheet-header">
        <h3 class="sheet-title">Keep this session</h3>
        <button type="button" data-sheet-close class="btn-ghost p-2 rounded-full" aria-label="Close">✕</button>
    </div>
    <div class="sheet-body space-y-1">
        <button type="button" class="sai-opt" id="saiSaveToNote">
            <span class="ic"><svg class="w-5 h-5" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" d="M11 5H6a2 2 0 00-2 2v11a2 2 0 002 2h11a2 2 0 002-2v-5m-1.4-9.4a2 2 0 112.8 2.8L11 15l-4 1 1-4 8.6-8.4z"/></svg></span>
            <span>Save as a new note<span class="sub">The whole session, into the notebook</span></span>
        </button>
        <button type="button" class="sai-opt" id="saiSaveToTask">
            <span class="ic"><svg class="w-5 h-5" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" d="M9 5H7a2 2 0 00-2 2v12a2 2 0 002 2h10a2 2 0 002-2V7a2 2 0 00-2-2h-2M9 5a2 2 0 002 2h2a2 2 0 002-2M9 5a2 2 0 012-2h2a2 2 0 012 2m-6 9l2 2 4-4"/></svg></span>
            <span>Attach to a task<span class="sub">File this session onto a task, in the notebook</span></span>
        </button>
    </div>
</div>

<div class="sheet hidden" id="saiTaskSheet" style="--sheet-width:22rem">
    <div class="sheet-handle"></div>
    <div class="sheet-header">
        <h3 class="sheet-title">Which task?</h3>
        <button type="button" data-sheet-close class="btn-ghost p-2 rounded-full" aria-label="Close">✕</button>
    </div>
    <div class="sheet-body space-y-1">
        @forelse ($saiTasks as $saiTask)
            <button type="button" class="sai-opt" data-sai-task="{{ $saiTask->id }}">
                <span class="ic"><svg class="w-5 h-5" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" d="M9 12l2 2 4-4m6 2a9 9 0 11-18 0 9 9 0 0118 0z"/></svg></span>
                <span class="min-w-0">{{ \Illuminate\Support\Str::limit($saiTask->activityTitle ?: 'Task', 40) }}<span class="sub">{{ $saiTask->targetDate ? \Illuminate\Support\Carbon::parse($saiTask->targetDate)->format('M j, Y') : 'no set date' }}</span></span>
            </button>
        @empty
            <p class="text-sm text-gray-500 text-center py-6">No tasks on this schedule yet.</p>
        @endforelse
    </div>
</div>

<div class="sheet hidden" id="saiNoteSheet" style="--sheet-width:22rem">
    <div class="sheet-handle"></div>
    <div class="sheet-header">
        <h3 class="sheet-title" id="saiNoteHeading">Save this session as a note</h3>
        <button type="button" data-sheet-close class="btn-ghost p-2 rounded-full" aria-label="Close">✕</button>
    </div>
    <div class="sheet-body space-y-3">
        <div>
            <label class="form-label" for="saiNoteTitle">Title</label>
            <input type="text" id="saiNoteTitle" class="form-input" maxlength="180" placeholder="Name this note">
        </div>
        <div>
            <label class="form-label" for="saiNoteDesc">Description <span class="text-gray-400 font-normal">(optional)</span></label>
            <textarea id="saiNoteDesc" class="form-textarea" rows="3" maxlength="2000" placeholder="Why this session is worth keeping…"></textarea>
        </div>
        <p class="text-xs text-gray-400">The whole session is attached underneath.</p>
        <button type="button" id="saiNoteSave" class="btn btn-primary w-full">Save to the notebook</button>
    </div>
</div>

<style>
    .sai-wrap { display: flex; flex-direction: column; height: 100%; width: 100%; min-height: 0; }
    .sai-layout { flex: 1 1 auto; display: flex; min-height: 0; position: relative; }
    /* Sessions sidebar */
    .sai-sessions { width: 13.5rem; flex-shrink: 0; display: flex; flex-direction: column; min-height: 0; background: var(--color-gray-50); border-right: 1px solid var(--color-gray-100); transition: transform .28s cubic-bezier(.22,1,.36,1); }
    .sai-sessions-head { display: flex; align-items: center; justify-content: space-between; padding: .6rem .7rem .4rem; font-size: .7rem; font-weight: 800; letter-spacing: .03em; text-transform: uppercase; color: var(--color-gray-400); }
    .sai-new { width: 1.6rem; height: 1.6rem; border-radius: .45rem; display: inline-flex; align-items: center; justify-content: center; color: var(--color-brand-700); background: var(--color-brand-50); }
    .sai-new:hover { background: var(--color-brand-100); }
    .sai-sessions-list { flex: 1 1 auto; overflow-y: auto; padding: 0 .4rem .5rem; scrollbar-width: thin; display: flex; flex-direction: column; gap: .2rem; }
    .sai-sessions-empty { color: var(--color-gray-400); font-size: .78rem; padding: .5rem .4rem; }
    .sai-sess { text-align: left; padding: .4rem .5rem; border-radius: .55rem; display: flex; flex-direction: column; gap: .05rem; color: var(--color-gray-700); transition: background .15s ease; }
    .sai-sess:hover { background: var(--color-gray-100); }
    .sai-sess.is-active { background: var(--color-white); box-shadow: 0 1px 3px rgb(0 0 0 / .1); }
    .sai-sess-title { font-size: .82rem; font-weight: 700; color: var(--color-gray-900); overflow: hidden; text-overflow: ellipsis; white-space: nowrap; }
    .sai-sess.is-active .sai-sess-title { color: var(--color-brand-700); }
    .sai-sess-meta { font-size: .62rem; color: var(--color-gray-400); overflow: hidden; text-overflow: ellipsis; white-space: nowrap; }
    /* Main column */
    .sai-main { flex: 1 1 auto; display: flex; flex-direction: column; min-width: 0; min-height: 0; }
    /* The tab's header wears the app's drifting header green (messenger
       language, gradSweep tide from the layout). Literal hex — identical in
       both modes so the white text always holds. */
    .sai-head { display: flex; align-items: center; gap: .5rem; padding: .55rem .8rem; color: #fff;
        background: linear-gradient(120deg, #3d6823, #6b9f3d 35%, #4a7c2a 60%, #2f5219 85%, #3d6823);
        background-size: 240% 240%; animation: gradSweep 12s ease-in-out infinite alternate; }
    .sai-spacer { flex: 1 1 auto; }
    .sai-sess-toggle { display: none; width: 2rem; height: 2rem; border-radius: .5rem; align-items: center; justify-content: center; color: #fff; background: rgb(255 255 255 / .18); flex-shrink: 0; }
    .sai-sess-toggle:hover { background: rgb(255 255 255 / .28); }
    .sai-save { display: inline-flex; align-items: center; gap: .3rem; padding: .3rem .55rem; border-radius: .6rem; font-size: .74rem; font-weight: 700; color: #fff; background: rgb(255 255 255 / .18); flex-shrink: 0; transition: background .15s ease; }
    .sai-save:hover { background: rgb(255 255 255 / .28); color: #fff; }
    .sai-save:disabled { opacity: .5; }
    .sai-title { font-weight: 800; font-size: .9rem; color: #fff; white-space: nowrap; overflow: hidden; text-overflow: ellipsis; }
    .sai-sub { font-weight: 600; font-size: .72rem; color: rgb(255 255 255 / .75); }
    .sai-credits { display: inline-flex; align-items: center; gap: .25rem; padding: .12rem .5rem; border-radius: 999px; background: rgb(255 255 255 / .18); color: #fff; font-size: .72rem; font-weight: 800; font-variant-numeric: tabular-nums; flex-shrink: 0; }
    .sai-credits:hover { background: rgb(255 255 255 / .28); }
    .sai-credits svg { color: var(--color-accent-400); }
    .sai-thread { flex: 1 1 auto; overflow-y: auto; padding: .8rem; display: flex; flex-direction: column; gap: .1rem; scrollbar-width: thin; }
    .sai-loading { margin: auto; color: var(--color-gray-400); font-size: .85rem; }
    /* Loading skeleton: ghost bubbles shimmer (plaza media-skel language)
       instead of a bare word on white. */
    .sai-skel { margin: auto 0 0; display: flex; flex-direction: column; gap: .7rem; padding: .25rem 0; width: 100%; }
    .sai-skel-row { display: flex; gap: .45rem; align-items: flex-end; }
    .sai-skel-row.me { flex-direction: row-reverse; }
    .sai-skel-face, .sai-skel-b { position: relative; overflow: hidden; background: var(--color-gray-100); }
    .sai-skel-face { width: 1.8rem; height: 1.8rem; border-radius: 999px; flex-shrink: 0; }
    .sai-skel-b { height: 2.6rem; border-radius: .9rem .9rem .9rem .25rem; width: min(60%, 16rem); }
    .sai-skel-row.me .sai-skel-b { border-radius: .9rem .9rem .25rem .9rem; width: min(45%, 12rem); }
    .sai-skel-row.tall .sai-skel-b { height: 4.2rem; }
    .sai-skel-face::before, .sai-skel-b::before { content: ''; position: absolute; inset: 0;
        background: linear-gradient(100deg, rgba(255,255,255,0) 20%, rgba(255,255,255,.55) 50%, rgba(255,255,255,0) 80%);
        background-size: 220% 100%; animation: saiSkelSweep 1.15s linear infinite; }
    html.dark .sai-skel-face, html.dark .sai-skel-b { background: rgb(255 255 255 / .06); }
    html.dark .sai-skel-face::before, html.dark .sai-skel-b::before {
        background: linear-gradient(100deg, rgba(255,255,255,0) 20%, rgba(255,255,255,.09) 50%, rgba(255,255,255,0) 80%);
        background-size: 220% 100%; }
    @keyframes saiSkelSweep { from { background-position: 220% 0; } to { background-position: -220% 0; } }
    .sai-sess-skel { height: 2.4rem; border-radius: .55rem; position: relative; overflow: hidden; background: var(--color-gray-100); flex-shrink: 0; }
    html.dark .sai-sess-skel { background: rgb(255 255 255 / .06); }
    .sai-sess-skel::before { content: ''; position: absolute; inset: 0;
        background: linear-gradient(100deg, rgba(255,255,255,0) 20%, rgba(255,255,255,.55) 50%, rgba(255,255,255,0) 80%);
        background-size: 220% 100%; animation: saiSkelSweep 1.15s linear infinite; }
    html.dark .sai-sess-skel::before {
        background: linear-gradient(100deg, rgba(255,255,255,0) 20%, rgba(255,255,255,.09) 50%, rgba(255,255,255,0) 80%);
        background-size: 220% 100%; }
    /* Intro card shown when a session is empty */
    .sai-intro { margin: auto; max-width: 30rem; text-align: center; color: var(--color-gray-500); padding: 1.5rem 1rem; animation: saiRise .28s cubic-bezier(.22,1,.36,1) both; }
    .sai-intro-badge { width: 3rem; height: 3rem; border-radius: 999px; margin: 0 auto .7rem; display: flex; align-items: center; justify-content: center; font-size: 1.4rem; background: linear-gradient(150deg, #6b9f3d, #3d6823); color: #fff; box-shadow: 0 6px 18px rgb(61 104 35 / .3); }
    .sai-intro h4 { font-family: var(--font-heading); font-weight: 800; font-size: 1rem; color: var(--color-gray-900); margin-bottom: .35rem; }
    .sai-intro p { font-size: .85rem; line-height: 1.55; }
    .sai-intro .sai-chips { display: flex; flex-wrap: wrap; gap: .35rem; justify-content: center; margin-top: .9rem; }
    .sai-chip { padding: .3rem .6rem; border-radius: 999px; background: var(--color-gray-100); color: var(--color-gray-700); font-size: .74rem; font-weight: 700; transition: background .15s ease; }
    .sai-chip:hover { background: var(--color-brand-50); color: var(--color-brand-700); }
    /* Only NEW messages animate in — a session's history arrives settled, it
       does not cascade on open. */
    .sai-msg { display: flex; gap: .45rem; margin-bottom: .6rem; align-items: flex-end; }
    .sai-msg.is-new { animation: saiRise .24s cubic-bezier(.22,1,.36,1) both; }
    .sai-msg.me { flex-direction: row-reverse; }
    @keyframes saiRise { from { opacity: 0; transform: translateY(8px); } to { opacity: 1; transform: none; } }
    .sai-face { width: 1.8rem; height: 1.8rem; border-radius: 999px; flex-shrink: 0; display: flex; align-items: center; justify-content: center; font-size: .62rem; font-weight: 800; background: var(--color-brand-50); color: var(--color-brand-700); }
    .sai-face.bot { background: linear-gradient(150deg, #6b9f3d, #3d6823); color: #fff; }
    .sai-col { max-width: 82%; display: flex; flex-direction: column; }
    .sai-msg.me .sai-col { align-items: flex-end; }
    .sai-who { font-size: .64rem; font-weight: 700; color: var(--color-gray-500); margin: 0 .25rem .1rem; }
    .sai-b { padding: .5rem .75rem; font-size: .92rem; line-height: 1.5; background: var(--color-gray-100); color: var(--color-gray-900); border-radius: .9rem .9rem .9rem .25rem; word-break: break-word; }
    .sai-msg.me .sai-b { background: linear-gradient(135deg, #4a7c2a, #3d6823); color: #fff; border-radius: .9rem .9rem .25rem .9rem; }
    .sai-b.bot { background: var(--color-white); border: 1px solid var(--color-gray-100); box-shadow: 0 1px 2px rgb(26 26 26 / .06); }
    .sai-b p { margin: .25rem 0; } .sai-b p:first-child { margin-top: 0; } .sai-b p:last-child { margin-bottom: 0; }
    .sai-b ul { list-style: disc; padding-left: 1.1rem; margin: .25rem 0; } .sai-b ol { list-style: decimal; padding-left: 1.25rem; margin: .25rem 0; }
    .sai-b img { max-width: 100%; max-height: 180px; border-radius: .5rem; margin-top: .3rem; }
    .sai-cost { display: inline-flex; align-items: center; gap: .25rem; margin-top: .35rem; padding: .1rem .45rem; border-radius: 999px; font-size: .62rem; font-weight: 800; color: #8a6100; background: rgb(245 197 24 / .15); }
    html.dark .sai-cost { color: var(--color-accent-400); }
    /* A whispered clock, not a shout. */
    .sai-when { display: block; font-size: .62rem; font-weight: 600; opacity: .55; margin-top: .25rem; text-align: right; font-variant-numeric: tabular-nums; }
    .sai-dots { display: inline-flex; gap: .2rem; align-items: center; height: 1rem; }
    .sai-dots i { width: .35rem; height: .35rem; border-radius: 999px; background: var(--color-brand-500); opacity: .35; animation: saidot .9s cubic-bezier(.4,0,.2,1) infinite; }
    .sai-dots i:nth-child(2) { animation-delay: .15s; } .sai-dots i:nth-child(3) { animation-delay: .3s; }
    @keyframes saidot { 0%,60%,100% { opacity: .3; transform: translateY(0); } 30% { opacity: 1; transform: translateY(-3px); } }
    .sai-composer { flex-shrink: 0; padding: .55rem .7rem .7rem; border-top: 1px solid var(--color-gray-100); }
    /* One chip per attached photo, each with its own remove; a chip mid-upload
       wears a spinner instead of ✕ (same language as the page composer). */
    .sai-photochips { display: flex; flex-wrap: wrap; gap: .4rem; margin-bottom: .4rem; }
    .sai-photochips:empty { display: none; }
    .sai-chip { position: relative; width: 3rem; height: 3rem; border-radius: .7rem; overflow: hidden;
        box-shadow: 0 0 0 2px var(--color-brand-200); background: var(--color-gray-100);
        animation: saiChipIn .28s cubic-bezier(.22,1,.36,1) both; }
    .sai-chip img { width: 100%; height: 100%; object-fit: cover; display: block; }
    .sai-chip .x { position: absolute; top: .12rem; right: .12rem; width: 1.15rem; height: 1.15rem;
        border-radius: 999px; display: flex; align-items: center; justify-content: center;
        background: rgb(17 24 39 / .72); color: #fff; font-size: .6rem; font-weight: 800; line-height: 1;
        transition: transform .15s ease, background-color .15s ease; }
    .sai-chip .x:hover { background: #b91c1c; transform: scale(1.1); }
    .sai-chip .st { position: absolute; inset: 0; display: none; align-items: center; justify-content: center;
        background: rgb(255 255 255 / .55); color: var(--color-brand-700); }
    .sai-chip.is-busy .st { display: flex; }
    .sai-chip.is-busy .x { display: none; }
    html.dark .sai-chip .st { background: rgb(0 0 0 / .45); color: #fff; }
    @keyframes saiChipIn { from { opacity: 0; transform: scale(.8); } to { opacity: 1; transform: none; } }
    /* The chip shimmers under its picture while the copy is in flight. */
    .sai-chip.is-busy { background: linear-gradient(100deg, var(--color-gray-100) 40%, var(--color-gray-200) 50%, var(--color-gray-100) 60%);
        background-size: 200% 100%; animation: saiChipShimmer 1.2s linear infinite; }
    @keyframes saiChipShimmer { to { background-position: -200% 0; } }
    .sai-busyline { display: flex; align-items: center; gap: .4rem; margin-bottom: .4rem;
        font-size: .72rem; font-weight: 700; color: var(--color-brand-700); }
    .sai-busyline.hidden { display: none; }
    .sai-busyline .sp { width: .8rem; height: .8rem; border-radius: 999px; flex-shrink: 0;
        border: 2px solid var(--color-brand-200); border-top-color: var(--color-brand-600);
        animation: saiSpin .8s linear infinite; }
    @keyframes saiSpin { to { transform: rotate(360deg); } }
    .sai-spin { animation: saiSpin .7s linear infinite; }
    /* The running price, under the box where the eye already is. */
    .sai-estimate { margin-top: .35rem; text-align: center; font-size: .7rem; font-weight: 600;
        color: var(--color-gray-500); font-variant-numeric: tabular-nums; }
    /* The sheets' rows (house sheet language; the page's .ai-attach-opt is not
       on this page's stylesheet). */
    .sai-opt { display: flex; align-items: center; gap: .75rem; width: 100%; padding: .7rem .8rem;
        border-radius: .9rem; text-align: left; font-size: .95rem; font-weight: 700; color: var(--color-gray-800);
        transition: background-color .15s ease; }
    .sai-opt:hover { background: var(--color-gray-100); }
    .sai-opt .ic { width: 2.4rem; height: 2.4rem; border-radius: .8rem; flex-shrink: 0; display: flex;
        align-items: center; justify-content: center; background: var(--color-brand-50); color: var(--color-brand-700); }
    .sai-opt .sub { display: block; font-size: .72rem; font-weight: 600; color: var(--color-gray-400); }
    .sai-box { display: flex; align-items: flex-end; gap: .25rem; border: 1.5px solid var(--color-gray-200); border-radius: 1.1rem; padding: .2rem .2rem .2rem .4rem; background: var(--color-white); }
    .sai-box:focus-within { border-color: var(--color-brand-500); box-shadow: 0 0 0 3px rgb(107 159 61 / .18); }
    .sai-cam { width: 2.15rem; height: 2.15rem; border-radius: .7rem; display: flex; align-items: center; justify-content: center; flex-shrink: 0; background: var(--color-brand-50); color: var(--color-brand-700); cursor: pointer; }
    .sai-cam:hover { background: var(--color-brand-100); }
    /* No scrollbar while the box is still growing — the autosize handler flips
       this to auto only once the text passes the max height. */
    #saiText { resize: none; border: 0; outline: none; background: transparent; flex: 1 1 auto; max-height: 6rem; overflow-y: hidden; padding: .4rem .25rem; font-size: .92rem; color: inherit; }
    .sai-send { width: 2.15rem; height: 2.15rem; border-radius: 999px; background: linear-gradient(140deg, #6b9f3d, #3d6823); color: #fff; display: flex; align-items: center; justify-content: center; flex-shrink: 0; box-shadow: 0 2px 8px -2px rgb(45 80 22 / .5); transition: transform .15s ease, opacity .15s ease; }
    .sai-send:hover:not(:disabled) { transform: scale(1.06); }
    .sai-send:active:not(:disabled) { transform: scale(.92); }
    .sai-send:disabled { opacity: .4; }
    /* Sidebar backdrop on mobile */
    .sai-backdrop { position: absolute; inset: 0; z-index: 4; background: rgb(17 24 39 / .35); opacity: 0; visibility: hidden; transition: opacity .28s cubic-bezier(.22,1,.36,1), visibility .28s; }
    .sai-backdrop.on { opacity: 1; visibility: visible; }
    @media (max-width: 640px) {
        .sai-sess-toggle { display: inline-flex; }
        .sai-sessions { position: absolute; inset: 0 auto 0 0; z-index: 5; width: min(15rem, 82%); box-shadow: 8px 0 26px rgb(0 0 0 / .18); }
        .sai-sessions.collapsed { transform: translateX(-102%); }
    }
    @media (prefers-reduced-motion: reduce) {
        .sai-head, .sai-msg.is-new, .sai-intro, .sai-chip { animation: none; }
        .sai-sessions, .sai-backdrop, .sai-send, .sai-save, .sai-chip, .sai-chip .x, .sai-opt { transition: none; }
        /* Loaders slow down rather than stop — the motion is the message. */
        .sai-dots i { animation-duration: 1.8s; }
        .sai-busyline .sp, .sai-spin { animation-duration: 1.6s; }
        .sai-chip.is-busy { animation: none; }
        .sai-skel-face::before, .sai-skel-b::before, .sai-sess-skel::before { animation: none; }
    }
</style>

<script>
(() => {
    const init = () => {
        const wrap = document.getElementById('saiWrap');
        if (!wrap || wrap.dataset.bound) return;
        wrap.dataset.bound = '1';
        const $ = (id) => document.getElementById(id);
        const thread = $('saiThread');

        const SCHEDULE_ID = @json($schedule->id);
        const ME = @json((int) auth()->id());
        const MY_INITIALS = @json(auth()->user()->initials ?? '');
        const U = {
            messages: @json(route('sm.ai.group.messages')),
            ask: @json(route('sm.ai.group.ask')),
            photo: @json(route('ai.photo')),
            sessions: @json(route('sm.ai.group.sessions')),
            sessionCreate: @json(route('sm.ai.group.session-create')),
            sessionNote: @json(route('sm.ai.group.session-note')),
        };

        const PRICE = @json($saiPriceCard);

        const rendered = new Set();
        let lastId = 0, busy = false, started = false, pollTimer = null, sessTimer = null, channel = null, thinkingEl = null;
        let currentSession = null, sessions = [], sessReq = 0, uploadsBusy = 0;

        const esc = (s) => String(s == null ? '' : s).replace(/&/g, '&amp;').replace(/</g, '&lt;').replace(/>/g, '&gt;').replace(/"/g, '&quot;');
        const scrollDown = () => { thread.scrollTop = thread.scrollHeight; };

        // Tiny markdown-ish formatter (same behaviour as the AI float).
        function render(text) {
            const lines = esc(text || '').split(/\r?\n/); let html = ''; let list = null;
            const close = () => { if (list) { html += `</${list}>`; list = null; } };
            const inline = (s) => s.replace(/\*\*([^*]+)\*\*/g, '<strong>$1</strong>');
            for (const raw of lines) {
                const line = raw.trim();
                if (!line) { close(); continue; }
                const b = line.match(/^[-*•]\s+(.*)$/), n = line.match(/^\d+[.)]\s+(.*)$/);
                if (b) { if (list !== 'ul') { close(); html += '<ul>'; list = 'ul'; } html += '<li>' + inline(b[1]) + '</li>'; }
                else if (n) { if (list !== 'ol') { close(); html += '<ol>'; list = 'ol'; } html += '<li>' + inline(n[1]) + '</li>'; }
                else { close(); html += '<p>' + inline(line) + '</p>'; }
            }
            close(); return html || '<p></p>';
        }
        const faceHtml = (m) => m.role === 'assistant'
            ? '<span class="sai-face bot">AI</span>'
            : `<span class="sai-face">${esc(m.mine ? MY_INITIALS : (m.initials || '·'))}</span>`;

        // Ghost bubbles shown while a session loads (matches the server-rendered
        // skeleton the tab opens with).
        const SKEL = '<div class="sai-skel" id="saiLoading" aria-hidden="true">'
            + '<div class="sai-skel-row"><span class="sai-skel-face"></span><span class="sai-skel-b"></span></div>'
            + '<div class="sai-skel-row me"><span class="sai-skel-face"></span><span class="sai-skel-b"></span></div>'
            + '<div class="sai-skel-row tall"><span class="sai-skel-face"></span><span class="sai-skel-b"></span></div>'
            + '</div>';

        /* ---------- intro (shown when a session is empty) ---------- */
        function showIntro() {
            if ($('saiIntro')) return;
            $('saiLoading')?.remove();
            const el = document.createElement('div');
            el.className = 'sai-intro'; el.id = 'saiIntro';
            el.innerHTML = `
                <div class="sai-intro-badge">🤖</div>
                <h4>Hi team — I'm your AI Technician</h4>
                <p>Ask me anything about this cropping plan: pests &amp; diseases, fertilizer rates and timing, irrigation, planting and harvest windows, or troubleshooting a problem. Everyone on the team sees the questions and answers, and you can save a whole session to your schedule notes.</p>
                <div class="sai-chips">
                    <button type="button" class="sai-chip" data-ask="What pests should I watch for at this crop stage?">Pests to watch</button>
                    <button type="button" class="sai-chip" data-ask="Suggest a fertilizer schedule for this crop.">Fertilizer plan</button>
                    <button type="button" class="sai-chip" data-ask="How much should I irrigate this week?">Irrigation</button>
                </div>`;
            thread.appendChild(el);
            el.querySelectorAll('.sai-chip').forEach((c) => c.addEventListener('click', () => {
                $('saiText').value = c.getAttribute('data-ask'); send();
            }));
        }
        const clearIntro = () => $('saiIntro')?.remove();

        // `settled` marks history batches: they arrive without the entrance
        // animation, so opening a session never cascades.
        function addMsg(m, settled) {
            if (m.id) { if (rendered.has(m.id)) return; rendered.add(m.id); if (m.id > lastId) lastId = m.id; }
            $('saiLoading')?.remove(); clearIntro();
            const me = !!m.mine;
            const el = document.createElement('div');
            el.className = 'sai-msg' + (settled ? '' : ' is-new') + (me ? ' me' : '');
            const who = (!me && m.role === 'user') ? `<span class="sai-who">${esc(m.name || 'Member')}</span>` : '';
            const body = m.role === 'assistant' ? render(m.content) : ('<p>' + esc(m.content).replace(/\r?\n/g, '<br>') + '</p>');
            const img = m.image ? `<img src="${esc(m.image)}" alt="">` : '';
            const cost = (m.role === 'assistant' && m.creditsCharged) ? `<p class="sai-cost">${esc(String(Math.round(m.creditsCharged * 100) / 100))} credits</p>` : '';
            const when = m.at ? `<time class="sai-when">${esc(m.at)}</time>` : '';
            el.innerHTML = `${faceHtml(m)}<div class="sai-col">${who}<div class="sai-b ${m.role === 'assistant' ? 'bot' : ''}">${body}${img}${cost}${when}</div></div>`;
            thread.appendChild(el); scrollDown();
        }
        function showThinking() {
            clearThinking(); clearIntro();
            const el = document.createElement('div');
            el.className = 'sai-msg is-new';
            el.innerHTML = '<span class="sai-face bot">AI</span><div class="sai-col"><div class="sai-b bot"><span class="sai-dots"><i></i><i></i><i></i></span></div></div>';
            thread.appendChild(el); thinkingEl = el; scrollDown();
        }
        function clearThinking() { if (thinkingEl) { thinkingEl.remove(); thinkingEl = null; } }
        function setBalance(v) { if (v !== undefined && v !== null) $('saiBalance').textContent = String(Math.round(v * 100) / 100); }

        /* ---------- sessions sidebar ---------- */
        function renderSessions() {
            const list = $('saiSessionsList');
            if (!sessions.length) { list.innerHTML = '<div class="sai-sessions-empty">No sessions yet.</div>'; return; }
            list.innerHTML = '';
            sessions.forEach((s) => {
                const b = document.createElement('button');
                b.type = 'button';
                b.className = 'sai-sess' + (s.id === currentSession ? ' is-active' : '');
                b.innerHTML = `<span class="sai-sess-title">${esc(s.title)}</span><span class="sai-sess-meta">${esc(s.startedBy)} · ${esc(s.at || '')}</span>`;
                b.addEventListener('click', () => { openSession(s.id); closeSidebarMobile(); });
                list.appendChild(b);
            });
        }
        function upsertSession(s) {
            const i = sessions.findIndex((x) => x.id === s.id);
            // Replace in place on update; a brand-new session goes to the top (newest).
            if (i >= 0) sessions[i] = s; else sessions.unshift(s);
            renderSessions();
        }
        async function loadSessions(selectId) {
            const my = ++sessReq;
            try {
                const r = await api(`${U.sessions}?scheduleId=${SCHEDULE_ID}${currentSession ? '&sessionId=' + currentSession : ''}`);
                if (my !== sessReq) return;
                sessions = r.data.sessions || [];
                if (selectId) currentSession = selectId;
                else if (!currentSession) currentSession = r.data.currentId;
                renderSessions();
            } catch (_) { /* keep last */ }
        }

        async function openSession(id) {
            if (id === currentSession && started && !$('saiLoading')) return;
            currentSession = id;
            renderSessions();
            rendered.clear(); lastId = 0; clearThinking();
            thread.innerHTML = SKEL;
            try {
                const res = await api(`${U.messages}?scheduleId=${SCHEDULE_ID}&sessionId=${currentSession}&after=0`);
                currentSession = res.data.sessionId || currentSession;
                $('saiLoading')?.remove();
                const msgs = res.data.messages || [];
                // History arrives settled — only live turns animate in.
                if (!msgs.length) showIntro(); else msgs.forEach((m) => addMsg(m, true));
                if (res.data.maxId > lastId) lastId = res.data.maxId;
                setBalance(res.data.balance);
            } catch (_) { const l = $('saiLoading'); if (l) l.outerHTML = '<div class="sai-loading" id="saiLoading">Could not load.</div>'; }
        }

        async function newSession() {
            try {
                const r = await api(`${U.sessionCreate}?scheduleId=${SCHEDULE_ID}`, { method: 'POST', body: {} });
                if (r.data && r.data.session) { upsertSession(r.data.session); await openSession(r.data.session.id); closeSidebarMobile(); }
            } catch (err) { if (window.toast) toast(err.message || 'Could not start a session.', 'error'); }
        }

        /* Keeping a session: which door first, then its name. Nothing lands in
         * the notebook unnamed, and a task variant says which task it belongs
         * to before the transcript starts. */
        let pendingTaskId = null;
        function fileAway(activityId) {
            if (!currentSession) { window.toast?.('Nothing to keep yet — ask something first.', 'error'); return; }
            pendingTaskId = activityId || null;
            const head = $('saiNoteHeading');
            if (head) head.textContent = pendingTaskId ? 'Attach this session to the task' : 'Save this session as a note';
            $('saiNoteTitle').value = '';
            $('saiNoteDesc').value = '';
            window.openSheet?.('saiNoteSheet');
            window.smFocus?.($('saiNoteTitle'), { delay: 120 });
        }
        async function saveNote() {
            // Its own gate, not fileAway's. This button lives on the sheet, and
            // a sheet outlives the door that opened it — a stale one still on
            // screen must never POST a null sessionId at the notebook.
            if (!currentSession) {
                window.toast?.('Nothing to keep yet — ask something first.', 'error');
                window.closeSheet?.('saiNoteSheet');
                return;
            }
            const btn = $('saiNoteSave');
            const was = btn.textContent;
            btn.disabled = true; btn.textContent = 'Saving…';
            try {
                const r = await api(`${U.sessionNote}?scheduleId=${SCHEDULE_ID}`, { method: 'POST', body: {
                    sessionId: currentSession,
                    activityId: pendingTaskId,
                    title: $('saiNoteTitle').value.trim(),
                    description: $('saiNoteDesc').value.trim(),
                } });
                window.closeSheet?.('saiNoteSheet');
                if (window.toast) toast((r && r.message) || 'Saved to the schedule notebook.', 'success');
            } catch (err) { if (window.toast) toast(err.message || 'Could not save this session.', 'error'); }
            finally { btn.disabled = false; btn.textContent = was; }
        }

        const refreshSessionsSoon = (() => {
            let t = null;
            return () => { clearTimeout(t); t = setTimeout(() => loadSessions(), 400); };
        })();

        /* ---------- ask / realtime ---------- */
        async function send() {
            if (busy) return;
            if (uploadsBusy > 0) { window.toast?.('Wait a moment — a photo is still uploading.', 'error'); return; }
            const text = ($('saiText').value || '').trim();
            const shots = attachedPaths();
            if (!text && !shots.length) return;
            if (!currentSession) { await loadSessions(); }
            busy = true; $('saiSend').disabled = true; clearIntro();
            addMsg({ id: null, role: 'user', mine: true, content: text, image: attachedUrls()[0] || null });
            $('saiText').value = ''; $('saiText').style.height = 'auto'; $('saiText').style.overflowY = 'hidden';
            showThinking();
            try {
                const res = await api(U.ask + `?scheduleId=${SCHEDULE_ID}`, { method: 'POST', body: {
                    message: text,
                    imagePaths: shots,
                    imageScheduleIds: attachedScheds(),
                    sessionId: currentSession,
                } });
                // Chips leave the moment the send is known good — before any
                // templating that could throw and strand them in the composer.
                clearPhotos();
                if (res.data.question && res.data.question.id) { rendered.add(res.data.question.id); lastId = Math.max(lastId, res.data.question.id); }
                clearThinking();
                addMsg(res.data.answer); setBalance(res.data.balance);
                refreshSessionsSoon();
            } catch (err) {
                clearThinking();
                addMsg({ role: 'assistant', content: err.message || 'The AI could not answer.' });
                if (err.data && err.data.outOfCredits) setBalance(err.data.balance);
                // Kept on purpose — a retry should not re-pick its photos. Said
                // out loud, so a failed send never reads as "sent but cleared".
                if (chips.children.length) window.toast?.('Your photos are still attached, ready for the retry.');
            } finally { busy = false; $('saiSend').disabled = false; $('saiText').focus(); sayEstimate(); }
        }

        function onQuestion(m) {
            if (m.sessionId && m.sessionId !== currentSession) { refreshSessionsSoon(); return; }
            if (m.id && rendered.has(m.id)) { showThinking(); return; }
            if (!(m.userId === ME)) addMsg(m); else if (m.id) { rendered.add(m.id); if (m.id > lastId) lastId = m.id; }
            showThinking();
        }
        function onAnswer(m) {
            if (m.sessionId && m.sessionId !== currentSession) { refreshSessionsSoon(); return; }
            clearThinking();
            if (m.error) { addMsg({ role: 'assistant', content: m.content || 'The AI could not answer.' }); return; }
            addMsg(m); setBalance(m.balance);
        }
        function onSession(payload) {
            if (payload && payload.session) upsertSession(payload.session);
        }

        async function reconcile() {
            if (!currentSession) return;
            try {
                const res = await api(`${U.messages}?scheduleId=${SCHEDULE_ID}&sessionId=${currentSession}&after=${lastId}`);
                (res.data.messages || []).forEach(addMsg);
                setBalance(res.data.balance);
            } catch (_) {}
        }
        async function start() {
            if (started) return; started = true;
            await loadSessions();
            await openSession(currentSession);
            if (window.Echo) {
                try {
                    channel = window.Echo.private('schedule-board.' + SCHEDULE_ID);
                    channel.listen('.ai.question', onQuestion).listen('.ai.answer', onAnswer).listen('.ai.session', onSession);
                } catch (_) {}
            }
            // Cadence follows the socket's real state, so a Pusher key that
            // never connects polls fast rather than backing off to 6s.
            const live = () => window.realtimeReady?.() ?? false;
            const tick = async () => { await reconcile(); pollTimer = setTimeout(tick, live() ? 6000 : 2500); };
            const sessTick = async () => { await loadSessions(); sessTimer = setTimeout(sessTick, live() ? 20000 : 12000); };
            pollTimer = setTimeout(tick, 2500);
            sessTimer = setTimeout(sessTick, 12000);
        }

        /* ---------- sidebar toggle (mobile) ---------- */
        function ensureBackdrop() {
            let bd = $('saiBackdrop');
            if (!bd) { bd = document.createElement('div'); bd.id = 'saiBackdrop'; bd.className = 'sai-backdrop'; bd.addEventListener('click', closeSidebarMobile); wrap.querySelector('.sai-layout').appendChild(bd); }
            return bd;
        }
        function openSidebarMobile() { $('saiSessions').classList.remove('collapsed'); ensureBackdrop().classList.add('on'); }
        function closeSidebarMobile() { if (window.matchMedia('(max-width: 640px)').matches) { $('saiSessions').classList.add('collapsed'); $('saiBackdrop')?.classList.remove('on'); } }
        // Start collapsed on mobile.
        if (window.matchMedia('(max-width: 640px)').matches) $('saiSessions').classList.add('collapsed');

        // Composer + control wiring.
        const input = $('saiText');
        const TEXT_MAX = 96;
        input.addEventListener('input', () => {
            input.style.height = 'auto';
            // The bar only shows once the box has stopped growing — while it
            // still fits, the height IS the scroll.
            input.style.overflowY = input.scrollHeight > TEXT_MAX ? 'auto' : 'hidden';
            input.style.height = Math.min(input.scrollHeight, TEXT_MAX) + 'px';
            sayEstimate();
        });
        input.addEventListener('keydown', (e) => { if (e.key === 'Enter' && !e.shiftKey && window.matchMedia('(min-width: 768px)').matches) { e.preventDefault(); send(); } });
        $('saiSend').addEventListener('click', send);
        $('saiNewSession').addEventListener('click', newSession);
        $('saiSessToggle').addEventListener('click', () => {
            const collapsed = $('saiSessions').classList.contains('collapsed');
            if (collapsed) openSidebarMobile(); else closeSidebarMobile();
        });

        /* ---------- keeping a session: the two doors ---------- */
        $('saiSaveSession').addEventListener('click', () => window.openSheet?.('saiSaveSheet'));
        $('saiSaveToNote').addEventListener('click', () => { window.closeSheet?.('saiSaveSheet'); fileAway(null); });
        $('saiSaveToTask').addEventListener('click', () => {
            window.closeSheet?.('saiSaveSheet');
            if (!currentSession) { window.toast?.('Nothing to keep yet — ask something first.', 'error'); return; }
            window.openSheet?.('saiTaskSheet');
        });
        // Bound on the sheet itself: openSheet moves it to <body>, out of this
        // partial's subtree, so a wrapper-level listener would never hear it.
        $('saiTaskSheet').addEventListener('click', (e) => {
            const b = e.target.closest('[data-sai-task]');
            if (!b) return;
            window.closeSheet?.('saiTaskSheet');
            fileAway(parseInt(b.dataset.saiTask, 10));
        });
        $('saiNoteSave').addEventListener('click', saveNote);

        /* The chips, and every door a photo comes through.
         *
         * The lightbox hands over a photo already copied into this user's AI
         * folder; the file inputs upload new ones; the gallery hands back a
         * reference to what this season already keeps. All three end as the
         * same chip, so nothing downstream needs to know which way it arrived. */
        const chips = $('saiPhotoChips');
        const MAX_PHOTOS = 4;
        const CHIP_SPIN = '<svg class="w-4 h-4 sai-spin" viewBox="0 0 24 24" fill="none" aria-hidden="true"><circle cx="12" cy="12" r="9" stroke="currentColor" stroke-width="2.6" stroke-opacity=".3"/><path d="M21 12a9 9 0 0 0-9-9" stroke="currentColor" stroke-width="2.6" stroke-linecap="round"/></svg>';

        function attachedChips() { return [...chips.querySelectorAll('.sai-chip[data-path]')]; }
        function attachedPaths() { return attachedChips().map((c) => c.dataset.path); }
        function attachedUrls() { return attachedChips().map((c) => c.querySelector('img')?.src).filter(Boolean); }
        // Index-aligned with the paths: which season's gallery a picture was
        // referenced from, null for this user's own uploads.
        function attachedScheds() { return attachedChips().map((c) => (c.dataset.sched ? parseInt(c.dataset.sched, 10) : null)); }
        function roomForAnother() {
            if (chips.children.length < MAX_PHOTOS) return true;
            window.toast?.('Four photos is as many as one question can carry.', 'error');
            return false;
        }
        function addChip(previewUrl) {
            const el = document.createElement('div');
            el.className = 'sai-chip is-busy';
            el.innerHTML = `<img src="${esc(previewUrl)}" alt="">`
                + `<span class="st">${CHIP_SPIN}</span>`
                + '<button type="button" class="x" aria-label="Remove photo">✕</button>';
            chips.appendChild(el);
            sayEstimate();
            return el;
        }
        function dropChip(el) {
            if (el._blob) { try { URL.revokeObjectURL(el._blob); } catch (e) {} }
            el.remove();
            sayEstimate();
        }
        function clearPhotos() { [...chips.children].forEach(dropChip); }
        chips.addEventListener('click', (e) => {
            const x = e.target.closest('.sai-chip .x');
            if (x) dropChip(x.closest('.sai-chip'));
        });
        function sayAttaching() {
            const line = $('saiAttachBusy');
            if (!line) return;
            line.classList.toggle('hidden', uploadsBusy === 0);
            line.querySelector('.tx').textContent = uploadsBusy > 1 ? `Attaching ${uploadsBusy} photos…` : 'Attaching photo…';
            $('saiSend').disabled = busy || uploadsBusy > 0;
        }
        // Uploads run one call per file; the chip spins until its path lands.
        function uploadOne(file) {
            if (!file || !(file.type || '').startsWith('image/')) return;
            if (!roomForAnother()) return;
            const preview = URL.createObjectURL(file);
            const chip = addChip(preview);
            chip._blob = preview;
            uploadsBusy++; sayAttaching();
            const form = new FormData(); form.append('image', file);
            api(U.photo, { method: 'POST', body: form })
                .then((r) => { chip.dataset.path = r.data.path; chip.classList.remove('is-busy'); })
                .catch((err) => { window.toast?.(err.message, 'error'); dropChip(chip); })
                .finally(() => { uploadsBusy--; sayAttaching(); });
        }
        $('saiPhotoFiles').addEventListener('change', (e) => { [...(e.target.files || [])].forEach(uploadOne); e.target.value = ''; });
        $('saiPhotoCam').addEventListener('change', (e) => { [...(e.target.files || [])].forEach(uploadOne); e.target.value = ''; });

        /* A gallery pick is a REFERENCE, not a copy: the server already hosts
         * this picture and the ask endpoint honours this schedule's own media
         * list — so the chip lands done, instantly. */
        function attachFromGallery(item) {
            if (!item || !item.url || !item.path) return;
            // The same picture picked twice is one photo to the server, which
            // dedupes by path and bills once — a second chip would only make
            // the estimate under the box quote a photo nobody pays for. Asked
            // before the room count, so a re-pick never burns one of the four
            // slots nor fires the "four is as many as one question can carry"
            // toast. (window.smAskAiAbout has always asked this; this door,
            // and only this door, forgot to.)
            if (attachedPaths().includes(item.path)) return;
            if (!roomForAnother()) return;
            // addChip reprices the line; the chip is the only thing the
            // estimate and the remove button both count, so one add is one
            // quote and one ✕ is one unquote.
            const chip = addChip(item.url);
            chip.dataset.path = item.path;
            chip.dataset.sched = String(SCHEDULE_ID);
            chip.classList.remove('is-busy');
        }

        const canGallery = () => typeof window.smPickMedia === 'function' && SCHEDULE_ID > 0;
        $('saiAttachBtn').addEventListener('click', () => {
            $('saiAttachGallery')?.classList.toggle('hidden', !canGallery());
            window.openSheet?.('saiAttachSheet');
        });
        $('saiAttachUpload').addEventListener('click', () => { window.closeSheet?.('saiAttachSheet'); $('saiPhotoFiles').click(); });
        $('saiAttachCamera').addEventListener('click', () => { window.closeSheet?.('saiAttachSheet'); $('saiPhotoCam').click(); });
        $('saiAttachGallery').addEventListener('click', () => {
            window.closeSheet?.('saiAttachSheet');
            if (!canGallery()) return;
            window.smPickMedia({
                scheduleId: SCHEDULE_ID,
                kinds: 'image',
                title: 'Attach from the gallery',
                // Several at once — the question can carry what room remains.
                multiple: true,
                max: MAX_PHOTOS - chips.children.length,
                onPick: attachFromGallery,
            });
        });

        window.smAskAiAbout = function (item) {
            if (!item || !item.path) return;
            if (attachedPaths().includes(item.path)) return;
            if (!roomForAnother()) return;
            const chip = addChip(item.url || '');
            chip.dataset.path = item.path;
            chip.classList.remove('is-busy');
            // Bring the composer into view and let them type the question.
            window.smShowAiTab?.();
            window.smFocus?.($('saiText'), { delay: 120 });
            window.toast?.('Photo attached — what would you like to ask about it?');
        };

        /* The bill, quoted before it is run up: the server's own pre-flight
         * formula, mirrored, repriced on every keystroke and every chip. */
        function sayEstimate() {
            const line = $('saiEstimate');
            if (!line) return;
            const msg = (input.value || '').trim();
            const shots = chips ? chips.children.length : 0;
            if (!msg && !shots) { line.textContent = line.dataset.idle || ''; return; }
            const tin = Math.ceil(msg.length / 4) + 900;
            const cost = Math.max(.01, Math.round((tin / 1000 * PRICE.inK + PRICE.halfOut / 1000 * PRICE.outK + shots * PRICE.img) * 100) / 100);
            line.textContent = `≈ ${cost} credits for this question`;
        }


        // Start when the AI tab is first shown.
        document.addEventListener('collab:show', (e) => { if (e.detail && e.detail.tab === 'ai') start(); });
    };
    if (document.readyState === 'loading') document.addEventListener('DOMContentLoaded', init, { once: true });
    else init();
})();
</script>
