@extends('layouts.public')

{{-- How It Works (2026-10-01): the season in seven steps, Anee at every one.
     The whole picture is the shared partial (public/how/journey), which the
     dashboard's tour uses too; this page adds the head and the way in. --}}
@include('public.partials.site-css')

@section('title', 'How anee.io Works: Anee at Every Step of the Season')
@section('meta_description', 'See how anee.io works from the first plan to the last sack: plan, plant, grow, protect and harvest, with Anee, your smart farm technician, at every step.')

@push('head')
    <link rel="canonical" href="{{ route('how') }}">
    <meta property="og:title" content="How anee.io works">
    <meta property="og:description" content="Plan, build the plan, plant, grow, protect, harvest and look back, with Anee, your smart farm technician, at every step.">
    <meta property="og:url" content="{{ route('how') }}">
    <meta property="og:image" content="{{ asset('images/site/photos/palay-phone.jpg') }}">
    @php
        $hwSchema = [
            '@context' => 'https://schema.org',
            '@type' => 'HowTo',
            'name' => 'How a cropping season runs on anee.io',
            'description' => \App\Support\HowItWorks::stepWord(true) . ' steps from the first plan to the last sack, with Anee, your smart farm technician, at every step.',
            // In the page's own order: Anee Auto first (public/how/journey).
            'step' => collect(\App\Support\HowItWorks::stages())->sortBy(fn ($s) => $s['key'] === 'auto' ? 0 : 1)->values()->map(fn ($s, $i) => [
                '@type' => 'HowToStep',
                'position' => $i + 1,
                'name' => $s['title'],
                'text' => $s['lede'],
                'url' => route('how') . '#hw-step-' . $s['key'],
            ])->all(),
        ];
    @endphp
    <script type="application/ld+json">{!! json_encode($hwSchema, JSON_UNESCAPED_SLASHES | JSON_UNESCAPED_UNICODE | JSON_HEX_TAG) !!}</script>
@endpush

@push('head')
<style>
    /* The way in at the foot of How It Works (2026-10-28). */
    .hwi { padding: 4.5rem 1rem; background: linear-gradient(180deg, #0d1609 0%, #13210d 30%, #f3f8ec 30%, #f3f8ec 100%); }
    @media (min-width: 640px) { .hwi { padding: 5.5rem 1.5rem; } }
    .hwi-in { max-width: 72rem; margin: 0 auto; }
    .hwi-card { position: relative; display: grid; gap: 1.6rem; padding: 1.8rem 1.3rem; border-radius: 1.8rem; overflow: hidden; color: #eef4e6;
        background: radial-gradient(40rem 22rem at 90% -10%, rgb(245 197 24 / .18), transparent 60%), linear-gradient(150deg, #2a4a17 0%, #1a2e10 55%, #12200b 100%);
        box-shadow: 0 40px 80px -40px rgb(13 22 9 / .8), inset 0 0 0 1px rgb(245 197 24 / .25); }
    @media (min-width: 900px) { .hwi-card { grid-template-columns: minmax(0, 1.25fr) minmax(0, .9fr); align-items: center; gap: 2.4rem; padding: 2.8rem 2.6rem; } }
    .hwi-kick { font-size: .74rem; font-weight: 900; letter-spacing: .14em; text-transform: uppercase; color: #a8cc7e; }
    .hwi-h { margin-top: .5rem; font-family: var(--font-heading); font-size: clamp(1.8rem, 4.4vw, 2.7rem); font-weight: 800; line-height: 1.1; color: #fff; text-wrap: balance; }
    .hwi-h em { font-style: normal; color: #f5c518; }
    .hwi-p { margin-top: .9rem; font-size: 1rem; line-height: 1.65; color: #c9d8b8; }
    .hwi-pts { display: grid; gap: .45rem; margin: 1rem 0 0; padding: 0; list-style: none; }
    .hwi-pts li { display: flex; align-items: center; gap: .55rem; font-size: .92rem; font-weight: 700; color: #eef4e6; }
    .hwi-pts svg { flex: none; width: 1.3rem; height: 1.3rem; padding: .2rem; border-radius: 999px; color: #2b2100; background: #f5c518; }
    .hwi-btns { display: flex; flex-wrap: wrap; gap: .6rem; margin-top: 1.4rem; }
    .hwi-ghost { display: inline-flex; align-items: center; padding: .8rem 1.3rem; border-radius: .9rem; font-weight: 800; color: #fff; border: 1.5px solid rgb(255 255 255 / .3);
        background: rgb(255 255 255 / .04); transition: background-color .28s cubic-bezier(.22,1,.36,1), border-color .28s cubic-bezier(.22,1,.36,1); }
    .hwi-ghost:hover { background: rgb(255 255 255 / .1); border-color: rgb(255 255 255 / .5); }
    .hwi-doors { display: grid; gap: .7rem; }
    .hwi-door { display: flex; align-items: center; gap: .85rem; padding: 1rem 1rem; border-radius: 1.2rem; color: #eef4e6; background: rgb(255 255 255 / .06);
        box-shadow: inset 0 0 0 1px rgb(255 255 255 / .1); transition: transform .3s cubic-bezier(.22,1,.36,1), background-color .3s cubic-bezier(.22,1,.36,1), box-shadow .3s cubic-bezier(.22,1,.36,1); }
    .hwi-door:hover { transform: translateY(-3px); background: rgb(255 255 255 / .1); box-shadow: inset 0 0 0 1px rgb(245 197 24 / .5); }
    .hwi-door img { flex: none; width: 3.2rem; height: 3.2rem; }
    .hwi-door span { flex: 1; min-width: 0; display: grid; gap: .15rem; }
    .hwi-door b { font-size: .98rem; font-weight: 800; color: #fff; }
    .hwi-door small { font-size: .8rem; color: #b9caa8; }
    .hwi-door > svg { flex: none; width: 1.2rem; height: 1.2rem; color: #a8cc7e; transition: transform .3s cubic-bezier(.22,1,.36,1); }
    .hwi-door:hover > svg { transform: translateX(3px); color: #f5c518; }
    .hwi-door.is-gold { background: linear-gradient(135deg, rgb(245 197 24 / .2), rgb(245 197 24 / .06)); box-shadow: inset 0 0 0 1px rgb(245 197 24 / .45); }
    @media (prefers-reduced-motion: reduce) { .hwi-door, .hwi-door > svg, .hwi-ghost { transition: none; } }
</style>
@endpush

@section('content')
    @include('public.how.journey', ['hwMode' => 'site'])

    {{-- The way in (2026-10-28, the owner: "make ... the lower parts of /how-it-works look better"): one card, the free
         start and the plans on the left, the two other doors on the right (how Anee differs, one free question). --}}
    @php
        $hwAllTools = collect(\App\Support\HowItWorks::stages())->flatMap(fn ($s) => array_filter($s['items']));
        $hwFreeTier = array_key_first(array_filter(config('tiers'), fn ($t, $k) => $k !== 'admin', ARRAY_FILTER_USE_BOTH));
        $hwFreeTools = $hwAllTools->filter(fn ($it) => ($it['plan']['tier'] ?? null) === $hwFreeTier)->count();
    @endphp
    <section class="hwi">
        <div class="hwi-in reveal">
            <div class="hwi-card">
                <div class="hwi-copy">
                    <p class="hwi-kick">Your season, start to finish</p>
                    <h2 class="hwi-h">Bring your next season to <em>anee.io</em></h2>
                    <p class="hwi-p">Start free on the Libre plan, with no payment and no trial clock. {{ $hwFreeTools }} of the {{ $hwAllTools->count() }} tools are free on every plan. Anee's analyses run on credits, and some tools come with paid plans.</p>
                    <ul class="hwi-pts">
                        @foreach (['No payment and no trial clock', 'Works at the far lot, without signal', 'Anee answers in Tagalog or English'] as $pt)
                            <li><svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.8" aria-hidden="true"><path stroke-linecap="round" stroke-linejoin="round" d="M5 12.5l4.5 4.5L19 7.5"/></svg>{{ $pt }}</li>
                        @endforeach
                    </ul>
                    <div class="hwi-btns">
                        <a href="{{ route('signup') }}" class="btn btn-accent btn-lg">Start free</a>
                        <a href="{{ route('pricing') }}" class="hwi-ghost">See the plans</a>
                    </div>
                </div>
                <div class="hwi-doors">
                    <a href="{{ route('how.why') }}" class="hwi-door">
                        <img src="{{ asset('images/anee/emoji/thinking.png') }}" alt="" width="56" height="56" loading="lazy">
                        <span><b>How is Anee different from ChatGPT?</b><small>See what she reads before she answers</small></span>
                        <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.4" aria-hidden="true"><path stroke-linecap="round" stroke-linejoin="round" d="M9 5l7 7-7 7"/></svg>
                    </a>
                    <a href="{{ url('/ask-anee') }}" class="hwi-door is-gold">
                        <img src="{{ asset('images/anee/emoji/wave.png') }}" alt="" width="56" height="56" loading="lazy">
                        <span><b>Ask Anee one question, free</b><small>One free question every week</small></span>
                        <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.4" aria-hidden="true"><path stroke-linecap="round" stroke-linejoin="round" d="M9 5l7 7-7 7"/></svg>
                    </a>
                </div>
            </div>
        </div>
    </section>
@endsection
