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
    $searchText = trim(implode(' ', array_filter($searchBits)));
@endphp
<div class="activity-card prio-{{ $a->priority }}{{ $a->isHidden ? ' is-hidden' : '' }}{{ $a->isDone ? ' is-done' : '' }}" draggable="{{ $a->isDone ? 'false' : 'true' }}"
     data-id="{{ $a->id }}"
     data-is-done="{{ $a->isDone ? 1 : 0 }}"
     data-tags="{{ json_encode(is_array($a->tags) ? $a->tags : []) }}"
     data-labour="{{ $a->labourTotal() }}"
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
     @if($cardLots->count()) style="--lot-accent: hsl({{ ($cardLots->first()->id * 137) % 360 }}, 55%, 40%)" @endif>
    <div class="flex items-start justify-between gap-2">
        <div class="flex items-start gap-2.5 min-w-0 grow">
            <button type="button" class="done-check{{ $a->isDone ? ' is-checked' : '' }}" data-id="{{ $a->id }}"
                title="{{ $a->isDone ? 'Mark as not done (unlocks editing)' : 'Mark this activity as done' }}"
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
            <div class="min-w-0 grow">
            {{-- Lot(s) first, as a prominent label — so it's clear which lot the
                 activity is for before you read the title. --}}
            <div class="activity-card-lots activity-card-lothead">
                @if($cardLots->count())
                    @foreach($cardLots as $lot)
                        {{-- Auto colour per lot (golden-angle hue → distinct + stable). --}}
                        <span class="item-tag lot-tag"
                              data-lot-id="{{ $lot->id }}"
                              data-lot-name="{{ $lot->lotName }}"
                              data-lot-variety="{{ $lot->variety ?? '' }}"
                              style="background: hsl({{ ($lot->id * 137) % 360 }}, 55%, 40%)">{{ $lot->lotName }}</span>
                    @endforeach
                @elseif ($a->activityType !== 'worker_payroll')
                    {{-- A payroll day is about who turned up, not which field,
                         so "no lot" is its normal state rather than a gap. --}}
                    <span class="item-tag activity-na-tag" title="Applies generally — not tied to any specific lot">N/A — Not lot-specific</span>
                @endif
            </div>
            <h3 class="activity-card-title">{{ $a->activityTitle }}</h3>
            <div class="activity-card-badges">
                <span class="pill pill-{{ $a->priority }}">{{ ucfirst($a->priority) }}</span>
                @if($a->activityType === 'irrigation')
                    @php $wtm = $a->waterTaskMeta(); @endphp
                    <span class="badge water-task-badge" style="--wt:{{ $wtm['color'] }}">
                        <svg class="w-3.5 h-3.5" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" d="M12 3s6 6.686 6 11a6 6 0 11-12 0c0-4.314 6-11 6-11z"/></svg>
                        {{ $wtm['label'] }}
                    </span>
                @elseif($a->activityType === 'service')
                    @php $svcPriceText = $a->servicePrice !== null ? '₱' . number_format((float) $a->servicePrice, 2) : ''; @endphp
                    <span class="badge service-badge">
                        <svg class="w-3.5 h-3.5" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" d="M11 5a4 4 0 105.03 5.03l4.35 4.35a2 2 0 11-2.83 2.83l-4.35-4.35A4 4 0 0111 5zM5 19l4-4"/></svg>
                        Service <span class="item-tag-price">{{ $svcPriceText }}</span>
                    </span>
                @elseif($a->activityType === 'worker_payroll')
                    {{-- Nothing here: the tag sits up beside the kebab, where
                         there is room, rather than alone on a row of its own. --}}
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
            {{-- Variety + DAS live here, below the title, as regular tags. The box
                 always renders when there are lots so the JS DAS refresh can
                 repopulate it after a move; :empty hides it when blank. --}}
            @if ($cardLots->count())
                @php $lotMultiMeta = $cardLots->count() > 1; $lotTp = $lotTransplantEff ?? []; @endphp
                <div class="activity-card-lots activity-card-lotmeta">
                    @foreach ($cardLots as $lot)
                        @php
                            $parts = [];
                            if (! empty($lot->variety)) { $parts[] = $lot->variety; }
                            // Per-lot day counter: DAP lots are a single count; DAS
                            // lots flip to DAT on/after the transplant date.
                            if ($startC) {
                                $suffix = null;
                                $mode = ($lot->dayType === 'DAP') ? 'DAP' : 'DAS';
                                $z = $lotDayZeroEff[$lot->id] ?? null;
                                if ($mode === 'DAS') {
                                    $tp = $lotTp[$lot->id] ?? null;
                                    if ($tp && $startC->gte($tp)) {
                                        $datDelta = (int) $tp->diffInDays($startC, false);
                                        if ($datDelta === 0 && $z) {
                                            $dd = (int) $z->diffInDays($startC, false);
                                            $suffix = 'DAS' . ($dd > 0 ? '+' : '') . $dd . ' → DAT0';
                                        } else {
                                            $suffix = 'DAT' . ($datDelta > 0 ? '+' : '') . $datDelta;
                                        }
                                    } elseif ($z) {
                                        $delta = (int) $z->diffInDays($startC, false);
                                        $suffix = 'DAS' . ($delta > 0 ? '+' : '') . $delta;
                                    }
                                } elseif ($z) {
                                    $delta = (int) $z->diffInDays($startC, false);
                                    $suffix = 'DAP' . ($delta > 0 ? '+' : '') . $delta;
                                }
                                if ($suffix !== null) { $parts[] = $suffix; }
                            }
                        @endphp
                        @if (count($parts))
                            <span class="item-tag lot-meta-tag">{{ $lotMultiMeta ? $lot->lotName . ' · ' : '' }}{{ implode(' · ', $parts) }}</span>
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
            <button type="button" class="icon-btn add-note-activity-btn" data-id="{{ $a->id }}" data-name="{{ $a->activityTitle }}" title="Add a note (activity is locked)">
                <svg class="w-4 h-4" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" d="M9 12h6m-6 4h6m2 5H7a2 2 0 01-2-2V5a2 2 0 012-2h5.586a1 1 0 01.707.293l5.414 5.414a1 1 0 01.293.707V19a2 2 0 01-2 2z"/></svg>
            </button>
            <div class="hidden md:flex items-center gap-0.5 done-hide">
                <button type="button" class="icon-btn hide-activity-toggle" data-id="{{ $a->id }}" title="Toggle visibility in presentations and exports" aria-pressed="{{ $a->isHidden ? 'true' : 'false' }}">
                    <svg class="w-4 h-4" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" d="M15 12a3 3 0 11-6 0 3 3 0 016 0z"/><path stroke-linecap="round" stroke-linejoin="round" d="M2.458 12C3.732 7.943 7.523 5 12 5c4.478 0 8.268 2.943 9.542 7-1.274 4.057-5.064 7-9.542 7-4.477 0-8.268-2.943-9.542-7z"/></svg>
                </button>
                <button type="button" class="icon-btn edit-activity-btn" data-id="{{ $a->id }}" title="Edit">
                    <svg class="w-4 h-4" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" d="M11 5H6a2 2 0 00-2 2v11a2 2 0 002 2h11a2 2 0 002-2v-5m-1.414-9.414a2 2 0 112.828 2.828L11.828 15H9v-2.828l8.586-8.586z"/></svg>
                </button>
                <button type="button" class="icon-btn duplicate-activity-btn" data-id="{{ $a->id }}" data-name="{{ $a->activityTitle }}" title="Duplicate">
                    <svg class="w-4 h-4" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" d="M8 16H6a2 2 0 01-2-2V6a2 2 0 012-2h8a2 2 0 012 2v2m-6 12h8a2 2 0 002-2v-8a2 2 0 00-2-2h-8a2 2 0 00-2 2v8a2 2 0 002 2z"/></svg>
                </button>
                <button type="button" class="icon-btn to-draft-activity-btn" data-id="{{ $a->id }}" data-name="{{ $a->activityTitle }}" title="Move to drafts (hide without deleting)">
                    <svg class="w-4 h-4" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" d="M5 8h14M5 8a2 2 0 110-4h14a2 2 0 110 4M5 8v10a2 2 0 002 2h10a2 2 0 002-2V8m-9 4h4"/></svg>
                </button>
                <button type="button" class="icon-btn icon-btn-danger delete-activity-btn" data-id="{{ $a->id }}" data-name="{{ $a->activityTitle }}" title="Delete">
                    <svg class="w-4 h-4" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" d="M19 7l-.867 12.142A2 2 0 0116.138 21H7.862a2 2 0 01-1.995-1.858L5 7m5 4v6m4-6v6m1-10V4a1 1 0 00-1-1h-4a1 1 0 00-1 1v3M4 7h16"/></svg>
                </button>
            </div>
            <button type="button" class="icon-btn card-menu-btn md:hidden done-hide" data-id="{{ $a->id }}" data-name="{{ $a->activityTitle }}" title="Actions">
                <svg class="w-5 h-5" fill="currentColor" viewBox="0 0 24 24"><circle cx="12" cy="5" r="1.6"/><circle cx="12" cy="12" r="1.6"/><circle cx="12" cy="19" r="1.6"/></svg>
            </button>
        </div>
    </div>
    @if($a->description)
        <div class="activity-description-content text-sm text-gray-700 mt-2" data-lightbox>{!! $a->description !!}</div>
    @endif
    @php $cardImages = $a->imageList(); @endphp
    @if(count($cardImages))
        <div class="activity-card-images mt-2" data-lightbox>
            @foreach($cardImages as $img)
                <img src="{{ $img['url'] }}" alt="Reference image" loading="lazy">
            @endforeach
        </div>
    @endif
    <div class="activity-meta">
        @if ($a->activityType !== 'worker_payroll')
            <span class="meta-time">
                <svg class="w-3.5 h-3.5" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" d="M12 8v4l3 3m6-3a9 9 0 11-18 0 9 9 0 0118 0z"/></svg>
                {{ $timeLabel }}
            </span>
        @endif
        @if ($a->activityType !== 'worker_payroll')
            {{-- A payroll card lists every name in its checklist already;
                 repeating them here is the same roster twice. --}}
            @foreach($a->workers as $w)
                {{-- Someone down as off this day, put on the work anyway. The
                     flag stays on the name so the choice is visible later,
                     rather than the roster quietly disagreeing with the rules.
                     Mirrors renderActivityCard() in the JS renderer. --}}
                <span class="item-tag worker-tag">{{ $w->workerName }}@if ($a->targetDate && ! $w->isAvailableOn($a->targetDate))<span class="w-forced" title="Marked off this day — working anyway">forced</span>@endif</span>
            @endforeach
        @endif
        @foreach($a->items as $it)
            @php
                $itUnit = $it->displayUnit();
                $itQty = $it->quantity !== null ? rtrim(rtrim(number_format((float) $it->quantity, 4, '.', ''), '0'), '.') : null;
                $itQtyText = $itQty !== null ? ' ×' . $itQty . ($itUnit ? ' ' . $itUnit : '') : '';
                $itPriceText = $it->unitPrice !== null ? '@ ₱' . number_format((float) $it->unitPrice, 2) : '';
            @endphp
            <span class="item-tag material-tag">{{ $it->displayName() }}{{ $itQtyText }} <span class="item-tag-price">{{ $itPriceText }}</span></span>
        @endforeach
    </div>
    {{-- A payroll day carries its roster on the card: tick who turned up
         without opening anything. Mirrors payrollChecklist() in the JS
         renderer — the two must produce the same markup. --}}
    @if ($a->activityType === 'worker_payroll' && $a->workers->count())
        @php $absent = \App\Models\AsScheduleAttendance::absentOn((int) $a->croppingScheduleId, (string) $a->targetDate?->toDateString()); @endphp
        <div class="act-check" data-att-date="{{ $a->targetDate?->toDateString() }}">
            @foreach ($a->workers as $w)
                @php $here = ! in_array($w->id, $absent, true); @endphp
                <label class="act-check-row{{ $here ? '' : ' is-out' }}" data-att-worker="{{ $w->id }}">
                    <input type="checkbox" @checked($here)>
                    <span class="act-check-name">{{ $w->workerName }}@if ($a->targetDate && ! $w->isAvailableOn($a->targetDate))<span class="w-forced" title="Marked off this day">forced</span>@endif</span>
                    <span class="act-check-pay">₱{{ number_format($a->workerPay($w), 2) }}</span>
                </label>
            @endforeach
            @php
                $due = $a->workers->reject(fn ($w) => in_array($w->id, $absent, true))
                    ->sum(fn ($w) => $a->workerPay($w));
            @endphp
            <div class="act-check-total">
                <span>To pay</span>
                <span data-att-total>₱{{ number_format($due, 2) }}</span>
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
                <label class="act-rem-row{{ $r['done'] ? ' is-done' : '' }}" data-rem-index="{{ $i }}">
                    <input type="checkbox" @checked($r['done'])>
                    <span class="act-rem-name">{{ $r['text'] }}</span>
                    @if ($r['kind'] !== 'none' && $r['amount'] > 0)
                        <span class="act-rem-amt is-{{ $r['kind'] }}">{{ $r['kind'] === 'income' ? '+' : '−' }}₱{{ number_format($r['amount'], 2) }}</span>
                    @endif
                </label>
            @endforeach
            @if ($remSpend > 0)
                <div class="act-rem-total"><span>To spend</span><span class="act-rem-amt is-expense">₱{{ number_format($remSpend, 2) }}</span></div>
            @endif
            @if ($remEarn > 0)
                <div class="act-rem-total"><span>To collect</span><span class="act-rem-amt is-income">₱{{ number_format($remEarn, 2) }}</span></div>
            @endif
        </div>
    @endif

    {{-- What the labour on this activity costs, per worker and in total.
         Mirrors labourLine() in the JS renderer. --}}
    @php $labour = $a->labourTotal(); @endphp
    {{-- A payroll card already lists every name and figure in its checklist;
         repeating them underneath is the same information twice. --}}
    @if ($labour > 0 && $a->activityType !== 'worker_payroll')
        <div class="activity-labour">
            <span class="al-total">₱{{ number_format($labour, 2) }}</span>
            <span class="al-parts">{{ $a->workers->map(function ($w) use ($a) {
                $part = $a->dayPartFor($w);
                $len = $part === 'half' ? '½d' : ($part === 'whole' ? '1d' : '—');
                return $w->workerName . ' ' . $len . ' ₱' . number_format($a->workerPay($w), 2);
            })->join(' · ') }}</span>
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
