{{-- The variety finders' look (2026-10-13), shared by the rice finder
     (variety-finder) and the crop finder (crop-variety-finder). Pushed
     once however many finders a page includes. --}}
@once
@push('head')
<style>
        .rv-tools { display: grid; gap: 1rem; margin-top: 1.6rem; padding: 1.1rem; border-radius: 1.4rem; background: #fff; border: 1px solid #e1ecd3; box-shadow: 0 24px 50px -40px rgb(20 33 12 / .6); }
        .rv-row { display: grid; gap: .5rem; }
        .rv-lab { font-size: .72rem; font-weight: 900; letter-spacing: .1em; text-transform: uppercase; color: #4a7c2a; }
        .rv-chips { display: flex; gap: .4rem; overflow-x: auto; scrollbar-width: none; margin: 0 -1.1rem; padding: .1rem 1.1rem .2rem; }
        .rv-chips::-webkit-scrollbar { display: none; }
        @media (min-width: 768px) { .rv-chips { flex-wrap: wrap; overflow: visible; margin: 0; padding: 0; } }
        .rv-chip { flex: none; display: inline-flex; align-items: center; gap: .35rem; padding: .45rem .85rem; border-radius: 999px; font-size: .84rem; font-weight: 800; color: #3f4b36;
            background: #f6faf1; border: 1px solid #dbe8cc; cursor: pointer; transition: background-color .28s cubic-bezier(.22,1,.36,1), color .28s cubic-bezier(.22,1,.36,1), border-color .28s cubic-bezier(.22,1,.36,1); }
        .rv-chip small { font-size: .72rem; font-weight: 800; color: #7c8d6b; }
        .rv-chip:hover { border-color: #a8cc7e; }
        .rv-chip.is-on { color: #fff; background: #2d5016; border-color: #2d5016; }
        .rv-chip.is-on small { color: #cfe3b8; }
        .rv-two { display: grid; gap: 1rem; }
        @media (min-width: 768px) { .rv-two { grid-template-columns: repeat(3, minmax(0, 1fr)); } }
        .rv-sel { width: 100%; padding: .62rem .8rem; border-radius: .9rem; border: 1px solid #d7e8c2; background: #fff; font-size: .9rem; font-weight: 700; color: #14210c; }
        .rv-sel:focus { outline: none; border-color: #86b556; box-shadow: 0 0 0 4px rgb(134 181 86 / .18); }
        .rv-count { margin-top: 1.4rem; display: flex; flex-wrap: wrap; align-items: center; justify-content: space-between; gap: .6rem; font-size: .9rem; color: #4b5563; }
        .rv-count b { color: #14210c; }
        .rv-reset { font-size: .85rem; font-weight: 800; color: #3d6823; text-decoration: underline; text-underline-offset: 3px; background: none; border: 0; cursor: pointer; }

        .rv-grid { margin-top: 1rem; display: grid; gap: .8rem; grid-template-columns: repeat(auto-fill, minmax(min(100%, 17rem), 1fr)); }
        .rv-card { position: relative; display: flex; flex-direction: column; gap: .55rem; padding: 1rem 1.05rem; border-radius: 1.2rem; background: #fff; border: 1px solid #e3ecd8;
            text-decoration: none; color: inherit; box-shadow: 0 16px 34px -30px rgb(20 33 12 / .55);
            transition: transform .28s cubic-bezier(.22,1,.36,1), border-color .28s cubic-bezier(.22,1,.36,1), box-shadow .28s cubic-bezier(.22,1,.36,1); }
        .rv-card:hover { transform: translateY(-3px); border-color: #b9d69a; box-shadow: 0 24px 44px -30px rgb(20 33 12 / .6); }
        .rv-card[hidden] { display: none; }
        .rv-card.is-hybrid { background: linear-gradient(160deg, #fffaf0, #fff 60%); border-color: #f0e3b4; }
        .rv-top { display: flex; align-items: flex-start; justify-content: space-between; gap: .5rem; }
        .rv-name { font-family: var(--font-heading); font-size: 1.12rem; font-weight: 800; color: #14210c; line-height: 1.2; }
        .rv-trade { display: block; margin-top: .15rem; font-size: .84rem; font-weight: 800; color: #8a6a00; }
        .rv-local { display: block; margin-top: .1rem; font-size: .78rem; font-weight: 700; color: #6b7f5a; }
        .rv-year { flex: none; padding: .15rem .5rem; border-radius: 999px; font-size: .72rem; font-weight: 800; color: #3d6823; background: #eef6e4; }
        .rv-tags { display: flex; flex-wrap: wrap; gap: .3rem; }
        .rv-tag { padding: .12rem .5rem; border-radius: 999px; font-size: .7rem; font-weight: 800; color: #4b5563; background: #f3f5f0; }
        .rv-tag.is-h { color: #6b4e00; background: #fdeeb2; }
        .rv-tag.is-g { color: #6b2c86; background: #f1e6f8; }
        .rv-nums { display: grid; grid-template-columns: repeat(3, minmax(0, 1fr)); gap: .4rem; }
        .rv-nums > span { display: block; padding: .45rem .5rem; border-radius: .8rem; background: #f7faf3; text-align: center; }
        .rv-nums b { display: block; font-family: var(--font-heading); font-size: 1.05rem; font-weight: 800; color: #1f3410; line-height: 1.1; }
        .rv-nums > span > span { display: block; margin-top: .15rem; font-size: .66rem; font-weight: 800; letter-spacing: .04em; text-transform: uppercase; color: #7c8d6b; }
        .rv-nodata { display: block; padding: .6rem .7rem; border-radius: .8rem; font-size: .78rem; font-weight: 700; color: #6b7f5a; background: #f7faf3; }
        .rv-bar { height: .35rem; border-radius: 999px; background: #eef3e8; overflow: hidden; }
        .rv-bar i { display: block; height: 100%; border-radius: inherit; background: linear-gradient(90deg, #86b556, #4a7c2a); }
        .rv-res { display: flex; flex-wrap: wrap; gap: .28rem; }
        .rv-res i { font-style: normal; display: inline-flex; align-items: center; gap: .25rem; padding: .12rem .42rem; border-radius: .5rem; font-size: .66rem; font-weight: 800; }
        .rv-res i.r { color: #1f5c1a; background: #e1f2d6; }
        .rv-res i.m { color: #6b5600; background: #fbf1c7; }
        .rv-res i.s { color: #9b1c1c; background: #fde4e4; }
        .rv-more { margin: 1.4rem auto 0; display: flex; padding: .7rem 1.4rem; border-radius: 999px; font-size: .92rem; font-weight: 800; color: #2d5016; background: #fff; border: 1px solid #cfe1bb; cursor: pointer;
            transition: background-color .28s cubic-bezier(.22,1,.36,1); }
        .rv-more:hover { background: #f4f9ee; }
        .rv-none { margin-top: 1.4rem; padding: 1.2rem; border-radius: 1.2rem; background: #fff; border: 1px dashed #cfe1bb; text-align: center; color: #4b5563; }

    @media (prefers-reduced-motion: reduce) { .rv-card, .rv-chip, .rv-more { transition: none; } }
</style>
@endpush
@endonce
