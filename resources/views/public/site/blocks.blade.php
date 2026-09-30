{{-- One page's blocks (App\Support\SitePages): the kinds the mother app's
     builder offers. Every string goes through SitePages::inline(), which
     escapes first and then turns **bold** and [label](/address) into markup. --}}
@php($S = \App\Support\SitePages::class)
@foreach ($blocks as $i => $b)
    @switch($b['type'] ?? '')
        @case('heading')
            @if (trim((string) ($b['text'] ?? '')) !== '')
                @if ((int) ($b['level'] ?? 2) === 3)
                    <h3 id="{{ $S::anchor($b['text'], $i) }}">{{ $b['text'] }}</h3>
                @else
                    <h2 id="{{ $S::anchor($b['text'], $i) }}">{{ $b['text'] }}</h2>
                @endif
            @endif
            @break
        @case('text')
            @foreach ($S::paragraphs($b['text'] ?? '') as $para)
                <p>{!! $S::inline($para) !!}</p>
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
                    <div class="sp-step"><b>{{ $it['title'] ?? '' }}</b><p>{!! $S::inline($it['text'] ?? '') !!}</p></div>
                @endforeach
            </div>
            @break
        @case('table')
            @php($rows = array_values(array_filter((array) ($b['rows'] ?? []), 'is_array')))
            @if ($rows)
                <div class="sp-table-wrap"><table class="sp-table">
                    @if (trim((string) ($b['caption'] ?? '')) !== '')<caption>{{ $b['caption'] }}</caption>@endif
                    <thead><tr>@foreach ($rows[0] as $c)<th>{!! $S::inline((string) $c) !!}</th>@endforeach</tr></thead>
                    <tbody>@foreach (array_slice($rows, 1) as $r)<tr>@foreach ($r as $c)<td>{!! $S::inline((string) $c) !!}</td>@endforeach</tr>@endforeach</tbody>
                </table></div>
            @endif
            @break
        @case('callout')
            @php($tone = in_array($b['tone'] ?? '', ['tip', 'warn', 'info'], true) ? $b['tone'] : 'tip')
            <div class="sp-call {{ $tone }}">
                @if ($tone === 'warn')
                    <svg fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" d="M12 9v4m0 4h.01M10.3 3.9L1.8 18a2 2 0 001.7 3h17a2 2 0 001.7-3L13.7 3.9a2 2 0 00-3.4 0z"/></svg>
                @elseif ($tone === 'info')
                    <svg fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24"><circle cx="12" cy="12" r="9"/><path stroke-linecap="round" d="M12 11v5m0-8h.01"/></svg>
                @else
                    <svg fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" d="M9 18h6m-5 3h4M12 3a6 6 0 00-3.5 10.9c.6.4 1 1.1 1 1.8V16h5v-.3c0-.7.4-1.4 1-1.8A6 6 0 0012 3z"/></svg>
                @endif
                <div>
                    @if (trim((string) ($b['title'] ?? '')) !== '')<b>{{ $b['title'] }}</b>@endif
                    <p>{!! $S::inline($b['text'] ?? '') !!}</p>
                </div>
            </div>
            @break
        @case('image')
            @if ($src = $S::img($b['src'] ?? ($b['url'] ?? null)))
                <figure class="sp-img">
                    <img src="{{ $src }}" alt="{{ $b['alt'] ?? '' }}" loading="lazy">
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
                <b>{{ $b['title'] ?? 'Run your farm on anee.io' }}</b>
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
                <a class="btn btn-accent" href="{{ preg_match('#^https?://#', $ctaUrl) ? $ctaUrl : url($ctaUrl) }}">{{ $b['label'] ?? 'Start free' }}</a>
            </div>
            @break
        @case('links')
            <nav class="sp-links" aria-label="{{ $b['title'] ?? 'Related guides' }}">
                <b>{{ $b['title'] ?? 'Related guides' }}</b>
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
                <b>Sources</b>
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
