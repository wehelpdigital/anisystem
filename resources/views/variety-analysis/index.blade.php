@extends('layouts.app')
@section('title', 'Variety Research')
@section('page-title', 'Variety Research')
@section('page-subtitle', 'The right variety, analyzed and compared')

@section('back', route('app.dashboard'))
@push('scripts')
<script>
    // Back goes to wherever you actually came from - the home card or the
    // schedules page - and only falls back to Home on a cold open.
    document.getElementById('appBackLink')?.addEventListener('click', (e) => {
        try {
            const ref = document.referrer ? new URL(document.referrer) : null;
            if (ref && ref.origin === location.origin && window.history.length > 1) {
                e.preventDefault();
                history.back();
            }
        } catch (_) { /* the href already points home */ }
    });
</script>
@endpush


@section('content')
<style>
    /* ---- VARIETY RESEARCH ----------------------------------------------
       The third of the Quick Tools: the sisters' wizard walk, veil and
       shelf, and the wtp-* dress copied whole so the three read as one
       family — but this one searches the web, and its answer is a ranked
       comparison with four bars per variety. */
    .wtp-tabs { display: flex; gap: .4rem; margin-bottom: 1rem; }
    .wtp-tab { flex: 1 1 0; padding: .6rem; border-radius: .8rem; font-weight: 800; font-size: .9rem;
        text-align: center; color: var(--color-gray-500); background: var(--color-white);
        border: 1px solid var(--color-gray-200); cursor: pointer; }
    .wtp-tab.is-on { background: var(--color-brand-600); border-color: var(--color-brand-600); color: #fff; }

    .wtp-quote { border-radius: .9rem; margin-bottom: 1rem; overflow: hidden;
        background: linear-gradient(115deg, #f3f8ec, #e4efd4); border: 1px solid #cfe3b8; }
    .wtp-quote b { color: #2d5016; }
    .q-head { display: flex; align-items: center; gap: .6rem; width: 100%; text-align: left;
        padding: .7rem .9rem; cursor: pointer; }
    .q-head .q-ico { font-size: 1.15rem; flex: none; }
    .q-title { flex: 1 1 auto; min-width: 0; font-size: .84rem; font-weight: 800; color: #2d5016; }
    .q-hint { flex: none; font-size: .74rem; font-weight: 700; color: #3d5226; opacity: 0;
        transition: opacity .28s cubic-bezier(.22,1,.36,1); }
    .is-min .q-hint { opacity: .8; }
    .q-c { flex: none; width: 1rem; height: 1rem; color: #3d5226; opacity: .6;
        transition: transform .28s cubic-bezier(.22,1,.36,1); }
    .is-min .q-c { transform: rotate(-90deg); }
    .q-body { overflow: hidden; opacity: 1;
        transition: max-height .28s cubic-bezier(.22,1,.36,1), opacity .28s cubic-bezier(.22,1,.36,1); }
    .is-min .q-body { max-height: 0; opacity: 0; }
    .q-body-in { overflow: hidden; min-height: 0; display: grid; gap: .5rem; padding: 0 .9rem; }
    .q-body-in::after { content: ''; height: .4rem; }
    .q-card { border-radius: .7rem; padding: .6rem .75rem; font-size: .82rem; color: #3d5226;
        line-height: 1.5; background: rgb(255 255 255 / .6); border: 1px solid rgb(207 227 184 / .8); }

    .wtp-wiz { position: relative; overflow: hidden; }
    .wtp-step { display: none; }
    .wtp-step.is-on { display: block; animation: wtpIn .32s cubic-bezier(.22,1,.36,1) both; }
    @keyframes wtpIn { from { opacity: 0; transform: translateX(24px); } to { opacity: 1; transform: none; } }
    .wtp-step.is-back { animation-name: wtpBack; }
    @keyframes wtpBack { from { opacity: 0; transform: translateX(-24px); } to { opacity: 1; transform: none; } }
    .wtp-dots { display: flex; gap: .35rem; justify-content: center; margin: 1rem 0 .2rem; }
    .wtp-dot { width: .5rem; height: .5rem; border-radius: 999px; background: var(--color-gray-200);
        transition: all .28s cubic-bezier(.22,1,.36,1); }
    .wtp-dot.is-on { width: 1.4rem; background: var(--color-brand-600); }
    .wtp-q { font-size: 1.05rem; font-weight: 800; color: var(--color-gray-900); margin-bottom: .2rem; }
    .wtp-sub { font-size: .8rem; color: var(--color-gray-500); margin-bottom: .8rem; }

    .wtp-choices { display: grid; gap: .5rem; }
    .wtp-choices.is-two { grid-template-columns: 1fr 1fr; }
    @media (max-width: 639px) { .wtp-choices.is-two { grid-template-columns: 1fr; } }
    .wtp-choice { display: flex; align-items: center; gap: .7rem; padding: .75rem .85rem; border-radius: .9rem;
        border: 1.5px solid var(--color-gray-200); background: var(--color-white); cursor: pointer;
        text-align: left; font-weight: 700; color: var(--color-gray-800); font-size: .9rem;
        transition: transform .28s cubic-bezier(.22,1,.36,1), border-color .2s, background .2s; }
    .wtp-choice:hover { transform: translateY(-1px); }
    .wtp-choice.is-on { border-color: var(--color-brand-600); background: var(--color-brand-50); color: var(--color-brand-800); }
    .wtp-choice .c-e { font-size: 1.3rem; }
    .wtp-choice small { display: block; font-weight: 500; font-size: .72rem; color: var(--color-gray-500); }

    .wtp-probs { display: grid; grid-template-columns: 1fr; gap: .4rem; }
    @media (min-width: 640px) { .wtp-probs { grid-template-columns: 1fr 1fr; } }
    .wtp-prob { display: flex; align-items: center; gap: .55rem; padding: .55rem .7rem; border-radius: .7rem;
        border: 1.5px solid var(--color-gray-200); background: var(--color-white); cursor: pointer;
        font-size: .8rem; font-weight: 600; color: var(--color-gray-700);
        transition: border-color .28s cubic-bezier(.22,1,.36,1), background .28s cubic-bezier(.22,1,.36,1); }
    .wtp-prob input { accent-color: #4a7c2a; width: 1rem; height: 1rem; flex: none; }
    .wtp-prob.is-on { border-color: var(--color-brand-500); background: var(--color-brand-50); }

    .wtp-nav { display: flex; gap: .6rem; margin-top: 1.1rem; }
    .wtp-run { display: flex; align-items: center; justify-content: center; gap: .5rem; width: 100%;
        padding: .85rem 1rem; border-radius: 1rem; color: #fff; font-weight: 800; font-size: .95rem;
        background: linear-gradient(115deg, #7bb24a, #4a7c2a 30%, #3d6823 55%, #6b9f3d 80%, #8fc96a);
        background-size: 260% 100%; animation: wtpTide 5.5s ease-in-out infinite alternate;
        box-shadow: 0 10px 22px -12px rgb(61 104 35 / .65); }
    .wtp-run:disabled { opacity: .6; }
    @keyframes wtpTide { from { background-position: 0% 50%; } to { background-position: 100% 50%; } }

    /* The crop tag and its sheet — the lot form's dress, copied whole so a
       farmer meets the same picker everywhere a crop is chosen. */
    .crop-tag { display: flex; align-items: center; gap: .5rem; width: 100%;
        padding: .65rem .8rem; border-radius: .8rem; cursor: pointer; text-align: left;
        border: 1.5px solid var(--color-gray-200); background: var(--color-white);
        transition: border-color .2s, background .2s; }
    .crop-tag:hover { border-color: var(--color-brand-300); background: var(--color-brand-50); }
    .crop-tag-e { font-size: 1.1rem; line-height: 1; flex: none; }
    .crop-tag-t { flex: 1 1 auto; min-width: 0; font-size: .9rem; font-weight: 700; color: #3d6823;
        overflow: hidden; text-overflow: ellipsis; white-space: nowrap; }
    .crop-tag-t.is-none { color: var(--color-gray-400); font-weight: 500; }
    .crop-tag-c { width: 1rem; height: 1rem; flex: none; color: var(--color-gray-400); }
    html.dark .crop-tag { background: #1c2416; border-color: #2b3a1c; }
    html.dark .crop-tag-t { color: #a5c97e; }
    .crop-search { position: relative; margin-bottom: .6rem; }
    .crop-search svg { position: absolute; left: .8rem; top: 50%; transform: translateY(-50%);
        width: 1.05rem; height: 1.05rem; color: var(--color-gray-400); pointer-events: none; }
    .crop-search .form-input { padding-left: 2.4rem; padding-right: 2.2rem; }
    .crop-search-x { position: absolute; right: .5rem; top: 50%; transform: translateY(-50%);
        width: 1.6rem; height: 1.6rem; border-radius: 999px; color: var(--color-gray-400); }
    .crop-search-x:hover { background: var(--color-gray-100); }
    .crop-group-h { font-size: .68rem; font-weight: 800; letter-spacing: .05em; text-transform: uppercase;
        color: var(--color-gray-400); margin: .8rem 0 .25rem; }
    .crop-row { display: flex; align-items: center; gap: .65rem; width: 100%; text-align: left;
        padding: .5rem .6rem; border-radius: .7rem; cursor: pointer; }
    .crop-row:hover { background: var(--color-brand-50); }
    .crop-row-e { font-size: 1.25rem; line-height: 1; flex: none; }
    .crop-row-t { min-width: 0; }
    .crop-row-t b { display: block; font-size: .875rem; font-weight: 700; color: var(--color-gray-900); }
    .crop-row-t small { display: block; font-size: .7rem; color: var(--color-gray-400); }
    .crop-none { font-size: .8rem; color: var(--color-gray-400); text-align: center; padding: 1rem 0; }
    html.dark .crop-row:hover { background: #22301a; }
    html.dark .crop-row-t b { color: #e8efe1; }

    /* The varieties the farmer is weighing: typed one at a time, worn as chips. */
    .va-add { display: flex; gap: .5rem; }
    .va-add .form-input { flex: 1 1 auto; min-width: 0; }
    .va-chips { display: flex; flex-wrap: wrap; gap: .4rem; margin-top: .7rem; }
    .va-chips:empty { display: none; }
    .va-chip { display: inline-flex; align-items: center; gap: .35rem; padding: .35rem .5rem .35rem .7rem;
        border-radius: 999px; font-size: .8rem; font-weight: 700; color: var(--color-brand-800);
        background: var(--color-brand-50); border: 1px solid var(--color-brand-200);
        animation: vaChipIn .28s cubic-bezier(.22,1,.36,1) both; }
    @keyframes vaChipIn { from { opacity: 0; transform: scale(.85); } to { opacity: 1; transform: none; } }
    .va-chip button { width: 1.25rem; height: 1.25rem; border-radius: 999px; display: inline-flex; align-items: center;
        justify-content: center; color: var(--color-brand-700); font-size: .8rem; line-height: 1; }
    .va-chip button:hover { background: rgb(74 124 42 / .15); }
    .va-none { font-size: .78rem; color: var(--color-gray-400); font-style: italic; }
    .va-anee { display: flex; align-items: center; gap: .6rem; margin-top: .6rem; padding: .6rem .75rem; border-radius: .8rem;
        background: #fdf7e6; border: 1px solid #f3dfa4; font-size: .78rem; color: #7a5a12; line-height: 1.45; }
    .va-anee img { width: 1.6rem; height: 1.6rem; border-radius: 999px; object-fit: cover; flex: none; }
    html.dark .va-chip { background: #22301a; border-color: #3d5226; color: #cfe6b8; }
    html.dark .va-chip button { color: #a5c97e; }
    html.dark .va-anee { background: #2a2413; border-color: #4a3d16; color: #e0c26a; }

    /* The priorities, ranked: an ordered list with arrows that swap rows;
       the row slides to its new place rather than jumping. */
    .va-rank { display: grid; gap: .45rem; }
    .va-row { display: flex; align-items: center; gap: .6rem; padding: .6rem .7rem; border-radius: .9rem;
        border: 1.5px solid var(--color-gray-200); background: var(--color-white);
        transition: transform .28s cubic-bezier(.22,1,.36,1), border-color .28s, background .28s; }
    .va-row.is-moving { transition: none; }
    .va-row .r-n { flex: none; width: 1.7rem; height: 1.7rem; border-radius: 999px; display: inline-flex;
        align-items: center; justify-content: center; font-size: .8rem; font-weight: 800;
        background: var(--color-brand-600); color: #fff; }
    .va-row:nth-child(2) .r-n { background: var(--color-brand-500); }
    .va-row:nth-child(3) .r-n { background: var(--color-brand-400); }
    .va-row:nth-child(4) .r-n { background: var(--color-gray-400); }
    .va-row .r-e { font-size: 1.25rem; flex: none; }
    .va-row .r-t { flex: 1 1 auto; min-width: 0; }
    .va-row .r-t b { display: block; font-size: .88rem; color: var(--color-gray-900); }
    .va-row .r-t small { display: block; font-size: .7rem; color: var(--color-gray-500); line-height: 1.35; }
    .va-row .r-w { flex: none; font-size: .68rem; font-weight: 800; color: var(--color-brand-700);
        background: var(--color-brand-50); padding: .15rem .45rem; border-radius: 999px; }
    .va-row .r-btns { flex: none; display: flex; flex-direction: column; gap: .15rem; }
    .va-row .r-btns button { width: 1.6rem; height: 1.35rem; border-radius: .4rem; display: inline-flex; align-items: center;
        justify-content: center; color: var(--color-gray-500); background: var(--color-gray-100); }
    .va-row .r-btns button:hover:not(:disabled) { background: var(--color-brand-100); color: var(--color-brand-800); }
    .va-row .r-btns button:disabled { opacity: .3; }
    .va-row .r-btns svg { width: .8rem; height: .8rem; }
    html.dark .va-row { background: #151b12; border-color: #2b3a1c; }
    html.dark .va-row .r-t b { color: #e8efe1; }
    html.dark .va-row .r-btns button { background: #222b1a; color: #93a684; }
    html.dark .va-row .r-w { background: #22301a; color: #a5c97e; }

    /* ---- THE REPORT ---- */
    .wtp-report { display: grid; gap: .9rem; }
    .wtp-hero { border-radius: 1.1rem; padding: 1.1rem 1.2rem; color: #fff;
        background: linear-gradient(130deg, #4a7c2a, #2d5016 70%); }
    .wtp-hero h2 { font-size: 1.15rem; font-weight: 800; margin-bottom: .15rem; }
    .wtp-hero .h-win { font-size: 1.5rem; font-weight: 800; letter-spacing: .01em; }
    .wtp-hero .h-by { font-size: .78rem; opacity: .85; margin-top: .1rem; }
    .wtp-hero .h-best { display: flex; flex-wrap: wrap; gap: .3rem .9rem; margin-top: .55rem; font-size: .78rem; opacity: .95; }
    .wtp-hero .h-best b { font-weight: 800; }
    .va-type { display: inline-block; vertical-align: middle; font-size: .58rem; font-weight: 800; letter-spacing: .05em; text-transform: uppercase;
        padding: .1rem .4rem; border-radius: 999px; background: #fef3c7; color: #92400e; margin-left: .3rem; }
    html.dark .va-type { background: #3b2f0e; color: #fcd34d; }
    .va-group-sub { font-size: .76rem; color: var(--color-gray-500); margin: -.3rem 0 .5rem; line-height: 1.45; }

    /* THE REPORT, FULL SCREEN when it lands: nothing else on the page —
       not the tabs, not the wizard — until the ✕. The same report is then
       left on the page underneath. */
    .va-view { position: fixed; inset: 0; z-index: 90; background: var(--color-gray-50); overflow-y: auto; -webkit-overflow-scrolling: touch;
        opacity: 0; transform: translateY(12px); transition: opacity .28s cubic-bezier(.22,1,.36,1), transform .28s cubic-bezier(.22,1,.36,1); }
    .va-view.is-on { opacity: 1; transform: none; }
    .va-view-bar { position: sticky; top: 0; z-index: 2; display: flex; align-items: center; gap: .6rem; padding: .7rem .9rem;
        padding-top: max(.7rem, env(safe-area-inset-top)); background: rgb(250 250 248 / .92); backdrop-filter: blur(8px); -webkit-backdrop-filter: blur(8px);
        border-bottom: 1px solid var(--color-gray-200); }
    .va-view-bar b { flex: 1 1 auto; min-width: 0; font-size: .95rem; color: var(--color-gray-900); overflow: hidden; text-overflow: ellipsis; white-space: nowrap; }
    .va-view-x { flex: none; width: 2.2rem; height: 2.2rem; border-radius: 999px; display: inline-flex; align-items: center; justify-content: center;
        background: var(--color-white); border: 1px solid var(--color-gray-200); color: var(--color-gray-700); font-size: 1rem; cursor: pointer; }
    .va-view-x:hover { background: var(--color-gray-100); }
    .va-view-body { max-width: 42rem; margin: 0 auto; padding: 1rem 1rem calc(2rem + env(safe-area-inset-bottom)); }
    html.va-view-lock { overflow: hidden; }
    html.dark .va-view { background: #0d110a; }
    html.dark .va-view-bar { background: rgb(13 17 10 / .92); border-color: #2b3a1c; }
    html.dark .va-view-bar b { color: #e8efe1; }
    html.dark .va-view-x { background: #151b12; border-color: #2b3a1c; color: #d5e3c5; }
    @media (prefers-reduced-motion: reduce) { .va-view { transition: none; transform: none; } }
    .wtp-hero .h-why { font-size: .84rem; opacity: .92; line-height: 1.55; margin-top: .45rem; }
    .wtp-chips { display: flex; flex-wrap: wrap; gap: .35rem; margin-top: .6rem; }
    .wtp-chip { font-size: .68rem; font-weight: 700; padding: .18rem .55rem; border-radius: 999px;
        background: rgb(255 255 255 / .18); }

    .wtp-card { border-radius: 1rem; border: 1px solid var(--color-gray-200); background: var(--color-white);
        padding: 1rem 1.1rem; }
    .wtp-card h3 { font-weight: 800; font-size: .92rem; color: var(--color-gray-900); margin-bottom: .6rem; }

    /* What the farmer said matters, worn as an ordered strip. */
    .va-order { display: flex; flex-wrap: wrap; gap: .35rem; }
    .va-order span { font-size: .72rem; font-weight: 700; padding: .25rem .6rem; border-radius: 999px;
        background: var(--color-brand-50); color: var(--color-brand-800); border: 1px solid var(--color-brand-200); }
    .va-order span i { font-style: normal; opacity: .65; margin-left: .25rem; }

    /* One variety: name, breeder, the four bars and the overall. */
    .va-rec { padding: .8rem 0; border-bottom: 1px solid var(--color-gray-100);
        opacity: 0; transform: translateY(6px); transition: all .45s cubic-bezier(.22,1,.36,1); }
    .va-rec:last-child { border-bottom: 0; padding-bottom: .2rem; }
    .wtp-report.is-drawn .va-rec { opacity: 1; transform: none; }
    .va-rec-top { display: flex; align-items: center; gap: .5rem; }
    .wp-rank { flex: none; width: 1.5rem; height: 1.5rem; border-radius: 999px; display: inline-flex;
        align-items: center; justify-content: center; font-size: .74rem; font-weight: 800;
        background: var(--color-brand-100); color: var(--color-brand-800); }
    .va-rec-top .v-name { min-width: 0; flex: 1 1 auto; }
    .va-rec-top .v-name b { display: block; font-size: .92rem; color: var(--color-gray-900); }
    .va-rec-top .v-name small { display: block; font-size: .7rem; color: var(--color-gray-400); }
    .va-overall { flex: none; text-align: center; line-height: 1; }
    .va-overall b { display: block; font-size: 1.15rem; font-weight: 800; color: var(--color-brand-700); }
    .va-overall small { font-size: .58rem; font-weight: 800; letter-spacing: .05em; text-transform: uppercase; color: var(--color-gray-400); }
    .va-facts { display: flex; flex-wrap: wrap; gap: .3rem; margin: .45rem 0 .5rem; }
    .va-fact { font-size: .66rem; font-weight: 700; padding: .14rem .5rem; border-radius: 999px;
        background: var(--color-gray-100); color: var(--color-gray-600); }
    .va-bars { display: grid; grid-template-columns: repeat(2, 1fr); gap: .35rem .8rem; }
    @media (min-width: 640px) { .va-bars { grid-template-columns: repeat(4, 1fr); } }
    .va-bar small { display: flex; justify-content: space-between; font-size: .62rem; font-weight: 700; color: var(--color-gray-500); }
    .va-bar small i { font-style: normal; color: var(--color-gray-700); }
    .wp-track { height: 6px; border-radius: 999px; background: var(--color-gray-100); margin-top: .2rem; overflow: hidden; }
    .wp-fill { display: block; height: 100%; border-radius: 999px; background: var(--color-brand-500);
        transform-origin: left; transform: scaleX(0); transition: transform .7s cubic-bezier(.22,1,.36,1); }
    .wp-fill.is-top { background: var(--color-brand-700); }
    .wtp-report.is-drawn .wp-fill { transform: scaleX(1); }
    .va-pm { display: grid; gap: .35rem; margin-top: .55rem; }
    @media (min-width: 640px) { .va-pm { grid-template-columns: 1fr 1fr; } }
    .va-pm ul { font-size: .76rem; line-height: 1.5; padding-left: 1rem; }
    .va-pm .is-plus li { list-style: '✓ '; color: #2d5016; }
    .va-pm .is-minus li { list-style: '– '; color: #92400e; }
    .wp-why { font-size: .8rem; color: var(--color-gray-600); line-height: 1.55; margin-top: .45rem; }
    .va-src { font-size: .68rem; color: var(--color-gray-400); margin-top: .3rem; }
    html.dark .va-rec { border-color: #222b1a; }
    html.dark .va-rec-top .v-name b { color: #e8efe1; }
    html.dark .va-fact { background: #222b1a; color: #93a684; }
    html.dark .wp-track { background: #222b1a; }
    html.dark .wp-why { color: #b7c2ad; }
    html.dark .va-bar small i { color: #d5e3c5; }
    html.dark .va-pm .is-plus li { color: #a5c97e; }
    html.dark .va-pm .is-minus li { color: #e0b95c; }
    html.dark .va-order span { background: #22301a; color: #cfe6b8; border-color: #3d5226; }

    .wtp-win-row { display: block; padding: .6rem .7rem; border-radius: .7rem; margin-bottom: .45rem;
        font-size: .84rem; line-height: 1.55; border: 1px solid; }
    .wtp-win-row b { margin-right: .3rem; }
    .wtp-win-row.is-note { background: #f7fbf2; border-color: #cfe3b8; color: #2d5016; }
    .wtp-win-row.is-new { background: #eff6ff; border-color: #bfdbfe; color: #1e3a8a; }
    .wtp-win-row.is-no { background: #fef2f2; border-color: #fecaca; color: #7f1d1d; }
    html.dark .wtp-win-row.is-note { background: #1c2913; border-color: #2b3a1c; color: #cfe6b8; }
    html.dark .wtp-win-row.is-new { background: #14203a; border-color: #1e3a8a; color: #bfdbfe; }
    html.dark .wtp-win-row.is-no { background: #2a1414; border-color: #4c1d1d; color: #fca5a5; }

    .va-cond { display: grid; gap: .5rem; }
    @media (min-width: 640px) { .va-cond { grid-template-columns: 1fr 1fr; } }
    .va-cond-box { border-radius: .8rem; padding: .65rem .75rem; background: var(--color-gray-50); border: 1px solid var(--color-gray-100); }
    .va-cond-box b { display: block; font-size: .72rem; font-weight: 800; letter-spacing: .04em; text-transform: uppercase; color: var(--color-gray-500); margin-bottom: .2rem; }
    .va-cond-box p { font-size: .8rem; line-height: 1.5; color: var(--color-gray-700); }
    .va-cond-box.is-wide { grid-column: 1 / -1; }
    .va-cond-box ul { font-size: .8rem; line-height: 1.5; color: #7f1d1d; padding-left: 1rem; }
    .va-cond-box ul li { list-style: disc; }
    html.dark .va-cond-box { background: #10150c; border-color: #222b1a; }
    html.dark .va-cond-box p { color: #d5e3c5; }
    html.dark .va-cond-box ul { color: #fca5a5; }

    .va-links { display: grid; gap: .3rem; }
    .va-link { display: flex; align-items: center; gap: .5rem; font-size: .78rem; color: var(--color-brand-700);
        text-decoration: none; padding: .35rem .5rem; border-radius: .6rem; min-width: 0; }
    .va-link:hover { background: var(--color-brand-50); }
    .va-link .l-t { min-width: 0; flex: 1 1 auto; overflow: hidden; text-overflow: ellipsis; white-space: nowrap; }
    .va-link .l-h { flex: none; font-size: .66rem; color: var(--color-gray-400); max-width: 9rem; overflow: hidden; text-overflow: ellipsis; white-space: nowrap; }
    .va-nosearch { font-size: .78rem; color: #92400e; background: #fffbeb; border: 1px solid #fde68a; border-radius: .7rem; padding: .55rem .7rem; }
    html.dark .va-link { color: #a5c97e; }
    html.dark .va-link:hover { background: #22301a; }
    html.dark .va-nosearch { background: #2a2413; border-color: #4a3d16; color: #e0c26a; }

    .wtp-gap { font-size: .78rem; color: var(--color-gray-500); line-height: 1.55; }
    .wtp-gap li { list-style: disc; margin-left: 1.1rem; }
    .wtp-fine { font-size: .72rem; color: var(--color-gray-400); line-height: 1.55; }
    .wtp-plain { font-size: .875rem; line-height: 1.65; color: var(--color-gray-700); }
    .va-mgmt li { list-style: none; position: relative; padding-left: 1.4rem; font-size: .82rem; line-height: 1.5; color: var(--color-gray-700); margin-bottom: .3rem; }
    .va-mgmt li::before { content: '🌱'; position: absolute; left: 0; top: 0; font-size: .8rem; }
    html.dark .wtp-plain, html.dark .va-mgmt li { color: #d5e3c5; }

    .wtp-acts { display: grid; gap: .5rem; }
    @media (min-width: 640px) { .wtp-acts { grid-template-columns: 1fr 1fr; } }

    .wtp-saved { display: flex; align-items: center; gap: .7rem; width: 100%; text-align: left;
        padding: .8rem .9rem; border-bottom: 1px solid var(--color-gray-100); cursor: pointer; }
    .wtp-saved:hover { background: var(--color-gray-50); }
    .wtp-saved b { display: block; font-size: .88rem; color: var(--color-gray-900); }
    .wtp-saved small { color: var(--color-gray-400); font-size: .72rem; }

    .wtp-anee-face { width: 1.15rem; height: 1.15rem; border-radius: 999px; object-fit: cover; }

    html.dark .wtp-tab { background: #151b12; border-color: #2b3a1c; color: #93a684; }
    html.dark .wtp-tab.is-on { background: #4a7c2a; border-color: #4a7c2a; color: #fff; }
    html.dark .wtp-quote { background: linear-gradient(115deg, #1c2913, #22301a); border-color: #2b3a1c; }
    html.dark .wtp-quote b { color: #cfe6b8; }
    html.dark .q-title { color: #cfe6b8; }
    html.dark .q-hint, html.dark .q-c { color: #a8bd93; }
    html.dark .q-card { background: rgb(255 255 255 / .05); border-color: #2b3a1c; color: #a8bd93; }
    html.dark .wtp-q { color: #e8efe1; }
    .wp-qh { font-size: .84rem; font-weight: 800; color: var(--color-gray-800); margin-bottom: .45rem; }
    .wp-qh small { display: block; font-weight: 500; font-size: .7rem; color: var(--color-gray-400); }
    html.dark .wp-qh { color: #e8efe1; }
    .wp-loc-country { margin-bottom: .8rem; }
    .wp-loc-country .form-label { margin-bottom: .3rem; }
    html.dark .wtp-choice, html.dark .wtp-prob { background: #151b12; border-color: #2b3a1c; color: #d5e3c5; }
    html.dark .wtp-choice.is-on { background: #22301a; border-color: #6b9f3d; color: #cfe6b8; }
    html.dark .wtp-prob.is-on { background: #22301a; border-color: #6b9f3d; }
    html.dark .wtp-card { background: #151b12; border-color: #2b3a1c; }
    html.dark .wtp-card h3 { color: #e8efe1; }
    html.dark .wtp-saved { border-color: #222b1a; }
    html.dark .wtp-saved:hover { background: #161e10; }
    html.dark .wtp-saved b { color: #e8efe1; }

    @media (max-width: 639px) {
        .wtp-hero { padding: .9rem 1rem; }
        .wtp-hero .h-win { font-size: 1.15rem; }
        .wtp-card { padding: .8rem .85rem; }
    }

    @media (prefers-reduced-motion: reduce) {
        .wtp-step.is-on { animation: none; }
        .wtp-run { animation: none; }
        .va-rec, .wp-fill, .wtp-dot { transition: none; transform: none; opacity: 1; }
        .q-body, .q-c, .q-hint, .wtp-prob, .va-row, .crop-tag, .crop-row { transition: none; }
        .va-chip { animation: none; }
    }
</style>

<div class="max-w-2xl mx-auto">
    <div class="wtp-tabs" role="tablist">
        <button type="button" class="wtp-tab is-on" id="vaTabGen">Generate</button>
        <button type="button" class="wtp-tab" id="vaTabSaved">Saved</button>
    </div>

    <div id="vaGen">
        <div class="wtp-quote" id="vaQuote" hidden>
            <button type="button" class="q-head" id="vaQuoteHead" aria-expanded="true">
                <span class="q-ico">🔬</span>
                <span class="q-title">Before you run one</span>
                <span class="q-hint" id="vaQuoteHint"></span>
                <svg class="q-c" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.4"><path stroke-linecap="round" stroke-linejoin="round" d="M6 9l6 6 6-6"/></svg>
            </button>
            <div class="q-body">
                <div class="q-body-in">
                    <div class="q-card" id="vaQuoteCost"></div>
                    <div class="q-card">Anee <b>analyzes deeply</b> for this one — the newest {{ \App\Support\Region::ph() ? 'Philippine' : \App\Support\Region::name() }} releases and registrations, hybrids from the top seed companies, trial yields, resistance ratings and days to maturity — then reads every variety against your soil, its troubles and the coming weather, and ranks them by what YOU said matters most.</div>
                </div>
            </div>
        </div>

        <div class="card p-5 wtp-wiz" id="vaWiz">
            {{-- Step 1: the place --}}
            <section class="wtp-step is-on" data-step="0">
                <p class="wtp-q">Where is the field?</p>
                {{-- The field's country: the farmer's own unless they say
                     otherwise. It decides the address words, the seasons on
                     offer, and whose seed houses, trials and agencies the
                     research reads. --}}
                <div class="wp-loc-country">
                    <label class="form-label">Country of the field</label>
                    @include('partials.country-pick', ['id' => 'vaCountry', 'name' => 'country', 'value' => \App\Support\Region::code()])
                </div>
                <p class="wtp-sub" id="vaLocSub">{{ \App\Support\Region::ph() ? 'Town and province' : ((\App\Support\Region::address()['city']['label'] ?? 'City') . ' and ' . strtolower(\App\Support\Region::address()['region']['label'] ?? 'state')) }} is enough — the climate and the trial results differ by region.</p>
                <input type="text" id="vaLocation" class="form-input" maxlength="160" placeholder="{{ \App\Support\Region::get('exampleLocation') }}">
            </section>
            {{-- Step 2: when it will be planted -- the year, and the season
                 with the months it usually spans where the field is. --}}
            <section class="wtp-step" data-step="1">
                <p class="wtp-q">When will you plant it?</p>
                <p class="wtp-sub">A variety bred for the wet season is not the dry season's — the research reads the outlook for the season you plan.</p>
                <p class="wp-qh">The year</p>
                <div class="wtp-choices is-two" id="vaYears"></div>
                <p class="wp-qh mt-4">The season</p>
                <div class="wtp-choices" id="vaSeasons"></div>
            </section>
            {{-- Step 3: the soil, and the lay of the land --}}
            <section class="wtp-step" data-step="2">
                <p class="wtp-q">What is the soil like?</p>
                <p class="wtp-sub">As your hands know it — no test needed.</p>
                <div class="wtp-choices" id="vaSoils"></div>
                <p class="wp-qh mt-4">And the land? <small>lowland, upland or highland — a variety is bred for one of them</small></p>
                <div class="wtp-choices" id="vaElevations"></div>
            </section>
            {{-- Step 4: the crop --}}
            <section class="wtp-step" data-step="3">
                <p class="wtp-q">Which crop?</p>
                <p class="wtp-sub">The same catalogue your lots choose from.</p>
                <button type="button" class="crop-tag" id="vaCropBtn">
                    <span class="crop-tag-e" id="vaCropIcon">🌱</span>
                    <span class="crop-tag-t is-none" id="vaCropNow">Choose the crop</span>
                    <svg class="crop-tag-c" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.4"><path stroke-linecap="round" stroke-linejoin="round" d="M6 9l6 6 6-6"/></svg>
                </button>
            </section>
            {{-- Step 5: the varieties --}}
            <section class="wtp-step" data-step="4">
                <p class="wtp-q">Which varieties are you weighing?</p>
                <p class="wtp-sub">Type each as it is sold and add it — up to eight. Leave the list empty and Anee picks the top-yielding ones for your ground herself.</p>
                <div class="va-add">
                    <input type="text" id="vaVarietyIn" class="form-input" maxlength="60" placeholder="e.g. {{ \App\Support\Region::ph() ? 'NSIC Rc222, NK6414' : 'Pioneer P1197, DKC64-34' }}" autocomplete="off">
                    <button type="button" class="btn btn-primary" id="vaVarietyAdd">Add</button>
                </div>
                <div class="va-chips" id="vaChips"></div>
                <div class="va-anee" id="vaAneePicks"><span id="vaAneeFaceSlot">🤖</span><span>No varieties yet — <b>Anee will choose the top-yielding released varieties</b> for your crop and ground, and compare those.</span></div>
            </section>
            {{-- Step 6: the priorities --}}
            <section class="wtp-step" data-step="5">
                <p class="wtp-q">What matters most to you?</p>
                <p class="wtp-sub">Put them in order — the first weighs 40% of the ranking, then 30%, 20%, 10%.</p>
                <div class="va-rank" id="vaRank"></div>
            </section>
            {{-- Step 7: the troubles --}}
            <section class="wtp-step" data-step="6">
                <p class="wtp-q">What does this ground struggle with?</p>
                <p class="wtp-sub">Tick what you have seen — each one moves the scores.</p>
                <div class="wtp-probs" id="vaProbs"></div>
                <label class="form-label mt-4" for="vaNotes">Anything else worth knowing? <span class="text-gray-400 font-normal">(optional)</span></label>
                <textarea id="vaNotes" class="form-textarea" rows="2" maxlength="400" placeholder="e.g. we transplant late; the buyer wants long grain"></textarea>
            </section>
            {{-- Step 8: the decision --}}
            <section class="wtp-step" data-step="7">
                <p class="wtp-q">Ready to run it?</p>
                <p class="wtp-sub" id="vaReview"></p>
                <button type="button" class="wtp-run" id="vaRun">
                    <svg class="w-5 h-5" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" d="M21 21l-4.35-4.35M17 11a6 6 0 11-12 0 6 6 0 0112 0z"/></svg>
                    <span id="vaRunSays">Run the research</span>
                </button>
                <p class="text-xs text-gray-400 mt-2 text-center" id="vaRunFine"></p>
            </section>

            {{-- The wait: Anee's face at work, shared by every AI run. --}}
            @include('sm.partials.anee-wait')

            <div class="wtp-dots" id="vaDots"></div>
            <div class="wtp-nav" id="vaNav">
                <button type="button" class="btn btn-white flex-1" id="vaBack" disabled>Back</button>
                <button type="button" class="btn btn-primary flex-1" id="vaNext">Next</button>
            </div>
        </div>

        <div class="wtp-report mt-4" id="vaReport" hidden></div>
    </div>

    {{-- The report, full screen, when one lands or a saved one is opened. --}}
    <div class="va-view" id="vaView" hidden role="dialog" aria-modal="true" aria-label="Variety research">
        <div class="va-view-bar">
            <b id="vaViewTitle">Variety research</b>
            <button type="button" class="va-view-x" id="vaViewX" aria-label="Close">✕</button>
        </div>
        <div class="va-view-body"><div class="wtp-report" id="vaViewReport"></div></div>
    </div>

    <div id="vaSavedPane" class="hidden">
        <div class="card !p-0 overflow-hidden">
            <div id="vaSavedList"></div>
            <div id="vaSavedEmpty" class="hidden text-center py-10">
                <p class="font-bold text-gray-900">Nothing saved yet</p>
                <p class="text-sm text-gray-400">Every finished research lands here by itself.</p>
            </div>
        </div>
        <div class="wtp-report mt-4" id="vaSavedReport" hidden></div>
    </div>
</div>

{{-- The crop sheet sits here, not on the 'sheets' stack: that stack prints
     after this script has run, and the script wires the sheet by id. --}}
{{-- The crop catalogue, searchable — the same sheet the lot form and the sisters use. --}}
<div class="sheet hidden" id="vaCropSheet" style="--sheet-width:30rem">
    <div class="sheet-handle"></div>
    <div class="sheet-header">
        <h3 class="sheet-title">Choose a crop</h3>
        <button type="button" data-sheet-close class="btn-ghost p-2 rounded-full" aria-label="Close">✕</button>
    </div>
    <div class="sheet-body">
        <div class="crop-search">
            <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path stroke-linecap="round" stroke-linejoin="round" d="M21 21l-4.35-4.35M17 11a6 6 0 11-12 0 6 6 0 0112 0z"/></svg>
            <input type="text" id="vaCropSearch" class="form-input" autocomplete="off"
                   placeholder="{{ \App\Support\Region::t('cropSearch') }}">
            <button type="button" class="crop-search-x hidden" id="vaCropSearchX" aria-label="Clear">✕</button>
        </div>
        <div id="vaCropList"></div>
        <p class="crop-none hidden" id="vaCropNone">Nothing matches that. Try the local name, or pick “Vegetables — mixed”.</p>
    </div>
</div>

<script>
(() => {
    const $id = (x) => document.getElementById(x);
    const esc = (s) => String(s ?? '').replace(/[&<>"']/g, (c) => ({ '&': '&amp;', '<': '&lt;', '>': '&gt;', '"': '&quot;', "'": '&#39;' }[c]));
    const U = {
        options: '{{ route('vary.options') }}',
        generate: '{{ route('vary.generate') }}',
        list: '{{ route('vary.list') }}',
        one: (id) => '{{ url('/app/variety-research/one') }}/' + id,
        job: (id) => '{{ url('/app/variety-research/job') }}/' + id,
        del: (id) => '{{ url('/app/variety-research') }}/' + id,
        anee: '{{ route('ai.index') }}',
    };
    const VA_META_URL = '{{ route('vary.meta') }}';

    let OPT = null;
    const state = { location: '', country: '', year: null, season: null, soil: null, elevation: null, crop: '', varieties: [], priorities: ['yield', 'protection', 'survival', 'quickness'], problems: [], notes: '' };
    const RULES = () => (window.ANEE_REGION_RULES || {});
    const rulesFor = (code) => RULES()[code] || RULES()['*'] || {};
    // The seasons on offer are the FIELD's country's.
    const seasonsFor = (code) => { const r = rulesFor(code); return (r.seasons && Object.keys(r.seasons).length) ? r.seasons : ((OPT && OPT.seasons) || {}); };
    const seasonSaid = (season, year, country) => {
        const y = Number(year) || 0;
        const label = seasonsFor(country || state.country || (OPT && OPT.country))[season] || ((OPT && OPT.seasons && OPT.seasons[season]) || '');
        const crosses = season === 'dry' || season === 'winter';
        return crosses && y ? `${label} ${y}–${String(y + 1).slice(-2)}` : `${label} ${y || ''}`.trim();
    };
    let step = 0;
    const STEPS = 8;

    async function boot() {
        try {
            const res = await api(U.options, { method: 'GET' });
            OPT = res.data;
            paintOptions();
        } catch (err) { toast(err.message, 'error'); }
    }

    function paintOptions() {
        state.country = state.country || OPT.country || ((window.ANEE_REGION || {}).code) || 'PH';
        $id('vaYears').innerHTML = (OPT.years || []).map((y) => `
            <button type="button" class="wtp-choice${state.year === y ? ' is-on' : ''}" data-year="${y}"><span class="c-e">🗓️</span><span>${y}${y === OPT.years[0] ? '<small>This year</small>' : ''}</span></button>`).join('');
        const seasonIcons = { dry: '☀️', wet: '🌧️', third: '🌗', spring: '🌱', summer: '☀️', autumn: '🍂', winter: '❄️' };
        /* Each season card says the months it usually spans where the
           field is; the dry season begins at a year's end and runs into
           the next, and the card says so with both years. */
        const seasonSubs = (y) => ({
            dry: `Early December ${y} to May ${y + 1} in most lowland regions — planting into ${y + 1} is part of it`,
            wet: `Roughly June to October ${y} in most lowland regions — planting as the rains set in`,
            third: `After the dry-season harvest, before the rains — roughly March to May ${y}, where water can be assured`,
            spring: `Roughly March to May ${y} in the northern hemisphere — the research places it for your location`,
            summer: `Roughly June to August ${y} in the northern hemisphere`,
            autumn: `Roughly September to November ${y} in the northern hemisphere`,
            winter: `December ${y} to February ${y + 1} in the northern hemisphere — a cool-season or protected planting`,
        });
        const paintSeasons = () => {
            const y = Number(state.year || (OPT.years || [new Date().getFullYear()])[0]);
            const subs = seasonSubs(y);
            $id('vaSeasons').innerHTML = Object.entries(seasonsFor(state.country)).map(([k, label]) => `
                <button type="button" class="wtp-choice${state.season === k ? ' is-on' : ''}" data-season="${k}"><span class="c-e">${seasonIcons[k] || '🌱'}</span><span>${esc(label)}${(k === 'dry' || k === 'winter') ? ' ' + y + '–' + String(y + 1).slice(-2) : ''}<small>${esc(subs[k] || '')}</small></span></button>`).join('');
        };
        paintSeasons();
        window.__vaPaintSeasons = paintSeasons;
        const landIcons = { lowland: '🌾', upland: '🌄', highland: '🌫️' };
        $id('vaElevations').innerHTML = Object.entries(OPT.elevations || {}).map(([k, label]) => `
            <button type="button" class="wtp-choice${state.elevation === k ? ' is-on' : ''}" data-elevation="${k}"><span class="c-e">${landIcons[k] || '⛰️'}</span><span>${esc(String(label).split(' — ')[0])}${String(label).includes(' — ') ? `<small>${esc(String(label).split(' — ')[1])}</small>` : ''}</span></button>`).join('');
        const soilIcons = { clay: '🧱', loam: '🟤', sandy: '🏖️', silty: '🌊', rocky: '⛰️', unsure: '🤷' };
        $id('vaSoils').innerHTML = Object.entries(OPT.soils).map(([k, label]) => `
            <button type="button" class="wtp-choice" data-soil="${k}"><span class="c-e">${soilIcons[k] || '🟫'}</span><span>${esc(String(label).split(' — ')[0])}${String(label).includes(' — ') ? `<small>${esc(String(label).split(' — ').slice(1).join(' — '))}</small>` : ''}</span></button>`).join('');
        const groups = {};
        OPT.crops.forEach((c) => { (groups[c.group] = groups[c.group] || []).push(c); });
        $id('vaCropList').innerHTML = Object.entries(groups).map(([g, list]) => `
            <div class="crop-group" data-crop-group>
                <p class="crop-group-h">${esc(g)}</p>
                ${list.map((c) => `
                    <button type="button" class="crop-row" data-crop="${esc(c.key)}" data-find="${esc((c.label + ' ' + g).toLowerCase())}">
                        <span class="crop-row-e">${esc(c.icon)}</span>
                        <span class="crop-row-t">
                            <b>${esc(c.label)}</b>
                            <small>${c.perennial ? 'Tree crop — read by its age' : (c.maturity ? c.maturity + ' days to harvest' : '')}</small>
                        </span>
                    </button>`).join('')}
            </div>`).join('');
        $id('vaProbs').innerHTML = Object.entries(OPT.problems).map(([k, label]) => `
            <label class="wtp-prob" data-prob="${k}"><input type="checkbox" value="${k}"><span>${esc(label)}</span></label>`).join('');
        $id('vaDots').innerHTML = Array.from({ length: STEPS }, (_, i) => `<span class="wtp-dot${i === 0 ? ' is-on' : ''}"></span>`).join('');
        if (OPT.aneeFace) $id('vaAneeFaceSlot').innerHTML = `<img src="${esc(OPT.aneeFace)}" alt="">`;
        paintRank();
        paintChips();
        paintQuote();
    }

    const QUOTE_MIN_KEY = 'anee-vary-quote-min';
    let quoteMin = false;
    try { quoteMin = localStorage.getItem(QUOTE_MIN_KEY) === '1'; } catch (_) { /* opens full */ }

    function paintQuote() {
        const q = $id('vaQuote');
        if (!OPT) return;
        if (!OPT.canUse) {
            $id('vaQuoteCost').innerHTML = esc(OPT.whyNot || 'The analysis is not available right now.');
            $id('vaQuoteHint').textContent = '';
            q.classList.remove('is-min');
            q.hidden = false;
            return;
        }
        if (!OPT.quote) { q.hidden = true; return; }
        q.classList.toggle('is-min', quoteMin);
        $id('vaQuoteHead').setAttribute('aria-expanded', quoteMin ? 'false' : 'true');
        $id('vaQuoteCost').innerHTML = `This research spends <b>${OPT.quote} credits</b> (a deep analysis, which is why it costs more than its sisters), and you have <b>${OPT.unlimited ? '∞' : Number(OPT.balance).toLocaleString()}</b>. Nothing is charged until you press Run.`;
        $id('vaQuoteHint').textContent = `${OPT.quote} credits`;
        q.hidden = false;
    }
    $id('vaQuoteHead').addEventListener('click', () => {
        quoteMin = !quoteMin;
        try { localStorage.setItem(QUOTE_MIN_KEY, quoteMin ? '1' : '0'); } catch (_) { /* not remembered */ }
        paintQuote();
    });

    /* ---------------- the walk ---------------- */
    function show(n, backwards) {
        step = Math.max(0, Math.min(STEPS - 1, n));
        document.querySelectorAll('#vaWiz .wtp-step').forEach((s) => {
            const on = Number(s.getAttribute('data-step')) === step;
            s.classList.toggle('is-on', on);
            s.classList.toggle('is-back', on && !!backwards);
        });
        document.querySelectorAll('#vaDots .wtp-dot').forEach((d, i) => d.classList.toggle('is-on', i <= step));
        $id('vaBack').disabled = step === 0;
        $id('vaNext').style.display = step === STEPS - 1 ? 'none' : '';
        // The field is focused on a desk, where a cursor is a courtesy; on a
        // phone the keypad would land on the step before it is read.
        if (step === 4 && window.matchMedia('(min-width: 640px)').matches) setTimeout(() => $id('vaVarietyIn')?.focus({ preventScroll: true }), 300);
        if (step === STEPS - 1) review();
    }

    function stepReady() {
        switch (step) {
            case 0: state.location = $id('vaLocation').value.trim();
                return !!state.location || (toast('Say where the field is.', 'error'), false);
            case 1: if (!state.year) { toast('Pick the year first.', 'error'); return false; }
                if (!state.season || !seasonsFor(state.country)[state.season]) { toast('Pick the season.', 'error'); return false; }
                return true;
            case 2: return !!state.soil || (toast('Pick the soil that sounds most like yours.', 'error'), false);
            case 3: return !!state.crop || (toast('Choose the crop.', 'error'), false);
            case 4: addVariety(); return true;
            case 6: state.problems = [...document.querySelectorAll('#vaProbs input:checked')].map((i) => i.value);
                state.notes = $id('vaNotes').value.trim(); return true;
            default: return true;
        }
    }

    function review() {
        const crop = OPT.crops.find((c) => c.key === state.crop) || {};
        const order = state.priorities.map((k, i) => `${i + 1}. ${OPT.priorities[k]?.label || k}`).join(' · ');
        $id('vaReview').innerHTML = `${esc(crop.icon || '🌱')} <b>${esc(crop.label || '')}</b> · 📍 ${esc(state.location)}${state.country && state.country !== (OPT.country || '') ? ' · ' + esc(rulesFor(state.country).name || state.country) : ''}`
            + ` · ${esc(seasonSaid(state.season, state.year))}`
            + `<br><span class="text-xs">${esc(OPT.soils[state.soil] || '')}${state.elevation ? ' · ' + esc(String((OPT.elevations || {})[state.elevation] || state.elevation).split(' — ')[0]) : ''}`
            + ` · ${state.varieties.length ? esc(state.varieties.join(', ')) : 'Anee picks the varieties'}`
            + (state.problems.length ? ` · ${state.problems.length} trouble${state.problems.length === 1 ? '' : 's'} considered` : '') + '</span>'
            + `<br><span class="text-xs">${esc(order)}</span>`;
        $id('vaRunSays').textContent = OPT.canUse && OPT.quote ? `Run the research (${OPT.quote} credits)` : 'Run the research';
        $id('vaRunFine').textContent = OPT.canUse
            ? 'Anee analyzes this one deeply. Charged to the same AI credits your questions use — it shows in your subscription’s credit log.'
            : (OPT.whyNot || '');
        $id('vaRun').disabled = !OPT.canUse;
    }

    $id('vaNext').addEventListener('click', () => { if (stepReady()) show(step + 1); });
    $id('vaBack').addEventListener('click', () => show(step - 1, true));
    $id('vaYears').addEventListener('click', (e) => {
        const b = e.target.closest('[data-year]');
        if (!b) return;
        state.year = Number(b.getAttribute('data-year'));
        document.querySelectorAll('#vaYears .wtp-choice').forEach((c) => c.classList.toggle('is-on', c === b));
        // The season cards say their years, and the dry one runs into the next.
        window.__vaPaintSeasons?.();
    });
    $id('vaSeasons').addEventListener('click', (e) => {
        const b = e.target.closest('[data-season]');
        if (!b) return;
        if (!state.year) { toast('Pick the year first.', 'error'); return; }
        state.season = b.getAttribute('data-season');
        document.querySelectorAll('#vaSeasons .wtp-choice').forEach((c) => c.classList.toggle('is-on', c === b));
        setTimeout(() => show(2), 180);
    });
    $id('vaSoils').addEventListener('click', (e) => {
        const b = e.target.closest('[data-soil]');
        if (!b) return;
        state.soil = b.getAttribute('data-soil');
        document.querySelectorAll('#vaSoils .wtp-choice').forEach((c) => c.classList.toggle('is-on', c === b));
        // The land is optional; the step moves on once the soil is picked.
        setTimeout(() => show(3), 180);
    });
    $id('vaElevations').addEventListener('click', (e) => {
        const b = e.target.closest('[data-elevation]');
        if (!b) return;
        state.elevation = state.elevation === b.getAttribute('data-elevation') ? null : b.getAttribute('data-elevation');
        document.querySelectorAll('#vaElevations .wtp-choice').forEach((c) => c.classList.toggle('is-on', c.getAttribute('data-elevation') === state.elevation));
    });
    $id('vaCountry')?.addEventListener('country:change', (e) => {
        const code = e.detail && e.detail.code;
        const r = e.detail && e.detail.rules;
        if (!code || !r) return;
        state.country = code;
        const city = (r.address && r.address.city && r.address.city.label) || 'City';
        const region = (r.address && r.address.region && r.address.region.label) || 'State / Region';
        $id('vaLocSub').textContent = `${code === 'PH' ? 'Town and province' : city + ' and ' + region.toLowerCase()} is enough — the climate and the trial results differ by region.`;
        $id('vaLocation').placeholder = r.exampleLocation || '';
        // The seasons on offer follow the field's country; a season that is
        // not one of them is dropped and asked for again.
        if (!seasonsFor(code)[state.season]) state.season = null;
        window.__vaPaintSeasons?.();
    });
    /* Some troubles cannot share a field: a soil is acidic or alkaline,
       not both. Ticking one quietly unticks its opposite. */
    const PROB_FOES = { acidic: ['alkaline'], alkaline: ['acidic'], drought: ['floods'], floods: ['drought'] };
    $id('vaProbs').addEventListener('change', (e) => {
        const l = e.target.closest('.wtp-prob');
        if (l) l.classList.toggle('is-on', e.target.checked);
        if (!e.target.checked) return;
        (PROB_FOES[e.target.value] || []).forEach((k) => {
            const foe = document.querySelector(`#vaProbs input[value="${k}"]`);
            if (foe && foe.checked) { foe.checked = false; foe.closest('.wtp-prob')?.classList.remove('is-on'); }
        });
    });

    /* ---- the crop: the tag opens the sheet, the sheet fills the tag ---- */
    $id('vaCropBtn').addEventListener('click', () => {
        openSheet('vaCropSheet');
        if (window.matchMedia('(min-width: 640px)').matches) {
            setTimeout(() => $id('vaCropSearch')?.focus(), 280);
        }
    });
    $id('vaCropSheet').addEventListener('click', (e) => {
        const row = e.target.closest('.crop-row');
        if (!row) return;
        state.crop = row.getAttribute('data-crop');
        const c = OPT.crops.find((x) => x.key === state.crop) || {};
        $id('vaCropIcon').textContent = c.icon || '🌱';
        const now = $id('vaCropNow');
        now.textContent = c.label || 'Choose the crop';
        now.classList.remove('is-none');
        closeSheet('vaCropSheet');
        setTimeout(() => show(4), 220);
    });
    const cropSift = () => {
        const q = ($id('vaCropSearch').value || '').trim().toLowerCase();
        $id('vaCropSearchX').classList.toggle('hidden', !q);
        let shown = 0;
        document.querySelectorAll('#vaCropList [data-crop-group]').forEach((g) => {
            let left = 0;
            g.querySelectorAll('.crop-row').forEach((r) => {
                const hit = !q || (r.getAttribute('data-find') || '').includes(q);
                r.hidden = !hit;
                if (hit) left++;
            });
            g.hidden = left === 0;
            shown += left;
        });
        $id('vaCropNone').classList.toggle('hidden', shown > 0);
    };
    $id('vaCropSearch').addEventListener('input', cropSift);
    $id('vaCropSearchX').addEventListener('click', () => {
        $id('vaCropSearch').value = '';
        cropSift();
        $id('vaCropSearch').focus();
    });

    /* ---- the varieties: typed, added, worn as chips; empty means Anee picks ---- */
    function addVariety() {
        const inp = $id('vaVarietyIn');
        const v = inp.value.trim().replace(/\s{2,}/g, ' ');
        if (!v) return false;
        if (state.varieties.length >= 8) { toast('Eight is plenty for one comparison.', 'error'); return false; }
        if (state.varieties.some((x) => x.toLowerCase() === v.toLowerCase())) { toast('That one is already on the list.', 'error'); inp.value = ''; return false; }
        state.varieties.push(v);
        inp.value = '';
        paintChips();
        return true;
    }
    function paintChips() {
        $id('vaChips').innerHTML = state.varieties.map((v, i) => `
            <span class="va-chip">${esc(v)}<button type="button" data-drop="${i}" aria-label="Remove ${esc(v)}">✕</button></span>`).join('');
        $id('vaAneePicks').hidden = state.varieties.length > 0;
    }
    $id('vaVarietyAdd').addEventListener('click', () => { addVariety(); $id('vaVarietyIn').focus(); });
    $id('vaVarietyIn').addEventListener('keydown', (e) => {
        if (e.key === 'Enter' || e.key === ',') { e.preventDefault(); addVariety(); }
    });
    $id('vaChips').addEventListener('click', (e) => {
        const b = e.target.closest('[data-drop]');
        if (!b) return;
        state.varieties.splice(Number(b.getAttribute('data-drop')), 1);
        paintChips();
    });

    /* ---- the priorities: an order the arrows change, rows sliding ---- */
    function paintRank() {
        $id('vaRank').innerHTML = state.priorities.map((k, i) => {
            const p = OPT.priorities[k] || { label: k, sub: '', icon: '•' };
            return `<div class="va-row" data-key="${k}">
                <span class="r-n">${i + 1}</span>
                <span class="r-e">${p.icon}</span>
                <span class="r-t"><b>${esc(p.label)}</b><small>${esc(p.sub)}</small></span>
                <span class="r-w">${Math.round((OPT.weights[i] || 0) * 100)}%</span>
                <span class="r-btns">
                    <button type="button" data-move="-1" ${i === 0 ? 'disabled' : ''} aria-label="Move ${esc(p.label)} up"><svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.6"><path stroke-linecap="round" stroke-linejoin="round" d="M5 15l7-7 7 7"/></svg></button>
                    <button type="button" data-move="1" ${i === state.priorities.length - 1 ? 'disabled' : ''} aria-label="Move ${esc(p.label)} down"><svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.6"><path stroke-linecap="round" stroke-linejoin="round" d="M19 9l-7 7-7-7"/></svg></button>
                </span>
            </div>`;
        }).join('');
    }
    $id('vaRank').addEventListener('click', (e) => {
        const b = e.target.closest('[data-move]');
        if (!b || b.disabled) return;
        const row = b.closest('.va-row');
        const from = state.priorities.indexOf(row.dataset.key);
        const to = from + Number(b.getAttribute('data-move'));
        if (to < 0 || to >= state.priorities.length) return;
        // FLIP: remember where every row was, reorder, then let each slide
        // from its old place to its new one.
        const rows = [...$id('vaRank').children];
        const before = new Map(rows.map((r) => [r.dataset.key, r.getBoundingClientRect().top]));
        [state.priorities[from], state.priorities[to]] = [state.priorities[to], state.priorities[from]];
        paintRank();
        const after = [...$id('vaRank').children];
        after.forEach((r) => {
            const dy = (before.get(r.dataset.key) ?? 0) - r.getBoundingClientRect().top;
            if (!dy) return;
            r.classList.add('is-moving');
            r.style.transform = `translateY(${dy}px)`;
            requestAnimationFrame(() => requestAnimationFrame(() => { r.classList.remove('is-moving'); r.style.transform = ''; }));
        });
        const again = $id('vaRank').querySelector(`.va-row[data-key="${row.dataset.key}"] [data-move="${b.getAttribute('data-move')}"]`);
        if (again && !again.disabled) again.focus({ preventScroll: true });
    });

    /* ---------------- the run ---------------- */
    $id('vaRun').addEventListener('click', async () => {
        if (!stepReady()) return;
        const wiz = $id('vaWiz');
        wiz.querySelectorAll('.wtp-step, .wtp-nav, .wtp-dots').forEach((el) => el.style.display = 'none');
        window.aneeWait.show({ title: 'Anee is researching…', lines: ['Reading the newest ' + ((window.ANEE_REGION || {}).ph === false ? (window.ANEE_REGION.name + ' ') : 'Philippine ') + 'releases and trials…', 'Checking the hybrids from the top seed companies…', 'Reading resistance, tolerance and days to maturity…', 'Weighing each variety against your soil and the coming weather…', 'Ranking by what you said matters most…'], sub: 'A minute or two — this is a deep analysis.' });
        $id('vaReport').hidden = true;
        let landed = false;
        try {
            const res = await api(U.generate, { method: 'POST', body: {
                location: state.location, country: state.country, year: state.year, season: state.season,
                soil: state.soil, elevation: state.elevation, crop: state.crop,
                varieties: state.varieties, priorities: state.priorities,
                problems: state.problems, notes: state.notes,
            } });
            let data = res.data;
            if (data.pending) {
                // The shared poll: the bar, the clock and the hang check ride
                // along, and a poll that cannot reach the server is retried
                // rather than taken for the job failing.
                data = await window.aneeWait.poll({ id: data.id || res.data.id, job: U.job, phases: window.aneeWait.phases.research });
            }
            OPT.balance = data.balance;
            landed = true;
            const item = { report: data.report, params: data.params, charged: data.charged, savedId: data.savedId };
            // A slip in drawing must not strand the veil: the result is on
            // the shelf either way, and the wait still lifts.
            try { drawReport($id('vaReport'), item, 'fresh', true); openView(item, 'fresh'); }
            catch (drawErr) { console.error(drawErr); toast('The analysis is saved on the Saved tab, but this page could not draw it.', 'error'); }
            await window.aneeWait.done({ title: 'Done!', line: `${data.charged} credits used — saved to the shelf.` });
            toast(`Done — ${data.charged} credits used. Saved to the shelf.`);
        } catch (err) {
            toast(err.message, 'error');
        } finally {
            if (!landed) window.aneeWait.fail();
            wiz.querySelectorAll('.wtp-step, .wtp-nav, .wtp-dots').forEach((el) => el.style.display = '');
            show(step);
            if (landed) { wiz.hidden = true; $id('vaQuote').hidden = true; }
        }
    });

    /* The report full screen: the tabs and the wizard are out of sight
       until the ✕; the page's own copy of the report stays underneath. */
    let VIEW_MODE = null;   // 'fresh' right after a run, 'saved' from the shelf
    function openView(item, mode) {
        VIEW_MODE = mode || null;
        const view = $id('vaView');
        const crop = (OPT ? OPT.crops.find((c) => c.key === (item.params || {}).crop) : null) || {};
        $id('vaViewTitle').textContent = (crop.label ? crop.label + ' — ' : '') + 'variety research';
        const host = $id('vaViewReport');
        host.classList.remove('is-drawn');
        drawReport(host, item, mode, true);
        view.hidden = false;
        document.documentElement.classList.add('va-view-lock');
        view.scrollTop = 0;
        requestAnimationFrame(() => requestAnimationFrame(() => { view.classList.add('is-on'); host.classList.add('is-drawn'); }));
    }
    function closeView() {
        const view = $id('vaView');
        if (view.hidden) return;
        view.classList.remove('is-on');
        document.documentElement.classList.remove('va-view-lock');
        // A fresh result was drawn over a hidden wizard: closing it brings
        // the wizard back, or the Generate tab stands empty.
        const wasFresh = VIEW_MODE === 'fresh';
        VIEW_MODE = null;
        setTimeout(() => { view.hidden = true; $id('vaViewReport').innerHTML = ''; if (wasFresh) wizardBack(); }, 300);
    }
    $id('vaViewX').addEventListener('click', closeView);
    document.addEventListener('keydown', (e) => { if (e.key === 'Escape') closeView(); });

    function wizardBack() {
        $id('vaWiz').hidden = false;
        if (OPT && OPT.canUse && OPT.quote) $id('vaQuote').hidden = false;
        $id('vaReport').hidden = true;
        $id('vaReport').classList.remove('is-drawn');
        show(0);
        $id('vaWiz').scrollIntoView({ behavior: 'smooth', block: 'start' });
    }

    /* ---------------- the report, drawn ---------------- */
    // Google hands its grounding pages back through a redirect door; the
    // title is then the site itself, and the door is not worth reading out.
    const host = (u) => { try { const h = new URL(u).hostname.replace(/^www\./, ''); return /vertexaisearch\.cloud\.google\.com$/.test(h) ? 'via Google Search' : h; } catch (_) { return ''; } };
    function drawReport(hostEl, item, mode, quiet) {
        const r = item.report || {};
        const p = item.params || {};
        const sweep = (t) => String(t || '').replace(/:[a-z0-9_-]+:/gi, '').replace(/\s{2,}/g, ' ').trim();
        const top = r.topPick || {};
        const crop = (OPT ? OPT.crops.find((c) => c.key === p.crop) : null) || {};
        const order = Array.isArray(p.priorities) ? p.priorities : Object.keys(OPT ? OPT.priorities : {});
        const PR = (OPT && OPT.priorities) || {};
        const weightOf = (k) => (r.weights && r.weights[k] != null) ? Math.round(r.weights[k] * 100) : Math.round(((OPT && OPT.weights[order.indexOf(k)]) || 0) * 100);
        const topKey = order[0];
        const list = (xs) => (Array.isArray(xs) ? xs : []).filter((x) => x && String(x).trim());

        hostEl.innerHTML = `
            <div class="wtp-hero">
                <h2>${esc(crop.icon || '🌱')} ${esc(r.headline || ('Best ' + (crop.label || 'variety') + ' for your ground'))}</h2>
                <p class="h-win">${esc(top.variety || '')}</p>
                ${top.by ? `<p class="h-by">${esc(top.by)}</p>` : ''}
                <p class="h-why">${esc(sweep(top.why))}</p>
                ${(r.bestHybrid && r.bestInbred) ? `<p class="h-best"><span>Best hybrid: <b>${esc(r.bestHybrid)}</b></span><span>Best inbred: <b>${esc(r.bestInbred)}</b></span></p>` : ''}
                <div class="wtp-chips">
                    <span class="wtp-chip">📍 ${esc(p.location || '')}</span>
                    <span class="wtp-chip">${esc(crop.label || '')}</span>
                    <span class="wtp-chip">Confidence: ${esc(r.confidence || 'moderate')}</span>
                    ${r.searched ? '<span class="wtp-chip">🔎 Deep analysis</span>' : ''}
                    ${item.charged ? `<span class="wtp-chip">${item.charged} credits</span>` : ''}
                </div>
            </div>

            <div class="wtp-card">
                <h3>Ranked by what you said matters</h3>
                <div class="va-order">${order.map((k, i) => `<span>${i + 1}. ${esc(PR[k]?.label || k)}<i>${weightOf(k)}%</i></span>`).join('')}</div>
            </div>

            ${(() => {
                /* Hybrids and inbreds ranked apart: the two are bought,
                   priced and grown differently, and a farmer chooses
                   between them before choosing within them. One card when
                   only one kind came back. */
                const all = r.ranking || [];
                const hybrids = all.filter((x) => x.type === 'hybrid');
                const inbreds = all.filter((x) => x.type !== 'hybrid');
                const groups = (hybrids.length && inbreds.length)
                    ? [['Hybrids', 'Seed bought fresh each season — usually the higher yield, at a higher seed cost', hybrids], ['Inbred & open-pollinated', 'Seed you can keep and replant — the public {{ \App\Support\Region::ph() ? 'NSIC-registered' : 'registered' }} varieties', inbreds]]
                    : [['The comparison, best first', '', all]];
                let delay = 0;
                return groups.map(([title, sub, rows]) => `
                <div class="wtp-card">
                    <h3>${esc(title)}</h3>
                    ${sub ? `<p class="va-group-sub">${esc(sub)}</p>` : ''}
                    ${rows.map((x, i) => {
                        const sc = x.scores || {};
                        const d = delay++;
                        return `
                        <div class="va-rec" style="transition-delay:${d * 70}ms">
                            <div class="va-rec-top">
                                <span class="wp-rank">${esc(String(x.rank || i + 1))}</span>
                                <span class="v-name"><b>${esc(x.variety || '')}${x.type === 'hybrid' ? ' <span class="va-type">hybrid</span>' : ''}</b>${x.by ? `<small>${esc(x.by)}${x.released && x.released !== 'n/a' ? ' · released ' + esc(x.released) : ''}</small>` : ''}</span>
                                <span class="va-overall"><b>${esc(String(x.overall ?? ''))}</b><small>overall</small></span>
                            </div>
                            <div class="va-facts">
                                ${x.maturityDays && x.maturityDays !== 'n/a' ? `<span class="va-fact">⏱️ ${esc(x.maturityDays)}</span>` : ''}
                                ${x.yieldPotential && !/not published/i.test(x.yieldPotential) ? `<span class="va-fact">🌾 ${esc(x.yieldPotential)}</span>` : ''}
                            </div>
                            <div class="va-bars">
                                ${order.map((k) => `<div class="va-bar"><small>${esc(PR[k]?.label || k)}<i>${esc(String(Math.round(Number(sc[k]) || 0)))}</i></small><div class="wp-track"><span class="wp-fill${k === topKey ? ' is-top' : ''}" style="width:${Math.max(3, Math.min(100, Number(sc[k]) || 0))}%;transition-delay:${120 + d * 70}ms"></span></div></div>`).join('')}
                            </div>
                            ${(list(x.strengths).length || list(x.weaknesses).length) ? `<div class="va-pm">
                                ${list(x.strengths).length ? `<ul class="is-plus">${list(x.strengths).map((s) => `<li>${esc(s)}</li>`).join('')}</ul>` : ''}
                                ${list(x.weaknesses).length ? `<ul class="is-minus">${list(x.weaknesses).map((s) => `<li>${esc(s)}</li>`).join('')}</ul>` : ''}
                            </div>` : ''}
                            ${x.fitNotes ? `<p class="wp-why">${esc(sweep(x.fitNotes))}</p>` : ''}
                            ${list(x.sources).length ? `<p class="va-src">Sources: ${esc(list(x.sources).join(' · '))}</p>` : ''}
                        </div>`;
                    }).join('')}
                </div>`).join('');
            })()}

            ${(r.givenVarieties || []).length ? `
            <div class="wtp-card">
                <h3>Your varieties, one by one</h3>
                ${(r.givenVarieties || []).map((g) => `
                    <div class="wtp-win-row is-note"><b>${esc(g.variety || '')}:</b><span>${esc(sweep(g.verdict))}</span></div>`).join('')}
            </div>` : ''}

            ${(r.newest || []).length ? `
            <div class="wtp-card">
                <h3>Newest in ${(window.ANEE_REGION || {}).ph === false ? (window.ANEE_REGION.name || 'your country') : 'the Philippines'}</h3>
                ${(r.newest || []).map((n) => `
                    <div class="wtp-win-row is-new"><b>🆕 ${esc(n.variety || '')}${n.year ? ' (' + esc(n.year) + ')' : ''}${n.by ? ' · ' + esc(n.by) : ''}:</b><span>${esc(sweep(n.note))}</span></div>`).join('')}
            </div>` : ''}

            ${r.conditions ? `
            <div class="wtp-card">
                <h3>What your ground and the weather ask of a variety</h3>
                <div class="va-cond">
                    <div class="va-cond-box"><b>Soil</b><p>${esc(sweep(r.conditions.soil))}</p></div>
                    <div class="va-cond-box"><b>Weather</b><p>${esc(sweep(r.conditions.weather))}</p></div>
                    ${list(r.conditions.risks).length ? `<div class="va-cond-box is-wide"><b>Risks to plan for</b><ul>${list(r.conditions.risks).map((s) => `<li>${esc(s)}</li>`).join('')}</ul></div>` : ''}
                </div>
            </div>` : ''}

            ${list(r.management).length ? `
            <div class="wtp-card">
                <h3>Growing the top pick here</h3>
                <ul class="va-mgmt">${list(r.management).map((s) => `<li>${esc(sweep(s))}</li>`).join('')}</ul>
            </div>` : ''}

            <div class="wtp-card">
                <h3>In plain words</h3>
                <p class="wtp-plain">${esc(sweep(r.summary))}</p>
                ${(r.dataGaps || []).length ? `
                    <h3 class="mt-4">What this research could not verify</h3>
                    <ul class="wtp-gap">${(r.dataGaps || []).map((g) => `<li>${esc(g)}</li>`).join('')}</ul>` : ''}
            </div>

            <div class="wtp-card">
                <h3>🌐 What Anee read</h3>
                ${(r.webSources || []).length ? `<div class="va-links">${(r.webSources || []).map((s) => `
                    <a class="va-link" href="${esc(s.url)}" target="_blank" rel="noopener nofollow"><span class="l-t">${esc(s.title || s.url)}</span><span class="l-h">${esc(host(s.url))}</span></a>`).join('')}</div>`
                    : (r.searched ? '<p class="wtp-fine">The pages she read were not handed back this time.</p>'
                        : '<p class="va-nosearch">Anee could not reach the web for this run, so this reading comes from her own knowledge and may miss the newest releases. Run it again later for a searched one.</p>')}
            </div>

            <div class="wtp-card">
                <h3>🧭 A guide, not a promise</h3>
                <p class="wtp-fine">Trial yields are what a variety did on a trial farm in its year; your field, your season and your hands will differ. What this gives you is a searched, scored shortlist — the varieties whose documented traits match what you described, weighed the way you asked — which beats choosing on the seed shop's word alone. Ask the nearest DA or PhilRice office what seed is actually available near you before you decide.</p>
            </div>

            <div class="wtp-acts">
                <button type="button" class="btn btn-primary w-full" data-va-attach>
                    ${OPT && OPT.aneeFace ? `<img class="wtp-anee-face" src="${esc(OPT.aneeFace)}" alt="">` : '🤖'} Attach to Anee
                </button>
                ${mode === 'fresh' ? `<button type="button" class="btn btn-white w-full" data-va-again>🔬 Run another research</button>` : ''}
                <button type="button" class="btn btn-white w-full" data-va-delete>🗑 Delete</button>
            </div>`;

        hostEl.hidden = false;
        if (!quiet) {
            requestAnimationFrame(() => requestAnimationFrame(() => hostEl.classList.add('is-drawn')));
            hostEl.scrollIntoView({ behavior: 'smooth', block: 'start' });
        } else if (hostEl.id === 'vaReport') {
            hostEl.classList.add('is-drawn');
        }

        hostEl.querySelector('[data-va-attach]').addEventListener('click', () => {
            if (item.savedId) window.location.href = U.anee + '?analysis=' + item.savedId;
        });
        hostEl.querySelector('[data-va-again]')?.addEventListener('click', () => { closeView(); wizardBack(); });
        hostEl.querySelector('[data-va-delete]').addEventListener('click', async () => {
            const delId = item.savedId;
            const ok = window.confirmAction
                ? await confirmAction({ title: 'Delete this research?', message: 'The credits it cost are already spent; only the report goes.', confirmText: 'Delete', danger: true })
                : confirm('Delete this research?');
            if (!ok) return;
            try {
                const res = await api(U.del(delId), { method: 'DELETE' });
                toast(res.message);
                hostEl.hidden = true;
                closeView();
                $id('vaReport').hidden = true; $id('vaSavedReport').hidden = true;
                loadSaved().catch(() => {});
                if (mode === 'fresh') wizardBack();
            } catch (err) { toast(err.message, 'error'); }
        });
    }

    /* ---------------- saved ---------------- */
    async function loadSaved() {
        const res = await api(U.list + '?_=' + Date.now(), { method: 'GET' });
        const rows = res.data.rows || [];
        VA_ROWS = rows;
        $id('vaSavedList').innerHTML = rows.map((r) => `
            <button type="button" class="wtp-saved" data-saved="${r.id}">
                <span class="grow min-w-0"><b>${esc(r.title)}</b><small>${r.description ? esc(r.description) + ' · ' : ''}${esc(r.at)} · ${r.credits} credits</small></span>
                <span role="button" tabindex="0" class="wtp-pen" data-meta="${r.id}" title="Edit name and description" aria-label="Edit ${esc(r.title)}">
                    <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" style="width:.85rem;height:.85rem"><path stroke-linecap="round" stroke-linejoin="round" d="M11 5H6a2 2 0 00-2 2v11a2 2 0 002 2h11a2 2 0 002-2v-5m-1.414-9.414a2 2 0 112.828 2.828L11.828 15H9v-2.828l8.586-8.586z"/></svg>
                </span>
                <svg class="w-4 h-4 text-gray-300 shrink-0" fill="none" stroke="currentColor" stroke-width="2.4" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" d="M9 5l7 7-7 7"/></svg>
            </button>`).join('');
        $id('vaSavedEmpty').classList.toggle('hidden', rows.length > 0);
    }

    let VA_ROWS = [];
    let VA_META_ID = null;
    document.addEventListener('click', async (e) => {
        const saveBtn = e.target.closest('#vaMetaSave');
        if (saveBtn && VA_META_ID !== null) {
            saveBtn.disabled = true;
            try {
                const res = await api(VA_META_URL, { method: 'POST', body: {
                    id: VA_META_ID,
                    title: $id('vaMetaTitle').value.trim(),
                    description: $id('vaMetaDesc').value.trim(),
                } });
                toast(res.message);
                closeSheet('vaMetaSheet');
                loadSaved().catch(() => {});
            } catch (err) { toast(err.message, 'error'); }
            finally { saveBtn.disabled = false; }
            return;
        }
        const pen = e.target.closest('[data-meta]');
        if (pen && pen.closest('#vaSavedList')) {
            e.stopPropagation();
            const r = VA_ROWS.find((x) => String(x.id) === pen.getAttribute('data-meta'));
            if (!r) return;
            VA_META_ID = r.id;
            $id('vaMetaTitle').value = r.title || '';
            $id('vaMetaDesc').value = r.description || '';
            openSheet('vaMetaSheet');
            return;
        }
        const b = e.target.closest('[data-saved]');
        if (!b) return;
        try {
            const res = await api(U.one(b.getAttribute('data-saved')), { method: 'GET' });
            const item = { report: res.data.report, params: res.data.params, charged: res.data.credits, savedId: res.data.id };
            openView(item, 'saved');
        } catch (err) { toast(err.message, 'error'); }
    });

    /* ---------------- tabs ---------------- */
    const tab = (which) => {
        $id('vaGen').classList.toggle('hidden', which !== 'gen');
        $id('vaSavedPane').classList.toggle('hidden', which !== 'saved');
        $id('vaTabGen').classList.toggle('is-on', which === 'gen');
        $id('vaTabSaved').classList.toggle('is-on', which === 'saved');
        if (which === 'saved') loadSaved().catch((err) => toast(err.message, 'error'));
    };
    $id('vaTabGen').addEventListener('click', () => tab('gen'));
    $id('vaTabSaved').addEventListener('click', () => tab('saved'));

    if (window.api) boot();
    else window.addEventListener('load', boot, { once: true });
})();
</script>
@endsection

@push('sheets')
{{-- Rename a saved research and describe it in your own words. --}}
<div class="sheet hidden" id="vaMetaSheet" style="--sheet-width:26rem">
    <div class="sheet-handle"></div>
    <div class="sheet-header">
        <h3 class="sheet-title">Edit this research</h3>
        <button type="button" data-sheet-close class="btn-ghost p-2 rounded-full" aria-label="Close">✕</button>
    </div>
    <div class="sheet-body space-y-4">
        <div>
            <label class="form-label" for="vaMetaTitle">Name</label>
            <input type="text" id="vaMetaTitle" class="form-input" maxlength="191">
        </div>
        <div>
            <label class="form-label" for="vaMetaDesc">Description <span class="text-gray-400 font-normal">(optional)</span></label>
            <textarea id="vaMetaDesc" class="form-textarea" rows="3" maxlength="2000"></textarea>
        </div>
    </div>
    <div class="sheet-footer">
        <button type="button" class="btn btn-ghost" data-sheet-close>Cancel</button>
        <button type="button" class="btn btn-primary" id="vaMetaSave">Save changes</button>
    </div>
</div>
@endpush

@push('head')
<style>
    .wtp-pen { flex: none; width: 1.6rem; height: 1.6rem; border-radius: .45rem; display: inline-flex;
        align-items: center; justify-content: center; color: var(--color-gray-400); }
    .wtp-pen:hover { color: var(--color-brand-700); background: var(--color-brand-50); }
    html.dark .wtp-pen:hover { background: rgb(107 159 61 / .18); color: #a5c97e; }
</style>
@endpush
