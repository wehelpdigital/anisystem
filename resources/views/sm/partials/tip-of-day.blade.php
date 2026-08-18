{{-- The AI technician's tip of the day.

     One thing worth knowing, chosen for what this grower's crops are actually
     doing today and held steady until tomorrow — a tip that changes on every
     page load reads as noise rather than advice.

     Expects: $tip (from App\Support\FarmTips::forToday), and optionally
     $aiHref for the "ask about this" link. --}}
@php $tip = $tip ?? null; @endphp
@if ($tip)
<div class="tod" role="note">
    <span class="tod-glow" aria-hidden="true"></span>
    <div class="tod-head">
        <span class="tod-bulb" aria-hidden="true">
            <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.9"><path stroke-linecap="round" stroke-linejoin="round" d="M9.5 17h5M10 21h4M12 3a6 6 0 00-3.5 10.9c.6.45.9 1.1.9 1.8V16h5.2v-.3c0-.7.3-1.35.9-1.8A6 6 0 0012 3z"/></svg>
        </span>
        <span class="min-w-0">
            <span class="tod-kicker">Tip of the day</span>
            <span class="tod-src">{{ $tip['source'] }}</span>
        </span>
        @if ($tip['scope'] === 'crop')
            <span class="tod-badge">Your crop, today</span>
        @endif
    </div>
    <p class="tod-text">{{ $tip['text'] }}</p>
    {{-- The tip stays — it is worth knowing whoever reads it — but the way
         through to the technician does not: sm.ai answers a worker with a 404,
         and a link that lands there teaches them the tip is broken too. --}}
    @if (! empty($aiHref) && ! \App\Support\WorkerContext::activeGrant())
        <a class="tod-ask" href="{{ $aiHref }}">
            Ask the AI technician about this
            <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.2"><path stroke-linecap="round" stroke-linejoin="round" d="M9 5l7 7-7 7"/></svg>
        </a>
    @endif
</div>

<style>
    .tod { position: relative; overflow: hidden; border-radius: 1rem; padding: .95rem 1.05rem;
        background: linear-gradient(135deg, #10160c 0%, #1c2416 55%, #24301a 100%);
        color: #e8efe1; margin-bottom: 1rem;
        box-shadow: 0 16px 40px -30px rgb(16 22 12 / .9);
        animation: todIn .45s cubic-bezier(.22,1,.36,1) both; }
    @keyframes todIn { from { opacity: 0; transform: translateY(.6rem); } }
    /* A slow sweep of light across the card — enough to catch the eye once,
       not enough to keep pulling at it. */
    .tod-glow { position: absolute; inset: -40% -10%; pointer-events: none;
        background: radial-gradient(closest-side, rgb(134 181 86 / .35), transparent 70%);
        animation: todGlow 7s ease-in-out infinite; }
    @keyframes todGlow {
        0%, 100% { transform: translateX(-30%) scale(.9); opacity: .5; }
        50% { transform: translateX(30%) scale(1.1); opacity: .85; }
    }
    .tod-head { position: relative; display: flex; align-items: center; gap: .6rem; }
    .tod-bulb { flex: 0 0 auto; width: 2.2rem; height: 2.2rem; border-radius: .7rem;
        display: inline-flex; align-items: center; justify-content: center;
        background: rgb(134 181 86 / .18); color: #a8cc7e;
        animation: todPulse 3.2s ease-in-out infinite; }
    @keyframes todPulse { 0%, 100% { box-shadow: 0 0 0 0 rgb(134 181 86 / .35); } 50% { box-shadow: 0 0 0 .5rem rgb(134 181 86 / 0); } }
    .tod-bulb svg { width: 1.2rem; height: 1.2rem; }
    .tod-kicker { display: block; font-size: .62rem; font-weight: 800; letter-spacing: .09em;
        text-transform: uppercase; color: #a8cc7e; }
    .tod-src { display: block; font-size: .78rem; font-weight: 700; color: #cdd8c0;
        overflow: hidden; text-overflow: ellipsis; white-space: nowrap; }
    .tod-badge { margin-left: auto; flex: 0 0 auto; font-size: .58rem; font-weight: 800;
        text-transform: uppercase; letter-spacing: .05em; padding: .2rem .5rem; border-radius: 999px;
        background: rgb(168 204 126 / .18); color: #bfe19a; }
    .tod-text { position: relative; font-size: .92rem; line-height: 1.55; margin-top: .6rem; color: #f1f6ec; }
    .tod-ask { position: relative; display: inline-flex; align-items: center; gap: .3rem; margin-top: .6rem;
        font-size: .74rem; font-weight: 800; color: #a8cc7e; text-decoration: none; }
    .tod-ask:hover { color: #cdd8c0; }
    .tod-ask svg { width: .85rem; height: .85rem; }
    @media (prefers-reduced-motion: reduce) {
        .tod, .tod-glow, .tod-bulb { animation: none; }
    }
</style>
@endif
