{{-- Between steps 3 and 4 (HowItWorks::board): a phone on the season's
     activities board, the way the app draws it. A note goes on today (the
     day's menu, then the note editor), a task is held and dragged to
     tomorrow, one is ticked done, then the Modules menu opens Growth Stages,
     where each lot's stage, tips and timeline are the app's own. Played by
     boardFilm() in the journey script; the notes beside the phone light as
     each part happens. Uses the journey's $hwB, $hwI, $hwDay, $hwDays and
     $hwTopR. --}}
@php
    $hb = \App\Support\HowItWorks::board();
    $hbOpen = array_key_first($hb['lots']);
@endphp
<section class="hw-band is-flip" id="hw-board" aria-labelledby="hw-board-h">
    <svg class="hw-band-svg" aria-hidden="true"></svg>
    <div class="hw-band-copy">
        <p class="hw-step">Every day <span>on the board</span></p>
        <{{ $hwHead }} class="hw-h2" id="hw-board-h">Work the season on the board</{{ $hwHead }}>
        <p class="hw-sub">Add a note as you go, drag a task to another day when the weather turns, tick it done, and check the stage each lot is in.</p>
    </div>
    <div class="hw-band-phone" aria-hidden="true">
        <div class="hw-phone2"><div class="hw-scr2"><div class="hwr">
            {{-- The board. --}}
            <div class="hw-scene s-acts s-board is-on">
                <div class="hwr-top">
                    <span class="hwr-back">{!! $hwI('M15 19l-7-7 7-7', 2.4) !!}</span>
                    <div><b>Activities</b><small>{{ $hwPhone['season'] }}</small></div>
                    {!! $hwTopR !!}
                </div>
                @include('public.how.act-tools')
                <div class="hwr-view"><div class="hwr-list">
                    <div class="hwa-ver">
                        <span class="sel">{!! $hwI('M12 3l9 5-9 5-9-5 9-5zM3 13l9 5 9-5') !!}Original{!! $hwI('M6 9l6 6 6-6', 2.4) !!}</span>
                        <span>{!! $hwI('M12 4v11m0 0l-4-4m4 4l4-4M5 20h14') !!}</span>
                        <span class="add">{!! $hwI('M12 5v14M5 12h14', 2.6) !!}</span>
                        <span class="ai"><img src="{{ asset('images/anee/avatar-160.jpg') }}" alt=""></span>
                    </div>
                    <div class="hwa-g" style="--c: #4a90e2">{!! $hwDay($hwDays[0], 1) !!}</div></div>
                    {{-- Today: the note lands at its top, and its tasks. --}}
                    <div class="hwa-g is-today is-open hwb-today" style="--c: #3fb468">
                        {!! $hwDay($hwDays[1], count($hwPhone['tasks'])) !!}<i class="br"></i><span class="wx">⛅ <i>{{ $hwPhone['place'] }}</i> <b>{{ $hwPhone['days'][0][2] }}°</b></span></div>
                        <div class="hwa-b"><div><div class="hwa-in">
                            <div class="hwb-memo is-shut">
                                <span class="tag">{!! $hwI('M7 3h10a2 2 0 012 2v14a2 2 0 01-2 2H7a2 2 0 01-2-2V5a2 2 0 012-2zM9 8h6M9 12h6M9 16h3') !!}Note</span>
                                <b>{{ $hb['note'][0] }}</b>
                                <p>{{ $hb['note'][1] }}</p>
                                <svg class="kb" viewBox="0 0 24 24" fill="currentColor" aria-hidden="true"><circle cx="12" cy="5" r="1.9"/><circle cx="12" cy="12" r="1.9"/><circle cx="12" cy="19" r="1.9"/></svg>
                                <svg class="grip" viewBox="0 0 24 24" fill="currentColor" aria-hidden="true"><circle cx="9" cy="6" r="1.5"/><circle cx="15" cy="6" r="1.5"/><circle cx="9" cy="12" r="1.5"/><circle cx="15" cy="12" r="1.5"/><circle cx="9" cy="18" r="1.5"/><circle cx="15" cy="18" r="1.5"/></svg>
                            </div>
                            @foreach ($hwPhone['tasks'] as $k => $task)
                                @include('public.how.act-card', ['task' => $task, 'k' => $k])
                            @endforeach
                        </div></div></div>
                    </div>
                    {{-- Tomorrow: empty, until the scouting is dragged onto it. --}}
                    <div class="hwb-fold hwb-rest"><div>
                        <div class="hwa-rest">
                            {!! $hwI('M20.354 15.354A9 9 0 018.646 3.646 9.003 9.003 0 0012 21a9.003 9.003 0 008.354-5.646z') !!}
                            <div><b>{{ $hwDays[2]->format('l, F j, Y') }}</b><small>No activities scheduled</small></div>
                            <span>+ Add</span>
                        </div>
                    </div></div>
                    <div class="hwb-fold hwb-tmr is-shut"><div>
                        <div class="hwa-g is-open" style="--c: #9b6ad6">
                            {!! $hwDay($hwDays[2], 1) !!}</div>
                            <div class="hwa-b"><div><div class="hwa-in">
                                @include('public.how.act-card', ['task' => $hwPhone['tasks'][2], 'k' => 2])
                            </div></div></div>
                        </div>
                    </div></div>
                    <div class="hwa-g" style="--c: #f5a623">{!! $hwDay($hwDays[3], 2) !!}</div></div>
                </div></div>

                {{-- The day's menu (its ⋮). --}}
                <div class="hwb-sheet hwb-menu">
                    <div class="hwb-panel">
                        <span class="hwb-grab"></span>
                        <div class="hwb-sh"><b>{{ $hwDays[1]->format('l, F j') }}</b>{!! $hwI('M6 6l12 12M18 6L6 18', 2.2) !!}</div>
                        <div class="hwb-rows">
                            <span class="hwb-row p"><i>{!! $hwI('M3 8l7.89 4.26a2 2 0 002.22 0L21 8M5 19h14a2 2 0 002-2V7a2 2 0 00-2-2H5a2 2 0 00-2 2v10a2 2 0 002 2z') !!}</i>Email this date</span>
                            <span class="hwb-row p" data-row="note"><i>{!! $hwI('M15.5 20H7a2 2 0 01-2-2V5a2 2 0 012-2h6l4 4v3M9 8h3M9 12h3M17 15v5m2.5-2.5h-5') !!}</i>Add a note to this day</span>
                            <span class="hwb-row p"><i><svg fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24" aria-hidden="true"><path stroke-linecap="round" stroke-linejoin="round" d="M3 9a2 2 0 012-2h.9a2 2 0 001.7-.9l.8-1.2A2 2 0 0110.1 4h3.8a2 2 0 011.7.9l.8 1.2a2 2 0 001.7.9H19a2 2 0 012 2v9a2 2 0 01-2 2H5a2 2 0 01-2-2V9z"/><circle cx="12" cy="13" r="3"/></svg></i>Take a photo</span>
                            <span class="hwb-row p"><i>{!! $hwI('M15 10l4.55-2.28A1 1 0 0121 8.62v6.76a1 1 0 01-1.45.9L15 14M5 18h8a2 2 0 002-2V8a2 2 0 00-2-2H5a2 2 0 00-2 2v8a2 2 0 002 2z') !!}</i>Record a video</span>
                            <span class="hwb-row p"><i>{!! $hwI('M19 11a7 7 0 01-14 0m7 7v3m0-3a7 7 0 01-7-7m7 7a7 7 0 007-7M12 14a3 3 0 003-3V5a3 3 0 10-6 0v6a3 3 0 003 3z') !!}</i>Record a voice note</span>
                        </div>
                    </div>
                </div>
                {{-- The note editor. --}}
                <div class="hwb-sheet hwb-editor">
                    <div class="hwb-panel">
                        <span class="hwb-grab"></span>
                        <div class="hwb-sh"><b>Add a note</b>{!! $hwI('M6 6l12 12M18 6L6 18', 2.2) !!}</div>
                        <div class="hwb-ed">
                            <div class="hwb-media">
                                <span><svg fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24" aria-hidden="true"><path stroke-linecap="round" stroke-linejoin="round" d="M3 9a2 2 0 012-2h.9a2 2 0 001.7-.9l.8-1.2A2 2 0 0110.1 4h3.8a2 2 0 011.7.9l.8 1.2a2 2 0 001.7.9H19a2 2 0 012 2v9a2 2 0 01-2 2H5a2 2 0 01-2-2V9z"/><circle cx="12" cy="13" r="3"/></svg>Camera</span>
                                <span><svg fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24" aria-hidden="true"><rect x="3" y="4" width="18" height="16" rx="2"/><circle cx="9" cy="10" r="2"/><path stroke-linecap="round" stroke-linejoin="round" d="M21 16l-5-5-9 9"/></svg>Upload</span>
                                <span>{!! $hwI('M15 10l4.55-2.28A1 1 0 0121 8.62v6.76a1 1 0 01-1.45.9L15 14M5 18h8a2 2 0 002-2V8a2 2 0 00-2-2H5a2 2 0 00-2 2v8a2 2 0 002 2z') !!}Video</span>
                                <span><i class="rec"></i>Record</span>
                                <span>{!! $hwI('M19 11a7 7 0 01-14 0m7 7v3m0-3a7 7 0 01-7-7m7 7a7 7 0 007-7M12 14a3 3 0 003-3V5a3 3 0 10-6 0v6a3 3 0 003 3z') !!}Voice</span>
                                <span><i class="emo">😊</i>Emoji</span>
                            </div>
                            <p class="hwb-lab">Title <em>*</em></p>
                            <span class="hwr-in hwb-title"><span class="ph">e.g. Pump repair, west line</span><span class="v"></span><em></em></span>
                            <p class="hwb-cap">Which lot?</p>
                            <div class="hwb-chips">@foreach (array_keys($hwB['das']) as $ln)<span @if ($ln === $hbOpen) data-pick @endif>{{ $ln }}</span>@endforeach</div>
                            <p class="hwb-cap">About which task?</p>
                            <div class="hwb-chips">@foreach ($hwPhone['tasks'] as $task)<span>{{ $task[2] }}</span>@endforeach</div>
                            <div class="hwb-box">
                                <div class="hwb-tools"><span>Paragraph ⌄</span><b>B</b><i>I</i><u>U</u><s>S</s><span>☰</span><span>❝</span></div>
                                <div class="hwb-text"><span class="ph">Write your note</span><span class="v"></span><em></em></div>
                            </div>
                            <p class="hwb-hint">Photos and videos are made smaller on upload.</p>
                        </div>
                        <div class="hwb-foot"><span class="c">Cancel</span><span class="hwb-save">Save note</span></div>
                    </div>
                </div>
                {{-- The Modules menu. --}}
                <div class="hwb-sheet hwb-mods">
                    <div class="hwb-panel">
                        <span class="hwb-grab"></span>
                        <div class="hwb-sh"><b>Modules</b>{!! $hwI('M6 6l12 12M18 6L6 18', 2.2) !!}</div>
                        <div class="hwb-mview"><div class="hwb-rows hwb-mlist">
                            <span class="hwb-row back"><i>{!! $hwI('M15 19l-7-7 7-7', 2.4) !!}</i>All cropping schedules</span>
                            @foreach ([['Activities', 'thunder.png'], ['Settings', 'gear.png'], ['Lots', 'treasure-map.png'], ['Workers', 'tractor.png'], ['Inventory', 'sack.png'],
                                ['Documentation', 'document.png'], ['Observations', 'pencil.png'], ['Notes', 'sticky-note.png'], ['Tags', 'label.png'], ['Gallery', 'gallery.png'],
                                ['Growth Stages', 'plant.png'], ['Weather', 'weather.png'], ['Reports', 'pie-chart.png'], ['Chat Anee', null]] as [$ml, $mi])
                                <span class="hwb-row {{ $ml === 'Activities' ? 'on' : '' }}" @if ($ml === 'Growth Stages') data-row="growth" @endif>
                                    <i>@if ($mi)<img data-src="{{ asset('images/' . $mi) }}" alt="">@else<img class="face" src="{{ asset('images/anee/avatar-160.jpg') }}" alt="">@endif</i>{{ $ml }}
                                </span>
                            @endforeach
                        </div></div>
                    </div>
                </div>
            </div>

            {{-- Growth Stages: each lot's stage today, read from the app's crop tables. --}}
            <div class="hw-scene s-growth">
                <div class="hwr-top">
                    <span class="hwr-back">{!! $hwI('M15 19l-7-7 7-7', 2.4) !!}</span>
                    <div><b>Growth Stages</b><small>{{ $hwPhone['season'] }}</small></div>
                    {!! $hwTopR !!}
                </div>
                <div class="hwr-view"><div class="hwr-list">
                    <div class="hwg-pills" style="--n: 0"><span class="m">{!! $hwI('M4 6h16M4 12h16M4 18h16') !!}Modules</span><span class="on">Growth Stages</span></div>
                    <div class="hwg-date" style="--n: 1">Crop stage on <span>{!! $hwI('M8 7V3m8 4V3m-9 8h10M5 21h14a2 2 0 002-2V7a2 2 0 00-2-2H5a2 2 0 00-2 2v12a2 2 0 002 2z') !!}Today, {{ $hwToday->format('M j, Y') }}</span></div>
                    <span class="hwg-all" style="--n: 2">Collapse all</span>
                    @foreach ($hb['lots'] as $ln => $g)
                        <div class="hwg-card {{ $ln === $hbOpen ? 'is-open' : '' }}" style="--n: {{ $loop->index + 3 }}">
                            <div class="hwg-top">
                                {!! $hwI('M9 5l7 7-7 7', 2.5) !!}
                                <span class="e">{{ $hwPhone['crops'] }}</span>
                                <span class="l"><b>{{ $ln }}</b><span class="c">{{ $hwB['crop'] }}</span>@if ($ln === $hbOpen)<span class="md">{{ $hb['mode'] }}</span>@endif<span class="hwg-chip">{{ $g['stage'] }}</span></span>
                                <span class="hwg-age"><b>{{ $g['day'] }}</b><i>{{ $hwB['counter'] }}</i></span>
                            </div>
                            @if ($ln === $hbOpen)
                                <div class="hwg-body">
                                    <p class="hwg-stage">{{ $g['stage'] }}</p>
                                    <p class="hwg-what">{{ $g['what'] }}</p>
                                    @if ($g['progress'] !== null)<span class="hwg-bar"><i style="width: {{ $g['progress'] }}%"></i></span>@endif
                                    <p class="hwg-next">Day {{ $g['dayIn'] }} of this stage @if ($g['next'])· {{ $g['next'][0] }} in about {{ $g['next'][1] }} days @endif</p>
                                    @if (! $g['do'] && $g['needs'])
                                        <p class="hwg-needs"><b>What it usually needs:</b> {{ $g['needs'] }}</p>
                                    @endif
                                    @if ($g['do'] || $g['watch'])
                                        <div class="hwg-lists">
                                            @if ($g['do'])
                                                <div class="hwg-list hwg-do"><h4>{!! $hwI('M5 13l4 4L19 7', 2.5) !!}What to do now</h4><ul>@foreach ($g['do'] as $t)<li>{{ $t }}</li>@endforeach</ul></div>
                                            @endif
                                            @if ($g['watch'])
                                                <div class="hwg-list hwg-watch"><h4>{!! $hwI('M12 9v4m0 4h.01M10.3 3.9L1.8 18a2 2 0 001.7 3h17a2 2 0 001.7-3L13.7 3.9a2 2 0 00-3.4 0z', 2.5) !!}What to watch for</h4><ul>@foreach ($g['watch'] as $t)<li>{{ $t }}</li>@endforeach</ul></div>
                                            @endif
                                        </div>
                                    @endif
                                    <div class="hwg-steps">
                                        @foreach ($g['timeline'] as [$sl, $sf, $sNow, $sPast])
                                            <span class="hwg-step {{ $sNow ? 'is-now' : ($sPast ? 'is-past' : '') }}"><i></i>{{ $sl }}<em>{{ $hwB['counter'] }} {{ $sf }}+</em></span>
                                        @endforeach
                                    </div>
                                </div>
                            @endif
                        </div>
                    @endforeach
                    <p class="hwg-warn" style="--n: 6">{!! $hwI('M12 11v5m0-8h.01M21 12a9 9 0 11-18 0 9 9 0 0118 0z') !!}These stages come from the calendar, not the plant. Trust what you see in the field over this page.</p>
                </div></div>
            </div>
            <span class="hw-tap"></span>
        </div></div></div>
    </div>
    <div class="hw-band-notes" aria-hidden="true">
        @foreach ($hb['notes'] as $k => [$emo, $t, $s])
            <div class="hw-note" style="--k: {{ $k }}"><span>{{ $emo }}</span><div><b>{{ $t }}</b><small>{{ $s }}</small></div></div>
        @endforeach
    </div>
    <p class="sr-only">A phone shows the season's activities board. A note is added to today from the day's menu. The scouting task is held and dragged to tomorrow. The urea task is ticked done. Then the Modules menu opens Growth Stages, where {{ $hbOpen }} is in {{ $hb['lots'][$hbOpen]['stage'] }} at {{ $hwB['counter'] }} {{ $hb['lots'][$hbOpen]['day'] }}, with what to do now, what to watch for and the stages ahead.</p>
</section>
