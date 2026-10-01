{{-- One task card on a film's activities board, drawn the way the app's
     board draws it (the hero's board and the board band both use it).
     Expects $task (a HowItWorks::phone() task) and $k (its index), and the
     journey's $hwB, $hwPrio and $hwI. --}}
@php
    [$ty, $pr, $tn, $tl, $tw, $th] = $task;
    [$cl, $cw, $cn] = $hwB['cards'][$k];
@endphp
<div class="hwa-c" data-k="{{ $k }}" style="--p: {{ $hwPrio[$pr] }}">
    <div class="hwa-btns">
        <span class="ck">{!! $hwI('M5 13l4 4L19 7', 3) !!}</span>
        <span class="ty"><svg fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24" aria-hidden="true"><rect x="6" y="4" width="12" height="17" rx="2"/><path stroke-linecap="round" stroke-linejoin="round" d="M9 4V3h6v1M9 13l2 2 4-4"/></svg></span>
        <span class="st"><svg fill="none" stroke="currentColor" stroke-width="2" stroke-linejoin="round" viewBox="0 0 24 24" aria-hidden="true"><path d="m12 3.4 2.63 5.33 5.88.86-4.25 4.15 1 5.86L12 16.85l-5.26 2.75 1-5.86-4.25-4.15 5.88-.86z"/></svg></span>
        <span class="dd">{!! $hwI('M8 7l-4 4 4 4M16 7l4 4-4 4M4 11h16', 2.4) !!}</span>
        <span class="mn">{!! $hwI('M4 6h16M4 12h16M4 18h16') !!}</span>
        <span class="fd">{!! $hwI('M18 15l-6-6-6 6', 2.2) !!}</span>
    </div>
    <div class="hwa-tl"><b>{{ $tn }}</b><span class="hwa-lot"><svg viewBox="0 0 24 24" fill="currentColor" aria-hidden="true"><path d="M12 2C8.13 2 5 5.13 5 9c0 5.25 7 13 7 13s7-7.75 7-13c0-3.87-3.13-7-7-7zm0 9.5a2.5 2.5 0 110-5 2.5 2.5 0 010 5z"/></svg>{{ $tl }}<i>{{ $hwB['counter'] }} {{ $hwB['das'][$tl] ?? $hwB['day'] }}</i></span></div>
    <div class="hwa-bd"><span class="{{ $pr }}">{{ $pr }}</span><span>{{ $cl }}</span>@if ($cw)<span class="w">{!! $hwI('M12 3s6 6.686 6 11a6 6 0 11-12 0c0-4.314 6-11 6-11z') !!}{{ $cw }}</span>@endif</div>
    <p>{{ $cn }}</p>
    @if ($th)<span class="hwa-tm">{!! $hwI('M12 8v4l3 3m6-3a9 9 0 11-18 0 9 9 0 0118 0z') !!}{{ $th }}</span>@endif
</div>
