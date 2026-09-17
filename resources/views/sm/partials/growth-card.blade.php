{{-- ONE LOT'S CARD on the Growth Stages page: the crop, its age, the stage
     read at the realigned day, the tips, the timeline and the Realign block.
     Drawn by the page for every lot, and by GrowthStageController::card()
     for one lot when Anee's reading lands, so the card is swapped in place
     instead of the page reloading. Expects $r (a rowsFor() row) and $schedule. --}}
    <div class="gr-card" data-lot="{{ $r['lot']->id }}">
        <div class="gr-top" title="Tap to fold or open this lot">
            <svg class="gr-chev" fill="none" stroke="currentColor" stroke-width="2.5" viewBox="0 0 24 24" aria-hidden="true"><path stroke-linecap="round" stroke-linejoin="round" d="M9 5l7 7-7 7"/></svg>
            <span class="gr-emoji">{{ $r['icon'] }}</span>
            <span class="min-w-0">
                <span class="gr-lot block">{{ $r['lot']->lotName }}</span>
                <span class="gr-crop">{{ $r['cropLabel'] ?: 'No crop set' }}</span>
                {{-- Which ruler this lot is read against, because the same crop
                     on the next block may be read against another one. --}}
                <span class="gr-mode">{{ \App\Http\Controllers\Manager\GrowthStageController::counterSays($r['lot']->dayType) }}</span>
                {{-- The stage, said as a chip in its band's colour — open or
                     folded, the header names where the crop is. --}}
                <span class="gr-stage-chip">{{ $r['blocked'] ? 'Not readable yet' : ($r['stage']['label'] ?? '') }}</span>
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
                {{-- Realign by Anee: filled by the shared renderer below, so
                     the block here is the one the board's sheet draws. --}}
                @unless ($r['isTree'])
                    <div data-grx-mount data-lot-id="{{ $r['lot']->id }}" data-lot-name="{{ $r['lot']->lotName }}" data-realign='@json($r['realign'])'
                         data-calendar="{{ $r['blocked'] ? '' : trim(($r['age']['counter'] ?? '') . ' ' . ($r['age']['day'] ?? '') . ' — ' . ($r['stage']['label'] ?? '')) }}"></div>
                @endunless
            @endif
        </div>
        </div></div>
    </div>
