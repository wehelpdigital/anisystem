@extends('layouts.app')
@section('title', 'Crop Protocol Analysis')
@section('page-title', 'Crop Protocol Analysis')
@section('page-subtitle', 'Your season, stage by stage')

@section('back', route('app.dashboard'))
@push('scripts')
<script>
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
    /* ---- CROP PROTOCOL ----------------------------------------------
       The fourth of the Quick Tools: the sisters' wizard walk, veil and
       shelf, the wtp-* dress copied whole — and a report that is a season
       laid out stage by stage, with the fertilizer drawn as bars and the
       shopping list added up. */
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
    .wtp-dots { display: flex; gap: .3rem; justify-content: center; margin: 1rem 0 .2rem; flex-wrap: wrap; }
    .wtp-dot { width: .5rem; height: .5rem; border-radius: 999px; background: var(--color-gray-200);
        transition: all .28s cubic-bezier(.22,1,.36,1); }
    .wtp-dot.is-on { width: 1.2rem; background: var(--color-brand-600); }
    .wtp-q { font-size: 1.05rem; font-weight: 800; color: var(--color-gray-900); margin-bottom: .2rem; }
    .wtp-sub { font-size: .8rem; color: var(--color-gray-500); margin-bottom: .8rem; }

    .wtp-choices { display: grid; gap: .5rem; }
    .wtp-choices.is-two { grid-template-columns: 1fr 1fr; }
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

    /* The crop tag and its sheet — the lot form's dress. */
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

    /* The target yield: a number and a unit that is a pair of pills. */
    .cp-yield { display: flex; gap: .5rem; align-items: stretch; }
    .cp-yield .form-input { flex: 1 1 auto; min-width: 0; }
    .cp-units { display: flex; border: 1.5px solid var(--color-gray-200); border-radius: .8rem; overflow: hidden; flex: none; }
    .cp-unit { padding: 0 .8rem; font-size: .8rem; font-weight: 800; color: var(--color-gray-500); background: var(--color-white); cursor: pointer; }
    .cp-unit.is-on { background: var(--color-brand-600); color: #fff; }
    html.dark .cp-units { border-color: #2b3a1c; }
    html.dark .cp-unit { background: #151b12; color: #93a684; }
    html.dark .cp-unit.is-on { background: #4a7c2a; color: #fff; }
    .cp-area { position: relative; }
    .cp-area .form-input { padding-right: 5rem; }
    .cp-area-u { position: absolute; right: .9rem; top: 50%; transform: translateY(-50%); font-size: .8rem; font-weight: 700; color: var(--color-gray-400); pointer-events: none; }

    /* ---- THE REPORT ---- */
    .wtp-report { display: grid; gap: .9rem; }
    /* A grid item's automatic minimum is its content's width — a chart's
       scroll box would widen the whole report without this. */
    .wtp-report > * { min-width: 0; max-width: 100%; }
    .wtp-hero { border-radius: 1.1rem; padding: 1.1rem 1.2rem; color: #fff;
        background: linear-gradient(130deg, #4a7c2a, #2d5016 70%); }
    .wtp-hero h2 { font-size: 1.15rem; font-weight: 800; margin-bottom: .15rem; }
    .wtp-hero .h-win { font-size: 1.25rem; font-weight: 800; letter-spacing: .01em; line-height: 1.3; }
    .wtp-hero .h-why { font-size: .84rem; opacity: .92; line-height: 1.55; margin-top: .45rem; }
    .wtp-chips { display: flex; flex-wrap: wrap; gap: .35rem; margin-top: .6rem; }
    .wtp-chip { font-size: .68rem; font-weight: 700; padding: .18rem .55rem; border-radius: 999px;
        background: rgb(255 255 255 / .18); }

    .wtp-card { border-radius: 1rem; border: 1px solid var(--color-gray-200); background: var(--color-white);
        padding: 1rem 1.1rem; }
    .wtp-card h3 { font-weight: 800; font-size: .92rem; color: var(--color-gray-900); margin-bottom: .6rem; }
    .wtp-card h3 small { display: block; font-size: .72rem; font-weight: 500; color: var(--color-gray-500); margin-top: .1rem; }

    /* The clock is the crop: one line the reader cannot miss. */
    .cp-clock { display: flex; gap: .7rem; align-items: flex-start; border-radius: 1rem; padding: .85rem 1rem;
        background: #fffbeb; border: 1px solid #fde68a; color: #78350f; font-size: .82rem; line-height: 1.5; }
    .cp-clock b { color: #92400e; }
    .cp-clock .e { font-size: 1.3rem; flex: none; }
    html.dark .cp-clock { background: #2a2413; border-color: #4a3d16; color: #e0c26a; }
    html.dark .cp-clock b { color: #fcd34d; }

    /* The season's stages: a rail of numbered stops. */
    .cp-stages { display: grid; gap: .55rem; }
    .cp-stage { display: flex; gap: .7rem; padding: .65rem .7rem; border-radius: .85rem; background: var(--color-gray-50); border: 1px solid var(--color-gray-100);
        opacity: 0; transform: translateY(6px); transition: all .45s cubic-bezier(.22,1,.36,1); }
    .wtp-report.is-drawn .cp-stage { opacity: 1; transform: none; }
    .cp-stage-n { flex: none; width: 1.6rem; height: 1.6rem; border-radius: 999px; display: inline-flex; align-items: center; justify-content: center;
        font-size: .74rem; font-weight: 800; background: var(--color-brand-600); color: #fff; }
    .cp-stage-b { min-width: 0; flex: 1 1 auto; }
    .cp-stage-b b { display: block; font-size: .88rem; color: var(--color-gray-900); }
    .cp-stage-b .signs { font-size: .76rem; color: var(--color-gray-600); line-height: 1.45; margin-top: .1rem; }
    .cp-stage-b .hint { font-size: .68rem; color: var(--color-gray-400); margin-top: .1rem; }
    .cp-stage-b ul { margin-top: .4rem; padding-left: 1rem; font-size: .78rem; line-height: 1.5; color: var(--color-gray-700); }
    .cp-stage-b ul li { list-style: disc; }
    html.dark .cp-stage { background: #10150c; border-color: #222b1a; }
    html.dark .cp-stage-b b { color: #e8efe1; }
    html.dark .cp-stage-b .signs, html.dark .cp-stage-b ul { color: #b7c2ad; }

    /* THE FERTILIZER, DRAWN: a bar per application, its height the bags
       for the whole field, split by product. */
    .cp-chart { display: flex; gap: .4rem; align-items: flex-end; height: 9rem; padding: .5rem .2rem 0; border-bottom: 1px solid var(--color-gray-200); overflow-x: auto; scrollbar-width: none; }
    .cp-chart::-webkit-scrollbar, .cp-lbls::-webkit-scrollbar { display: none; }
    .cp-lbls { scrollbar-width: none; }
    .cp-col { flex: 1 1 0; min-width: 3.2rem; display: flex; flex-direction: column; align-items: center; justify-content: flex-end; height: 100%; }
    .cp-col-val { font-size: .66rem; font-weight: 800; color: var(--color-gray-700); margin-bottom: .2rem; white-space: nowrap; }
    .cp-bar { width: 70%; max-width: 2.6rem; display: flex; flex-direction: column-reverse; border-radius: .35rem .35rem 0 0; overflow: hidden;
        transform-origin: bottom; transform: scaleY(0); transition: transform .7s cubic-bezier(.22,1,.36,1); }
    .wtp-report.is-drawn .cp-bar { transform: scaleY(1); }
    .cp-seg { width: 100%; }
    .cp-lbls { display: flex; gap: .4rem; padding: .3rem .2rem 0; overflow-x: auto; }
    .cp-lbl { flex: 1 1 0; min-width: 3.2rem; font-size: .6rem; font-weight: 700; color: var(--color-gray-500); text-align: center; line-height: 1.2; }
    .cp-legend { display: flex; flex-wrap: wrap; gap: .3rem .7rem; margin-top: .6rem; }
    .cp-legend span { display: inline-flex; align-items: center; gap: .3rem; font-size: .7rem; font-weight: 700; color: var(--color-gray-600); }
    .cp-legend i { width: .7rem; height: .7rem; border-radius: .2rem; display: inline-block; }
    .cp-fert { margin-top: .8rem; display: grid; gap: .45rem; }
    .cp-fert-row { display: flex; gap: .6rem; align-items: flex-start; padding: .55rem .65rem; border-radius: .75rem; background: var(--color-gray-50); border: 1px solid var(--color-gray-100); }
    .cp-fert-row .st { flex: none; width: 6.5rem; }
    .cp-fert-row .st b { display: block; font-size: .78rem; color: var(--color-gray-900); }
    .cp-fert-row .st i { display: block; font-style: normal; font-size: .66rem; color: var(--color-gray-400); line-height: 1.3; }
    .cp-fert-row .pr { flex: 1 1 auto; min-width: 0; display: grid; gap: .25rem; }
    .cp-fert-row .pr div { font-size: .78rem; color: var(--color-gray-700); line-height: 1.4; }
    .cp-fert-row .pr div b { color: var(--color-gray-900); }
    .cp-fert-row .pr div small { color: var(--color-gray-400); font-size: .68rem; }
    .cp-totals { display: flex; flex-wrap: wrap; gap: .35rem; margin-top: .7rem; }
    .cp-total { font-size: .72rem; font-weight: 800; padding: .3rem .6rem; border-radius: 999px; background: var(--color-brand-50); color: var(--color-brand-800); border: 1px solid var(--color-brand-200); }
    .cp-total i { font-style: normal; font-weight: 600; opacity: .7; }
    .cp-note { font-size: .76rem; color: var(--color-gray-500); line-height: 1.5; margin-top: .6rem; }
    html.dark .cp-chart { border-color: #2b3a1c; }
    html.dark .cp-col-val { color: #d5e3c5; }
    html.dark .cp-fert-row { background: #10150c; border-color: #222b1a; }
    html.dark .cp-fert-row .st b, html.dark .cp-fert-row .pr div b { color: #e8efe1; }
    html.dark .cp-fert-row .pr div { color: #b7c2ad; }
    html.dark .cp-total { background: #22301a; color: #cfe6b8; border-color: #3d5226; }
    html.dark .cp-legend span { color: #b7c2ad; }

    /* Protection: one card per pest / disease / weed, colour by kind. */
    .cp-prot { display: grid; gap: .5rem; }
    @media (min-width: 640px) { .cp-prot { grid-template-columns: 1fr 1fr; } }
    .cp-pc { border-radius: .8rem; padding: .6rem .7rem; border: 1px solid; font-size: .76rem; line-height: 1.45; }
    .cp-pc b { display: block; font-size: .84rem; margin-bottom: .1rem; }
    .cp-pc .tag { display: inline-block; font-size: .6rem; font-weight: 800; letter-spacing: .04em; text-transform: uppercase; padding: .1rem .45rem; border-radius: 999px; margin-bottom: .3rem; }
    .cp-pc p { margin-top: .15rem; }
    .cp-pc p em { font-style: normal; font-weight: 700; }
    .cp-pc.is-insect { background: #fff7ed; border-color: #fed7aa; color: #7c2d12; }
    .cp-pc.is-insect .tag { background: #ffedd5; color: #9a3412; }
    .cp-pc.is-disease { background: #fdf2f8; border-color: #fbcfe8; color: #831843; }
    .cp-pc.is-disease .tag { background: #fce7f3; color: #9d174d; }
    .cp-pc.is-weed { background: #f0fdf4; border-color: #bbf7d0; color: #14532d; }
    .cp-pc.is-weed .tag { background: #dcfce7; color: #166534; }
    html.dark .cp-pc.is-insect { background: #2a1a0e; border-color: #5a3a1a; color: #fdba74; }
    html.dark .cp-pc.is-disease { background: #2a1020; border-color: #5a2040; color: #f9a8d4; }
    html.dark .cp-pc.is-weed { background: #10240f; border-color: #1e4a1e; color: #86efac; }
    html.dark .cp-pc .tag { background: rgb(255 255 255 / .1); color: inherit; }

    /* Water: a row per stage with a drop meter. */
    .cp-water { display: grid; gap: .45rem; }
    .cp-wrow { display: flex; gap: .6rem; padding: .55rem .65rem; border-radius: .75rem; background: #eff6ff; border: 1px solid #bfdbfe; }
    .cp-wrow .e { flex: none; font-size: 1.1rem; }
    .cp-wrow .t { min-width: 0; font-size: .78rem; color: #1e3a8a; line-height: 1.45; }
    .cp-wrow .t b { display: block; color: #1e40af; font-size: .82rem; }
    .cp-wrow .t i { display: block; font-style: normal; font-size: .7rem; color: #3b82f6; margin-top: .15rem; }
    html.dark .cp-wrow { background: #0f1a2e; border-color: #1e3a8a; }
    html.dark .cp-wrow .t { color: #bfdbfe; }
    html.dark .cp-wrow .t b { color: #dbeafe; }

    /* Yield: target against realistic, as two bars. */

    /* ---- the two-part protocol ---- */
    .cp2-part { display: flex; align-items: center; gap: .6rem; margin: .4rem 0 -.3rem; }
    .cp2-part-n { flex: none; width: 1.9rem; height: 1.9rem; border-radius: 999px; display: inline-flex; align-items: center; justify-content: center; font-weight: 800; font-size: .85rem; background: #3d6823; color: #fff; }
    .cp2-part b { display: block; font-family: var(--font-heading); font-size: 1.05rem; color: var(--color-gray-900); line-height: 1.1; }
    .cp2-part small { display: block; font-size: .72rem; color: var(--color-gray-500); }
    .cp2-bg { display: grid; gap: .8rem; }
    .cp2-bg-row { display: flex; gap: .7rem; align-items: flex-start; }
    .cp2-bg-row + .cp2-bg-row { padding-top: .8rem; border-top: 1px solid var(--color-gray-100); }
    .cp2-bg-e { flex: none; font-size: 1.25rem; line-height: 1.2; }
    .cp2-bg-row > div { min-width: 0; flex: 1 1 auto; }
    .cp2-bg-row b { display: block; font-size: .9rem; color: var(--color-gray-900); margin-bottom: .2rem; }
    .cp2-bg-row b small { font-weight: 600; color: var(--color-gray-500); font-size: .72rem; }
    .cp2-bg-row p { font-size: .84rem; line-height: 1.55; color: var(--color-gray-700); }
    .cp2-dim { color: var(--color-gray-500) !important; font-size: .78rem !important; margin-top: .2rem; }
    .cp2-pills { display: flex; flex-wrap: wrap; gap: .35rem; margin: .4rem 0; }
    .cp2-pill { font-size: .7rem; font-weight: 700; padding: .22rem .6rem; border-radius: 999px; background: var(--color-brand-50); color: var(--color-brand-800); border: 1px solid var(--color-brand-100); }
    .cp2-pill.is-risk { background: #fff1e6; color: #9a3412; border-color: #fdd7b0; }
    .cp2-pill.is-src { background: var(--color-gray-100); color: var(--color-gray-600); border-color: var(--color-gray-200); }
    .wp-watch { font-size: .76rem; color: #92610e; margin-top: .3rem; }
    /* the stage picker */
    /* The chart fits the screen whatever the count of stages: the columns
       share the width (no minimum, no sideways scroll) and the labels are
       the stage numbers, which the rows under the chart spell out. */
    .cp2-chart { overflow: hidden; gap: .25rem; padding-left: 0; padding-right: 0; }
    .cp2-chart .cp2-col { min-width: 0; border: 0; background: transparent; padding: 0; cursor: pointer; border-radius: .5rem .5rem 0 0; transition: background .2s; }
    .cp2-chart .cp-bar { width: 72%; max-width: 2.4rem; }
    .cp2-chart .cp-col-val { font-size: .62rem; }
    .cp2-lbls { overflow: hidden; gap: .25rem; padding-left: 0; padding-right: 0; }
    .cp2-lbls .cp-lbl { min-width: 0; }
    .cp2-chart .cp2-col:hover { background: var(--color-gray-50); }
    .cp2-chart .cp2-col.is-sel { background: var(--color-brand-50); }
    .cp2-chart .cp2-col.is-sel .cp-col-val { color: var(--color-brand-800); }
    .cp2-lbls .cp-lbl.is-sel { color: var(--color-brand-800); }
    .cp2-detail { margin-top: .8rem; padding: .75rem .85rem; border-radius: .9rem; background: var(--color-gray-50); border: 1px solid var(--color-gray-200); transition: opacity .28s; min-height: 4rem; }
    .cp2-detail.is-swap { opacity: 0; }
    .cp2-d-head { display: flex; align-items: center; gap: .6rem; }
    .cp2-d-head > span:nth-child(2) { flex: 1 1 auto; min-width: 0; }
    .cp2-d-head b { display: block; font-size: .92rem; color: var(--color-gray-900); }
    .cp2-d-head small { display: block; font-size: .68rem; color: var(--color-gray-400); }
    .cp2-d-nav { flex: none; display: flex; gap: .25rem; }
    .cp2-d-nav button { width: 1.8rem; height: 1.8rem; border-radius: 999px; border: 1px solid var(--color-gray-200); background: var(--color-white); color: var(--color-gray-700); font-size: 1rem; line-height: 1; cursor: pointer; }
    .cp2-d-nav button:disabled { opacity: .3; cursor: default; }
    .cp2-d-signs { font-size: .8rem; color: var(--color-gray-600); line-height: 1.5; margin-top: .4rem; }
    .cp2-d-fert { display: grid; gap: .4rem; margin-top: .55rem; }
    .cp2-d-app { display: flex; gap: .5rem; align-items: flex-start; font-size: .82rem; line-height: 1.45; color: var(--color-gray-700); }
    .cp2-d-app i { flex: none; width: .7rem; height: .7rem; border-radius: .2rem; margin-top: .3rem; }
    .cp2-d-app b { color: var(--color-gray-900); }
    .cp2-d-app small { color: var(--color-gray-400); font-size: .7rem; }
    .cp2-d-app em { display: block; font-style: normal; font-size: .76rem; color: var(--color-brand-800); }
    .cp2-d-obs { display: flex; gap: .5rem; align-items: flex-start; margin-top: .5rem; font-size: .8rem; line-height: 1.5; color: var(--color-gray-700); }
    .cp2-d-obs span { flex: none; font-size: .66rem; font-weight: 800; letter-spacing: .04em; text-transform: uppercase; padding: .18rem .5rem; border-radius: 999px; margin-top: .1rem; }
    .cp2-d-obs .is-obs { background: #e6f2d8; color: #2f5219; }
    .cp2-d-obs .is-act { background: #fff1e6; color: #9a3412; }
    .cp2-d-obs p { margin: 0; }
    /* Every grid here is one bounded column: a track that may grow to its
       content is how a long row pushed the whole report sideways. */
    .cp2-rows, .cp2-tot, .cp2-bg, .cp2-shop, .cp2-d-fert { grid-template-columns: minmax(0, 1fr); }
    .cp2-rows { display: grid; gap: .25rem; margin-top: .6rem; }
    .cp2-row { display: flex; align-items: center; gap: .6rem; width: 100%; min-width: 0; max-width: 100%; text-align: left; border: 0; background: transparent; padding: .45rem .5rem; border-radius: .7rem; cursor: pointer; font: inherit; color: var(--color-gray-700); transition: background .2s; }
    .cp2-row:hover { background: var(--color-gray-50); }
    .cp2-row.is-sel { background: var(--color-brand-50); }
    .cp2-row-n { flex: none; width: 1.5rem; height: 1.5rem; border-radius: 999px; display: inline-flex; align-items: center; justify-content: center; font-size: .72rem; font-weight: 800; background: var(--color-brand-100); color: var(--color-brand-800); }
    .cp2-row.is-sel .cp2-row-n { background: #3d6823; color: #fff; }
    .cp2-row-t { flex: 1 1 auto; min-width: 0; }
    .cp2-row-t b { display: block; font-size: .82rem; color: var(--color-gray-900); }
    .cp2-row-t small { display: -webkit-box; -webkit-box-orient: vertical; -webkit-line-clamp: 2; overflow: hidden; font-size: .68rem; line-height: 1.35; color: var(--color-gray-400); }
    .cp2-row-b { flex: none; font-size: .74rem; font-weight: 800; color: var(--color-brand-800); }
    /* totals */
    .cp2-tot { display: grid; gap: .5rem; }
    .cp2-tot-row { display: grid; grid-template-columns: 1fr auto auto; grid-template-areas: "n b s" "tr tr tr"; align-items: center; gap: .15rem .5rem; font-size: .78rem; }
    .cp2-tot-row .n { grid-area: n; display: flex; align-items: center; gap: .35rem; font-weight: 700; color: var(--color-gray-800); min-width: 0; }
    .cp2-tot-row .n i { flex: none; width: .7rem; height: .7rem; border-radius: .2rem; }
    .cp2-tot-row b { grid-area: b; }
    .cp2-tot-row small { grid-area: s; }
    .cp2-tot-row .tr { grid-area: tr; height: .6rem; border-radius: 999px; background: var(--color-gray-100); overflow: hidden; }
    .cp2-tot-row .tr span { display: block; height: 100%; border-radius: 999px; transform-origin: left; transform: scaleX(0); transition: transform .7s cubic-bezier(.22,1,.36,1); }
    .wtp-report.is-drawn .cp2-tot-row .tr span { transform: scaleX(1); }
    .cp2-tot-row b { color: var(--color-gray-900); white-space: nowrap; }
    .cp2-tot-row small { color: var(--color-gray-400); font-size: .68rem; white-space: nowrap; }
    .cp2-npk { display: flex; flex-wrap: wrap; align-items: baseline; gap: .5rem .9rem; margin-top: .7rem; font-size: .78rem; color: var(--color-gray-600); }
    .cp2-npk b { color: var(--color-gray-900); font-size: .9rem; }
    .cp2-npk small { flex-basis: 100%; font-size: .68rem; color: var(--color-gray-400); }
    .cp2-shop { margin-top: .7rem; }
    .cp2-threats { display: grid; gap: .5rem; }
    .cp2-threats { grid-template-columns: minmax(0, 1fr); }
    @media (min-width: 640px) { .cp2-threats { grid-template-columns: minmax(0, 1fr) minmax(0, 1fr); } }
    .cp2-threat p.is-prod { color: var(--color-brand-800); }
    .cp2-threat p.is-prod em { color: var(--color-brand-700); }
    html.dark .cp2-threat p.is-prod { color: #a5c97e; }
    .cp2-foliars { display: grid; grid-template-columns: minmax(0, 1fr); gap: .45rem; }
    .cp2-foliar { display: flex; gap: .6rem; align-items: flex-start; padding: .6rem .7rem; border-radius: .8rem; background: #f4faee; border: 1px solid #cfe3bd; }
    .cp2-foliar .e { flex: none; font-size: 1.1rem; }
    .cp2-foliar b { display: block; font-size: .82rem; color: var(--color-gray-900); }
    .cp2-foliar small { display: block; font-size: .66rem; font-weight: 700; color: var(--color-brand-700); text-transform: uppercase; letter-spacing: .04em; }
    .cp2-foliar p { font-size: .76rem; color: var(--color-gray-600); line-height: 1.45; margin-top: .1rem; }
    html.dark .cp2-foliar { background: rgb(107 159 61 / .1); border-color: #2f4d24; }
    html.dark .cp2-foliar b { color: #e8efe1; }
    html.dark .cp2-foliar small { color: #a5c97e; }
    html.dark .cp2-foliar p { color: #b7c2ad; }
    .cp2-d-fol { display: flex; gap: .5rem; align-items: flex-start; margin-top: .5rem; font-size: .8rem; line-height: 1.5; color: var(--color-gray-700); }
    .cp2-d-fol span { flex: none; font-size: .66rem; font-weight: 800; letter-spacing: .04em; text-transform: uppercase; padding: .18rem .5rem; border-radius: 999px; margin-top: .1rem; background: #e0f2fe; color: #075985; }
    .cp2-d-fol p { margin: 0; }
    html.dark .cp2-d-fol { color: #b7c2ad; }
    html.dark .cp2-d-fol span { background: #0c2a3a; color: #7dd3fc; }
    .cp2-threat { padding: .6rem .7rem; border-radius: .8rem; background: #fff7ed; border: 1px solid #fed7aa; }
    .cp2-threat .tag { display: inline-block; font-size: .62rem; font-weight: 800; letter-spacing: .04em; text-transform: uppercase; color: #9a3412; margin-bottom: .15rem; }
    .cp2-threat b { display: block; font-size: .84rem; color: var(--color-gray-900); }
    .cp2-threat p { font-size: .76rem; line-height: 1.45; color: var(--color-gray-700); margin-top: .15rem; }
    .cp2-threat em { font-style: normal; font-weight: 700; color: var(--color-gray-500); }
    html.dark .cp2-part b { color: #e8efe1; }
    html.dark .cp2-bg-row + .cp2-bg-row { border-color: #222b1a; }
    html.dark .cp2-bg-row b, html.dark .cp2-d-head b, html.dark .cp2-d-app b, html.dark .cp2-row-t b, html.dark .cp2-tot-row b, html.dark .cp2-npk b, html.dark .cp2-threat b { color: #e8efe1; }
    html.dark .cp2-bg-row p, html.dark .cp2-d-app, html.dark .cp2-d-obs, html.dark .cp2-row, html.dark .cp2-threat p { color: #b7c2ad; }
    html.dark .cp2-pill { background: #22301a; color: #cfe6b8; border-color: #2b3a1c; }
    html.dark .cp2-pill.is-risk { background: #3a2a0a; color: #fcd34d; border-color: #5a3d10; }
    html.dark .cp2-pill.is-src { background: #1c2416; color: #93a684; border-color: #2b3a1c; }
    html.dark .cp2-chart .cp2-col.is-sel, html.dark .cp2-row.is-sel { background: #22301a; }
    html.dark .cp2-detail { background: #10150c; border-color: #2b3a1c; }
    html.dark .cp2-d-nav button { background: #1c2416; border-color: #2b3a1c; color: #d5e3c5; }
    html.dark .cp2-d-app em { color: #a5c97e; }
    html.dark .cp2-d-obs .is-obs { background: #2f4d24; color: #cfe6b5; }
    html.dark .cp2-d-obs .is-act { background: #3a2a0a; color: #fcd34d; }
    html.dark .cp2-tot-row .tr { background: #222b1a; }
    html.dark .cp2-threat { background: #2a1f10; border-color: #4a3416; }
    html.dark .cp2-threat .tag { color: #fdba74; }
    html.dark .wp-watch { color: #e0b95c; }
    .va-link.is-plain { color: var(--color-gray-700); cursor: default; }
    .va-link.is-plain:hover { background: transparent; }
    html.dark .va-link.is-plain { color: #d5e3c5; }
    @media (prefers-reduced-motion: reduce) { .cp2-detail, .cp2-tot-row .tr span, .cp2-row, .cp2-chart .cp2-col { transition: none; } }
    .cp-yo { display: grid; gap: .45rem; margin-top: .3rem; }
    .cp-yo-row { display: flex; align-items: center; gap: .6rem; font-size: .78rem; color: var(--color-gray-600); }
    .cp-yo-row small { flex: none; width: 5rem; font-weight: 700; }
    .cp-yo-row .tr { flex: 1 1 auto; height: .8rem; border-radius: 999px; background: var(--color-gray-100); overflow: hidden; }
    .cp-yo-row .tr span { display: block; height: 100%; border-radius: 999px; background: var(--color-brand-500); transform-origin: left; transform: scaleX(0); transition: transform .7s cubic-bezier(.22,1,.36,1); }
    .cp-yo-row .tr span.is-target { background: #f59e0b; }
    .wtp-report.is-drawn .cp-yo-row .tr span { transform: scaleX(1); }
    .cp-yo-row b { flex: none; min-width: 4.5rem; text-align: right; color: var(--color-gray-900); }
    html.dark .cp-yo-row .tr { background: #222b1a; }
    html.dark .cp-yo-row b { color: #e8efe1; }

    /* The shopping list. */
    .cp-shop { display: grid; gap: .3rem; }
    .cp-item { display: flex; align-items: center; gap: .6rem; padding: .5rem .6rem; border-radius: .7rem; border: 1px dashed var(--color-gray-200); font-size: .8rem; }
    .cp-item .n { flex: 1 1 auto; min-width: 0; color: var(--color-gray-800); }
    .cp-item .n small { display: block; font-size: .66rem; color: var(--color-gray-400); }
    .cp-item .q { flex: none; font-weight: 800; color: var(--color-gray-900); text-align: right; }
    .cp-item .q small { display: block; font-size: .64rem; font-weight: 600; color: var(--color-gray-400); }
    html.dark .cp-item { border-color: #2b3a1c; }
    html.dark .cp-item .n, html.dark .cp-item .q { color: #e8efe1; }

    .wtp-win-row { display: block; padding: .6rem .7rem; border-radius: .7rem; margin-bottom: .45rem; font-size: .84rem; line-height: 1.55; border: 1px solid; }
    .wtp-win-row.is-no { background: #fef2f2; border-color: #fecaca; color: #7f1d1d; }
    .wtp-win-row.is-note { background: #f7fbf2; border-color: #cfe3b8; color: #2d5016; }
    html.dark .wtp-win-row.is-no { background: #2a1414; border-color: #4c1d1d; color: #fca5a5; }
    html.dark .wtp-win-row.is-note { background: #1c2913; border-color: #2b3a1c; color: #cfe6b8; }
    .cp-watch li { list-style: none; position: relative; padding-left: 1.4rem; font-size: .82rem; line-height: 1.5; color: var(--color-gray-700); margin-bottom: .3rem; }
    .cp-watch li::before { content: '👀'; position: absolute; left: 0; top: 0; font-size: .8rem; }
    html.dark .cp-watch li { color: #d5e3c5; }
    .va-links { display: grid; gap: .3rem; }
    .va-link { display: flex; align-items: center; gap: .5rem; font-size: .78rem; color: var(--color-brand-700); text-decoration: none; padding: .35rem .5rem; border-radius: .6rem; min-width: 0; }
    .va-link:hover { background: var(--color-brand-50); }
    .va-link .l-t { min-width: 0; flex: 1 1 auto; overflow: hidden; text-overflow: ellipsis; white-space: nowrap; }
    .va-link .l-h { flex: none; font-size: .66rem; color: var(--color-gray-400); max-width: 9rem; overflow: hidden; text-overflow: ellipsis; white-space: nowrap; }
    html.dark .va-link { color: #a5c97e; }
    html.dark .va-link:hover { background: #22301a; }

    .wtp-gap { font-size: .78rem; color: var(--color-gray-500); line-height: 1.55; }
    .wtp-gap li { list-style: disc; margin-left: 1.1rem; }
    .wtp-fine { font-size: .72rem; color: var(--color-gray-400); line-height: 1.55; }
    .wtp-plain { font-size: .875rem; line-height: 1.65; color: var(--color-gray-700); }
    html.dark .wtp-plain { color: #d5e3c5; }
    .wtp-acts { display: grid; gap: .5rem; }
    @media (min-width: 640px) { .wtp-acts { grid-template-columns: 1fr 1fr; } }
    .wtp-saved { display: flex; align-items: center; gap: .7rem; width: 100%; text-align: left;
        padding: .8rem .9rem; border-bottom: 1px solid var(--color-gray-100); cursor: pointer; }
    .wtp-saved:hover { background: var(--color-gray-50); }
    .wtp-saved b { display: block; font-size: .88rem; color: var(--color-gray-900); }
    .wtp-saved small { color: var(--color-gray-400); font-size: .72rem; }
    .wtp-anee-face { width: 1.15rem; height: 1.15rem; border-radius: 999px; object-fit: cover; }

    /* Full screen when it lands. */
    .va-view { position: fixed; inset: 0; z-index: 90; background: var(--color-gray-50); overflow-y: auto; -webkit-overflow-scrolling: touch;
        opacity: 0; transform: translateY(12px); transition: opacity .28s cubic-bezier(.22,1,.36,1), transform .28s cubic-bezier(.22,1,.36,1); }
    .va-view.is-on { opacity: 1; transform: none; }
    .va-view-bar { position: sticky; top: 0; z-index: 2; display: flex; align-items: center; gap: .6rem; padding: .7rem .9rem;
        padding-top: max(.7rem, env(safe-area-inset-top)); background: rgb(250 250 248 / .92); backdrop-filter: blur(8px); -webkit-backdrop-filter: blur(8px);
        border-bottom: 1px solid var(--color-gray-200); }
    .va-view-bar b { flex: 1 1 auto; min-width: 0; font-size: .95rem; color: var(--color-gray-900); overflow: hidden; text-overflow: ellipsis; white-space: nowrap; }
    .va-view-x { flex: none; width: 2.2rem; height: 2.2rem; border-radius: 999px; display: inline-flex; align-items: center; justify-content: center;
        background: var(--color-white); border: 1px solid var(--color-gray-200); color: var(--color-gray-700); font-size: 1rem; cursor: pointer; }
    .va-view-body { max-width: 42rem; margin: 0 auto; padding: 1rem 1rem calc(2rem + env(safe-area-inset-bottom)); }
    html.va-view-lock { overflow: hidden; }
    html.dark .va-view { background: #0d110a; }
    html.dark .va-view-bar { background: rgb(13 17 10 / .92); border-color: #2b3a1c; }
    html.dark .va-view-bar b { color: #e8efe1; }
    html.dark .va-view-x { background: #151b12; border-color: #2b3a1c; color: #d5e3c5; }

    html.dark .wtp-tab { background: #151b12; border-color: #2b3a1c; color: #93a684; }
    html.dark .wtp-tab.is-on { background: #4a7c2a; border-color: #4a7c2a; color: #fff; }
    html.dark .wtp-quote { background: linear-gradient(115deg, #1c2913, #22301a); border-color: #2b3a1c; }
    html.dark .wtp-quote b { color: #cfe6b8; }
    html.dark .q-title { color: #cfe6b8; }
    html.dark .q-hint, html.dark .q-c { color: #a8bd93; }
    html.dark .q-card { background: rgb(255 255 255 / .05); border-color: #2b3a1c; color: #a8bd93; }
    html.dark .wtp-q { color: #e8efe1; }
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
        .wtp-card { padding: .8rem .85rem; }
        .wtp-choices.is-two { grid-template-columns: 1fr; }
        .cp-fert-row { flex-direction: column; gap: .3rem; }
        .cp-fert-row .st { width: auto; }
    }
    @media (prefers-reduced-motion: reduce) {
        .wtp-step.is-on { animation: none; }
        .wtp-run { animation: none; }
        .cp-stage, .cp-bar, .cp-yo-row .tr span, .wtp-dot { transition: none; transform: none; opacity: 1; }
        .q-body, .q-c, .q-hint, .wtp-prob, .crop-tag, .crop-row, .va-view { transition: none; }
    }
</style>

<div class="max-w-2xl mx-auto">
    <div class="wtp-tabs" role="tablist">
        <button type="button" class="wtp-tab is-on" id="cpTabGen">Generate</button>
        <button type="button" class="wtp-tab" id="cpTabSaved">Saved</button>
    </div>

    <div id="cpGen">
        <div class="wtp-quote" id="cpQuote" hidden>
            <button type="button" class="q-head" id="cpQuoteHead" aria-expanded="true">
                <span class="q-ico">📋</span>
                <span class="q-title">Before you run one</span>
                <span class="q-hint" id="cpQuoteHint"></span>
                <svg class="q-c" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.4"><path stroke-linecap="round" stroke-linejoin="round" d="M6 9l6 6 6-6"/></svg>
            </button>
            <div class="q-body">
                <div class="q-body-in">
                    <div class="q-card" id="cpQuoteCost"></div>
                    <div class="q-card">Anee <b>analyzes deeply</b> for this one — your variety's real traits, the official nutrient and pest recommendations for your crop and region, the seasonal outlook and the ENSO state — and writes a <b>season protocol by growth stage</b>: the bags of fertilizer and when, the sprays and foliars to have ready, how to run the water, what to watch for. Hung on the crop's stages, never on a day count.</div>
                </div>
            </div>
        </div>

        {{-- The crop sheet sits here, not on the 'sheets' stack: that stack prints
             after this script has run, and the script wires the sheet by id. --}}
        <div class="sheet hidden" id="cpCropSheet" style="--sheet-width:30rem">
            <div class="sheet-handle"></div>
            <div class="sheet-header">
                <h3 class="sheet-title">Choose a crop</h3>
                <button type="button" data-sheet-close class="btn-ghost p-2 rounded-full" aria-label="Close">✕</button>
            </div>
            <div class="sheet-body">
                <div class="crop-search">
                    <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path stroke-linecap="round" stroke-linejoin="round" d="M21 21l-4.35-4.35M17 11a6 6 0 11-12 0 6 6 0 0112 0z"/></svg>
                    <input type="text" id="cpCropSearch" class="form-input" autocomplete="off" placeholder="{{ \App\Support\Region::t('cropSearch') }}">
                    <button type="button" class="crop-search-x hidden" id="cpCropSearchX" aria-label="Clear">✕</button>
                </div>
                <div id="cpCropList"></div>
                <p class="crop-none hidden" id="cpCropNone">{{ \App\Support\Region::t('cropNone') }}</p>
            </div>
        </div>

        <div class="card p-5 wtp-wiz" id="cpWiz">
            {{-- 0: the place --}}
            <section class="wtp-step is-on" data-step="0">
                <p class="wtp-q">Where is the field?</p>
                <p class="wtp-sub">{{ \App\Support\Region::ph() ? 'Town and province' : ((\App\Support\Region::address()['city']['label'] ?? 'City') . ' and ' . strtolower(\App\Support\Region::address()['region']['label'] ?? 'state')) }} is enough — the climate, the outlook and the recommendations differ by region.</p>
                <input type="text" id="cpLocation" class="form-input" maxlength="160" placeholder="{{ \App\Support\Region::get('exampleLocation') }}">
            </section>
            {{-- 1: the crop --}}
            <section class="wtp-step" data-step="1">
                <p class="wtp-q">Which crop?</p>
                <p class="wtp-sub">The same catalogue your lots choose from.</p>
                <button type="button" class="crop-tag" id="cpCropBtn">
                    <span class="crop-tag-e" id="cpCropIcon">🌱</span>
                    <span class="crop-tag-t is-none" id="cpCropNow">Choose the crop</span>
                    <svg class="crop-tag-c" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.4"><path stroke-linecap="round" stroke-linejoin="round" d="M6 9l6 6 6-6"/></svg>
                </button>
            </section>
            {{-- 2: the variety --}}
            <section class="wtp-step" data-step="2">
                <p class="wtp-q">Which variety?</p>
                <p class="wtp-sub">Type it as it is sold — e.g. {{ \App\Support\Region::ph() ? 'NSIC Rc222, SL-8H' : 'Pioneer P1197, DKC64-34' }}. Leave it empty and Anee assumes a widely grown one and says which.</p>
                <input type="text" id="cpVariety" class="form-input" maxlength="80" placeholder="Variety name (optional)">
            </section>
            {{-- 3: when --}}
            <section class="wtp-step" data-step="3">
                <p class="wtp-q">When will you plant?</p>
                <p class="wtp-sub">The month decides the season, the outlook and the weather the crop will meet.</p>
                <div class="wtp-choices is-two" id="cpMonths"></div>
            </section>
            {{-- 4: the method --}}
            <section class="wtp-step" data-step="4">
                <p class="wtp-q">How will you plant it?</p>
                <p class="wtp-sub" id="cpMethodSub">The method changes the stages, the water and the first fertilizer.</p>
                <div class="wtp-choices" id="cpMethods"></div>
            </section>
            {{-- 5: the aim and the target --}}
            <section class="wtp-step" data-step="5">
                <p class="wtp-q">What are you after this season?</p>
                <p class="wtp-sub">The aim bends the rates — fuller for yield, leaner for cost.</p>
                <div class="wtp-choices" id="cpPriorities"></div>
                <label class="form-label mt-4" for="cpTarget">Target yield <span class="text-gray-400 font-normal">(optional, per hectare)</span></label>
                <div class="cp-yield">
                    <input type="number" id="cpTarget" class="form-input" min="0" step="any" inputmode="decimal" placeholder="e.g. 120">
                    <div class="cp-units" role="radiogroup" aria-label="Unit" id="cpUnits"></div>
                </div>
                <p class="form-hint">Anee will say whether it is realistic for this variety, place and season.</p>
            </section>
            {{-- 6: the field --}}
            <section class="wtp-step" data-step="6">
                <p class="wtp-q">How big is the field?</p>
                <p class="wtp-sub">The bags and the shopping list are worked out for the whole field.</p>
                <div class="cp-area">
                    <input type="number" id="cpArea" class="form-input" min="0.01" step="any" inputmode="decimal" placeholder="e.g. 1.5">
                    <span class="cp-area-u">hectares</span>
                </div>
                <p class="wtp-q mt-5">What is the soil like?</p>
                <p class="wtp-sub">As your hands know it — no test needed.</p>
                <div class="wtp-choices" id="cpSoils"></div>
                <p class="wtp-q mt-5">What water does the field get?</p>
                <div class="wtp-choices" id="cpWaters"></div>
            </section>
            {{-- 7: the troubles --}}
            <section class="wtp-step" data-step="7">
                <p class="wtp-q">What does this ground struggle with?</p>
                <p class="wtp-sub">Tick what you have seen — each one changes the protocol.</p>
                <div class="wtp-probs" id="cpProbs"></div>
                <label class="form-label mt-4" for="cpNotes">Anything else worth knowing? <span class="text-gray-400 font-normal">(optional)</span></label>
                <textarea id="cpNotes" class="form-textarea" rows="2" maxlength="400" placeholder="e.g. last season tungro hit us; we have a pump but diesel is dear"></textarea>
            </section>
            {{-- 8: the decision --}}
            <section class="wtp-step" data-step="8">
                <p class="wtp-q">Ready to run it?</p>
                <p class="wtp-sub" id="cpReview"></p>
                <button type="button" class="wtp-run" id="cpRun">
                    <svg class="w-5 h-5" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" d="M9 12h6m-6 4h6m2 5H7a2 2 0 01-2-2V5a2 2 0 012-2h5.586a1 1 0 01.707.293l5.414 5.414a1 1 0 01.293.707V19a2 2 0 01-2 2z"/></svg>
                    <span id="cpRunSays">Write the protocol</span>
                </button>
                <p class="text-xs text-gray-400 mt-2 text-center" id="cpRunFine"></p>
            </section>

            @include('sm.partials.anee-wait')

            <div class="wtp-dots" id="cpDots"></div>
            <div class="wtp-nav" id="cpNav">
                <button type="button" class="btn btn-white flex-1" id="cpBack" disabled>Back</button>
                <button type="button" class="btn btn-primary flex-1" id="cpNext">Next</button>
            </div>
        </div>

        <div class="wtp-report mt-4" id="cpReport" hidden></div>
    </div>

    <div class="va-view" id="cpView" hidden role="dialog" aria-modal="true" aria-label="Crop protocol analysis">
        <div class="va-view-bar">
            <b id="cpViewTitle">Crop protocol analysis</b>
            <button type="button" class="va-view-x" id="cpViewX" aria-label="Close">✕</button>
        </div>
        <div class="va-view-body"><div class="wtp-report" id="cpViewReport"></div></div>
    </div>

    <div id="cpSavedPane" class="hidden">
        <div class="card !p-0 overflow-hidden">
            <div id="cpSavedList"></div>
            <div id="cpSavedEmpty" class="hidden text-center py-10">
                <p class="font-bold text-gray-900">Nothing saved yet</p>
                <p class="text-sm text-gray-400">Every finished protocol lands here by itself.</p>
            </div>
        </div>
    </div>
</div>

<script>
(() => {
    const $id = (x) => document.getElementById(x);
    const esc = (s) => String(s ?? '').replace(/[&<>"']/g, (c) => ({ '&': '&amp;', '<': '&lt;', '>': '&gt;', '"': '&quot;', "'": '&#39;' }[c]));
    const U = {
        options: '{{ route('proto.options') }}',
        generate: '{{ route('proto.generate') }}',
        list: '{{ route('proto.list') }}',
        one: (id) => '{{ url('/app/crop-protocol/one') }}/' + id,
        job: (id) => '{{ url('/app/crop-protocol/job') }}/' + id,
        del: (id) => '{{ url('/app/crop-protocol') }}/' + id,
        anee: '{{ route('ai.index') }}',
    };
    const META_URL = '{{ route('proto.meta') }}';

    let OPT = null;
    const state = { location: '', crop: '', variety: '', month: null, method: null, priority: null, targetYield: '', yieldUnit: '', area: '', soil: null, water: null, problems: [], notes: '' };
    let step = 0;
    const STEPS = 9;
    const phone = () => !window.matchMedia('(min-width: 640px)').matches;

    async function boot() {
        try {
            const res = await api(U.options, { method: 'GET' });
            OPT = res.data;
            paintOptions();
        } catch (err) { toast(err.message, 'error'); }
    }

    /* A label with " — " in it is a name and its words: the words go small under the name. */
    const split = (label) => { const s = String(label); const i = s.indexOf(' — '); return i < 0 ? [s, ''] : [s.slice(0, i), s.slice(i + 3)]; };
    const choice = (attr, k, icon, label, sub) => `<button type="button" class="wtp-choice" data-${attr}="${esc(k)}"><span class="c-e">${icon}</span><span>${esc(label)}${sub ? `<small>${esc(sub)}</small>` : ''}</span></button>`;

    function paintOptions() {
        const groups = {};
        OPT.crops.forEach((c) => { (groups[c.group] = groups[c.group] || []).push(c); });
        $id('cpCropList').innerHTML = Object.entries(groups).map(([g, list]) => `
            <div class="crop-group" data-crop-group>
                <p class="crop-group-h">${esc(g)}</p>
                ${list.map((c) => `
                    <button type="button" class="crop-row" data-crop="${esc(c.key)}" data-find="${esc((c.label + ' ' + g).toLowerCase())}">
                        <span class="crop-row-e">${esc(c.icon)}</span>
                        <span class="crop-row-t"><b>${esc(c.label)}</b><small>${c.perennial ? 'Tree crop — read by its age' : (c.maturity ? c.maturity + ' days to harvest' : '')}</small></span>
                    </button>`).join('')}
            </div>`).join('');
        $id('cpMonths').innerHTML = OPT.months.map((m, i) => choice('month', m.key, '🗓️', m.label, i === 0 ? 'This month' : '')).join('');
        $id('cpPriorities').innerHTML = Object.entries(OPT.priorities).map(([k, p]) => choice('priority', k, p.icon, p.label, p.sub)).join('');
        const soilIcons = { clay: '🧱', loam: '🟤', sandy: '🏖️', silty: '🌊', rocky: '⛰️', unsure: '🤷' };
        $id('cpSoils').innerHTML = Object.entries(OPT.soils).map(([k, label]) => { const [n, s] = split(label); return choice('soil', k, soilIcons[k] || '🟫', n, s); }).join('');
        const waterIcons = { irrigated: '🚰', limited: '🚿', rainfed: '🌧️' };
        $id('cpWaters').innerHTML = Object.entries(OPT.waters).map(([k, label]) => choice('water', k, waterIcons[k] || '💧', label, '')).join('');
        $id('cpProbs').innerHTML = Object.entries(OPT.problems).map(([k, label]) => `
            <label class="wtp-prob" data-prob="${k}"><input type="checkbox" value="${k}"><span>${esc(label)}</span></label>`).join('');
        $id('cpDots').innerHTML = Array.from({ length: STEPS }, (_, i) => `<span class="wtp-dot${i === 0 ? ' is-on' : ''}"></span>`).join('');
        // The yield units are the country's (cavans and tons at home, tons and kg elsewhere); the first is the default.
        const units = Object.keys(OPT.yieldUnits || {});
        if (!state.yieldUnit || !units.includes(state.yieldUnit)) state.yieldUnit = units[0] || 'tons';
        $id('cpUnits').innerHTML = units.map((k) => `<button type="button" class="cp-unit${k === state.yieldUnit ? ' is-on' : ''}" data-unit="${esc(k)}" role="radio" aria-checked="${k === state.yieldUnit ? 'true' : 'false'}">${esc(String(OPT.yieldUnits[k]).replace(' per hectare', ''))}</button>`).join('');
        paintMethods();
        paintQuote();
    }
    /* The ways in follow the crop: rice has its three, corn goes straight
       from seed, cassava from cuttings, a mango from a grafted seedling.
       One way only is chosen for the farmer and said so. */
    function paintMethods() {
        const c = OPT.crops.find((x) => x.key === state.crop);
        const keys = (c && c.methods && c.methods.length) ? c.methods : Object.keys(OPT.methods);
        if (state.method && !keys.includes(state.method)) state.method = null;
        if (keys.length === 1) state.method = keys[0];
        $id('cpMethods').innerHTML = keys.map((k) => { const m = OPT.methods[k]; return m ? choice('method', k, m.icon, m.label, m.sub) : ''; }).join('');
        document.querySelectorAll('#cpMethods .wtp-choice').forEach((b) => b.classList.toggle('is-on', b.getAttribute('data-method') === state.method));
        $id('cpMethodSub').textContent = keys.length === 1
            ? `${c ? c.label : 'This crop'} goes in one way — tap it or just press Next.`
            : `The options follow the crop — ${c ? c.label : 'this one'} is planted in ${keys.length} ways.`;
    }

    const QUOTE_MIN_KEY = 'anee-proto-quote-min';
    let quoteMin = false;
    try { quoteMin = localStorage.getItem(QUOTE_MIN_KEY) === '1'; } catch (_) { }
    function paintQuote() {
        const q = $id('cpQuote');
        if (!OPT) return;
        if (!OPT.canUse) {
            $id('cpQuoteCost').innerHTML = esc(OPT.whyNot || 'The analysis is not available right now.');
            $id('cpQuoteHint').textContent = '';
            q.classList.remove('is-min');
            q.hidden = false;
            return;
        }
        if (!OPT.quote) { q.hidden = true; return; }
        q.classList.toggle('is-min', quoteMin);
        $id('cpQuoteHead').setAttribute('aria-expanded', quoteMin ? 'false' : 'true');
        $id('cpQuoteCost').innerHTML = `This protocol spends <b>${OPT.quote} credits</b> (the deepest of the analyses, which is why it costs the most), and you have <b>${OPT.unlimited ? '∞' : Number(OPT.balance).toLocaleString()}</b>. Nothing is charged until you press Run.`;
        $id('cpQuoteHint').textContent = `${OPT.quote} credits`;
        q.hidden = false;
    }
    $id('cpQuoteHead').addEventListener('click', () => {
        quoteMin = !quoteMin;
        try { localStorage.setItem(QUOTE_MIN_KEY, quoteMin ? '1' : '0'); } catch (_) { }
        paintQuote();
    });

    /* ---------------- the walk ---------------- */
    function show(n, backwards) {
        step = Math.max(0, Math.min(STEPS - 1, n));
        /* Leave whatever field was being typed in: on a phone the keypad
           otherwise rides along into the next step (a button tap does not
           take focus on iOS, so the hidden field would keep it). */
        try { if (document.activeElement && document.activeElement !== document.body) document.activeElement.blur(); } catch (_) { }
        document.querySelectorAll('#cpWiz .wtp-step').forEach((s) => {
            const on = Number(s.getAttribute('data-step')) === step;
            s.classList.toggle('is-on', on);
            s.classList.toggle('is-back', on && !!backwards);
        });
        document.querySelectorAll('#cpDots .wtp-dot').forEach((d, i) => d.classList.toggle('is-on', i <= step));
        $id('cpBack').disabled = step === 0;
        $id('cpNext').style.display = step === STEPS - 1 ? 'none' : '';
        if (step === 4) paintMethods();
        if (step === STEPS - 1) review();
    }
    function stepReady() {
        switch (step) {
            case 0: state.location = $id('cpLocation').value.trim();
                return !!state.location || (toast('Say where the field is.', 'error'), false);
            case 1: return !!state.crop || (toast('Choose the crop.', 'error'), false);
            case 2: state.variety = $id('cpVariety').value.trim(); return true;
            case 3: return !!state.month || (toast('Pick the month you will plant.', 'error'), false);
            case 4: return !!state.method || (toast('Say how you will plant it.', 'error'), false);
            case 5: state.targetYield = $id('cpTarget').value.trim();
                return !!state.priority || (toast('Say what you are after this season.', 'error'), false);
            case 6: state.area = $id('cpArea').value.trim();
                if (!(Number(state.area) > 0)) { toast('How big is the field, in hectares?', 'error'); if (!phone()) $id('cpArea').focus(); return false; }
                if (!state.soil) { toast('Pick the soil that sounds most like yours.', 'error'); return false; }
                if (!state.water) { toast('Say what water the field gets.', 'error'); return false; }
                return true;
            case 7: state.problems = [...document.querySelectorAll('#cpProbs input:checked')].map((i) => i.value);
                state.notes = $id('cpNotes').value.trim(); return true;
            default: return true;
        }
    }
    function review() {
        const crop = OPT.crops.find((c) => c.key === state.crop) || {};
        const month = (OPT.months.find((m) => m.key === state.month) || {}).label || '';
        const target = state.targetYield ? `${state.targetYield} ${state.yieldUnit}/ha` : 'no target';
        $id('cpReview').innerHTML = `${esc(crop.icon || '🌱')} <b>${esc(crop.label || '')}</b>${state.variety ? ' · ' + esc(state.variety) : ''} · 📍 ${esc(state.location)}`
            + `<br><span class="text-xs">${esc(month)} · ${esc(OPT.methods[state.method]?.label || '')} · ${esc(OPT.priorities[state.priority]?.label || '')} · ${esc(target)}</span>`
            + `<br><span class="text-xs">${esc(state.area)} ha · ${esc(split(OPT.soils[state.soil] || '')[0])} · ${esc(OPT.waters[state.water] || '')}${state.problems.length ? ' · ' + state.problems.length + ' trouble' + (state.problems.length === 1 ? '' : 's') : ''}</span>`;
        $id('cpRunSays').textContent = OPT.canUse && OPT.quote ? `Write the protocol (${OPT.quote} credits)` : 'Write the protocol';
        $id('cpRunFine').textContent = OPT.canUse
            ? 'Anee analyzes this one deeply — a few minutes. Charged to the same AI credits your questions use.'
            : (OPT.whyNot || '');
        $id('cpRun').disabled = !OPT.canUse;
    }
    $id('cpNext').addEventListener('click', () => { if (stepReady()) show(step + 1); });
    $id('cpBack').addEventListener('click', () => show(step - 1, true));
    const pickWire = (hostId, attr, key, next) => {
        $id(hostId).addEventListener('click', (e) => {
            const b = e.target.closest(`[data-${attr}]`);
            if (!b) return;
            state[key] = b.getAttribute(`data-${attr}`);
            document.querySelectorAll(`#${hostId} .wtp-choice`).forEach((c) => c.classList.toggle('is-on', c === b));
            if (next !== null) setTimeout(() => show(next), 180);
        });
    };
    pickWire('cpMonths', 'month', 'month', 4);
    pickWire('cpMethods', 'method', 'method', 5);
    pickWire('cpPriorities', 'priority', 'priority', null);
    pickWire('cpSoils', 'soil', 'soil', null);
    pickWire('cpWaters', 'water', 'water', null);
    $id('cpUnits').addEventListener('click', (e) => {
        const b = e.target.closest('[data-unit]');
        if (!b) return;
        state.yieldUnit = b.getAttribute('data-unit');
        document.querySelectorAll('.cp-unit').forEach((u) => { u.classList.toggle('is-on', u === b); u.setAttribute('aria-checked', u === b ? 'true' : 'false'); });
    });
    const PROB_FOES = { acidic: ['alkaline'], alkaline: ['acidic'], drought: ['floods'], floods: ['drought'] };
    $id('cpProbs').addEventListener('change', (e) => {
        const l = e.target.closest('.wtp-prob');
        if (l) l.classList.toggle('is-on', e.target.checked);
        if (!e.target.checked) return;
        (PROB_FOES[e.target.value] || []).forEach((k) => {
            const foe = document.querySelector(`#cpProbs input[value="${k}"]`);
            if (foe && foe.checked) { foe.checked = false; foe.closest('.wtp-prob')?.classList.remove('is-on'); }
        });
    });

    /* ---- the crop ---- */
    $id('cpCropBtn').addEventListener('click', () => {
        openSheet('cpCropSheet');
        if (!phone()) setTimeout(() => $id('cpCropSearch')?.focus(), 280);
    });
    $id('cpCropSheet').addEventListener('click', (e) => {
        const row = e.target.closest('.crop-row');
        if (!row) return;
        state.crop = row.getAttribute('data-crop');
        const c = OPT.crops.find((x) => x.key === state.crop) || {};
        $id('cpCropIcon').textContent = c.icon || '🌱';
        const now = $id('cpCropNow');
        now.textContent = c.label || 'Choose the crop';
        now.classList.remove('is-none');
        closeSheet('cpCropSheet');
        setTimeout(() => show(2), 220);
    });
    const cropSift = () => {
        const q = ($id('cpCropSearch').value || '').trim().toLowerCase();
        $id('cpCropSearchX').classList.toggle('hidden', !q);
        let shown = 0;
        document.querySelectorAll('#cpCropList [data-crop-group]').forEach((g) => {
            let left = 0;
            g.querySelectorAll('.crop-row').forEach((r) => { const hit = !q || (r.getAttribute('data-find') || '').includes(q); r.hidden = !hit; if (hit) left++; });
            g.hidden = left === 0;
            shown += left;
        });
        $id('cpCropNone').classList.toggle('hidden', shown > 0);
    };
    $id('cpCropSearch').addEventListener('input', cropSift);
    $id('cpCropSearchX').addEventListener('click', () => { $id('cpCropSearch').value = ''; cropSift(); $id('cpCropSearch').focus(); });

    /* ---------------- the run ---------------- */
    $id('cpRun').addEventListener('click', async () => {
        if (!stepReady()) return;
        const wiz = $id('cpWiz');
        wiz.querySelectorAll('.wtp-step, .wtp-nav, .wtp-dots').forEach((el) => el.style.display = 'none');
        window.aneeWait.show({ title: 'Anee is writing your protocol…', lines: ['Reading up on the variety\'s real traits…', 'Reading the official nutrient and pest guidance for your region…', 'Checking the seasonal outlook and the ENSO state…', 'Working out the bags, the water and the watch-list, stage by stage…', 'Adding up what to prepare for the whole field…'], sub: 'A few minutes — this is the deepest of the analyses.' });
        $id('cpReport').hidden = true;
        let landed = false;
        try {
            const res = await api(U.generate, { method: 'POST', body: {
                location: state.location, crop: state.crop, variety: state.variety, month: state.month, method: state.method,
                priority: state.priority, targetYield: state.targetYield || null, yieldUnit: state.yieldUnit, area: state.area,
                soil: state.soil, water: state.water, problems: state.problems, notes: state.notes,
            } });
            let data = res.data;
            if (data.pending) {
                // The shared poll: the bar, the clock and the hang check ride along.
                data = await window.aneeWait.poll({ id: data.id || res.data.id, job: U.job, phases: window.aneeWait.phases.research });
            }
            OPT.balance = data.balance;
            landed = true;
            const item = { report: data.report, params: data.params, charged: data.charged, savedId: data.savedId };
            drawReport($id('cpReport'), item, 'fresh', true);
            openView(item, 'fresh');
            await window.aneeWait.done({ title: 'Done!', line: `${data.charged} credits used — saved to the shelf.` });
            toast(`Done — ${data.charged} credits used. Saved to the shelf.`);
        } catch (err) {
            toast(err.message, 'error');
        } finally {
            if (!landed) window.aneeWait.fail();
            wiz.querySelectorAll('.wtp-step, .wtp-nav, .wtp-dots').forEach((el) => el.style.display = '');
            show(step);
            if (landed) { wiz.hidden = true; $id('cpQuote').hidden = true; }
        }
    });

    /* Full screen when it lands; the page's copy underneath. */
    function openView(item, mode) {
        const view = $id('cpView');
        const crop = (OPT ? OPT.crops.find((c) => c.key === (item.params || {}).crop) : null) || {};
        $id('cpViewTitle').textContent = (crop.label ? crop.label + ' — ' : '') + 'crop protocol analysis';
        const host = $id('cpViewReport');
        host.classList.remove('is-drawn');
        drawReport(host, item, mode, true);
        view.hidden = false;
        document.documentElement.classList.add('va-view-lock');
        view.scrollTop = 0;
        requestAnimationFrame(() => requestAnimationFrame(() => { view.classList.add('is-on'); host.classList.add('is-drawn'); }));
    }
    function closeView() {
        const view = $id('cpView');
        if (view.hidden) return;
        view.classList.remove('is-on');
        document.documentElement.classList.remove('va-view-lock');
        setTimeout(() => { view.hidden = true; $id('cpViewReport').innerHTML = ''; }, 300);
    }
    $id('cpViewX').addEventListener('click', closeView);
    document.addEventListener('keydown', (e) => { if (e.key === 'Escape') closeView(); });

    function wizardBack() {
        $id('cpWiz').hidden = false;
        if (OPT && OPT.canUse && OPT.quote) $id('cpQuote').hidden = false;
        $id('cpReport').hidden = true;
        $id('cpReport').classList.remove('is-drawn');
        show(0);
        $id('cpWiz').scrollIntoView({ behavior: 'smooth', block: 'start' });
    }

    /* ---------------- the report, drawn ---------------- */
    const host = (u) => { try { const h = new URL(u).hostname.replace(/^www\./, ''); return /vertexaisearch\.cloud\.google\.com$/.test(h) ? '' : h; } catch (_) { return ''; } };
    const list = (xs) => (Array.isArray(xs) ? xs : []).filter((x) => x && String(x).trim());
    const PALETTE = ['#4a7c2a', '#8fc96a', '#b45309', '#2563eb', '#7c3aed', '#db2777', '#0891b2', '#65a30d'];
    const trimN = (n) => { const v = Number(n) || 0; return Number.isInteger(v) ? String(v) : String(Math.round(v * 10) / 10); };
    const numOf = (s) => { const m = String(s || '').replace(/,/g, '').match(/(\d+(\.\d+)?)/); return m ? Number(m[1]) : null; };

    /* Two shapes live on the shelf: protocols written before 2026-09-18
       (stages + fertilizer program + protection lists) and the two-part
       protocol (background + recommendation). Each is drawn by its own
       hand; the tail (attach, again, delete) is shared. */
    function drawReport(hostEl, item, mode, quiet) {
        const r = item.report || {};
        if (r.recommendation && r.background) drawV2(hostEl, item, mode);
        else drawV1(hostEl, item, mode);
        finishDraw(hostEl, item, mode, quiet);
    }

    function drawV1(hostEl, item, mode) {
        const r = item.report || {};
        const p = item.params || {};
        const sweep = (t) => String(t || '').replace(/:[a-z0-9_-]+:/gi, '').replace(/\s{2,}/g, ' ').trim();
        const crop = (OPT ? OPT.crops.find((c) => c.key === p.crop) : null) || {};
        const month = (OPT ? (OPT.months.find((m) => m.key === p.month) || {}).label : '') || p.month || '';
        const A = r.assumed || {};
        const fert = r.fertilizer || {};
        const prog = fert.program || [];
        const names = [...new Set(prog.flatMap((s) => (s.products || []).map((x) => x.name || 'Fertilizer')))];
        const colour = (n) => PALETTE[Math.max(0, names.indexOf(n)) % PALETTE.length];
        const maxBags = Math.max(0.1, ...prog.map((s) => Number(s.bags) || 0));
        const prot = r.protection || {};
        const yo = r.yieldOutlook || {};
        const tN = numOf(yo.target), rN = numOf(yo.realistic);
        const yMax = Math.max(tN || 0, rN || 0, 1);

        hostEl.innerHTML = `
            <div class="wtp-hero">
                <h2>${esc(crop.icon || '🌱')} ${esc(crop.label || 'Your crop')}${p.variety ? ' · ' + esc(p.variety) : (A.variety ? ' · ' + esc(A.variety) : '')}</h2>
                <p class="h-win">${esc(r.headline || 'Your season, stage by stage')}</p>
                <p class="h-why">${esc(sweep(A.note || ''))}</p>
                <div class="wtp-chips">
                    <span class="wtp-chip">📍 ${esc(p.location || '')}</span>
                    <span class="wtp-chip">🗓️ ${esc(month)}</span>
                    <span class="wtp-chip">${esc(OPT?.methods?.[p.method]?.label || p.method || '')}</span>
                    <span class="wtp-chip">${esc(trimN(p.area))} ha</span>
                    ${A.maturityDays ? `<span class="wtp-chip">⏱️ ${esc(A.maturityDays)}</span>` : ''}
                    <span class="wtp-chip">${esc(OPT?.priorities?.[p.priority]?.label || '')}</span>
                    <span class="wtp-chip">Confidence: ${esc(r.confidence || 'moderate')}</span>
                    ${item.charged ? `<span class="wtp-chip">${item.charged} credits</span>` : ''}
                </div>
            </div>

            <div class="cp-clock"><span class="e">👁️</span><span><b>The crop is the clock, not the calendar.</b> Every step below is hung on a growth stage and the signs you can see in the field. The "about N weeks" hints are only hints — go by the stage you actually see, look every few days, and act on what the plants and the pests are doing. That is precision farming.</span></div>

            ${(r.stages || []).length ? `
            <div class="wtp-card">
                <h3>The season, stage by stage <small>what to do when the crop gets there</small></h3>
                <div class="cp-stages">
                    ${(r.stages || []).map((s, i) => `
                    <div class="cp-stage" style="transition-delay:${i * 60}ms">
                        <span class="cp-stage-n">${i + 1}</span>
                        <span class="cp-stage-b">
                            <b>${esc(s.stage || '')}</b>
                            ${s.signs ? `<div class="signs">${esc(sweep(s.signs))}</div>` : ''}
                            ${s.hint && s.hint !== 'n/a' ? `<div class="hint">${esc(s.hint)}</div>` : ''}
                            ${list(s.tasks).length ? `<ul>${list(s.tasks).map((t) => `<li>${esc(sweep(t))}</li>`).join('')}</ul>` : ''}
                        </span>
                    </div>`).join('')}
                </div>
            </div>` : ''}

            <div class="wtp-card">
                <h3>Fertilizer <small>50-kg bags for the whole field (${esc(trimN(p.area))} ha) — ${esc(trimN(fert.totalBags || 0))} bags in all</small></h3>
                ${prog.length ? `
                <div class="cp-chart">
                    ${prog.map((s) => `<div class="cp-col"><span class="cp-col-val">${esc(trimN(s.bags))}</span><div class="cp-bar" style="height:${Math.max(4, Math.round(((Number(s.bags) || 0) / maxBags) * 100))}%">${(s.products || []).map((x) => `<span class="cp-seg" style="flex:${Math.max(0.01, Number(x.totalBags) || 0)};background:${colour(x.name || 'Fertilizer')}" title="${esc(x.name)} · ${esc(trimN(x.totalBags))} bags"></span>`).join('')}</div></div>`).join('')}
                </div>
                <div class="cp-lbls">${prog.map((s) => `<span class="cp-lbl">${esc(s.stage || '')}</span>`).join('')}</div>
                <div class="cp-legend">${names.map((n) => `<span><i style="background:${colour(n)}"></i>${esc(n)}</span>`).join('')}</div>
                <div class="cp-fert">
                    ${prog.map((s) => `
                    <div class="cp-fert-row">
                        <span class="st"><b>${esc(s.stage || '')}</b><i>${esc(s.timing || '')}</i></span>
                        <span class="pr">${(s.products || []).map((x) => `<div><b>${esc(trimN(x.totalBags))} ${Number(x.totalBags) === 1 ? 'bag' : 'bags'} ${esc(x.name || '')}</b> <small>(${esc(trimN(x.bagsPerHa))}/ha)</small>${x.why ? ' — ' + esc(sweep(x.why)) : ''}</div>`).join('')}${s.note ? `<div><small>${esc(sweep(s.note))}</small></div>` : ''}</span>
                    </div>`).join('')}
                </div>
                <div class="cp-totals">${(fert.totals || []).map((t) => `<span class="cp-total">${esc(trimN(t.bags))} bags <i>${esc(t.name)}</i></span>`).join('')}</div>
                ${fert.note ? `<p class="cp-note">${esc(sweep(fert.note))}</p>` : ''}` : '<p class="wtp-plain">No fertilizer program came back.</p>'}
            </div>

            ${((prot.insects || []).length || (prot.diseases || []).length || (prot.weeds || []).length) ? `
            <div class="wtp-card">
                <h3>Crop protection <small>scout first, spray only past the threshold — and keep these on hand</small></h3>
                <div class="cp-prot">
                    ${(prot.insects || []).map((x) => `<div class="cp-pc is-insect"><span class="tag">Insect · ${esc(x.stage || '')}</span><b>${esc(x.pest || '')}</b>${x.watchFor ? `<p><em>Look for:</em> ${esc(sweep(x.watchFor))}</p>` : ''}${x.threshold ? `<p><em>Act when:</em> ${esc(sweep(x.threshold))}</p>` : ''}${x.action ? `<p><em>Do:</em> ${esc(sweep(x.action))}</p>` : ''}${x.prepare ? `<p><em>Prepare:</em> ${esc(sweep(x.prepare))}</p>` : ''}</div>`).join('')}
                    ${(prot.diseases || []).map((x) => `<div class="cp-pc is-disease"><span class="tag">Disease · ${esc(x.stage || '')}</span><b>${esc(x.disease || '')}</b>${x.watchFor ? `<p><em>Look for:</em> ${esc(sweep(x.watchFor))}</p>` : ''}${x.action ? `<p><em>Do:</em> ${esc(sweep(x.action))}</p>` : ''}${x.prepare ? `<p><em>Prepare:</em> ${esc(sweep(x.prepare))}</p>` : ''}</div>`).join('')}
                    ${(prot.weeds || []).map((x) => `<div class="cp-pc is-weed"><span class="tag">Weed · ${esc(x.when || '')}</span><b>${esc(x.weed || '')}</b>${x.control ? `<p>${esc(sweep(x.control))}</p>` : ''}</div>`).join('')}
                </div>
            </div>` : ''}

            ${(r.foliar || []).length ? `
            <div class="wtp-card">
                <h3>Foliars & extras <small>the little top-ups, and whether they pay</small></h3>
                ${(r.foliar || []).map((f) => `<div class="wtp-win-row is-note"><b>${esc(f.product || '')}${f.stage ? ' · ' + esc(f.stage) : ''}${f.optional ? ' · optional' : ''}:</b> <span>${esc(sweep(f.why))}</span></div>`).join('')}
            </div>` : ''}

            ${(r.irrigation || []).length ? `
            <div class="wtp-card">
                <h3>Water <small>with ${esc((OPT?.waters?.[p.water] || '').toLowerCase())}</small></h3>
                <div class="cp-water">
                    ${(r.irrigation || []).map((w) => `<div class="cp-wrow"><span class="e">💧</span><span class="t"><b>${esc(w.stage || '')}</b>${esc(sweep(w.need || ''))}${w.how ? ' — ' + esc(sweep(w.how)) : ''}${w.ifDry ? `<i>If it turns dry: ${esc(sweep(w.ifDry))}</i>` : ''}${w.ifWet ? `<i>If it turns wet: ${esc(sweep(w.ifWet))}</i>` : ''}</span></div>`).join('')}
                </div>
            </div>` : ''}

            ${r.weather ? `
            <div class="wtp-card">
                <h3>The sky this season</h3>
                <p class="wtp-plain">${esc(sweep(r.weather.outlook || ''))}</p>
                ${r.weather.enso ? `<p class="wtp-plain mt-2"><b>ENSO:</b> ${esc(sweep(r.weather.enso))}</p>` : ''}
                ${list(r.weather.risks).length ? `<div class="mt-3">${list(r.weather.risks).map((x) => `<div class="wtp-win-row is-no"><b>⚠️</b> <span>${esc(x)}</span></div>`).join('')}</div>` : ''}
            </div>` : ''}

            <div class="wtp-card">
                <h3>Yield <small>your target against what this variety realistically gives here</small></h3>
                <div class="cp-yo">
                    ${tN ? `<div class="cp-yo-row"><small>Target</small><div class="tr"><span class="is-target" style="width:${Math.round((tN / yMax) * 100)}%"></span></div><b>${esc(yo.target || '')}</b></div>` : ''}
                    ${rN ? `<div class="cp-yo-row"><small>Realistic</small><div class="tr"><span style="width:${Math.round((rN / yMax) * 100)}%"></span></div><b>${esc(yo.realistic || '')}</b></div>` : ''}
                </div>
                ${yo.note ? `<p class="cp-note">${esc(sweep(yo.note))}</p>` : ''}
                ${!tN && !rN ? `<p class="wtp-plain">${esc(sweep(yo.realistic || yo.note || ''))}</p>` : ''}
            </div>

            ${(r.prepare || []).length ? `
            <div class="wtp-card">
                <h3>What to prepare <small>for the whole field, and when it is needed</small></h3>
                <div class="cp-shop">
                    ${(r.prepare || []).map((x) => `<div class="cp-item"><span class="n">${esc(x.item || '')}<small>${esc(x.whenNeeded || '')}</small></span><span class="q">${esc(x.qty != null ? trimN(x.qty) : '')} ${esc(x.unit || '')}${x.estCost ? `<small>${esc(x.estCost)}</small>` : ''}</span></div>`).join('')}
                </div>
                ${r.prepareCost ? `<p class="cp-note"><b>${esc(r.prepareCost.sum)}</b> for the ${r.prepareCost.known === r.prepareCost.of ? 'whole list' : r.prepareCost.known + ' of ' + r.prepareCost.of + ' items with a price'} — rough shop prices Anee found; yours will differ.</p>` : ''}
            </div>` : ''}

            ${list(r.watch).length ? `
            <div class="wtp-card">
                <h3>Keep an eye on <small>the things that go wrong here, and the sign to act on</small></h3>
                <ul class="cp-watch">${list(r.watch).map((x) => `<li>${esc(sweep(x))}</li>`).join('')}</ul>
            </div>` : ''}

            <div class="wtp-card">
                <h3>In plain words</h3>
                <p class="wtp-plain">${esc(sweep(r.summary))}</p>
                ${(r.dataGaps || []).length ? `<h3 class="mt-4">What this protocol could not verify</h3><ul class="wtp-gap">${(r.dataGaps || []).map((g) => `<li>${esc(g)}</li>`).join('')}</ul>` : ''}
            </div>

            ${(r.webSources || []).length ? `
            <div class="wtp-card">
                <h3>📚 Other Sources in Analysis</h3>
                <div class="va-links">${(() => { const seen = new Set(); return (r.webSources || []).map((x) => ({ name: x.title || host(x.url) || 'A published source', h: host(x.url) })).filter((x) => { const k = x.name.toLowerCase(); if (seen.has(k)) return false; seen.add(k); return true; }).map((x) => `<span class="va-link is-plain"><span class="l-t">${esc(x.name)}</span>${x.h && x.h !== x.name ? `<span class="l-h">${esc(x.h)}</span>` : ''}</span>`).join(''); })()}</div>
            </div>` : ''}

            <div class="wtp-card">
                <h3>🧭 A guide, not a promise</h3>
                <p class="wtp-fine">This is a starting protocol, not a prescription: the rates follow the official recommendations bent to your answers, and the sprays are what to have ready, not what to pour on a date. What makes the season is observation — walk the field, read the plants, count the pests, and use each step when the crop reaches its stage. Ask {{ \App\Support\Region::t('extensionOffice') }} to confirm the products registered for your area.</p>
            </div>

            <div class="wtp-acts">
                <button type="button" class="btn btn-primary w-full" data-cp-attach>${OPT && OPT.aneeFace ? `<img class="wtp-anee-face" src="${esc(OPT.aneeFace)}" alt="">` : '🤖'} Attach to Anee</button>
                ${mode === 'fresh' ? `<button type="button" class="btn btn-white w-full" data-cp-again>📋 Write another protocol</button>` : ''}
                <button type="button" class="btn btn-white w-full" data-cp-delete>🗑 Delete</button>
            </div>`;

    }

    /* ---- the shared tail: the actions under either drawing ---- */
    const actionsHtml = (mode) => `
            <div class="wtp-acts">
                <button type="button" class="btn btn-primary w-full" data-cp-attach>${OPT && OPT.aneeFace ? `<img class="wtp-anee-face" src="${esc(OPT.aneeFace)}" alt="">` : '🤖'} Attach to Anee</button>
                ${mode === 'fresh' ? `<button type="button" class="btn btn-white w-full" data-cp-again>📋 Write another protocol</button>` : ''}
                <button type="button" class="btn btn-white w-full" data-cp-delete>🗑 Delete</button>
            </div>`;
    const sourcesHtml = (r) => (r.webSources || []).length ? `
            <div class="wtp-card">
                <h3>📚 Other Sources in Analysis</h3>
                <div class="va-links">${(() => { const seen = new Set(); return (r.webSources || []).map((x) => ({ name: x.title || host(x.url) || 'A published source', h: host(x.url) })).filter((x) => { const k = x.name.toLowerCase(); if (seen.has(k)) return false; seen.add(k); return true; }).map((x) => `<span class="va-link is-plain"><span class="l-t">${esc(x.name)}</span>${x.h && x.h !== x.name ? `<span class="l-h">${esc(x.h)}</span>` : ''}</span>`).join(''); })()}</div>
            </div>` : '';

    /* ---- THE TWO-PART PROTOCOL: background, then the recommendation ---- */
    function drawV2(hostEl, item, mode) {
        const r = item.report || {};
        const p = item.params || {};
        const sweep = (t) => String(t || '').replace(/:[a-z0-9_-]+:/gi, '').replace(/\s{2,}/g, ' ').trim();
        const crop = (OPT ? OPT.crops.find((c) => c.key === p.crop) : null) || {};
        const month = (OPT ? (OPT.months.find((m) => m.key === p.month) || {}).label : '') || p.month || '';
        const bg = r.background || {};
        const wx = bg.weather || {};
        const v = bg.variety || {};
        const rec = r.recommendation || {};
        const stages = rec.stages || [];
        const names = [...new Set(stages.flatMap((st) => (st.fertilizer || []).map((x) => x.product || 'Fertilizer')))];
        const colour = (n) => PALETTE[Math.max(0, names.indexOf(n)) % PALETTE.length];
        const maxBags = Math.max(0.1, ...stages.map((st) => Number(st.bags) || 0));
        const totals = rec.totals || [];
        const maxTotal = Math.max(0.1, ...totals.map((t) => Number(t.bags) || 0));
        const yo = rec.yield || {};
        const tN = numOf(yo.target), rN = numOf(yo.realistic);
        const yMax = Math.max(tN || 0, rN || 0, 1);
        const npk = rec.npk || {};
        const water = rec.water || {};
        const firstFert = Math.max(0, stages.findIndex((st) => (st.fertilizer || []).length));
        const norm = (t) => String(t || '').toLowerCase().replace(/[^a-z0-9]+/g, ' ').trim();
        const sameStage = (a, b) => { const x = norm(a), y = norm(b); return !!x && !!y && (x === y || x.includes(y) || y.includes(x)); };

        hostEl.innerHTML = `
            <div class="wtp-hero">
                <h2>${esc(crop.icon || '🌱')} ${esc(crop.label || 'Your crop')}${(p.variety || v.name) ? ' · ' + esc(p.variety || v.name) : ''}</h2>
                <p class="h-win">${esc(r.headline || 'Your season, stage by stage')}</p>
                <div class="wtp-chips">
                    <span class="wtp-chip">📍 ${esc(p.location || '')}</span>
                    <span class="wtp-chip">🗓️ ${esc(month)}</span>
                    <span class="wtp-chip">${esc(OPT?.methods?.[p.method]?.label || p.method || '')}</span>
                    <span class="wtp-chip">${esc(trimN(p.area))} ha</span>
                    ${Number(v.maturityDays) > 0 ? `<span class="wtp-chip">⏱️ ${esc(String(Math.round(Number(v.maturityDays))))} days</span>` : ''}
                    <span class="wtp-chip">${esc(OPT?.priorities?.[p.priority]?.label || '')}</span>
                    <span class="wtp-chip">Confidence: ${esc(r.confidence || 'moderate')}</span>
                    ${item.charged ? `<span class="wtp-chip">${item.charged} credits</span>` : ''}
                </div>
            </div>

            <div class="cp2-part"><span class="cp2-part-n">1</span><span><b>Background</b><small>the place, the weather ahead, the variety</small></span></div>

            <div class="wtp-card cp2-bg">
                <div class="cp2-bg-row"><span class="cp2-bg-e">📍</span><div><b>The place</b><p>${esc(sweep(bg.place))}</p>${bg.field ? `<p class="cp2-dim">${esc(sweep(bg.field))}</p>` : ''}</div></div>
                <div class="cp2-bg-row"><span class="cp2-bg-e">🌤️</span><div><b>The weather ahead</b><p>${esc(sweep(wx.outlook))}</p>${wx.enso ? `<p class="cp2-dim">${esc(sweep(wx.enso))}</p>` : ''}
                    ${list(wx.risks).length ? `<div class="cp2-pills">${list(wx.risks).map((x) => `<span class="cp2-pill is-risk">⚠️ ${esc(sweep(x))}</span>`).join('')}</div>` : ''}</div></div>
                <div class="cp2-bg-row"><span class="cp2-bg-e">🧬</span><div><b>${esc(v.name || p.variety || 'The variety')}${v.by ? ` <small>by ${esc(v.by)}${v.released ? ', ' + esc(v.released) : ''}</small>` : ''}</b>
                    ${v.found === false ? `<p class="cp2-dim">Not found online as published — the numbers below assume this variety.</p>` : ''}
                    <div class="cp2-pills">
                        ${Number(v.maturityDays) > 0 ? `<span class="cp2-pill">⏱ ${esc(String(Math.round(Number(v.maturityDays))))} days to maturity</span>` : ''}
                        ${v.yieldPotential ? `<span class="cp2-pill">🌾 ${esc(v.yieldPotential)}</span>` : ''}
                        ${v.season ? `<span class="cp2-pill">🗓️ ${esc(v.season)}</span>` : ''}
                        ${v.source ? `<span class="cp2-pill is-src">📚 ${esc(v.source)}</span>` : ''}
                    </div>
                    ${v.traits ? `<p>${esc(sweep(v.traits))}</p>` : ''}
                    ${v.caution ? `<p class="wp-watch">⚠️ ${esc(sweep(v.caution))}</p>` : ''}
                </div></div>
            </div>

            <div class="cp2-part"><span class="cp2-part-n">2</span><span><b>Recommendation</b><small>what to apply at each stage, and why</small></span></div>

            ${rec.intro ? `<div class="wtp-card"><p class="wtp-plain">${esc(sweep(rec.intro))}</p></div>` : ''}

            <div class="cp-clock"><span class="e">👁️</span><span><b>The crop is the clock, not the calendar.</b> Tap a stage below to see what goes on then, and why.</span></div>

            ${stages.length ? `
            <div class="wtp-card" data-cp2-stages data-sel="${firstFert}">
                <h3>Fertilizer by growth stage <small>50-kg bags for the whole field (${esc(trimN(p.area))} ha) — ${esc(trimN(rec.totalBags || 0))} bags in all</small></h3>
                <div class="cp-chart cp2-chart">
                    ${stages.map((st, i) => `<button type="button" class="cp-col cp2-col" data-cp2-stage="${i}" aria-label="${esc(st.stage || '')}"><span class="cp-col-val">${Number(st.bags) > 0 ? esc(trimN(st.bags)) : '·'}</span><div class="cp-bar" style="height:${Math.max(3, Math.round(((Number(st.bags) || 0) / maxBags) * 100))}%">${(st.fertilizer || []).map((x) => `<span class="cp-seg" style="height:${Math.max(0, ((Number(x.totalBags) || 0) / Math.max(0.1, Number(st.bags) || 0)) * 100)}%;background:${colour(x.product || 'Fertilizer')}"></span>`).join('')}</div></button>`).join('')}
                </div>
                <div class="cp-lbls cp2-lbls">${stages.map((st, i) => `<span class="cp-lbl" data-cp2-lbl="${i}">${i + 1}</span>`).join('')}</div>
                ${names.length ? `<div class="cp-legend">${names.map((n) => `<span><i style="background:${colour(n)}"></i>${esc(n)}</span>`).join('')}</div>` : ''}
                <div class="cp2-detail" data-cp2-detail></div>
                <div class="cp2-rows">
                    ${stages.map((st, i) => `
                    <button type="button" class="cp2-row" data-cp2-stage="${i}">
                        <span class="cp2-row-n">${i + 1}</span>
                        <span class="cp2-row-t"><b>${esc(st.stage || '')}</b><small>${(st.fertilizer || []).length ? (st.fertilizer || []).map((x) => esc(trimN(x.totalBags)) + ' ' + esc(x.product || '')).join(' · ') : (st.observe ? 'watch' : 'no inputs')}</small></span>
                        <span class="cp2-row-b">${Number(st.bags) > 0 ? esc(trimN(st.bags)) + ' bags' : ''}</span>
                    </button>`).join('')}
                </div>
            </div>` : ''}

            ${(totals.length || (rec.supplies || []).length) ? `
            <div class="wtp-card">
                <h3>Totals to use <small>for the whole field — quantities only</small></h3>
                ${totals.length ? `<div class="cp2-tot">
                    ${totals.map((t) => `<div class="cp2-tot-row"><span class="n"><i style="background:${colour(t.product)}"></i>${esc(t.product)}</span><div class="tr"><span style="width:${Math.max(2, Math.round((Number(t.bags) / maxTotal) * 100))}%;background:${colour(t.product)}"></span></div><b>${esc(trimN(t.bags))} bags</b><small>${esc(trimN(t.bagsPerHa))}/ha</small></div>`).join('')}
                </div>` : ''}
                ${(npk.n || npk.p || npk.k) ? `<div class="cp2-npk"><span>N <b>${esc(trimN(npk.n))}</b></span><span>P₂O₅ <b>${esc(trimN(npk.p))}</b></span><span>K₂O <b>${esc(trimN(npk.k))}</b></span><small>kg per hectare for the season</small></div>${npk.note ? `<p class="cp-note">${esc(sweep(npk.note))}</p>` : ''}` : ''}
                ${(rec.supplies || []).length ? `<div class="cp-shop cp2-shop">
                    ${(rec.supplies || []).map((x) => `<div class="cp-item"><span class="n">${esc(x.item || '')}<small>${esc(x.when || '')}</small></span><span class="q">${esc(x.qty != null && x.qty !== '' ? trimN(x.qty) : '')} ${esc(x.unit || '')}</span></div>`).join('')}
                </div>` : ''}
            </div>` : ''}

            ${(rec.threats || []).length ? `
            <div class="wtp-card">
                <h3>Threats to check <small>the sign to act on, and the action</small></h3>
                <div class="cp2-threats">
                    ${(rec.threats || []).map((t) => `<div class="cp2-threat"><span class="tag">${esc(t.stage || '')}</span><b>${esc(t.threat || '')}</b>${t.sign ? `<p><em>Look for:</em> ${esc(sweep(t.sign))}</p>` : ''}${t.action ? `<p><em>Then:</em> ${esc(sweep(t.action))}</p>` : ''}${t.product ? `<p class="is-prod"><em>Use:</em> ${esc(sweep(t.product))}</p>` : ''}</div>`).join('')}
                </div>
            </div>` : ''}

            ${(rec.foliars || []).length ? `
            <div class="wtp-card">
                <h3>Foliar sprays <small>only where they pay on this ground</small></h3>
                <div class="cp2-foliars">
                    ${(rec.foliars || []).map((f) => `<div class="cp2-foliar"><span class="e">🍃</span><div><small>${esc(f.stage || '')}</small><b>${esc(f.product || '')}</b>${f.why ? `<p>${esc(sweep(f.why))}</p>` : ''}</div></div>`).join('')}
                </div>
            </div>` : ''}

            ${water.plan ? `
            <div class="wtp-card">
                <h3>Water <small>with ${esc((OPT?.waters?.[p.water] || '').toLowerCase())}</small></h3>
                <p class="wtp-plain">${esc(sweep(water.plan))}</p>
                <div class="cp2-pills">${water.ifDry ? `<span class="cp2-pill">☀️ If dry: ${esc(sweep(water.ifDry))}</span>` : ''}${water.ifWet ? `<span class="cp2-pill">🌧️ If wet: ${esc(sweep(water.ifWet))}</span>` : ''}</div>
            </div>` : ''}

            ${(tN || rN || yo.note) ? `
            <div class="wtp-card">
                <h3>Yield <small>your target against what this variety realistically gives here</small></h3>
                <div class="cp-yo">
                    ${tN ? `<div class="cp-yo-row"><small>Target</small><div class="tr"><span class="is-target" style="width:${Math.round((tN / yMax) * 100)}%"></span></div><b>${esc(yo.target || '')}</b></div>` : ''}
                    ${rN ? `<div class="cp-yo-row"><small>Realistic</small><div class="tr"><span style="width:${Math.round((rN / yMax) * 100)}%"></span></div><b>${esc(yo.realistic || '')}</b></div>` : ''}
                </div>
                ${yo.note ? `<p class="cp-note">${esc(sweep(yo.note))}</p>` : ''}
            </div>` : ''}

            <div class="wtp-card">
                <h3>In plain words</h3>
                <p class="wtp-plain">${esc(sweep(r.summary))}</p>
                ${(r.dataGaps || []).length ? `<h3 class="mt-4">What this protocol could not verify</h3><ul class="wtp-gap">${(r.dataGaps || []).map((g) => `<li>${esc(g)}</li>`).join('')}</ul>` : ''}
            </div>

            ${sourcesHtml(r)}

            <div class="wtp-card">
                <h3>🧭 A guide, not a promise</h3>
                <p class="wtp-fine">This is a starting protocol, not a prescription: the rates follow the official recommendations bent to your answers, and the interventions are what to do only when the sign is seen, not what to pour on a date. Your own eyes on the crop, and a soil test where you can get one, finish what this starts.</p>
            </div>
            ${actionsHtml(mode)}`;

        // The stage picker: the chart's bars and the rows both choose a
        // stage; the detail panel explains it.
        const card = hostEl.querySelector('[data-cp2-stages]');
        if (card) {
            const paint = () => {
                const i = Math.max(0, Math.min(stages.length - 1, Number(card.dataset.sel) || 0));
                const st = stages[i] || {};
                card.querySelectorAll('[data-cp2-stage]').forEach((el) => el.classList.toggle('is-sel', Number(el.dataset.cp2Stage) === i));
                card.querySelectorAll('[data-cp2-lbl]').forEach((el) => el.classList.toggle('is-sel', Number(el.dataset.cp2Lbl) === i));
                const d = card.querySelector('[data-cp2-detail]');
                d.classList.add('is-swap');
                setTimeout(() => {
                    d.innerHTML = `
                        <div class="cp2-d-head"><span class="cp2-row-n">${i + 1}</span><span><b>${esc(st.stage || '')}</b>${st.hint && st.hint !== 'n/a' ? `<small>${esc(st.hint)}</small>` : ''}</span>
                            <span class="cp2-d-nav"><button type="button" data-cp2-prev ${i === 0 ? 'disabled' : ''} aria-label="Previous stage">‹</button><button type="button" data-cp2-next ${i === stages.length - 1 ? 'disabled' : ''} aria-label="Next stage">›</button></span></div>
                        ${st.signs ? `<p class="cp2-d-signs">👁️ ${esc(sweep(st.signs))}</p>` : ''}
                        ${(st.fertilizer || []).length ? `<div class="cp2-d-fert">${(st.fertilizer || []).map((x) => `<div class="cp2-d-app"><i style="background:${colour(x.product || 'Fertilizer')}"></i><span><b>${esc(trimN(x.totalBags))} ${Number(x.totalBags) === 1 ? 'bag' : 'bags'} ${esc(x.product || '')}</b> <small>(${esc(trimN(x.bagsPerHa))}/ha)</small>${x.purpose ? `<em>${esc(sweep(x.purpose))}</em>` : ''}</span></div>`).join('')}</div>` : `<p class="cp2-dim">No fertilizer at this stage.</p>`}
                        ${st.observe ? `<div class="cp2-d-obs"><span class="is-obs">🔎 Observe</span><p>${esc(sweep(st.observe))}</p></div>` : ''}
                        ${st.intervene ? `<div class="cp2-d-obs"><span class="is-act">🛠️ Intervene</span><p>${esc(sweep(st.intervene))}</p></div>` : ''}
                        ${(rec.foliars || []).filter((f) => sameStage(f.stage, st.stage)).map((f) => `<div class="cp2-d-fol"><span>🍃 Foliar</span><p><b>${esc(f.product || '')}</b>${f.why ? ' — ' + esc(sweep(f.why)) : ''}</p></div>`).join('')}`;
                    d.classList.remove('is-swap');
                }, d.innerHTML ? 140 : 0);
            };
            card.addEventListener('click', (e) => {
                const b = e.target.closest('[data-cp2-stage]');
                if (b) { card.dataset.sel = b.dataset.cp2Stage; paint(); return; }
                if (e.target.closest('[data-cp2-prev]')) { card.dataset.sel = String(Math.max(0, Number(card.dataset.sel) - 1)); paint(); return; }
                if (e.target.closest('[data-cp2-next]')) { card.dataset.sel = String(Math.min(stages.length - 1, Number(card.dataset.sel) + 1)); paint(); }
            });
            paint();
        }
    }

    function finishDraw(hostEl, item, mode, quiet) {
        hostEl.hidden = false;
        if (!quiet) {
            requestAnimationFrame(() => requestAnimationFrame(() => hostEl.classList.add('is-drawn')));
            hostEl.scrollIntoView({ behavior: 'smooth', block: 'start' });
        } else if (hostEl.id === 'cpReport') {
            hostEl.classList.add('is-drawn');
        }

        hostEl.querySelector('[data-cp-attach]').addEventListener('click', () => { if (item.savedId) window.location.href = U.anee + '?analysis=' + item.savedId; });
        hostEl.querySelector('[data-cp-again]')?.addEventListener('click', () => { closeView(); wizardBack(); });
        hostEl.querySelector('[data-cp-delete]').addEventListener('click', async () => {
            const ok = window.confirmAction
                ? await confirmAction({ title: 'Delete this protocol?', message: 'The credits it cost are already spent; only the report goes.', confirmText: 'Delete', danger: true })
                : confirm('Delete this protocol?');
            if (!ok) return;
            try {
                const res = await api(U.del(item.savedId), { method: 'DELETE' });
                toast(res.message);
                closeView();
                $id('cpReport').hidden = true;
                loadSaved().catch(() => {});
                if (mode === 'fresh') wizardBack();
            } catch (err) { toast(err.message, 'error'); }
        });
    }

    /* ---------------- saved ---------------- */
    let ROWS = [];
    let META_ID = null;
    async function loadSaved() {
        const res = await api(U.list + '?_=' + Date.now(), { method: 'GET' });
        ROWS = res.data.rows || [];
        $id('cpSavedList').innerHTML = ROWS.map((r) => `
            <button type="button" class="wtp-saved" data-saved="${r.id}">
                <span class="grow min-w-0"><b>${esc(r.title)}</b><small>${r.description ? esc(r.description) + ' · ' : ''}${esc(r.at)} · ${r.credits} credits</small></span>
                <span role="button" tabindex="0" class="wtp-pen" data-meta="${r.id}" title="Edit name and description" aria-label="Edit ${esc(r.title)}">
                    <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" style="width:.85rem;height:.85rem"><path stroke-linecap="round" stroke-linejoin="round" d="M11 5H6a2 2 0 00-2 2v11a2 2 0 002 2h11a2 2 0 002-2v-5m-1.414-9.414a2 2 0 112.828 2.828L11.828 15H9v-2.828l8.586-8.586z"/></svg>
                </span>
                <svg class="w-4 h-4 text-gray-300 shrink-0" fill="none" stroke="currentColor" stroke-width="2.4" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" d="M9 5l7 7-7 7"/></svg>
            </button>`).join('');
        $id('cpSavedEmpty').classList.toggle('hidden', ROWS.length > 0);
    }
    document.addEventListener('click', async (e) => {
        const saveBtn = e.target.closest('#cpMetaSave');
        if (saveBtn && META_ID !== null) {
            saveBtn.disabled = true;
            try {
                const res = await api(META_URL, { method: 'POST', body: { id: META_ID, title: $id('cpMetaTitle').value.trim(), description: $id('cpMetaDesc').value.trim() } });
                toast(res.message);
                closeSheet('cpMetaSheet');
                loadSaved().catch(() => {});
            } catch (err) { toast(err.message, 'error'); }
            finally { saveBtn.disabled = false; }
            return;
        }
        const pen = e.target.closest('[data-meta]');
        if (pen && pen.closest('#cpSavedList')) {
            e.stopPropagation();
            const r = ROWS.find((x) => String(x.id) === pen.getAttribute('data-meta'));
            if (!r) return;
            META_ID = r.id;
            $id('cpMetaTitle').value = r.title || '';
            $id('cpMetaDesc').value = r.description || '';
            openSheet('cpMetaSheet');
            return;
        }
        const b = e.target.closest('[data-saved]');
        if (!b) return;
        try {
            const res = await api(U.one(b.getAttribute('data-saved')), { method: 'GET' });
            openView({ report: res.data.report, params: res.data.params, charged: res.data.credits, savedId: res.data.id }, 'saved');
        } catch (err) { toast(err.message, 'error'); }
    });

    const tab = (which) => {
        $id('cpGen').classList.toggle('hidden', which !== 'gen');
        $id('cpSavedPane').classList.toggle('hidden', which !== 'saved');
        $id('cpTabGen').classList.toggle('is-on', which === 'gen');
        $id('cpTabSaved').classList.toggle('is-on', which === 'saved');
        if (which === 'saved') loadSaved().catch((err) => toast(err.message, 'error'));
    };
    $id('cpTabGen').addEventListener('click', () => tab('gen'));
    $id('cpTabSaved').addEventListener('click', () => tab('saved'));

    if (window.api) boot();
    else window.addEventListener('load', boot, { once: true });
})();
</script>
@endsection

@push('sheets')
<div class="sheet hidden" id="cpMetaSheet" style="--sheet-width:26rem">
    <div class="sheet-handle"></div>
    <div class="sheet-header">
        <h3 class="sheet-title">Edit this protocol</h3>
        <button type="button" data-sheet-close class="btn-ghost p-2 rounded-full" aria-label="Close">✕</button>
    </div>
    <div class="sheet-body space-y-4">
        <div>
            <label class="form-label" for="cpMetaTitle">Name</label>
            <input type="text" id="cpMetaTitle" class="form-input" maxlength="191">
        </div>
        <div>
            <label class="form-label" for="cpMetaDesc">Description <span class="text-gray-400 font-normal">(optional)</span></label>
            <textarea id="cpMetaDesc" class="form-textarea" rows="3" maxlength="2000"></textarea>
        </div>
    </div>
    <div class="sheet-footer">
        <button type="button" class="btn btn-primary w-full" id="cpMetaSave">Save changes</button>
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
