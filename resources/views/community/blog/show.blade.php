@extends('layouts.app')

@section('title', $post->title . ' · Blog')
@section('body-class', 'plaza-ground')
@section('page-title', 'Technician\'s Blog')
@section('page-subtitle', \Illuminate\Support\Str::limit($post->title, 40))
@section('back', \App\Support\BackTo::url(route('community.blog')))

{{-- THE READING PAGE (rebuilt 2026-10-23, the owner: "fix how each blog looks
     when you read it. i don't like it. make it better").

     The words used to sit straight on the plaza's green ground at the full
     width of the column, links looked like plain text, wide tables were cut
     off on a phone and the end of an article just stopped. Now an article is
     a sheet of paper: the cover across its top, the category, the headline,
     the line under it and how long it takes to read; an "In this article" list
     for the long guides; type set for reading (a measure of about 70
     characters, real links, sections with an accent, tables that turn into
     cards on a phone, questions that open); a reading bar under the app bar;
     sharing (the public page when there is one, so a farmer with no account
     can read it); then more from the same category and the comments. --}}
@php
    $catUrl = $post->category ? route('community.blog', ['cat' => $post->category]) : route('community.blog');
    $shareUrl = $publicUrl ?: route('community.blog.show', ['id' => $post->id]);
@endphp

@push('head')
@include('community.partials.plaza-css')
@include('community.blog.partials.card-css')
<style>
    :root { --rd-ease: cubic-bezier(.22,1,.36,1); }

    /* ---- the reading bar, just under the app bar ---- */
    .rd-progress { position:fixed; left:0; right:0; top:var(--app-head, 64px); height:3px; z-index:35; pointer-events:none; }
    .rd-progress i { display:block; height:100%; width:100%; transform-origin:0 50%; transform:scaleX(0);
        background:linear-gradient(90deg, var(--color-brand-500, #6b9f3d), #f5c518); transition:transform .12s linear; }

    /* ---- the sheet ---- */
    .rd { position:relative; background:var(--color-white); box-shadow:var(--shadow-card);
        border-top:1px solid var(--color-gray-100); border-bottom:1px solid var(--color-gray-100);
        margin-left:calc(var(--plaza-gutter, 1rem) * -1); margin-right:calc(var(--plaza-gutter, 1rem) * -1);
        animation:rdIn .5s var(--rd-ease) both; }
    @keyframes rdIn { from { opacity:0; transform:translateY(10px); } }
    @media (min-width:1024px) {
        .rd { margin-left:0; margin-right:0; border-radius:1.25rem; border:1px solid var(--color-gray-200); overflow:hidden; }
        html.dark .rd { border-color:#2b3a1c; }
    }

    /* ---- the head: cover, kicker, headline, the line under it, the facts ---- */
    .rd-cover { position:relative; margin:0; aspect-ratio:16/8; max-height:24rem; overflow:hidden; background:var(--color-brand-50); }
    .rd-cover img { width:100%; height:100%; object-fit:cover; display:block; opacity:0; transform:scale(1.03);
        transition:opacity .5s var(--rd-ease), transform 1.2s var(--rd-ease); }
    .rd-cover img.is-loaded { opacity:1; transform:none; }
    .rd-cover::after { content:''; position:absolute; inset:auto 0 0 0; height:35%; pointer-events:none;
        background:linear-gradient(180deg, transparent, rgb(0 0 0 / .18)); }
    .rd-cover:not(:has(img.is-loaded))::before { content:''; position:absolute; inset:0; pointer-events:none;
        background:linear-gradient(100deg, rgba(255,255,255,0) 20%, rgba(255,255,255,.5) 50%, rgba(255,255,255,0) 80%);
        background-size:220% 100%; animation:rdShimmer 1.15s linear infinite; }
    @keyframes rdShimmer { from { background-position:220% 0; } to { background-position:-220% 0; } }
    .rd-head-in, .rd-toc, .rd-body, .rd-end { max-width:43rem; margin-left:auto; margin-right:auto; }
    .rd-head-in { padding:1.3rem 1.15rem 0; }
    @media (min-width:640px) { .rd-head-in { padding:1.9rem 2rem 0; } }
    .rd-kicks { display:flex; flex-wrap:wrap; align-items:center; gap:.45rem; font-size:.74rem; font-weight:800; letter-spacing:.06em; text-transform:uppercase; }
    .rd-kicks a { text-decoration:none; transition:color .28s var(--rd-ease), background-color .28s var(--rd-ease); }
    .rd-crumb { color:var(--color-gray-500); }
    .rd-crumb:hover { color:var(--color-brand-700); }
    .rd-dot { width:.3rem; height:.3rem; border-radius:999px; background:var(--color-gray-300); }
    .rd-cat { padding:.3rem .65rem; border-radius:999px; color:var(--color-brand-700); background:var(--color-brand-50); border:1px solid var(--color-brand-100); }
    .rd-cat:hover { background:var(--color-brand-100); }
    html.dark .rd-cat { color:#bfe19a; background:rgb(61 104 35 / .25); border-color:#3f5626; }
    .rd-title { margin-top:.8rem; font-family:var(--font-heading); font-weight:800; letter-spacing:-.015em; line-height:1.16;
        font-size:clamp(1.6rem, 4.6vw, 2.25rem); color:var(--color-gray-900); text-wrap:balance; }
    .rd-dek { margin-top:.75rem; font-size:clamp(1rem, 2.2vw, 1.12rem); line-height:1.6; color:var(--color-gray-600); text-wrap:pretty; }
    .rd-meta { display:flex; flex-wrap:wrap; align-items:center; gap:.5rem 1.1rem; margin-top:1.15rem; padding:.9rem 0 1.1rem;
        border-top:1px solid var(--color-gray-100); font-size:.8rem; font-weight:700; color:var(--color-gray-500); }
    html.dark .rd-meta { border-top-color:rgb(255 255 255 / .08); }
    .rd-meta > span { display:inline-flex; align-items:center; gap:.35rem; }
    .rd-meta svg { width:1rem; height:1rem; color:var(--color-brand-600); flex:none; }
    .rd-share { display:inline-flex; align-items:center; gap:.4rem; margin-left:auto; min-height:2.3rem; padding:.4rem .9rem; border-radius:999px;
        font-size:.8rem; font-weight:800; color:var(--color-brand-700); background:var(--color-brand-50); border:1px solid var(--color-brand-100);
        cursor:pointer; transition:background-color .28s var(--rd-ease), transform .28s var(--rd-ease); }
    .rd-share:hover { background:var(--color-brand-100); }
    .rd-share:active { transform:scale(.96); }
    .rd-share svg { width:1rem; height:1rem; color:currentColor; }
    html.dark .rd-share { color:#bfe19a; background:rgb(61 104 35 / .25); border-color:#3f5626; }

    /* ---- "In this article" (built from the sections when there are three or more) ---- */
    .rd-toc { margin-top:.2rem; margin-bottom:.4rem; padding:0 1.15rem; }
    @media (min-width:640px) { .rd-toc { padding:0 2rem; } }
    .rd-toc-box { border-radius:1rem; background:var(--color-gray-50); border:1px solid var(--color-gray-100); overflow:hidden; }
    html.dark .rd-toc-box { background:rgb(255 255 255 / .03); border-color:rgb(255 255 255 / .07); }
    .rd-toc summary { display:flex; align-items:center; gap:.6rem; padding:.85rem 1rem; cursor:pointer; list-style:none;
        font-size:.86rem; font-weight:800; color:var(--color-gray-800); }
    .rd-toc summary::-webkit-details-marker { display:none; }
    .rd-toc summary small { font-size:.74rem; font-weight:700; color:var(--color-gray-400); }
    .rd-toc summary svg.rd-toc-ico { width:1.05rem; height:1.05rem; color:var(--color-brand-600); flex:none; }
    .rd-toc-chev { margin-left:auto; width:1rem; height:1rem; color:var(--color-gray-400); transition:transform .28s var(--rd-ease); }
    .rd-toc details[open] .rd-toc-chev { transform:rotate(180deg); }
    .rd-toc ol { list-style:none; margin:0; padding:0 .55rem .7rem; counter-reset:rdToc; }
    .rd-toc details[open] ol { animation:rdOpen .32s var(--rd-ease); }
    @keyframes rdOpen { from { opacity:0; transform:translateY(-4px); } }
    .rd-toc li { counter-increment:rdToc; }
    .rd-toc a { display:flex; gap:.6rem; padding:.45rem .5rem; border-radius:.65rem; font-size:.88rem; font-weight:600; line-height:1.35;
        color:var(--color-gray-700); text-decoration:none; transition:background-color .28s var(--rd-ease), color .28s var(--rd-ease); }
    .rd-toc a::before { content:counter(rdToc); flex:none; display:grid; place-items:center; width:1.45rem; height:1.45rem; border-radius:999px;
        font-size:.7rem; font-weight:800; color:var(--color-brand-700); background:var(--color-brand-100); transition:background-color .28s var(--rd-ease), color .28s var(--rd-ease); }
    .rd-toc a:hover { background:var(--color-white); color:var(--color-gray-900); }
    .rd-toc a.is-here { color:var(--color-brand-800); background:var(--color-white); }
    .rd-toc a.is-here::before { color:#fff; background:var(--color-brand-600); }
    html.dark .rd-toc a:hover, html.dark .rd-toc a.is-here { background:rgb(255 255 255 / .05); }

    /* ---- the words ---- */
    .rd-body { padding:1rem 1.15rem 1.5rem; font-size:1.02rem; line-height:1.78; color:var(--color-gray-800); overflow-wrap:break-word; }
    @media (min-width:640px) { .rd-body { padding:1.3rem 2rem 1.8rem; font-size:1.07rem; } }
    .rd-body > p:first-child { font-size:1.08em; color:var(--color-gray-700); }
    .rd-body p { margin:0 0 1.05rem; }
    .rd-body a:not(.a-btn):not(.a-outlet) { color:var(--color-brand-700); font-weight:600; text-decoration:underline;
        text-decoration-color:var(--color-brand-300); text-decoration-thickness:2px; text-underline-offset:3px;
        transition:text-decoration-color .28s var(--rd-ease), color .28s var(--rd-ease); }
    .rd-body a:not(.a-btn):not(.a-outlet):hover { text-decoration-color:var(--color-brand-600); }
    html.dark .rd-body a:not(.a-btn):not(.a-outlet) { color:#bfe19a; text-decoration-color:rgb(191 225 154 / .4); }
    .rd-body b, .rd-body strong { color:var(--color-gray-900); font-weight:800; }
    .rd-body h2 { position:relative; margin:2.3rem 0 .8rem; padding-top:.9rem; font-family:var(--font-heading); font-weight:800;
        font-size:clamp(1.3rem, 3vw, 1.5rem); line-height:1.25; letter-spacing:-.01em; color:var(--color-gray-900); scroll-margin-top:calc(var(--app-head, 64px) + 1rem); }
    .rd-body h2::before { content:''; position:absolute; left:0; top:0; width:2.4rem; height:4px; border-radius:4px;
        background:linear-gradient(90deg, var(--color-brand-600, #4a7c2a), #f5c518); }
    .rd-body h3 { margin:1.8rem 0 .6rem; font-family:var(--font-heading); font-weight:800; font-size:1.17rem; line-height:1.3; color:var(--color-gray-900);
        scroll-margin-top:calc(var(--app-head, 64px) + 1rem); }
    .rd-body h4 { margin:1.4rem 0 .45rem; font-family:var(--font-heading); font-weight:800; font-size:1.02rem; color:var(--color-gray-900); }
    .rd-body ul, .rd-body ol { margin:0 0 1.15rem; padding-left:1.35rem; }
    .rd-body li { margin:.35rem 0; padding-left:.2rem; }
    .rd-body ul > li::marker { color:var(--color-brand-500, #6b9f3d); font-size:1.1em; }
    .rd-body ol > li::marker { color:var(--color-brand-700); font-weight:800; }
    .rd-body blockquote { position:relative; margin:1.6rem 0; padding:1rem 1.2rem 1rem 3rem; border-radius:1rem; font-size:1.06em; font-style:italic;
        color:var(--color-gray-700); background:var(--color-gray-50); }
    .rd-body blockquote::before { content:'\201C'; position:absolute; left:.8rem; top:.2rem; font-family:Georgia, serif; font-size:3rem; line-height:1;
        font-style:normal; color:var(--color-brand-400, #86b556); }
    .rd-body blockquote cite { display:block; margin-top:.5rem; font-size:.84rem; font-style:normal; font-weight:700; color:var(--color-gray-500); }
    html.dark .rd-body blockquote { background:rgb(255 255 255 / .04); }
    .rd-body hr { border:0; height:auto; margin:2rem 0; text-align:center; }
    .rd-body hr::after { content:'\2022  \2022  \2022'; white-space:pre; letter-spacing:.4em; color:var(--color-gray-300); font-size:1rem; }
    .rd-body .a-fig { margin:1.6rem 0; }
    .rd-body .a-fig img, .rd-body > img { width:100%; height:auto; display:block; border-radius:1rem; box-shadow:0 14px 30px -22px rgb(0 0 0 / .6); }
    .rd-body .a-cap { margin-top:.5rem; text-align:center; font-size:.82rem; line-height:1.45; color:var(--color-gray-500); }
    .rd-body .a-gallery { display:grid; grid-template-columns:repeat(auto-fit, minmax(9rem, 1fr)); gap:.5rem; margin:1.4rem 0; }
    .rd-body .a-gallery img { width:100%; height:100%; aspect-ratio:4/3; object-fit:cover; border-radius:.8rem; }
    .rd-body .a-embed { position:relative; padding-top:56.25%; margin:1.4rem 0; border-radius:1rem; overflow:hidden; }
    .rd-body .a-embed iframe { position:absolute; inset:0; width:100%; height:100%; border:0; }

    /* A note the writer wants read: a card with an accent down its side. */
    .rd-body .a-note { position:relative; margin:1.5rem 0; padding:1rem 1.1rem 1rem 1.25rem; border-radius:1rem; font-size:.97em; line-height:1.65;
        background:var(--color-brand-50); border:1px solid var(--color-brand-100); }
    .rd-body .a-note::before { content:''; position:absolute; left:0; top:.9rem; bottom:.9rem; width:4px; border-radius:0 4px 4px 0; background:var(--color-brand-500, #6b9f3d); }
    .rd-body .a-note > b:first-child { display:block; margin-bottom:.3rem; font-family:var(--font-heading); font-size:1.02em; color:var(--color-gray-900); }
    .rd-body .a-note ul { margin:.35rem 0 0; }
    .rd-body .a-note p:last-child { margin-bottom:0; }
    .rd-body .a-note.is-warn { background:#fff8ec; border-color:#f3dcae; }
    .rd-body .a-note.is-warn::before { background:#e9a80b; }
    html.dark .rd-body .a-note { background:rgb(61 104 35 / .16); border-color:#2f4420; }
    html.dark .rd-body .a-note.is-warn { background:#241d10; border-color:#5c4a24; }
    .rd-body .a-note.lt { --lt-track:var(--color-white); --lt-line:var(--color-brand-200); --lt-on:var(--color-brand-600); --lt-ink:var(--color-brand-800); --lt-on-ink:#fff; }

    /* Buttons and newsroom chips (news roundups). */
    .rd-body .a-btn { display:inline-flex; align-items:center; gap:.45rem; margin:.3rem 0; padding:.6rem 1.15rem; border-radius:999px; font-weight:800; font-size:.92rem;
        color:#fff !important; background:var(--color-brand-600); text-decoration:none; transition:transform .28s var(--rd-ease), background-color .28s var(--rd-ease); }
    .rd-body .a-btn:hover { transform:translateY(-1px); }
    .rd-body .a-btn.a-out::after { content:''; flex:none; width:.95rem; height:.95rem; background:currentColor;
        -webkit-mask:url("data:image/svg+xml,%3Csvg xmlns='http://www.w3.org/2000/svg' viewBox='0 0 24 24' fill='none' stroke='black' stroke-width='2.4' stroke-linecap='round' stroke-linejoin='round'%3E%3Cpath d='M14 4h6v6M20 4l-9 9M18 14v5a1 1 0 01-1 1H5a1 1 0 01-1-1V7a1 1 0 011-1h5'/%3E%3C/svg%3E") center / contain no-repeat;
        mask:url("data:image/svg+xml,%3Csvg xmlns='http://www.w3.org/2000/svg' viewBox='0 0 24 24' fill='none' stroke='black' stroke-width='2.4' stroke-linecap='round' stroke-linejoin='round'%3E%3Cpath d='M14 4h6v6M20 4l-9 9M18 14v5a1 1 0 01-1 1H5a1 1 0 01-1-1V7a1 1 0 011-1h5'/%3E%3C/svg%3E") center / contain no-repeat; }
    .rd-body .a-outlets { display:flex; flex-wrap:wrap; gap:.45rem; margin:.2rem 0 1.1rem; }
    .rd-body .a-outlet { display:inline-flex; align-items:center; gap:.45rem; min-height:2.5rem; padding:.35rem .85rem .35rem .5rem; border-radius:999px;
        border:1.5px solid var(--color-gray-200); background:var(--color-white); color:var(--color-gray-800) !important; font-size:.86rem; font-weight:700; text-decoration:none;
        transition:border-color .28s var(--rd-ease); }
    .rd-body .a-outlet:hover { border-color:var(--color-brand-500, #6b9f3d); }
    .rd-body .a-outlet img { flex:none; width:1.15rem; height:1.15rem; margin:0; border-radius:.3rem; background:#fff; object-fit:contain; }
    html.dark .rd-body .a-outlet { border-color:#2b3a1c; background:#151b12; color:#e8efe1 !important; }

    /* Tables: a rounded card with a tinted head; on a phone, every row a card of its own. */
    .rd-body .a-table { margin:1.5rem 0 .4rem; border-radius:1rem; border:1px solid var(--color-gray-200); overflow-x:auto; -webkit-overflow-scrolling:touch; }
    .rd-body table { width:100%; border-collapse:collapse; font-size:.9rem; line-height:1.5; }
    .rd-body .a-table table { margin:0; }
    .rd-body th, .rd-body td { padding:.65rem .8rem; text-align:left; vertical-align:top; border-bottom:1px solid var(--color-gray-100); }
    .rd-body th { font-size:.74rem; font-weight:800; letter-spacing:.05em; text-transform:uppercase; color:var(--color-brand-800); background:var(--color-brand-50); }
    .rd-body tbody tr:nth-child(even) td { background:var(--color-gray-50); }
    .rd-body tbody tr:last-child td { border-bottom:0; }
    .rd-body td:first-child { font-weight:700; color:var(--color-gray-900); }
    html.dark .rd-body .a-table { border-color:#2b3a1c; }
    html.dark .rd-body th { color:#bfe19a; background:rgb(61 104 35 / .2); }
    html.dark .rd-body tbody tr:nth-child(even) td { background:rgb(255 255 255 / .025); }
    html.dark .rd-body th, html.dark .rd-body td { border-bottom-color:rgb(255 255 255 / .06); }
    @media (max-width:639.98px) {
        .rd-body .a-table.is-stack { border:0; border-radius:0; overflow:visible; }
        .rd-body .is-stack table, .rd-body .is-stack tbody, .rd-body .is-stack tr, .rd-body .is-stack td { display:block; width:100%; }
        .rd-body .is-stack thead { display:none; }
        .rd-body .is-stack tr { margin-bottom:.65rem; border-radius:1rem; border:1px solid var(--color-gray-200); overflow:hidden; background:var(--color-white); }
        .rd-body .is-stack tbody tr:nth-child(even) td { background:transparent; }
        .rd-body .is-stack td { display:grid; grid-template-columns:minmax(6.5rem, 38%) 1fr; gap:.6rem; padding:.55rem .85rem; border-bottom:1px solid var(--color-gray-100); }
        .rd-body .is-stack td::before { content:attr(data-label); font-size:.7rem; font-weight:800; letter-spacing:.04em; text-transform:uppercase; color:var(--color-gray-500); padding-top:.15rem; }
        .rd-body .is-stack td:first-child { display:block; font-family:var(--font-heading); font-size:1rem; color:var(--color-gray-900); background:var(--color-brand-50) !important; }
        .rd-body .is-stack td:first-child::before { display:none; }
        .rd-body .is-stack td:last-child { border-bottom:0; }
        html.dark .rd-body .is-stack tr { border-color:#2b3a1c; }
        html.dark .rd-body .is-stack td:first-child { background:rgb(61 104 35 / .2) !important; }
    }

    /* Questions and answers (a FAQ section): each opens on a tap. */
    .rd-body .rd-qa { margin:.55rem 0; border-radius:1rem; border:1px solid var(--color-gray-200); background:var(--color-white); overflow:hidden;
        transition:border-color .28s var(--rd-ease), box-shadow .28s var(--rd-ease); }
    .rd-body .rd-qa[open] { border-color:var(--color-brand-200); box-shadow:0 12px 26px -22px rgb(20 33 12 / .6); }
    .rd-body .rd-qa summary { display:flex; align-items:center; gap:.75rem; padding:.85rem 1rem; cursor:pointer; list-style:none;
        font-family:var(--font-heading); font-weight:800; font-size:.98rem; line-height:1.35; color:var(--color-gray-900); }
    .rd-body .rd-qa summary::-webkit-details-marker { display:none; }
    .rd-body .rd-qa summary i { position:relative; flex:none; margin-left:auto; width:1.7rem; height:1.7rem; border-radius:999px; background:var(--color-brand-50);
        transition:background-color .28s var(--rd-ease), transform .28s var(--rd-ease); }
    .rd-body .rd-qa summary i::before, .rd-body .rd-qa summary i::after { content:''; position:absolute; left:50%; top:50%; width:.7rem; height:2px; margin:-1px 0 0 -.35rem;
        border-radius:2px; background:var(--color-brand-700); transition:transform .28s var(--rd-ease); }
    .rd-body .rd-qa summary i::after { transform:rotate(90deg); }
    .rd-body .rd-qa[open] summary i { background:#f5c518; }
    .rd-body .rd-qa[open] summary i::after { transform:rotate(0deg); }
    .rd-body .rd-qa > div { padding:0 1rem .95rem; font-size:.96em; color:var(--color-gray-700); }
    .rd-body .rd-qa[open] > div { animation:rdOpen .32s var(--rd-ease); }
    .rd-body .rd-qa > div p:last-child { margin-bottom:0; }
    html.dark .rd-body .rd-qa { border-color:#2b3a1c; }

    /* ---- the end of the article ---- */
    .rd-end { padding:0 1.15rem 1.4rem; }
    @media (min-width:640px) { .rd-end { padding:0 2rem 2rem; } }
    .rd-end-in { display:flex; flex-wrap:wrap; align-items:center; gap:.65rem; padding-top:1.1rem; border-top:1px solid var(--color-gray-100); }
    html.dark .rd-end-in { border-top-color:rgb(255 255 255 / .08); }
    .rd-end-cat { display:inline-flex; align-items:center; gap:.4rem; min-height:2.3rem; padding:.4rem .9rem; border-radius:999px; font-size:.82rem; font-weight:800;
        text-decoration:none; color:var(--color-gray-700); border:1px solid var(--color-gray-200); transition:border-color .28s var(--rd-ease), color .28s var(--rd-ease); }
    .rd-end-cat:hover { border-color:var(--color-brand-300); color:var(--color-brand-700); }
    .rd-end-cat svg { width:1rem; height:1rem; }
    .rd-end .rd-share { margin-left:0; }
    .rd-end-note { width:100%; margin-top:.2rem; font-size:.78rem; line-height:1.5; color:var(--color-gray-400); }

    /* ---- read next, and the talk ---- */
    .rd-sec-head { display:flex; align-items:baseline; justify-content:space-between; gap:1rem; margin:2rem 0 .85rem; }
    .rd-sec-head h2 { font-family:var(--font-heading); font-size:1.2rem; font-weight:800; color:var(--color-gray-900); }
    .rd-sec-head a { font-size:.82rem; font-weight:800; color:var(--color-brand-700); text-decoration:none; }
    .rd-sec-head a:hover { text-decoration:underline; }
    .rd-talk-n { display:inline-grid; place-items:center; min-width:1.6rem; height:1.6rem; margin-left:.45rem; padding:0 .45rem; border-radius:999px;
        font-size:.78rem; font-weight:800; vertical-align:.15em; color:var(--color-brand-700); background:var(--color-brand-50); }
    html.dark .rd-talk-n { color:#bfe19a; background:rgb(61 104 35 / .25); }
    .rd-compose { padding:.85rem; border-radius:1.1rem; }

    @media (prefers-reduced-motion: reduce) {
        .rd, .rd-toc details[open] ol, .rd-body .rd-qa[open] > div { animation:none; }
        .rd-cover img { transform:none; transition:opacity .3s ease; }
        .rd-cover:not(:has(img.is-loaded))::before { animation:none; background:rgb(255 255 255 / .25); }
        .rd-progress i, .rd-share, .rd-toc a, .rd-toc a::before, .rd-toc-chev, .rd-body a, .rd-body .rd-qa, .rd-body .rd-qa summary i,
        .rd-body .rd-qa summary i::before, .rd-body .rd-qa summary i::after, .rd-end-cat, .rd-body .a-btn, .rd-body .a-outlet { transition:none; }
    }
</style>
@include('partials.lang-tabs')
@endpush

@section('content')
<div class="rd-progress" aria-hidden="true"><i id="rdBar"></i></div>

{{-- Two columns on a wide screen: the article, and the rail (the wall, your chats, the rooms). --}}
<div class="plaza-shell">
<div class="plaza-center">

<article class="rd" id="rdArticle">
    <header>
        @if ($post->coverUrl())
            <figure class="rd-cover"><img src="{{ $post->coverUrl() }}" alt=""
                onload="this.classList.add('is-loaded')" onerror="this.closest('.rd-cover')?.remove()"></figure>
        @endif
        <div class="rd-head-in">
            <div class="rd-kicks">
                <a href="{{ route('community.blog') }}" class="rd-crumb">Technician's Blog</a>
                @if ($post->category)
                    <span class="rd-dot" aria-hidden="true"></span>
                    <a href="{{ $catUrl }}" class="rd-cat">{{ $post->category }}</a>
                @endif
            </div>
            <h1 class="rd-title">{{ $post->title }}</h1>
            @if ($post->excerpt)
                <p class="rd-dek">{{ $post->excerpt }}</p>
            @endif
            {{-- No byline in the app: who wrote it is not shown (the owner, 2026-10-18). --}}
            <div class="rd-meta">
                @if ($post->publishedAt)
                    <span><svg fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24" aria-hidden="true"><path stroke-linecap="round" stroke-linejoin="round" d="M8 7V3m8 4V3M5 11h14M5 21h14a2 2 0 002-2V7a2 2 0 00-2-2H5a2 2 0 00-2 2v12a2 2 0 002 2z"/></svg>{{ $post->publishedAt->format('F j, Y') }}</span>
                @endif
                <span><svg fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24" aria-hidden="true"><path stroke-linecap="round" stroke-linejoin="round" d="M12 8v4l3 2m6-2a9 9 0 11-18 0 9 9 0 0118 0z"/></svg>{{ $minutes }} min read</span>
                <span><svg fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24" aria-hidden="true"><path stroke-linecap="round" stroke-linejoin="round" d="M15 12a3 3 0 11-6 0 3 3 0 016 0z"/><path stroke-linecap="round" stroke-linejoin="round" d="M2.5 12C3.8 7.9 7.6 5 12 5s8.2 2.9 9.5 7c-1.3 4.1-5.1 7-9.5 7s-8.2-2.9-9.5-7z"/></svg>{{ number_format($post->viewCount) }} {{ \Illuminate\Support\Str::plural('read', $post->viewCount) }}</span>
                <button type="button" class="rd-share" data-rd-share>
                    <svg fill="none" stroke="currentColor" stroke-width="2.2" viewBox="0 0 24 24" aria-hidden="true"><path stroke-linecap="round" stroke-linejoin="round" d="M8.7 10.7l6.6-3.4m-6.6 6l6.6 3.4M21 5a3 3 0 11-6 0 3 3 0 016 0zM9 12a3 3 0 11-6 0 3 3 0 016 0zm12 7a3 3 0 11-6 0 3 3 0 016 0z"/></svg>
                    Share
                </button>
            </div>
        </div>
    </header>

    {{-- Filled by the script below from the article's own sections (three or more). --}}
    <nav class="rd-toc" id="rdToc" aria-label="In this article" hidden>
        <details class="rd-toc-box">
            <summary>
                <svg class="rd-toc-ico" fill="none" stroke="currentColor" stroke-width="2.2" viewBox="0 0 24 24" aria-hidden="true"><path stroke-linecap="round" stroke-linejoin="round" d="M4 6h16M4 12h16M4 18h10"/></svg>
                In this article <small id="rdTocN"></small>
                <svg class="rd-toc-chev" fill="none" stroke="currentColor" stroke-width="2.4" viewBox="0 0 24 24" aria-hidden="true"><path stroke-linecap="round" stroke-linejoin="round" d="M6 9l6 6 6-6"/></svg>
            </summary>
            <ol id="rdTocList"></ol>
        </details>
    </nav>

    {{-- An article is written by staff in a builder, not by a member in a
         comment box, so it goes through the wider list — the one that lets a
         picture, a pull quote and a section heading survive. --}}
    <div class="rd-body" id="rdBody">{!! \App\Support\CommunityText::articleHtml($post->body) !!}</div>

    <footer class="rd-end">
        <div class="rd-end-in">
            @if ($post->category)
                <a href="{{ $catUrl }}" class="rd-end-cat">
                    <svg fill="none" stroke="currentColor" stroke-width="2.2" viewBox="0 0 24 24" aria-hidden="true"><path stroke-linecap="round" stroke-linejoin="round" d="M7 7h.01M3 12l9-9h8v8l-9 9-8-8z"/></svg>
                    More in {{ $post->category }}
                </a>
            @endif
            <button type="button" class="rd-share" data-rd-share>
                <svg fill="none" stroke="currentColor" stroke-width="2.2" viewBox="0 0 24 24" aria-hidden="true"><path stroke-linecap="round" stroke-linejoin="round" d="M8.7 10.7l6.6-3.4m-6.6 6l6.6 3.4M21 5a3 3 0 11-6 0 3 3 0 016 0zM9 12a3 3 0 11-6 0 3 3 0 016 0zm12 7a3 3 0 11-6 0 3 3 0 016 0z"/></svg>
                Share this article
            </button>
            @if ($publicUrl)
                <p class="rd-end-note">The link opens the same article on anee.io, so a farmer without an account can read it too.</p>
            @endif
        </div>
    </footer>
</article>

@if ($next->count())
    <section aria-labelledby="rdNextH">
        <div class="rd-sec-head">
            <h2 id="rdNextH">{{ $post->category ? 'More in ' . $post->category : 'Read next' }}</h2>
            <a href="{{ $catUrl }}">See all</a>
        </div>
        <div class="blog-grid">
            @include('community.blog.partials.cards', ['posts' => $next])
        </div>
    </section>
@endif

{{-- Comments --}}
<section id="blogComments" aria-labelledby="rdTalkH">
    <div class="rd-sec-head">
        <h2 id="rdTalkH">Comments<span class="rd-talk-n" id="blogCommentCount">{{ $comments->count() }}</span></h2>
    </div>

    <div class="card rd-compose mb-4 emoji-scope">
        <textarea id="blogCommentInput" class="form-textarea" rows="3" maxlength="5000" placeholder="Ask a question or share what worked on your farm…"></textarea>

        <div id="blogAttach" class="hidden mt-2">
            <div class="flex items-center gap-2">
                <img id="blogAttachThumb" alt="" class="w-16 h-16 object-cover rounded-lg border border-gray-100">
                <button type="button" id="blogAttachRemove" class="text-xs font-semibold text-gray-400 hover:text-red-500">✕ Remove photo</button>
            </div>
        </div>

        <div class="flex items-center justify-between gap-2 mt-2">
            <div class="flex items-center gap-1">
                <button type="button" class="btn btn-white btn-sm js-emoji-btn" data-target="blogCommentInput" title="Add emoji" aria-label="Add emoji">
                    <svg class="w-5 h-5" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" d="M14.828 14.828a4 4 0 01-5.656 0M9 10h.01M15 10h.01M21 12a9 9 0 11-18 0 9 9 0 0118 0z"/></svg>
                </button>
                <label class="btn btn-white btn-sm cursor-pointer mb-0" title="Add a photo">
                    <svg class="w-5 h-5" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" d="M4 16l4.586-4.586a2 2 0 012.828 0L16 16m-2-2l1.586-1.586a2 2 0 012.828 0L20 14m-6-6h.01M6 20h12a2 2 0 002-2V6a2 2 0 00-2-2H6a2 2 0 00-2 2v12a2 2 0 002 2z"/></svg>
                    <input type="file" id="blogCommentImage" accept="image/jpeg,image/png,image/webp" class="hidden">
                </label>
            </div>
            <button type="button" id="blogCommentSend" class="btn btn-primary btn-sm">Post comment</button>
        </div>
    </div>

    <div id="blogCommentList" class="space-y-3">
        @forelse ($comments as $comment)
            @include('community.blog.partials.comment', ['comment' => $comment])
        @empty
            <p class="text-sm text-gray-400 text-center py-4" id="blogNoComments">Be the first to comment.</p>
        @endforelse
    </div>
</section>

</div>{{-- /plaza-center --}}
<aside class="plaza-side plaza-side-right">
    @include('community.partials.plaza-rail', ['rail' => ['posts', 'chats', 'discussions']])
</aside>
</div>{{-- /plaza-shell --}}
@endsection

@push('scripts')
@include('community.partials.emoji-js')
@include('community.partials.react-js')
@include('community.partials.lightbox-js')
@include('community.partials.comment-tools-js')
<script>
/* The reading page: the bar, the sections list, the tables, the questions and sharing. */
(function reader() {
    const body = document.getElementById('rdBody');
    const article = document.getElementById('rdArticle');
    if (!body || !article) return;
    const still = window.matchMedia('(prefers-reduced-motion: reduce)').matches;

    // How far through the article: a thin bar under the app bar.
    const bar = document.getElementById('rdBar');
    let ticking = false;
    const paintBar = () => {
        ticking = false;
        const r = article.getBoundingClientRect();
        const total = r.height - window.innerHeight * 0.6;
        const done = Math.min(1, Math.max(0, -r.top / Math.max(1, total)));
        if (bar) bar.style.transform = 'scaleX(' + done.toFixed(4) + ')';
    };
    window.addEventListener('scroll', () => { if (!ticking) { ticking = true; requestAnimationFrame(paintBar); } }, { passive: true });
    window.addEventListener('resize', paintBar);
    paintBar();

    // Questions under a questions section open on a tap: each h4 and the words after it.
    body.querySelectorAll('h2').forEach((h2) => {
        if (!/question|faq|tanong/i.test(h2.textContent)) return;
        let el = h2.nextElementSibling;
        while (el && el.tagName !== 'H2') {
            const next = el.nextElementSibling;
            if (el.tagName === 'H4') {
                const qa = document.createElement('details');
                qa.className = 'rd-qa';
                const sum = document.createElement('summary');
                sum.textContent = el.textContent;
                sum.appendChild(document.createElement('i'));
                const ans = document.createElement('div');
                let a = el.nextElementSibling;
                while (a && !/^H[234]$/.test(a.tagName)) {
                    const n = a.nextElementSibling;
                    ans.appendChild(a);
                    a = n;
                }
                qa.append(sum, ans);
                el.replaceWith(qa);
                el = a;
                continue;
            }
            el = next;
        }
    });

    // A table of three columns or more becomes a card per row on a phone.
    body.querySelectorAll('.a-table').forEach((wrap) => {
        const heads = [...wrap.querySelectorAll('thead th')].map((th) => th.textContent.trim());
        if (heads.length < 3) return;
        wrap.querySelectorAll('tbody tr').forEach((tr) => {
            [...tr.children].forEach((td, i) => td.setAttribute('data-label', heads[i] || ''));
        });
        wrap.classList.add('is-stack');
    });

    // "In this article": the sections, when there are three or more.
    const heads = [...body.querySelectorAll('h2')].filter((h) => h.textContent.trim() !== '');
    const toc = document.getElementById('rdToc');
    if (toc && heads.length >= 3) {
        const list = document.getElementById('rdTocList');
        const used = {};
        heads.forEach((h, i) => {
            let id = 'rd-' + (h.textContent.toLowerCase().replace(/[^a-z0-9]+/g, '-').replace(/^-|-$/g, '').slice(0, 60) || 'part');
            if (used[id]) id += '-' + i;
            used[id] = true;
            h.id = id;
            const li = document.createElement('li');
            const a = document.createElement('a');
            a.href = '#' + id;
            a.textContent = h.textContent.trim();
            a.addEventListener('click', (e) => {
                e.preventDefault();
                h.scrollIntoView({ behavior: still ? 'auto' : 'smooth', block: 'start' });
                try { history.replaceState(null, '', '#' + id); } catch (_) {}
            });
            li.appendChild(a);
            list.appendChild(li);
        });
        document.getElementById('rdTocN').textContent = '· ' + heads.length + ' parts';
        toc.hidden = false;
        // Open on a desk, folded on a phone (the words come first there).
        if (window.matchMedia('(min-width: 1024px)').matches) toc.querySelector('details').open = true;
        // The part being read is marked in the list.
        if ('IntersectionObserver' in window) {
            const links = [...list.querySelectorAll('a')];
            const io = new IntersectionObserver((entries) => {
                entries.forEach((en) => {
                    if (!en.isIntersecting) return;
                    const i = heads.indexOf(en.target);
                    links.forEach((l, j) => l.classList.toggle('is-here', j === i));
                });
            }, { rootMargin: '-15% 0px -70% 0px' });
            heads.forEach((h) => io.observe(h));
        }
    }

    // Share: the phone's own share sheet, else the link copied.
    const shareUrl = @json($shareUrl);
    const shareTitle = @json($post->title);
    document.querySelectorAll('[data-rd-share]').forEach((btn) => btn.addEventListener('click', async () => {
        if (navigator.share) {
            try { await navigator.share({ title: shareTitle, url: shareUrl }); return; } catch (e) { if (e && e.name === 'AbortError') return; }
        }
        try {
            await navigator.clipboard.writeText(shareUrl);
            window.toast?.('Link copied. Paste it in a chat or a post.');
        } catch (_) {
            window.prompt?.('Copy this link', shareUrl);
        }
    }));
})();

(function blogComments() {
    const $ = (id) => document.getElementById(id);
    const input = $('blogCommentInput');
    const fileInput = $('blogCommentImage');
    const attach = $('blogAttach');
    const attachThumb = $('blogAttachThumb');
    const SEND_URL = @json(route('community.blog.comment', ['id' => $post->id]));

    const clearAttach = () => {
        fileInput.value = '';
        attach.classList.add('hidden');
        if (attachThumb.src) { try { URL.revokeObjectURL(attachThumb.src); } catch (_) {} attachThumb.removeAttribute('src'); }
    };
    fileInput.addEventListener('change', () => {
        const f = fileInput.files[0];
        if (f) { attachThumb.src = URL.createObjectURL(f); attach.classList.remove('hidden'); }
        else clearAttach();
    });
    $('blogAttachRemove').addEventListener('click', clearAttach);

    $('blogCommentSend').addEventListener('click', async (e) => {
        const btn = e.currentTarget;
        const body = input.value.trim();
        const file = fileInput.files[0];
        if (!body && !file) { window.toast && toast('Write a comment or add a photo.', 'error'); return; }
        const prevHtml = btn.innerHTML;
        btn.disabled = true;
        btn.classList.add('is-sending');
        btn.innerHTML = '<span class="inline-flex items-center gap-1.5"><svg class="w-4 h-4 plaza-spin" viewBox="0 0 24 24" fill="none" aria-hidden="true"><circle cx="12" cy="12" r="9" stroke="currentColor" stroke-width="2.4" stroke-opacity=".25"/><path d="M21 12a9 9 0 0 0-9-9" stroke="currentColor" stroke-width="2.4" stroke-linecap="round"/></svg>Posting…</span>';
        try {
            const fd = new FormData();
            if (body) fd.append('body', body);
            if (file) fd.append('image', file);
            const res = await window.api(SEND_URL, { method: 'POST', body: fd });
            $('blogNoComments')?.remove();
            $('blogCommentList').insertAdjacentHTML('afterbegin', res.data.html);
            window.plazaCommentFx?.animateIn($('blogCommentList').firstElementChild);
            input.value = '';
            clearAttach();
            const c = $('blogCommentCount');
            const n = $('blogCommentList').querySelectorAll('[data-blog-comment]').length;
            if (c) c.textContent = n;
            window.toast && toast('Comment posted.');
        } catch (err) {
            window.toast && toast(err.message, 'error');
        } finally {
            btn.disabled = false;
            btn.classList.remove('is-sending');
            btn.innerHTML = prevHtml;
        }
    });
})();
</script>
@endpush
