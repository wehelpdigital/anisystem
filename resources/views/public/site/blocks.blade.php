{{-- One page's blocks (App\Support\SitePages): the kinds the mother app's
     builder offers. Every string goes through SitePages::inline(), which
     escapes first and then turns **bold** and [label](/address) into markup. --}}
@php
    $S = \App\Support\SitePages::class;
    // A Tagalog page's own words around the content (the content itself is the writer's).
    $tl = isset($page) && ($page->lang ?? '') === 'tl';
    // The English and Taglish switch's addresses: unique even when a page's blocks come in two includes.
    $ltBase = 'lt' . substr(md5(serialize($blocks)), 0, 6);
@endphp
@foreach ($blocks as $i => $b)
    @switch($b['type'] ?? '')
        @case('heading')
            @if (trim((string) ($b['text'] ?? '')) !== '')
                @if ((int) ($b['level'] ?? 2) === 3)
                    <h3 id="{{ $S::blockAnchor($b, $i) }}">{{ $b['text'] }}</h3>
                @else
                    <h2 id="{{ $S::blockAnchor($b, $i) }}">{{ $b['text'] }}</h2>
                @endif
            @endif
            @break
        @case('text')
            @foreach ($S::paragraphs($b['text'] ?? '') as $para)
                @if ($outlets = $S::outletLine($para))
                    {{-- The same news from several newsrooms (the owner, 2026-10-16): each newsroom a
                         small icon chip, the reader picks where to read it (the Tech Blog draws it the same). --}}
                    <p class="sp-srcline is-many"><span>{{ $outlets[0] }}. Read it at:</span>
                        <span class="sp-outlets">@foreach ($outlets[1] as [$oName, $oUrl])<a class="sp-outlet" href="{{ $oUrl }}" target="_blank" rel="noopener" title="Read it at {{ $oName }}"><img src="{{ url($S::newsIcon($oUrl)) }}" alt="" width="18" height="18" loading="lazy" referrerpolicy="no-referrer" onerror="this.remove()">{{ $oName }}</a>@endforeach</span></p>
                @elseif (preg_match('/^\*\*(.+?)\*\*\s*\[(Read the full report[^\]]*)\]\((https?:\/\/[^)\s]+)\)\s*$/', trim($para), $m))
                    {{-- A news roundup's source line: who and when, then the report itself as a
                         button a thumb can hit (the owner, 2026-10-07; the Tech Blog draws it the same). --}}
                    <p class="sp-srcline"><span>{{ rtrim($m[1], '.') }}</span>
                        <a class="sp-srcbtn" href="{{ $m[3] }}" target="_blank" rel="noopener"><img src="{{ url($S::newsIcon($m[3])) }}" alt="" width="18" height="18" loading="lazy" referrerpolicy="no-referrer" onerror="this.remove()">{{ $m[2] }}<svg fill="none" stroke="currentColor" stroke-width="2.2" viewBox="0 0 24 24" aria-hidden="true"><path stroke-linecap="round" stroke-linejoin="round" d="M14 4h6v6m0-6L10 14M18 14v5a1 1 0 01-1 1H5a1 1 0 01-1-1V7a1 1 0 011-1h5"/></svg></a></p>
                @else
                    <p>{!! $S::inline($para) !!}</p>
                @endif
            @endforeach
            @break
        @case('list')
            @php($items = array_values(array_filter((array) ($b['items'] ?? []), fn ($x) => trim((string) $x) !== '')))
            @if ($items)
                @if (! empty($b['ordered']))
                    <ol class="sp-list">@foreach ($items as $it)<li>{!! $S::inline($it) !!}</li>@endforeach</ol>
                @else
                    <ul class="sp-list">@foreach ($items as $it)<li>{!! $S::inline($it) !!}</li>@endforeach</ul>
                @endif
            @endif
            @break
        @case('steps')
            <div class="sp-steps">
                @foreach ((array) ($b['items'] ?? []) as $it)
                    <div class="sp-step"><b>{{ $it['title'] ?? '' }}</b><p>{!! $S::inline($it['text'] ?? '') !!}</p>
                        {{-- A step done in parts (How to Plant, 2026-10-19): numbered under it, each with its time. --}}
                        @if (! empty($it['parts']) && is_array($it['parts']))
                            <ol class="sp-parts">
                                @foreach ($it['parts'] as $pt)
                                    @continue(! is_array($pt) || trim((string) ($pt['title'] ?? '')) === '')
                                    <li><b>{{ $pt['title'] }}</b>@if (! empty($pt['time']))<span class="sp-ptime">{{ $pt['time'] }}</span>@endif<span>{!! $S::inline($pt['text'] ?? '') !!}</span></li>
                                @endforeach
                            </ol>
                        @endif
                    </div>
                @endforeach
            </div>
            @break
        @case('table')
            @php($rows = array_values(array_filter((array) ($b['rows'] ?? []), 'is_array')))
            @if ($rows)
                {{-- Three columns or more do not fit a phone: there each row becomes a card,
                     its first cell the card's name and every other cell labelled with its
                     column (data-th), so no value hides past the screen's edge. --}}
                @php($heads = array_map(fn ($c) => $S::plain((string) $c), array_values($rows[0])))
                <div class="sp-table-wrap {{ count($heads) >= 3 ? 'is-stack' : '' }}"><table class="sp-table">
                    @if (trim((string) ($b['caption'] ?? '')) !== '')<caption>{{ $b['caption'] }}</caption>@endif
                    <thead><tr>@foreach ($rows[0] as $c)<th scope="col">{!! $S::inline((string) $c) !!}</th>@endforeach</tr></thead>
                    <tbody>@foreach (array_slice($rows, 1) as $r)<tr>@foreach (array_values($r) as $k => $c)<td data-th="{{ $heads[$k] ?? '' }}">{!! $S::inline((string) $c) !!}</td>@endforeach</tr>@endforeach</tbody>
                </table></div>
            @endif
            @break
        @case('callout')
            @php($tone = in_array($b['tone'] ?? '', ['tip', 'warn', 'info'], true) ? $b['tone'] : 'tip')
            {{-- A news story's "so what" in English and Taglish (2026-10-17, the owner): two tabs
                 in the card, Taglish first; both texts are in the page (partials/lang-tabs). --}}
            @php($textTl = trim((string) ($b['textTl'] ?? '')))
            @php($langTabs = $textTl !== '' && trim((string) ($b['text'] ?? '')) !== '')
            <div class="sp-call {{ $tone }} {{ $langTabs ? 'lt' : '' }}">
                @if ($tone === 'warn')
                    <svg fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" d="M12 9v4m0 4h.01M10.3 3.9L1.8 18a2 2 0 001.7 3h17a2 2 0 001.7-3L13.7 3.9a2 2 0 00-3.4 0z"/></svg>
                @elseif ($tone === 'info')
                    <svg fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24"><circle cx="12" cy="12" r="9"/><path stroke-linecap="round" d="M12 11v5m0-8h.01"/></svg>
                @else
                    <svg fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" d="M9 18h6m-5 3h4M12 3a6 6 0 00-3.5 10.9c.6.4 1 1.1 1 1.8V16h5v-.3c0-.7.4-1.4 1-1.8A6 6 0 0012 3z"/></svg>
                @endif
                @if ($langTabs)
                    @php($ltId = $ltBase . '-' . $i)
                    <div>
                        <div class="lt-head">
                            @if (trim((string) ($b['title'] ?? '')) !== '')<b>{{ $b['title'] }}</b>@endif
                            <div class="lt-tabs is-tl" role="tablist" aria-label="{{ $tl ? 'Wika' : 'Language' }}">
                                <button type="button" class="lt-tab" role="tab" id="{{ $ltId }}-tab-en" aria-controls="{{ $ltId }}-en" aria-selected="false" tabindex="-1" data-lang="en">English</button>
                                <button type="button" class="lt-tab" role="tab" id="{{ $ltId }}-tab-tl" aria-controls="{{ $ltId }}-tl" aria-selected="true" tabindex="0" data-lang="tl">Taglish</button>
                            </div>
                        </div>
                        <div class="lt-pane lt-en" id="{{ $ltId }}-en" role="tabpanel" aria-labelledby="{{ $ltId }}-tab-en" tabindex="0" @if ($tl) lang="en" @endif hidden><p>{!! $S::inline($b['text']) !!}</p></div>
                        <div class="lt-pane lt-tl" id="{{ $ltId }}-tl" role="tabpanel" aria-labelledby="{{ $ltId }}-tab-tl" tabindex="0" lang="fil"><p>{!! $S::inline($textTl) !!}</p></div>
                    </div>
                @else
                    <div>
                        @if (trim((string) ($b['title'] ?? '')) !== '')<b>{{ $b['title'] }}</b>@endif
                        <p>{!! $S::inline($b['text'] ?? '') !!}</p>
                    </div>
                @endif
            </div>
            @break
        @case('image')
            @if ($src = $S::img($b['src'] ?? ($b['url'] ?? null)))
                {{-- A news photo (a roundup's, from the original report) sits in a 16 by 9 frame,
                     and goes away quietly if the newsroom stops serving it. --}}
                {{-- "small" (2026-10-13): a step picture or a diagram that needs no full width. --}}
                <figure class="sp-img {{ ['news' => 'is-news', 'small' => 'is-small'][$b['style'] ?? ''] ?? '' }}">
                    <img src="{{ $src }}" alt="{{ $b['alt'] ?? '' }}" loading="lazy" referrerpolicy="no-referrer" @if (($b['style'] ?? '') === 'news') onerror="this.closest('figure').remove()" @endif>
                    @if (trim((string) ($b['caption'] ?? '')) !== '')<figcaption>{!! $S::inline($b['caption']) !!}</figcaption>@endif
                </figure>
            @endif
            @break
        @case('quote')
            <blockquote class="sp-quote">{!! $S::inline($b['text'] ?? '') !!}@if (trim((string) ($b['cite'] ?? '')) !== '')<cite>{{ $b['cite'] }}</cite>@endif</blockquote>
            @break
        @case('faq')
            <div class="sp-faq">
                @foreach ((array) ($b['items'] ?? []) as $it)
                    @if (trim((string) ($it['q'] ?? '')) !== '')
                        <details>
                            <summary><span>{{ $it['q'] }}</span><svg fill="none" stroke="currentColor" stroke-width="2.4" viewBox="0 0 24 24"><path stroke-linecap="round" d="M12 5v14M5 12h14"/></svg></summary>
                            <div class="ans">{!! $S::inline($it['a'] ?? '') !!}</div>
                        </details>
                    @endif
                @endforeach
            </div>
            @break
        @case('cta')
            <div class="sp-cta">
                <b>{{ $b['title'] ?? ($tl ? 'Patakbuhin ang bukid mo sa anee.io' : 'Run your farm on anee.io') }}</b>
                {{-- The first line is the pitch; any line after it is a benefit, ticked. --}}
                @php($ctaLines = array_values(array_filter(array_map('trim', preg_split('/\R+/', (string) ($b['text'] ?? ''))), fn ($l) => $l !== '')))
                @if ($ctaLines)<p>{!! $S::inline($ctaLines[0]) !!}</p>@endif
                @if (count($ctaLines) > 1)
                    <ul class="sp-cta-list">
                        @foreach (array_slice($ctaLines, 1) as $l)
                            <li><svg fill="none" stroke="currentColor" stroke-width="2.6" viewBox="0 0 24 24" aria-hidden="true"><path stroke-linecap="round" stroke-linejoin="round" d="M5 13l4 4L19 7"/></svg><span>{!! $S::inline($l) !!}</span></li>
                        @endforeach
                    </ul>
                @endif
                @php($ctaUrl = trim((string) ($b['url'] ?? '')) ?: '/signup')
                <a class="btn btn-accent" href="{{ preg_match('#^https?://#', $ctaUrl) ? $ctaUrl : url($ctaUrl) }}">{{ $b['label'] ?? ($tl ? 'Magsimula nang libre' : 'Start free') }}</a>
            </div>
            @break
        @case('links')
            @php($linksTitle = $b['title'] ?? ($tl ? 'Kaugnay na gabay' : 'Related guides'))
            <nav class="sp-links" aria-label="{{ $linksTitle }}">
                <b>{{ $linksTitle }}</b>
                <ul>
                    @foreach ((array) ($b['items'] ?? []) as $it)
                        @if (trim((string) ($it['url'] ?? '')) !== '')
                            <li><a href="{{ preg_match('#^https?://#', $it['url']) ? $it['url'] : url($it['url']) }}"><svg fill="none" stroke="currentColor" stroke-width="2.6" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" d="M9 5l7 7-7 7"/></svg>{{ $it['label'] ?? $it['url'] }}</a></li>
                        @endif
                    @endforeach
                </ul>
            </nav>
            @break
        @case('sources')
            <div class="sp-sources">
                <b>{{ $tl ? 'Mga sanggunian' : 'Sources' }}</b>
                <ol>
                    @foreach ((array) ($b['items'] ?? []) as $it)
                        @if (preg_match('#^https?://#', (string) ($it['url'] ?? '')))
                            <li><a href="{{ $it['url'] }}" target="_blank" rel="noopener">{{ $it['label'] ?? $it['url'] }}</a></li>
                        @endif
                    @endforeach
                </ol>
            </div>
            @break
        @case('divider')
            <hr class="sp-divider">
            @break
    @endswitch
@endforeach
