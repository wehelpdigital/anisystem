{{-- The top of a permanent price page (App\Support\PriceHomes): the newest records and news, so the page stays today's
     answer without anybody editing it. $topic is 'palay' or 'fertilizer'. --}}
@php
    $PH = \App\Support\PriceHomes::class;
    $now = $topic === 'palay' ? $PH::palayNow() : null;
    $money = fn ($v) => \App\Support\Region::money($v, 2);
    $news = $PH::latestNews();
    $newsDay = fn ($p) => $p->publishedAt ? \Illuminate\Support\Carbon::parse($p->publishedAt)->timezone('Asia/Manila')->format('F j, Y') : '';
    $rows = $now ? array_filter(['Dry palay' => $now['dry'] ?? null, 'Wet palay' => $now['wet'] ?? null]) : [];
@endphp
<style>
    .phm { margin: 0 0 1.75rem; padding: 1.1rem 1.15rem; border: 1px solid #dfe9d3; border-radius: 1rem; background: #f7faf3; }
    .phm h2 { margin: 0 0 .7rem !important; font-size: 1.05rem; font-weight: 800; color: #24331a; }
    .phm-rows { display: grid; gap: .6rem; grid-template-columns: repeat(auto-fit, minmax(13rem, 1fr)); margin-bottom: .55rem; }
    .phm-row { padding: .7rem .85rem; border-radius: .75rem; background: #fff; border: 1px solid #e7efdc; }
    .phm-row b { display: block; font-size: .78rem; font-weight: 700; color: #5b6b4a; }
    .phm-row strong { display: block; margin-top: .1rem; font-size: 1.35rem; font-weight: 800; color: #2f5316; }
    .phm-row span { display: block; font-size: .75rem; color: #6b7a5c; }
    .phm-note { margin: 0 !important; font-size: .78rem; color: #6b7a5c; }
    .phm-news { list-style: none; margin: 0 !important; padding: 0 !important; display: grid; gap: .1rem; }
    .phm-news li { margin: 0 !important; padding: .4rem 0 !important; font-size: .9rem; line-height: 1.35; }
    .phm-news li + li { border-top: 1px solid #e7efdc; }
    .phm-news time { display: block; font-size: .72rem; color: #6b7a5c; }
    .phm-news a { color: #2f5316; font-weight: 600; }
    .phm + .phm { margin-top: -.75rem; }
</style>
@if ($rows)
    <section class="phm" aria-label="Latest palay prices">
        <h2>Palay price right now</h2>
        <div class="phm-rows">
            @foreach ($rows as $label => $r)
                <div class="phm-row">
                    <b>{{ $label }}, per kilo</b>
                    <strong>{{ $money($r['p']) }}</strong>
                    <span>Most sold between {{ $money($r['lo']) }} and {{ $money($r['hi']) }}</span>
                </div>
            @endforeach
        </div>
        <p class="phm-note">The middle of {{ collect($rows)->sum('n') }} farmgate reports in the app's own price records, up to {{ \Illuminate\Support\Carbon::parse(collect($rows)->max('to'))->format('F j, Y') }}. Your own price depends on your province, your variety and how dry the grain is.</p>
    </section>
@endif
@if ($news->isNotEmpty())
    <section class="phm" aria-label="Latest farm news">
        <h2>Latest news that moves {{ $topic === 'palay' ? 'palay prices' : 'fertilizer prices' }}</h2>
        <ul class="phm-news">
            @foreach ($news as $p)
                <li><time>{{ $newsDay($p) }}</time><a href="{{ \App\Support\SitePages::url($p->section, $p->slug) }}">{{ $p->title }}</a></li>
            @endforeach
        </ul>
    </section>
@endif
