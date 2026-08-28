@extends(request()->boolean('partial') ? 'layouts.partial' : 'layouts.app')

@section('title', 'Growth Stages — ' . $schedule->title)
@section('page-title', 'Growth Stages')
@section('page-subtitle', $schedule->title)
@section('help-key', 'growth')
@section('back', route('sm.hub', ['id' => $schedule->id]))

@push('head')
<style>
    /* One card per lot, and each card reads top to bottom as an answer:
       where the crop is, what that means, what to do, what to watch, and
       where it sits in the season. */
    .gr-date { display: flex; align-items: center; gap: .5rem; flex-wrap: wrap; margin-bottom: .85rem; }
    .gr-date-lbl { font-size: .78rem; font-weight: 700; color: var(--color-gray-500); }
    /* The date is a tag, not a form field: tap it and the picker comes up.
       The real input rides along invisibly — the label forwards the tap to
       it, and the input is what knows how to show a calendar. */
    .gr-date-tag { position: relative; display: inline-flex; align-items: center; gap: .45rem;
        padding: .4rem .85rem; border-radius: 999px; border: 1px solid #cfe3b8; background: #f0f7e8;
        font-size: .8rem; font-weight: 700; color: #3d6823; cursor: pointer; user-select: none;
        transition: background .28s cubic-bezier(.22,1,.36,1), border-color .28s cubic-bezier(.22,1,.36,1),
            transform .28s cubic-bezier(.22,1,.36,1); }
    .gr-date-tag:hover { background: #e4efd4; border-color: #b7d597; }
    .gr-date-tag:active { transform: scale(.96); }
    .gr-date-tag svg { width: .95rem; height: .95rem; }
    .gr-date-tag input[type="date"] { position: absolute; inset: 0; opacity: 0; pointer-events: none; }
    html.dark .gr-date-tag { background: rgb(61 104 35 / .25); border-color: #3f5626; color: #bfe19a; }
    html.dark .gr-date-tag:hover { background: rgb(61 104 35 / .4); }
    @media (prefers-reduced-motion: reduce) { .gr-date-tag { transition: none; } }

    /* ---- the season, said in colour --------------------------------------
       Soil brown at the start, the greens through establishment and
       tillering, water blue where the crop's demand for it peaks, the golds
       through flowering and filling, and a deep harvest amber at the end.
       Held under a fifth of an alpha: the words on top are what anybody came
       to read. The same eight the growth tool in Activities wears, so a lot
       is the same colour in both places. */
    .gr-c0 { background-image: linear-gradient(135deg, rgb(138 90 43 / .16), rgb(180 130 80 / .10) 45%, rgb(110 70 32 / .16)); }
    .gr-c1 { background-image: linear-gradient(135deg, rgb(143 194 103 / .18), rgb(190 220 150 / .10) 45%, rgb(107 159 61 / .16)); }
    .gr-c2 { background-image: linear-gradient(135deg, rgb(107 159 61 / .20), rgb(143 194 103 / .12) 45%, rgb(74 124 42 / .18)); }
    .gr-c3 { background-image: linear-gradient(135deg, rgb(47 82 25 / .20), rgb(74 124 42 / .12) 45%, rgb(31 61 16 / .18)); }
    .gr-c4 { background-image: linear-gradient(135deg, rgb(13 148 136 / .18), rgb(56 189 248 / .12) 45%, rgb(30 64 175 / .18)); }
    .gr-c5 { background-image: linear-gradient(135deg, rgb(251 191 36 / .20), rgb(253 224 71 / .12) 45%, rgb(240 180 41 / .18)); }
    .gr-c6 { background-image: linear-gradient(135deg, rgb(249 168 37 / .20), rgb(251 191 36 / .12) 45%, rgb(217 130 20 / .18)); }
    .gr-c7 { background-image: linear-gradient(135deg, rgb(180 83 9 / .20), rgb(217 178 60 / .12) 45%, rgb(120 53 15 / .18)); }
    .gr-card { background-size: 240% 240%; animation: gradSweep 16s ease-in-out infinite alternate; }
    @media (prefers-reduced-motion: reduce) { .gr-card { animation: none; } }
    html.dark .gr-c0 { background-image: linear-gradient(135deg, rgb(138 90 43 / .30), rgb(180 130 80 / .18) 45%, rgb(110 70 32 / .30)); }
    html.dark .gr-c1 { background-image: linear-gradient(135deg, rgb(143 194 103 / .26), rgb(190 220 150 / .16) 45%, rgb(107 159 61 / .26)); }
    html.dark .gr-c2 { background-image: linear-gradient(135deg, rgb(107 159 61 / .30), rgb(143 194 103 / .18) 45%, rgb(74 124 42 / .28)); }
    html.dark .gr-c3 { background-image: linear-gradient(135deg, rgb(74 124 42 / .32), rgb(107 159 61 / .18) 45%, rgb(47 82 25 / .30)); }
    html.dark .gr-c4 { background-image: linear-gradient(135deg, rgb(13 148 136 / .30), rgb(56 189 248 / .18) 45%, rgb(30 64 175 / .28)); }
    html.dark .gr-c5 { background-image: linear-gradient(135deg, rgb(251 191 36 / .28), rgb(253 224 71 / .16) 45%, rgb(240 180 41 / .26)); }
    html.dark .gr-c6 { background-image: linear-gradient(135deg, rgb(249 168 37 / .28), rgb(251 191 36 / .16) 45%, rgb(217 130 20 / .26)); }
    html.dark .gr-c7 { background-image: linear-gradient(135deg, rgb(217 119 6 / .30), rgb(217 178 60 / .16) 45%, rgb(146 64 14 / .28)); }
    /* background-COLOR, not the shorthand: the shorthand resets
       background-image, and the stage's tint is an image. A white card with
       a coloured wash over it is what this is meant to be. */
    .gr-card { border: 1px solid var(--color-gray-200); border-radius: 1rem; overflow: hidden;
        background-color: var(--color-white); margin-bottom: .9rem; }
    /* The header is transparent so the card's own stage colour shows
       through it: the tint belongs to the lot, not to one band of it, and a
       header painting its own green over the top was the reason only the
       top inch of the card was coloured. */
    .gr-top { display: flex; align-items: center; gap: .7rem; padding: .8rem .9rem;
        background: rgb(255 255 255 / .28); cursor: pointer; user-select: none; }
    .gr-top:hover { background: rgb(255 255 255 / .45); }
    /* Accordion, the same one the activities board uses: a lot folds down to
       its header, the chevron flags state, and the body is a 1fr→0fr grid
       row so height animates without knowing the content size. */
    .gr-chev { width: 1rem; height: 1rem; flex-shrink: 0; color: #6b9f3d; transition: transform .18s ease; }
    .gr-card:not(.is-folded) .gr-chev { transform: rotate(90deg); }
    .gr-fold { display: grid; grid-template-rows: 1fr; transition: grid-template-rows .28s cubic-bezier(.22,1,.36,1); }
    /* Sliding and fading together, as every other fold in the app now
       does. The slide alone leaves the contents at full strength against a
       shutting edge, which reads as a clip rather than a movement. */
    .gr-fold-inner { overflow: hidden; min-height: 0; opacity: 1;
        transition: opacity .22s ease; }
    .gr-card.is-folded .gr-fold-inner { opacity: 0; }
    .gr-card.is-folded .gr-fold { grid-template-rows: 0fr; }
    /* Folded, the header answers for the body: the stage takes the counter
       explainer's line, so a folded page reads lot | stage | day. */
    .gr-fold-stage { display: none; font-size: .72rem; font-weight: 700; color: var(--color-gray-500); margin-top: .1rem; }
    .gr-card.is-folded .gr-mode { display: none; }
    .gr-card.is-folded .gr-fold-stage { display: block; }
    /* Restoring the remembered folds on load applies instantly. */
    #grCards.no-fold-anim .gr-fold, #grCards.no-fold-anim .gr-chev,
    #grCards.no-fold-anim .gr-fold-inner { transition: none; }
    @media (prefers-reduced-motion: reduce) { .gr-fold, .gr-chev { transition: none; } }
    /* Collapse all leads the row rather than trailing it: it acts on
       everything below, and a control for the whole list belongs at the
       start of the line the list begins on. */
    .gr-foldall { margin-right: auto; }
    html.dark .gr-chev { color: #86b556; }
    .gr-emoji { font-size: 1.7rem; line-height: 1; }
    .gr-lot { font-size: .98rem; font-weight: 800; color: var(--color-gray-900); }
    .gr-mode { display: block; font-size: .68rem; color: var(--color-gray-400); margin-top: .1rem; }
    .gr-crop { font-size: .72rem; font-weight: 700; color: #3d6823; }
    .gr-age { margin-left: auto; text-align: right; flex: 0 0 auto; }
    .gr-age-n { font-size: 1.35rem; font-weight: 800; line-height: 1; color: #3d6823; }
    .gr-age-l { font-size: .62rem; font-weight: 800; letter-spacing: .06em; text-transform: uppercase; color: #6b9f3d; }

    .gr-body { padding: .85rem .9rem; }
    .gr-stage { font-size: 1.05rem; font-weight: 800; color: var(--color-gray-900); }
    .gr-what { font-size: .85rem; line-height: 1.5; color: var(--tl-text-muted, #4b5563); margin-top: .2rem; }
    /* The one line of guidance a patterned crop carries. Same shape as the
       do-list below it, so a crop with the short answer and a crop with the
       long one read as the same kind of page. */
    .gr-needs { font-size: .84rem; line-height: 1.5; margin-top: .7rem;
        padding: .6rem .7rem; border-radius: .7rem;
        background: var(--color-brand-50); color: #3d6823; }
    .gr-needs b { font-weight: 800; }
    html.dark .gr-needs { background: rgb(61 104 35 / .25); color: #bfe19a; }
    .gr-bar { height: .4rem; border-radius: 999px; background: var(--color-gray-200); overflow: hidden; margin-top: .6rem; }
    .gr-bar span { display: block; height: 100%; border-radius: 999px;
        background: linear-gradient(90deg, #6b9f3d, #4a7c2a); }
    .gr-next { font-size: .72rem; color: var(--color-gray-500); margin-top: .3rem; }

    .gr-lists { display: grid; gap: .5rem; margin-top: .8rem; }
    @media (min-width: 720px) { .gr-lists { grid-template-columns: 1fr 1fr; } }
    .gr-list { border-radius: .8rem; padding: .65rem .75rem; }
    .gr-list h4 { font-size: .64rem; font-weight: 800; letter-spacing: .06em; text-transform: uppercase;
        display: flex; align-items: center; gap: .35rem; margin-bottom: .35rem; }
    .gr-list ul { display: grid; gap: .3rem; }
    .gr-list li { font-size: .8rem; line-height: 1.45; display: flex; gap: .4rem; }
    .gr-list li::before { content: ''; flex: 0 0 auto; width: .35rem; height: .35rem; border-radius: 999px;
        margin-top: .5rem; background: currentColor; opacity: .5; }
    .gr-do { background: #f0f7e8; color: #2d5016; }
    .gr-watch { background: #fff7ed; color: #9a3412; }
    html.dark .gr-do { background: rgb(61 104 35 / .22); color: #bfe19a; }
    html.dark .gr-watch { background: rgb(154 52 18 / .2); color: #fdba74; }

    .gr-steps { margin-top: .85rem; border-top: 1px dashed var(--color-gray-200); padding-top: .65rem; display: grid; gap: .3rem; }
    .gr-step { display: flex; align-items: flex-start; gap: .5rem; font-size: .78rem; color: var(--color-gray-500); }
    .gr-dot { flex: 0 0 auto; width: .6rem; height: .6rem; border-radius: 999px; margin-top: .35rem; background: var(--color-gray-300); }
    .gr-step.is-past .gr-dot { background: #a8cc7e; }
    .gr-step.is-now { color: var(--color-gray-900); font-weight: 700; }
    .gr-step.is-now .gr-dot { background: #4a7c2a; box-shadow: 0 0 0 3px rgb(74 124 42 / .2); }
    .gr-when { margin-left: auto; flex: 0 0 auto; font-variant-numeric: tabular-nums; opacity: .7; }

    .gr-blocked { padding: .9rem; font-size: .83rem; line-height: 1.5; color: var(--color-gray-500);
        background: var(--color-gray-50); border-radius: .7rem; }
    .gr-note { display: flex; gap: .6rem; align-items: flex-start; margin: .2rem 0 .6rem;
        padding: .7rem .8rem; border-radius: .8rem; background: #fffbeb; border: 1px solid #fde68a; }
    .gr-note p { font-size: .78rem; line-height: 1.5; color: #92400e; margin: 0; }
    .gr-note-ico { flex: 0 0 auto; color: #b45309; }
    .gr-note-ico svg { width: 1.1rem; height: 1.1rem; }
    html.dark .gr-note { background: rgb(180 83 9 / .16); border-color: rgb(180 83 9 / .45); }
    html.dark .gr-note p { color: #fcd34d; }
    html.dark .gr-note-ico { color: #fcd34d; }

    html.dark .gr-card { background-color: #151b12; border-color: #2b3a1c; }
    html.dark .gr-top { background: rgb(0 0 0 / .18); }
    html.dark .gr-top:hover { background: rgb(0 0 0 / .26); }
    html.dark .gr-lot, html.dark .gr-stage, html.dark .gr-step.is-now { color: #e8efe1; }
    html.dark .gr-blocked { background: rgb(255 255 255 / .04); }
</style>
@endpush

@section('content')
@include('sm.partials.module-header', ['schedule' => $schedule, 'module' => 'growth'])

{{-- The whole page is "on this date": today by default, any date on request,
     because planning next week's spray means reading next week's stage. --}}
<form method="GET" action="{{ route('sm.growth') }}" class="gr-date">
    <input type="hidden" name="id" value="{{ $schedule->id }}">
    <span class="gr-date-lbl">Reading the crop on</span>
    <label class="gr-date-tag" title="Pick another date to read the crop on">
        <svg fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24" aria-hidden="true"><path stroke-linecap="round" stroke-linejoin="round" d="M8 7V3m8 4V3m-9 8h10M5 21h14a2 2 0 002-2V7a2 2 0 00-2-2H5a2 2 0 00-2 2v12a2 2 0 002 2z"/></svg>
        <span>{{ $on->isToday() ? 'Today — ' . $on->format('M j, Y') : $on->format('D, M j, Y') }}</span>
        <input type="date" name="on" value="{{ $on->toDateString() }}" aria-label="Reading the crop on"
            onchange="this.form.submit()"
            onclick="try { this.showPicker && this.showPicker(); } catch (_) {}">
    </label>
    @if (! $on->isToday())
        <a class="btn btn-white btn-sm" href="{{ route('sm.growth', ['id' => $schedule->id]) }}">Back to today</a>
    @endif
    @if (count($rows))
        <button type="button" id="grFoldAll" class="btn btn-white btn-sm gr-foldall">Collapse all</button>
    @endif
</form>

<div id="grCards">
@forelse ($rows as $r)
    @php
        /* Which of the eight colour bands this lot's stage sits in.
           By fraction through the season, not by the stage's name: rice has
           eight stages and a mango tree has five, and the question the colour
           answers — how far through is this — is the same either way. */
        $grStage = $r['stage'] ?? [];
        $grSteps = max(1, count($r['timeline'] ?? []) - 1);
        $grBand = (int) round((($grStage['index'] ?? 0) / $grSteps) * 7);
        $grBand = max(0, min(7, $grBand));
    @endphp
    <div class="gr-card gr-c{{ $grBand }}" data-lot="{{ $r['lot']->id }}">
        <div class="gr-top" title="Tap to fold or open this lot">
            <svg class="gr-chev" fill="none" stroke="currentColor" stroke-width="2.5" viewBox="0 0 24 24" aria-hidden="true"><path stroke-linecap="round" stroke-linejoin="round" d="M9 5l7 7-7 7"/></svg>
            <span class="gr-emoji">{{ $r['icon'] }}</span>
            <span class="min-w-0">
                <span class="gr-lot block">{{ $r['lot']->lotName }}</span>
                <span class="gr-crop">{{ $r['cropLabel'] ?: 'No crop set' }}</span>
                {{-- Which ruler this lot is read against, because the same crop
                     on the next block may be read against another one. --}}
                <span class="gr-mode">{{ \App\Http\Controllers\Manager\GrowthStageController::counterSays($r['lot']->dayType) }}</span>
                {{-- Folded, the stage stands in for the explainer above. --}}
                <span class="gr-fold-stage">{{ $r['blocked'] ? 'Not readable yet' : ($r['stage']['label'] ?? '') }}</span>
            </span>
            @if ($r['age'])
                {{-- A tree's number is months, not days, and the label has to
                     say so — "66 DAP" on a five-year-old mango reads as a
                     seedling nine weeks out of the nursery. --}}
                @php
                    $isAge = ($r['age']['counter'] ?? '') === 'AGE';
                    $ageYears = $isAge ? floor($r['age']['day'] / 12) : 0;
                @endphp
                <span class="gr-age" @if ($isAge) title="{{ $r['age']['day'] }} months old" @endif>
                    <span class="gr-age-n block">{{ $isAge && $ageYears >= 2 ? $ageYears : $r['age']['day'] }}</span>
                    <span class="gr-age-l">{{ $isAge ? ($ageYears >= 2 ? 'years old' : 'months') : $r['age']['counter'] }}</span>
                </span>
            @endif
        </div>

        <div class="gr-fold"><div class="gr-fold-inner">
        <div class="gr-body">
            @if ($r['blocked'])
                <p class="gr-blocked">{{ $r['blocked'] }}</p>
            @else
                @php $st = $r['stage']; @endphp
                <div class="gr-stage">{{ $st['label'] }}</div>
                <p class="gr-what">{{ $st['what'] }}</p>
                @if ($st['progress'] !== null)
                    <div class="gr-bar"><span style="width: {{ round($st['progress'] * 100) }}%"></span></div>
                @endif
                {{-- A tree's stages are months apart, so "day 14 of this
                     stage · next in about 24 days" would be wrong twice
                     over — and a tree has no harvest window to be at the
                     end of, only the last stage of its life. --}}
                @php $unit = $st['unit'] ?? 'day'; @endphp
                <p class="gr-next">
                    {{ ucfirst($unit) }} {{ $st['dayInStage'] + 1 }} of this stage
                    @if ($st['next'])
                        · {{ $st['next']['label'] }} in about {{ $st['next']['inDays'] }} {{ \Illuminate\Support\Str::plural($unit, $st['next']['inDays']) }}
                    @elseif ($unit === 'month')
                        · the last of its stages
                    @else
                        · the harvest window
                    @endif
                </p>

                {{-- What the stage asks for.
                     The seven crops with hand-written guidance get the full
                     do/watch lists below. Every other crop still carries the
                     one line its stage was written with, and showing it is
                     the difference between guidance and a bare label. --}}
                @if (! $r['tips']['do'] && ! empty($st['needs']))
                    <p class="gr-needs"><b>What it usually needs:</b> {{ $st['needs'] }}</p>
                @endif

                <div class="gr-lists">
                    @if ($r['tips']['do'])
                        <div class="gr-list gr-do">
                            <h4>
                                <svg class="w-3.5 h-3.5" fill="none" stroke="currentColor" stroke-width="2.5" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" d="M5 13l4 4L19 7"/></svg>
                                What to do now
                            </h4>
                            <ul>@foreach ($r['tips']['do'] as $t)<li>{{ $t }}</li>@endforeach</ul>
                        </div>
                    @endif
                    @if ($r['tips']['watch'])
                        <div class="gr-list gr-watch">
                            <h4>
                                <svg class="w-3.5 h-3.5" fill="none" stroke="currentColor" stroke-width="2.5" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" d="M12 9v4m0 4h.01M10.3 3.9L1.8 18a2 2 0 001.7 3h17a2 2 0 001.7-3L13.7 3.9a2 2 0 00-3.4 0z"/></svg>
                                What to watch for
                            </h4>
                            <ul>@foreach ($r['tips']['watch'] as $t)<li>{{ $t }}</li>@endforeach</ul>
                        </div>
                    @endif
                </div>

                @if ($r['timeline'])
                    <div class="gr-steps">
                        @foreach ($r['timeline'] as $step)
                            <div class="gr-step{{ $step['isNow'] ? ' is-now' : ($step['isPast'] ? ' is-past' : '') }}">
                                <span class="gr-dot"></span>
                                <span class="grow">{{ $step['label'] }}</span>
                                <span class="gr-when">{{ $r['age']['counter'] }} {{ $step['from'] }}+</span>
                            </div>
                        @endforeach
                    </div>
                @endif
            @endif
        </div>
        </div></div>
    </div>
@empty
    <div class="card card-body text-center text-gray-500 py-10">
        <p class="font-bold text-gray-800 mb-1">No lots yet</p>
        <p class="text-sm">Add a lot, say what is growing on it, and this page will read the crop for you.</p>
        <a class="btn btn-primary mt-4 inline-flex" href="{{ route('sm.lots', ['id' => $schedule->id]) }}">Open Lots</a>
    </div>
@endforelse
</div>

@if (count($rows))
    {{-- Said properly, and where it cannot be missed: a stage read off a
         calendar is a guess about a plant, and a season that ran through a
         typhoon is not the season this table describes. --}}
    <div class="gr-note">
        <span class="gr-note-ico" aria-hidden="true">
            <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><circle cx="12" cy="12" r="9"/><path stroke-linecap="round" stroke-linejoin="round" d="M12 11v5m0-8h.01"/></svg>
        </span>
        <p>These stages are counted from the calendar, not from the plant. A crop runs late or early with the weather it gets — a cold spell, a drought, flooding, a typhoon, pest damage or a hungry field all shift it, and so do the variety and how it was established. Walk the field and believe what you see there over what this page says.</p>
    </div>
@endif

<script>
    /* The lots fold like the board's days do, and the set of folded lots is
       remembered per schedule — this module re-fetches on every open (the
       shell marks it fresh), so the memory has to live in the browser. */
    (() => {
        const KEY = 'growthFolded:' + @json($schedule->id);
        const cards = document.getElementById('grCards');
        const btn = document.getElementById('grFoldAll');
        if (!cards) return;
        const all = () => Array.from(cards.querySelectorAll('.gr-card[data-lot]'));
        const folded = new Set((() => {
            try { return JSON.parse(localStorage.getItem(KEY) || '[]'); } catch (_) { return []; }
        })());
        const save = () => { try { localStorage.setItem(KEY, JSON.stringify([...folded])); } catch (_) { /* private mode */ } };
        const sayBtn = () => {
            if (!btn) return;
            btn.textContent = all().some((c) => !c.classList.contains('is-folded')) ? 'Collapse all' : 'Expand all';
        };

        // Apply the remembered folds instantly — restoring is not a change,
        // and a wave of closing animations on load reads as one.
        cards.classList.add('no-fold-anim');
        all().forEach((c) => c.classList.toggle('is-folded', folded.has(c.getAttribute('data-lot'))));
        void cards.offsetWidth;
        requestAnimationFrame(() => cards.classList.remove('no-fold-anim'));
        sayBtn();

        cards.addEventListener('click', (e) => {
            const top = e.target.closest('.gr-top');
            const card = top && top.closest('.gr-card[data-lot]');
            if (!card) return;
            const id = card.getAttribute('data-lot');
            if (card.classList.toggle('is-folded')) folded.add(id);
            else folded.delete(id);
            save();
            sayBtn();
        });

        btn?.addEventListener('click', () => {
            // If anything is open, the button means "close everything";
            // only a fully folded page flips it to mean the opposite.
            const fold = all().some((c) => !c.classList.contains('is-folded'));
            all().forEach((c) => {
                c.classList.toggle('is-folded', fold);
                const id = c.getAttribute('data-lot');
                if (fold) folded.add(id);
                else folded.delete(id);
            });
            save();
            sayBtn();
        });
    })();
</script>
@endsection
