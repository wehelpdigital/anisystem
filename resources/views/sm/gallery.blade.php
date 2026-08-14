@extends(request()->boolean('partial') ? 'layouts.partial' : 'layouts.app')

@section('title', 'Gallery — ' . $schedule->title)
@section('page-title', 'Gallery')
@section('page-subtitle', $schedule->title)
@section('help-key', 'gallery')
@section('back', route('sm.hub', ['id' => $schedule->id]))

@push('head')
<style>
    .ga-top { display: flex; align-items: center; gap: .5rem; flex-wrap: wrap; margin-bottom: .9rem; }
    .ga-new { display: inline-flex; align-items: center; gap: .4rem; height: 2.75rem; padding: 0 1rem;
        border-radius: .8rem; background: #4a7c2a; color: #fff; font-size: .85rem; font-weight: 800;
        cursor: pointer; box-shadow: 0 8px 18px -12px rgb(61 104 35 / .9); }
    .ga-new:hover { background: #3d6823; }
    .ga-new svg { width: 1.05rem; height: 1.05rem; }

    .ga-album { border: 1px solid var(--color-gray-200); border-radius: 1rem; background: var(--color-white);
        margin-bottom: .9rem; overflow: hidden; }
    .ga-head { display: flex; align-items: flex-start; gap: .6rem; padding: .8rem .9rem; }
    .ga-title { font-size: .98rem; font-weight: 800; color: var(--color-gray-900); }
    .ga-desc { font-size: .8rem; line-height: 1.45; color: var(--tl-text-muted, #6b7280); margin-top: .15rem; }
    .ga-count { font-size: .68rem; font-weight: 800; text-transform: uppercase; letter-spacing: .04em;
        color: #3d6823; background: #e4efd4; border-radius: 999px; padding: .15rem .5rem; }
    .ga-acts { margin-left: auto; display: flex; gap: .25rem; flex: 0 0 auto; }
    .ga-act { width: 2rem; height: 2rem; border-radius: .55rem; display: inline-flex; align-items: center;
        justify-content: center; color: var(--color-gray-500); background: var(--color-gray-50); cursor: pointer; }
    .ga-act:hover { background: var(--color-gray-100); color: var(--color-gray-800); }
    .ga-act.is-danger:hover { background: #fee2e2; color: #b91c1c; }
    .ga-act svg { width: 1rem; height: 1rem; }

    .ga-grid { display: grid; grid-template-columns: repeat(auto-fill, minmax(7rem, 1fr)); gap: .5rem;
        padding: 0 .9rem .9rem; }
    .ga-cell { position: relative; aspect-ratio: 1; border-radius: .7rem; overflow: hidden; background: #0b1220;
        cursor: pointer; }
    .ga-cell img, .ga-cell video { width: 100%; height: 100%; object-fit: cover; display: block;
        opacity: 0; transition: opacity .28s ease; }
    .ga-cell img.is-loaded, .ga-cell video.is-loaded { opacity: 1; }
    /* So a still frame is not mistaken for a photo. */
    .ga-vid { position: absolute; left: 50%; top: 50%; transform: translate(-50%, -50%);
        width: 2rem; height: 2rem; border-radius: 999px; background: rgb(0 0 0 / .55); color: #fff;
        display: flex; align-items: center; justify-content: center; font-size: .8rem; pointer-events: none; }
    .ga-cell.is-gone::after { content: 'File missing'; position: absolute; inset: 0; display: flex;
        align-items: center; justify-content: center; font-size: .66rem; font-weight: 700; color: #94a3b8; }
    /* Picking is a mode: the tick is always there to start it, and once one
       picture is chosen the whole grid becomes a checklist. */
    .ga-pick { position: absolute; top: .3rem; left: .3rem; width: 1.5rem; height: 1.5rem; border-radius: 999px;
        background: rgb(17 24 39 / .55); border: 2px solid rgb(255 255 255 / .8); display: inline-flex;
        align-items: center; justify-content: center; color: transparent; }
    .ga-cell.is-picked .ga-pick { background: #4a7c2a; border-color: #fff; color: #fff; }
    .ga-cell.is-picked { outline: 3px solid #4a7c2a; outline-offset: -3px; }
    .ga-pick svg { width: .9rem; height: .9rem; }
    .ga-empty { padding: 0 .9rem 1rem; font-size: .8rem; color: var(--color-gray-400); }

    /* The bar that appears once something is picked. */
    .ga-bar { position: fixed; left: 50%; transform: translateX(-50%); bottom: 1rem; z-index: 60;
        display: flex; align-items: center; gap: .5rem; padding: .5rem .6rem; border-radius: 999px;
        background: #10160c; color: #e8efe1; box-shadow: 0 18px 40px -18px rgb(0 0 0 / .8);
        animation: gaBarIn .28s cubic-bezier(.22,1,.36,1) both; }
    @keyframes gaBarIn { from { opacity: 0; transform: translate(-50%, 1rem); } }
    .ga-bar[hidden] { display: none; }
    .ga-bar-n { font-size: .78rem; font-weight: 800; padding-left: .35rem; }
    .ga-bar button { font-size: .78rem; font-weight: 700; padding: .4rem .7rem; border-radius: 999px;
        background: rgb(255 255 255 / .12); color: #e8efe1; cursor: pointer; }
    .ga-bar button:hover { background: rgb(255 255 255 / .22); }
    .ga-bar .ga-bar-del:hover { background: #b91c1c; color: #fff; }
    @media (prefers-reduced-motion: reduce) { .ga-bar { animation: none; } .ga-cell img { transition: none; } }

    /* ---- tabs, and the "everything" shelf ---- */
    /* The shelf picker: one button that says where you are. */
    .ga-pick { margin-bottom: .8rem; }
    .ga-pickbtn { display: inline-flex; align-items: center; gap: .4rem; max-width: 100%;
        padding: .45rem .8rem; border-radius: 999px; cursor: pointer;
        border: 1px solid var(--color-gray-200); background: var(--color-white);
        font-size: .84rem; font-weight: 800; color: var(--color-gray-800);
        transition: border-color .28s cubic-bezier(.22,1,.36,1), color .28s cubic-bezier(.22,1,.36,1); }
    .ga-pickbtn:hover { border-color: #a8cc7e; color: #3d6823; }
    .ga-pickico { width: 1rem; height: 1rem; flex: none; color: #4a7c2a; }
    .ga-pickchev { width: .8rem; height: .8rem; flex: none; color: var(--color-gray-400); }
    #gaTabNow { overflow: hidden; text-overflow: ellipsis; white-space: nowrap; }
    html.dark .ga-pickbtn { background: #1c2416; border-color: #2b3a1c; color: #e8efe1; }

    .ga-modal { position: fixed; inset: 0; z-index: 120; display: flex; align-items: flex-end; justify-content: center; }
    @media (min-width: 640px) { .ga-modal { align-items: center; padding: 1.5rem; } }
    .ga-modal.hidden { display: none; }
    .ga-modal-back { position: absolute; inset: 0; background: rgb(10 14 20 / .55); opacity: 0;
        transition: opacity .28s cubic-bezier(.22,1,.36,1); }
    .ga-modal.is-open .ga-modal-back { opacity: 1; }
    .ga-modal-card { position: relative; width: 100%; max-width: 24rem; background: var(--color-white);
        border-radius: 1rem 1rem 0 0; overflow: hidden; box-shadow: var(--shadow-card-lg);
        transform: translateY(1.5rem); opacity: 0;
        transition: transform .28s cubic-bezier(.22,1,.36,1), opacity .28s cubic-bezier(.22,1,.36,1); }
    @media (min-width: 640px) { .ga-modal-card { border-radius: 1rem; } }
    .ga-modal.is-open .ga-modal-card { transform: none; opacity: 1; }
    .ga-modal-head { display: flex; align-items: center; justify-content: space-between;
        padding: .8rem 1rem; border-bottom: 1px solid var(--color-gray-100); }
    .ga-modal-body { padding: .5rem; display: grid; gap: .2rem;
        padding-bottom: calc(.5rem + env(safe-area-inset-bottom, 0px)); }
    .ga-opt { display: flex; align-items: center; gap: .6rem; width: 100%; text-align: left;
        padding: .6rem .7rem; border-radius: .7rem; cursor: pointer;
        transition: background .28s cubic-bezier(.22,1,.36,1); }
    .ga-opt:hover { background: var(--color-gray-50); }
    .ga-opt.is-on { background: #f4f9ee; }
    .ga-opt-txt { display: flex; flex-direction: column; gap: .1rem; min-width: 0; flex: 1 1 auto; }
    .ga-opt-txt b { font-size: .86rem; font-weight: 700; color: var(--color-gray-900); }
    .ga-opt-txt i { font-style: normal; font-size: .72rem; line-height: 1.4; color: var(--color-gray-500); }
    .ga-opt-tick { flex: none; width: 1.1rem; height: 1.1rem; color: #4a7c2a; opacity: 0;
        transition: opacity .28s cubic-bezier(.22,1,.36,1); }
    .ga-opt-tick svg { width: 100%; height: 100%; }
    .ga-opt.is-on .ga-opt-tick { opacity: 1; }
    html.dark .ga-modal-card { background: #151b12; }
    html.dark .ga-modal-head { border-color: #2b3a1c; }
    html.dark .ga-opt:hover { background: rgb(255 255 255 / .05); }
    html.dark .ga-opt.is-on { background: #24301a; }
    html.dark .ga-opt-txt b { color: #e8efe1; }
    @media (prefers-reduced-motion: reduce) {
        .ga-pickbtn, .ga-modal-back, .ga-modal-card, .ga-opt, .ga-opt-tick { transition: none; }
    }
    /* The count that rides on the picker and on each shelf in the sheet. */
    .ga-n { font-size: .7rem; opacity: .75; font-weight: 800; }
    .ga-pane[hidden] { display: none; }
    .ga-tools { display: flex; gap: .5rem; align-items: center; margin-bottom: .7rem; flex-wrap: wrap; }
    .ga-search { position: relative; flex: 1 1 12rem; }
    .ga-search input { width: 100%; padding: .5rem .7rem .5rem 2.1rem; border-radius: .7rem;
        border: 1px solid var(--color-gray-200); background: var(--color-white); font-size: .85rem; }
    .ga-search svg { position: absolute; left: .65rem; top: 50%; transform: translateY(-50%);
        width: 1rem; height: 1rem; color: var(--color-gray-400); }
    .ga-filters { display: flex; gap: .3rem; flex-wrap: wrap; }
    .ga-filter { padding: .3rem .6rem; border-radius: 999px; font-size: .72rem; font-weight: 700;
        border: 1px solid var(--color-gray-200); background: var(--color-white); color: var(--color-gray-600); cursor: pointer; }
    .ga-filter.is-on { background: #eaf4dd; border-color: #a8cc7e; color: #3d6823; }

    .ga-all { display: grid; grid-template-columns: repeat(auto-fill, minmax(8.5rem, 1fr)); gap: .6rem; }
    .ga-item { position: relative; border-radius: .75rem; overflow: hidden; background: var(--color-white);
        border: 1px solid var(--color-gray-200); text-align: left; display: block;
        transition: transform .28s cubic-bezier(.22,1,.36,1), box-shadow .28s cubic-bezier(.22,1,.36,1); }
    .ga-item:hover { transform: translateY(-2px); box-shadow: 0 12px 26px -18px rgb(0 0 0 / .5); }
    .ga-shot { position: relative; aspect-ratio: 1; background: #0b1220; }
    .ga-shot img, .ga-shot video { width: 100%; height: 100%; object-fit: cover; display: block; opacity: 0; transition: opacity .28s ease; }
    .ga-shot img.is-loaded, .ga-shot video.is-loaded { opacity: 1; }
    /* The bin rides on the tile rather than inside it: the tile is already a
       button, and a button inside a button is not a thing. */
    .ga-wrap { position: relative; }
    .ga-del { position: absolute; right: .35rem; top: .35rem; z-index: 2; width: 1.7rem; height: 1.7rem;
        display: flex; align-items: center; justify-content: center; border-radius: .5rem; cursor: pointer;
        background: rgb(0 0 0 / .5); color: #fff; opacity: 0;
        transition: opacity .28s cubic-bezier(.22,1,.36,1), background .28s cubic-bezier(.22,1,.36,1); }
    .ga-del svg { width: .85rem; height: .85rem; }
    .ga-wrap:hover .ga-del, .ga-del:focus-visible { opacity: 1; }
    .ga-del:hover { background: #dc2626; }
    /* No hover on a touch screen, so there it simply shows. */
    @media (hover: none) { .ga-del { opacity: .85; } }
    @media (prefers-reduced-motion: reduce) { .ga-del { transition: none; } }
    .ga-shot.is-gone::after { content: 'File missing'; position: absolute; inset: 0; display: flex;
        align-items: center; justify-content: center; font-size: .66rem; font-weight: 700; color: #94a3b8; }
    .ga-kind { position: absolute; left: .35rem; top: .35rem; padding: .1rem .4rem; border-radius: 999px;
        font-size: .6rem; font-weight: 800; text-transform: uppercase; letter-spacing: .04em;
        background: rgb(17 24 39 / .65); color: #fff; }
    .ga-kind.is-drawing { background: rgb(217 119 6 / .9); }
    .ga-kind.is-map { background: rgb(37 99 235 / .9); }
    .ga-kind.is-video { background: rgb(190 24 93 / .9); }
    .ga-play { position: absolute; inset: 0; display: flex; align-items: center; justify-content: center;
        color: #fff; pointer-events: none; text-shadow: 0 1px 8px rgb(0 0 0 / .7); }
    .ga-play svg { width: 2rem; height: 2rem; }
    .ga-info { padding: .4rem .5rem .5rem; }
    .ga-it { font-size: .74rem; font-weight: 700; color: var(--color-gray-900); line-height: 1.25;
        display: -webkit-box; -webkit-line-clamp: 2; -webkit-box-orient: vertical; overflow: hidden; }
    .ga-is { font-size: .63rem; color: var(--color-gray-400); margin-top: .1rem; }
    .ga-none { text-align: center; padding: 2.5rem 1rem; color: var(--color-gray-400); font-size: .85rem; }

    html.dark .ga-item { background: #151b12; border-color: #2b3a1c; }
    html.dark .ga-it { color: #e8efe1; }
    html.dark .ga-search input, html.dark .ga-filter { background: #1c2416; border-color: #2b3a1c; color: #cdd8c0; }

    html.dark .ga-album { background: #151b12; border-color: #2b3a1c; }
    html.dark .ga-title { color: #e8efe1; }
    html.dark .ga-count { background: rgb(61 104 35 / .35); color: #a8cc7e; }
    html.dark .ga-act { background: rgb(255 255 255 / .05); color: #cdd8c0; }
</style>
@endpush

@section('content')
@include('sm.partials.module-header', ['schedule' => $schedule, 'module' => 'gallery'])
@include('sm.partials.module-note', [
    'say' => 'Everything the season produced, wherever it was taken — photos and clips from notes, days, drawings, maps and the AI — plus the albums you put together yourself. Nothing here is a copy: delete a picture where it lives and it leaves here too, and an album picture can be deleted from here because here is where it lives.',
])

@push('head')
<style>
    .tb-say { font-size: .8rem; line-height: 1.6; color: var(--color-gray-500); margin-bottom: .6rem; }
    .tb-filters { margin-bottom: .7rem; }
    .tb-grid { display: grid; grid-template-columns: repeat(auto-fill, minmax(13rem, 1fr)); gap: .7rem; }
    .tb-card { display: flex; flex-direction: column; border-radius: .8rem; overflow: hidden;
        background: var(--color-white); border: 1px solid var(--color-gray-200); text-decoration: none;
        transition: transform .28s cubic-bezier(.22,1,.36,1), box-shadow .28s cubic-bezier(.22,1,.36,1); }
    .tb-card:hover { transform: translateY(-2px); box-shadow: 0 12px 28px -18px rgb(0 0 0 / .5); }
    .tb-shot { position: relative; aspect-ratio: 16/10; background: #0b1220; }
    .tb-shot img, .tb-shot video { width: 100%; height: 100%; object-fit: cover; display: block; }
    .tb-kind { position: absolute; left: .4rem; top: .4rem; padding: .1rem .45rem; border-radius: 999px;
        font-size: .6rem; font-weight: 800; letter-spacing: .02em; text-transform: uppercase;
        background: rgb(0 0 0 / .6); color: #fff; }
    .tb-play { position: absolute; inset: 0; display: flex; align-items: center; justify-content: center;
        pointer-events: none; }
    .tb-play span { width: 2.6rem; height: 2.6rem; border-radius: 999px; background: rgb(0 0 0 / .55);
        display: flex; align-items: center; justify-content: center; color: #fff; font-size: 1rem; }
    .tb-body { padding: .55rem .65rem .65rem; display: flex; flex-direction: column; gap: .15rem; min-width: 0; }
    .tb-title { font-size: .84rem; font-weight: 800; color: var(--color-gray-900); line-height: 1.35;
        display: -webkit-box; -webkit-line-clamp: 2; -webkit-box-orient: vertical; overflow: hidden; }
    .tb-note { font-size: .72rem; color: var(--color-gray-500); line-height: 1.45;
        display: -webkit-box; -webkit-line-clamp: 2; -webkit-box-orient: vertical; overflow: hidden; }
    .tb-meta { margin-top: .15rem; font-size: .66rem; font-weight: 600; color: var(--color-gray-400); }
    html.dark .tb-card { background: #151b12; border-color: #2b3a1c; }
    html.dark .tb-title { color: #e8efe1; }
    @media (prefers-reduced-motion: reduce) { .tb-card { transition: none; } }
</style>
@endpush

{{-- Two questions, two tabs: everything the season has a picture of, and
     the ones you put together on purpose. --}}
{{-- One button, not a strip that scrolls.

     Four shelves never fitted across a phone, so the strip scrolled — and a
     tab you have to discover by dragging is a tab most people never learn
     is there. This says which shelf you are on and opens the rest, the same
     way the schedule list asks about its order. --}}
<div class="ga-pick">
    <button type="button" id="gaTabBtn" class="ga-pickbtn" aria-haspopup="dialog">
        <svg class="ga-pickico" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" d="M4 6a2 2 0 012-2h5l2 2h5a2 2 0 012 2v9a2 2 0 01-2 2H6a2 2 0 01-2-2V6z"/></svg>
        <span id="gaTabNow">All media</span>
        <span class="ga-n" id="gaTabNowN">{{ $counts['all'] }}</span>
        <svg class="ga-pickchev" fill="none" stroke="currentColor" stroke-width="2.6" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" d="M19 9l-7 7-7-7"/></svg>
    </button>
</div>

{{-- The shelves, asked for once. --}}
<div class="ga-modal hidden" id="gaTabModal" role="dialog" aria-modal="true" aria-label="Which shelf?">
    <div class="ga-modal-back" data-ga-close></div>
    <div class="ga-modal-card">
        <div class="ga-modal-head">
            <p class="font-bold text-gray-900">Which shelf?</p>
            <button type="button" class="btn-ghost rounded-full w-8 h-8 flex items-center justify-center text-gray-400 hover:text-gray-700" data-ga-close aria-label="Close">✕</button>
        </div>
        <div class="ga-modal-body" role="tablist">
            @php
                $shelves = [
                    ['all', 'All media', $counts['all'], 'Everything the season produced, wherever it was taken.'],
                    ['albums', 'Albums', count($albums), 'The ones you put together on purpose.'],
                    ['videos', 'Videos', $counts['videos'], 'Clips on their own, because you pick a video and scan photos.'],
                    ['team', 'Team box', $counts['team'], 'What the Collab Room made: recordings, whiteboards, saved maps.'],
                ];
            @endphp
            @foreach ($shelves as [$key, $label, $n, $why])
                @if ($key !== 'videos' || $counts['videos'])
                    <button type="button" class="ga-opt{{ $key === 'all' ? ' is-on' : '' }}" data-tab="{{ $key }}" role="tab" aria-selected="{{ $key === 'all' ? 'true' : 'false' }}">
                        <span class="ga-opt-txt">
                            <b>{{ $label }} <span class="ga-n">{{ $n }}</span></b>
                            <i>{{ $why }}</i>
                        </span>
                        <span class="ga-opt-tick" aria-hidden="true">
                            <svg fill="none" stroke="currentColor" stroke-width="3" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" d="M5 13l4 4L19 7"/></svg>
                        </span>
                    </button>
                @endif
            @endforeach
        </div>
    </div>
</div>
    </div>
    <div class="ga-all" id="gaAll"></div>
    <p class="ga-none hidden" id="gaAllNone">Nothing here yet. Anything taken anywhere in this schedule — a note, a day, a drawing, a map — arrives here on its own.</p>
</div>

{{-- ============================== albums ============================== --}}
<div class="ga-pane" data-pane="albums" hidden>
    <div class="ga-top">
        <button type="button" class="ga-new" id="gaNewAlbum">
            <svg fill="none" stroke="currentColor" stroke-width="2.2" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" d="M12 4v16m8-8H4"/></svg>
            <span>New album</span>
        </button>
        <p class="text-xs text-gray-500">An album is what you chose to keep together — a corner of the field, a problem you are following, the pictures a buyer asked for.</p>
    </div>

    <div id="gaAlbums"></div>

    <div class="card p-8 text-center hidden" id="gaEmpty">
        <div class="text-4xl mb-2">🖼️</div>
        <p class="font-bold text-gray-900">No albums yet</p>
        <p class="text-sm text-gray-500 mt-1">Make one for a corner of the field, a problem you are following, or anything worth keeping together.</p>
    </div>
</div>

{{-- ============================== videos ============================== --}}
<div class="ga-pane" data-pane="videos" hidden>
    <div class="ga-all" id="gaVideos"></div>
</div>

{{-- ============================= team box ============================= --}}
<div class="ga-pane" data-pane="team" hidden>
    <p class="tb-say">Everything the Collab Room made: recordings from a shared camera or a call, the whiteboard drawings, and the maps the team saved. Kept for the schedule, so anyone on it can find them again.</p>

    <div class="ga-filters tb-filters" id="tbFilters">
        <button type="button" class="ga-filter is-on" data-tb="">Everything</button>
        <button type="button" class="ga-filter" data-tb="Recording">Recordings</button>
        <button type="button" class="ga-filter" data-tb="Drawing">Drawings</button>
        <button type="button" class="ga-filter" data-tb="Map">Maps</button>
    </div>

    <div class="tb-grid" id="tbGrid"></div>
    <p class="ga-none hidden" id="tbNone">The Collab Room has not made anything yet. Recordings, whiteboard drawings and saved maps all land here.</p>
</div>

{{-- The picking bar, shown once a picture is chosen. --}}
<div class="ga-bar" id="gaBar" hidden>
    <span class="ga-bar-n" id="gaBarN">0 picked</span>
    <button type="button" id="gaMove">Move to…</button>
    <button type="button" class="ga-bar-del" id="gaDelete">Delete</button>
    <button type="button" id="gaClear">Cancel</button>
</div>

@if (! request()->boolean('partial'))
    @include('sm.partials.note-lightbox')
@endif
@endsection

@push('sheets')
<div class="sheet hidden" id="gaAlbumSheet" style="--sheet-width:28rem">
    <div class="sheet-handle"></div>
    <div class="sheet-header">
        <h3 class="sheet-title" id="gaAlbumSheetTitle">New album</h3>
        <button type="button" data-sheet-close class="btn-ghost p-2 rounded-full" aria-label="Close">✕</button>
    </div>
    <div class="sheet-body">
        <input type="hidden" id="gaAlbumId">
        <label class="form-label" for="gaAlbumTitle">Title <span class="text-red-500">*</span></label>
        <input type="text" id="gaAlbumTitle" class="form-input" maxlength="191" placeholder="e.g. Flooded corner — August">
        <label class="form-label mt-3" for="gaAlbumDesc">Description <span class="text-gray-400 font-normal">(optional)</span></label>
        <textarea id="gaAlbumDesc" class="form-input" rows="3" maxlength="2000" placeholder="What these pictures are about."></textarea>
    </div>
    <div class="sheet-footer">
        <button type="button" class="btn btn-ghost" data-sheet-close>Cancel</button>
        <button type="button" class="btn btn-primary" id="gaAlbumSave">Save album</button>
    </div>
</div>

<div class="sheet hidden" id="gaMoveSheet" style="--sheet-width:26rem">
    <div class="sheet-handle"></div>
    <div class="sheet-header">
        <h3 class="sheet-title">Move to which album?</h3>
        <button type="button" data-sheet-close class="btn-ghost p-2 rounded-full" aria-label="Close">✕</button>
    </div>
    <div class="sheet-body" id="gaMoveList" style="padding-bottom:1rem"></div>
</div>
@endpush

@push('scripts')
<script>
(() => {
    const init = () => {
        const SCHEDULE_ID = @json($schedule->id);
        const EVERYTHING = @json($everything);
        const U = {
            album: @json(route('sm.gallery.album.save')) + '?scheduleId=' + SCHEDULE_ID,
            albumDel: @json(route('sm.gallery.album.destroy')) + '?scheduleId=' + SCHEDULE_ID,
            upload: @json(route('sm.gallery.image.store')) + '?scheduleId=' + SCHEDULE_ID,
            move: @json(route('sm.gallery.image.move')) + '?scheduleId=' + SCHEDULE_ID,
            del: @json(route('sm.gallery.image.destroy')) + '?scheduleId=' + SCHEDULE_ID,
        };
        const CSRF = document.querySelector('meta[name=csrf-token]').content;
        let ALBUMS = @json($albums);
        const picked = new Set();
        const $ = (id) => document.getElementById(id);

        const esc = window.escapeHtml || ((s) => String(s ?? ''));

        function albumHtml(a) {
            const cells = a.images.map((im) => {
                // A clip in an album is a clip. Putting one in an <img> is how
                // a perfectly good recording came to report itself missing.
                const shot = im.kind === 'video'
                    ? `<video src="${esc(im.url)}" preload="metadata" playsinline muted
                        onloadeddata="this.classList.add('is-loaded')"
                        onerror="this.closest('.ga-cell')?.classList.add('is-gone'); this.remove();"></video>
                       <span class="ga-vid" aria-hidden="true">▶</span>`
                    : `<img src="${esc(im.url)}" alt="${esc(im.caption || '')}" loading="lazy"
                        onload="this.classList.add('is-loaded')"
                        onerror="this.closest('.ga-cell')?.classList.add('is-gone'); this.remove();">`;
                return `
                <div class="ga-cell" data-image="${im.id}" data-lb-type="${im.kind === 'video' ? 'video' : 'image'}" data-lb-url="${esc(im.url)}">
                    ${shot}
                    <span class="ga-pick" data-pick><svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="3.5"><path stroke-linecap="round" stroke-linejoin="round" d="M5 13l4 4L19 7"/></svg></span>
                </div>`;
            }).join('');
            return `<div class="ga-album" data-album="${a.id}">
                <div class="ga-head">
                    <span class="min-w-0 grow">
                        <span class="ga-title block">${esc(a.title)}</span>
                        ${a.description ? `<span class="ga-desc block">${esc(a.description)}</span>` : ''}
                        <span class="ga-count mt-1 inline-block">${a.images.length} ${a.images.length === 1 ? 'picture' : 'pictures'}</span>
                    </span>
                    <span class="ga-acts">
                        <button type="button" class="ga-act" data-add title="Add pictures" aria-label="Add pictures">
                            <svg fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" d="M12 4v16m8-8H4"/></svg>
                        </button>
                        <button type="button" class="ga-act" data-edit title="Rename" aria-label="Rename album">
                            <svg fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" d="M11 5H6a2 2 0 00-2 2v11a2 2 0 002 2h11a2 2 0 002-2v-5m-1.414-9.414a2 2 0 112.828 2.828L11.828 15H9v-2.828l8.586-8.586z"/></svg>
                        </button>
                        <button type="button" class="ga-act is-danger" data-del title="Delete album" aria-label="Delete album">
                            <svg fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" d="M19 7l-.9 12.1a2 2 0 01-2 1.9H7.9a2 2 0 01-2-1.9L5 7m3 0V5a2 2 0 012-2h4a2 2 0 012 2v2m-11 0h16"/></svg>
                        </button>
                    </span>
                </div>
                ${a.images.length
                    ? `<div class="ga-grid">${cells}</div>`
                    : '<p class="ga-empty">Nothing in here yet — tap + to add pictures.</p>'}
            </div>`;
        }

        function paint() {
            $('gaAlbums').innerHTML = ALBUMS.map(albumHtml).join('');
            $('gaEmpty').classList.toggle('hidden', ALBUMS.length > 0);
            paintBar();
        }

        function paintBar() {
            const n = picked.size;
            $('gaBar').hidden = n === 0;
            $('gaBarN').textContent = n + ' picked';
            document.querySelectorAll('.ga-cell').forEach((c) => {
                c.classList.toggle('is-picked', picked.has(c.getAttribute('data-image')));
            });
        }

        /* ---- albums ---- */
        function openAlbumSheet(album) {
            $('gaAlbumId').value = album ? album.id : '';
            $('gaAlbumSheetTitle').textContent = album ? 'Rename album' : 'New album';
            $('gaAlbumTitle').value = album ? album.title : '';
            $('gaAlbumDesc').value = album ? (album.description || '') : '';
            openSheet('gaAlbumSheet');
            window.smFocus($('gaAlbumTitle'), { delay: 150 });
        }
        $('gaNewAlbum').addEventListener('click', () => openAlbumSheet(null));

        $('gaAlbumSave').addEventListener('click', async (e) => {
            const btn = e.currentTarget;
            const title = $('gaAlbumTitle').value.trim();
            if (!title) { toast('Give the album a title.', 'error'); return; }
            btn.disabled = true;
            try {
                const res = await api(U.album, { method: 'POST', body: {
                    id: $('gaAlbumId').value || null, title, description: $('gaAlbumDesc').value.trim() || null,
                } });
                const row = res.data;
                const at = ALBUMS.findIndex((a) => String(a.id) === String(row.id));
                if (at >= 0) { ALBUMS[at].title = row.title; ALBUMS[at].description = row.description; }
                else ALBUMS.unshift({ id: row.id, title: row.title, description: row.description, images: [] });
                paint();
                closeSheet('gaAlbumSheet');
                toast(res.message);
            } catch (err) { toast(err.message || 'Could not save that.', 'error'); }
            finally { btn.disabled = false; }
        });

        async function deleteAlbum(album) {
            const has = album.images.length;
            const others = ALBUMS.filter((a) => a.id !== album.id);
            // An album full of pictures is not something to remove on one tap.
            const ok = await confirmAction({
                title: 'Delete “' + album.title + '”?',
                message: has
                    ? has + ' ' + (has === 1 ? 'picture is' : 'pictures are') + ' in it. They are deleted with it — move them to another album first if you want to keep them.'
                    : 'The album is empty.',
                confirmText: has ? 'Delete album and pictures' : 'Delete album',
            });
            if (!ok) return;
            try {
                const res = await api(U.albumDel, { method: 'DELETE', body: { id: album.id, withImages: has ? 1 : 0 } });
                ALBUMS = ALBUMS.filter((a) => a.id !== album.id);
                album.images.forEach((im) => picked.delete(String(im.id)));
                paint();
                toast(res.message);
            } catch (err) { toast(err.message || 'Could not remove that.', 'error'); }
        }

        /* ---- pictures ---- */
        const filePicker = document.createElement('input');
        filePicker.type = 'file';
        filePicker.accept = 'image/*';
        filePicker.multiple = true;
        filePicker.className = 'hidden';
        document.body.appendChild(filePicker);
        let uploadInto = null;

        filePicker.addEventListener('change', async () => {
            const files = [...(filePicker.files || [])];
            filePicker.value = '';
            if (!files.length || !uploadInto) return;
            const form = new FormData();
            form.append('albumId', uploadInto);
            files.forEach((f) => form.append('images[]', f));
            toast(files.length === 1 ? 'Adding the picture…' : ('Adding ' + files.length + ' pictures…'));
            try {
                const res = await fetch(U.upload, {
                    method: 'POST',
                    headers: { 'X-CSRF-TOKEN': CSRF, Accept: 'application/json' },
                    body: form, credentials: 'same-origin',
                });
                const json = await res.json();
                if (!json.success) throw new Error(json.message || 'Upload failed.');
                const album = ALBUMS.find((a) => String(a.id) === String(json.data.albumId));
                if (album) album.images.unshift(...json.data.images);
                paint();
                toast(json.message);
            } catch (err) { toast(err.message || 'Could not add those.', 'error'); }
        });

        document.addEventListener('click', async (e) => {
            const albumEl = e.target.closest('.ga-album');
            const album = albumEl ? ALBUMS.find((a) => String(a.id) === albumEl.getAttribute('data-album')) : null;

            if (e.target.closest('[data-add]') && album) { uploadInto = album.id; filePicker.click(); return; }
            if (e.target.closest('[data-edit]') && album) { openAlbumSheet(album); return; }
            if (e.target.closest('[data-del]') && album) { deleteAlbum(album); return; }

            // The tick starts picking; once picking, the whole cell toggles.
            const cell = e.target.closest('.ga-cell');
            if (!cell) return;
            const id = cell.getAttribute('data-image');
            if (e.target.closest('[data-pick]') || picked.size > 0) {
                e.preventDefault();
                e.stopPropagation();
                if (picked.has(id)) picked.delete(id); else picked.add(id);
                paintBar();
            }
        }, true);

        $('gaClear').addEventListener('click', () => { picked.clear(); paintBar(); });

        $('gaDelete').addEventListener('click', async () => {
            const n = picked.size;
            const ok = await confirmAction({
                title: 'Delete ' + n + ' ' + (n === 1 ? 'picture' : 'pictures') + '?',
                message: 'They are removed from the album and from storage.',
                confirmText: 'Delete',
            });
            if (!ok) return;
            try {
                const ids = [...picked];
                const res = await api(U.del, { method: 'DELETE', body: { ids } });
                ALBUMS.forEach((a) => { a.images = a.images.filter((im) => !picked.has(String(im.id))); });
                picked.clear();
                paint();
                toast(res.message);
            } catch (err) { toast(err.message || 'Could not delete those.', 'error'); }
        });

        $('gaMove').addEventListener('click', () => {
            const list = $('gaMoveList');
            if (ALBUMS.length < 2) {
                toast('Make another album first — there is nowhere to move them to.', 'error');
                return;
            }
            list.innerHTML = ALBUMS.map((a) => `
                <button type="button" class="w-full flex items-center gap-3 rounded-xl px-3 py-3 text-left font-semibold text-gray-700 hover:bg-gray-50" data-move-to="${a.id}">
                    <span class="grow min-w-0">${esc(a.title)}</span>
                    <span class="ga-count">${a.images.length}</span>
                </button>`).join('');
            openSheet('gaMoveSheet');
        });

        $('gaMoveList').addEventListener('click', async (e) => {
            const btn = e.target.closest('[data-move-to]');
            if (!btn) return;
            const albumId = btn.getAttribute('data-move-to');
            try {
                const ids = [...picked];
                const res = await api(U.move, { method: 'POST', body: { ids, albumId } });
                // Rebuild in place: the pictures leave one album and join another.
                const moving = [];
                ALBUMS.forEach((a) => {
                    a.images = a.images.filter((im) => {
                        if (!picked.has(String(im.id))) return true;
                        moving.push(im);
                        return false;
                    });
                });
                const target = ALBUMS.find((a) => String(a.id) === String(albumId));
                if (target) target.images.unshift(...moving);
                picked.clear();
                paint();
                closeSheet('gaMoveSheet');
                toast(res.message);
            } catch (err) { toast(err.message || 'Could not move those.', 'error'); }
        });

        /* ================================================================
         * The "all pictures" shelf: read-only, and every tile knows the way
         * back to the record it came from. A drawing opens in the pad that
         * can change it; a map opens in Maps; a photo opens where it was
         * explained. Videos get their own tab because you pick a video and
         * scan photos, and mixing them makes both harder.
         * ============================================================= */
        const KIND_LABEL = { drawing: 'Drawing', map: 'Map', video: 'Video', image: '' };
        let findText = '';
        let findSource = '';

        // What the file name says, when the payload and the file disagree.
        // Belt and braces: an item typed 'image' whose path is an .mp4 is how
        // a perfectly good clip came to report itself missing, and the file
        // name is the one thing that cannot be wrong about that.
        const VIDEO_RE = /\.(mp4|mov|webm|mkv|m4v|3gp)(\?|$)/i;

        function itemHtml(m) {
            const kind = (m.kind === 'image' && VIDEO_RE.test(m.url || '')) ? 'video' : (m.kind || 'image');
            const badge = KIND_LABEL[kind]
                ? `<span class="ga-kind is-${kind}">${KIND_LABEL[kind]}</span>` : '';
            // No poster — which is what a clip stored on a server without
            // ffmpeg looks like — so show the video's own first frame rather
            // than an empty black box.
            const shot = kind === 'video'
                ? `<div class="ga-shot">${m.posterUrl
                        ? `<img src="${esc(m.posterUrl)}" alt="" loading="lazy" onload="this.classList.add('is-loaded')">`
                        : `<video src="${esc(m.url)}" preload="metadata" playsinline muted
                             onloadeddata="this.classList.add('is-loaded')"
                             onerror="this.closest('.ga-shot')?.classList.add('is-gone'); this.remove();"></video>`}
                     <span class="ga-play"><svg viewBox="0 0 24 24" fill="currentColor"><path d="M8 5v14l11-7z"/></svg></span>${badge}</div>`
                : `<div class="ga-shot"><img src="${esc(m.url)}" alt="" loading="lazy"
                        onload="this.classList.add('is-loaded')"
                        onerror="this.closest('.ga-shot')?.classList.add('is-gone'); this.remove();">${badge}</div>`;
            const inner = `${shot}<div class="ga-info">
                    <span class="ga-it">${esc(m.title)}</span>
                    <span class="ga-is">${esc(m.source)}${m.when ? ' · ' + esc(m.when) : ''}</span>
                </div>`;
            /* Only an album picture can be deleted from here, and only
               because the Gallery is where it lives. Everything else on this
               shelf is a view of something kept in a note, a drawing or a
               map — offering to delete one of those here would either lie
               about what it did or tear a hole in the record it belongs to.
               Those still say "delete it where it lives", which is what the
               note at the top of the page has always promised. */
            const bin = m.albumImageId
                ? `<button type="button" class="ga-del" data-del-image="${m.albumImageId}"
                        title="Delete this from the album" aria-label="Delete">
                        <svg fill="none" stroke="currentColor" stroke-width="2.2" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" d="M19 7l-.9 12.1a2 2 0 01-2 1.9H7.9a2 2 0 01-2-1.9L5 7m3 0V5a2 2 0 012-2h4a2 2 0 012 2v2m-11 0h16"/></svg>
                   </button>`
                : '';
            // Photos and videos open in the lightbox; drawings and maps open
            // where they can be worked on.
            const tile = (kind === 'drawing' || kind === 'map') && m.href
                ? `<a class="ga-item" href="${esc(m.href)}">${inner}</a>`
                : `<button type="button" class="ga-item" data-lb-type="${kind === 'video' ? 'video' : 'image'}"
                        data-lb-url="${esc(m.url)}" data-lb-poster="${esc(m.posterUrl || '')}">${inner}</button>`;
            return bin ? `<div class="ga-wrap">${tile}${bin}</div>` : tile;
        }

        function paintAll() {
            const q = findText.trim().toLowerCase();
            const shown = EVERYTHING.filter((m) => m.kind !== 'video')
                .filter((m) => !findSource || m.source === findSource)
                .filter((m) => !q || (m.title + ' ' + m.source).toLowerCase().includes(q));
            $('gaAll').innerHTML = shown.map(itemHtml).join('');
            $('gaAllNone').classList.toggle('hidden', shown.length > 0);
            if (shown.length === 0 && (q || findSource)) {
                $('gaAllNone').textContent = 'Nothing matches that.';
            }
            const vids = EVERYTHING.filter((m) => m.kind === 'video');
            if ($('gaVideos')) $('gaVideos').innerHTML = vids.length
                ? vids.map(itemHtml).join('')
                : '<p class="ga-none">No videos in this schedule yet.</p>';
        }

        /* ---- Which shelf ------------------------------------------------
         * One button says where you are; the sheet is the whole list. The
         * strip that used to sit here scrolled, and a tab you only find by
         * dragging is a tab most people never learn is there. */
        (function shelfPicker() {
            const modal = $('gaTabModal');
            const btn = $('gaTabBtn');
            if (!modal || !btn) return;

            const open = () => {
                modal.classList.remove('hidden');
                void modal.offsetWidth;
                modal.classList.add('is-open');
                document.body.style.overflow = 'hidden';
                window.registerOverlay?.('gaTabs', close);
            };
            const close = () => {
                modal.classList.remove('is-open');
                document.body.style.overflow = '';
                setTimeout(() => modal.classList.add('hidden'), 260);
            };

            function show(want) {
                modal.querySelectorAll('.ga-opt').forEach((o) => {
                    const on = o.dataset.tab === want;
                    o.classList.toggle('is-on', on);
                    o.setAttribute('aria-selected', on ? 'true' : 'false');
                    if (on) {
                        // The button wears the shelf's own name and count, so
                        // it says where you are without being opened.
                        $('gaTabNow').textContent = o.querySelector('b')?.childNodes[0]?.textContent.trim() || '';
                        $('gaTabNowN').textContent = o.querySelector('b .ga-n')?.textContent || '';
                    }
                });
                document.querySelectorAll('.ga-pane').forEach((p) => {
                    p.hidden = p.getAttribute('data-pane') !== want;
                });
            }

            btn.addEventListener('click', open);
            modal.addEventListener('click', (e) => {
                if (e.target.closest('[data-ga-close]')) { close(); return; }
                const opt = e.target.closest('.ga-opt');
                if (!opt) return;
                show(opt.dataset.tab);
                close();
            });
            document.addEventListener('keydown', (e) => {
                if (e.key === 'Escape' && !modal.classList.contains('hidden')) close();
            });

            // Anything that used to click a tab can still ask for one.
            window.gaShowShelf = show;
        })();

        /* ---- Team box ---------------------------------------------------
         * Three different things — a recording, a whiteboard page, a saved
         * map — drawn the same way because people are looking for them with
         * the same question. A recording plays where it sits; the other two
         * open the thing they came from, because a picture of a map is not
         * a map. */
        const TEAM = @json($teamBox);
        let tbFilter = '';

        function paintTeam() {
            const rows = TEAM.filter((r) => !tbFilter || r.kind === tbFilter);
            const grid = $('tbGrid');
            const none = $('tbNone');
            if (!grid) return;
            none?.classList.toggle('hidden', rows.length > 0);
            grid.innerHTML = rows.map((r) => {
                const shot = r.video
                    ? '<video src="' + esc(r.url) + '"' + (r.posterUrl ? ' poster="' + esc(r.posterUrl) + '"' : '')
                        + ' preload="metadata" playsinline controls></video>'
                        + (r.posterUrl ? '' : '<span class="tb-play"><span>▶</span></span>')
                    : '<img src="' + esc(r.url) + '" alt="" loading="lazy">';
                const inner = '<span class="tb-shot"><span class="tb-kind">' + esc(r.kind) + '</span>' + shot + '</span>'
                    + '<span class="tb-body">'
                    + '<span class="tb-title">' + esc(r.title) + '</span>'
                    + (r.note ? '<span class="tb-note">' + esc(r.note) + '</span>' : '')
                    + '<span class="tb-meta">' + esc([r.by, r.when].filter(Boolean).join(' · ')) + '</span>'
                    + '</span>';
                // A recording is watched here; a drawing or a map is a way
                // back to the thing itself, which is still editable there.
                return r.href
                    ? '<a class="tb-card" href="' + esc(r.href) + '">' + inner + '</a>'
                    : '<div class="tb-card">' + inner + '</div>';
            }).join('');
        }

        $('tbFilters')?.addEventListener('click', (e) => {
            const b = e.target.closest('.ga-filter');
            if (!b) return;
            tbFilter = b.getAttribute('data-tb') || '';
            $('tbFilters').querySelectorAll('.ga-filter').forEach((x) => x.classList.toggle('is-on', x === b));
            paintTeam();
        });
        paintTeam();

        // A notification that says "saved to the Team box" should land on it.
        if (new URLSearchParams(location.search).get('tab') === 'team') {
            window.gaShowShelf?.('team');
        }

        /* Deleting from the shelf. It asks first, and it says what it is
           about to do — a picture is somebody's record of a day, and the file
           goes with the row. */
        document.addEventListener('click', async (e) => {
            const bin = e.target.closest('[data-del-image]');
            if (!bin) return;
            e.preventDefault();
            e.stopPropagation();
            const id = parseInt(bin.getAttribute('data-del-image'), 10);
            if (!id) return;

            const ok = window.confirmAction
                ? await window.confirmAction({
                    title: 'Delete this from the album?',
                    message: 'It will be removed from the Gallery and the file deleted. This cannot be undone.',
                    confirmText: 'Delete', danger: true,
                })
                : confirm('Delete this from the album?');
            if (!ok) return;

            const wrap = bin.closest('.ga-wrap');
            try {
                const res = await api(U.del, { method: 'DELETE', body: { ids: [id] } });
                // Gone from the page, and gone from the list the page redraws
                // from — otherwise a search would bring it back.
                const at = EVERYTHING.findIndex((m) => m.albumImageId === id);
                if (at >= 0) EVERYTHING.splice(at, 1);
                // The albums tab draws from its own list, which would still
                // be holding the picture when you switched to it.
                ALBUMS.forEach((a) => { a.images = a.images.filter((im) => Number(im.id) !== id); });
                if (wrap && window.animateOut) window.animateOut(wrap, () => wrap.remove());
                else wrap?.remove();
                toast(res.message || 'Deleted.');
            } catch (err) {
                toast(err.message || 'Could not delete that.', 'error');
            }
        });

        $('gaFind')?.addEventListener('input', (e) => { findText = e.target.value; paintAll(); });
        $('gaFilters')?.addEventListener('click', (e) => {
            const b = e.target.closest('.ga-filter');
            if (!b) return;
            findSource = b.getAttribute('data-src') || '';
            document.querySelectorAll('.ga-filter').forEach((x) => x.classList.toggle('is-on', x === b));
            paintAll();
        });

        paintAll();
        paint();
    };
    if (document.readyState === 'loading') document.addEventListener('DOMContentLoaded', init, { once: true });
    else init();
})();
</script>
@endpush
