{{-- One photo, twenty comments, seven answers (2026-10-28, the owner: "this is the main example of innacuracy of solution
     if you ask in social media which could lead to disaster. now blur the sender and the people who commented, show it
     in the homepage and why anee to feature it ... animate the people giving wrong solutions then just say ... this is a
     classic example of why we don't recommend social media solutions since it could lead to disaster ... and say this is
     a real screenshot from a facebook group").

     A real post from a Facebook group of farmers, every name and face blurred (scratchpad fb/blur.py made the pictures in
     images/site/social). The comments scroll on their own while the section is seen; what they said is counted beside
     them, one guess at a time; then why that can go wrong and what Anee does instead. On the homepage and on Why Anee.
     Counted from the screenshots: 20 comments; rice bug 7 (dangaw, atangya, alitangya, ricebag), stem borer 3, too much
     nitrogen 2, neck rot, whitefly, black bug, a dry and acidic field 1 each; 4 said to spray, none asked a question.
     On the ads landing page too (2026-10-28), with $sgSignup (its signup link, carrying the ad's tags), $sgAsk false (no
     way off the page) and $sgLean (what Anee does instead in one line, the page's next sections say the rest), and
     $sgLine, a line from Anee above the heading. --}}
@php
    $sgSignup = $sgSignup ?? route('signup');
    $sgAsk = $sgAsk ?? true;
    $sgLean = $sgLean ?? false;
    $sgGuesses = [
        ['🪲', 'Rice bug', 'dangaw, atangya', 7],
        ['🐛', 'Stem borer', '"white heads"', 3],
        ['🧪', 'Too much nitrogen', 'too much urea', 2],
        ['🍄', 'Neck rot', 'a disease', 1],
        ['🦟', 'Whitefly', '', 1],
        ['⚫', 'Black bug', '', 1],
        ['🏜️', 'A dry and acidic field', '', 1],
    ];
@endphp
<section class="sg" id="social-advice" aria-labelledby="sg-h">
    <div class="sg-in">
        <header class="sg-head" data-sg>
            @if (! empty($sgLine))<div class="mb-5">@include('public.partials.anee-says', ['say' => $sgLine])</div>@endif
            <p class="hp-kick is-red">A real post from a Facebook group</p>
            <h2 class="hp-h2" id="sg-h">One photo. Twenty comments. <em>Seven different answers.</em></h2>
            <p class="hp-p">A farmer posted this photo in a Facebook group of farmers and asked: <b>"Bakit po kaya nagkaganito ang palay ko?"</b> Why did my palay turn out like this? Everyone meant well. Look at what came back.</p>
        </header>

        <div class="sg-grid">
            {{-- The post and its comments, as they are, every name and face blurred. --}}
            <figure class="sg-phone" data-sg style="--d: 120ms">
                <div class="sg-scr">
                    <img class="sg-post" src="{{ asset('images/site/social/facebook-palay-post.webp') }}" alt="A Facebook post asking why the palay turned out like this, with a photo of rice panicles with pale, empty grains" width="699" height="1036" loading="lazy" decoding="async">
                    <div class="sg-comments" aria-label="The comments under the post">
                        <div class="sg-roll">
                            <img src="{{ asset('images/site/social/facebook-palay-comments-1.webp') }}" alt="Comments: stem borer, rice bug, dangaw, neck rot, sucking insects, whitefly or stem borer, atangya, too much nitrogen, rice bug, stem borer white heads, a dry and acidic field" width="720" height="978" loading="lazy" decoding="async">
                            <img src="{{ asset('images/site/social/facebook-palay-comments-2.webp') }}" alt="More comments: not stem borer but atangya, spray a product as soon as you see small moths, you were too late, spray insecticide first, too much urea, black bug, rice bug, and a joke" width="713" height="640" loading="lazy" decoding="async">
                        </div>
                        <span class="sg-fade" aria-hidden="true"></span>
                    </div>
                </div>
                <figcaption>A real screenshot from a Facebook group. Names and faces are blurred.</figcaption>
            </figure>

            {{-- What the comments said, counted. --}}
            <div class="sg-side">
                <p class="sg-k" data-sg style="--d: 160ms">What the twenty comments said it was</p>
                <ul class="sg-guesses">
                    @foreach ($sgGuesses as $i => [$emo, $what, $also, $n])
                        <li data-sg style="--d: {{ 260 + $i * 140 }}ms; --w: {{ round($n / 7 * 100) }}%">
                            <span class="sg-g-e" aria-hidden="true">{{ $emo }}</span>
                            <span class="sg-g-t"><b>{{ $what }}</b>@if ($also)<small>{{ $also }}</small>@endif<i class="sg-g-bar" aria-hidden="true"></i></span>
                            <span class="sg-g-n">{{ $n }}</span>
                        </li>
                    @endforeach
                </ul>
                <div class="sg-tally" data-sg style="--d: 1300ms">
                    <span><b>7</b> different causes</span>
                    <span><b>4</b> said spray now</span>
                    <span class="is-red"><b>0</b> asked a question</span>
                </div>
                {{-- Why following them can end badly: three cards, each a way the harvest is lost (2026-10-28, the owner: "make it look better"). --}}
                <div class="sg-risk" data-sg style="--d: 1450ms">
                    <p class="sg-risk-h"><span aria-hidden="true">⚠️</span>Why that can end in disaster</p>
                    <div class="sg-risk-g">
                        <div class="sg-rk" style="--k: 0"><span class="sg-rk-i" aria-hidden="true">💸</span><div><b>Money sprayed away</b><p>Spray for a pest that is not there and the money is gone, while the real cause keeps eating the crop.</p></div></div>
                        <div class="sg-rk" style="--k: 1"><span class="sg-rk-i" aria-hidden="true">🍄</span><div><b>The wrong medicine</b><p>Neck rot is a disease: no insecticide touches it. A rice bug and a stem borer each need their own timing.</p></div></div>
                        <div class="sg-rk" style="--k: 2"><span class="sg-rk-i" aria-hidden="true">🕷️</span><div><b>The field's friends killed</b><p>Spraying just in case kills the spiders and wasps that eat the pests, so the next attack hits harder.</p></div></div>
                    </div>
                </div>
            </div>
        </div>

        {{-- The point, said plainly. --}}
        <div class="sg-verdict" data-sg>
            <span class="sg-v-i" aria-hidden="true">⚠️</span>
            <p><b>This is a classic example of why we do not recommend solutions from social media.</b> Twenty kind people gave seven different answers, and the farmer still does not know which one is right. Following the wrong one costs money, and it can cost the whole harvest.</p>
        </div>

        {{-- What Anee does instead. --}}
        <div class="sg-anee" data-sg>
            <div class="sg-anee-h">
                <img src="{{ asset('images/anee/emoji/thinking.png') }}" alt="" width="64" height="64" loading="lazy">
                <div><p class="sg-k">What Anee does instead</p><h3>She looks for the sign that tells them apart, then gives one answer.</h3></div>
            </div>
            @unless ($sgLean)
            <ol class="sg-steps">
                <li><b>She studies the photo closely</b><span>The grains, the neck of the panicle, the color of the leaves.</span></li>
                <li><b>She reads your lot</b><span>Its stage, the weather of the last weeks, the fertilizer and the sprays it already had.</span></li>
                <li><b>She asks for the one sign that decides it</b><span>A white head that slides out with a hole at its base is a stem borer. Brown spots on empty grains mean a rice bug. A dark rot at the neck is neck rot, a matter for a fungicide.</span></li>
                <li><b>One answer, with what to do</b><span>What to put on now, how much, when, and how to stop it next season.</span></li>
            </ol>
            @endunless
            <div class="sg-cta">
                <a href="{{ $sgSignup }}" class="btn btn-accent btn-lg">Start free, ask with a photo</a>
                @if ($sgAsk)<a href="{{ url('/ask-anee') }}" class="sg-ghost">Or ask her one question now</a>@endif
            </div>
        </div>
        <p class="sg-fine">A real screenshot from a public Facebook group, shared to show how many answers from strangers can pull a farmer in different directions. Every name and face is blurred. Everyone in it was trying to help.</p>
    </div>
</section>

@once
@push('head')
<style>
    .sg { --e: cubic-bezier(.22,1,.36,1); position: relative; isolation: isolate; padding-block: 4.5rem; overflow: hidden;
        background: radial-gradient(48rem 26rem at 90% 0%, rgb(254 226 226 / .7), transparent 60%), linear-gradient(180deg, #fffaf5 0%, #fff 100%); }
    @media (min-width: 640px) { .sg { padding-block: 6rem; } }
    .sg-in { max-width: 72rem; margin: 0 auto; padding: 0 1rem; }
    @media (min-width: 640px) { .sg-in { padding: 0 1.5rem; } }
    .sg [data-sg] { opacity: 0; transform: translateY(16px); transition: opacity .7s var(--e) var(--d, 0ms), transform .7s var(--e) var(--d, 0ms); }
    .sg.is-in [data-sg] { opacity: 1; transform: none; }
    .sg-head { max-width: 46rem; margin: 0 auto; text-align: center; }
    .sg-head .hp-kick { justify-content: center; }
    .sg-head .hp-p b { color: #1f2937; }
    .sg-k { font-size: .72rem; font-weight: 900; letter-spacing: .12em; text-transform: uppercase; color: #9a3412; }

    .sg-grid { display: grid; gap: 2rem; margin-top: 2.6rem; align-items: start; }
    @media (min-width: 900px) { .sg-grid { grid-template-columns: minmax(0, 23rem) minmax(0, 1fr); gap: 3rem; } }

    /* The phone: the post on top, the comments rolling under it. */
    .sg-phone { margin: 0 auto; width: 100%; max-width: 23rem; }
    .sg-scr { position: relative; border-radius: 2rem; padding: .55rem; background: #111; box-shadow: 0 30px 60px -30px rgb(0 0 0 / .55), 0 0 0 1px rgb(0 0 0 / .08); overflow: hidden; }
    .sg-post { display: block; width: 100%; height: auto; border-radius: 1.5rem 1.5rem .3rem .3rem; }
    .sg-comments { position: relative; height: 15rem; margin-top: .3rem; overflow: hidden; border-radius: .3rem .3rem 1.5rem 1.5rem; background: #242526; }
    .sg-roll img { display: block; width: 100%; height: auto; }
    .sg.is-live .sg-roll { animation: sgRoll 34s linear infinite; }
    .sg-phone:hover .sg-roll { animation-play-state: paused; }
    @keyframes sgRoll { 0%, 4% { transform: translateY(0); } 92%, 100% { transform: translateY(calc(-100% + 15rem)); } }
    .sg-fade { position: absolute; left: 0; right: 0; bottom: 0; height: 3rem; background: linear-gradient(180deg, transparent, #242526); pointer-events: none; }
    .sg-phone figcaption { display: flex; align-items: center; justify-content: center; gap: .4rem; margin-top: .8rem; font-size: .76rem; font-weight: 700; color: #6b7280; text-align: center; }

    /* The guesses, one at a time, each with how many said it. */
    .sg-guesses { display: grid; gap: .5rem; margin: .8rem 0 0; padding: 0; list-style: none; }
    .sg-guesses li { position: relative; display: flex; align-items: center; gap: .7rem; padding: .65rem .8rem; border-radius: 1rem 1rem 1rem .3rem; background: #fff; box-shadow: 0 1px 0 rgb(0 0 0 / .04), 0 0 0 1px #f1e6dc; }
    .sg-guesses li::before { content: ''; position: absolute; left: -.35rem; bottom: .25rem; width: .7rem; height: .7rem; background: #fff; transform: rotate(45deg); box-shadow: -1px 1px 0 #f1e6dc; }
    .sg.is-in .sg-guesses li { animation: sgPop .5s var(--e) both; animation-delay: var(--d); }
    @keyframes sgPop { 0% { transform: translateY(14px) scale(.94); } 60% { transform: translateY(-3px) scale(1.02); } 100% { transform: none; } }
    .sg-g-e { flex: none; width: 2.2rem; height: 2.2rem; border-radius: 999px; display: grid; place-items: center; font-size: 1.1rem; background: #fff7ed; }
    .sg-g-t { flex: 1; min-width: 0; display: grid; gap: .1rem; }
    .sg-g-t b { font-size: .95rem; font-weight: 800; color: #1f2937; }
    .sg-g-t small { font-size: .74rem; color: #6b7280; }
    .sg-g-bar { display: block; height: .3rem; margin-top: .3rem; border-radius: 999px; background: linear-gradient(90deg, #f97316, #dc2626); width: var(--w); transform-origin: left; transform: scaleX(0); transition: transform .9s var(--e) calc(var(--d) + .25s); }
    .sg.is-in .sg-g-bar { transform: none; }
    .sg-g-n { flex: none; min-width: 2.2rem; padding: .2rem .5rem; border-radius: 999px; text-align: center; font-size: .85rem; font-weight: 900; color: #fff; background: #dc2626; }
    .sg-tally { display: grid; grid-template-columns: repeat(3, minmax(0, 1fr)); gap: .45rem; margin-top: 1rem; }
    .sg-tally span { display: grid; gap: .1rem; justify-items: center; padding: .7rem .4rem; border-radius: 1rem; text-align: center; font-size: .72rem; font-weight: 700; color: #6b7280; background: #fff; box-shadow: 0 0 0 1px #f1e6dc; }
    .sg-tally b { font-family: var(--font-heading); font-size: 1.6rem; line-height: 1; color: #1f2937; }
    .sg-tally .is-red b { color: #dc2626; }
    /* Why it can end in disaster: three cards, a red edge, each rising in turn. */
    .sg-risk { margin-top: 1.4rem; }
    .sg-risk-h { display: flex; align-items: center; gap: .5rem; font-family: var(--font-heading); font-size: 1.15rem; font-weight: 800; color: #991b1b; }
    .sg-risk-h span { display: grid; place-items: center; width: 2rem; height: 2rem; border-radius: .7rem; font-size: 1rem; background: #fee2e2; }
    .sg-risk-g { display: grid; gap: .55rem; margin-top: .75rem; }
    .sg-rk { position: relative; display: flex; gap: .8rem; align-items: flex-start; padding: .85rem .95rem .85rem 1.1rem; border-radius: 1rem; overflow: hidden;
        background: linear-gradient(110deg, #fff5f5, #fff 70%); box-shadow: 0 1px 0 rgb(0 0 0 / .03), 0 0 0 1px #fde0e0, 0 14px 28px -24px rgb(153 27 27 / .55);
        transition: transform .3s var(--e), box-shadow .3s var(--e); }
    .sg-rk::before { content: ''; position: absolute; left: 0; top: 0; bottom: 0; width: 4px; background: linear-gradient(180deg, #f87171, #b91c1c); }
    .sg-rk:hover { transform: translateY(-2px); box-shadow: 0 1px 0 rgb(0 0 0 / .03), 0 0 0 1px #fca5a5, 0 18px 32px -22px rgb(153 27 27 / .6); }
    .sg-rk-i { flex: none; width: 2.6rem; height: 2.6rem; border-radius: 999px; display: grid; place-items: center; font-size: 1.3rem; background: #fee2e2; box-shadow: inset 0 0 0 1px #fecaca; }
    .sg-rk b { display: block; font-size: .95rem; font-weight: 800; color: #7f1d1d; }
    .sg-rk p { margin: .2rem 0 0; font-size: .84rem; line-height: 1.5; color: #57534e; }
    .sg.is-in .sg-rk { animation: sgRk .55s var(--e) both; animation-delay: calc(1.55s + var(--k) * .15s); }
    @keyframes sgRk { from { opacity: 0; transform: translateX(-14px); } }
    .sg.is-live .sg-rk-i { animation: sgRkPulse 3s ease-in-out infinite; animation-delay: calc(var(--k) * .5s); }
    @keyframes sgRkPulse { 50% { transform: scale(1.08); box-shadow: inset 0 0 0 1px #fecaca, 0 0 0 6px rgb(254 202 202 / .45); } }

    /* The point. */
    .sg-verdict { display: flex; gap: .9rem; align-items: flex-start; margin-top: 2.4rem; padding: 1.2rem 1.3rem; border-radius: 1.3rem; color: #fff; background: linear-gradient(135deg, #b91c1c, #7f1d1d); box-shadow: 0 24px 50px -26px rgb(127 29 29 / .8); }
    .sg-v-i { flex: none; font-size: 1.8rem; line-height: 1; }
    .sg-verdict p { margin: 0; font-size: 1rem; line-height: 1.6; color: #fee2e2; }
    .sg-verdict b { color: #fff; }
    .sg.is-live .sg-v-i { animation: sgBlink 2.4s ease-in-out infinite; }
    @keyframes sgBlink { 50% { transform: scale(1.12); } }

    /* What Anee does instead. */
    .sg-anee { margin-top: 1.4rem; padding: 1.3rem; border-radius: 1.4rem; background: linear-gradient(160deg, #f3f8ec, #fff); box-shadow: inset 0 0 0 1px #d9e8c6; }
    .sg-anee-h { display: flex; gap: .9rem; align-items: center; }
    .sg-anee-h img { flex: none; width: 3.6rem; height: 3.6rem; }
    .sg-anee-h .sg-k { color: #3f6b1f; }
    .sg-anee-h h3 { margin-top: .2rem; font-family: var(--font-heading); font-size: clamp(1.1rem, 2.6vw, 1.4rem); font-weight: 800; line-height: 1.3; color: #1f2937; }
    .sg-steps { display: grid; gap: .7rem; margin: 1.1rem 0 0; padding: 0; list-style: none; counter-reset: sg; }
    @media (min-width: 900px) { .sg-steps { grid-template-columns: repeat(4, minmax(0, 1fr)); gap: .9rem; } }
    .sg-steps li { position: relative; display: grid; align-content: start; gap: .25rem; padding: .9rem .9rem .9rem 3rem; border-radius: 1rem; background: #fff; box-shadow: 0 0 0 1px #e3ecd8; counter-increment: sg; }
    .sg-steps li::before { content: counter(sg); position: absolute; left: .8rem; top: .85rem; width: 1.6rem; height: 1.6rem; border-radius: 999px; display: grid; place-items: center; font-size: .8rem; font-weight: 900; color: #2b2100; background: #f5c518; }
    .sg-steps b { font-size: .9rem; font-weight: 800; color: #1f2937; }
    .sg-steps span { font-size: .82rem; line-height: 1.5; color: #4b5563; }
    .sg-cta { display: flex; flex-wrap: wrap; gap: .6rem; margin-top: 1.2rem; }
    @media (max-width: 520px) { .sg-cta > * { flex: 1 1 100%; text-align: center; justify-content: center; } }
    .sg-ghost { display: inline-flex; align-items: center; padding: .78rem 1.25rem; border-radius: .9rem; font-weight: 800; color: #2d5016; border: 1.5px solid #b6cf9a; background: #fff; transition: background-color .28s var(--e), border-color .28s var(--e); }
    .sg-ghost:hover { background: #f3f8ec; border-color: #8fbf68; }
    .sg-fine { margin: 1rem auto 0; max-width: 46rem; text-align: center; font-size: .74rem; line-height: 1.5; color: #9ca3af; }
    @media (prefers-reduced-motion: reduce) {
        .sg [data-sg] { opacity: 1; transform: none; transition: none; }
        .sg .sg-roll, .sg .sg-guesses li, .sg .sg-v-i, .sg .sg-rk, .sg .sg-rk-i { animation: none !important; }
        .sg-g-bar { transform: none; transition: none; }
        .sg-comments { height: auto; max-height: 30rem; overflow-y: auto; }
    }
</style>
@endpush
@push('scripts')
<script>
(() => {
    // The section shows itself once on screen (is-in) and rolls its comments only while it is seen (is-live).
    document.querySelectorAll('.sg').forEach((sg) => {
        new IntersectionObserver((list) => list.forEach((en) => {
            if (en.isIntersecting && en.intersectionRatio > 0.06) sg.classList.add('is-in');
            sg.classList.toggle('is-live', en.isIntersecting);
        }), { threshold: [0, 0.06] }).observe(sg);
    });
})();
</script>
@endpush
@endonce
