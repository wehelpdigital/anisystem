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
<div class="activity-card prio-{{ $a->priority }}{{ $a->isHidden ? ' is-hidden' : '' }}" draggable="true"
     data-id="{{ $a->id }}"
     data-target-date="{{ $startC ? $startC->format('Y-m-d') : '' }}"
     data-target-end-date="{{ $endC ? $endC->format('Y-m-d') : '' }}"
     data-lot-signature="{{ $lotSig }}"
     data-sequence-order="{{ (int) $a->sequenceOrder }}"
     data-is-day-zero="{{ $a->isDayZero ? 1 : 0 }}"
     data-activity-type="{{ $a->activityType ?: '' }}"
     data-is-hidden="{{ $a->isHidden ? 1 : 0 }}"
     data-search="{{ $searchText }}">
    <div class="flex items-start justify-between gap-2">
        <div class="min-w-0 grow">
            <h3 class="activity-card-title">{{ $a->activityTitle }}</h3>
            <div class="activity-card-badges">
                <span class="pill pill-{{ $a->priority }}">{{ ucfirst($a->priority) }}</span>
                @if($typeLabel)
                    <span class="badge badge-green activity-type-badge">{{ $typeLabel }}</span>
                @endif
                @if($a->isDayZero)
                    <span class="badge day-zero-badge" title="This activity's start date becomes {{ $schedule->dayType }} 0 for every lot it covers">
                        <svg class="w-3 h-3" viewBox="0 0 20 20" fill="currentColor"><path d="M9.049 2.927c.3-.921 1.603-.921 1.902 0l1.07 3.292a1 1 0 00.95.69h3.462c.969 0 1.371 1.24.588 1.81l-2.8 2.034a1 1 0 00-.364 1.118l1.07 3.292c.3.921-.755 1.688-1.54 1.118l-2.8-2.034a1 1 0 00-1.175 0l-2.8 2.034c-.784.57-1.838-.197-1.539-1.118l1.07-3.292a1 1 0 00-.364-1.118L2.98 8.72c-.783-.57-.38-1.81.588-1.81h3.461a1 1 0 00.951-.69l1.07-3.292z"/></svg>
                        {{ $schedule->dayType }} 0
                    </span>
                @endif
                @if($isRange)
                    <span class="badge badge-gray range-badge" title="Multi-day range">&rarr; {{ $endC->format('M j') }} ({{ $rangeDays }}d)</span>
                @endif
                <span class="badge badge-gray hide-activity-tag" @if(!$a->isHidden) style="display:none;" @endif>Hidden</span>
            </div>
            <div class="activity-card-lots">
                @if($cardLots->count())
                    @foreach($cardLots as $lot)
                        @php
                            $dasSuffix = '';
                            $anchor = $lotDayZeroEff[$lot->id] ?? null;
                            if ($anchor && $startC) {
                                $delta = (int) $anchor->diffInDays($startC, false);
                                $dasSuffix = ' · ' . $schedule->dayType . ($delta > 0 ? '+' : '') . $delta;
                            }
                        @endphp
                        <span class="item-tag lot-tag"
                              data-lot-id="{{ $lot->id }}"
                              data-lot-name="{{ $lot->lotName }}"
                              data-lot-variety="{{ $lot->variety ?? '' }}">{{ $lot->lotName }}@if(!empty($lot->variety)) · {{ $lot->variety }}@endif{{ $dasSuffix }}</span>
                    @endforeach
                @else
                    <span class="item-tag activity-na-tag" title="Applies generally — not tied to any specific lot">N/A — Not lot-specific</span>
                @endif
            </div>
        </div>
        <div class="flex items-center shrink-0">
            <div class="hidden md:flex items-center gap-0.5">
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
            <button type="button" class="icon-btn card-menu-btn md:hidden" data-id="{{ $a->id }}" data-name="{{ $a->activityTitle }}" title="Actions">
                <svg class="w-5 h-5" fill="currentColor" viewBox="0 0 24 24"><circle cx="12" cy="5" r="1.6"/><circle cx="12" cy="12" r="1.6"/><circle cx="12" cy="19" r="1.6"/></svg>
            </button>
        </div>
    </div>
    @if($a->description)
        <div class="activity-description-content text-sm text-gray-700 mt-2">{!! $a->description !!}</div>
    @endif
    @if($a->imageUrl())
        <div class="activity-card-image mt-2"><img src="{{ $a->imageUrl() }}" alt="Reference image" loading="lazy"></div>
    @endif
    <div class="activity-meta">
        <span class="meta-time">
            <svg class="w-3.5 h-3.5" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" d="M12 8v4l3 3m6-3a9 9 0 11-18 0 9 9 0 0118 0z"/></svg>
            {{ $timeLabel }}
        </span>
        @foreach($a->workers as $w)
            <span class="item-tag worker-tag">{{ $w->workerName }}</span>
        @endforeach
        @foreach($a->items as $it)
            @if($it->itemType === 'material')
                <span class="item-tag material-tag">{{ $it->material->materialName ?? 'Material #'.$it->materialId }} &times;{{ rtrim(rtrim(number_format((float) $it->quantity, 4, '.', ''), '0'), '.') }} {{ $it->unitOfMeasure ?: ($it->material->unitOfMeasure ?? '') }}</span>
            @else
                <span class="item-tag service-tag">{{ $it->service->serviceName ?? 'Service #'.$it->serviceId }}</span>
            @endif
        @endforeach
    </div>
</div>
