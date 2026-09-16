{{--
    Single activity card — server-side twin of the JS renderActivityCard(a).
    Keep the structure, classes and data-attributes in sync with the JS
    renderer in sm/activities.blade.php.

    Expects: $a (AsScheduleActivity with lots/workers/items loaded),
             $schedule, $activityTypes, $lotDayZeroEff (lotId => Carbon|null)
--}}
@php
    $cardLots = $a->lots;
    $lotSig = $cardLots->pluck('id')->sort()->values()->implode(',');
    $startC = $a->targetDate ? \Illuminate\Support\Carbon::parse($a->targetDate) : null;
    $endC = $a->targetEndDate ? \Illuminate\Support\Carbon::parse($a->targetEndDate) : null;
    $isRange = $startC && $endC && $endC->greaterThan($startC);
    $rangeDays = $isRange ? ($startC->diffInDays($endC) + 1) : 0;
    $typeLabel = ($a->activityType && isset($activityTypes[$a->activityType])) ? $activityTypes[$a->activityType] : null;
    $timeLabel = $a->timeRequired === 'whole' ? 'Whole day' : ($a->timeRequired === 'half' ? 'Half day' : 'N/A');

    $searchBits = [mb_strtolower($a->activityTitle), mb_strtolower($typeLabel ?? '')];
    foreach ($cardLots as $l) { $searchBits[] = mb_strtolower($l->lotName . ' ' . ($l->variety ?? '')); }
    foreach ($a->workers as $w) { $searchBits[] = mb_strtolower($w->workerName); }
    foreach ($a->items as $it) {
        $searchBits[] = mb_strtolower($it->itemType === 'material' ? ($it->material->materialName ?? '') : ($it->service->serviceName ?? ''));
    }
    // The card's tags: searchable words AND an exact handle for filters.
    $cardTags = ($activityTags ?? [])[$a->id] ?? [];
    foreach ($cardTags as $tg) { $searchBits[] = mb_strtolower($tg['name']); }
    $searchText = trim(implode(' ', array_filter($searchBits)));

    // What this viewer may do to the plan. A control they may not use is still
    // drawn, in its usual place, disabled and dimmed — a board whose buttons
    // come and go per person is one nobody can be told how to use, and the
    // greying says "not yours" where a gap would just look broken.
    // Twin of LOCK_EDIT / editTitle in activities-js.blade.php.
    // Note: appending a note to an activity goes through the write gate too,
    // so it follows canEdit; "notes only" buys the DAY's note, not this one.
    /* THE PER-LOT DAY COUNTER, worked out once for the whole card.
     *
     * It used to be computed inside the meta row below the title. It belongs
     * on the lot's own chip now - the day number is the first thing a grower
     * checks against the work in front of them, and it was sitting a line
     * away from the lot it counts for - so it is hoisted here, where both
     * places that draw a lot chip can read it.
     *
     * Twin of computeDasLabel() in activities-js.blade.php, and it has to be
     * read against that function rather than reinvented here: only a lot whose
     * dayType is DAT - sown and then transplanted - flips to a fresh DAT count
     * on and after its transplant date, naming the count it converts from at
     * the pivot. DAS is direct seeded and never flips; DAP is planted and is
     * one count throughout. The earlier version of this block had that rule
     * backwards, so the server drew DAT on a direct-seeded lot and the JS
     * refresh corrected it a moment later - invisible while the number sat in
     * a grey row, a visible flicker now it rides the lot's own chip.
     *
     * A lot with no anchor date has no number at all. */
    $lotDaySuffix = [];
    if ($startC) {
        $lotTpEff = $lotTransplantEff ?? [];
        foreach ($cardLots as $lot) {
            $mode = strtoupper($lot->dayType ?: 'DAT');
            if (! in_array($mode, ['DAP', 'DAS', 'TREE'], true)) { $mode = 'DAT'; }
            $z = $lotDayZeroEff[$lot->id] ?? null;
            $suffix = null;

            // Only a sown-then-transplanted lot flips to a fresh DAT count.
            if ($mode === 'DAT') {
                $tp = $lotTpEff[$lot->id] ?? null;
                if ($tp && $startC->gte($tp)) {
                    $datDelta = (int) $tp->diffInDays($startC, false);
                    if ($datDelta === 0 && $z) {
                        // At the pivot, name the count it converts from.
                        $dd = (int) $z->diffInDays($startC, false);
                        $suffix = 'DAS' . ($dd > 0 ? '+' : '') . $dd . ' → DAT0';
                    } else {
                        $suffix = 'DAT' . ($datDelta > 0 ? '+' : '') . $datDelta;
                    }
                }
            }

            // Base phase: DAS before any transplant, DAP throughout.
            if ($suffix === null && $z) {
                $delta = (int) $z->diffInDays($startC, false);
                $suffix = ($mode === 'DAP' ? 'DAP' : 'DAS') . ($delta > 0 ? '+' : '') . $delta;
            }

            if ($suffix !== null) { $lotDaySuffix[$lot->id] = $suffix; }
        }
    }

    /* WHAT THIS ACTIVITY COSTS, and who it is spent on.
     *
     * Both used to be worked out at the bottom of the card and printed there.
     * The figure has moved up beside the fold chevron, where it is a tag you
     * can read without opening the card; the breakdown has no room up there,
     * so it rides the tag's tooltip and an attribute.
     *
     * The attribute is what the day's cash sheet reads. It used to scrape the
     * text out of the line at the bottom of the card, so taking that line away
     * would have emptied the "who" column of every wage row in the sheet. A
     * fact the page needs belongs in an attribute, not in a sentence somebody
     * else is parsing.
     *
     * A payroll card names every worker and figure in its own checklist, so
     * it carries neither: the same information twice is not twice as useful. */
    $labour = $a->labourTotal();
    $labourParts = $a->workers->map(function ($w) use ($a) {
        $part = $a->dayPartFor($w);
        $len = $part === 'half' ? '½d' : ($part === 'whole' ? '1d' : '—');

        return $w->workerName . ' ' . $len . ' ' . \App\Support\Region::symbol() . number_format($a->workerPay($w), 2);
    })->join(' · ');
    $showCost = $labour > 0 && $a->activityType !== 'worker_payroll';
    /* THE SAME FIGURE, SHORT, FOR WHEN THE ROW CANNOT HOLD IT.
     *
     * The tag stands in a row of five chips, and on a 360px phone the exact
     * peso pushed it off that row onto a line of its own. Both are rendered
     * and CSS picks: the full figure where there is room, ₱x.xk where there
     * is not. Decided by the stylesheet rather than by measuring, so it costs
     * nothing per card on a board with two hundred of them.
     *
     * Transcribed from moneyShort() in activities-js. The thresholds sit just
     * under the round number because the rounding happens after: ₱999,999 is
     * a million to one decimal place, and testing against a million would
     * have called it ₱1000k. */
    $shortMoney = function ($n) {
        $v = (int) round((float) $n);
        $trim = fn ($x) => rtrim(rtrim(number_format($x, 1, '.', ''), '0'), '.');
        if ($v >= 999500) {
            $m = $v / 1000000;

            return \App\Support\Region::symbol() . ($m >= 10 ? (string) round($m) : $trim($m)) . 'M';
        }
        if ($v >= 1000) {
            $k = $v / 1000;

            return \App\Support\Region::symbol() . ($k >= 10 ? (string) round($k) : $trim($k)) . 'k';
        }

        return \App\Support\Region::symbol() . $v;
    };

    $mayEdit = \App\Support\WorkerContext::canEdit();
    $lockCls = $mayEdit ? '' : ' is-locked';
    $editTitle = fn ($plain) => $mayEdit ? $plain : 'Only someone who can edit the plan may do this';
@endphp
{{-- draggable follows $mayEdit too: a grip cursor on a card whose drop the
     gate will refuse is a promise the board cannot keep. A worker never drags,
     editing or not — the board's JS asks the same question as MAY_DRAG, and
     asking it here as well is what keeps the grab cursor off the first paint. --}}
<div class="activity-card prio-{{ $a->priority }}{{ $a->isHidden ? ' is-hidden' : '' }}{{ $a->isDone ? ' is-done' : '' }}" draggable="{{ ($a->isDone || ! $mayEdit || \App\Support\WorkerContext::activeGrant()) ? 'false' : 'true' }}"
     data-id="{{ $a->id }}"
     data-is-done="{{ $a->isDone ? 1 : 0 }}"
     data-tags="{{ json_encode(is_array($a->tags) ? $a->tags : []) }}"
     data-labour="{{ $labour }}"
     data-labour-parts="{{ $showCost ? $labourParts : '' }}"
     data-materials="{{ $a->materialsTotal() }}"
     data-target-date="{{ $startC ? $startC->format('Y-m-d') : '' }}"
     data-target-end-date="{{ $endC ? $endC->format('Y-m-d') : '' }}"
     data-lot-signature="{{ $lotSig }}"
     data-sequence-order="{{ (int) $a->sequenceOrder }}"
     data-is-day-zero="{{ $a->isDayZero ? 1 : 0 }}"
     data-is-transplant="{{ $a->isTransplant ? 1 : 0 }}"
     data-activity-type="{{ $a->activityType ?: '' }}"
     {{-- Everything in the tank, primary first: the day's warnings read this
          rather than the single type, or a fungicide riding along with an
          insecticide would be invisible to them. --}}
     data-activity-types="{{ implode(',', $a->typeSlugs()) }}"
     data-is-hidden="{{ $a->isHidden ? 1 : 0 }}"
     data-search="{{ $searchText }}"
     data-tag-ids="{{ collect($cardTags)->pluck('id')->implode(',') }}"
     @if($cardLots->count()) style="--lot-accent: hsl({{ ($cardLots->first()->id * 137) % 360 }}, 55%, 40%)" @endif>
    <div class="flex items-start justify-between gap-2">
        <div class="flex items-start gap-2.5 min-w-0 grow">
            {{-- THE CHIPS AND THE COST ARE ONE THING THAT CANNOT BE BROKEN UP.
                 The head row wraps on a phone - that is how the lot gets a
                 line of its own - and what wraps is decided by what fits, so
                 the cost kept being the item that did not and went down a
                 line by itself. Held in a run of their own, they are a single
                 item of that row: the figure is beside the chevron because
                 there is no arrangement in which it is not.
                 display:contents where a mouse is, so nothing about the
                 desktop card changes. Twin of the same span in the JS
                 renderer. --}}
            <span class="act-head-chips">
            <button type="button" class="done-check{{ $a->isDone ? ' is-checked' : '' }}{{ $lockCls }}" data-id="{{ $a->id }}" @disabled(! $mayEdit)
                title="{{ $editTitle($a->isDone ? 'Mark as not done (unlocks editing)' : 'Mark this activity as done') }}"
                aria-pressed="{{ $a->isDone ? 'true' : 'false' }}" aria-label="Mark activity as done">
                <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="3"><path stroke-linecap="round" stroke-linejoin="round" d="M5 13l4 4L19 7"/></svg>
            </button>
            <span class="type-ico {{ $a->activityType === 'irrigation' ? 'type-ico-irrigation' : ($a->activityType === 'service' ? 'type-ico-service' : ($a->activityType === 'worker_payroll' ? 'type-ico-payroll' : ($a->activityType === 'reminder_checklist' ? 'type-ico-reminder' : 'type-ico-task'))) }}" aria-hidden="true">
                @if($a->activityType === 'reminder_checklist')
                    {{-- A ticked list, because that is the whole activity. --}}
                    <svg fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" d="M9 5H7a2 2 0 00-2 2v12a2 2 0 002 2h10a2 2 0 002-2V7a2 2 0 00-2-2h-2M9 5a2 2 0 002 2h2a2 2 0 002-2M9 5a2 2 0 012-2h2a2 2 0 012 2m-6 9l1.5 1.5L15 12"/></svg>
                @elseif($a->activityType === 'worker_payroll')
                    {{-- People, not a clipboard: what this day is about is who
                         came, and the icon should say so before the title does. --}}
                    <svg fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" d="M17 20h5v-1a4 4 0 00-4-4h-1M9 11a4 4 0 100-8 4 4 0 000 8zm8 0a3 3 0 100-6M2 20v-1a5 5 0 015-5h4a5 5 0 015 5v1H2z"/></svg>
                @elseif($a->activityType === 'irrigation')
                    <svg fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" d="M12 3s6 6.686 6 11a6 6 0 11-12 0c0-4.314 6-11 6-11z"/></svg>
                @elseif($a->activityType === 'service')
                    <svg fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" d="M11 5a4 4 0 105.03 5.03l4.35 4.35a2 2 0 11-2.83 2.83l-4.35-4.35A4 4 0 0111 5zM5 19l4-4"/></svg>
                @else
                    <svg fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" d="M9 5H7a2 2 0 00-2 2v12a2 2 0 002 2h10a2 2 0 002-2V7a2 2 0 00-2-2h-2M9 5a2 2 0 002 2h2a2 2 0 002-2M9 5a2 2 0 012-2h2a2 2 0 012 2m-6 9l2 2 4-4"/></svg>
                @endif
            </span>
            {{-- A star, and nothing behind it.
                 The board already tells you a line's priority, its type, its
                 status and its tags. This one is not another of those: it is
                 a mark somebody put here for a reason they never had to give
                 the app. Tap it and the colours open, each with its name.
                 Twin of the star in renderActivityCard(). --}}
            @php
                $starInk = (int) ($a->markerColor ?? 0);
                // Same order as STAR_NAMES in activities-js and the swatches
                // in #markerPickGrid — one list, said in three places.
                $starInkName = ['None', 'Leaf', 'Sun', 'Ember', 'Rose', 'Orchid', 'Dusk', 'Sky', 'Tide'][$starInk] ?? 'None';
            @endphp
            <button type="button" class="icon-btn star-btn{{ $lockCls }}" data-star-btn data-id="{{ $a->id }}"
                    data-star="{{ $starInk }}" @disabled(! $mayEdit)
                    title="{{ $editTitle($starInk ? 'Marker: ' . $starInkName : 'Marker — tap to pick a colour') }}"
                    aria-label="Marker: {{ $starInkName }}">
                <svg viewBox="0 0 24 24" stroke-linejoin="round"><path d="m12 3.4 2.63 5.33 5.88.86-4.25 4.15 1 5.86L12 16.85l-5.26 2.75 1-5.86-4.25-4.15 5.88-.86z"/></svg>
            </button>
            <button type="button" class="icon-btn card-menu-btn" data-id="{{ $a->id }}" data-name="{{ $a->activityTitle }}" title="Actions">
                <svg fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" d="M4 6h16M4 12h16M4 18h16"/></svg>
            </button>
            <span class="act-fold-chip" aria-hidden="true"><svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.2"><path stroke-linecap="round" stroke-linejoin="round" d="M6 9l6 6 6-6"/></svg></span>
            @if ($showCost)
                <span class="act-cost-tag" title="{{ $labourParts }}"><span class="acx-full">{{ \App\Support\Region::money($labour) }}</span><span class="acx-short">{{ $shortMoney($labour) }}</span></span>
            @endif
            </span>
            <div class="min-w-0 grow">
            {{-- Lot(s) first, as a prominent label — so it's clear which lot the
                 activity is for before you read the title. --}}
            <div class="activity-card-lots activity-card-lothead">
                @if($a->activityType === 'reminder_checklist')
                    {{-- A reminder checklist is not work on a lot, it is a list
                         of errands — so the slot that names the ground says what
                         the card is instead, and the lot (when there is one)
                         moves down beside the priority. --}}
                    <span class="badge reminder-head-badge">Reminder Checklist</span>
                @elseif($cardLots->count())
                    @foreach($cardLots as $lot)
                        {{-- Auto colour per lot (golden-angle hue → distinct + stable). --}}
                        <span class="item-tag lot-tag"
                              data-lot-id="{{ $lot->id }}"
                              data-lot-name="{{ $lot->lotName }}"
                              data-lot-variety="{{ $lot->variety ?? '' }}"
                              style="background: hsl({{ ($lot->id * 137) % 360 }}, 55%, 40%)">{{ $lot->lotName }}@isset($lotDaySuffix[$lot->id])<span class="lot-tag-das">{{ $lotDaySuffix[$lot->id] }}</span>@endisset</span>
                    @endforeach
                @elseif ($a->activityType !== 'worker_payroll')
                    {{-- A payroll day is about who turned up, not which field,
                         so "no lot" is its normal state rather than a gap. --}}
                    <span class="item-tag activity-na-tag" title="Applies generally — not tied to any specific lot">N/A — Not lot-specific</span>
                @endif
            </div>
            <div class="act-title-line">
                <h3 class="activity-card-title">{{ $a->activityTitle }}</h3>
                <span class="act-inline-meta">@include('sm.partials.activity-card-daychips')</span>
            </div>
            <div class="activity-card-badges">
                <span class="pill pill-{{ $a->priority }}">{{ ucfirst($a->priority) }}</span>
                @if($a->activityType === 'reminder_checklist')
                    @foreach($cardLots as $lot)
                        <span class="item-tag lot-tag"
                              data-lot-id="{{ $lot->id }}"
                              data-lot-name="{{ $lot->lotName }}"
                              data-lot-variety="{{ $lot->variety ?? '' }}"
                              style="background: hsl({{ ($lot->id * 137) % 360 }}, 55%, 40%)">{{ $lot->lotName }}@isset($lotDaySuffix[$lot->id])<span class="lot-tag-das">{{ $lotDaySuffix[$lot->id] }}</span>@endisset</span>
                    @endforeach
                @endif
                @if($a->activityType === 'irrigation')
                    @php $wtm = $a->waterTaskMeta(); @endphp
                    <span class="badge water-task-badge" style="--wt:{{ $wtm['color'] }}">
                        <svg class="w-3.5 h-3.5" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" d="M12 3s6 6.686 6 11a6 6 0 11-12 0c0-4.314 6-11 6-11z"/></svg>
                        {{ $wtm['label'] }}
                    </span>
                @elseif($a->activityType === 'service')
                    @php $svcPriceText = $a->servicePrice !== null ? \App\Support\Region::symbol() . number_format((float) $a->servicePrice, 2) : ''; @endphp
                    <span class="badge service-badge">
                        <svg class="w-3.5 h-3.5" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" d="M11 5a4 4 0 105.03 5.03l4.35 4.35a2 2 0 11-2.83 2.83l-4.35-4.35A4 4 0 0111 5zM5 19l4-4"/></svg>
                        Service <span class="item-tag-price">{{ $svcPriceText }}</span>
                    </span>
                @elseif($a->activityType === 'worker_payroll')
                    {{-- Nothing here: the tag sits up beside the kebab, where
                         there is room, rather than alone on a row of its own. --}}
                @elseif($a->activityType === 'reminder_checklist')
                    {{-- Nothing here either: this card names itself in the slot
                         above, where other cards name their lot. The client
                         renderer has always left it out; this branch is what
                         kept the two from matching. --}}
                @elseif($typeLabel)
                    <span class="badge badge-green activity-type-badge">{{ $typeLabel }}</span>
                @endif
                @if($a->isDayZero)
                    @php $dzMode = ($cardLots->count() && $cardLots->every(fn ($l) => $l->dayType === 'DAP')) ? 'DAP' : 'DAS'; @endphp
                    <span class="badge day-zero-badge" title="This activity's start date becomes {{ $dzMode }} 0 for every lot it covers">
                        <svg class="w-3 h-3" viewBox="0 0 20 20" fill="currentColor"><path d="M9.049 2.927c.3-.921 1.603-.921 1.902 0l1.07 3.292a1 1 0 00.95.69h3.462c.969 0 1.371 1.24.588 1.81l-2.8 2.034a1 1 0 00-.364 1.118l1.07 3.292c.3.921-.755 1.688-1.54 1.118l-2.8-2.034a1 1 0 00-1.175 0l-2.8 2.034c-.784.57-1.838-.197-1.539-1.118l1.07-3.292a1 1 0 00-.364-1.118L2.98 8.72c-.783-.57-.38-1.81.588-1.81h3.461a1 1 0 00.951-.69l1.07-3.292z"/></svg>
                        {{ $dzMode }} 0
                    </span>
                @endif
                @if($a->isTransplant)
                    <span class="badge transplant-badge" title="Transplant day — starts a fresh DAT counter for every lot it covers">
                        <svg class="w-3 h-3" viewBox="0 0 20 20" fill="currentColor"><path d="M9.049 2.927c.3-.921 1.603-.921 1.902 0l1.07 3.292a1 1 0 00.95.69h3.462c.969 0 1.371 1.24.588 1.81l-2.8 2.034a1 1 0 00-.364 1.118l1.07 3.292c.3.921-.755 1.688-1.54 1.118l-2.8-2.034a1 1 0 00-1.175 0l-2.8 2.034c-.784.57-1.838-.197-1.539-1.118l1.07-3.292a1 1 0 00-.364-1.118L2.98 8.72c-.783-.57-.38-1.81.588-1.81h3.461a1 1 0 00.951-.69l1.07-3.292z"/></svg>
                        DAT 0
                    </span>
                @endif
                @if($isRange)
                    <span class="badge badge-gray range-badge" title="Multi-day range">&rarr; {{ $endC->format('M j') }} ({{ $rangeDays }}d)</span>
                @endif
                <span class="badge badge-gray hide-activity-tag" @if(!$a->isHidden) style="display:none;" @endif>Hidden</span>
            </div>
            {{-- The variety lives here, below the title, as a regular tag. The
                 day count that used to sit beside it has moved up on to the
                 lot's own chip, where the question is asked. The box always
                 renders when there are lots so the JS refresh can repopulate
                 it after a move; :empty hides it when blank. --}}
            @if ($cardLots->count())
                @php $lotMultiMeta = $cardLots->count() > 1; @endphp
                <div class="activity-card-lots activity-card-lotmeta">
                    @foreach ($cardLots as $lot)
                        @if (! empty($lot->variety))
                            <span class="item-tag lot-meta-tag">{{ $lotMultiMeta ? $lot->lotName . ' · ' : '' }}{{ $lot->variety }}</span>
                        @endif
                    @endforeach
                </div>
            @endif
            </div>
        </div>
        <div class="flex items-center shrink-0">
            @if($a->activityType === 'worker_payroll')
                <span class="badge payroll-badge mr-1">Worker checklist</span>
            @endif
            <div class="hidden md:flex items-center gap-0.5 done-hide">
                <button type="button" class="icon-btn hide-activity-toggle{{ $lockCls }}" data-id="{{ $a->id }}" @disabled(! $mayEdit) title="{{ $editTitle('Toggle visibility in presentations and exports') }}" aria-pressed="{{ $a->isHidden ? 'true' : 'false' }}">
                    <svg class="w-4 h-4" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" d="M15 12a3 3 0 11-6 0 3 3 0 016 0z"/><path stroke-linecap="round" stroke-linejoin="round" d="M2.458 12C3.732 7.943 7.523 5 12 5c4.478 0 8.268 2.943 9.542 7-1.274 4.057-5.064 7-9.542 7-4.477 0-8.268-2.943-9.542-7z"/></svg>
                </button>
                <button type="button" class="icon-btn edit-activity-btn{{ $lockCls }}" data-id="{{ $a->id }}" @disabled(! $mayEdit) title="{{ $editTitle('Edit') }}">
                    <svg class="w-4 h-4" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" d="M11 5H6a2 2 0 00-2 2v11a2 2 0 002 2h11a2 2 0 002-2v-5m-1.414-9.414a2 2 0 112.828 2.828L11.828 15H9v-2.828l8.586-8.586z"/></svg>
                </button>
                {{-- The JS twin has always drawn this one; the server render did
                     not, so a freshly-loaded board was missing a button that
                     appeared the moment anything re-rendered. --}}
                <button type="button" class="icon-btn tag-activity-btn{{ $lockCls }}" data-id="{{ $a->id }}" data-name="{{ $a->activityTitle }}" @disabled(! $mayEdit) title="{{ $editTitle('Tag a drawing, map or note') }}">
                    <svg class="w-4 h-4" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" d="M7 7h.01M3 11V4a1 1 0 011-1h7l9 9-8 8-9-9z"/></svg>
                </button>
                <button type="button" class="icon-btn duplicate-activity-btn{{ $lockCls }}" data-id="{{ $a->id }}" data-name="{{ $a->activityTitle }}" @disabled(! $mayEdit) title="{{ $editTitle('Duplicate') }}">
                    <svg class="w-4 h-4" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" d="M8 16H6a2 2 0 01-2-2V6a2 2 0 012-2h8a2 2 0 012 2v2m-6 12h8a2 2 0 002-2v-8a2 2 0 00-2-2h-8a2 2 0 00-2 2v8a2 2 0 002 2z"/></svg>
                </button>
                <button type="button" class="icon-btn to-draft-activity-btn{{ $lockCls }}" data-id="{{ $a->id }}" data-name="{{ $a->activityTitle }}" @disabled(! $mayEdit) title="{{ $editTitle('Move to drafts (hide without deleting)') }}">
                    <svg class="w-4 h-4" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" d="M5 8h14M5 8a2 2 0 110-4h14a2 2 0 110 4M5 8v10a2 2 0 002 2h10a2 2 0 002-2V8m-9 4h4"/></svg>
                </button>
                <button type="button" class="icon-btn icon-btn-danger delete-activity-btn{{ $lockCls }}" data-id="{{ $a->id }}" data-name="{{ $a->activityTitle }}" @disabled(! $mayEdit) title="{{ $editTitle('Delete') }}">
                    <svg class="w-4 h-4" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" d="M3 6h18M19 6v14a2 2 0 01-2 2H7a2 2 0 01-2-2V6M8 6V4a2 2 0 012-2h4a2 2 0 012 2v2M10 11v6M14 11v6"/></svg>
                </button>
            </div>
        </div>
    </div>
    @if($a->description)
        <div class="activity-description-content text-sm text-gray-700 mt-2" data-lightbox>{!! $a->description !!}</div>
    @endif
    @php $cardImages = $a->imageList(); @endphp
    @if(count($cardImages))
        <div class="activity-card-images mt-2" data-lightbox>
            @foreach($cardImages as $img)
                @if (($img['kind'] ?? 'image') === 'audio')
                    {{-- A voice note is a chip that unfolds its player. --}}
                    <button type="button" class="act-voice-chip" data-audio-url="{{ $img['url'] }}">
                        <img src="{{ asset('images/voice-recorder.png') }}" alt="" style="width:.95rem;height:.95rem;object-fit:contain"> Voice note
                    </button>
                @elseif (($img['kind'] ?? 'image') === 'video')
                    {{-- A clip plays where it sits. Pointing an <img> at an
                         .mp4 is what a broken-image glyph is made of. --}}
                    <video src="{{ $img['url'] }}" controls playsinline preload="metadata"></video>
                @else
                    <img src="{{ $img['url'] }}" alt="Reference image" loading="lazy">
                @endif
            @endforeach
        </div>
    @endif
    <div class="activity-meta">
        <span class="am-phone">@include('sm.partials.activity-card-daychips')</span>
        @foreach($a->items as $it)
            @php
                $itUnit = $it->displayUnit();
                $itQty = $it->quantity !== null ? rtrim(rtrim(number_format((float) $it->quantity, 4, '.', ''), '0'), '.') : null;
                $itQtyText = $itQty !== null ? ' ×' . $itQty . ($itUnit ? ' ' . $itUnit : '') : '';
                $itPriceText = $it->unitPrice !== null ? '@ ' . \App\Support\Region::symbol() . number_format((float) $it->unitPrice, 2) : '';
            @endphp
            <span class="item-tag material-tag">{{ $it->displayName() }}{{ $itQtyText }} <span class="item-tag-price">{{ $itPriceText }}</span></span>
        @endforeach
    </div>
    {{-- A task with a roster carries it on the card: tick who turned up
         without opening anything. Always for a payroll day, and for any other
         task whose author asked for one. Mirrors payrollChecklist() in the JS
         renderer — the two must produce the same markup. --}}
    @if ($a->hasWorkerChecklist() && $a->workers->count())
        @php $absent = \App\Models\AsScheduleAttendance::absentOn((int) $a->croppingScheduleId, (string) $a->targetDate?->toDateString()); @endphp
        @php
            // Who is allowed to move a tick on this card. Anyone who can edit
            // the plan can; a worker can move only their own, and only if the
            // task was set up to let them. `$meWorkerId` is the roster row
            // this login belongs to — null for an owner, who needs no row.
            $canTickAll = \App\Support\WorkerContext::canEdit();
            $meWorkerId = optional(\App\Support\WorkerContext::activeGrant())->scheduleWorkerId;
        @endphp
        <div class="act-check" data-att-date="{{ $a->targetDate?->toDateString() }}">
            @foreach ($a->workers as $w)
                @php
                    $here = ! in_array($w->id, $absent, true);
                    $mine = $meWorkerId && (int) $meWorkerId === (int) $w->id;
                    $mayTick = $canTickAll || ($mine && $a->workerSelfCheck);
                @endphp
                <label class="act-check-row{{ $here ? '' : ' is-out' }}{{ $mine ? ' is-me' : '' }}{{ $mayTick ? '' : ' is-locked' }}" data-att-worker="{{ $w->id }}">
                    <input type="checkbox" @checked($here) @disabled(! $mayTick)>
                    <span class="act-check-name">{{ $w->workerName }}@if ($a->targetDate && ! $w->isAvailableOn($a->targetDate))<span class="w-forced" title="Marked off this day">forced</span>@endif</span>
                    <span class="act-check-pay">{{ \App\Support\Region::money($a->workerPay($w)) }}</span>
                </label>
            @endforeach
            @php
                $due = $a->workers->reject(fn ($w) => in_array($w->id, $absent, true))
                    ->sum(fn ($w) => $a->workerPay($w));
            @endphp
            <div class="act-check-total">
                <span>To pay</span>
                <span data-att-total>{{ \App\Support\Region::money($due) }}</span>
            </div>
        </div>
    @endif

    {{-- The day's errands. Ticking one here is what makes its money real:
         the server writes an ordinary day expense or income row for it, so
         the day header and every report see it without knowing what a
         reminder is. Mirrors reminderChecklist() in the JS renderer. --}}
    @if ($a->activityType === 'reminder_checklist' && count($a->reminderList()))
        @php
            $rems = $a->reminderList();
            $remSpend = $a->reminderTotal('expense');
            $remEarn = $a->reminderTotal('income');
        @endphp
        <div class="act-rem" data-rem-activity="{{ $a->id }}">
            @foreach ($rems as $i => $r)
                {{-- Ticking an errand writes a money row, so it needs the same
                     right as any other change to the plan. A tick a viewer may
                     not make still shows, greyed — an inviting box that flips,
                     403s and flips back is worse than one that says "not
                     yours". Twin of reminderChecklist() in the JS renderer. --}}
                <label class="act-rem-row{{ $r['done'] ? ' is-done' : '' }}{{ $mayEdit ? '' : ' is-locked' }}" data-rem-index="{{ $i }}" title="{{ $editTitle('Tick this errand off') }}">
                    <input type="checkbox" @checked($r['done']) @disabled(! $mayEdit)>
                    <span class="act-rem-name">{{ $r['text'] }}</span>
                    @if ($r['kind'] !== 'none' && $r['amount'] > 0)
                        <span class="act-rem-amt is-{{ $r['kind'] }}">{{ $r['kind'] === 'income' ? '+' : '−' }}{{ \App\Support\Region::money($r['amount']) }}</span>
                    @endif
                </label>
            @endforeach
            @if ($remSpend > 0)
                <div class="act-rem-total"><span>To spend</span><span class="act-rem-amt is-expense">{{ \App\Support\Region::money($remSpend) }}</span></div>
            @endif
            @if ($remEarn > 0)
                <div class="act-rem-total"><span>To collect</span><span class="act-rem-amt is-income">{{ \App\Support\Region::money($remEarn) }}</span></div>
            @endif
        </div>
    @endif

    {{-- Things this activity points at. The row is always here so the JS can
         fill it after a tag is added; :empty keeps it out of the way. --}}
    <div class="activity-tags">
        @foreach ((is_array($a->tags) ? $a->tags : []) as $t)
            @php
                $kind = $t['kind'] ?? 'note';
                $ico = $kind === 'drawing'
                    ? 'M4 20l4-1L20 7a2 2 0 00-3-3L5 16l-1 4zM14 6l4 4'
                    : ($kind === 'map'
                        ? 'M9 20l-5-2V6l5 2m0 12l6-2m-6 2V8m6 10l5 2V8l-5-2m0 12V6M9 8l6-2'
                        : 'M9 12h6m-6 4h6m2 5H7a2 2 0 01-2-2V5a2 2 0 012-2h5.586a1 1 0 01.707.293l5.414 5.414a1 1 0 01.293.707V19a2 2 0 01-2 2z');
            @endphp
            <a class="act-tag" href="{{ $t['url'] ?? '#' }}" data-kind="{{ $kind }}" data-url="{{ $t['url'] ?? '' }}" title="Open this {{ $kind }}">
                <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path stroke-linecap="round" stroke-linejoin="round" d="{{ $ico }}"/></svg>
                {{ $t['label'] ?? ucfirst($kind) }}
            </a>
        @endforeach
    </div>
</div>
