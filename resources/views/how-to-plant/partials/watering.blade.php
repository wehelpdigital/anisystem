{{-- Watering (2026-10-19, the owner: "in how to plant also include watering
     recommendations and schedules"): how farmers here water the crop, a
     schedule by stage on the guide's own day count, the stages that must never
     go dry, the seasons, and the signs of too little and too much water side by
     side. Drawn after a way's steps; HowToPlant::watering gives the way's own,
     else the guide's. Expects $water, $w (the way), $months, $crop. Styles live
     in how-to-plant/index; the steps' water chips jump to these rows. --}}
@php
    $H = \App\Support\HowToPlant::class;
    $T = \App\Support\Tagalog::class;
    $wid = 'hgw-' . $crop . '-' . $w['key'];
    $sched = array_values(array_filter((array) ($water['schedule'] ?? []), 'is_array'));
    $crit = array_values(array_filter((array) ($water['critical'] ?? []), fn ($c) => trim((string) $c) !== ''));
    $signs = (array) ($water['signs'] ?? []);
    $seasons = array_values(array_filter([
        ['rainfed', 'Without irrigation', 'rainfed', 'M7 15a4 4 0 01-.9-7.9A5.5 5.5 0 0116.6 8 4 4 0 1117 16H7zm1 3l-1 2m5-2l-1 2m5-2l-1 2'],
        ['dryseason', 'In the dry season', null, 'M12 4V2m0 20v-2m8-8h2M2 12h2m13.7-5.7l1.4-1.4M4.9 19.1l1.4-1.4m0-11.4L4.9 4.9m14.2 14.2l-1.4-1.4M16 12a4 4 0 11-8 0 4 4 0 018 0z'],
        ['wet', 'In the rainy season', null, 'M4 14a8 8 0 0116 0H4zm8 0v5a2 2 0 01-4 0M12 4v2'],
    ], fn ($x) => trim((string) ($water[$x[0]] ?? '')) !== ''));
    $drop = 'M12 3c3.5 4.5 6 8 6 11a6 6 0 01-12 0c0-3 2.5-6.5 6-11z';
    $warnIc = '<svg fill="none" stroke="currentColor" stroke-width="2.2" viewBox="0 0 24 24" aria-hidden="true"><path stroke-linecap="round" stroke-linejoin="round" d="M12 9v4m0 4h.01M10.3 3.9L2.4 17.5A2 2 0 004.1 20.5h15.8a2 2 0 001.7-3L13.7 3.9a2 2 0 00-3.4 0z"/></svg>';
@endphp
<section class="hw" id="{{ $wid }}" aria-labelledby="{{ $wid }}-h">
    <div class="hw-head">
        <span class="hw-ic" aria-hidden="true"><svg fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" d="{{ $drop }}"/><path stroke-linecap="round" d="M9.5 14.5a2.6 2.6 0 002.5 2.5"/></svg></span>
        <div>
            <h3 class="hg-h" id="{{ $wid }}-h">Watering{{ $T::flag('watering-schedule') }}</h3>
            <p>When to water and how much, stage by stage.</p>
        </div>
    </div>

    @if (trim((string) ($water['traditional'] ?? '')) !== '' || trim((string) ($water['method'] ?? '')) !== '')
        <div class="hw-trad">
            @if (trim((string) ($water['traditional'] ?? '')) !== '')<p>{{ $water['traditional'] }}</p>@endif
            @if (trim((string) ($water['method'] ?? '')) !== '')<p class="hw-method"><b>How it is done.</b> {{ $water['method'] }}</p>@endif
        </div>
    @endif

    @if ($crit)
        <div class="hw-crit">
            <b>Never let it go dry{{ $T::flag('critical-stage') }}</b>
            <ul>
                @foreach ($crit as $c)
                    <li>{!! $warnIc !!}<span>{{ $c }}</span></li>
                @endforeach
            </ul>
        </div>
    @endif

    @if ($sched)
        <ol class="hw-sched" aria-label="The watering schedule">
            @foreach ($sched as $i => $st)
                @php $isCrit = $H::critical($water, $st); @endphp
                <li class="hw-st {{ $isCrit ? 'is-crit' : '' }}" id="{{ $wid }}-{{ $i }}">
                    <span class="hw-dot" aria-hidden="true"></span>
                    <div class="hw-st-h">
                        <b>{{ $st['stage'] }}</b>
                        <span class="hw-range">{{ $H::spanWords((int) $st['from'], (int) $st['to'], $w, $months) }}</span>
                        @if ($isCrit)<span class="hw-flag">{!! $warnIc !!}Must not dry out</span>@endif
                    </div>
                    @if (! empty($st['often']) || ! empty($st['amount']))
                        <dl class="hw-st-g">
                            @if (! empty($st['often']))<div><dt>How often</dt><dd>{{ $st['often'] }}</dd></div>@endif
                            @if (! empty($st['amount']))<div><dt>How much</dt><dd>{{ $st['amount'] }}</dd></div>@endif
                        </dl>
                    @endif
                    @if (! empty($st['why']))<p class="hw-why">{{ $st['why'] }}</p>@endif
                </li>
            @endforeach
        </ol>
    @endif

    @if ($seasons)
        <div class="hw-seasons" style="--n: {{ count($seasons) }}">
            @foreach ($seasons as [$sk, $sLabel, $sTl, $sIcon])
                <div class="hw-season">
                    <h4><svg fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24" aria-hidden="true"><path stroke-linecap="round" stroke-linejoin="round" d="{{ $sIcon }}"/></svg>{{ $sLabel }}@if ($sTl){{ $T::flag($sTl) }}@endif</h4>
                    <p>{{ $water[$sk] }}</p>
                </div>
            @endforeach
        </div>
    @endif

    @if (trim((string) ($signs['dry'] ?? '')) !== '' || trim((string) ($signs['wet'] ?? '')) !== '')
        <div class="hw-signs">
            @if (trim((string) ($signs['dry'] ?? '')) !== '')
                <div class="hw-sign is-dry">
                    <b><svg fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24" aria-hidden="true"><path stroke-linecap="round" stroke-linejoin="round" d="M12 21V11m0 0c0-3-1.5-5-5-6 0 3 1 5.5 5 6zm0 0c1-2.5 3-4 6-4.5-.5 3-2 5-6 5.5M5 21h14"/></svg>Too little water</b>
                    <p>{{ $signs['dry'] }}</p>
                </div>
            @endif
            @if (trim((string) ($signs['wet'] ?? '')) !== '')
                <div class="hw-sign is-wet">
                    <b><svg fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24" aria-hidden="true"><path stroke-linecap="round" stroke-linejoin="round" d="M3 17c1.5 1.3 3 1.3 4.5 0s3-1.3 4.5 0 3 1.3 4.5 0 3-1.3 4.5 0M3 12.5c1.5 1.3 3 1.3 4.5 0s3-1.3 4.5 0 3 1.3 4.5 0 3-1.3 4.5 0M12 3c1.6 2 2.5 3.4 2.5 4.6a2.5 2.5 0 01-5 0C9.5 6.4 10.4 5 12 3z"/></svg>Too much water</b>
                    <p>{{ $signs['wet'] }}</p>
                </div>
            @endif
        </div>
    @endif

    @if (! empty($water['better']))
        <div class="hw-better">
            <b>Water better for a higher yield</b>
            <ul>
                @foreach ($water['better'] as $bl)
                    <li><span class="hg-ok" aria-hidden="true"><svg fill="none" stroke="currentColor" stroke-width="3" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" d="M5 13l4 4L19 7"/></svg></span><p>{{ $bl }}</p></li>
                @endforeach
            </ul>
        </div>
    @endif
</section>
