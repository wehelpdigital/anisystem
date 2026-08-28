@once
{{-- WHAT AN ARTICLE LOOKS LIKE.

     A band, not a tile. Three 16:9 tiles to a row is half a card of
     photograph before a word of the article, which is what the blog's own
     page was rebuilt away from — and Home was still drawing the old shape,
     so the same article looked like two different things depending on which
     screen you met it on.

     Full-bleed on a phone by default, the way the wall's posts and a
     discussion's head are. Add `.blog-grid-inset` around them where the
     surrounding page already has its own gutter and the bleed would push the
     bands out past it. --}}
<style>
    /* --- An article is a band, not a tile ---
       Three to a row on a desktop and one to a row on a phone meant the same
       page had two personalities, and a 16:9 cover across a full-width tile
       is half a screen of photograph before a word of the article. One column
       of bands, each with its own colour along the top and the bottom — the
       shape the discussions and the wall already read in — and from 640px up
       the cover steps aside to the left so the words start at the top. */
    .blog-grid { display:grid; grid-template-columns:1fr; gap:.85rem; }
    .blog-card { position:relative; display:flex; flex-direction:column; overflow:hidden;
        border-radius:0; border-left:0; border-right:0;
        border-top:1px solid var(--color-gray-100); border-bottom:1px solid var(--color-gray-100);
        margin-left:calc(var(--plaza-gutter, 1rem) * -1);
        margin-right:calc(var(--plaza-gutter, 1rem) * -1);
        background:var(--color-white); box-shadow:var(--shadow-card); text-decoration:none;
        --bl-a:#4a7c2a; --bl-b:#8fc267;
        transition:box-shadow .28s cubic-bezier(.22,1,.36,1); }
    /* Above the cover, which starts at the very edge of the band. */
    .blog-card::before, .blog-card::after { content:''; position:absolute; inset:0 0 auto 0; height:3px;
        z-index:3; pointer-events:none;
        background:linear-gradient(90deg, var(--bl-a), var(--bl-b) 55%, transparent); }
    .blog-card::after { inset:auto 0 0 0;
        background:linear-gradient(270deg, var(--bl-a), var(--bl-b) 55%, transparent); }
    /* Each article keeps its colour by id, so it is the same one every visit. */
    .bl-hue-1 { --bl-a:#1d4ed8; --bl-b:#7aa5f5; }
    .bl-hue-2 { --bl-a:#b45309; --bl-b:#ecc06a; }
    .bl-hue-3 { --bl-a:#0f766e; --bl-b:#6cc9bf; }
    .bl-hue-4 { --bl-a:#7c3aed; --bl-b:#b393f5; }
    .bl-hue-5 { --bl-a:#be185d; --bl-b:#f090b8; }
    /* A band that lifts on hover lifts the page with it; it deepens instead. */
    .blog-card:hover { box-shadow:0 10px 30px -12px rgb(0 0 0 / .25); }
    .blog-cover { position:relative; height:9.5rem; background:linear-gradient(120deg,var(--color-brand-100),var(--color-brand-50)); overflow:hidden; }
    @media (min-width:640px) {
        .blog-card { flex-direction:row; align-items:stretch; }
        .blog-cover { flex:none; width:16rem; height:auto; min-height:8.5rem; }
        .blog-body { flex:1 1 auto; justify-content:center; padding:1rem 1.25rem; }
        .blog-title { font-size:1.05rem; }
    }
    /* Covers fade up out of a shimmer instead of popping in — the gallery's
       loading language. A 404 just leaves the quiet brand gradient. */
    .blog-cover img { width:100%; height:100%; object-fit:cover; opacity:0; transition:opacity .28s ease; }
    .blog-cover img.is-loaded { opacity:1; }
    /* A story with several covers stacks them and shows one at a time. The
       page drifts through them on its own clock (crossfade); a thumb slides
       them left or right (the prep/out classes give the slide its
       direction). No dots and no arrows, on purpose. */
    .blog-cover img.bc-img { position:absolute; inset:0; opacity:0;
        transition:opacity .7s cubic-bezier(.22,1,.36,1), transform .5s cubic-bezier(.22,1,.36,1); }
    .blog-cover img.bc-img.is-on.is-loaded { opacity:1; }
    .blog-cover img.bc-prep-l { transform:translateX(-32%); transition:none; }
    .blog-cover img.bc-prep-r { transform:translateX(32%); transition:none; }
    .blog-cover img.bc-img.is-on { transform:translateX(0); }
    .blog-cover img.bc-out-l { transform:translateX(-32%); }
    .blog-cover img.bc-out-r { transform:translateX(32%); }
    .blog-cover[data-covers] { touch-action:pan-y; }
    .blog-more { font-size:.8rem; font-weight:800; color:var(--color-brand-700);
        transition:color .28s cubic-bezier(.22,1,.36,1); }
    .blog-card:hover .blog-more { text-decoration:underline; }
    html.dark .blog-more { color:var(--color-brand-300, #a3d284); }
    @media (prefers-reduced-motion: reduce) {
        .blog-cover .bc-img { transition:opacity .3s ease; transform:none !important; }
    }
    .blog-cover:has(img)::before { content:''; position:absolute; inset:0; pointer-events:none;
        background:linear-gradient(100deg, rgba(255,255,255,0) 20%, rgba(255,255,255,.5) 50%, rgba(255,255,255,0) 80%);
        background-size:220% 100%; animation:blogShimmer 1.15s linear infinite; }
    .blog-cover:has(img.is-loaded)::before, .blog-cover:not(:has(img))::before { display:none; }
    @keyframes blogShimmer { from { background-position:220% 0; } to { background-position:-220% 0; } }
    @media (prefers-reduced-motion: reduce) {
        .blog-card { transition:none; }
        .blog-cover:has(img)::before { animation:none; background:rgb(255 255 255 / .25); }
    }
    .blog-cover-fallback { width:100%; height:100%; display:flex; align-items:center; justify-content:center; font-size:2.4rem; }
    .blog-body { padding:.85rem 1rem 1rem; display:flex; flex-direction:column; gap:.35rem; }
    .blog-title { font-family:var(--font-heading); font-weight:700; color:var(--color-gray-900); line-height:1.25; }
    .blog-excerpt { font-size:.83rem; color:var(--color-gray-500); line-height:1.4; }
    .blog-meta { margin-top:.4rem; font-size:.72rem; color:var(--color-gray-400); display:flex; gap:.6rem; flex-wrap:wrap; }

    /* Inside a page that already has a gutter — Home — the bands stay within
       it rather than reaching past both edges. */
    .blog-grid-inset .blog-card { margin-left: 0; margin-right: 0;
        border-radius: 1rem; border-left: 1px solid var(--color-gray-100);
        border-right: 1px solid var(--color-gray-100); }
</style>
@endonce
