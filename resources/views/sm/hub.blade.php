@extends('layouts.app')

@section('title', $schedule->title)
@section('page-title', $schedule->title)
@section('page-subtitle', 'Schedule modules')
@section('help-key', 'hub')
@section('back', route('sm.index'))

@php
    $statusBadges = [
        'draft' => 'bg-gray-100 text-gray-600',
        'setup' => 'bg-blue-100 text-blue-700',
        'generated' => 'bg-indigo-100 text-indigo-700',
        'completed' => 'bg-brand-100 text-brand-800',
        'archived' => 'bg-gray-800 text-white',
    ];

    // Module launcher cards: [label, moduleKey, count|null, svg path].
    // Each tile opens the Activities single-page shell with that module already
    // loaded (?module=key), so the module shows with the hamburger nav rather
    // than as its own standalone page.
    // The doors a worker without the note right does not get offered — the
    // owner named this list. Defined up here because the Quick buttons render
    // long before the module grid does.
    // What this worker may open, module by module, as their owner set it in
    // the Workers module. An owner standing on their own farm gets true for
    // all of them, so these read the same for everybody.
    $may = fn (string $key) => \App\Support\WorkerContext::canUseModule($key);
    $mayNotes = $may('notes');
    // The doors no worker gets, whatever their tier. Workers is the roster and
    // the logins that go with it — a worker managing the people they are one of
    // is the wrong way round. The other four are the ones the owner named in
    // the Activities toolbar; the hub is the same door in another coat, and
    // leaving it open here would only make the toolbar's absence look broken.
    $isWorker = \App\Support\WorkerContext::activeGrant() !== null;
    // The roster and its logins stay the owner's: a worker managing the
    // people they are one of is the wrong way round. Everything else below
    // is the owner's answer for this person, not a rule about workers.
    $doorOpen = [
        'workers' => ! $isWorker,
        // What the farm owns and what it cost is the owner's ledger, and a
        // worker's tick of "done" spends from it without ever opening it.
        'inventory' => ! $isWorker,
        'notes' => $mayNotes,
        // What the season is documented as, and what came off it at the end,
        // are the owner's record of their own farm — not a shelf a worker
        // reads or writes. No tier of grant opens these.
        'documentation' => ! $isWorker,
        'post-harvest' => ! $isWorker,
        'maps' => $may('maps'),
        'draw' => $may('draw'),
        'ai' => $may('ai'),
    ];
    $moduleCards = [
        ['Settings', 'settings', null,
            'M10.325 4.317c.426-1.756 2.924-1.756 3.35 0a1.724 1.724 0 002.573 1.066c1.543-.94 3.31.826 2.37 2.37a1.724 1.724 0 001.065 2.572c1.756.426 1.756 2.924 0 3.35a1.724 1.724 0 00-1.066 2.573c.94 1.543-.826 3.31-2.37 2.37a1.724 1.724 0 00-2.572 1.065c-.426 1.756-2.924 1.756-3.35 0a1.724 1.724 0 00-2.573-1.066c-1.543.94-3.31-.826-2.37-2.37a1.724 1.724 0 00-1.065-2.572c-1.756-.426-1.756-2.924 0-3.35a1.724 1.724 0 001.066-2.573c-.94-1.543.826-3.31 2.37-2.37.996.608 2.296.07 2.572-1.065zM15 12a3 3 0 11-6 0 3 3 0 016 0z'],
        ['Lots', 'lots', (int) $schedule->lots_count,
            'M9 20l-5-2V6l5 2m0 12l6-2m-6 2V8m6 10l5 2V8l-5-2m0 12V6M9 8l6-2'],
        ['Workers', 'workers', (int) $schedule->workers_count,
            'M17 20h5v-1a4 4 0 00-4-4h-1M9 11a4 4 0 100-8 4 4 0 000 8zm8 0a3 3 0 100-6M2 20v-1a5 5 0 015-5h4a5 5 0 015 5v1H2z'],
        ['Inventory', 'inventory', null,
            'M20 7l-8-4-8 4m16 0l-8 4m8-4v10l-8 4m0-10L4 7m8 4v10M4 7v10l8 4'],
        ['Documentation', 'documentation', (int) $documentationCount,
            'M9 12h6m-6 4h6m2 5H7a2 2 0 01-2-2V5a2 2 0 012-2h5.586a1 1 0 01.707.293l5.414 5.414a1 1 0 01.293.707V19a2 2 0 01-2 2z'],
        ['Observations', 'post-harvest', (int) $postHarvestCount,
            'M3 13.125C3 12.504 3.504 12 4.125 12h2.25c.621 0 1.125.504 1.125 1.125v6.75C7.5 20.496 6.996 21 6.375 21h-2.25A1.125 1.125 0 013 19.875v-6.75zM9.75 8.625c0-.621.504-1.125 1.125-1.125h2.25c.621 0 1.125.504 1.125 1.125v11.25c0 .621-.504 1.125-1.125 1.125h-2.25a1.125 1.125 0 01-1.125-1.125V8.625zM16.5 4.125c0-.621.504-1.125 1.125-1.125h2.25C20.496 3 21 3.504 21 4.125v15.75c0 .621-.504 1.125-1.125 1.125h-2.25a1.125 1.125 0 01-1.125-1.125V4.125z'],
        // Pencil-over-page, not the document glyph: Documentation two rows up
        // wears that one, and two tiles with the same icon read as one.
        ['Notes', 'notes', (int) $notesCount,
            'M11 5H6a2 2 0 00-2 2v11a2 2 0 002 2h11a2 2 0 002-2v-5m-1.414-9.414a2 2 0 112.828 2.828L11.828 15H9v-2.828l8.586-8.586z'],
        // Folded map, not a pin: the module is for drawing the ground, and a
        // pin reads as "where am I" rather than "plan the field".
        ['Weather', 'weather', null,
            'M3 15a4 4 0 004 4h9a5 5 0 10-.9-9.95A5.5 5.5 0 006.5 8 4.5 4.5 0 003 15z'],
        // What the crop is doing today, which is the question the day counts
        // were always standing in for.
        ['Growth Stages', 'growth', null,
            'M12 21c0-4 1-7 4-9M12 21c0-5-2-8-6-9m6 9V8m0 0c0-2.5 1.5-4 4-4 0 2.5-1.5 4-4 4zm0 0C12 5.5 10.5 4 6.5 4c0 2.5 1.5 4 5.5 4z'],
        // Every picture and video the season produced, plus the albums a
        // grower puts together on purpose. It was two modules and nobody
        // could remember which one a photo had gone into.
        ['Gallery', 'gallery', (int) $mediaCount,
            'M4 6a2 2 0 012-2h5l2 2h5a2 2 0 012 2v9a2 2 0 01-2 2H6a2 2 0 01-2-2V6zM8 14l2.5-3 2 2.5L15 10l3 4'],
        ['Maps', 'maps', null,
            'M9 20l-5-2V6l5 2m0 12l6-2m-6 2V8m6 10l5 2V8l-5-2m0 12V6M9 8l6-2M15 11.5a2 2 0 11-4 0 2 2 0 014 0z'],
        // A pencil on a sheet, distinct from the Notes pencil-over-page: this
        // one is the sheet itself, because the drawing is the whole point.
        ['Draw', 'draw', null,
            'M4 20l4-1L20 7a2 2 0 00-3-3L5 16l-1 4zM14 6l4 4'],
        // "Chat Anee": on this shelf the tile beside it is a drawing
        // pad and the one above it is a map, and "AI" named the machinery
        // rather than the person you talk to. The person is Anee.
        ['Chat Anee', 'ai', null,
            'M12 3v2m0 0a7 7 0 017 7v3a3 3 0 01-3 3H8a3 3 0 01-3-3v-3a7 7 0 017-7zM9 12h.01M15 12h.01M9.5 17h5'],
    ];
@endphp

@section('content')

    {{-- The season's own card. It used to be a plain white box with the
         title, some badges and a button that said "Mark completed" — which
         reads like a checkbox rather than the end of a season. It now looks
         like the top of something, says what state the season is in on its
         own line, and names the crops growing under it (which live on the
         lots now, so a season with two crops says both). --}}
    <div class="sched-head mb-4">
        <div class="sched-head-top">
            <div class="min-w-0 grow">
                <div class="flex items-start gap-2">
                    <h2 class="sched-title" id="schedTitleText">{{ $schedule->title }}</h2>
                    <button type="button" id="schedRenameBtn" class="sched-pen" title="Rename this schedule" aria-label="Edit name and description">
                        <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path stroke-linecap="round" stroke-linejoin="round" d="M11 5H6a2 2 0 00-2 2v11a2 2 0 002 2h11a2 2 0 002-2v-5m-1.414-9.414a2 2 0 112.828 2.828L11.828 15H9v-2.828l8.586-8.586z"/></svg>
                    </button>
                </div>
                <p class="sched-desc" id="schedDescText">{{ $schedule->description ?: 'No description yet — tap the pencil to add one.' }}</p>
            </div>
            <span class="sched-state sched-state-{{ $schedule->status }}">{{ ucfirst($schedule->status) }}</span>
        </div>

        <div class="sched-facts">
            @php
                $cropLabels = $schedule->lots->pluck('crop')->filter()
                    ->map(fn ($c) => \App\Support\CropStages::label($c))->filter()->unique()->values();
            @endphp
            @foreach ($cropLabels as $c)
                <span class="sched-fact"><span class="sf-emoji">{{ \App\Support\CropStages::icon($c) }}</span>{{ $c }}</span>
            @endforeach
            <span class="sched-fact"><span class="sf-emoji">📐</span>{{ $schedule->lots_count }} {{ \Illuminate\Support\Str::plural('lot', $schedule->lots_count) }}</span>
            <span class="sched-fact"><span class="sf-emoji">👷</span>{{ $schedule->workers_count }} {{ \Illuminate\Support\Str::plural('worker', $schedule->workers_count) }}</span>
            <span class="sched-fact"><span class="sf-emoji">🗓️</span>{{ $schedule->dayType ?: 'DAS' }} counting</span>
            <span class="sched-fact"><span class="sf-emoji">🌱</span>Started {{ $schedule->created_at->format('M j, Y') }}</span>
        </div>

        {{-- The share-to-community switch used to stand here. Sharing a whole
             plan into the community is retired — the community talks in posts
             and discussions now — so the toggle and the "View in Community"
             link it turned on have both gone. The row keeps its shape by
             pushing the one remaining control to the right. --}}
        <div class="sched-foot sched-foot-end">
            <div class="flex items-center gap-3 shrink-0">
                {{-- "Mark completed" sounded like ticking a task off. What the
                     button does is close the season and lock it, and the way
                     back is Reopen — so it says that. --}}
                {{-- Closing a season drifts like every other filled button in
                     the app — in the green this one has always been, whatever
                     the utility that paints it is called. Reopening stays a
                     plain white button: undoing is not an event. --}}
                <button type="button" id="statusToggleBtn" data-locked="{{ $schedule->isLocked() ? 1 : 0 }}"
                        class="btn btn-sm {{ $schedule->isLocked() ? 'btn-white' : 'btn-accent sweep-fill sweep-green' }}"
                        style="--sw-t:{{ 9 + ($schedule->id % 7) }}s;--sw-d:-{{ $schedule->id % 11 }}s">
                    {{ $schedule->isLocked() ? 'Reopen this season' : 'Close this season' }}
                </button>
            </div>
        </div>
    </div>

    {{-- Rename / describe. Small on purpose: two fields and a save. --}}
    <div class="sheet hidden" id="schedRenameSheet" style="--sheet-width:28rem">
        <div class="sheet-handle"></div>
        <div class="sheet-header">
            <h3 class="sheet-title">Name and description</h3>
            <button type="button" data-sheet-close class="btn-ghost p-2 rounded-full -mr-1" aria-label="Close">
                <svg class="w-5 h-5" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" d="M6 6l12 12M18 6L6 18"/></svg>
            </button>
        </div>
        <div class="sheet-body">
            <label class="form-label" for="schedTitleInput">Title <span class="text-red-500">*</span></label>
            <input type="text" id="schedTitleInput" class="form-input" maxlength="255" value="{{ $schedule->title }}">
            <label class="form-label mt-3" for="schedDescInput">Description <span class="text-gray-400 font-normal">(optional)</span></label>
            <textarea id="schedDescInput" class="form-input" rows="4" maxlength="5000">{{ $schedule->description }}</textarea>
        </div>
        <div class="sheet-footer">
            <button type="button" class="btn btn-ghost" data-sheet-close>Cancel</button>
            <button type="button" class="btn btn-primary" id="schedRenameSave">Save</button>
        </div>
    </div>

    <style>
        /* A card, not a poster. The season's name has to be the most legible
           thing on the hub, and a full-bleed green panel fought everything
           placed on it — a light button vanished into it, a dark one looked
           like a hole. The colour is a band across the top instead, and the
           card itself is the same surface as every other card on the page, so
           text and buttons keep the contrast they were designed with. */
        .sched-head { position: relative; border-radius: 1.1rem; overflow: hidden;
            background: var(--color-white, #fff);
            border: 1px solid var(--color-gray-200, #e5e7eb);
            box-shadow: 0 12px 32px -26px rgb(15 23 42 / .5); }
        .sched-head::before { content: ''; position: absolute; inset: 0 0 auto 0; height: .35rem;
            background: linear-gradient(90deg, #3d6823, #6b9f3d 55%, #a8cc7e);
            /* Drifts on the shared gradSweep tide (layout); the oversize is
               what gives the band room to move. */
            background-size: 220% 100%;
            animation: gradSweep 12s ease-in-out infinite alternate; }
        @media (prefers-reduced-motion: reduce) { .sched-head::before { animation: none; } }
        .sched-head-top { display: flex; align-items: flex-start; gap: .75rem; padding: 1.05rem 1.1rem .35rem; }
        .sched-title { font-size: 1.3rem; font-weight: 800; line-height: 1.2;
            color: var(--color-gray-900, #111827); word-break: break-word; }
        /* Centred on the title's FIRST LINE, not hung below it: the line box
           is 1.56rem and the button is 2rem, so it lifts by half the
           difference. A long title wrapping to two lines keeps the pencil
           beside the first, which is where the name starts. */
        .sched-pen { flex: 0 0 auto; width: 2rem; height: 2rem; border-radius: 999px; margin-top: -.22rem;
            display: inline-flex; align-items: center; justify-content: center;
            background: #f3f8ec; color: #3d6823; cursor: pointer;
            transition: background .25s ease, color .25s ease; }
        .sched-pen:hover { background: #4a7c2a; color: #fff; }
        .sched-pen svg { width: 1rem; height: 1rem; }
        .sched-desc { font-size: .85rem; line-height: 1.5; color: var(--color-gray-500, #6b7280); margin-top: .3rem; }
        .sched-state { flex: 0 0 auto; font-size: .64rem; font-weight: 800; text-transform: uppercase;
            letter-spacing: .05em; padding: .2rem .55rem; border-radius: 999px;
            background: #e4efd4; color: #3d6823; }
        .sched-state-completed { background: #fde68a; color: #78350f; }
        .sched-facts { display: flex; flex-wrap: wrap; gap: .35rem; padding: .65rem 1.1rem 0; }
        .sched-fact { display: inline-flex; align-items: center; gap: .3rem; font-size: .72rem; font-weight: 700;
            padding: .24rem .6rem; border-radius: 999px;
            background: var(--color-gray-50, #f9fafb); border: 1px solid var(--color-gray-200, #e5e7eb);
            color: var(--color-gray-700, #374151); }
        .sf-emoji { font-size: .85rem; line-height: 1; }
        /* With the share switch gone the row has one child; space-between
           would leave it hard against the left edge of a full-width bar. */
        .sched-foot-end { justify-content: flex-end !important; }
        .sched-foot { display: flex; align-items: center; justify-content: space-between; gap: .75rem;
            flex-wrap: wrap; margin-top: .9rem; padding: .7rem 1.1rem;
            background: var(--color-gray-50, #f9fafb);
            border-top: 1px solid var(--color-gray-100, #f3f4f6); }
        /* The one button on this card, and it closes a season — it should read
           as deliberate in both themes rather than blending into the strip. */
        /* background-COLOR, not the shorthand: the shorthand resets
           background-image, and this button carries a drifting gradient over
           its fill (sweep-fill, in the layout). The colour underneath still
           does its job for anything that is not sweeping. */
        .sched-foot .btn-primary, .sched-foot .btn-accent {
            background-color: #4a7c2a; border-color: #4a7c2a; color: #fff;
        }
        .sched-foot .btn-primary:hover, .sched-foot .btn-accent:hover { background-color: #3d6823; border-color: #3d6823; }

        html.dark .sched-head { background: #151b12; border-color: #2b3a1c; }
        html.dark .sched-title { color: #e8efe1; }
        html.dark .sched-desc { color: #9fb08e; }
        html.dark .sched-state { background: rgb(107 159 61 / .22); color: #a8cc7e; }
        html.dark .sched-fact { background: rgb(255 255 255 / .04); border-color: #2b3a1c; color: #cdd8c0; }
        html.dark .sched-foot { background: rgb(255 255 255 / .03); border-top-color: #2b3a1c; }
        html.dark .sched-pen { background: rgb(107 159 61 / .2); color: #a8cc7e; }
        html.dark .sched-pen:hover { background: #4a7c2a; color: #fff; }
        html.dark .sched-foot .btn-primary, html.dark .sched-foot .btn-accent {
            /* White, now the fill is a gradient: dark ink was legible on one
               flat bright green and is not on the deep end of a sweep. */
            background-color: #6b9f3d; border-color: #6b9f3d; color: #fff;
        }
        html.dark .sched-foot .btn-primary:hover, html.dark .sched-foot .btn-accent:hover {
            background: #86b556; border-color: #86b556;
        }
        @media (prefers-reduced-motion: reduce) { .sched-pen { transition: none; } }
    </style>

    @include('sm.partials.tip-of-day', ['tip' => $tip ?? null, 'aiHref' => route('sm.ai', ['id' => $schedule->id])])

    {{-- Featured: Activities (2/3) + Quick Capture (1/3) as matched CTA tiles --}}
    <style>
        /* The two doors off the hub, in the page's own language: a white card
           with a tinted icon badge and a colour that belongs to it, rather
           than two slabs of flat paint that fought the cards around them. */
        .cta-tile { position: relative; overflow: hidden; cursor: pointer; text-decoration: none;
            background: var(--color-white); border: 1px solid var(--color-gray-200); color: var(--color-gray-900);
            transition: transform .28s cubic-bezier(.22,1,.36,1), box-shadow .28s cubic-bezier(.22,1,.36,1),
                border-color .28s cubic-bezier(.22,1,.36,1); }
        .cta-tile::before { content: ''; position: absolute; inset: 0 auto 0 0; width: .3rem;
            /* A quiet glint sliding along each door's own accent — the shared
               gradSweep tide, vertical this time. */
            background: linear-gradient(180deg,
                var(--cta-accent, #6b9f3d),
                color-mix(in srgb, var(--cta-accent, #6b9f3d) 62%, #fff) 50%,
                var(--cta-accent, #6b9f3d));
            background-size: 100% 220%;
            animation: gradSweep 10s ease-in-out infinite alternate; }
        @media (prefers-reduced-motion: reduce) { .cta-tile::before { animation: none; } }
        .cta-tile:hover { transform: translateY(-2px); box-shadow: 0 14px 30px -20px rgb(0 0 0 / .5); }
        .cta-tile:active { transform: translateY(0); }
        .cta-tile .cta-chip { color: #fff; background: var(--cta-accent, #6b9f3d);
            transition: transform .28s cubic-bezier(.22,1,.36,1); }
        .cta-tile:hover .cta-chip { transform: scale(1.06); }
        .cta-tile .cta-sub { color: var(--color-gray-500); }
        .cta-tile .cta-arrow { color: var(--cta-accent, #6b9f3d); opacity: .75; flex-shrink: 0;
            transition: transform .28s cubic-bezier(.22,1,.36,1); }
        .cta-tile:hover .cta-arrow { transform: translateX(4px); }

        /* Activities — the season's own green. */
        .act-cta { --cta-accent: #4a7c2a; }
        .act-cta:hover { border-color: #cfe3b8; }
        /* Quick Capture — amber, the colour it wears everywhere else. */
        .qc-cta { --cta-accent: #d97706; }
        .qc-cta:hover { border-color: #f0dcae; }
        /* Quick Record — the red it wears on the schedule list. */
        .qr-cta { --cta-accent: #b91c1c; }
        .qr-cta:hover { border-color: #f3c4c4; }

        html.dark .cta-tile { background: #151b12; border-color: #2b3a1c; color: #e8efe1; }
        html.dark .cta-tile .cta-sub { color: #9fb08e; }
        html.dark .act-cta { --cta-accent: #6b9f3d; }
        html.dark .qc-cta { --cta-accent: #e0912e; }
        html.dark .qr-cta { --cta-accent: #e06a6a; }
        @media (prefers-reduced-motion: reduce) {
            .cta-tile, .cta-tile .cta-chip, .cta-tile .cta-arrow { transition: none; }
        }

        @media (max-width: 767px) {
            /* Phones read the hub as a list of places to go, and a column
               tile spends most of its height on air under a big icon: eleven
               of them ran past 1,400px, so Reports and the danger zone were a
               long thumb-drag away. Each tile becomes a row — icon, name,
               count — which halves the grid without dropping anything.
               `display: contents` dissolves the icon/badge wrapper so all
               three become items of that row; the markup is untouched. */
            .hub-grid { gap: .5rem; }
            .hub-grid > * > div {
                flex-direction: row; align-items: center;
                gap: .6rem; padding: .7rem .75rem;
            }
            /* The wrapper, not the icon — both carry `flex`, and dissolving
               the icon would un-centre the glyph inside it. */
            .hub-grid > * > div > .justify-between { display: contents; }
            .hub-grid > * > div > span {
                order: 1; flex: 1 1 auto; min-width: 0; line-height: 1.15;
                font-size: .84rem;
                white-space: nowrap; overflow: hidden; text-overflow: ellipsis;
            }
            .hub-grid .badge { order: 2; margin-left: auto; flex-shrink: 0; }
            /* A grey badge is a zero — it says nothing the tile does not, and
               on a phone it was costing the name its last 30px: "Documentation"
               came out clipped mid-word. Counts that exist still show. */
            .hub-grid .badge-gray { display: none; }
            .hub-grid .w-11 { width: 2.3rem; height: 2.3rem; border-radius: .65rem; }
            .hub-grid .w-11 svg { width: 1.2rem; height: 1.2rem; }

            /* Both doors are the same row on a phone — icon, name, arrow. */
            .cta-tile { padding: .85rem .95rem; gap: .75rem; }
            .cta-tile .cta-chip { width: 2.6rem; height: 2.6rem; }
            .cta-tile .cta-chip svg { width: 1.5rem; height: 1.5rem; }
            .cta-tile .cta-title { font-size: 1rem; }
        }
    </style>

    {{-- Four columns, not three: Activities keeps half the row and the two
         quick doors take a quarter each. Both of them are the same act — you
         are standing in front of something and want it kept — so they belong
         side by side rather than one of them being somewhere else. --}}
    {{-- One column for a worker rather than four: the two quick doors beside
         Activities are not drawn for them, and a half-width card floating
         against 550px of nothing reads as a card that failed to load rather
         than one that is on its own. Done by collapsing the row instead of
         widening the card, because col-span-4 is not a class this build's CSS
         carries and the card would silently fall back to a single column. --}}
    @php
        $quickDoors = ($may('camera') ? 1 : 0) + ($may('video') ? 1 : 0);
        // Written out, not composed: Tailwind reads this file to decide what
        // to emit, so a class name assembled at runtime is a class that does
        // not exist. Two columns on a tablet, four when there is room.
        $actSpan = match ($quickDoors) {
            2 => 'sm:col-span-2 xl:col-span-2',
            1 => 'sm:col-span-2 xl:col-span-3',
            default => '',
        };
    @endphp
    <div class="grid grid-cols-1 {{ $quickDoors ? 'sm:grid-cols-2 xl:grid-cols-4' : '' }} gap-3 mb-4">
        {{-- Activities (2/4) --}}
        <a href="{{ route('sm.activities', ['id' => $schedule->id]) }}" data-nav-loader
            class="cta-tile act-cta {{ $actSpan }} rounded-2xl p-5 flex items-center gap-4">
            <span class="cta-chip w-12 h-12 rounded-xl flex items-center justify-center shrink-0">
                <svg class="w-7 h-7" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" d="M13 10V3L4 14h7v7l9-11h-7z"/></svg>
            </span>
            <span class="min-w-0 grow">
                <span class="flex items-center gap-2 flex-wrap">
                    <span class="cta-title text-lg font-bold leading-tight">Activities</span>
                    <span class="badge badge-yellow">{{ $schedule->activities_count }}</span>
                </span>
                <span class="cta-sub block text-sm leading-snug mt-0.5">The heart of your schedule — the day-by-day timeline.</span>
            </span>
            <svg class="cta-arrow w-6 h-6" fill="none" stroke="currentColor" stroke-width="2.5" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" d="M9 5l7 7-7 7"/></svg>
        </a>

        {{-- Quick Capture (1/4) — the same shape as its neighbours, so the
             three read as three doors rather than three kinds of thing. --}}
        @if ($may('camera'))
        <button type="button" id="quickCaptureBtn"
            class="cta-tile qc-cta rounded-2xl p-5 flex items-center gap-4 text-left">
            <span class="cta-chip w-12 h-12 rounded-xl flex items-center justify-center shrink-0">
                <svg class="w-7 h-7" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" d="M3 9a2 2 0 012-2h.93a2 2 0 001.66-.9l.82-1.2A2 2 0 0110.07 4h3.86a2 2 0 011.66.9l.82 1.2a2 2 0 001.66.9H19a2 2 0 012 2v9a2 2 0 01-2 2H5a2 2 0 01-2-2V9z"/><path stroke-linecap="round" stroke-linejoin="round" d="M15 13a3 3 0 11-6 0 3 3 0 016 0z"/></svg>
            </span>
            <span class="min-w-0 grow">
                <span class="cta-title block text-lg font-bold leading-tight">Quick Capture</span>
                <span class="cta-sub block text-sm leading-snug mt-0.5">Snap a photo, file it in seconds.</span>
            </span>
            <svg class="cta-arrow w-6 h-6" fill="none" stroke="currentColor" stroke-width="2.5" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" d="M9 5l7 7-7 7"/></svg>
        </button>
        @endif

        {{-- Quick Record (1/4). Some of what a field does is only legible
             moving — a pump that sounds wrong, water finding a path — and
             the Hub is where somebody standing in that field arrives. --}}
        @if ($may('video'))
        <button type="button" id="quickRecordBtn"
            class="cta-tile qr-cta rounded-2xl p-5 flex items-center gap-4 text-left">
            <span class="cta-chip w-12 h-12 rounded-xl flex items-center justify-center shrink-0">
                <svg class="w-7 h-7" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" d="M15 10l4.55-2.28A1 1 0 0121 8.62v6.76a1 1 0 01-1.45.9L15 14M5 6h8a2 2 0 012 2v8a2 2 0 01-2 2H5a2 2 0 01-2-2V8a2 2 0 012-2z"/></svg>
            </span>
            <span class="min-w-0 grow">
                <span class="cta-title block text-lg font-bold leading-tight">Quick Record</span>
                <span class="cta-sub block text-sm leading-snug mt-0.5">Film it when a picture will not do.</span>
            </span>
            <svg class="cta-arrow w-6 h-6" fill="none" stroke="currentColor" stroke-width="2.5" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" d="M9 5l7 7-7 7"/></svg>
        </button>
        @endif
    </div>

    {{-- Collab Room, Share and Reports live as square tiles in the grid below. --}}
    @if (\App\Support\ScheduleTeam::hasTeam($schedule))
        @include('sm.partials.collab-enter-modal', ['schedule' => $schedule])
    @endif

    {{-- Module grid + the team/share/report actions, all as matched square tiles. --}}
    <div class="grid grid-cols-2 md:grid-cols-4 gap-3 mb-6 stagger-children hub-grid">
        @foreach ($moduleCards as [$label, $moduleKey, $count, $iconPath])
            @continue(array_key_exists($moduleKey, $doorOpen) && ! $doorOpen[$moduleKey])
            <a href="{{ route('sm.activities', ['id' => $schedule->id, 'module' => $moduleKey]) }}" data-nav-loader class="card card-hover block">
                <div class="p-4 flex flex-col gap-3">
                    <div class="flex items-start justify-between">
                        <div class="w-11 h-11 rounded-xl bg-brand-50 flex items-center justify-center">
                            <svg class="w-6 h-6 text-brand-700" fill="none" stroke="currentColor" stroke-width="1.8" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" d="{{ $iconPath }}"/></svg>
                        </div>
                        @if ($count !== null)
                            <span class="badge {{ $count > 0 ? 'badge-green' : 'badge-gray' }}">{{ $count }}</span>
                        @endif
                    </div>
                    <span class="font-bold text-gray-900 text-sm">{{ $label }}</span>
                </div>
            </a>
        @endforeach

        {{-- Collab Room — right after AI Technician --}}
        @if (\App\Support\ScheduleTeam::hasTeam($schedule))
            {{-- No data-nav-loader: data-collab-open intercepts this click to
                 ask about the team first, so the tap does not always leave. --}}
            <a href="{{ route('sm.collab', ['id' => $schedule->id]) }}" data-collab-open class="card card-hover block">
                <div class="p-4 flex flex-col gap-3">
                    <div class="w-11 h-11 rounded-xl bg-brand-50 flex items-center justify-center">
                        <svg class="w-6 h-6 text-brand-700" fill="none" stroke="currentColor" stroke-width="1.8" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" d="M3 4h18M5 4v11a2 2 0 002 2h10a2 2 0 002-2V4M8 9h8M8 12h5M12 17v4m-3 0h6"/></svg>
                    </div>
                    <span class="font-bold text-gray-900 text-sm">Collab Room</span>
                </div>
            </a>
        @endif

        {{-- Share this schedule --}}
        <button type="button" id="shareScheduleBtn" class="card card-hover block w-full text-left">
            <div class="p-4 flex flex-col gap-3">
                <div class="w-11 h-11 rounded-xl bg-brand-50 flex items-center justify-center">
                    <svg class="w-6 h-6 text-brand-700" fill="none" stroke="currentColor" stroke-width="1.8" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" d="M8.684 13.342C8.886 12.938 9 12.482 9 12c0-.482-.114-.938-.316-1.342m0 2.684a3 3 0 110-2.684m0 2.684l6.632 3.316m-6.632-6l6.632-3.316m0 0a3 3 0 105.367-2.684 3 3 0 00-5.367 2.684zm0 9.316a3 3 0 105.368 2.684 3 3 0 00-5.368-2.684z"/></svg>
                </div>
                <span class="font-bold text-gray-900 text-sm">Share</span>
            </div>
        </button>

        {{-- Reports --}}
        @if ($may('reports'))
        <a href="{{ route('sm.reports', ['id' => $schedule->id]) }}" data-nav-loader class="card card-hover block">
            <div class="p-4 flex flex-col gap-3">
                <div class="w-11 h-11 rounded-xl bg-brand-50 flex items-center justify-center">
                    <svg class="w-6 h-6 text-brand-700" fill="none" stroke="currentColor" stroke-width="1.8" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" d="M11 3.055A9 9 0 1020.945 13H12a1 1 0 01-1-1V3.055zM15 3.936A9.02 9.02 0 0120.064 9H15V3.936z"/></svg>
                </div>
                <span class="font-bold text-gray-900 text-sm">Reports</span>
            </div>
        </a>
        @endif
    </div>

    {{-- Danger zone. Ending a season belongs to whoever owns it: sm.destroy
         has always refused a worker, so the card was a red button that could
         only ever answer no. --}}
    @if (! $isWorker)
    <style>
        /* A red band over the one card that can end a season: the same slow
           gradSweep tide as the green lines above, wearing warning colours. */
        .danger-head { position: relative; overflow: hidden; }
        .danger-head::before { content: ''; position: absolute; inset: 0 0 auto 0; height: .25rem;
            background: linear-gradient(90deg, #b91c1c, #f87171 55%, #b91c1c);
            background-size: 220% 100%;
            animation: gradSweep 10s ease-in-out infinite alternate; }
        @media (prefers-reduced-motion: reduce) { .danger-head::before { animation: none; } }
    </style>
    <div class="card border-red-100 danger-head">
        <div class="card-body">
            <h3 class="font-bold text-red-700 mb-1">Danger zone</h3>
            <p class="text-sm text-gray-500 mb-4">Deleting hides this schedule and all its modules from your account.</p>
            <button type="button" id="deleteScheduleBtn" class="btn btn-danger-outline">
                <svg class="w-5 h-5" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" d="M3 6h18M19 6v14a2 2 0 01-2 2H7a2 2 0 01-2-2V6M8 6V4a2 2 0 012-2h4a2 2 0 012 2v2M10 11v6M14 11v6"/></svg>
                Delete schedule
            </button>
        </div>
    </div>
    @endif

    @include('sm.partials.share-sheet', ['schedule' => $schedule])
    {{-- The camera and the recorder come with their own sheets and pickers;
         with a door gone there is nothing left to open its sheet. --}}
    @if ($may('camera'))
    @include('sm.partials.quick-capture', ['fixedScheduleId' => $schedule->id])
    @endif
    {{-- Both quick doors are fixed to this schedule, so neither asks which
         one you meant. Quick Record borrows the shared recorder. --}}
    @if ($may('video'))
    @include('sm.partials.quick-record', ['fixedScheduleId' => $schedule->id, 'allSchedules' => collect()])
    @endif
    @include('community.partials.video-js')
    {{-- The bubble is the AI technician wearing a different hat: removing its
         tile and leaving the bubble floating over it would only teach a worker
         that the tile was the broken half. --}}
    @if ($may('ai'))
    @include('sm.partials.ai-float', ['schedule' => $schedule])
    @endif
    {{-- Team chat + whiteboard now live in the Collab Room. --}}
@endsection

@push('scripts')
<script>
document.addEventListener('DOMContentLoaded', () => {
    document.getElementById('deleteScheduleBtn')?.addEventListener('click', async () => {
        const ok = await confirmAction({
            title: 'Delete schedule?',
            message: @json('"' . $schedule->title . '" and its modules will be hidden from your account.'),
            detail: 'Lots, workers and activities tied to it are preserved but no longer visible.',
            confirmText: 'Delete',
        });
        if (!ok) return;

        try {
            const res = await api(`{{ route('sm.destroy') }}?id={{ $schedule->id }}`, { method: 'DELETE' });
            toast(res.message);
            window.location.href = @json(route('sm.index'));
        } catch (err) {
            toast(err.message, 'error');
        }
    });

    /* Rename and describe, from the pencil on the header card. */
    document.getElementById('schedRenameBtn')?.addEventListener('click', () => {
        openSheet('schedRenameSheet');
        window.smFocus('schedTitleInput', { delay: 150 });
    });
    document.getElementById('schedRenameSave')?.addEventListener('click', async (e) => {
        const btn = e.currentTarget;
        const title = document.getElementById('schedTitleInput').value.trim();
        const description = document.getElementById('schedDescInput').value.trim();
        if (!title) { toast('Give the schedule a title.', 'error'); return; }
        btn.disabled = true;
        try {
            // The endpoint reads the schedule from the query, not the body.
            const res = await api(@json(route('sm.update')) + '?id={{ $schedule->id }}', {
                method: 'PUT',
                body: { title, description: description || null },
            });
            // No reload: the two places the name shows are right here.
            document.getElementById('schedTitleText').textContent = title;
            document.getElementById('schedDescText').textContent =
                description || 'No description yet — tap the pencil to add one.';
            document.querySelectorAll('[data-page-subtitle]').forEach((el) => { el.textContent = title; });
            closeSheet('schedRenameSheet');
            toast(res.message || 'Saved.');
        } catch (err) {
            toast(err.message || 'Could not save that.', 'error');
        } finally { btn.disabled = false; }
    });

    // Close the season (lock) / reopen it.
    document.getElementById('statusToggleBtn')?.addEventListener('click', async (e) => {
        const btn = e.currentTarget;
        const locked = btn.getAttribute('data-locked') === '1';
        if (!locked) {
            const ok = await confirmAction({
                title: 'Close this season?',
                message: 'It becomes read-only — nothing can be added or changed until you reopen it. Everything is kept.',
                confirmText: 'Close the season',
            });
            if (!ok) return;
        }
        btn.disabled = true;
        try {
            const res = await api(@json(route('sm.status')), { method: 'POST', body: { id: {{ $schedule->id }}, status: locked ? 'setup' : 'completed' } });
            toast(res.message);
            setTimeout(() => window.location.reload(), 500);
        } catch (err) { toast(err.message || 'Could not update.', 'error'); btn.disabled = false; }
    });
});
</script>
@endpush
