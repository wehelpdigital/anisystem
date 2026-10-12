{{-- Anee Auto's showcase card, shared by its own page, the dashboard and the
     public homepage. @once. --}}
@once
<style>
    .aa-hero { position: relative; overflow: hidden; display: grid; gap: 1.2rem; padding: 1.3rem 1.25rem; border-radius: 1.5rem; color: #e8f1de;
        background: radial-gradient(130% 150% at 100% 0%, #5d9431 0%, #24410f 52%, #0d1a07 100%); container-type: inline-size; }
    @container (min-width: 700px) { .aa-hero-in { grid-template-columns: minmax(0, 1.15fr) minmax(0, .85fr); } }
    .aa-hero-in { display: grid; gap: 1.2rem; align-items: center; position: relative; z-index: 1; }
    .aa-hero::before { content: ''; position: absolute; inset: 0; opacity: .5; pointer-events: none;
        background-image: radial-gradient(circle at 12% 25%, rgb(255 255 255 / .55) 0 1px, transparent 1.6px), radial-gradient(circle at 70% 65%, rgb(255 255 255 / .35) 0 1px, transparent 1.6px);
        background-size: 130px 120px, 190px 160px; animation: aaDrift 34s linear infinite; }
    @keyframes aaDrift { to { background-position: 130px 120px, -190px 160px; } }
    .aa-kick { display: inline-flex; align-items: center; gap: .4rem; padding: .25rem .65rem; border-radius: 999px; font-size: .7rem; font-weight: 800; letter-spacing: .05em; text-transform: uppercase; color: #2b3a1c; background: #f5c518; }
    .aa-hero h2 { margin-top: .6rem; font-family: var(--font-heading); font-weight: 800; font-size: 1.55rem; line-height: 1.15; color: #fff; }
    .aa-hero p.aa-lead { margin-top: .5rem; font-size: .92rem; line-height: 1.6; color: #cfe3b9; }
    .aa-points { display: grid; gap: .45rem; margin-top: .9rem; }
    .aa-points li { display: flex; gap: .55rem; font-size: .86rem; line-height: 1.45; color: #e8f1de; animation: aaIn .5s cubic-bezier(.22,1,.36,1) both; animation-delay: calc(var(--i) * 80ms + .1s); }
    .aa-points li span { flex: none; width: 1.7rem; height: 1.7rem; border-radius: .6rem; display: grid; place-items: center; background: rgb(255 255 255 / .1); }
    @keyframes aaIn { from { opacity: 0; transform: translateY(8px); } }
    .aa-cta { display: flex; flex-wrap: wrap; align-items: center; gap: .7rem; margin-top: 1.1rem; }
    .aa-go { position: relative; overflow: hidden; display: inline-flex; align-items: center; gap: .5rem; padding: .85rem 1.35rem; border-radius: 999px; font-weight: 800; color: #1d330d; background: #f5c518;
        box-shadow: 0 14px 30px -14px rgb(245 197 24 / .7); transition: transform .28s cubic-bezier(.22,1,.36,1); }
    .aa-go:hover { transform: translateY(-2px); }
    .aa-go::after { content: ''; position: absolute; inset: 0; background: linear-gradient(100deg, transparent 30%, rgb(255 255 255 / .5) 50%, transparent 70%); transform: translateX(-100%); animation: aaShine 3.2s ease-in-out infinite; }
    @keyframes aaShine { 55%, 100% { transform: translateX(100%); } }
    .aa-price { font-size: .78rem; color: #cfe3b9; }
    .aa-price b { color: #fff; }
    /* The phone, with Anee's morning in it */
    .aa-phone { position: relative; margin: 0 auto; width: min(100%, 17.5rem); padding: .7rem; border-radius: 2rem; background: #0b1406; border: 1px solid rgb(255 255 255 / .12); box-shadow: 0 30px 60px -30px rgb(0 0 0 / .8); animation: aaFloat 6s ease-in-out infinite; }
    @keyframes aaFloat { 50% { transform: translateY(-6px); } }
    .aa-screen { border-radius: 1.5rem; padding: .7rem .6rem; background: #f6f9f1; display: grid; gap: .45rem; min-height: 19rem; }
    .aa-b { display: flex; gap: .4rem; align-items: flex-end; opacity: 0; animation: aaPop .5s cubic-bezier(.22,1,.36,1) forwards; animation-delay: calc(var(--d) * 1s + .4s); }
    .aa-b img { flex: none; width: 1.6rem; height: 1.6rem; border-radius: 999px; background: #e4efd4; }
    .aa-b div { padding: .45rem .6rem; border-radius: .9rem .9rem .9rem .25rem; background: #fff; border: 1px solid #e3eadb; font-size: .7rem; line-height: 1.4; color: #1f2a17; }
    .aa-b.is-me { justify-content: flex-end; } .aa-b.is-me div { background: #e7f2da; border-color: #cfe3b8; border-radius: .9rem .9rem .25rem .9rem; }
    .aa-t { display: flex; gap: .4rem; align-items: center; padding: .4rem .5rem; border-radius: .7rem; background: #fff; border: 1px solid #e3eadb; font-size: .66rem; font-weight: 700; color: #1f2a17; margin-top: .25rem; }
    .aa-t i { font-style: normal; } .aa-t em { margin-left: auto; font-style: normal; font-size: .6rem; font-weight: 800; color: #8a5a00; background: #fff4d6; padding: 0 .35rem; border-radius: 999px; }
    @keyframes aaPop { from { opacity: 0; transform: translateY(8px) scale(.97); } to { opacity: 1; transform: none; } }
    .aa-row { display: flex; align-items: center; gap: .7rem; padding: .85rem .95rem; border-radius: 1.1rem; background: var(--color-white); border: 1px solid var(--color-gray-200);
        animation: aaIn .4s cubic-bezier(.22,1,.36,1) both; animation-delay: calc(var(--i) * 50ms); transition: border-color .28s cubic-bezier(.22,1,.36,1), transform .28s cubic-bezier(.22,1,.36,1); }
    .aa-row:hover { border-color: #b7d597; transform: translateY(-2px); }
    .aa-row-e { flex: none; width: 2.6rem; height: 2.6rem; border-radius: .9rem; display: grid; place-items: center; font-size: 1.3rem; background: #eef6e4; }
    .aa-row b { display: block; font-weight: 800; color: var(--color-gray-900); overflow: hidden; text-overflow: ellipsis; white-space: nowrap; }
    .aa-row small { display: block; font-size: .76rem; color: var(--color-gray-500); overflow: hidden; text-overflow: ellipsis; white-space: nowrap; }
    .aa-row-st { flex: none; font-size: .68rem; font-weight: 800; padding: .2rem .55rem; border-radius: 999px; color: #2d5016; background: #e4efd4; }
    .aa-row-st.is-setting_up { color: #8a5a00; background: #fff4d6; } .aa-row-st.is-finished { color: var(--color-gray-500); background: var(--color-gray-100); }
    html.dark .aa-row { background: #161c12; border-color: #2b3a1c; } html.dark .aa-row b { color: #eef3e8; } html.dark .aa-row-e { background: #22301a; }
    @media (prefers-reduced-motion: reduce) { .aa-hero::before, .aa-phone, .aa-go::after, .aa-points li, .aa-row { animation: none; } .aa-b { animation: none; opacity: 1; } }
</style>
@endonce
