{{-- EASY TO USE, on the homepage (2026-10-29, the owner: "a new section in the homepage ... about anee.io is easy to use
     and understand then say because it has video tutorials, it has taglish icons, etc ... and also specifically mention
     that it is made and designed by filipino farmers for filipino farmers thats why it is catered and designed for the
     philippines"). Its full story is /how-it-works/easy-to-use (public.easy-to-use).

     A phone that shows what makes it easy, one thing at a time (a tutorial card rises, a Taglish tag opens on DAT, Anee
     answers in Tagalog, the offline badge), beside six tiles; then the band that says who made it and why it fits the
     Philippines. Plain words, no dashes. Expects $signup; $ezOnPage (the Easy to Use page itself) drops the link to it. --}}
@php
    $ezTl = \App\Support\Tagalog::book()['dat'] ?? ['en' => 'DAT (days after transplanting)', 'tl' => 'Bilang ng araw mula nang ilipat sa bukid ang punla. Ang araw ng paglipat ay araw 0.'];
    $ezFace = asset('images/anee/avatar-160.jpg');
    $ezTiles = [
        ['🎬', 'A video on every screen', 'Each main screen opens with a short video that shows how it works. Watch once, close it for good.'],
        ['🏷️', 'Taglish help tags', 'A small Taglish tag sits beside the technical words. Tap it for a plain explanation in Tagalog.'],
        ['💬', 'Ask in your own language', 'Ask Anee in Tagalog, Bisaya, Ilocano or English, and send a photo. She answers the way you asked.'],
        ['👆', 'Plain words, big buttons', 'Today\'s work first, one tap to mark it done. No jargon where a plain word will do.'],
        ['📶', 'Works in the field', 'Turn on offline mode and your season opens even where the signal does not reach.'],
        ['⚖️', 'The units you use', 'Cavans, sacks, pesos and hectares, and the day count of your crop, counted for you.'],
    ];
@endphp
<section class="hp-sec ez" id="easy" aria-labelledby="ezH">
    <div class="max-w-6xl mx-auto px-4 sm:px-6">
        <div class="hp-head reveal">
            <p class="hp-kick">Easy to use</p>
            <h2 class="hp-h2" id="ezH">Easy to Use. <em>Easy to Understand.</em></h2>
            <p class="hp-p">If you can send a photo in a chat, you can farm with anee.io. Every screen teaches itself, every hard word explains itself, and Anee speaks your language.</p>
        </div>
        <div class="ez-grid">
            <div class="ez-show reveal" data-ez-show aria-hidden="true">
                <div class="ez-phone">
                    <div class="ez-scr">
                        <div class="ez-bar"><b>Lot 1, north paddies</b><small>Wet season palay</small></div>
                        <div class="ez-day">
                            <span class="ez-day-n">DAT 21<i class="ez-tag" data-ez-tag>Taglish</i></span>
                            <span class="ez-day-s">Active tillering</span>
                        </div>
                        <div class="ez-task"><i>💧</i><span>Keep the water at 3 to 5 cm</span><em class="ez-tick">✓</em></div>
                        <div class="ez-task"><i>👀</i><span>Scout for stem borer</span><em></em></div>
                        {{-- The four moments, one at a time. --}}
                        <div class="ez-pop is-tut" data-ez-pop>
                            <span class="ez-tut-v"><span class="ez-play">▶</span></span>
                            <b>How this screen works</b><small>A 30 second video. Close it for good.</small>
                        </div>
                        <div class="ez-pop is-tl" data-ez-pop>
                            <b>{{ $ezTl['en'] }}</b><small>{{ $ezTl['tl'] }}</small>
                        </div>
                        <div class="ez-pop is-chat" data-ez-pop>
                            <span class="ez-q">Bakit naninilaw ang dahon ng palay ko?</span>
                            <span class="ez-a"><img src="{{ $ezFace }}" alt="">Sa DAT 21, madalas po ito ay kulang sa nitrogen. Padalhan mo ako ng larawan ng dahon para matiyak ko.</span>
                        </div>
                        <div class="ez-pop is-off" data-ez-pop>
                            <b>📶 Offline mode is on</b><small>Your season opens even with no signal in the field.</small>
                        </div>
                    </div>
                </div>
            </div>
            <div class="ez-tiles">
                @foreach ($ezTiles as $i => [$ic, $h, $p])
                    <div class="ez-tile reveal" style="--reveal-delay: {{ $i * 0.05 }}s" data-ez-tile="{{ $i }}"><em>{{ $ic }}</em><b>{{ $h }}</b><p>{{ $p }}</p></div>
                @endforeach
            </div>
        </div>
        {{-- Who made it. --}}
        <div class="ez-made reveal">
            <div class="ez-made-sun" aria-hidden="true">
                <svg viewBox="0 0 100 100"><g>@for ($r = 0; $r < 8; $r++)<path d="M50 6 L54 26 L50 22 L46 26 Z" transform="rotate({{ $r * 45 }} 50 50)"/>@endfor</g><circle cx="50" cy="50" r="17"/></svg>
            </div>
            <div class="ez-made-t">
                <p class="ez-made-k">Gawa ng magsasakang Pilipino</p>
                <h3>Made and Designed by Filipino Farmers, <em>for Filipino Farmers.</em></h3>
                <p>anee.io was not built abroad and translated. It was made by farmers who plant palay, mais and gulay here, for the fields, the weather and the market of the Philippines. That is why it fits the way you already farm.</p>
                <ul>
                    <li>Philippine crops, by the names you use</li>
                    <li>Products registered in the Philippines</li>
                    <li>Your town's typhoons and floods</li>
                    <li>Prices from Philippine markets</li>
                    <li>Pests by their local names</li>
                    <li>Hatian, Benta o Bilad and the cavan</li>
                </ul>
            </div>
        </div>
        <div class="hp-cta is-tight">
            <div class="hp-cta-row">
                <a href="{{ $signup }}" class="btn btn-accent btn-lg hp-go">Try it free <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.2" aria-hidden="true"><path stroke-linecap="round" stroke-linejoin="round" d="M13 7l5 5-5 5M6 12h12"/></svg></a>
                @if (empty($ezOnPage))<a href="{{ route('how.easy') }}" class="hp-alt">See why it is easy</a>@endif
            </div>
        </div>
    </div>
</section>

@once
@push('head')
<style>
    /* ---- easy to use (ez-) ---- */
    .ez { position: relative; overflow: hidden; background: radial-gradient(46rem 26rem at 90% 0%, #fdf1c7, transparent 60%), linear-gradient(180deg, #fffdf6, #f6faf0); }
    .ez-grid { display: grid; gap: 2.5rem; align-items: center; margin-top: 3rem; }
    @media (min-width: 960px) { .ez-grid { grid-template-columns: minmax(0, .8fr) minmax(0, 1.2fr); gap: 3.5rem; } }
    .ez-show { display: grid; place-items: center; }
    .ez-phone { position: relative; width: min(17rem, 78vw); aspect-ratio: 9 / 18.5; padding: .45rem; border-radius: 2.3rem; background: linear-gradient(155deg, #2a3820, #0b1207 60%);
        box-shadow: 0 0 0 2px rgb(245 197 24 / .45), 0 50px 90px -40px rgb(20 33 12 / .7); animation: ezBob 7s ease-in-out infinite; }
    @keyframes ezBob { 50% { translate: 0 -8px; } }
    .ez-scr { position: relative; height: 100%; overflow: hidden; border-radius: 1.9rem; background: #f4f7f0; padding: 1.1rem .8rem; }
    .ez-bar { padding: .2rem .3rem .6rem; }
    .ez-bar b { display: block; font-size: .9rem; font-weight: 900; color: #14210c; }
    .ez-bar small { font-size: .72rem; color: #6b7280; }
    .ez-day { padding: .8rem; border-radius: 1rem; color: #fff; background: linear-gradient(135deg, #2f5219, #4a7c2a); }
    .ez-day-n { display: inline-flex; align-items: center; gap: .35rem; font-family: var(--font-heading); font-size: 1.35rem; font-weight: 900; }
    .ez-tag { display: inline-block; padding: .12rem .4rem; border-radius: 999px; font-style: normal; font-size: .58rem; font-weight: 900; color: #14210c; background: #fde68a; box-shadow: 0 0 0 0 rgb(253 230 138 / .8); }
    .ez-tag.is-tap { animation: ezTap 1s ease-out; }
    @keyframes ezTap { 0% { box-shadow: 0 0 0 0 rgb(253 230 138 / .9); transform: scale(1); } 30% { transform: scale(1.18); } 100% { box-shadow: 0 0 0 12px rgb(253 230 138 / 0); transform: scale(1); } }
    .ez-day-s { display: block; margin-top: .15rem; font-size: .8rem; color: #d9ead0; }
    .ez-task { display: flex; align-items: center; gap: .5rem; margin-top: .55rem; padding: .6rem .65rem; border-radius: .85rem; background: #fff; box-shadow: 0 0 0 1px #e3ecd9; font-size: .74rem; font-weight: 800; color: #14210c; }
    .ez-task i { font-style: normal; }
    .ez-task em { margin-left: auto; display: grid; place-items: center; width: 1.3rem; height: 1.3rem; border-radius: .4rem; font-style: normal; font-size: .7rem; color: #fff; background: #4a7c2a; }
    .ez-task em:empty { background: transparent; box-shadow: inset 0 0 0 2px #c9dcb4; }
    .ez-pop { position: absolute; left: .7rem; right: .7rem; bottom: .9rem; padding: .85rem; border-radius: 1.1rem; background: #fff; box-shadow: 0 20px 40px -14px rgb(20 33 12 / .55), 0 0 0 1px #e3ecd9;
        opacity: 0; transform: translateY(24px) scale(.96); transition: opacity .5s var(--hp-ease), transform .5s var(--hp-ease); }
    .ez-pop.is-on { opacity: 1; transform: none; }
    .ez-pop b { display: block; font-size: .84rem; font-weight: 900; color: #14210c; }
    .ez-pop small { display: block; margin-top: .25rem; font-size: .72rem; line-height: 1.45; color: #4b5563; }
    .ez-pop.is-tl { background: #fffbe8; box-shadow: 0 20px 40px -14px rgb(120 90 0 / .45), 0 0 0 1.5px #fde68a; }
    .ez-tut-v { display: grid; place-items: center; height: 5.2rem; margin-bottom: .6rem; border-radius: .8rem; background: linear-gradient(135deg, #2f5219, #6b9f3d); }
    .ez-play { display: grid; place-items: center; width: 2.2rem; height: 2.2rem; border-radius: 999px; font-size: .8rem; color: #14210c; background: #f5c518; box-shadow: 0 0 0 6px rgb(245 197 24 / .3); animation: ezPulse 1.6s ease-in-out infinite; }
    @keyframes ezPulse { 50% { box-shadow: 0 0 0 11px rgb(245 197 24 / 0); } }
    .ez-pop.is-chat { display: grid; gap: .5rem; background: transparent; box-shadow: none; padding: 0; }
    .ez-q { justify-self: end; max-width: 85%; padding: .55rem .7rem; border-radius: 1rem 1rem .3rem 1rem; font-size: .72rem; font-weight: 700; color: #fff; background: #4a7c2a; }
    .ez-a { display: flex; align-items: flex-start; gap: .4rem; max-width: 92%; padding: .55rem .7rem; border-radius: 1rem 1rem 1rem .3rem; font-size: .72rem; font-weight: 700; color: #14210c; background: #fff; box-shadow: 0 10px 24px -14px rgb(20 33 12 / .5), 0 0 0 1px #e3ecd9; }
    .ez-a img { flex: none; width: 1.3rem; height: 1.3rem; border-radius: 999px; object-fit: cover; }
    .ez-tiles { display: grid; gap: .8rem; grid-template-columns: repeat(2, minmax(0, 1fr)); }
    @media (max-width: 479.98px) { .ez-tiles { grid-template-columns: minmax(0, 1fr); } }
    .ez-tile { padding: 1.05rem 1.05rem 1.15rem; border-radius: 1.2rem; background: #fff; box-shadow: 0 0 0 1px #ece4c8, 0 18px 36px -30px rgb(60 50 10 / .5);
        transition: transform .28s var(--hp-ease), box-shadow .28s var(--hp-ease); }
    .ez-tile.is-lit { transform: translateY(-4px); box-shadow: 0 0 0 2px #f5c518, 0 22px 40px -26px rgb(184 138 0 / .6); }
    .ez-tile em { display: grid; place-items: center; width: 2.6rem; height: 2.6rem; border-radius: .9rem; font-style: normal; font-size: 1.3rem; background: #fff6d6; }
    .ez-tile b { display: block; margin-top: .65rem; font-family: var(--font-heading); font-size: 1.02rem; font-weight: 800; color: #14210c; }
    .ez-tile p { margin-top: .3rem; font-size: .86rem; line-height: 1.55; color: #4b5563; }
    /* Who made it: a band with the sun of the flag, turning slowly. */
    .ez-made { position: relative; overflow: hidden; display: grid; gap: 1.5rem; align-items: center; margin-top: 3rem; padding: 1.8rem 1.5rem; border-radius: 1.8rem; color: #fff;
        background: radial-gradient(30rem 20rem at 0% 0%, rgb(245 197 24 / .22), transparent 60%), linear-gradient(135deg, #1f3a12, #2f5219 60%, #3b6620); box-shadow: 0 30px 60px -36px rgb(20 33 12 / .8); }
    @media (min-width: 760px) { .ez-made { grid-template-columns: 9rem minmax(0, 1fr); padding: 2.2rem 2.4rem; gap: 2.2rem; } }
    .ez-made-sun { width: 7rem; aspect-ratio: 1; margin: 0 auto; }
    .ez-made-sun svg { width: 100%; height: 100%; fill: #f5c518; filter: drop-shadow(0 0 18px rgb(245 197 24 / .6)); animation: ezSun 30s linear infinite; }
    @keyframes ezSun { to { transform: rotate(360deg); } }
    .ez-made-k { font-size: .74rem; font-weight: 900; letter-spacing: .12em; text-transform: uppercase; color: #fde68a; }
    .ez-made h3 { margin-top: .4rem; font-family: var(--font-heading); font-size: clamp(1.35rem, 3vw, 1.9rem); font-weight: 800; line-height: 1.2; }
    .ez-made h3 em { font-style: normal; color: #f5c518; }
    .ez-made-t > p:not(.ez-made-k) { margin-top: .7rem; font-size: .96rem; line-height: 1.65; color: #d6e2cb; }
    .ez-made ul { display: flex; flex-wrap: wrap; gap: .45rem .5rem; margin: 1rem 0 0; padding: 0; list-style: none; }
    .ez-made li { padding: .38rem .75rem; border-radius: 999px; font-size: .8rem; font-weight: 800; color: #fff; background: rgb(255 255 255 / .1); box-shadow: inset 0 0 0 1px rgb(255 255 255 / .2); }
    .ez-made li::before { content: '✓ '; color: #f5c518; }
    @media (prefers-reduced-motion: reduce) {
        .ez-phone, .ez-play, .ez-made-sun svg, .ez-tag { animation: none !important; }
        .ez-pop, .ez-tile { transition: none !important; }
    }
</style>
@endpush
@push('scripts')
<script>
(() => {
    /* The phone shows one easy thing at a time and lights its tile: the video, the Taglish tag, a question in Tagalog,
       offline mode. Only while seen; still when motion is not welcome. */
    document.querySelectorAll('[data-ez-show]').forEach((show) => {
        const pops = [...show.querySelectorAll('[data-ez-pop]')], tag = show.querySelector('[data-ez-tag]');
        const sec = show.closest('.ez'), tiles = sec ? [...sec.querySelectorAll('[data-ez-tile]')] : [];
        const lightOf = [0, 1, 2, 4];
        let k = 0, t = 0;
        const turn = () => {
            pops.forEach((p, i) => p.classList.toggle('is-on', i === k));
            tiles.forEach((el, i) => el.classList.toggle('is-lit', i === lightOf[k]));
            if (k === 1 && tag) { tag.classList.remove('is-tap'); void tag.offsetWidth; tag.classList.add('is-tap'); }
            k = (k + 1) % pops.length;
            t = setTimeout(turn, 3200);
        };
        if (matchMedia('(prefers-reduced-motion: reduce)').matches || !('IntersectionObserver' in window)) { pops[1] && pops[1].classList.add('is-on'); return; }
        new IntersectionObserver((es) => es.forEach((e) => { clearTimeout(t); if (e.isIntersecting) turn(); }), { threshold: .3 }).observe(show);
    });
})();
</script>
@endpush
@endonce
