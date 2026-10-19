{{-- One crop's How to Plant guide (2026-10-19, the owner). Drawn on the page
     and again when another crop is picked (HowToPlantController::guide).
     Expects $guide (App\Support\HowToPlant::guide) and $way (the way key
     open first). The disclaimer comes first, always. Styles live in
     how-to-plant/index. --}}
@php
    $H = \App\Support\HowToPlant::class;
    $T = \App\Support\Tagalog::class;
    $crop = (string) $guide['crop'];
    $name = (string) $guide['name'];
    $local = trim((string) ($guide['local'] ?? '')) ?: $name;
    $months = $H::perennial($crop);
    $icon = \App\Support\CropCatalog::CROPS[$crop]['icon'] ?? '🌱';
    $ways = array_values((array) $guide['ways']);
    $aka = array_values(array_filter((array) ($guide['aka'] ?? []), fn ($a) => mb_strtolower($a) !== mb_strtolower($local) && mb_strtolower($a) !== mb_strtolower($name)));
    $tools = $H::tools($guide);
    $video = $guide['video'] ?? null;
    $langs = ['fil' => 'Tagalog', 'en' => 'English', 'ceb' => 'Bisaya', 'ilo' => 'Ilocano', 'hil' => 'Hiligaynon', 'bik' => 'Bikol', 'war' => 'Waray'];
    $details = [
        ['spacing', 'Spacing', 'plant-spacing', 'M4 7h16M4 17h16M7 4v6M17 4v6M7 14v6M17 14v6'],
        ['water', 'Water', null, 'M12 3c3.5 4.5 6 8 6 11a6 6 0 01-12 0c0-3 2.5-6.5 6-11z'],
        ['fertilizer', 'Fertilizer', null, 'M6 7h12l-1 13H7L6 7zm3 0V5a3 3 0 016 0v2'],
        ['care', 'Care', null, 'M12 21s-7-4.4-7-10a4 4 0 017-2.6A4 4 0 0119 11c0 5.6-7 10-7 10z'],
        ['harvest', 'Harvest', 'harvest', 'M5 9h14l-1.5 10a2 2 0 01-2 1.7h-7a2 2 0 01-2-1.7L5 9zm3 0V7a4 4 0 018 0v2'],
    ];
    $go = '<svg fill="none" stroke="currentColor" stroke-width="2.6" viewBox="0 0 24 24" aria-hidden="true"><path stroke-linecap="round" stroke-linejoin="round" d="M9 5l7 7-7 7"/></svg>';
@endphp
<article class="hg" data-crop="{{ $crop }}" data-way="{{ $way }}" data-name="{{ $local }}">
    {{-- The disclaimer, on top of every guide (the owner's ask). --}}
    <aside class="hg-warn" role="note" style="--i:0">
        <span class="hg-warn-ic" aria-hidden="true"><svg fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" d="M12 9v4m0 4h.01M10.3 3.9L2.4 17.5A2 2 0 004.1 20.5h15.8a2 2 0 001.7-3L13.7 3.9a2 2 0 00-3.4 0z"/></svg></span>
        <div>
            <b>This is the traditional way{{ $T::flag('traditional-way') }}</b>
            <p>This is how traditional farmers usually plant {{ mb_strtolower($local) === mb_strtolower($name) ? mb_strtolower($name) : $local }}. Use it as a starting point. For a higher yield, update it: plant certified seed or clean planting material, test your soil, and follow the right spacing and fertilizer.</p>
            <a href="#hgBetter" class="hg-warn-go" data-hg-jump>How to get a higher yield {!! $go !!}</a>
        </div>
    </aside>

    <header class="hg-hero" style="--i:1">
        <span class="hg-hero-ic" aria-hidden="true">{{ $icon }}</span>
        <div class="hg-hero-t">
            <small>How to plant</small>
            <h2>{{ $local }}</h2>
            <span class="hg-names">{{ $name }}@if ($aka) <i>also {{ implode(', ', array_slice($aka, 0, 4)) }}</i>@endif</span>
        </div>
        @if ($guide['summary'] ?? '')<p class="hg-sum">{{ $guide['summary'] }}</p>@endif
        <div class="hg-badges">
            <span><svg fill="none" stroke="currentColor" stroke-width="2.2" viewBox="0 0 24 24" aria-hidden="true"><path stroke-linecap="round" stroke-linejoin="round" d="M9 5H7a2 2 0 00-2 2v12a2 2 0 002 2h10a2 2 0 002-2V7a2 2 0 00-2-2h-2M9 5a2 2 0 002 2h2a2 2 0 002-2M9 5a2 2 0 012-2h2a2 2 0 012 2"/></svg>{{ count($ways) === 1 ? '1 way to plant' : count($ways) . ' ways to plant' }}</span>
            @if ($video)<span class="is-vid"><svg viewBox="0 0 24 24" aria-hidden="true"><path d="M8 5.5v13l11-6.5z" fill="currentColor"/></svg>With a video</span>@endif
        </div>
    </header>

    <section class="hg-facts" style="--i:2">
        <div class="hg-fact">
            <span class="hg-fact-ic" aria-hidden="true"><svg fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" d="M8 7V3m8 4V3M4 11h16M5 5h14a1 1 0 011 1v13a1 1 0 01-1 1H5a1 1 0 01-1-1V6a1 1 0 011-1z"/></svg></span>
            <h3>When to plant</h3>
            <p>{{ $guide['season'] }}</p>
        </div>
        <div class="hg-fact">
            <span class="hg-fact-ic" aria-hidden="true"><svg fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" d="M12 21v-9m0 0C12 7 8 5 4 5c0 4 3 7 8 7zm0 0c0-4 3-7 8-7 0 4-4 7-8 7z"/></svg></span>
            <h3>What you plant{{ $T::flag('planting-material') }}</h3>
            <p>{{ $guide['material'] }}</p>
        </div>
    </section>

    @if ($video)
        <section class="hg-video" style="--i:3" aria-label="Video">
            <h3 class="hg-h">Watch how it is done</h3>
            <div class="hg-yt" data-yt="{{ $video['youtubeId'] }}">
                <button type="button" class="hg-yt-btn" aria-label="Play the video: {{ $video['title'] }}">
                    <img src="https://i.ytimg.com/vi/{{ $video['youtubeId'] }}/hqdefault.jpg" alt="" loading="lazy" decoding="async" width="480" height="360">
                    <span class="hg-yt-play" aria-hidden="true"><svg viewBox="0 0 24 24"><path d="M8 5.5v13l11-6.5z" fill="currentColor"/></svg></span>
                    @if (! empty($video['minutes']))<span class="hg-yt-len">{{ (int) $video['minutes'] }} min</span>@endif
                </button>
            </div>
            <div class="hg-yt-cap">
                <b>{{ $video['title'] }}</b>
                <span>{{ $video['channel'] }}@if (isset($langs[$video['lang'] ?? ''])) · {{ $langs[$video['lang']] }}@endif</span>
                @if ($video['why'] ?? '')<p>{{ $video['why'] }}</p>@endif
            </div>
        </section>
    @endif

    <section class="hg-steps" style="--i:4" aria-label="The steps">
        <h3 class="hg-h">Step by step</h3>
        @if (count($ways) > 1)
            <div class="hg-ways" role="tablist" aria-label="How it is planted" style="--n: {{ count($ways) }}">
                <span class="hg-ways-ind" aria-hidden="true"></span>
                @foreach ($ways as $w)
                    <button type="button" role="tab" class="hg-way" data-way="{{ $w['key'] }}" aria-selected="{{ $w['key'] === $way ? 'true' : 'false' }}" id="hgTab-{{ $crop }}-{{ $w['key'] }}">
                        <b>{{ $H::wayShort($w) }}</b>
                        @if ($hw = $H::harvestWords($w, $months))<small>{{ preg_replace('/^About /', '', $hw) }}</small>@endif
                    </button>
                @endforeach
            </div>
        @endif
        @foreach ($ways as $w)
            @php
                $on = $w['key'] === $way;
                $byPhase = [];
                foreach ($w['steps'] as $s) { $byPhase[$s['phase']][] = $s; }
                $hw = $H::harvestWords($w, $months);
            @endphp
            <div class="hg-pane" data-way="{{ $w['key'] }}" role="tabpanel" @if (count($ways) > 1) aria-labelledby="hgTab-{{ $crop }}-{{ $w['key'] }}" @endif @unless ($on) hidden @endunless>
                <div class="hg-way-head">
                    <div>
                        <b>{{ $w['label'] }}</b>
                        @if ($hw)<span>{{ $hw }}{{ $T::flag('days-to-harvest') }}</span>@endif
                    </div>
                    @if ($w['note'] ?? '')<p>{{ $w['note'] }}</p>@endif
                </div>
                <div class="hg-port">
                    <button type="button" class="hg-port-btn" data-port data-crop="{{ $crop }}" data-way="{{ $w['key'] }}">
                        <svg fill="none" stroke="currentColor" stroke-width="2.2" viewBox="0 0 24 24" aria-hidden="true"><path stroke-linecap="round" stroke-linejoin="round" d="M9 5H7a2 2 0 00-2 2v12a2 2 0 002 2h10a2 2 0 002-2V7a2 2 0 00-2-2h-2M9 5a2 2 0 002 2h2a2 2 0 002-2M9 5a2 2 0 012-2h2a2 2 0 012 2m-6 9l2 2 4-4"/></svg>
                        <span>Use this in my Protocol Builder</span>
                        <i aria-hidden="true">{!! $go !!}</i>
                    </button>
                    <small>Makes a new protocol draft with every step on its day, ready to change. Your other protocols stay as they are.</small>
                </div>
                <ol class="hg-line">
                    @foreach ($H::PHASES as $pk => [$pLabel, $pTl, $pIcon])
                        @continue(empty($byPhase[$pk]))
                        <li class="hg-phase is-{{ $pk }}">
                            <div class="hg-phase-h">
                                <span class="hg-phase-ic" aria-hidden="true"><svg fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" d="{{ $pIcon }}"/></svg></span>
                                <b>{{ $pLabel }}</b><i>{{ $pTl }}</i>
                                <em>{{ count($byPhase[$pk]) }} {{ count($byPhase[$pk]) === 1 ? 'step' : 'steps' }}</em>
                            </div>
                            <ol>
                                @foreach ($byPhase[$pk] as $s)
                                    <li class="hg-step">
                                        <span class="hg-dot" aria-hidden="true"></span>
                                        <div class="hg-step-top">
                                            <span class="hg-when">{{ $H::when((int) $s['day'], $w, $months) }}</span>
                                            <span class="hg-kind">{{ $H::TASKS[$s['task']][0] ?? 'Farm work' }}</span>
                                        </div>
                                        <h4>{{ $s['title'] }}</h4>
                                        <p>{{ $s['how'] }}</p>
                                        @if (! empty($s['tip']))
                                            <p class="hg-tip"><svg fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24" aria-hidden="true"><path stroke-linecap="round" stroke-linejoin="round" d="M9.7 17h4.6M12 3a6 6 0 00-3.6 10.8c.4.3.6.8.6 1.3V16h6v-.9c0-.5.2-1 .6-1.3A6 6 0 0012 3zm-2 17h4"/></svg><span>{{ $s['tip'] }}</span></p>
                                        @endif
                                        @if (! empty($s['parts']))
                                            {{-- The owner (2026-10-19): a step done in parts (soak, incubate, sow ...) shows them numbered, folded until tapped. --}}
                                            @php $pid = 'hgp-' . $crop . '-' . $w['key'] . '-' . $pk . '-' . $loop->index; @endphp
                                            <div class="hg-parts">
                                                <button type="button" class="hg-parts-btn" aria-expanded="false" aria-controls="{{ $pid }}">
                                                    <span class="hg-parts-n">{{ count($s['parts']) }}</span>
                                                    <span>{{ count($s['parts']) === 1 ? 'How to do it' : 'How to do it, in ' . count($s['parts']) . ' parts' }}</span>
                                                    <svg fill="none" stroke="currentColor" stroke-width="2.6" viewBox="0 0 24 24" aria-hidden="true"><path stroke-linecap="round" stroke-linejoin="round" d="M6 9l6 6 6-6"/></svg>
                                                </button>
                                                <div class="hg-parts-fold" id="{{ $pid }}" role="region" aria-label="{{ $s['title'] }}, part by part">
                                                    <ol>
                                                        @foreach ($s['parts'] as $pt)
                                                            <li>
                                                                <i aria-hidden="true">{{ $loop->iteration }}</i>
                                                                <div>
                                                                    <b>{{ $pt['title'] }}</b>@if (! empty($pt['time']))<span class="hg-time"><svg fill="none" stroke="currentColor" stroke-width="2.4" viewBox="0 0 24 24" aria-hidden="true"><circle cx="12" cy="12" r="8.5"/><path stroke-linecap="round" d="M12 8v4.5l3 1.8"/></svg>{{ $pt['time'] }}</span>@endif
                                                                    <p>{{ $pt['how'] }}</p>
                                                                    @if (! empty($pt['tip']))<p class="hg-ptip">{{ $pt['tip'] }}</p>@endif
                                                                </div>
                                                            </li>
                                                        @endforeach
                                                    </ol>
                                                </div>
                                            </div>
                                        @endif
                                    </li>
                                @endforeach
                            </ol>
                        </li>
                    @endforeach
                </ol>
            </div>
        @endforeach
    </section>

    <section class="hg-details" style="--i:5" aria-label="The details">
        <h3 class="hg-h">The details</h3>
        <div class="hg-dgrid">
            @foreach ($details as [$dk, $dLabel, $dTl, $dIcon])
                @continue(trim((string) ($guide[$dk] ?? '')) === '')
                <div class="hg-d">
                    <span class="hg-d-ic" aria-hidden="true"><svg fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" d="{{ $dIcon }}"/></svg></span>
                    <h4>{{ $dLabel }}@if ($dTl){{ $T::flag($dTl) }}@endif</h4>
                    <p>{{ $guide[$dk] }}</p>
                </div>
            @endforeach
        </div>
    </section>

    @if (! empty($guide['mistakes']))
        <section class="hg-mist" style="--i:6">
            <h3 class="hg-h">Common mistakes</h3>
            <ul>
                @foreach ($guide['mistakes'] as $m)
                    <li><span aria-hidden="true"><svg fill="none" stroke="currentColor" stroke-width="2.8" viewBox="0 0 24 24"><path stroke-linecap="round" d="M7 7l10 10M17 7L7 17"/></svg></span>{{ $m }}</li>
                @endforeach
            </ul>
        </section>
    @endif

    @if (! empty($guide['better']))
        <section class="hg-better" id="hgBetter" style="--i:7">
            <h3 class="hg-h">To get a higher yield</h3>
            <p class="hg-better-lead">What to change from the traditional way, one step at a time.</p>
            <ul>
                @foreach ($guide['better'] as $i => $b)
                    <li>
                        <span class="hg-ok" aria-hidden="true"><svg fill="none" stroke="currentColor" stroke-width="3" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" d="M5 13l4 4L19 7"/></svg></span>
                        <div>
                            <p>{{ $b }}</p>
                            @if (! empty($tools[$i]))
                                <div class="hg-tools">
                                    @foreach ($tools[$i] as [$tLabel, $tUrl, $tKey])
                                        @php $tLocked = $tKey && ! \App\Support\Tier::farmCan($tKey); $tRung = $tLocked ? \App\Support\Tier::farmUnlocksAt($tKey) : null; @endphp
                                        <a href="{{ $tUrl }}" class="hg-tool" @if ($tLocked) data-tier-lock="{{ $tRung }}" data-lock-say="{{ $tLabel }} comes with {{ \App\Support\Tier::withPlan($tRung) }}." @endif>
                                            @if ($tLocked)<svg class="hg-lock" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.3" aria-hidden="true"><rect x="4" y="10.5" width="16" height="10" rx="2.5"/><path d="M8 10.5V7a4 4 0 0 1 8 0v3.5"/></svg>@endif
                                            {{ $tLabel }}{!! $go !!}
                                        </a>
                                    @endforeach
                                </div>
                            @endif
                        </div>
                    </li>
                @endforeach
            </ul>
            <div class="hg-port is-end">
                <button type="button" class="hg-port-btn" data-port data-crop="{{ $crop }}" data-way="{{ $way }}">
                    <svg fill="none" stroke="currentColor" stroke-width="2.2" viewBox="0 0 24 24" aria-hidden="true"><path stroke-linecap="round" stroke-linejoin="round" d="M9 5H7a2 2 0 00-2 2v12a2 2 0 002 2h10a2 2 0 002-2V7a2 2 0 00-2-2h-2M9 5a2 2 0 002 2h2a2 2 0 002-2M9 5a2 2 0 012-2h2a2 2 0 012 2m-6 9l2 2 4-4"/></svg>
                    <span>Use this in my Protocol Builder</span>
                    <i aria-hidden="true">{!! $go !!}</i>
                </button>
                <small>Then change the steps there for a higher yield: your seed, your spacing, your fertilizer.</small>
            </div>
        </section>
    @endif
</article>
