{{-- THE SHOP, COMING SOON (2026-10-23, the owner: "in the main dashboard, add a
     new card section in there called shop then say coming soon ... make it
     look great"). The Shop left the phone's bottom bar for the Blog; until
     it opens, this card on Home is its door.

     A storefront: a striped awning across the top, the deep green of the
     tip of the day under it with a slow sweep of light, the shop's sign and
     a big gold "Coming soon" sign with a light running across it and a glow
     that breathes (the owner, the same day: "remove the take a peek and make
     the coming soon more noticeable"), then its shelves drifting past (the
     same six the Shop page lists) and a bar of shelves still being stocked.
     Every motion stops for a reader who asked for less. --}}
<section class="ss-card" aria-labelledby="ssH">
    <span class="ss-awning" aria-hidden="true"></span>
    <span class="ss-glow" aria-hidden="true"></span>
    <div class="ss-in">
        <div class="ss-head">
            <span class="ss-ico" aria-hidden="true">
                <svg fill="none" stroke="currentColor" stroke-width="1.8" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" d="M3 9l1.2-4.2A2 2 0 016.1 3.5h11.8a2 2 0 011.9 1.3L21 9M3 9v10a2 2 0 002 2h14a2 2 0 002-2V9M3 9h18M9 21v-6a2 2 0 012-2h2a2 2 0 012 2v6"/></svg>
            </span>
            <div class="ss-titles">
                <p class="ss-kick">Shop</p>
                <h2 id="ssH">The Anee Shop</h2>
            </div>
        </div>
        <p class="ss-p">Seeds, fertilizer, tools and safety gear for your season, picked by our technicians and priced for farmers, right here in your app.</p>
        <p class="ss-stamp" role="status"><i aria-hidden="true"></i><span>Coming soon</span></p>
    </div>

    {{-- The shelves, drifting past (twice over, so the loop has no seam). --}}
    <div class="ss-shelf" aria-hidden="true">
        <div class="ss-track">
            @foreach ([1, 2] as $copy)
                @foreach ([
                    ['🌾', 'Seeds and varieties', 'Certified, by crop'],
                    ['🌱', 'Fertilizer and inputs', 'Priced per bag'],
                    ['🛠️', 'Tools and equipment', 'From bolo to pump'],
                    ['🧤', 'Safety gear', 'For every spray day'],
                    ['📖', 'Cheat Sheets', 'Short farm guides'],
                    ['👕', 'Farm wear', 'Made for the field'],
                ] as [$emo, $name, $line])
                    <span class="ss-item"><b>{{ $emo }}</b><span><strong>{{ $name }}</strong><small>{{ $line }}</small></span></span>
                @endforeach
            @endforeach
        </div>
    </div>
    <ul class="sr-only">
        <li>Seeds and varieties</li><li>Fertilizer and inputs</li><li>Tools and equipment</li><li>Safety gear</li><li>Cheat Sheets</li><li>Farm wear</li>
    </ul>

    <div class="ss-foot">
        <div class="ss-stock">
            <span class="ss-stock-k">Stocking the shelves</span>
            <span class="ss-bar" aria-hidden="true"><i></i></span>
        </div>
        <span class="ss-note">
            <svg fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24" aria-hidden="true"><path stroke-linecap="round" stroke-linejoin="round" d="M15 17h5l-1.4-1.4A2 2 0 0118 14.2V11a6 6 0 10-12 0v3.2a2 2 0 01-.6 1.4L4 17h5m6 0v1a3 3 0 11-6 0v-1m6 0H9"/></svg>
            We will tell you in your notifications the day it opens.
        </span>
    </div>
</section>

@once
@push('head')
<style>
    .ss-card { position:relative; overflow:hidden; isolation:isolate; border-radius:1.25rem; color:#e8efe1;
        background:linear-gradient(135deg, #10160c 0%, #1c2416 55%, #24301a 100%);
        box-shadow:0 22px 48px -34px rgb(16 22 12 / .95); animation:ssIn .55s cubic-bezier(.22,1,.36,1) both; }
    @keyframes ssIn { from { opacity:0; transform:translateY(10px); } }
    /* The awning: yellow and cream stripes, scalloped along the bottom. */
    /* A solid band, and under each stripe a rounded tongue: the classic scalloped edge. */
    .ss-awning { position:absolute; inset:0 0 auto 0; height:1.4rem; z-index:2;
        background:repeating-linear-gradient(90deg, #f5c518 0 1.5rem, #fff7d6 1.5rem 3rem);
        -webkit-mask:linear-gradient(#000, #000) 0 0 / 100% .65rem no-repeat,
            radial-gradient(circle at 50% .65rem, #000 .74rem, transparent .76rem) 0 0 / 1.5rem 100% repeat-x;
        mask:linear-gradient(#000, #000) 0 0 / 100% .65rem no-repeat,
            radial-gradient(circle at 50% .65rem, #000 .74rem, transparent .76rem) 0 0 / 1.5rem 100% repeat-x;
        filter:drop-shadow(0 3px 4px rgb(0 0 0 / .35)); }
    .ss-glow { position:absolute; inset:-40% -20%; z-index:-1; pointer-events:none;
        background:radial-gradient(closest-side, rgb(134 181 86 / .32), transparent 70%);
        animation:ssGlow 8s ease-in-out infinite; }
    @keyframes ssGlow { 0%, 100% { transform:translateX(-28%) scale(.9); opacity:.55; } 50% { transform:translateX(28%) scale(1.1); opacity:.9; } }
    .ss-in { position:relative; padding:2rem 1.15rem 0; }
    @media (min-width:640px) { .ss-in { padding:2.15rem 1.5rem 0; } }
    .ss-head { display:flex; align-items:center; gap:.8rem; }
    .ss-ico { flex:none; width:2.9rem; height:2.9rem; border-radius:.95rem; display:grid; place-items:center; color:#fff;
        background:rgb(255 255 255 / .1); box-shadow:inset 0 0 0 1px rgb(255 255 255 / .12); animation:ssBob 3.4s ease-in-out infinite; }
    .ss-ico svg { width:1.55rem; height:1.55rem; }
    @keyframes ssBob { 0%, 100% { transform:translateY(0); } 50% { transform:translateY(-3px); } }
    .ss-titles { min-width:0; }
    .ss-kick { font-size:.7rem; font-weight:800; letter-spacing:.12em; text-transform:uppercase; color:#f5c518; }
    .ss-titles h2 { font-family:var(--font-heading); font-size:1.25rem; font-weight:800; line-height:1.15; color:#fff; }
    @media (min-width:640px) { .ss-titles h2 { font-size:1.4rem; } }
    .ss-p { margin-top:.85rem; max-width:36rem; font-size:.9rem; line-height:1.55; color:#c9d5bd; }

    /* The sign: big, gold, a light running across it and a glow that breathes. */
    .ss-stamp { position:relative; overflow:hidden; display:inline-flex; align-items:center; gap:.7rem; margin-top:1.05rem;
        padding:.8rem 1.5rem .8rem 1.15rem; border-radius:999px; color:#1f3312;
        background:linear-gradient(100deg, #f5c518 0%, #fde68a 45%, #f5c518 70%, #e9a80b 100%);
        box-shadow:0 0 0 0 rgb(245 197 24 / .55), 0 14px 30px -14px rgb(245 197 24 / .9);
        animation:ssGlowRing 2.2s ease-out infinite; }
    .ss-stamp span { position:relative; font-family:var(--font-heading); font-size:clamp(1.15rem, 3.6vw, 1.45rem); font-weight:900;
        letter-spacing:.12em; text-transform:uppercase; line-height:1; }
    .ss-stamp i { position:relative; flex:none; width:.8rem; height:.8rem; border-radius:999px; background:#2d5016;
        box-shadow:0 0 0 0 rgb(45 80 22 / .5); animation:ssDot 1.6s ease-out infinite; }
    .ss-stamp::after { content:''; position:absolute; inset:0; pointer-events:none;
        background:linear-gradient(105deg, transparent 30%, rgb(255 255 255 / .75) 50%, transparent 70%);
        transform:translateX(-120%); animation:ssShine 3.2s cubic-bezier(.22,1,.36,1) infinite 1s; }
    @keyframes ssGlowRing { 0% { box-shadow:0 0 0 0 rgb(245 197 24 / .55), 0 14px 30px -14px rgb(245 197 24 / .9); }
        70%, 100% { box-shadow:0 0 0 16px rgb(245 197 24 / 0), 0 14px 30px -14px rgb(245 197 24 / .9); } }
    @keyframes ssDot { 0% { box-shadow:0 0 0 0 rgb(45 80 22 / .55); } 70%, 100% { box-shadow:0 0 0 9px rgb(45 80 22 / 0); } }
    @keyframes ssShine { 0%, 55% { transform:translateX(-120%); } 100% { transform:translateX(120%); } }
    @media (max-width:479.98px) { .ss-stamp { display:flex; justify-content:center; } }

    .ss-shelf { position:relative; margin-top:1rem; overflow:hidden;
        -webkit-mask-image:linear-gradient(90deg, transparent, #000 8%, #000 92%, transparent);
        mask-image:linear-gradient(90deg, transparent, #000 8%, #000 92%, transparent); }
    .ss-track { display:flex; gap:.6rem; width:max-content; padding:.15rem 0 .2rem; animation:ssDrift 38s linear infinite; }
    .ss-card:hover .ss-track { animation-play-state:paused; }
    @keyframes ssDrift { to { transform:translateX(-50%); } }
    .ss-item { flex:none; display:inline-flex; align-items:center; gap:.6rem; padding:.55rem .85rem .55rem .6rem; border-radius:.95rem;
        background:rgb(255 255 255 / .06); border:1px solid rgb(255 255 255 / .09); }
    .ss-item b { display:grid; place-items:center; width:2.2rem; height:2.2rem; border-radius:.7rem; font-size:1.2rem; background:rgb(255 255 255 / .08); }
    .ss-item strong { display:block; font-size:.82rem; font-weight:800; color:#f2f7ec; white-space:nowrap; }
    .ss-item small { display:block; font-size:.7rem; color:#a9bb98; white-space:nowrap; }

    .ss-foot { display:grid; gap:.75rem; padding:1rem 1.15rem 1.2rem; }
    @media (min-width:640px) { .ss-foot { grid-template-columns:minmax(0, 1fr) auto; align-items:center; gap:1.5rem; padding:1.05rem 1.5rem 1.3rem; } }
    /* Shelves still being stocked: a striped bar that keeps moving. */
    .ss-stock { display:grid; gap:.4rem; }
    .ss-stock-k { font-size:.72rem; font-weight:800; letter-spacing:.08em; text-transform:uppercase; color:#f5c518; }
    .ss-bar { position:relative; display:block; height:.6rem; border-radius:999px; overflow:hidden; background:rgb(255 255 255 / .08); }
    .ss-bar i { position:absolute; inset:0 30% 0 0; border-radius:999px;
        background:repeating-linear-gradient(45deg, #f5c518 0 .6rem, #e9a80b .6rem 1.2rem); background-size:1.7rem 100%;
        animation:ssStripes .9s linear infinite, ssFill 4.5s cubic-bezier(.22,1,.36,1) infinite alternate; }
    @keyframes ssStripes { to { background-position:1.7rem 0; } }
    @keyframes ssFill { from { right:62%; } to { right:22%; } }
    .ss-note { display:inline-flex; align-items:center; gap:.45rem; font-size:.78rem; font-weight:600; color:#a9bb98; }
    .ss-note svg { width:1rem; height:1rem; flex:none; color:#f5c518; }

    @media (prefers-reduced-motion: reduce) {
        .ss-card, .ss-glow, .ss-ico, .ss-track, .ss-stamp, .ss-stamp i, .ss-stamp::after, .ss-bar i { animation:none; }
        .ss-stamp::after { display:none; }
        .ss-shelf { overflow-x:auto; }
    }
</style>
@endpush
@endonce
