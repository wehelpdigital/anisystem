{{-- The day-length chip and the roster — one chunk worn twice: beside the
     title from md up (.act-inline-meta) and in the phone's meta strip
     (.am-phone). Mirrors dayWorkerChips in the JS renderer. --}}
{{-- Half day / whole day answers "how much of a day does this take" —
     a question a list of errands does not have. --}}
@if (! in_array($a->activityType, ['worker_payroll', 'reminder_checklist'], true))
    <span class="meta-time">
        <svg class="w-3.5 h-3.5" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" d="M12 8v4l3 3m6-3a9 9 0 11-18 0 9 9 0 0118 0z"/></svg>
        {{ $timeLabel }}
    </span>
@endif
@if (! $a->hasWorkerChecklist())
    {{-- A card with a checklist lists every name in it already; repeating
         them here is the same roster twice. --}}
    @foreach($a->workers as $w)
        {{-- Someone down as off this day, put on the work anyway. The flag
             stays on the name so the choice is visible later, rather than
             the roster quietly disagreeing with the rules. --}}
        <span class="item-tag worker-tag">{{ $w->workerName }}@if ($a->targetDate && ! $w->isAvailableOn($a->targetDate))<span class="w-forced" title="Marked off this day — working anyway">forced</span>@endif</span>
    @endforeach
@endif
