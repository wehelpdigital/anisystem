{{-- THE SHOP, COMING SOON (2026-10-23, the owner: "in the main dashboard, add a
     new card section in there called shop then say coming soon ... make it
     look great"). The Shop left the phone's bottom bar for the Blog; until
     it opens, this card on Home is its door.

     A storefront: a striped awning across the top, the deep green of the
     tip of the day under it with a slow sweep of light, the shop's sign and
     a "Coming soon" pill that breathes, then its shelves drifting past (the
     same six the Shop page lists, greyed there), and one quiet link to peek
     in. Every motion stops for a reader who asked for less. --}}
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
            <span class="ss-soon"><i></i>Coming soon</span>
        </div>
        <p class="ss-p">Seeds, fertilizer, tools and safety gear for your season, picked by our technicians and priced for farmers. Opening soon, right here in your app.</p>
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
        <span class="ss-note">
            <svg fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24" aria-hidden="true"><path stroke-linecap="round" stroke-linejoin="round" d="M15 17h5l-1.4-1.4A2 2 0 0118 14.2V11a6 6 0 10-12 0v3.2a2 2 0 01-.6 1.4L4 17h5m6 0v1a3 3 0 11-6 0v-1m6 0H9"/></svg>
            We will tell you in your notifications the day it opens.
        </span>
        <a href="{{ route('shop.index') }}" class="ss-go" data-nav-loader>
            Take a peek
            <svg fill="none" stroke="currentColor" stroke-width="2.4" viewBox="0 0 24 24" aria-hidden="true"><path stroke-linecap="round" stroke-linejoin="round" d="M13 7l5 5-5 5M6 12h12"/></svg>
        </a>
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
    .ss-soon { flex:none; margin-left:auto; display:inline-flex; align-items:center; gap:.45rem; padding:.4rem .85rem; border-radius:999px;
        font-size:.76rem; font-weight:800; white-space:nowrap; color:#1f3312; background:#f5c518; box-shadow:0 8px 20px -10px rgb(245 197 24 / .8); }
    .ss-soon i { width:.5rem; height:.5rem; border-radius:999px; background:#2d5016; animation:ssPulse 1.8s ease-in-out infinite; }
    @keyframes ssPulse { 0%, 100% { transform:scale(1); opacity:1; } 50% { transform:scale(1.5); opacity:.45; } }
    .ss-p { margin-top:.85rem; max-width:36rem; font-size:.9rem; line-height:1.55; color:#c9d5bd; }

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

    .ss-foot { display:flex; flex-wrap:wrap; align-items:center; justify-content:space-between; gap:.7rem 1rem; padding:1rem 1.15rem 1.15rem; }
    @media (min-width:640px) { .ss-foot { padding:1.05rem 1.5rem 1.3rem; } }
    .ss-note { display:inline-flex; align-items:center; gap:.45rem; font-size:.78rem; font-weight:600; color:#a9bb98; }
    .ss-note svg { width:1rem; height:1rem; flex:none; color:#f5c518; }
    .ss-go { display:inline-flex; align-items:center; gap:.4rem; min-height:2.4rem; padding:.45rem 1rem; border-radius:999px; font-size:.82rem; font-weight:800;
        color:#fff; background:rgb(255 255 255 / .1); border:1px solid rgb(255 255 255 / .22); text-decoration:none;
        transition:background-color .28s cubic-bezier(.22,1,.36,1), border-color .28s cubic-bezier(.22,1,.36,1); }
    .ss-go:hover { background:rgb(255 255 255 / .18); border-color:rgb(255 255 255 / .5); }
    .ss-go svg { width:1rem; height:1rem; transition:transform .28s cubic-bezier(.22,1,.36,1); }
    .ss-go:hover svg { transform:translateX(3px); }
    @media (max-width:479.98px) { .ss-soon { padding:.35rem .7rem; font-size:.7rem; } .ss-go { width:100%; justify-content:center; } }

    @media (prefers-reduced-motion: reduce) {
        .ss-card, .ss-glow, .ss-ico, .ss-soon i, .ss-track { animation:none; }
        .ss-shelf { overflow-x:auto; }
        .ss-go, .ss-go svg { transition:none; }
    }
</style>
@endpush
@endonce
