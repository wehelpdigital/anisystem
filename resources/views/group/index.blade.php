@extends('layouts.app')
@section('title', 'Level Up Farmers')
@section('page-title', 'Group')
@section('page-subtitle', 'Level Up Farmers on Facebook')

{{-- LEVEL UP FARMERS (2026-11-24, the owner: "make it Group and when opened, ask them to join this group,
     www.facebook.com/groups/levelupfarmers/ ... Anee's group is called Level Up farmers so make it a persuasive
     page to ask them to join"). The bottom bar's Group seat (App\Support\GroupInvite).

     One ask, made five ways down the page: the promise and the button, what is inside, why it matters, how to join
     in three taps, and the button again. Honest: no member counts, no promised events, nothing the group does not
     do. A tap on a join button tells the app (so its popup stops asking); "I already joined" says the same. --}}
@php
    $G = \App\Support\GroupInvite::class;
    $url = $G::URL;
    $joined = $answer === 'joined';
    $fb = '<svg viewBox="0 0 24 24" aria-hidden="true"><circle cx="12" cy="12" r="12" fill="#1877F2"/><path fill="#fff" d="M13.4 19.5v-6.1h2.05l.31-2.38H13.4V9.5c0-.69.19-1.16 1.18-1.16h1.26V6.21a16.9 16.9 0 00-1.84-.09c-1.82 0-3.07 1.11-3.07 3.15v1.75H8.87v2.38h2.06v6.1h2.47z"/></svg>';
    $perks = [
        ['📸', 'Ask with a photo, get answers', 'Post a picture of the spot, the worm or the yellow leaf. Farmers who have seen it before tell you what it was and what worked for them.'],
        ['🧑‍🌾', 'Tips from our own technicians', 'The agronomists and technicians who built anee.io on our own farms share what they do, season by season, in plain Tagalog and English.'],
        ['🌾', 'Real fields, real harvests', 'Members show their crops, their mistakes and what fixed them. You learn from a hundred seasons instead of only your own.'],
        ['🌀', 'Warnings before the damage', 'When a typhoon, a dry spell or a pest outbreak heads to an area, the farmers there say so first.'],
        ['🚀', 'First to hear what is new', 'New anee.io tools, free guides and features reach the group first, with a word on how to use them.'],
        ['🤝', 'Farmers who want more', 'A group for farmers who want a higher yield and a better income, and help each other get there.'],
    ];
    $steps = [
        ['Tap the button', 'Facebook opens Level Up Farmers, in the app if you have it.'],
        ['Tap Join group', 'If Facebook asks a few short questions, answer them so we know you farm.'],
        ['Say hello', 'Tell us your crop and your town. Ask your first question anytime.'],
    ];
@endphp

@section('content')
<style>
    /* Level Up Farmers: the farm's own green, the brand's yellow for the one thing to do, and Facebook's blue only in
       its own mark. Every motion stops for a reader who asked for less. */
    .lu { max-width: 56rem; margin: 0 auto; }
    .lu-hero { position: relative; isolation: isolate; overflow: hidden; border-radius: 1.4rem; color: #fff; padding: 1.6rem 1.25rem 1.5rem; text-align: center;
        box-shadow: 0 30px 60px -40px rgb(16 32 9 / .9); }
    @media (min-width: 640px) { .lu-hero { padding: 2.6rem 2.2rem 2.4rem; } }
    .lu-hero-bg { position: absolute; inset: 0; z-index: -2; width: 100%; height: 100%; object-fit: cover; transform: scale(1.05); animation: luDrift 26s ease-in-out infinite alternate; }
    @keyframes luDrift { to { transform: scale(1.12) translate(-1.5%, -1%); } }
    .lu-hero-shade { position: absolute; inset: 0; z-index: -1; background: radial-gradient(40rem 20rem at 50% 0%, rgb(245 197 24 / .2), transparent 60%), linear-gradient(180deg, rgb(10 22 6 / .78), rgb(12 26 7 / .93)); }
    .lu-chip { display: inline-flex; align-items: center; gap: .45rem; padding: .35rem .8rem .35rem .4rem; border-radius: 999px; font-size: .78rem; font-weight: 800;
        background: rgb(255 255 255 / .12); border: 1px solid rgb(255 255 255 / .22); backdrop-filter: blur(6px); }
    .lu-chip svg { width: 1.35rem; height: 1.35rem; }
    .lu-h1 { margin-top: .9rem; font-family: var(--font-heading); font-weight: 800; font-size: clamp(1.9rem, 6vw, 3rem); line-height: 1.06; letter-spacing: -.02em; text-wrap: balance; }
    .lu-h1 em { font-style: normal; color: #f5c518; }
    .lu-lede { margin: .9rem auto 0; max-width: 34rem; font-size: clamp(.98rem, 2.2vw, 1.1rem); line-height: 1.65; color: #dde8d2; }
    .lu-join { display: inline-flex; align-items: center; justify-content: center; gap: .6rem; margin-top: 1.4rem; padding: .95rem 1.5rem; border-radius: 999px;
        font-size: 1.05rem; font-weight: 800; color: #1f1500; background: #f5c518; text-decoration: none; box-shadow: 0 0 0 0 rgb(245 197 24 / .55), 0 18px 34px -16px rgb(245 197 24 / .9);
        animation: luPulse 2.6s ease-in-out infinite; transition: transform .28s cubic-bezier(.22,1,.36,1), background-color .28s cubic-bezier(.22,1,.36,1); }
    .lu-join:hover { transform: translateY(-2px); background: #ffd84a; }
    .lu-join svg { width: 1.5rem; height: 1.5rem; flex: none; }
    @keyframes luPulse { 0%, 100% { box-shadow: 0 0 0 0 rgb(245 197 24 / .5), 0 18px 34px -16px rgb(245 197 24 / .9); } 50% { box-shadow: 0 0 0 12px rgb(245 197 24 / 0), 0 18px 34px -16px rgb(245 197 24 / .9); } }
    .lu-ticks { display: flex; flex-wrap: wrap; justify-content: center; gap: .4rem 1.1rem; margin-top: 1.1rem; font-size: .85rem; font-weight: 700; color: #e6efdc; }
    .lu-ticks li { display: inline-flex; align-items: center; gap: .35rem; }
    .lu-ticks svg { width: 1rem; height: 1rem; color: #f5c518; }
    .lu-done { display: inline-flex; align-items: center; gap: .5rem; margin-top: 1rem; padding: .5rem 1rem; border-radius: 999px; font-size: .88rem; font-weight: 800; color: #14210c; background: #d9f0c4; }
    .lu-sec { margin-top: 2rem; }
    .lu-kick { font-size: .74rem; font-weight: 800; letter-spacing: .1em; text-transform: uppercase; color: #4a7c2a; text-align: center; }
    .lu-h2 { margin-top: .35rem; font-family: var(--font-heading); font-size: clamp(1.35rem, 4vw, 1.9rem); font-weight: 800; line-height: 1.15; color: #14210c; text-align: center; text-wrap: balance; }
    .lu-h2 em { font-style: normal; color: #4a7c2a; }
    .lu-perks { display: grid; gap: .75rem; margin-top: 1.2rem; }
    @media (min-width: 640px) { .lu-perks { grid-template-columns: repeat(2, minmax(0, 1fr)); } }
    @media (min-width: 900px) { .lu-perks { grid-template-columns: repeat(3, minmax(0, 1fr)); } }
    .lu-perk { padding: 1.1rem 1.05rem 1rem; border-radius: 1.15rem; background: #fff; border: 1px solid #e5eedc; box-shadow: 0 18px 36px -34px rgb(20 33 12 / .6);
        opacity: 0; transform: translateY(12px); animation: luIn .6s cubic-bezier(.22,1,.36,1) forwards; animation-delay: var(--d, 0s); }
    @keyframes luIn { to { opacity: 1; transform: none; } }
    .lu-perk i { display: grid; place-items: center; width: 2.6rem; height: 2.6rem; border-radius: .9rem; font-style: normal; font-size: 1.35rem; background: linear-gradient(140deg, #eaf4df, #d7ebc2); }
    .lu-perk h3 { margin-top: .7rem; font-family: var(--font-heading); font-size: 1.02rem; font-weight: 800; color: #14210c; }
    .lu-perk p { margin-top: .3rem; font-size: .88rem; line-height: 1.55; color: #4b5563; }
    .lu-why { position: relative; margin-top: 2rem; padding: 1.4rem 1.25rem; border-radius: 1.3rem; text-align: center; color: #fff;
        background: linear-gradient(120deg, #2f5219, #4a7c2a 45%, #2f5219); background-size: 200% 200%; animation: luSweep 12s ease-in-out infinite alternate; }
    @keyframes luSweep { to { background-position: 100% 50%; } }
    .lu-why p { font-family: var(--font-heading); font-size: clamp(1.15rem, 3.4vw, 1.5rem); font-weight: 800; line-height: 1.3; text-wrap: balance; }
    .lu-why small { display: block; margin-top: .5rem; font-size: .88rem; font-weight: 600; color: #d7ebc2; }
    .lu-steps { display: grid; gap: .7rem; margin-top: 1.2rem; counter-reset: lu; }
    @media (min-width: 720px) { .lu-steps { grid-template-columns: repeat(3, minmax(0, 1fr)); } }
    .lu-step { display: flex; gap: .8rem; align-items: flex-start; padding: 1rem; border-radius: 1.1rem; background: #fbfdf8; border: 1px solid #e5eedc; }
    .lu-step b { flex: none; display: grid; place-items: center; width: 2.1rem; height: 2.1rem; border-radius: 999px; font-weight: 900; color: #1f1500; background: #f5c518; }
    .lu-step h3 { font-size: .98rem; font-weight: 800; color: #14210c; }
    .lu-step p { margin-top: .2rem; font-size: .86rem; line-height: 1.5; color: #4b5563; }
    .lu-final { margin-top: 2rem; padding: 1.5rem 1.25rem; border-radius: 1.3rem; text-align: center; background: #fffaf0; border: 1px solid #f3e2a6; }
    .lu-final h2 { font-family: var(--font-heading); font-size: clamp(1.25rem, 3.6vw, 1.6rem); font-weight: 800; color: #14210c; }
    .lu-final p { margin-top: .4rem; font-size: .92rem; color: #5b6651; }
    .lu-already { margin-top: .9rem; font-size: .85rem; font-weight: 700; color: #4a7c2a; background: none; border: 0; text-decoration: underline; text-underline-offset: 3px; cursor: pointer; }
    .lu-link { margin-top: .7rem; font-size: .78rem; color: #6b7280; word-break: break-all; }
    html.dark .lu-perk, html.dark .lu-step { background: #151b12; border-color: #2b3a1c; }
    html.dark .lu-perk h3, html.dark .lu-step h3, html.dark .lu-h2, html.dark .lu-final h2 { color: #e8efe1; }
    html.dark .lu-perk p, html.dark .lu-step p { color: #b9c9a8; }
    html.dark .lu-final { background: #1c1a10; border-color: #4a4320; }
    html.dark .lu-final p { color: #c9c4a3; }
    @media (prefers-reduced-motion: reduce) {
        .lu-hero-bg, .lu-join, .lu-why { animation: none; }
        .lu-perk { animation: none; opacity: 1; transform: none; }
    }
</style>

<div class="lu">
    <section class="lu-hero">
        <img src="{{ asset('images/site/photos/team-thumbs-800.webp') }}" srcset="{{ asset('images/site/photos/team-thumbs-480.webp') }} 480w, {{ asset('images/site/photos/team-thumbs-800.webp') }} 800w, {{ asset('images/site/photos/team-thumbs.jpg') }} 1600w" sizes="(min-width: 900px) 56rem, 100vw" alt="" class="lu-hero-bg">
        <span class="lu-hero-shade" aria-hidden="true"></span>
        <span class="lu-chip">{!! $fb !!} Anee's Facebook group</span>
        <h1 class="lu-h1">Join <em>Level Up Farmers.</em></h1>
        <p class="lu-lede">The free Facebook group of anee.io, where Filipino farmers help each other grow a bigger harvest. Ask with a photo, learn from farmers who have seen it before, and get tips from our own technicians.</p>
        <a href="{{ $url }}" target="_blank" rel="noopener" class="lu-join" data-lu-join>{!! $fb !!} Join Level Up Farmers</a>
        <ul class="lu-ticks">
            @foreach (['Free to join', 'Tagalog and English', 'Real Filipino farmers'] as $t)
                <li><svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="3" aria-hidden="true"><path stroke-linecap="round" stroke-linejoin="round" d="M5 12.5l4.5 4.5L19 7.5"/></svg>{{ $t }}</li>
            @endforeach
        </ul>
        @if ($joined)
            <p class="lu-done">🎉 You are in. See you in the group!</p>
        @endif
    </section>

    <section class="lu-sec">
        <p class="lu-kick">What is inside</p>
        <h2 class="lu-h2">Everything a Farmer Gets <em>in Level Up Farmers</em></h2>
        <div class="lu-perks">
            @foreach ($perks as $i => [$em, $h, $p])
                <article class="lu-perk" style="--d: {{ $i * 0.07 }}s">
                    <i aria-hidden="true">{{ $em }}</i>
                    <h3>{{ $h }}</h3>
                    <p>{{ $p }}</p>
                </article>
            @endforeach
        </div>
    </section>

    <div class="lu-why">
        <p>Walang magsasakang dapat mag isa. Sama sama tayong mag level up.</p>
        <small>No farmer should farm alone. Let us level up together.</small>
    </div>

    <section class="lu-sec">
        <p class="lu-kick">Three taps</p>
        <h2 class="lu-h2">How to Join <em>Level Up Farmers</em></h2>
        <ol class="lu-steps">
            @foreach ($steps as $i => [$h, $p])
                <li class="lu-step"><b>{{ $i + 1 }}</b><div><h3>{{ $h }}</h3><p>{{ $p }}</p></div></li>
            @endforeach
        </ol>
    </section>

    <section class="lu-final">
        <h2>Your next harvest is better with company.</h2>
        <p>It is free, it takes a minute, and the farmers inside are waiting to help.</p>
        <a href="{{ $url }}" target="_blank" rel="noopener" class="lu-join" data-lu-join>{!! $fb !!} Join the group now</a>
        @unless ($joined)
            <div><button type="button" class="lu-already" id="luAlready">I already joined</button></div>
        @endunless
        <p class="lu-link">facebook.com/groups/levelupfarmers</p>
    </section>
</div>

@push('scripts')
<script>
(() => {
    // A tap on a join button, or "I already joined", tells the app, so the popup stops asking (App\Support\GroupInvite).
    const ANSWER = @json(route('group.answer'));
    const tell = (answer) => {
        try { localStorage.setItem('anee_grp_answer', answer); } catch (e) { /* private window */ }
        const body = new FormData();
        body.append('answer', answer);
        return fetch(ANSWER, { method: 'POST', body, keepalive: true, credentials: 'same-origin',
            headers: { 'X-CSRF-TOKEN': document.querySelector('meta[name=csrf-token]')?.content || '', Accept: 'application/json' } }).catch(() => {});
    };
    document.querySelectorAll('[data-lu-join]').forEach((a) => a.addEventListener('click', () => tell('joined')));
    const already = document.getElementById('luAlready');
    already?.addEventListener('click', async () => {
        already.disabled = true;
        await tell('joined');
        window.toast ? window.toast('Thank you! We will stop asking.') : null;
        already.closest('div').remove();
    });
})();
</script>
@endpush
@endsection
