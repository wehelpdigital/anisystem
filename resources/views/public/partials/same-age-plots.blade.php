{{-- Two plots, the same age and variety (2026-10-29, the owner: "the right part uses anee guidance and left does not.
     its the same age and variety. tell that and make sure it looks great"). The owner's own photo, the levee between
     the plots: the left farmed the usual way, the right on Anee's guidance. On the homepage and on Anee vs Now.

     When it comes into view: the photo settles in, the labels drop onto each side, the right side lights up, and the callouts under each side pop in. Expects $signup. --}}
@php
    $saTick = '<svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="3" aria-hidden="true"><path stroke-linecap="round" stroke-linejoin="round" d="M5 13l4 4L19 7"/></svg>';
    $saX = '<svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="3" aria-hidden="true"><path stroke-linecap="round" d="M7 7l10 10M17 7L7 17"/></svg>';
@endphp
<section class="hp-sec sa" id="same-age" aria-labelledby="saH">
    <div class="max-w-6xl mx-auto px-4 sm:px-6">
        <div class="hp-head reveal">
            <p class="hp-kick">Real photo from the field</p>
            <h2 class="hp-h2" id="saH">Same Age. Same Variety. <em>One Followed Anee.</em></h2>
            <p class="hp-p">Two plots side by side, the same variety, planted on the same day. The left was farmed the usual way. The right followed Anee's guidance. The difference shows in the color and in how full the plants grew.</p>
        </div>

        <figure class="sa-fig" data-sa>
            <div class="sa-stage">
            <div class="sa-photo">
                <img src="{{ asset('images/site/photos/same-age-plots.webp') }}" srcset="{{ asset('images/site/photos/same-age-plots-900.webp') }} 900w, {{ asset('images/site/photos/same-age-plots.webp') }} 1600w" sizes="(min-width: 1024px) 60rem, 100vw" alt="Two rice plots of the same age and variety on either side of a levee: the left paler and thinner, the right deep green and fuller" width="1600" height="1200" loading="lazy">
                <span class="sa-side is-left" aria-hidden="true"></span>
                <span class="sa-side is-right" aria-hidden="true"></span>
                <span class="sa-tag is-left">The usual way</span>
                <span class="sa-tag is-right"><img src="{{ asset('images/anee/avatar-160.jpg') }}" alt="" width="40" height="40">With Anee's guidance</span>
                <span class="sa-same"><b>Same age</b><i aria-hidden="true">·</i><b>Same variety</b></span>
                <span class="sa-real"><i aria-hidden="true"></i>Real photo</span>
            </div>
            {{-- The Anee side, up close (2026-10-29, the owner: "use this photo in a circle mask animated to look like it
                 is the zoomed version of the side of anee"): a ring lands on the right plot, a beam opens from it, and a
                 round lens shows the owner's close photo of that side. The script lays the beam along the two lines that
                 touch both circles, whatever the width. --}}
            <svg class="sa-beam" aria-hidden="true"><path class="sa-beam-f"/><path class="sa-beam-l" pathLength="1"/><path class="sa-beam-l" pathLength="1"/></svg>
            <span class="sa-aim" aria-hidden="true"><i></i></span>
            <span class="sa-lens">
                <span class="sa-lens-c"><img src="{{ asset('images/site/photos/same-age-closeup.webp') }}" alt="The Anee side up close: thick, deep green palay with dew on its leaves" width="720" height="720" loading="lazy"></span>
                <b><img src="{{ asset('images/anee/avatar-160.jpg') }}" alt="" width="40" height="40">Up close, the Anee side</b>
            </span>
            </div>
            <figcaption class="sa-notes">
                <div class="sa-note is-left">
                    <p class="sa-note-h">The usual way</p>
                    <ul>
                        <li>{!! $saX !!}Paler, yellowing leaves</li>
                        <li>{!! $saX !!}A thinner stand</li>
                    </ul>
                </div>
                <div class="sa-note is-right">
                    <p class="sa-note-h"><img src="{{ asset('images/anee/avatar-160.jpg') }}" alt="" width="40" height="40">With Anee's guidance</p>
                    <ul>
                        <li>{!! $saTick !!}Deep green, healthy leaves</li>
                        <li>{!! $saTick !!}A fuller, thicker stand</li>
                    </ul>
                </div>
            </figcaption>
        </figure>

        <div class="hp-cta is-tight reveal">
            <div class="hp-cta-row">
                <a href="{{ $signup }}" class="btn btn-accent btn-lg hp-go">Farm the right side <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.2" aria-hidden="true"><path stroke-linecap="round" stroke-linejoin="round" d="M13 7l5 5-5 5M6 12h12"/></svg></a>
            </div>
            <p class="hp-cta-note">Start free. Anee's guidance comes with Libre + Anee.</p>
        </div>
    </div>
</section>

@once
@push('head')
<style>
    .sa { position: relative; overflow: hidden; border-top: 1px solid #ecd9a0;
        background: radial-gradient(rgb(184 138 0 / .1) 1px, transparent 1.4px) 0 0 / 18px 18px, radial-gradient(50rem 26rem at 85% 0%, #fdeeb8, transparent 65%), linear-gradient(180deg, #fff8e6, #fdf4dc); }
    .sa::before { content: ''; position: absolute; left: 0; right: 0; top: 0; height: 4px; background: linear-gradient(90deg, #6b9f3d, #f5c518 50%, #6b9f3d); }
    .sa-fig { max-width: 60rem; margin: 2.4rem auto 0; }
    .sa-photo { position: relative; aspect-ratio: 4 / 3; border-radius: 1.8rem; overflow: hidden; background: #2b3a1c;
        box-shadow: 0 0 0 1px rgb(184 138 0 / .35), 0 28px 50px -34px rgb(20 33 12 / .6); }
    .sa-photo > img { display: block; width: 100%; height: 100%; object-fit: cover; transform: scale(1.06); transition: transform 1.6s cubic-bezier(.22,1,.36,1); }
    .sa-fig.is-in .sa-photo > img { transform: scale(1); }
    /* Each side's wash, the left a little grey, the right a little gold; each follows the levee, which runs from 43%
       across at the top to 40% at the bottom of the photo. */
    .sa-side { position: absolute; inset: 0; pointer-events: none; opacity: 0; transition: opacity .9s ease;
        -webkit-mask-image: linear-gradient(180deg, transparent 10%, #000 28%); mask-image: linear-gradient(180deg, transparent 10%, #000 28%); }
    .sa-side.is-left { clip-path: polygon(0 0, 43% 0, 40% 100%, 0 100%); background: linear-gradient(90deg, rgb(20 20 20 / .24), rgb(20 20 20 / .06)); transition-delay: .9s; }
    .sa-side.is-right { clip-path: polygon(43% 0, 100% 0, 100% 100%, 40% 100%); background: radial-gradient(60% 80% at 72% 40%, rgb(253 230 138 / .24), transparent 70%); transition-delay: 1.1s; }
    .sa-fig.is-in .sa-side { opacity: 1; }
    .sa-tag { position: absolute; z-index: 2; top: 1.1rem; display: inline-flex; align-items: center; gap: .45rem; padding: .45rem .9rem; border-radius: 999px; font-size: .86rem; font-weight: 900;
        box-shadow: 0 12px 26px -12px rgb(0 0 0 / .7); opacity: 0; transform: translateY(-10px); transition: opacity .5s cubic-bezier(.22,1,.36,1), transform .5s cubic-bezier(.22,1,.36,1); }
    .sa-tag.is-left { left: 1.1rem; color: #fff; background: rgb(60 60 60 / .78); backdrop-filter: blur(6px); -webkit-backdrop-filter: blur(6px); transition-delay: .3s; }
    .sa-tag.is-right { right: 1.1rem; color: #14210c; background: #f5c518; transition-delay: .45s; }
    .sa-tag img { width: 1.5rem; height: 1.5rem; margin-left: -.4rem; border-radius: 999px; object-fit: cover; box-shadow: 0 0 0 2px #fff; }
    .sa-fig.is-in .sa-tag { opacity: 1; transform: none; }
    .sa-same { position: absolute; z-index: 2; left: 50%; bottom: 1.1rem; display: inline-flex; align-items: center; gap: .5rem; padding: .5rem 1rem; border-radius: 999px; white-space: nowrap;
        font-size: .9rem; color: #fff; background: rgb(10 14 7 / .72); backdrop-filter: blur(6px); -webkit-backdrop-filter: blur(6px); box-shadow: inset 0 0 0 1px rgb(255 255 255 / .25);
        opacity: 0; transform: translate(-50%, 10px); transition: opacity .5s cubic-bezier(.22,1,.36,1) 1.5s, transform .5s cubic-bezier(.22,1,.36,1) 1.5s; }
    .sa-same b { font-weight: 900; }
    .sa-same i { font-style: normal; color: #f5c518; }
    .sa-fig.is-in .sa-same { opacity: 1; transform: translate(-50%, 0); }
    .sa-real { position: absolute; z-index: 2; right: 1.1rem; bottom: 1.2rem; display: inline-flex; align-items: center; gap: .35rem; padding: .3rem .65rem .3rem .5rem; border-radius: 999px;
        font-size: .68rem; font-weight: 900; letter-spacing: .06em; text-transform: uppercase; color: #f5c518; background: rgb(10 14 7 / .62); }
    .sa-real i { width: .45rem; height: .45rem; border-radius: 999px; background: #ef4444; }
    /* The Anee side up close: a ring on the right plot, a beam from it, and a round lens with the close photo. The lens
       hangs past the photo's edge where the page has room. */
    .sa-stage { position: relative; }
    .sa-beam { position: absolute; inset: 0; z-index: 2; width: 100%; height: 100%; overflow: visible; pointer-events: none; }
    .sa-beam-f { fill: rgb(255 255 255 / .16); opacity: 0; transition: opacity .6s ease 1.6s; }
    .sa-beam-l { fill: none; stroke: rgb(255 255 255 / .92); stroke-width: 1.6; stroke-linecap: round; stroke-dasharray: 1; stroke-dashoffset: 1;
        filter: drop-shadow(0 1px 2px rgb(0 0 0 / .45)); transition: stroke-dashoffset .6s cubic-bezier(.45,0,.25,1) 1.45s; }
    .sa-fig.is-in .sa-beam-f { opacity: 1; }
    .sa-fig.is-in .sa-beam-l { stroke-dashoffset: 0; }
    .sa-aim { position: absolute; z-index: 2; left: 62%; top: 70%; width: 7%; aspect-ratio: 1; border-radius: 999px; transform: translate(-50%, -50%);
        box-shadow: 0 0 0 2px #fff, 0 0 0 4px rgb(245 197 24 / .95), 0 0 22px rgb(245 197 24 / .6), inset 0 0 0 999px rgb(255 255 255 / .08);
        opacity: 0; scale: .2; transition: opacity .4s ease 1.2s, scale .55s cubic-bezier(.34,1.56,.64,1) 1.2s; }
    .sa-aim i { position: absolute; inset: 0; border-radius: 999px; border: 2px solid #f5c518; opacity: 0; }
    .sa-fig.is-in .sa-aim { opacity: 1; scale: 1; }
    .sa-fig.is-in .sa-aim i { animation: saPing 1.8s ease-out 1.8s infinite; }
    @keyframes saPing { 0% { opacity: .9; transform: scale(1); } 100% { opacity: 0; transform: scale(2.2); } }
    .sa-lens { position: absolute; z-index: 3; right: 3%; top: 24%; width: 36%; aspect-ratio: 1; transform-origin: 15% 85%;
        opacity: 0; scale: .25; transition: opacity .45s ease 1.75s, scale .8s cubic-bezier(.34,1.4,.64,1) 1.75s; }
    @media (min-width: 1100px) { .sa-lens { right: -6%; top: 22%; width: 33%; } }
    .sa-fig.is-in .sa-lens { opacity: 1; scale: 1; }
    .sa-lens-c { position: absolute; inset: 0; overflow: hidden; border-radius: 999px; background: #2b3a1c;
        box-shadow: 0 0 0 5px #fff, 0 0 0 9px #f5c518, 0 30px 60px -20px rgb(20 33 12 / .75); }
    .sa-lens-c img { display: block; width: 100%; height: 100%; object-fit: cover; scale: 1.7; transition: scale 1.6s cubic-bezier(.22,1,.36,1) 1.75s; }
    .sa-fig.is-in .sa-lens-c img { scale: 1.08; animation: saDrift 16s ease-in-out 3.4s infinite alternate; }
    @keyframes saDrift { to { transform: translate(-3%, -2%) scale(1.06); } }
    /* The lens's glass: a soft shine at the top and a darker edge. */
    .sa-lens-c::after { content: ''; position: absolute; inset: 0; border-radius: inherit; pointer-events: none;
        background: radial-gradient(60% 45% at 32% 20%, rgb(255 255 255 / .32), rgb(255 255 255 / 0) 70%); box-shadow: inset 0 0 26px rgb(0 0 0 / .45); }
    .sa-lens b { position: absolute; left: 50%; bottom: -1rem; z-index: 1; width: max-content; display: inline-flex; align-items: center; gap: .4rem; padding: .35rem .8rem .35rem .4rem; border-radius: 999px;
        white-space: nowrap; font-size: .8rem; font-weight: 900; color: #14210c; background: #f5c518; box-shadow: 0 12px 24px -12px rgb(0 0 0 / .7);
        transform: translateX(-50%) translateY(8px); opacity: 0; transition: opacity .4s ease 2.35s, transform .45s cubic-bezier(.22,1,.36,1) 2.35s; }
    .sa-lens b img { width: 1.4rem; height: 1.4rem; border-radius: 999px; object-fit: cover; box-shadow: 0 0 0 2px #fff; }
    .sa-fig.is-in .sa-lens b { opacity: 1; transform: translateX(-50%) translateY(0); }
    /* The callouts under each side. */
    .sa-notes { position: relative; z-index: 2; display: grid; grid-template-columns: minmax(0, .9fr) minmax(0, 1.1fr); gap: .8rem; margin-top: 1.4rem; }
    .sa-note { padding: .9rem 1rem 1rem; border-radius: 1.2rem; opacity: 0; transform: translateY(12px); transition: opacity .55s cubic-bezier(.22,1,.36,1), transform .55s cubic-bezier(.22,1,.36,1); }
    .sa-note.is-left { background: #fff; box-shadow: inset 0 0 0 1px #e6e2d6; transition-delay: 1.9s; }
    .sa-note.is-right { background: linear-gradient(160deg, #fff, #f3f9ea); box-shadow: inset 0 0 0 1.5px #c9dcb4, 0 14px 28px -22px rgb(47 82 25 / .55); transition-delay: 2.05s; }
    .sa-fig.is-in .sa-note { opacity: 1; transform: none; }
    .sa-note-h { display: flex; align-items: center; gap: .45rem; font-size: .74rem; font-weight: 900; letter-spacing: .08em; text-transform: uppercase; color: #6b7280; }
    .sa-note.is-right .sa-note-h { color: #3d6823; }
    .sa-note-h img { width: 1.4rem; height: 1.4rem; border-radius: 999px; object-fit: cover; box-shadow: 0 0 0 2px #f5c518; }
    .sa-note ul { display: grid; gap: .35rem; margin: .55rem 0 0; padding: 0; list-style: none; }
    .sa-note li { display: flex; align-items: center; gap: .5rem; font-size: .95rem; font-weight: 700; line-height: 1.35; color: #4b5563; }
    .sa-note.is-right li { color: #14210c; }
    .sa-note li svg { flex: none; width: 1.3rem; height: 1.3rem; padding: .25rem; border-radius: 999px; color: #fff; background: #9ca3af; }
    .sa-note.is-right li svg { background: #4a7c2a; }
    @media (max-width: 640px) {
        .sa-photo { border-radius: 1.3rem; }
        .sa-tag { top: .7rem; padding: .35rem .65rem; font-size: .7rem; }
        .sa-tag.is-left { left: .7rem; } .sa-tag.is-right { right: .7rem; }
        .sa-tag img { width: 1.2rem; height: 1.2rem; }
        .sa-same { bottom: .7rem; padding: .38rem .75rem; font-size: .74rem; }
        .sa-real { display: none; }
        .sa-notes { gap: .5rem; }
        .sa-note { padding: .7rem .7rem .75rem; }
        .sa-note-h { font-size: .62rem; letter-spacing: .05em; }
        .sa-note-h img { width: 1.1rem; height: 1.1rem; }
        .sa-note li { font-size: .8rem; gap: .4rem; }
        .sa-note li svg { width: 1.1rem; height: 1.1rem; padding: .2rem; }
        /* On a phone the lens sits higher and the ring lower, so the beam between them shows. */
        .sa-lens { top: 17%; width: 31%; }
        .sa-aim { left: 57%; top: 77%; width: 8%; }
        .sa-lens-c { box-shadow: 0 0 0 3px #fff, 0 0 0 6px #f5c518, 0 20px 40px -16px rgb(20 33 12 / .75); }
        .sa-lens b { bottom: -.85rem; gap: .3rem; padding: .25rem .6rem .25rem .3rem; font-size: .62rem; }
        .sa-lens b img { width: 1.05rem; height: 1.05rem; }
    }
    @media (prefers-reduced-motion: reduce) {
        .sa-photo > img, .sa-side, .sa-tag, .sa-same, .sa-note, .sa-beam-f, .sa-beam-l, .sa-aim, .sa-lens, .sa-lens-c img, .sa-lens b { transition: none !important; }
        .sa-aim i, .sa-lens-c img { animation: none !important; }
    }
</style>
@endpush
@push('scripts')
<script>
(() => {
    // The two plots play in once, the first time they are seen.
    document.querySelectorAll('[data-sa]').forEach((fig) => {
        // The beam from the ring to the lens: the two lines that touch both circles from outside, and the band between
        // them. Laid out from the boxes before any scaling, again whenever the photo changes size.
        const stage = fig.querySelector('.sa-stage'), svg = fig.querySelector('.sa-beam'), aim = fig.querySelector('.sa-aim'), lens = fig.querySelector('.sa-lens');
        if (stage && svg && aim && lens) {
            const [band, l1, l2] = svg.querySelectorAll('path');
            const lay = () => {
                const W = stage.offsetWidth, H = stage.offsetHeight;
                const r1 = aim.offsetWidth / 2, x1 = aim.offsetLeft, y1 = aim.offsetTop; // its box is pulled back by half, so left/top are its centre
                const r2 = lens.offsetWidth / 2, x2 = lens.offsetLeft + r2, y2 = lens.offsetTop + r2;
                const dx = x2 - x1, dy = y2 - y1, d = Math.hypot(dx, dy);
                if (!W || d <= Math.abs(r2 - r1)) return;
                const t = Math.atan2(dy, dx), a = Math.acos((r1 - r2) / d);
                const at = (x, y, r, k) => [x + r * Math.cos(t + k * a), y + r * Math.sin(t + k * a)].map((v) => v.toFixed(1)).join(' ');
                svg.setAttribute('viewBox', `0 0 ${W} ${H}`);
                l1.setAttribute('d', `M${at(x1, y1, r1, 1)}L${at(x2, y2, r2, 1)}`);
                l2.setAttribute('d', `M${at(x1, y1, r1, -1)}L${at(x2, y2, r2, -1)}`);
                band.setAttribute('d', `M${at(x1, y1, r1, 1)}L${at(x2, y2, r2, 1)}L${at(x2, y2, r2, -1)}L${at(x1, y1, r1, -1)}Z`);
            };
            lay();
            if ('ResizeObserver' in window) new ResizeObserver(lay).observe(stage); else addEventListener('resize', lay);
        }
        if (matchMedia('(prefers-reduced-motion: reduce)').matches || !('IntersectionObserver' in window)) { fig.classList.add('is-in'); return; }
        const io = new IntersectionObserver((es) => { if (es.some((e) => e.isIntersecting)) { fig.classList.add('is-in'); io.disconnect(); } }, { threshold: .3 });
        io.observe(fig);
    });
})();
</script>
@endpush
@endonce
