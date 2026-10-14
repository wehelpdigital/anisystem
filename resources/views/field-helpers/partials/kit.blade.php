{{-- The in-app Field helpers' kit (2026-10-14): the looks and the small
     machines every helper shares, made for a phone first.

     window.FH holds them:
       FH.ing(name, group, mode, products)
                                  one active ingredient as a record
                                  {name, group, mode, shop, products}
       FH.ingList(records)        the spray list: a row per ingredient, its
                                  group beside it, who sells it under it and
                                  the products sold here ("Sold as")
       FH.prodCards               product key => card (App\Support\
                                  ProductBook::card), filled by each page
                                  with only the products it can show
       FH.prodStrip(cards, h)     the products a label names, as cards
       FH.productSheet(card)      one product in a small sheet (tap a chip)
       FH.pick({...})             an option sheet (a promise of the choice)
       FH.openModal / closeModal  a full screen sheet (dialog.fhm)
       FH.catalogue({...})        a whole catalogue in a full screen sheet:
                                  search, crop and kind tags, cards, details
       FH.swap(el, fn)            redraw el with its height easing

     The option sheet wears the crop tag's looks (public.site.partials.
     crop-pick, which every helper page includes) under its own class, so
     the crop tag's script leaves it alone. --}}
@once
@push('head')
<style>
    /* ---- The catalogue strip: the way into the whole catalogue ---------- */
    .fh-cat-open { width: 100%; display: flex; align-items: center; gap: .85rem; margin: 0 0 1.1rem; padding: .7rem .75rem .7rem .8rem; border-radius: 1.2rem; text-align: left;
        color: #14210c; background: #fff; border: 1px solid #e1edd3; box-shadow: 0 16px 34px -30px rgb(20 33 12 / .6); cursor: pointer;
        transition: transform .28s cubic-bezier(.22,1,.36,1), border-color .28s cubic-bezier(.22,1,.36,1), box-shadow .28s cubic-bezier(.22,1,.36,1); }
    .fh-cat-open:hover { transform: translateY(-2px); border-color: #a8cc7e; box-shadow: 0 20px 38px -28px rgb(20 33 12 / .6); }
    .fh-cat-open:focus-visible { outline: 3px solid #f5c518; outline-offset: 2px; }
    .fh-faces { flex: none; display: flex; padding-left: .55rem; }
    .fh-faces img, .fh-faces span { width: 2.7rem; height: 2.7rem; margin-left: -.55rem; border-radius: .85rem; object-fit: cover; border: 2px solid #fff; background: #e4efd4;
        box-shadow: 0 6px 14px -8px rgb(20 33 12 / .55); }
    .fh-faces span { display: grid; place-items: center; font-size: 1.35rem; }
    .fh-faces > :nth-child(2) { transform: translateY(-3px) rotate(-3deg); }
    .fh-faces > :nth-child(3) { transform: rotate(3deg); }
    .fh-cat-t { flex: 1; min-width: 0; }
    .fh-cat-t b { display: block; font-family: var(--font-heading); font-weight: 800; font-size: 1rem; line-height: 1.25; }
    .fh-cat-t small { display: block; margin-top: .1rem; font-size: .8rem; line-height: 1.4; color: #6b7280; }
    .fh-cat-go { flex: none; display: inline-flex; align-items: center; gap: .3rem; padding: .5rem .8rem; border-radius: 999px; font-size: .8rem; font-weight: 800; color: #fff; background: #2d5016;
        transition: background-color .28s cubic-bezier(.22,1,.36,1); }
    .fh-cat-open:hover .fh-cat-go { background: #3d6823; }
    .fh-cat-go svg { width: .9rem; height: .9rem; }
    @media (max-width: 419.98px) { .fh-cat-go span { display: none; } .fh-cat-go { padding: .55rem; } }

    /* ---- The spray list: one row per active ingredient ------------------ */
    .fh-ings { display: grid; gap: .45rem; margin-top: .55rem; }
    @media (min-width: 640px) { .fh-ings { grid-template-columns: repeat(auto-fill, minmax(15.5rem, 1fr)); } }
    .fh-ing { min-width: 0; padding: .55rem .6rem .5rem .75rem; border-radius: .9rem; background: #fff; border: 1px solid #e1e9d7; }
    .fh-ing-top { display: flex; align-items: flex-start; justify-content: space-between; gap: .5rem; }
    .fh-ing-top b { min-width: 0; font-size: .92rem; font-weight: 800; line-height: 1.35; color: #14210c; overflow-wrap: anywhere; }
    .fh-grp { flex: none; margin-top: .05rem; padding: .14rem .5rem; border-radius: 999px; font-style: normal; font-size: .68rem; font-weight: 800; white-space: nowrap; color: #2d5016; background: #e4efd4; }
    .fh-ing-foot { display: flex; flex-wrap: wrap; align-items: center; gap: .3rem .6rem; margin-top: .35rem; }
    .fh-ing-foot small { flex: 1 1 8rem; min-width: 0; font-size: .74rem; line-height: 1.4; color: #6b7280; }
    /* The shops by their own logos (2026-10-15, the owner): a white tile, the
       logo centred, the shop named for screen readers and on hover. */
    .shop-logo { display: inline-flex; align-items: center; justify-content: center; flex: none; width: 2.25rem; height: 2.25rem; border-radius: .7rem;
        background: #fff; border: 1px solid #e5e7eb; box-shadow: 0 4px 10px -8px rgb(17 24 39 / .45); text-decoration: none;
        transition: transform .28s cubic-bezier(.22,1,.36,1), border-color .28s cubic-bezier(.22,1,.36,1), box-shadow .28s cubic-bezier(.22,1,.36,1); }
    .shop-logo img { width: 1.35rem; height: 1.35rem; object-fit: contain; display: block; }
    .shop-logo:hover { transform: translateY(-1px); box-shadow: 0 8px 16px -10px rgb(17 24 39 / .5); }
    .shop-logo.is-sp:hover { border-color: #f6a58d; }
    .shop-logo.is-lz:hover { border-color: #f59ac8; }
    .shop-logo:focus-visible { outline: 3px solid #f5c518; outline-offset: 2px; }
    @media (prefers-reduced-motion: reduce) { .shop-logo { transition: none; } }
    .fh-shop { display: inline-flex; gap: .35rem; }
    .fh-ing-foot small + .fh-shop { margin-left: auto; }
    .fh-prods { display: grid; gap: .35rem; margin-top: .55rem; padding-top: .55rem; border-top: 1px dashed #e1e9d7; }
    .fh-prods-l { font-size: .62rem; font-weight: 900; letter-spacing: .08em; text-transform: uppercase; color: #6b7f5a; }
    /* One product a row (2026-10-15, the owner): its name opens what it is,
       and each product has its own Shopee and Lazada search beside it. */
    .fh-prod { display: flex; align-items: center; gap: .45rem; min-width: 0; padding: .3rem .35rem .3rem .3rem; border-radius: .85rem; background: #f3f7ee; border: 1px solid #dfe8d4;
        transition: border-color .28s cubic-bezier(.22,1,.36,1), background-color .28s cubic-bezier(.22,1,.36,1); }
    .fh-prod:hover { border-color: #a8cc7e; background: #fff; }
    .fh-prod-n { flex: 1 1 auto; min-width: 0; display: grid; gap: .05rem; padding: .2rem .35rem; border-radius: .6rem; text-align: left; color: #14210c; cursor: pointer; }
    .fh-prod-n:focus-visible, .fh-pcard-n:focus-visible { outline: 3px solid #f5c518; outline-offset: 2px; }
    .fh-prod-n b { display: flex; align-items: center; flex-wrap: wrap; gap: .1rem .35rem; min-width: 0; font-size: .82rem; font-weight: 800; line-height: 1.25; }
    /* The whole name shows, on two lines if it must: a cut brand cannot be searched for in a shop. */
    .fh-prod-n b span { min-width: 0; overflow-wrap: anywhere; }
    .fh-prod-n small { display: flex; align-items: center; flex-wrap: wrap; gap: .1rem .35rem; min-width: 0; font-size: .68rem; line-height: 1.3; color: #6b7280; }
    .fh-prod-n small span { min-width: 0; overflow-wrap: anywhere; }
    .fh-prod em, .fh-pcard em { flex: none; font-style: normal; font-size: .58rem; font-weight: 900; letter-spacing: .04em; text-transform: uppercase; padding: .08rem .38rem; border-radius: 999px; color: #6b4e00; background: #fdeeb2; }
    .fh-pshop { flex: none; display: inline-flex; gap: .35rem; }
    .fh-rate { margin-top: .45rem; }
    .fh-pcards { display: grid; gap: .5rem; grid-template-columns: repeat(2, minmax(0, 1fr)); }
    @media (min-width: 640px) { .fh-pcards { grid-template-columns: repeat(auto-fill, minmax(12rem, 1fr)); } }
    .fh-pcard { display: flex; flex-direction: column; min-width: 0; border-radius: .95rem; overflow: hidden; color: #14210c;
        background: #f7faf3; border: 1px solid #e3ecd8; transition: border-color .28s cubic-bezier(.22,1,.36,1), transform .28s cubic-bezier(.22,1,.36,1); }
    .fh-pcard:hover { border-color: #a8cc7e; transform: translateY(-2px); }
    .fh-pcard-n { flex: 1 1 auto; display: flex; flex-direction: column; align-items: flex-start; gap: .15rem; min-width: 0; padding: .6rem .7rem .55rem; text-align: left; color: inherit; cursor: pointer; }
    .fh-pcard .fh-pshop { padding: 0 .7rem .65rem; }
    .fh-pcard b { font-size: .9rem; font-weight: 800; line-height: 1.25; overflow-wrap: anywhere; }
    .fh-pcard small { font-size: .72rem; color: #6b7280; }
    .fh-pcard span { font-size: .74rem; line-height: 1.35; color: #374151; }
    .fh-pcard i { margin-top: .2rem; font-style: normal; font-size: .64rem; font-weight: 800; padding: .1rem .45rem; border-radius: 999px; color: #2d5016; background: #e4efd4; }
    .fh-psheet { padding: 0 1.15rem .4rem; overflow-y: auto; }
    .fh-psheet .fh-pkind { font-size: .86rem; color: #4b5563; }
    .fh-psheet .fh-pkind span { display: inline-block; margin-right: .3rem; padding: .1rem .5rem; border-radius: 999px; font-size: .66rem; font-weight: 900; letter-spacing: .05em; text-transform: uppercase; color: #2d5016; background: #e4efd4; }
    .fh-psheet .fhd-facts { margin-top: .8rem; }
    .fh-psheet .fhd-facts > div { background: #f7faf3; }
    .fh-psheet .fh-pnote { margin-top: .8rem; padding: .7rem .8rem; border-radius: .9rem; font-size: .92rem; line-height: 1.55; color: #24400f; background: #f3f8ec; border: 1px solid #d7e8c2; }
    .fh-psheet .fh-fine { margin-top: .7rem; }
    .fh-psheet-shop { margin-top: .9rem; }
    .fh-psheet-shop > b { display: block; margin-bottom: .4rem; font-size: .66rem; font-weight: 900; letter-spacing: .08em; text-transform: uppercase; color: #3d6823; }
    /* In the sheet there is room for the words beside the logo. */
    .fh-psheet-shop .fh-pshop { display: grid; grid-template-columns: 1fr 1fr; gap: .45rem; }
    .fh-psheet-shop .shop-logo { width: auto; height: 2.85rem; gap: .5rem; padding: 0 .8rem; border-radius: .9rem; font-size: .86rem; font-weight: 800; color: #1f2937; }
    .fh-psheet-shop .shop-logo img { width: 1.5rem; height: 1.5rem; }
    .fh-spray-h { display: inline-flex; align-items: center; gap: .35rem; font-size: .7rem; font-weight: 800; letter-spacing: .08em; text-transform: uppercase; color: #3d6823; }
    .fh-spray-h svg { width: .95rem; height: .95rem; }
    .fh-no { display: flex; gap: .5rem; align-items: flex-start; padding: .65rem .75rem; border-radius: .85rem; font-size: .88rem; line-height: 1.5; color: #6b4a00; background: #fff8e6; border: 1px solid #f5d98a; }
    .fh-no b { display: block; font-size: .7rem; font-weight: 800; letter-spacing: .08em; text-transform: uppercase; color: #8a5a00; }
    .fh-no svg { flex: none; width: 1.05rem; height: 1.05rem; margin-top: .12rem; color: #c79e00; }
    .fh-fine { font-size: .76rem; line-height: 1.5; color: #6b7280; }

    /* ---- A finder's answer: one card per pest or disease ----------------- */
    .fh-res { display: grid; gap: .75rem; }
    .fh-rc { --g: 98; min-width: 0; border-radius: 1.1rem; background: #f6f8f3; border: 1px solid #e5ebdf; overflow: hidden; transition: border-color .28s cubic-bezier(.22,1,.36,1); }
    .fh-rc:hover { border-color: hsl(var(--g) 40% 72%); }
    .fh-rc-head { width: 100%; display: grid; grid-template-columns: 4.4rem minmax(0, 1fr) auto; gap: .75rem; align-items: center; padding: .6rem .7rem .55rem .6rem;
        text-align: left; color: inherit; background: none; border: 0; cursor: pointer; }
    .fh-rc-head:focus-visible { outline: 3px solid #f5c518; outline-offset: -3px; border-radius: 1.1rem; }
    .fh-rc-head > img, .fh-rc-head > .none { width: 4.4rem; height: 4.4rem; border-radius: .85rem; object-fit: cover; background: hsl(var(--g) 35% 88%); }
    .fh-rc-head > .none { display: grid; place-items: center; color: hsl(var(--g) 40% 45%); }
    .fh-rc-head > .none svg { width: 1.6rem; height: 1.6rem; }
    .fh-rc-t { min-width: 0; }
    .fh-rc-t b { display: block; font-family: var(--font-heading); font-size: 1.02rem; font-weight: 800; line-height: 1.25; color: #14210c; }
    .fh-rc-t small { display: block; margin-top: .25rem; font-size: .78rem; line-height: 1.45; color: #4b5563; }
    .fh-rc-t small span { font-style: italic; }
    .fh-rc-t em { display: inline-block; margin-right: .35rem; padding: .08rem .45rem; border-radius: 999px; font-style: normal; font-size: .62rem; font-weight: 800; letter-spacing: .05em;
        text-transform: uppercase; color: hsl(var(--g) 55% 22%); background: hsl(var(--g) 55% 91%); vertical-align: 1px; }
    .fh-rc-go { width: 2.2rem; height: 2.2rem; border-radius: 999px; display: grid; place-items: center; color: hsl(var(--g) 50% 26%); background: hsl(var(--g) 50% 90%);
        transition: transform .28s cubic-bezier(.22,1,.36,1); }
    .fh-rc-go svg { width: 1rem; height: 1rem; }
    .fh-rc-head:hover .fh-rc-go { transform: translateX(3px); }
    .fh-rc-sign { display: flex; gap: .45rem; align-items: flex-start; padding: 0 .85rem .7rem; font-size: .86rem; line-height: 1.5; color: #374151; }
    .fh-rc-sign svg { flex: none; width: 1rem; height: 1rem; margin-top: .15rem; color: #6b9f3d; }
    .fh-rc-spray { display: grid; padding: .65rem .7rem .75rem; border-top: 1px dashed #dfe8d4; }
    .fh-rc-spray .fh-ings { margin-top: .45rem; }
    html.dark .fh-rc { background: #1c2616; border-color: #2b3a1c; }
    html.dark .fh-rc .fh-ing { background: #151b12; }
    html.dark .fh-rc-t b { color: #e8efe1; }
    html.dark .fh-rc-t small { color: #b9caa8; }
    html.dark .fh-rc-sign { color: #d5e3c5; }
    html.dark .fh-rc-spray { border-color: #2b3a1c; }
    html.dark .fh-rc-go { background: hsl(var(--g) 30% 22%); color: hsl(var(--g) 50% 80%); }
    @media (prefers-reduced-motion: reduce) { .fh-rc, .fh-rc-go { transition: none; } }

    /* ---- A full screen sheet: the catalogues and the variety details ---- */
    .fhm { padding: 0; border: 0; margin: 0; inset: 0; width: 100%; height: 100%; max-width: none; max-height: none; overflow: hidden; color: #14210c; background: #f4f6f1; }
    .fhm[open] { display: flex; flex-direction: column; animation: fhmIn .34s cubic-bezier(.22,1,.36,1); }
    .fhm::backdrop { background: rgb(10 18 6 / .45); }
    .fhm.is-closing { animation: fhmOut .24s cubic-bezier(.22,1,.36,1) forwards; }
    @keyframes fhmIn { from { opacity: 0; transform: translateY(28px); } }
    @keyframes fhmOut { to { opacity: 0; transform: translateY(28px); } }
    .fhm-bar { flex: none; position: relative; z-index: 3; display: flex; align-items: center; gap: .55rem; padding: .6rem .75rem; padding-top: max(.6rem, env(safe-area-inset-top));
        background: rgb(255 255 255 / .94); backdrop-filter: blur(8px); -webkit-backdrop-filter: blur(8px); border-bottom: 1px solid #e3ead9; }
    .fhm-title { flex: 1; min-width: 0; }
    .fhm-title b { display: block; font-family: var(--font-heading); font-weight: 800; font-size: 1.02rem; line-height: 1.25; white-space: nowrap; overflow: hidden; text-overflow: ellipsis; }
    .fhm-title small { display: block; font-size: .74rem; color: #6b7280; white-space: nowrap; overflow: hidden; text-overflow: ellipsis; }
    .fhm-back, .fhm-x { flex: none; width: 2.5rem; height: 2.5rem; border-radius: 999px; display: grid; place-items: center; color: #374151; background: #f1f4ed; border: 1px solid #e3ead9; cursor: pointer;
        transition: background-color .28s cubic-bezier(.22,1,.36,1), opacity .28s cubic-bezier(.22,1,.36,1), transform .28s cubic-bezier(.22,1,.36,1); }
    .fhm-back:hover, .fhm-x:hover { background: #e5ecdc; }
    .fhm-back svg, .fhm-x svg { width: 1.15rem; height: 1.15rem; }
    .fhm-back[hidden] { display: grid; visibility: hidden; opacity: 0; transform: translateX(-6px) scale(.8); pointer-events: none; width: 0; margin-right: -.55rem; border-width: 0; }
    .fhm.is-instant .fhm-pane { transition: none !important; }
    .fhm-panes { flex: 1; position: relative; overflow: hidden; }
    .fhm:focus { outline: none; }
    .fhm-pane { position: absolute; inset: 0; overflow-y: auto; overscroll-behavior: contain; -webkit-overflow-scrolling: touch;
        transition: transform .36s cubic-bezier(.22,1,.36,1), opacity .36s cubic-bezier(.22,1,.36,1), visibility 0s linear 0s; }
    .fhm-in { max-width: 74rem; margin: 0 auto; padding: 1rem 1rem calc(2.5rem + env(safe-area-inset-bottom)); }
    .fhm-detail { transform: translateX(100%); visibility: hidden; transition: transform .36s cubic-bezier(.22,1,.36,1), visibility 0s linear .36s; }
    .fhm.is-detail .fhm-list { transform: translateX(-18%); opacity: 0; visibility: hidden; transition: transform .36s cubic-bezier(.22,1,.36,1), opacity .36s cubic-bezier(.22,1,.36,1), visibility 0s linear .36s; }
    .fhm.is-detail .fhm-detail { transform: none; visibility: visible; transition: transform .36s cubic-bezier(.22,1,.36,1), visibility 0s linear 0s; }
    .fhm-detail .fhm-in { max-width: 62rem; }
    html.fh-lock { overflow: hidden; }

    /* ---- The catalogue's list ------------------------------------------- */
    .fhc-tools { display: grid; gap: .6rem; padding: .8rem; border-radius: 1.25rem; background: #fff; border: 1px solid #e3ecd8; box-shadow: 0 18px 40px -36px rgb(20 33 12 / .55); }
    @media (min-width: 900px) { .fhc-tools { grid-template-columns: minmax(0, 1fr) minmax(0, 30rem); align-items: center; padding: .9rem 1rem; } .fhc-tools.is-one { grid-template-columns: minmax(0, 1fr); } }
    .fhc-search { position: relative; display: block; min-width: 0; }
    .fhc-search svg { position: absolute; left: .85rem; top: 50%; translate: 0 -50%; width: 1.05rem; height: 1.05rem; color: #6b9f3d; pointer-events: none; }
    .fhc-search input { width: 100%; min-height: 2.9rem; padding: .65rem .9rem .65rem 2.5rem; border-radius: 999px; border: 1px solid #d7e8c2; background: #fbfcf9; font-size: 1rem; color: #14210c;
        transition: border-color .28s cubic-bezier(.22,1,.36,1), box-shadow .28s cubic-bezier(.22,1,.36,1), background-color .28s cubic-bezier(.22,1,.36,1); }
    .fhc-search input:focus { outline: none; border-color: #86b556; background: #fff; box-shadow: 0 0 0 4px rgb(134 181 86 / .18); }
    .fhc-tags { display: grid; grid-template-columns: repeat(2, minmax(0, 1fr)); gap: .5rem; }
    /* The tags here sit two to a row: the crop tag's look, tighter. */
    .fh-tight .cp-tag { margin-top: 0; min-height: 3.1rem; padding: .35rem .45rem .35rem .4rem; gap: .5rem; border-radius: .95rem; }
    .fh-tight .cp-ico { width: 2.15rem; height: 2.15rem; border-radius: .7rem; font-size: 1.1rem; }
    .fh-tight .cp-ico svg { width: 1.2rem; height: 1.2rem; }
    .fh-tight .cp-t b { font-size: .9rem; white-space: nowrap; overflow: hidden; text-overflow: ellipsis; }
    .fh-tight .cp-t small { font-size: .72rem; white-space: nowrap; overflow: hidden; text-overflow: ellipsis; }
    .fh-tight .cp-chev { font-size: 0; gap: 0; padding: .35rem; }
    .fh-tight .cp-chev svg { width: .8rem; height: .8rem; }
    .fh-tight .cp-tag.is-set { background: #eef6e4; border-color: #a8cc7e; }
    .fhc-count { display: flex; align-items: center; justify-content: space-between; gap: .6rem; margin: .95rem .15rem .65rem; font-size: .88rem; color: #4b5563; }
    .fhc-count b { color: #14210c; }
    .fhc-clear { font-size: .84rem; font-weight: 800; color: #3d6823; text-decoration: underline; text-underline-offset: 3px; background: none; border: 0; cursor: pointer; }
    .fhc-grid { display: grid; gap: .65rem; grid-template-columns: repeat(2, minmax(0, 1fr)); }
    @media (min-width: 640px) { .fhc-grid { gap: .9rem; grid-template-columns: repeat(auto-fill, minmax(12.5rem, 1fr)); } }
    .fhc-card { --g: 98; display: flex; flex-direction: column; min-width: 0; overflow: hidden; border-radius: 1.05rem; text-align: left; color: inherit; background: #fff; border: 1px solid #e5ebdf; cursor: pointer;
        transition: transform .28s cubic-bezier(.22,1,.36,1), box-shadow .28s cubic-bezier(.22,1,.36,1), border-color .28s cubic-bezier(.22,1,.36,1); }
    .fhc-card:hover { transform: translateY(-3px); border-color: hsl(var(--g) 40% 75%); box-shadow: 0 18px 36px -26px rgb(20 33 12 / .55); }
    .fhc-card:focus-visible { outline: 3px solid #f5c518; outline-offset: 2px; }
    .fhc-card[hidden] { display: none; }
    .fhc-ph { position: relative; display: block; aspect-ratio: 4 / 3; overflow: hidden; background: radial-gradient(circle at 30% 20%, hsl(var(--g) 45% 92%), hsl(var(--g) 35% 84%)); }
    .fhc-ph img { width: 100%; height: 100%; object-fit: cover; transition: transform .6s cubic-bezier(.22,1,.36,1); }
    .fhc-card:hover .fhc-ph img { transform: scale(1.05); }
    .fhc-ph .none { position: absolute; inset: 0; display: grid; place-items: center; color: hsl(var(--g) 40% 55%); }
    .fhc-ph .none svg { width: 2.4rem; height: 2.4rem; }
    .fhc-ph i { position: absolute; left: .45rem; top: .45rem; padding: .18rem .5rem; border-radius: 999px; font-style: normal; font-size: .6rem; font-weight: 800; letter-spacing: .05em;
        text-transform: uppercase; color: hsl(var(--g) 60% 20%); background: hsl(var(--g) 60% 93% / .94); }
    .fhc-in { display: flex; flex-direction: column; gap: .12rem; padding: .6rem .7rem .75rem; min-width: 0; }
    .fhc-in b { font-family: var(--font-heading); font-size: .95rem; line-height: 1.25; color: #14210c; }
    .fhc-in em { font-size: .74rem; line-height: 1.35; color: #4b5563; display: -webkit-box; -webkit-line-clamp: 2; -webkit-box-orient: vertical; overflow: hidden; }
    .fhc-in small { font-size: .7rem; font-style: italic; color: #6b7280; white-space: nowrap; overflow: hidden; text-overflow: ellipsis; }
    @media (min-width: 640px) { .fhc-in b { font-size: 1rem; } .fhc-in em { font-size: .8rem; } .fhc-ph i { font-size: .64rem; } }
    .fhc-card.is-in { animation: fhIn .34s cubic-bezier(.22,1,.36,1) both; }
    @keyframes fhIn { from { opacity: 0; transform: translateY(10px); } }
    .fhc-none { padding: 2rem 1rem; text-align: center; color: #6b7280; border: 1px dashed #d7e8c2; border-radius: 1.1rem; animation: fhIn .34s cubic-bezier(.22,1,.36,1) both; }

    /* ---- One entry, in the catalogue's second pane ---------------------- */
    .fhd { --g: 98; display: grid; gap: 1rem; }
    @media (min-width: 900px) { .fhd { grid-template-columns: minmax(0, 24rem) minmax(0, 1fr); gap: 1.6rem; align-items: start; } .fhd-ph { position: sticky; top: 0; } }
    .fhd-ph { margin: 0; }
    .fhd-ph img, .fhd-ph .none { display: block; width: 100%; aspect-ratio: 4 / 3; object-fit: cover; border-radius: 1.25rem; background: radial-gradient(circle at 30% 20%, hsl(var(--g) 45% 92%), hsl(var(--g) 35% 84%)); }
    .fhd-ph .none { display: grid; place-items: center; color: hsl(var(--g) 40% 55%); }
    .fhd-ph .none svg { width: 3.4rem; height: 3.4rem; }
    .fhd-ph figcaption { margin-top: .4rem; font-size: .7rem; line-height: 1.4; color: #6b7280; }
    .fhd-body { display: grid; gap: .8rem; min-width: 0; }
    .fhd-head h2 { margin-top: .45rem; font-family: var(--font-heading); font-weight: 800; font-size: 1.55rem; line-height: 1.2; color: #14210c; }
    .fhd-pill { display: inline-flex; align-items: center; gap: .35rem; padding: .22rem .6rem; border-radius: 999px; font-size: .68rem; font-weight: 800; letter-spacing: .05em; text-transform: uppercase;
        color: hsl(var(--g) 60% 20%); background: hsl(var(--g) 60% 91%); }
    .fhd-pill svg { width: .9rem; height: .9rem; }
    .fhd-sci { margin-top: .2rem; font-style: italic; font-size: .92rem; color: #4b5563; }
    .fhd-local { margin-top: .45rem; font-size: .88rem; line-height: 1.55; color: #374151; }
    .fhd-local b { font-weight: 800; color: #14210c; }
    .fhd-sec { padding: .85rem .95rem .95rem; border-radius: 1.1rem; background: #fff; border: 1px solid #e5ebdf; min-width: 0; }
    .fhd-sec > h3 { display: flex; align-items: center; gap: .4rem; margin-bottom: .5rem; font-size: .7rem; font-weight: 800; letter-spacing: .08em; text-transform: uppercase; color: #3d6823; }
    .fhd-sec > h3 svg { width: 1rem; height: 1rem; }
    .fhd-sec > p:not(.fh-fine) { font-size: .95rem; line-height: 1.6; color: #1f2937; }
    .fhd-sec > .fh-fine { margin-top: .55rem; }
    .fh-fine + .fh-fine { margin-top: .35rem; }
    .fhd-sec .fh-ings { margin-top: 0; }
    .fhd-chips { display: flex; flex-wrap: wrap; gap: .35rem; }
    .fhd-chips li { display: inline-flex; align-items: center; gap: .35rem; padding: .32rem .65rem; border-radius: 999px; font-size: .84rem; font-weight: 700; color: #2d3a24; background: #f3f7ee; border: 1px solid #e1ebd5; }
    .fhd-chips li.is-on { color: #fff; background: #2d5016; border-color: #2d5016; }
    .fhd-chips li small { font-size: .74rem; font-weight: 600; opacity: .7; }
    .fhd-chips li.is-in { animation: fhIn .34s cubic-bezier(.22,1,.36,1) both; }
    .fhd-chips li.fhd-chips-more { padding: 0; border: 0; background: none; }
    .fhd-chips-more button { padding: .32rem .75rem; border-radius: 999px; font-size: .82rem; font-weight: 800; color: #2d5016; background: #e4efd4; cursor: pointer; transition: background-color .28s cubic-bezier(.22,1,.36,1); }
    .fhd-chips-more button:hover { background: #d7e8c2; }
    html.dark .fhd-chips li.fhd-chips-more { background: none; }
    html.dark .fhd-chips-more button { background: #2b3a1c; color: #cfe3b8; }
    .fhd-facts { display: grid; grid-template-columns: repeat(2, minmax(0, 1fr)); gap: .5rem; }
    .fhd-facts > div { padding: .6rem .75rem; border-radius: .9rem; background: #fff; border: 1px solid #e5ebdf; min-width: 0; }
    .fhd-facts small { display: block; font-size: .66rem; font-weight: 800; letter-spacing: .07em; text-transform: uppercase; color: #6b7f5a; }
    .fhd-facts b { display: block; margin-top: .1rem; font-size: .95rem; color: #14210c; line-height: 1.3; }
    .fhd-src { font-size: .76rem; line-height: 1.55; color: #6b7280; }
    .fhd-src a { color: #3d6823; font-weight: 700; text-decoration: underline; text-underline-offset: 3px; }
    .fhd-cta { display: flex; align-items: center; justify-content: center; gap: .5rem; width: 100%; min-height: 3rem; padding: .7rem 1rem; border-radius: .95rem; font-family: var(--font-heading);
        font-size: 1rem; font-weight: 800; color: #3b2f00; background: #f5c518; box-shadow: 0 12px 24px -18px rgb(59 47 0 / .8); cursor: pointer; transition: background-color .28s cubic-bezier(.22,1,.36,1); }
    .fhd-cta:hover { background: #ffd23f; }
    .fhd-cta svg { width: 1.1rem; height: 1.1rem; }
    .fhm.is-detail .fhd-ph, .fhm.is-detail .fhd-body > * { animation: fhIn .4s cubic-bezier(.22,1,.36,1) both; }
    .fhd-body > :nth-child(2) { animation-delay: .04s !important; } .fhd-body > :nth-child(3) { animation-delay: .08s !important; }
    .fhd-body > :nth-child(4) { animation-delay: .12s !important; } .fhd-body > :nth-child(n+5) { animation-delay: .16s !important; }

    /* ---- The option sheet (a filter's choices) -------------------------- */
    .fh-sheet { padding: 0; border: 0; background: transparent; max-width: none; max-height: none; width: 100%; height: 100%; margin: 0; inset: 0; overflow: hidden; }
    .fh-sheet::backdrop { background: rgb(10 18 6 / .5); animation: cpFade .28s cubic-bezier(.22,1,.36,1); }
    .fh-sheet.is-closing::backdrop { animation: cpFade .22s cubic-bezier(.22,1,.36,1) reverse forwards; }
    .fh-sheet.is-closing .cp-box { animation: cpUp .24s cubic-bezier(.22,1,.36,1) reverse forwards; }
    @media (min-width: 640px) { .fh-sheet.is-closing .cp-box { animation-name: cpPop; } }
    .fh-sheet .cp-row[disabled] { opacity: .4; cursor: not-allowed; }
    .fh-sheet-note { margin: -.2rem 1.15rem .55rem; font-size: .8rem; line-height: 1.45; color: #6b7280; }
    .fh-sheet-foot { flex: none; display: flex; gap: .5rem; padding: .7rem 1rem calc(.8rem + env(safe-area-inset-bottom)); border-top: 1px solid #eef2e8; }
    .fh-sheet-foot button { flex: 1; min-height: 2.9rem; border-radius: .9rem; font-weight: 800; cursor: pointer; transition: background-color .28s cubic-bezier(.22,1,.36,1); }
    .fh-sheet-clear { color: #2d5016; background: #f1f6ea; }
    .fh-sheet-done { color: #fff; background: #2d5016; }
    .fh-sheet-done:hover { background: #3d6823; }

    /* ---- A taglike filter button: the label, what is picked, a chevron -- */
    .fh-ftags { display: grid; grid-template-columns: repeat(2, minmax(0, 1fr)); gap: .5rem; }
    @media (min-width: 768px) { .fh-ftags { grid-template-columns: repeat(auto-fill, minmax(12rem, 1fr)); } }
    .fh-ftag { display: flex; align-items: center; gap: .45rem; width: 100%; min-width: 0; min-height: 3.1rem; padding: .45rem .5rem .45rem .75rem; border-radius: .95rem; text-align: left;
        color: #14210c; background: #f6f8f3; border: 1px solid #dfe8d4; cursor: pointer;
        transition: border-color .28s cubic-bezier(.22,1,.36,1), background-color .28s cubic-bezier(.22,1,.36,1), box-shadow .28s cubic-bezier(.22,1,.36,1); }
    .fh-ftag:hover { border-color: #a8cc7e; background: #fff; box-shadow: 0 10px 24px -20px rgb(20 33 12 / .6); }
    .fh-ftag:focus-visible { outline: 3px solid #f5c518; outline-offset: 2px; }
    .fh-ftag > span:first-child { flex: 1; min-width: 0; }
    .fh-ftag small { display: block; font-size: .62rem; font-weight: 900; letter-spacing: .06em; text-transform: uppercase; color: #4a7c2a; white-space: nowrap; overflow: hidden; text-overflow: ellipsis; }
    /* Two to a row on a phone: an odd last one takes the whole row. */
    @media (max-width: 767.98px) { .fh-ftags > :last-child:nth-child(odd) { grid-column: 1 / -1; } }
    .fh-ftag b { display: block; margin-top: .05rem; font-size: .88rem; font-weight: 800; white-space: nowrap; overflow: hidden; text-overflow: ellipsis; }
    .fh-ftag i { flex: none; width: 1.6rem; height: 1.6rem; border-radius: 999px; display: grid; place-items: center; color: #2d5016; background: #e4efd4;
        transition: transform .28s cubic-bezier(.22,1,.36,1), background-color .28s cubic-bezier(.22,1,.36,1); }
    .fh-ftag i svg { width: .8rem; height: .8rem; }
    .fh-ftag.is-set { background: #eef6e4; border-color: #a8cc7e; }
    .fh-ftag.is-set b { color: #2d5016; }
    .fh-ftag.is-set i { color: #3b2f00; background: #f5c518; }
    .fh-ftag.is-bump i, .cp-tag.is-bump .cp-ico { animation: cpBump .42s cubic-bezier(.22,1,.36,1); }

    /* The answer redraws: its parts come up, its height eases (FH.swap). */
    .wc-card.is-swap .fh-res > * { animation: wcIn .34s cubic-bezier(.22,1,.36,1) both; }
    .wc-card.is-swap .fh-res > *:nth-child(2) { animation-delay: .04s; } .wc-card.is-swap .fh-res > *:nth-child(3) { animation-delay: .08s; }
    .wc-card.is-swap .fh-res > *:nth-child(4) { animation-delay: .12s; } .wc-card.is-swap .fh-res > *:nth-child(n+5) { animation-delay: .16s; }

    @media (prefers-reduced-motion: reduce) {
        .fh-cat-open, .fh-cat-go, .fh-shop a, .fhm-back, .fhm-x, .fhm-pane, .fhm-detail, .fhm.is-detail .fhm-list, .fhm.is-detail .fhm-detail, .fhc-search input, .fhc-card, .fhc-ph img,
        .fh-ftag, .fh-ftag i, .fh-sheet-foot button, .fhd-cta, .fh-prod, .fh-pcard { transition: none; }
        .fhm[open], .fhm.is-closing, .fhc-card.is-in, .fhc-none, .fhm.is-detail .fhd-ph, .fhm.is-detail .fhd-body > *, .fh-sheet::backdrop, .fh-sheet.is-closing::backdrop,
        .fh-sheet.is-closing .cp-box, .fh-ftag.is-bump i, .wc-card.is-swap .fh-res > * { animation: none; }
    }

    /* ---- Night ---------------------------------------------------------- */
    html.dark .fh-cat-open, html.dark .fhc-tools, html.dark .fhc-card, html.dark .fhd-sec, html.dark .fhd-facts > div { background: #151b12; border-color: #2b3a1c; box-shadow: none; color: #e8efe1; }
    html.dark .fh-faces img, html.dark .fh-faces span { border-color: #151b12; }
    html.dark .fh-cat-t small, html.dark .fhm-title small, html.dark .fhc-count, html.dark .fhd-src, html.dark .fhd-ph figcaption, html.dark .fh-fine, html.dark .fh-sheet-note, html.dark .fhc-none { color: #b9caa8; }
    html.dark .fh-cat-go { background: #4a7c2a; }
    html.dark .fh-ing { background: #1c2616; border-color: #2b3a1c; }
    html.dark .fh-ing-top b, html.dark .fhc-in b, html.dark .fhd-head h2, html.dark .fhd-local b, html.dark .fhd-facts b, html.dark .fhc-count b { color: #e8efe1; }
    html.dark .fh-ing-foot small, html.dark .fhc-in em, html.dark .fhc-in small, html.dark .fhd-sci, html.dark .fhd-local, html.dark .fhd-sec > p:not(.fh-fine) { color: #c9d8b8; }
    html.dark .fh-grp { background: #2b3a1c; color: #cfe3b8; }
    html.dark .fh-spray-h, html.dark .fhd-sec > h3, html.dark .fhc-clear, html.dark .fhd-src a { color: #a8cc7e; }
    html.dark .fh-no { background: #241d10; border-color: #5c4a24; color: #f3d9a4; }
    html.dark .fh-no b { color: #f5c518; }
    html.dark .fhm { background: #0d110a; color: #e8efe1; }
    html.dark .fhm-bar { background: rgb(13 17 10 / .92); border-color: #2b3a1c; }
    html.dark .fhm-back, html.dark .fhm-x { background: #151b12; border-color: #2b3a1c; color: #d5e3c5; }
    html.dark .fhc-search input { background: #1c2616; border-color: #2b3a1c; color: #e8efe1; }
    html.dark .fhc-card:hover { border-color: hsl(var(--g) 30% 35%); }
    html.dark .fhd-chips li { background: #1c2616; border-color: #2b3a1c; color: #d5e3c5; }
    html.dark .fhd-chips li.is-on { background: #4a7c2a; border-color: #4a7c2a; color: #fff; }
    html.dark .fhd-facts small { color: #9fb58a; }
    html.dark .fh-ftag, html.dark .fh-tight .cp-tag.is-set { background: #1c2616; border-color: #2b3a1c; color: #e8efe1; }
    html.dark .fh-ftag:hover { background: #22301a; }
    html.dark .fh-ftag small { color: #a8cc7e; }
    html.dark .fh-ftag i { background: #2b3a1c; color: #d5e3c5; }
    html.dark .fh-ftag.is-set { background: #22301a; border-color: #4a7c2a; }
    html.dark .fh-ftag.is-set b { color: #cfe3b8; }
    html.dark .fh-ftag.is-set i { background: #f5c518; color: #3b2f00; }
    html.dark .fh-sheet-foot { border-color: #2b3a1c; }
    html.dark .fh-sheet-clear { background: #1c2616; color: #d5e3c5; }
    html.dark .fh-sheet-done { background: #4a7c2a; }
    html.dark .fh-prods { border-color: #2b3a1c; }
    html.dark .fh-prods-l { color: #9fb58a; }
    html.dark .fh-prod, html.dark .fh-pcard { background: #1c2616; border-color: #2b3a1c; color: #e8efe1; }
    html.dark .fh-prod:hover { background: #222e1b; }
    html.dark .fh-prod-n { color: #e8efe1; }
    html.dark .fh-prod-n small { color: #b9caa8; }
    html.dark .fh-psheet-shop > b { color: #a5c97e; }
    html.dark .shop-logo { border-color: transparent; box-shadow: 0 0 0 1px rgb(255 255 255 / .08); }
    html.dark .fh-rc .fh-prod { background: #22301a; }
    html.dark .fh-prod small, html.dark .fh-pcard small { color: #b9caa8; }
    html.dark .fh-pcard span { color: #d5e3c5; }
    html.dark .fh-pcard i, html.dark .fh-psheet .fh-pkind span { background: #2b3a1c; color: #cfe3b8; }
    html.dark .fh-psheet .fh-pkind { color: #c9d8b8; }
    html.dark .fh-psheet .fhd-facts > div { background: #1c2616; }
    html.dark .fh-psheet .fh-pnote { background: #17220f; border-color: #2b3a1c; color: #d5e3c5; }
    /* Pictures still on their way: a dark face, not a pale one. */
    html.dark .fhc-ph, html.dark .fhd-ph img, html.dark .fhd-ph .none, html.dark .fh-rc-head > img, html.dark .fh-rc-head > .none, html.dark .fh-faces img {
        background: radial-gradient(circle at 30% 20%, hsl(var(--g, 98) 22% 22%), hsl(var(--g, 98) 18% 14%)); }
</style>
@endpush
@push('scripts')
<script>
(() => {
    /* The kit every in-app field helper shares (see the note at the top of
       field-helpers/partials/kit). */
    const FH = (window.FH = window.FH || {});
    FH.products = FH.products || {};
    const esc = (FH.esc = (s) => String(s ?? '').replace(/[&<>"']/g, (c) => ({ '&': '&amp;', '<': '&lt;', '>': '&gt;', '"': '&quot;', "'": '&#39;' }[c])));
    const reduce = (FH.reduce = () => matchMedia('(prefers-reduced-motion: reduce)').matches);
    const svg = (d, w) => '<svg fill="none" stroke="currentColor" stroke-width="' + (w || 2) + '" stroke-linecap="round" stroke-linejoin="round" viewBox="0 0 24 24" aria-hidden="true">' + d + '</svg>';
    FH.icon = {
        arrow: svg('<path d="M9 5l7 7-7 7"/>', 2.6), back: svg('<path d="M15 19l-7-7 7-7"/>', 2.6), x: svg('<path d="M6 6l12 12M18 6L6 18"/>', 2.4),
        chev: svg('<path d="M19 9l-7 7-7-7"/>', 3), check: svg('<path d="M5 13l4 4L19 7"/>', 3.2),
        leaf: svg('<path d="M12 21v-9m0 0C12 7 8 5 4 5c0 4 3 7 8 7zm0 0c0-4 3-7 8-7 0 4-4 7-8 7z"/>', 1.8),
        drop: svg('<path d="M12 3.5s6 6.4 6 10.6a6 6 0 01-12 0c0-4.2 6-10.6 6-10.6z"/>', 2.2),
        warn: svg('<path d="M12 9v4m0 4h.01M10.3 3.9L1.8 18a2 2 0 001.7 3h17a2 2 0 001.7-3L13.7 3.9a2 2 0 00-3.4 0z"/>'),
        eye: svg('<path d="M2.5 12S6 5.5 12 5.5 21.5 12 21.5 12 18 18.5 12 18.5 2.5 12 2.5 12z"/><circle cx="12" cy="12" r="2.8"/>'),
        pin: svg('<path d="M12 21s-6.5-5.6-6.5-11a6.5 6.5 0 0113 0c0 5.4-6.5 11-6.5 11z"/><circle cx="12" cy="10" r="2.3"/>'),
        bag: svg('<path d="M6 8h12l-1 12H7L6 8zm3 0V6.5a3 3 0 016 0V8"/>', 2.2),
        book: svg('<path d="M4 5.5A2.5 2.5 0 016.5 3H20v15H6.5A2.5 2.5 0 004 20.5v-15zM4 20.5A2.5 2.5 0 006.5 23H20"/>'),
        search: svg('<circle cx="11" cy="11" r="6.5"/><path d="M20 20l-4.2-4.2"/>', 2.2),
        sprout: svg('<path d="M12 21v-8m0 0c0-4-3-6.5-7-6.5 0 4 3 6.5 7 6.5zm0 0c0-3 2.5-5.5 6-5.5 0 3.2-2.6 5.5-6 5.5z"/>', 1.9),
    };

    /* ---- Who sells it, and the spray list ------------------------------
       A Shopee search and a Lazada tag page for one active ingredient (the
       bracket notes and "plus" dropped, "2,4 D" read as "2,4-D"); never a
       recommendation, the note under each list says so. */
    FH.shop = (n) => {
        const q = String(n).replace(/\s*\([^)]*\)/g, '').replace(/\s+plus\s+/gi, ' ').replace(/\b2,4 D\b/g, '2,4-D').replace(/\s+/g, ' ').trim();
        const slug = q.toLowerCase().replace(/[^a-z0-9]+/g, '-').replace(/^-+|-+$/g, '');
        return { q, shopee: 'https://shopee.ph/search?keyword=' + encodeURIComponent(q.toLowerCase()), lazada: 'https://www.lazada.com.ph/tag/' + slug + '/' };
    };
    // One active ingredient: everything a spray list row shows. Add a field here and draw it in ingRow.
    FH.ing = (name, group, mode, products) => ({ name: String(name), group: group || '', mode: mode || '', shop: FH.shop(name), products: (products || []).filter(Boolean) });
    // ProductBook::norm: the key a product list is filed under.
    FH.norm = (s) => String(s).toLowerCase().replace(/-/g, ' ').replace(/,/g, '').replace(/\s+/g, ' ').trim();
    FH.prodCards = FH.prodCards || {};
    const TIMING = { pre: ['Pre', 'Pre emergence, before the weeds come up'], early_post: ['Early post', 'Early post emergence, on young weeds'],
        post: ['Post', 'Post emergence, on weeds already up'], burndown: ['Burndown', 'Burndown, to clear the field before planting'] };
    const KIND = { herbicide: 'Herbicide', insecticide: 'Insecticide', fungicide: 'Fungicide', molluscicide: 'Molluscicide', nematicide: 'Nematicide', rodenticide: 'Rodenticide' };
    // The plain line wherever products show: the label decides the rate.
    FH.rateLine = (noun) => '<p class="fh-fine fh-rate">Use the rate on the product label. Rotate groups so ' + (noun === 'weeds' ? 'the weeds do' : 'the ' + esc(noun || 'pest') + ' does') + ' not build resistance.</p>';
    // A product's own search on each shop (2026-10-15, the owner): the brand as it is sold.
    FH.shopOf = (name) => {
        const q = String(name || '').toLowerCase().replace(/\s+/g, ' ').trim();
        return { shopee: 'https://shopee.ph/search?keyword=' + encodeURIComponent(q), lazada: 'https://www.lazada.com.ph/catalog/?q=' + encodeURIComponent(q) };
    };
    // The two shops by their logos; `words` adds "Search Shopee" beside the logo (the product sheet).
    const LOGO = { sp: @json(asset('images/shops/shopee.webp')), lz: @json(asset('images/shops/lazada.webp')) };
    FH.shopLinks = (shopee, lazada, what, cls, words) => '<span class="' + cls + '">'
        + '<a class="shop-logo is-sp" href="' + esc(shopee) + '" target="_blank" rel="noopener nofollow" title="Search Shopee for ' + esc(what) + '" aria-label="Search Shopee for ' + esc(what) + '"><img src="' + LOGO.sp + '" alt="" width="48" height="48">' + (words ? '<span>Search Shopee</span>' : '') + '</a>'
        + '<a class="shop-logo is-lz" href="' + esc(lazada) + '" target="_blank" rel="noopener nofollow" title="Search Lazada for ' + esc(what) + '" aria-label="Search Lazada for ' + esc(what) + '"><img src="' + LOGO.lz + '" alt="" width="48" height="48">' + (words ? '<span>Search Lazada</span>' : '') + '</a></span>';
    FH.prodShop = (p, words) => { const u = FH.shopOf(p.brand); return FH.shopLinks(u.shopee, u.lazada, p.brand, 'fh-pshop', words); };
    FH.prodChip = (p) => '<div class="fh-prod"><button type="button" class="fh-prod-n" data-prod="' + esc(p.key) + '">'
        + '<b><span>' + esc(p.brand) + '</span></b>'
        + (p.timing && TIMING[p.timing] || p.company || p.form ? '<small>' + (p.timing && TIMING[p.timing] ? '<em>' + TIMING[p.timing][0] + '</em>' : '')
            + '<span>' + esc([p.company, p.form].filter(Boolean).join(', ')) + '</span></small>' : '') + '</button>'
        + FH.prodShop(p) + '</div>';
    FH.prodStrip = (cards) => (cards && cards.length) ? '<div class="fh-pcards">' + cards.map((p) => '<div class="fh-pcard"><button type="button" class="fh-pcard-n" data-prod="' + esc(p.key) + '">'
        + '<b>' + esc(p.brand) + '</b><small>' + esc([p.company, p.form].filter(Boolean).join(', ')) + '</small>'
        + ((p.ai || []).length ? '<span>' + esc(p.ai.join(' plus ')) + '</span>' : '') + (p.group ? '<i>' + esc(p.group) + '</i>' : '')
        + (p.timing && TIMING[p.timing] ? '<em>' + TIMING[p.timing][0] + '</em>' : '') + '</button>' + FH.prodShop(p) + '</div>').join('') + '</div>' : '';
    FH.ingRow = (i) => '<li class="fh-ing">'
        + '<div class="fh-ing-top"><b>' + esc(i.name) + '</b>' + (i.group ? '<i class="fh-grp"' + (i.mode ? ' title="' + esc(i.mode) + '"' : '') + '>' + esc(i.group) + '</i>' : '') + '</div>'
        + '<div class="fh-ing-foot">' + (i.mode ? '<small>' + esc(i.mode) + '</small>' : '')
        + FH.shopLinks(i.shop.shopee, i.shop.lazada, i.shop.q || i.name, 'fh-shop') + '</div>'
        + (i.products && i.products.length ? '<div class="fh-prods"><span class="fh-prods-l">Sold as</span>' + i.products.map(FH.prodChip).join('') + '</div>' : '')
        + '</li>';
    FH.ingList = (list) => '<ul class="fh-ings">' + list.map(FH.ingRow).join('') + '</ul>';
    FH.SHOP_NOTE = '<p class="fh-fine">The Shopee and Lazada links only search those shops for the active ingredient or the product. They are not a recommendation of any seller. Check the label, your crop and the FPA registration before you buy.</p>';

    // The local names a farmer would say, when they are names and not a sentence about names.
    FH.localOf = (i) => {
        const s = String(i.local || '').trim(), first = s.split(',')[0].trim();
        if (/\b(is|are|call|called|means)\b/i.test(s)) return '';
        const pick = s.length <= 60 ? s : (first.length <= 48 ? first : '');
        return pick && !String(i.name).toLowerCase().includes(pick.toLowerCase()) ? pick : '';
    };

    /* ---- Redraw with the height easing ---------------------------------- */
    FH.swap = (el, draw) => {
        if (reduce()) { draw(); return; }
        const h0 = el.offsetHeight;
        draw();
        el.classList.remove('is-swap'); void el.offsetWidth; el.classList.add('is-swap');
        const h1 = el.offsetHeight;
        if (Math.abs(h1 - h0) < 2) return;
        el.style.height = h0 + 'px'; el.style.overflow = 'hidden';
        void el.offsetHeight;
        el.style.transition = 'height .34s cubic-bezier(.22,1,.36,1)';
        el.style.height = h1 + 'px';
        clearTimeout(el._swapT);
        el._swapT = setTimeout(() => { el.style.height = ''; el.style.overflow = ''; el.style.transition = ''; }, 380);
    };
    FH.bump = (el) => { if (!el || reduce()) return; el.classList.remove('is-bump'); void el.offsetWidth; el.classList.add('is-bump'); };

    /* ---- Full screen sheets --------------------------------------------- */
    let locks = 0, keys = 0;
    FH.lock = (on) => { locks = Math.max(0, locks + (on ? 1 : -1)); document.documentElement.classList.toggle('fh-lock', locks > 0); };
    // The phone's Back closes what is on top (app.js keeps one history entry
    // per overlay): an entry's page goes back to its list, then the sheet closes.
    const backKey = (dlg) => dlg._backKey || (dlg._backKey = 'fhm:' + (dlg.id || ++keys));
    const onBack = (dlg) => {
        if (dlg.classList.contains('is-detail') && dlg._back) {
            dlg._back();
            window.unregisterOverlay?.(backKey(dlg));
            window.registerOverlay?.(backKey(dlg), () => onBack(dlg));
            return;
        }
        FH.closeModal(dlg);
    };
    FH.openModal = (dlg) => {
        if (!dlg || dlg.open) return;
        dlg.classList.remove('is-closing');
        dlg.showModal();
        window.registerOverlay?.(backKey(dlg), () => onBack(dlg));
        if (!dlg.hasAttribute('tabindex')) dlg.setAttribute('tabindex', '-1');
        dlg.focus({ preventScroll: true });
        FH.lock(true);
        dlg.dispatchEvent(new CustomEvent('fhopen'));
    };
    FH.closeModal = (dlg) => new Promise((res) => {
        if (!dlg || !dlg.open || dlg.classList.contains('is-closing')) { res(); return; }
        window.unregisterOverlay?.(backKey(dlg));
        const done = () => { dlg.classList.remove('is-closing'); dlg.close(); FH.lock(false); dlg.dispatchEvent(new CustomEvent('fhclose')); res(); };
        if (reduce()) { done(); return; }
        dlg.classList.add('is-closing');
        setTimeout(done, 230);
    });
    document.addEventListener('click', (e) => { const x = e.target.closest('[data-fhm-close]'); if (x) FH.closeModal(x.closest('dialog.fhm')); });
    document.addEventListener('cancel', (e) => {
        const dlg = e.target;
        if (!dlg.classList?.contains('fhm')) return;
        e.preventDefault();
        // Esc in an entry goes back to the list first.
        if (dlg.classList.contains('is-detail') && dlg._back) dlg._back(); else FH.closeModal(dlg);
    }, true);

    /* ---- The option sheet ----------------------------------------------- */
    let sheet = null, settle = null, sheetKind = '';
    let sheetTok = 0;
    const closeSheet = (v) => {
        if (!sheet || !sheet.open) return;
        window.unregisterOverlay?.('fh-sheet');
        // A sheet opened again while this one slides away keeps it open.
        const tok = ++sheetTok;
        const done = () => { if (tok !== sheetTok) return; sheet.classList.remove('is-closing'); sheet.close(); FH.lock(false); };
        if (reduce()) done(); else { sheet.classList.add('is-closing'); setTimeout(done, 230); }
        const s = settle; settle = null; if (s) s(v);
    };
    const filterSheet = () => {
        const q = (sheet.querySelector('.cp-find input')?.value || '').toLowerCase().trim();
        let any = false;
        sheet.querySelectorAll('.cp-row').forEach((r) => { const on = !q || r.dataset.q.includes(q); r.hidden = !on; any = any || on; });
        sheet.querySelector('.cp-none')?.classList.toggle('is-on', !any);
    };
    // FH.pick({ title, note, opts: [{k, l, sub, n, icon}], value, multi, all, search, counts }) resolves the choice, or null.
    // The one sheet, made the first time it is wanted.
    const ensureSheet = () => {
        if (!sheet) {
            sheet = document.createElement('dialog');
            sheet.className = 'fh-sheet';
            document.body.appendChild(sheet);
            sheet.addEventListener('click', (e) => {
                if (e.target === sheet || e.target.closest('.cp-x')) { closeSheet(null); return; }
                if (sheetKind === 'info') { if (e.target.closest('.fh-sheet-done')) closeSheet(null); return; }
                const row = e.target.closest('.cp-row');
                if (row && !row.disabled) {
                    if (sheet._multi) { row.setAttribute('aria-selected', String(row.getAttribute('aria-selected') !== 'true')); return; }
                    closeSheet(row.dataset.k);
                    return;
                }
                if (e.target.closest('.fh-sheet-clear')) sheet.querySelectorAll('.cp-row').forEach((r) => r.setAttribute('aria-selected', 'false'));
                if (e.target.closest('.fh-sheet-done')) closeSheet([...sheet.querySelectorAll('.cp-row[aria-selected="true"]')].map((r) => r.dataset.k));
            });
            sheet.addEventListener('input', filterSheet);
            sheet.addEventListener('cancel', (e) => { e.preventDefault(); closeSheet(null); });
        }
        return sheet;
    };
    FH.pick = (cfg) => new Promise((resolve) => {
        ensureSheet();
        if (settle) settle(null);
        settle = resolve;
        sheetKind = 'pick';
        const multi = !!cfg.multi, chosen = new Set(multi ? (cfg.value || []) : [cfg.value ?? '']);
        const rows = (!multi && cfg.all ? [{ k: '', l: cfg.all }] : []).concat(cfg.opts || []);
        sheet._multi = multi;
        sheet.setAttribute('aria-label', cfg.title || 'Pick');
        sheet.innerHTML = '<div class="cp-box" tabindex="-1"><div class="cp-grip" aria-hidden="true"></div>'
            + '<div class="cp-head"><h2>' + esc(cfg.title) + '</h2><button type="button" class="cp-x" aria-label="Close">' + FH.icon.x + '</button></div>'
            + (cfg.note ? '<p class="fh-sheet-note">' + esc(cfg.note) + '</p>' : '')
            + (cfg.search ? '<label class="cp-find">' + FH.icon.search + '<span class="sr-only">Search</span><input type="search" placeholder="' + esc(cfg.search) + '" autocomplete="off"></label>' : '')
            + '<div class="cp-list" role="listbox" aria-label="' + esc(cfg.title) + '"' + (multi ? ' aria-multiselectable="true"' : '') + '>'
            + rows.map((o) => '<button type="button" class="cp-row" role="option" aria-selected="' + chosen.has(o.k) + '" data-k="' + esc(o.k) + '" data-q="' + esc((o.l + ' ' + (o.sub || '')).toLowerCase()) + '"'
                + (cfg.counts !== false && o.k !== '' && o.n === 0 ? ' disabled' : '') + '>'
                + (o.icon ? '<span class="cp-ico" aria-hidden="true">' + o.icon + '</span>' : '')
                + '<span class="cp-t"><b>' + esc(o.l) + '</b>' + (o.sub ? '<small>' + esc(o.sub) + '</small>' : '') + '</span>'
                + (cfg.counts !== false && o.n ? '<span class="cp-n">' + esc(o.n) + '</span>' : '')
                + '<span class="cp-on" aria-hidden="true">' + FH.icon.check + '</span></button>').join('')
            + '<p class="cp-none">Nothing by that name.</p></div>'
            + (multi ? '<div class="fh-sheet-foot"><button type="button" class="fh-sheet-clear">Clear</button><button type="button" class="fh-sheet-done">Show them</button></div>' : '')
            + '</div>';
        sheetTok++;
        sheet.classList.remove('is-closing');
        if (!sheet.open) { sheet.showModal(); FH.lock(true); }
        window.registerOverlay?.('fh-sheet', () => closeSheet(null));
        sheet.querySelector('.cp-row[aria-selected="true"]')?.scrollIntoView({ block: 'nearest' });
        if (cfg.search && matchMedia('(pointer: fine)').matches) sheet.querySelector('.cp-find input').focus();
        else sheet.querySelector('.cp-box').focus({ preventScroll: true });
    });

    // One product in the same sheet: what it is, what is in it, when it goes on, what its label names.
    FH.productSheet = (p) => {
        if (!p) return;
        ensureSheet();
        if (settle) settle(null);
        settle = null;
        sheetKind = 'info';
        sheet._multi = false;
        sheet.setAttribute('aria-label', p.brand);
        const facts = [['Active ingredients', (p.ai || []).join(', ')], ['Group', p.group], ['Form', p.form], ['When', p.timing && TIMING[p.timing] ? TIMING[p.timing][1] : ''],
            ['Labelled for', (p.crops || []).join(', ')]].filter((x) => x[1]);
        sheet.innerHTML = '<div class="cp-box" tabindex="-1"><div class="cp-grip" aria-hidden="true"></div>'
            + '<div class="cp-head"><h2>' + esc(p.brand) + '</h2><button type="button" class="cp-x" aria-label="Close">' + FH.icon.x + '</button></div>'
            + '<div class="fh-psheet"><p class="fh-pkind"><span>' + esc(KIND[p.kind] || p.kind || 'Product') + '</span>' + (p.company ? 'by ' + esc(p.company) : '') + '</p>'
            + '<div class="fhd-facts">' + facts.map(([l, v]) => '<div><small>' + esc(l) + '</small><b style="font-size:.88rem">' + esc(v) + '</b></div>').join('') + '</div>'
            + (p.note ? '<p class="fh-pnote">' + esc(p.note) + '</p>' : '')
            + '<div class="fh-psheet-shop"><b>Find it in a shop</b>' + FH.prodShop(p, true) + '</div>'
            + FH.rateLine(p.kind === 'herbicide' ? 'weeds' : (p.kind === 'fungicide' ? 'disease' : 'pest'))
            + '<p class="fh-fine">The links only search Shopee and Lazada for this name. They are not a recommendation of any seller. Check the label and the FPA registration before you buy.</p></div>'
            + '<div class="fh-sheet-foot"><button type="button" class="fh-sheet-done">Close</button></div></div>';
        sheetTok++;
        sheet.classList.remove('is-closing');
        if (!sheet.open) { sheet.showModal(); FH.lock(true); }
        window.registerOverlay?.('fh-sheet', () => closeSheet(null));
        sheet.querySelector('.cp-box').focus({ preventScroll: true });
    };
    document.addEventListener('click', (e) => { const b = e.target.closest('[data-prod]'); if (b && FH.prodCards[b.dataset.prod]) FH.productSheet(FH.prodCards[b.dataset.prod]); });

    /* ---- A whole catalogue in a full screen sheet -------------------------
       cfg: { dlg (the dialog.fhm a field-helpers.partials.catalogue drew),
       items (entries, each with k, name, sci, local, crops, hue, thumb),
       noun, nouns, tag(i) the word on its card, facet {key, title, all,
       opts [[k, label, icon, n]], icon(k)} and detail(i) its page. }
       Returns { open({k, crop}) }. */
    FH.catalogue = (cfg) => {
        const dlg = cfg.dlg;
        if (!dlg) return { open() {} };
        const $ = (s) => dlg.querySelector(s);
        const grid = $('[data-fhc-grid]'), none = $('[data-fhc-none]'), nOut = $('[data-fhc-n]'), nounOut = $('[data-fhc-noun]'), clear = $('[data-fhc-clear]'), q = $('.fhc-search input');
        const cropTag = $('.fhc-crop .cp-tag'), facetTag = $('[data-fhc-facet]'), detail = $('[data-fhc-detail]'), back = $('.fhm-back');
        const title = $('[data-fhm-title]'), sub = $('[data-fhm-sub]'), title0 = title.textContent, sub0 = sub.textContent;
        const listPane = $('.fhm-list'), detailPane = $('.fhm-detail');
        const st = { q: '', crop: '', facet: '' };
        const byK = {};
        cfg.items.forEach((i) => { byK[i.k] = i; i._q = (i.name + ' ' + (i.sci || '') + ' ' + (i.local || '') + ' ' + (cfg.tag(i) || '')).toLowerCase(); });
        let drawn = false, cards = [];
        const card = (i) => {
            const loc = FH.localOf(i);
            return '<button type="button" class="fhc-card" style="--g: ' + (i.hue || 98) + '" data-k="' + esc(i.k) + '">'
                + '<span class="fhc-ph">' + (i.thumb ? '<img src="' + esc(i.thumb) + '" alt="" loading="lazy" decoding="async" referrerpolicy="no-referrer">' : '<span class="none">' + FH.icon.leaf + '</span>')
                + '<i>' + esc(cfg.tag(i)) + '</i></span>'
                + '<span class="fhc-in"><b>' + esc(i.name) + '</b>' + (loc ? '<em>' + esc(loc) + '</em>' : '') + (i.sci ? '<small>' + esc(i.sci) + '</small>' : '') + '</span></button>';
        };
        const draw = () => { if (drawn) return; grid.innerHTML = cfg.items.map(card).join(''); cards = [...grid.children]; drawn = true; };
        const paint = (animate) => {
            draw();
            const t = st.q.toLowerCase().trim();
            let n = 0;
            cards.forEach((c, x) => {
                const i = cfg.items[x];
                const on = (!t || i._q.includes(t)) && (!st.crop || i.crops.includes(st.crop)) && (!st.facet || i.kind === st.facet);
                // Under a crop, its worst first, as its sources rank them.
                c.style.order = st.crop && i.ranks && i.ranks[st.crop] !== undefined ? String(i.ranks[st.crop] - 1000) : '';
                if (c.hidden === on) c.hidden = !on;
                if (on && animate && !reduce()) { c.classList.remove('is-in'); c.style.animationDelay = Math.min(n, 14) * 22 + 'ms'; void c.offsetWidth; c.classList.add('is-in'); }
                if (on) n++;
            });
            nOut.textContent = n;
            if (nounOut) nounOut.textContent = n === 1 ? cfg.noun : cfg.nouns;
            none.hidden = n > 0;
            clear.hidden = !(t || st.crop || st.facet);
        };
        const facetSub0 = facetTag.querySelector('.cp-t small').textContent;
        const setFacet = (k) => {
            st.facet = k;
            const o = (cfg.facet.opts || []).find((x) => x[0] === k);
            facetTag.querySelector('.cp-t b').textContent = o ? o[1] : cfg.facet.all;
            facetTag.querySelector('.cp-t small').textContent = o ? o[3] + ' ' + (o[3] === 1 ? cfg.noun : cfg.nouns) : facetSub0;
            facetTag.querySelector('.cp-ico').innerHTML = (o && cfg.facet.icon ? cfg.facet.icon(o[0]) : '') || cfg.facet.allIcon || '';
            facetTag.classList.toggle('is-set', !!k);
        };
        const setCrop = (k) => { st.crop = k || ''; if (cropTag) { window.cropPickSet?.(cropTag, st.crop); cropTag.classList.toggle('is-set', !!st.crop); } };
        let qt = 0;
        q.addEventListener('input', () => { clearTimeout(qt); qt = setTimeout(() => { st.q = q.value; paint(true); }, 140); });
        cropTag?.addEventListener('croppick', (e) => { st.crop = e.detail.crop || ''; cropTag.classList.toggle('is-set', !!st.crop); paint(true); });
        facetTag.addEventListener('click', async () => {
            const k = await FH.pick({ title: cfg.facet.title, all: cfg.facet.all, value: st.facet,
                opts: cfg.facet.opts.map(([k, l, icon, n]) => ({ k, l, n, icon: cfg.facet.icon ? cfg.facet.icon(k) : icon })) });
            if (k === null || k === st.facet) return;
            setFacet(k); FH.bump(facetTag); paint(true);
        });
        clear.addEventListener('click', () => { q.value = ''; st.q = ''; setCrop(''); setFacet(''); paint(true); });

        const showList = () => {
            dlg.classList.remove('is-detail');
            back.hidden = true;
            title.textContent = title0; sub.textContent = sub0;
            listPane.removeAttribute('inert'); detailPane.setAttribute('inert', '');
        };
        // An entry slides in over the list; opened straight from a finder it is simply there.
        const showDetail = (i, instant) => {
            detail.innerHTML = cfg.detail(i, { crop: st.crop });
            detailPane.scrollTop = 0;
            title.textContent = i.name; sub.textContent = title0;
            back.hidden = false;
            listPane.setAttribute('inert', ''); detailPane.removeAttribute('inert');
            if (instant) {
                dlg.classList.add('is-instant', 'is-detail');
                requestAnimationFrame(() => requestAnimationFrame(() => dlg.classList.remove('is-instant')));
            } else requestAnimationFrame(() => dlg.classList.add('is-detail'));
        };
        dlg._back = showList;
        back.addEventListener('click', showList);
        setFacet('');
        grid.addEventListener('click', (e) => { const c = e.target.closest('.fhc-card'); if (c && byK[c.dataset.k]) showDetail(byK[c.dataset.k]); });
        detailPane.setAttribute('inert', '');
        return {
            open(o = {}) {
                if (o.crop !== undefined) setCrop(o.crop);
                paint(false);
                if (o.k && byK[o.k]) showDetail(byK[o.k], !dlg.open); else showList();
                FH.openModal(dlg);
                if (!o.k) listPane.scrollTop = 0;
            },
            close: () => FH.closeModal(dlg),
            get: (k) => byK[k],
        };
    };

    // Crop chips for an entry's page: its crops by name, the one asked about first; a long list shows twelve and a way to the rest.
    FH.cropChips = (keys, names, first) => {
        const list = keys.slice().sort((a, b) => (b === first) - (a === first)).filter((k) => names[k]);
        const SHOW = 12, more = list.length - SHOW;
        return '<ul class="fhd-chips">' + list.map((k, x) => {
            const c = names[k];
            return '<li' + (k === first ? ' class="is-on"' : '') + (more > 2 && x >= SHOW ? ' hidden data-more' : '') + '><span aria-hidden="true">' + esc(c[2] || '') + '</span>' + esc(c[0])
                + (c[0] !== c[1] ? ' <small>' + esc(c[1].replace(/,.*$/, '')) + '</small>' : '') + '</li>';
        }).join('') + (more > 2 ? '<li class="fhd-chips-more"><button type="button" data-chips-more>And ' + more + ' more crops</button></li>' : '') + '</ul>';
    };
    document.addEventListener('click', (e) => {
        const b = e.target.closest('[data-chips-more]');
        if (!b) return;
        const ul = b.closest('ul');
        ul.querySelectorAll('li[data-more]').forEach((li, x) => { li.hidden = false; if (!reduce()) { li.style.animationDelay = Math.min(x, 30) * 12 + 'ms'; li.classList.add('is-in'); } });
        b.parentElement.remove();
    });
    // An entry's photo, with its credit (most are shared under a licence that asks for one).
    FH.photo = (i) => {
        const first = i.thumb || i.photo;
        if (first && i.photo && i.photo !== first) {
            const big = new Image();
            big.referrerPolicy = 'no-referrer';
            big.onload = () => document.querySelectorAll('img[data-big="' + CSS.escape(i.photo) + '"]').forEach((im) => { im.src = i.photo; });
            big.src = i.photo;
        }
        return '<figure class="fhd-ph">' + (first ? '<img src="' + esc(first) + '" data-big="' + esc(i.photo || '') + '" alt="' + esc(i.alt || i.name) + '" referrerpolicy="no-referrer">' : '<span class="none">' + FH.icon.leaf + '</span>')
            + (i.credit ? '<figcaption>Photo: ' + esc(i.credit) + '</figcaption>' : '') + '</figure>';
    };
    FH.sec = (icon, h, body) => '<section class="fhd-sec"><h3>' + icon + esc(h) + '</h3>' + body + '</section>';
    FH.sources = (list) => (list && list.length) ? '<p class="fhd-src">Based on ' + list.slice(0, 4).map((s) => s.url ? '<a href="' + esc(s.url) + '" target="_blank" rel="noopener nofollow">' + esc(s.label) + '</a>' : esc(s.label)).join(', ') + '.</p>' : '';
})();
</script>
@endpush
@endonce
