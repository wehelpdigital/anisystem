{{-- The crop variety pages' look (2026-10-13): the crop list at
     /crop-varieties and each crop's finder. The finder cards themselves
     wear variety-finder-css. --}}
<style>
    .cv-hero { position: relative; margin: 0; border-radius: 1.6rem; overflow: hidden; background: #e4efd4; box-shadow: 0 30px 60px -40px rgb(20 33 12 / .7); aspect-ratio: 4 / 3; }
    .cv-hero img { width: 100%; height: 100%; object-fit: cover; display: block; }
    .cv-hero-stats { position: absolute; left: .8rem; right: .8rem; bottom: .8rem; display: flex; flex-wrap: wrap; gap: .45rem; }
    .cv-hero-stats span { display: inline-flex; flex-direction: column; padding: .5rem .75rem; border-radius: .9rem; font-size: .72rem; font-weight: 800; color: #3f4b36;
        background: rgb(255 255 255 / .92); backdrop-filter: blur(6px); line-height: 1.2; }
    .cv-hero-stats b { font-family: var(--font-heading); font-size: 1.05rem; color: #1f3410; }

    .rv-stats { display: grid; grid-template-columns: repeat(2, minmax(0, 1fr)); gap: .7rem; }
    .rv-stat { padding: 1rem 1.1rem; border-radius: 1.2rem; background: #fff; border: 1px solid #e1ecd3; box-shadow: 0 18px 36px -30px rgb(20 33 12 / .5); }
    .rv-stat b { display: block; font-family: var(--font-heading); font-size: clamp(1.6rem, 3vw, 2.1rem); font-weight: 800; line-height: 1; color: #2d5016; }
    .rv-stat span { display: block; margin-top: .35rem; font-size: .82rem; font-weight: 700; color: #5b6b4c; line-height: 1.35; }
    .rv-stat.is-gold b { color: #a07800; }

    .rv-pop { margin-top: 1.2rem; display: flex; flex-wrap: wrap; gap: .45rem; }
    .rv-pop a { display: inline-flex; align-items: center; gap: .35rem; padding: .45rem .85rem; border-radius: 999px; font-size: .86rem; font-weight: 800; color: #2d5016; background: #fff; border: 1px solid #dbe8cc; text-decoration: none;
        transition: transform .28s cubic-bezier(.22,1,.36,1), border-color .28s cubic-bezier(.22,1,.36,1); }
    .rv-pop a:hover { transform: translateY(-2px); border-color: #a8cc7e; }
    .rv-pop a small { font-weight: 700; color: #7c8d6b; }
    .rv-how { margin-top: 1.6rem; display: grid; gap: .8rem; grid-template-columns: repeat(auto-fill, minmax(min(100%, 15rem), 1fr)); }
    .rv-how div { padding: 1.1rem; border-radius: 1.2rem; background: #fff; border: 1px solid #e3ecd8; }
    .rv-how h3 { font-family: var(--font-heading); font-size: 1.02rem; font-weight: 800; color: #14210c; }
    .rv-how p { margin-top: .35rem; font-size: .9rem; line-height: 1.6; color: #4b5563; }
    .rv-links { margin-top: 1.4rem; display: grid; gap: .6rem; grid-template-columns: repeat(auto-fill, minmax(min(100%, 16rem), 1fr)); }
    .rv-links a { display: flex; align-items: center; justify-content: space-between; gap: .6rem; padding: .85rem 1rem; border-radius: 1rem; background: #fff; border: 1px solid #e3ecd8;
        font-weight: 800; color: #14210c; text-decoration: none; transition: border-color .28s cubic-bezier(.22,1,.36,1), transform .28s cubic-bezier(.22,1,.36,1); }
    .rv-links a:hover { border-color: #a8cc7e; transform: translateY(-2px); }
    .rv-links svg { flex: none; width: 1rem; height: 1rem; color: #4a7c2a; }
    .rv-src { margin-top: 1rem; font-size: .82rem; line-height: 1.6; color: #6b7280; }
    .rv-src a { color: #3d6823; font-weight: 700; text-decoration: underline; text-underline-offset: 2px; }

    /* The crop list: a group heading, then a card per crop. */
    .cv-group + .cv-group { margin-top: 2.6rem; }
    .cv-group h3 { font-family: var(--font-heading); font-size: 1.2rem; font-weight: 800; color: #14210c; }
    .cv-crops { margin-top: .9rem; display: grid; gap: .7rem; grid-template-columns: repeat(auto-fill, minmax(min(100%, 15.5rem), 1fr)); }
    .cv-crop { display: flex; flex-direction: column; gap: .35rem; padding: 1rem 1.05rem; border-radius: 1.2rem; background: #fff; border: 1px solid #e3ecd8; text-decoration: none; color: inherit;
        box-shadow: 0 16px 34px -30px rgb(20 33 12 / .55); transition: transform .28s cubic-bezier(.22,1,.36,1), border-color .28s cubic-bezier(.22,1,.36,1); }
    a.cv-crop:hover { transform: translateY(-3px); border-color: #b9d69a; }
    .cv-crop-top { display: flex; align-items: baseline; justify-content: space-between; gap: .5rem; }
    .cv-crop b { font-family: var(--font-heading); font-size: 1.12rem; font-weight: 800; color: #14210c; }
    .cv-crop .n { flex: none; padding: .12rem .5rem; border-radius: 999px; font-size: .72rem; font-weight: 800; color: #3d6823; background: #eef6e4; }
    .cv-crop .l { font-size: .8rem; font-weight: 700; color: #6b7f5a; }
    .cv-crop .d { font-size: .84rem; line-height: 1.5; color: #4b5563; }
    .cv-crop.is-rice { background: linear-gradient(160deg, #fffaf0, #fff 60%); border-color: #f0e3b4; }
    .cv-crop .few { display: flex; flex-wrap: wrap; gap: .3rem; margin-top: .2rem; }
    .cv-crop .few a { font-size: .8rem; font-weight: 800; color: #2d5016; text-decoration: underline; text-underline-offset: 2px; }
    /* On a phone the crops sit two to a row: name, local name, count and days. */
    @media (max-width: 639.98px) {
        .cv-crops { grid-template-columns: repeat(2, minmax(0, 1fr)); gap: .55rem; }
        .cv-crop { padding: .8rem .85rem; border-radius: 1rem; }
        .cv-crop b { font-size: 1rem; }
        .cv-crop .d { font-size: .76rem; line-height: 1.4; }
        .cv-crop .d .h { display: none; }
        .cv-crop.is-rice .d { display: none; }
    }

    /* The search over every crop. */
    .cv-find { margin-top: 1.4rem; padding: 1.1rem; border-radius: 1.4rem; background: #fff; border: 1px solid #e1ecd3; box-shadow: 0 24px 50px -40px rgb(20 33 12 / .6); }
    .cv-res { margin-top: .9rem; display: grid; gap: .45rem; grid-template-columns: repeat(auto-fill, minmax(min(100%, 16rem), 1fr)); }
    .cv-res a { display: flex; flex-direction: column; padding: .6rem .8rem; border-radius: .9rem; background: #f7faf3; border: 1px solid #e6efdb; text-decoration: none;
        opacity: 0; translate: 0 6px; animation: cvIn .28s cubic-bezier(.22,1,.36,1) forwards; }
    .cv-res a b { font-weight: 800; color: #14210c; }
    .cv-res a span { font-size: .76rem; font-weight: 700; color: #6b7f5a; }
    .cv-res a:hover { border-color: #b9d69a; }
    .cv-res-none { margin-top: .9rem; font-size: .9rem; color: #6b7280; }
    @keyframes cvIn { to { opacity: 1; translate: 0 0; } }
    @media (prefers-reduced-motion: reduce) {
        .rv-pop a, .rv-links a, .cv-crop { transition: none; }
        .cv-res a { animation: none; opacity: 1; translate: none; }
    }
</style>
