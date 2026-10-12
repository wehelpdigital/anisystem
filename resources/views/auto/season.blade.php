@extends('layouts.app')
@section('title', 'Anee Auto')
@section('body-class', 'hide-tabbar')
@section('page-title', 'Anee Auto')
@section('page-subtitle', $schedule->title)
@section('back', route('auto.index'))

{{-- ANEE AUTO, THE SEASON (2026-10-12). A chat with Anee that is also the
     day's plan: the story so far, then today's work and the week ahead as
     her latest message, then the composer (ask, or show her the field).
     Done and Can't today steer it; she moves the rest. See
     App\Http\Controllers\AneeAutoController::feed. --}}
@section('content')
@include('partials.anee-emoji')
<style>
    :root { --ac-ease: cubic-bezier(.22,1,.36,1); }
    .ac { max-width: 60rem; margin: 0 auto; display: flex; flex-direction: column; min-height: calc(100svh - var(--app-head, 4rem) - 1.5rem); }
    .ac-head { position: relative; overflow: hidden; border-radius: 1.3rem; padding: .9rem 1rem; color: #e8f1de; margin-bottom: .8rem;
        background: radial-gradient(130% 170% at 100% 0%, #5d9431 0%, #24410f 55%, #10200a 100%); }
    .ac-head::after { content: ''; position: absolute; inset: 0; pointer-events: none; opacity: .45;
        background-image: radial-gradient(circle at 15% 30%, rgb(255 255 255 / .5) 0 1px, transparent 1.5px), radial-gradient(circle at 75% 70%, rgb(255 255 255 / .35) 0 1px, transparent 1.5px);
        background-size: 120px 110px, 170px 150px; animation: acDrift 30s linear infinite; }
    @keyframes acDrift { to { background-position: 120px 110px, -170px 150px; } }
    .ac-head-row { position: relative; z-index: 1; display: flex; align-items: center; gap: .7rem; }
    .ac-head h2 { font-family: var(--font-heading); font-weight: 800; font-size: 1.05rem; color: #fff; line-height: 1.25; min-width: 0; overflow: hidden; text-overflow: ellipsis; white-space: nowrap; }
    .ac-head p { font-size: .76rem; color: #cfe3b9; }
    .ac-head .ac-acts { margin-left: auto; display: flex; gap: .35rem; flex: none; }
    .ac-hbtn { display: inline-flex; align-items: center; gap: .3rem; padding: .38rem .65rem; border-radius: 999px; font-size: .74rem; font-weight: 800; color: #e8f1de; background: rgb(255 255 255 / .13);
        transition: background-color .28s var(--ac-ease); }
    .ac-hbtn:hover { background: rgb(255 255 255 / .22); }
    .ac-lots { position: relative; z-index: 1; display: flex; gap: .45rem; overflow-x: auto; scrollbar-width: none; margin-top: .75rem; padding-bottom: .1rem; }
    .ac-lots::-webkit-scrollbar { display: none; }
    .ac-lot { flex: none; display: flex; align-items: center; gap: .5rem; padding: .45rem .7rem; border-radius: .95rem; background: rgb(255 255 255 / .11); border: 1px solid rgb(255 255 255 / .14);
        animation: acIn .4s var(--ac-ease) both; animation-delay: calc(var(--i) * 70ms); }
    .ac-lot > span { font-size: 1.3rem; }
    .ac-lot b { display: block; font-size: .8rem; color: #fff; }
    .ac-lot small { display: block; font-size: .7rem; color: #cfe3b9; white-space: nowrap; }
    .ac-week { position: relative; z-index: 1; display: grid; grid-template-columns: repeat(7, minmax(0, 1fr)); gap: .3rem; margin-top: .6rem; }
    .ac-week div { text-align: center; padding: .3rem 0; border-radius: .7rem; background: rgb(0 0 0 / .14); font-size: .66rem; font-weight: 800; color: #cfe3b9; }
    .ac-week div.is-today { background: rgb(245 197 24 / .25); color: #fff; }
    .ac-week i { display: block; font-style: normal; font-size: 1rem; line-height: 1.4; }
    .ac-week em { display: block; font-style: normal; font-weight: 700; color: #a8d4f5; }
    .ac-stats { position: relative; z-index: 1; display: flex; gap: .4rem; margin-top: .6rem; font-size: .7rem; font-weight: 800; color: #cfe3b9; }
    .ac-stats span { padding: .15rem .5rem; border-radius: 999px; background: rgb(255 255 255 / .1); }

    .ac-feed { flex: 1; display: flex; flex-direction: column; gap: .7rem; padding: .2rem 0 1rem; }
    .ac-more { align-self: center; font-size: .78rem; font-weight: 800; color: var(--color-brand-700); padding: .35rem .8rem; border-radius: 999px; background: var(--color-white); border: 1px solid var(--color-gray-200); }
    .ac-day { align-self: center; font-size: .68rem; font-weight: 800; letter-spacing: .05em; text-transform: uppercase; color: var(--color-gray-400); margin: .3rem 0; }
    .ac-msg { display: flex; gap: .55rem; align-items: flex-end; max-width: 92%; animation: acIn .38s var(--ac-ease) both; }
    @keyframes acIn { from { opacity: 0; transform: translateY(10px); } }
    .ac-msg.is-me { align-self: flex-end; flex-direction: row-reverse; }
    .ac-av { flex: none; width: 2.1rem; height: 2.1rem; border-radius: 999px; background: #eef6e4; overflow: hidden; }
    .ac-av img { width: 100%; height: 100%; object-fit: cover; }
    .ac-bub { min-width: 0; padding: .65rem .85rem; border-radius: 1.1rem 1.1rem 1.1rem .35rem; background: var(--color-white); border: 1px solid var(--color-gray-200); font-size: .88rem; line-height: 1.55; color: var(--color-gray-800); }
    .ac-msg.is-me .ac-bub { border-radius: 1.1rem 1.1rem .35rem 1.1rem; background: #e7f2da; border-color: #cfe3b8; color: #1f2a17; }
    .ac-bub small { display: block; margin-top: .3rem; font-size: .66rem; color: var(--color-gray-400); }
    .ac-bub .ac-media { display: flex; gap: .35rem; flex-wrap: wrap; margin-top: .45rem; }
    .ac-bub .ac-media img, .ac-bub .ac-media video { max-width: 9rem; max-height: 9rem; border-radius: .7rem; object-fit: cover; }
    .ac-bub .ac-media audio { max-width: 15rem; }
    .ac-obs { margin-top: .5rem; padding: .55rem .7rem; border-radius: .8rem; background: var(--color-gray-50); border: 1px solid var(--color-gray-100); font-size: .8rem; }
    .ac-obs b { color: var(--color-gray-900); }
    .ac-obs ul { margin: .25rem 0 0 1rem; list-style: disc; }
    .ac-sev { display: inline-block; padding: .05rem .45rem; border-radius: 999px; font-size: .66rem; font-weight: 800; margin-left: .3rem; }
    .ac-sev.is-high { background: #fde8e8; color: #9f1d1d; } .ac-sev.is-medium { background: #fff4d6; color: #8a5a00; } .ac-sev.is-low, .ac-sev.is-none { background: #e4efd4; color: #2d5016; }
    .ac-sys { align-self: center; font-size: .72rem; color: var(--color-gray-400); }

    /* Today: Anee's latest message */
    .ac-today { align-self: stretch; max-width: none; }
    .ac-today .ac-bub { flex: 1; padding: .85rem .9rem; background: linear-gradient(180deg, #fbfdf8, #fff); }
    .ac-h { display: flex; align-items: center; gap: .4rem; font-family: var(--font-heading); font-weight: 800; font-size: .98rem; color: var(--color-gray-900); }
    .ac-h small { display: inline; margin: 0 0 0 auto; font-family: var(--font-body, inherit); font-size: .72rem; font-weight: 800; color: var(--color-gray-400); }
    .ac-tasks { display: grid; gap: .5rem; margin-top: .6rem; }
    .ac-task { border-radius: 1rem; border: 1px solid var(--color-gray-200); background: var(--color-white); overflow: hidden; animation: acIn .36s var(--ac-ease) both; animation-delay: calc(var(--i) * 55ms);
        transition: border-color .28s var(--ac-ease), box-shadow .28s var(--ac-ease), opacity .4s var(--ac-ease), transform .4s var(--ac-ease); }
    .ac-task.is-open { border-color: #b7d597; box-shadow: 0 14px 30px -24px rgb(15 23 42 / .5); }
    .ac-task.is-leaving { opacity: 0; transform: translateX(30px) scale(.97); }
    .ac-task.is-critical { border-left: 4px solid #e8a33a; }
    .ac-task.is-late { border-left: 4px solid #d9534f; }
    .ac-tt { display: flex; gap: .6rem; align-items: center; width: 100%; padding: .65rem .75rem; text-align: left; }
    .ac-te { flex: none; width: 2.3rem; height: 2.3rem; border-radius: .8rem; display: grid; place-items: center; font-size: 1.2rem; background: var(--tone-bg, #eef6e4); }
    .ac-task[data-tone="gold"] { --tone-bg: #fdf3d3; } .ac-task[data-tone="rose"] { --tone-bg: #fde9ec; } .ac-task[data-tone="sky"] { --tone-bg: #e5f2fc; } .ac-task[data-tone="amber"] { --tone-bg: #fff0dc; }
    .ac-tm { flex: 1; min-width: 0; }
    .ac-tm b { display: block; font-size: .9rem; font-weight: 800; color: var(--color-gray-900); line-height: 1.3; }
    .ac-tm small { display: flex; flex-wrap: wrap; gap: .25rem .45rem; margin-top: .15rem; font-size: .72rem; color: var(--color-gray-500); }
    .ac-flag { font-weight: 800; padding: 0 .4rem; border-radius: 999px; }
    .ac-flag.is-crit { color: #8a5a00; background: #fff4d6; } .ac-flag.is-late { color: #9f1d1d; background: #fde8e8; } .ac-flag.is-risk { color: #1e4b6b; background: #e5f2fc; }
    .ac-chev { flex: none; width: 1rem; height: 1rem; color: var(--color-gray-400); transition: transform .28s var(--ac-ease); }
    .ac-task.is-open .ac-chev { transform: rotate(180deg); }
    .ac-fold { display: grid; grid-template-rows: 0fr; transition: grid-template-rows .34s var(--ac-ease); }
    .ac-task.is-open .ac-fold { grid-template-rows: 1fr; }
    .ac-fold > div { overflow: hidden; }
    .ac-fin { padding: 0 .8rem .8rem; font-size: .84rem; line-height: 1.55; color: var(--color-gray-700); }
    .ac-fin h5 { margin: .65rem 0 .2rem; font-size: .66rem; font-weight: 800; letter-spacing: .05em; text-transform: uppercase; color: var(--color-gray-400); }
    .ac-fin ol { margin-left: 1.1rem; list-style: decimal; } .ac-fin ul { margin-left: 1rem; list-style: disc; }
    .ac-why { padding: .55rem .7rem; border-radius: .8rem; background: #f3f8ec; border: 1px solid #d6e6c2; color: #2d4a17; }
    .ac-prod { padding: .5rem .65rem; border-radius: .8rem; border: 1px solid var(--color-gray-200); margin-top: .35rem; }
    .ac-prod b { color: var(--color-gray-900); } .ac-prod em { font-style: normal; font-size: .7rem; font-weight: 800; color: #6b4e00; background: #fdf3d3; padding: .05rem .4rem; border-radius: 999px; margin-left: .3rem; }
    .ac-shop { display: flex; gap: .35rem; margin-top: .35rem; }
    .ac-shop a { font-size: .7rem; font-weight: 800; padding: .2rem .55rem; border-radius: 999px; border: 1px solid var(--color-gray-200); color: var(--color-gray-600); }
    .ac-shop a:hover { border-color: #f0a46b; color: #b45309; }
    .ac-safe { padding: .55rem .7rem; border-radius: .8rem; background: #fff6dd; border: 1px solid #f3d98f; color: #6b4a00; }
    .ac-tbtns { display: flex; gap: .45rem; margin-top: .8rem; }
    .ac-tbtns .btn { flex: 1; }
    .ac-done-btn { background: #4a7c2a !important; color: #fff !important; }
    .ac-soon { display: grid; gap: .3rem; margin-top: .5rem; }
    .ac-srow { display: flex; gap: .55rem; align-items: center; padding: .45rem .55rem; border-radius: .8rem; text-align: left; width: 100%; transition: background-color .28s var(--ac-ease); }
    .ac-srow:hover { background: var(--color-gray-50); }
    .ac-srow i { flex: none; font-style: normal; width: 3.8rem; font-size: .7rem; font-weight: 800; color: var(--color-gray-500); }
    .ac-srow b { flex: 1; min-width: 0; font-size: .82rem; font-weight: 700; color: var(--color-gray-800); overflow: hidden; text-overflow: ellipsis; white-space: nowrap; }
    .ac-srow em { flex: none; font-style: normal; font-size: .7rem; color: var(--color-gray-400); }
    .ac-ready { display: flex; flex-wrap: wrap; gap: .3rem; margin-top: .45rem; }
    .ac-ready span { font-size: .74rem; font-weight: 700; padding: .2rem .55rem; border-radius: 999px; background: #fff0dc; color: #7a4a00; }
    .ac-none { margin-top: .5rem; font-size: .84rem; color: var(--color-gray-500); }
    .ac-typing { display: inline-flex; gap: .25rem; padding: .2rem 0; }
    .ac-typing i { width: .45rem; height: .45rem; border-radius: 999px; background: #8fbf5f; animation: acDot 1.1s ease-in-out infinite; }
    .ac-typing i:nth-child(2) { animation-delay: .15s; } .ac-typing i:nth-child(3) { animation-delay: .3s; }
    @keyframes acDot { 50% { transform: translateY(-4px); opacity: .5; } }

    /* The composer */
    .ac-comp { position: sticky; bottom: 0; z-index: 20; margin: 0 -1rem; padding: .6rem 1rem calc(.6rem + env(safe-area-inset-bottom)); background: rgb(249 250 251 / .95);
        backdrop-filter: blur(8px); -webkit-backdrop-filter: blur(8px); border-top: 1px solid var(--color-gray-200); }
    .ac-modes { display: flex; gap: .35rem; margin-bottom: .45rem; }
    .ac-mode { padding: .3rem .7rem; border-radius: 999px; font-size: .74rem; font-weight: 800; color: var(--color-gray-500); background: var(--color-white); border: 1px solid var(--color-gray-200); transition: background-color .28s var(--ac-ease), color .28s var(--ac-ease); }
    .ac-mode[aria-pressed="true"] { color: #fff; background: var(--color-brand-600); border-color: var(--color-brand-600); }
    .ac-crow { display: flex; gap: .4rem; align-items: flex-end; }
    .ac-crow textarea { flex: 1; min-height: 2.75rem; max-height: 8rem; resize: none; }
    .ac-ib { flex: none; width: 2.75rem; height: 2.75rem; border-radius: 999px; display: grid; place-items: center; font-size: 1.15rem; background: var(--color-white); border: 1px solid var(--color-gray-200); transition: background-color .28s var(--ac-ease); }
    .ac-ib.is-rec { background: #fde8e8; border-color: #f5c2c2; animation: acRec 1s ease-in-out infinite; }
    @keyframes acRec { 50% { box-shadow: 0 0 0 6px rgb(220 38 38 / .15); } }
    .ac-send { flex: none; width: 2.75rem; height: 2.75rem; border-radius: 999px; display: grid; place-items: center; color: #fff; background: var(--color-brand-600); }
    .ac-send:disabled { opacity: .5; }
    .ac-att { display: flex; gap: .35rem; flex-wrap: wrap; margin-bottom: .4rem; }
    .ac-att:empty { display: none; }
    .ac-att span { position: relative; display: inline-flex; align-items: center; gap: .3rem; padding: .25rem; border-radius: .7rem; background: var(--color-white); border: 1px solid var(--color-gray-200); font-size: .74rem; font-weight: 700; animation: acIn .25s var(--ac-ease) both; }
    .ac-att img { width: 3rem; height: 3rem; border-radius: .5rem; object-fit: cover; }
    .ac-att button { width: 1.3rem; height: 1.3rem; border-radius: 999px; background: var(--color-gray-100); font-size: .7rem; }
    .ac-lotpick { font-size: .74rem; padding: .3rem .55rem; border-radius: 999px; border: 1px solid var(--color-gray-200); background: var(--color-white); }

    .ac-modal { position: fixed; inset: 0; z-index: 230; display: flex; align-items: flex-end; justify-content: center; padding: 0; background: rgb(12 20 8 / .55); opacity: 0; transition: opacity .28s var(--ac-ease); }
    @media (min-width: 640px) { .ac-modal { align-items: center; padding: 1rem; } }
    .ac-modal.is-on { opacity: 1; }
    .ac-modal[hidden] { display: none; }
    .ac-modal > div { width: 100%; max-width: 28rem; max-height: 92vh; overflow-y: auto; border-radius: 1.4rem 1.4rem 0 0; background: var(--color-white); padding: 1.1rem 1.15rem calc(1.1rem + env(safe-area-inset-bottom)); transform: translateY(30px); transition: transform .34s var(--ac-ease); }
    @media (min-width: 640px) { .ac-modal > div { border-radius: 1.3rem; } }
    .ac-modal.is-on > div { transform: none; }
    .ac-modal h3 { font-family: var(--font-heading); font-weight: 800; font-size: 1.08rem; color: var(--color-gray-900); }
    .ac-modal p.ac-msub { margin: .3rem 0 .8rem; font-size: .84rem; color: var(--color-gray-500); }
    .ac-reasons { display: grid; grid-template-columns: repeat(3, minmax(0, 1fr)); gap: .4rem; }
    .ac-reason { display: flex; flex-direction: column; align-items: center; gap: .25rem; padding: .65rem .3rem; border-radius: .95rem; font-size: .72rem; font-weight: 800; text-align: center; color: var(--color-gray-700);
        border: 1px solid var(--color-gray-200); background: var(--color-white); transition: background-color .28s var(--ac-ease), border-color .28s var(--ac-ease), transform .2s var(--ac-ease); }
    .ac-reason span { font-size: 1.4rem; }
    .ac-reason:active { transform: scale(.96); }
    .ac-reason[aria-pressed="true"] { background: #f3f8ec; border-color: var(--color-brand-600); color: #2d5016; }
    .ac-lab { display: block; margin: .8rem 0 .3rem; font-size: .76rem; font-weight: 800; color: var(--color-gray-700); }
    .ac-pills { display: flex; flex-wrap: wrap; gap: .35rem; }
    .ac-pill { padding: .35rem .7rem; border-radius: 999px; font-size: .78rem; font-weight: 700; color: var(--color-gray-600); background: var(--color-gray-100); transition: background-color .28s var(--ac-ease), color .28s var(--ac-ease); }
    .ac-pill[aria-pressed="true"] { color: #fff; background: var(--color-brand-600); }
    .ac-macts { display: flex; gap: .5rem; margin-top: 1rem; } .ac-macts .btn { flex: 1; }
    .ac-log-day { margin-top: 1rem; } .ac-log-day h4 { font-size: .72rem; font-weight: 800; letter-spacing: .05em; text-transform: uppercase; color: var(--color-gray-400); }
    .ac-log-row { display: flex; gap: .6rem; padding: .45rem 0; border-bottom: 1px dashed var(--color-gray-200); font-size: .82rem; color: var(--color-gray-700); }
    .ac-log-row i { flex: none; width: 3.6rem; font-style: normal; font-size: .7rem; color: var(--color-gray-400); }
    .ac-log-row b { color: var(--color-gray-900); }

    html.dark .ac-bub, html.dark .ac-task, html.dark .ac-more, html.dark .ac-mode:not([aria-pressed="true"]), html.dark .ac-ib, html.dark .ac-modal > div, html.dark .ac-reason, html.dark .ac-att span, html.dark .ac-lotpick { background: #161c12; border-color: #2b3a1c; color: #dbe5d0; }
    html.dark .ac-today .ac-bub { background: linear-gradient(180deg, #19210f, #161c12); }
    html.dark .ac-msg.is-me .ac-bub { background: #22301a; border-color: #3f5a2a; color: #e8efe1; }
    html.dark .ac-tm b, html.dark .ac-h, html.dark .ac-srow b, html.dark .ac-modal h3, html.dark .ac-prod b, html.dark .ac-obs b, html.dark .ac-log-row b { color: #eef3e8; }
    html.dark .ac-why { background: #17220f; border-color: #2b3a1c; color: #c5e09f; }
    html.dark .ac-safe { background: #2a2210; border-color: #5b4716; color: #f3d9a4; }
    html.dark .ac-obs, html.dark .ac-srow:hover { background: #1a2114; border-color: #2b3a1c; }
    html.dark .ac-comp { background: rgb(16 20 13 / .95); border-color: #2b3a1c; }
    html.dark .ac-reason[aria-pressed="true"] { background: #22301a; color: #c5e09f; }
    html.dark .ac-task[data-tone] { --tone-bg: #22301a; }
    @media (prefers-reduced-motion: reduce) {
        .ac-head::after, .ac-lot, .ac-msg, .ac-task, .ac-typing i, .ac-ib.is-rec, .ac-att span { animation: none; }
        .ac-fold, .ac-chev, .ac-task, .ac-modal, .ac-modal > div { transition: none; }
    }
</style>

<div class="ac">
    <div class="ac-head">
        <div class="ac-head-row">
            <div class="min-w-0"><h2 id="acTitle">{{ $schedule->title }}</h2><p id="acSay">Anee plans, you follow.</p></div>
            <div class="ac-acts">
                <button type="button" class="ac-hbtn" id="acLogBtn">📖 Log</button>
                <button type="button" class="ac-hbtn" id="acSafeBtn">🛡️ Safety</button>
                <a class="ac-hbtn" href="{{ route('sm.hub', ['id' => $schedule->id]) }}">🗂️ Season</a>
            </div>
        </div>
        <div class="ac-lots" id="acLots"></div>
        <div class="ac-week" id="acWeek"></div>
        <div class="ac-stats" id="acStats"></div>
    </div>

    <div class="ac-feed" id="acFeed" aria-live="polite"><p class="ac-sys">Loading your season…</p></div>

    <div class="ac-comp">
        <div class="ac-modes" role="tablist">
            <button type="button" class="ac-mode" data-mode="ask" aria-pressed="true">💬 Ask Anee</button>
            <button type="button" class="ac-mode" data-mode="observe" aria-pressed="false">👀 Show Anee the field</button>
            <select id="acLotPick" class="ac-lotpick ml-auto" aria-label="Which lot" hidden></select>
        </div>
        <div class="ac-att" id="acAtt"></div>
        <div class="ac-crow">
            <button type="button" class="ac-ib" id="acPhoto" title="Take or add a photo" aria-label="Photo">📷</button>
            <button type="button" class="ac-ib" id="acVideo" title="Record or add a short video" aria-label="Video">🎥</button>
            <button type="button" class="ac-ib" id="acVoice" title="Record a voice note" aria-label="Voice">🎙️</button>
            <textarea id="acText" class="form-textarea" rows="1" maxlength="2000" placeholder="Ask Anee anything about your season"></textarea>
            <button type="button" class="ac-send" id="acSend" aria-label="Send"><svg width="20" height="20" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.4"><path stroke-linecap="round" stroke-linejoin="round" d="M5 12h14m0 0l-6-6m6 6l-6 6"/></svg></button>
        </div>
        <input type="file" id="acPhotoIn" accept="image/*" capture="environment" multiple hidden>
        <input type="file" id="acVideoIn" accept="video/*" capture="environment" hidden>
    </div>
</div>

<div class="ac-modal" id="acModal" hidden><div id="acModalBox"></div></div>
@endsection

@push('scripts')
<script>
(() => {
    const $ = (id) => document.getElementById(id);
    const esc = (s) => String(s ?? '').replace(/[&<>"']/g, (c) => ({ '&': '&amp;', '<': '&lt;', '>': '&gt;', '"': '&quot;', "'": '&#39;' }[c]));
    const still = () => matchMedia('(prefers-reduced-motion: reduce)').matches;
    const U = {
        feed: @json(route('auto.feed', $auto->id)), log: @json(route('auto.log', $auto->id)), observe: @json(route('auto.observe', $auto->id)), ask: @json(route('auto.ask', $auto->id)),
        done: (t) => @json(url('/app/auto/' . $auto->id . '/task')) + '/' + t + '/done', cant: (t) => @json(url('/app/auto/' . $auto->id . '/task')) + '/' + t + '/cant',
    };
    const FACE = @json(\App\Support\AneeEmoji::url('happy'));
    const SHOP_NOTE = 'These links only show who may be selling it. Anee does not recommend a shop or a brand: read the label and buy only FPA registered products.';
    let F = null, EV = [], open = new Set();

    const chev = '<svg class="ac-chev" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.4"><path stroke-linecap="round" stroke-linejoin="round" d="M6 9l6 6 6-6"/></svg>';
    const taskCard = (t, i) => {
        const flags = (t.critical ? '<span class="ac-flag is-crit">Cannot wait</span>' : '') + (t.late ? '<span class="ac-flag is-late">Late</span>' : '')
            + (t.note && t.note.risk ? '<span class="ac-flag is-risk">' + ({ weather: 'Weather risk', workers: 'Short of hands', both: 'Weather and hands' })[t.note.risk] + '</span>' : '');
        const meta = [t.lot, t.time ? (t.time === 'morning' ? 'Morning' : 'Afternoon') : null, t.workers && t.workers.length ? '👥 ' + t.workers.join(', ') : null, t.note && t.note.sky ? t.note.sky.emoji + ' ' + t.note.sky.pop + '%' : null].filter(Boolean);
        const d = t.detail || {};
        const prods = (d.products || []).map((p, k) => {
            const shop = (t.shop || [])[k];
            return '<div class="ac-prod"><b>' + esc(p.name) + '</b>' + (p.group ? '<em>' + esc(p.group) + '</em>' : '') + (p.rate ? '<div class="text-xs mt-1">Rate: ' + esc(p.rate) + '</div>' : '') + (p.amount ? '<div class="text-xs">For this lot: ' + esc(p.amount) + '</div>' : '')
                + (shop ? '<div class="ac-shop"><a href="' + esc(shop.shopee) + '" target="_blank" rel="noopener nofollow">Shopee</a><a href="' + esc(shop.lazada) + '" target="_blank" rel="noopener nofollow">Lazada</a></div>' : '') + '</div>';
        }).join('');
        return '<div class="ac-task' + (t.critical ? ' is-critical' : '') + (t.late ? ' is-late' : '') + (open.has(t.id) ? ' is-open' : '') + '" data-task="' + t.id + '" data-tone="' + esc(t.tone) + '" style="--i:' + i + '">'
            + '<button type="button" class="ac-tt" data-toggle><span class="ac-te">' + t.emoji + '</span><span class="ac-tm"><b>' + esc(t.title) + '</b><small>' + meta.map(esc).join(' · ') + flags + '</small></span>' + chev + '</button>'
            + '<div class="ac-fold"><div><div class="ac-fin">'
            + (t.summary ? '<p>' + esc(t.summary) + '</p>' : '')
            + (t.why ? '<h5>Why</h5><div class="ac-why">' + esc(t.why) + '</div>' : '')
            + ((d.steps || []).length ? '<h5>How</h5><ol>' + d.steps.map((s) => '<li>' + esc(s) + '</li>').join('') + '</ol>' : '')
            + (prods ? '<h5>What to use</h5>' + prods + ((t.shop || []).length ? '<p class="text-xs text-gray-400 mt-1">' + esc(SHOP_NOTE) + '</p>' : '') : '')
            + ((d.safety || []).length ? '<h5>Safety</h5><div class="ac-safe"><ul>' + d.safety.map((s) => '<li>' + esc(s) + '</li>').join('') + '</ul></div>' : '')
            + ((d.bring || []).length ? '<h5>Have ready</h5><ul>' + d.bring.map((s) => '<li>' + esc(s) + '</li>').join('') + '</ul>' : '')
            + (t.moved ? '<p class="text-xs text-gray-400 mt-2">Moved ' + t.moved + ' time' + (t.moved > 1 ? 's' : '') + ' so far.</p>' : '')
            + (t.status === 'planned' ? '<div class="ac-tbtns"><button type="button" class="btn ac-done-btn" data-done>✅ Done</button><button type="button" class="btn btn-white" data-cant>🙅 Can\'t today</button></div>' : '')
            + '</div></div></div></div>';
    };
    const todayBlock = () => {
        if (!F) return '';
        if (F.status !== 'active') return '<div class="ac-msg ac-today"><span class="ac-av"><img src="' + FACE + '" alt=""></span><div class="ac-bub"><div class="ac-h">🎉 This season is finished</div><p class="ac-none">The log keeps every task, observation and change for the post harvest report.</p></div></div>';
        const soon = F.soon.map((t) => '<button type="button" class="ac-srow" data-peek="' + t.id + '"><i>' + esc(t.when) + '</i><span>' + t.emoji + '</span><b>' + esc(t.title) + '</b><em>' + esc(t.lot || '') + '</em></button>').join('');
        return '<div class="ac-msg ac-today" id="acToday"><span class="ac-av"><img src="' + FACE + '" alt=""></span><div class="ac-bub">'
            + '<div class="ac-h">📋 Today<small>' + new Date().toLocaleDateString(undefined, { weekday: 'long', month: 'short', day: 'numeric' }) + '</small></div>'
            + (F.today.length ? '<div class="ac-tasks">' + F.today.map(taskCard).join('') + '</div>' : '<p class="ac-none">Nothing to do today. Walk the field, and tell me if you see anything :)</p>')
            + (F.soon.length ? '<div class="ac-h mt-4">🗓️ This week</div><div class="ac-soon">' + soon + '</div>' : '')
            + (F.ready.length ? '<div class="ac-h mt-4">🛒 Get these ready</div><div class="ac-ready">' + F.ready.map((r) => '<span>' + esc(r) + '</span>').join('') + '</div>' : '')
            + '</div></div>';
    };
    const evRow = (e) => {
        if (e.kind === 'email') return '<p class="ac-sys">✉️ The morning plan went out to ' + (e.data && e.data.sent) + ' ' + ((e.data && e.data.sent) === 1 ? 'person' : 'people') + '.</p>';
        const me = e.role === 'farmer';
        const media = (e.media || []).map((m) => m.type === 'video' ? '<video src="' + esc(m.url) + '" controls playsinline preload="metadata"></video>' : m.type === 'audio' ? '<audio src="' + esc(m.url) + '" controls preload="none"></audio>' : '<img src="' + esc(m.url) + '" alt="">').join('');
        const d = e.data || {};
        const obs = !me && d.what ? '<div class="ac-obs"><b>' + esc(d.what) + '</b>' + (d.severity ? '<span class="ac-sev is-' + esc(d.severity) + '">' + esc(d.severity) + '</span>' : '') + (d.confidence ? ' <span class="text-xs text-gray-400">· ' + esc(d.confidence) + ' confidence</span>' : '')
            + (d.reading ? '<p class="mt-1">' + esc(d.reading) + '</p>' : '') + ((d.actions || []).length ? '<ul>' + d.actions.map((a) => '<li>' + esc(a) + '</li>').join('') + '</ul>' : '') + (d.watch ? '<p class="mt-1 text-xs"><b>Watch:</b> ' + esc(d.watch) + '</p>' : '') + '</div>' : '';
        return '<div class="ac-msg' + (me ? ' is-me' : '') + '" data-ev="' + e.id + '">' + (me ? '' : '<span class="ac-av"><img src="' + FACE + '" alt=""></span>')
            + '<div class="ac-bub">' + e.html + obs + (media ? '<div class="ac-media">' + media + '</div>' : '') + '<small>' + esc(e.at) + '</small></div></div>';
    };
    const paint = (keepScroll = false) => {
        if (!F) return;
        let lastDay = null;
        const rows = EV.map((e) => { const day = (e.at || '').split(',')[0]; const head = day !== lastDay ? '<p class="ac-day">' + esc(lastDay = day) + '</p>' : ''; return head + evRow(e); }).join('');
        $('acFeed').innerHTML = (F.hasMore ? '<button type="button" class="ac-more" id="acMore">Show earlier</button>' : '') + rows + todayBlock();
        if (!keepScroll) requestAnimationFrame(() => { const t = $('acToday'); if (t) t.scrollIntoView({ block: 'start', behavior: still() ? 'auto' : 'smooth' }); });
    };
    const paintHead = () => {
        $('acTitle').textContent = F.title;
        $('acLots').innerHTML = F.lots.map((l, i) => '<div class="ac-lot" style="--i:' + i + '"><span>' + l.icon + '</span><div><b>' + esc(l.name) + '</b><small>' + esc([l.say, l.stage].filter(Boolean).join(' · ') || l.crop || '') + (l.harvest ? ' · 🧺 ' + esc(l.harvest) : '') + '</small></div></div>').join('');
        $('acWeek').innerHTML = F.week.map((d, i) => '<div class="' + (i === 0 ? 'is-today' : '') + '">' + esc(d.dow) + '<i>' + d.emoji + '</i><em>' + d.pop + '%</em></div>').join('');
        $('acStats').innerHTML = '<span>✅ ' + F.doneCount + ' done</span><span>📌 ' + F.left + ' to go</span>';
        $('acSay').textContent = F.today.length ? F.today.length + ' task' + (F.today.length > 1 ? 's' : '') + ' today' : 'Nothing due today';
        const pick = $('acLotPick');
        pick.innerHTML = '<option value="">The whole farm</option>' + F.lots.map((l) => '<option value="' + l.id + '">' + esc(l.name) + '</option>').join('');
    };
    const load = async (keepScroll = false) => {
        try {
            const r = await window.api(U.feed);
            F = r.data; EV = F.events;
            paintHead(); paint(keepScroll);
        } catch (err) { $('acFeed').innerHTML = '<p class="ac-sys">' + esc(err.message) + '</p>'; }
    };
    $('acFeed').addEventListener('click', async (e) => {
        if (e.target.id === 'acMore') {
            try { const r = await window.api(U.feed + '?before=' + (EV[0] ? EV[0].id : 0)); EV = r.data.events.concat(EV); F.hasMore = r.data.hasMore; paint(true); } catch (_) {}
            return;
        }
        const card = e.target.closest('[data-task]');
        const peek = e.target.closest('[data-peek]');
        if (peek) { const t = F.soon.find((x) => String(x.id) === peek.dataset.peek); if (t) peekTask(t); return; }
        if (!card) return;
        const id = Number(card.dataset.task);
        const t = F.today.find((x) => x.id === id) || F.soon.find((x) => x.id === id);
        if (e.target.closest('[data-toggle]')) { card.classList.toggle('is-open'); card.classList.contains('is-open') ? open.add(id) : open.delete(id); return; }
        if (e.target.closest('[data-done]')) return doneModal(t, card);
        if (e.target.closest('[data-cant]')) return cantModal(t, card);
    });

    /* ---- modals ---- */
    const modal = (html, mount) => { const m = $('acModal'); $('acModalBox').innerHTML = html; m.hidden = false; void m.offsetWidth; m.classList.add('is-on'); $('acModalBox').querySelectorAll('[data-close]').forEach((b) => b.addEventListener('click', closeModal)); mount && mount($('acModalBox')); };
    const closeModal = () => { const m = $('acModal'); m.classList.remove('is-on'); setTimeout(() => { m.hidden = true; }, 300); };
    $('acModal').addEventListener('click', (e) => { if (e.target === $('acModal')) closeModal(); });
    const peekTask = (t) => modal('<div class="ac-task is-open" data-tone="' + esc(t.tone) + '" style="border:0">' + taskCard(t, 0).replace(/^<div[^>]*>/, '').replace(/<div class="ac-tbtns">[\s\S]*?<\/div>/, '') + '<p class="text-xs text-gray-500 mt-2 px-1">Planned for ' + esc(t.when) + '. Anee moves it if the weather or the workers change.</p><div class="ac-macts"><button type="button" class="btn btn-white" data-close>Close</button></div>');
    const today = () => new Date(Date.now() - new Date().getTimezoneOffset() * 60000).toISOString().slice(0, 10);
    const doneModal = (t, card) => modal('<h3>✅ ' + esc(t.title) + '</h3><p class="ac-msub">Anee keeps it in the season log. If it went in on another day, say which: the days after it count from there.</p>'
        + '<label class="ac-lab" for="acDDate">Done on</label><input type="date" id="acDDate" class="form-input" max="' + today() + '" value="' + today() + '">'
        + '<label class="ac-lab" for="acDUsed">What you used <small class="font-normal text-gray-400">(optional)</small></label><input type="text" id="acDUsed" class="form-input w-full" maxlength="400" placeholder="Like 2 bags of urea, or the product and how much">'
        + (F.people.length ? '<span class="ac-lab">Who did it</span><div class="ac-pills" id="acDWho">' + F.people.map((p) => '<button type="button" class="ac-pill" data-w="' + p.id + '" aria-pressed="' + (t.workers || []).includes(p.name) + '">' + esc(p.name) + '</button>').join('') + '</div>' : '')
        + '<label class="ac-lab" for="acDNote">Notes <small class="font-normal text-gray-400">(optional)</small></label><textarea id="acDNote" class="form-textarea w-full" rows="2" maxlength="2000" placeholder="Anything worth remembering"></textarea>'
        + '<label class="ac-lab">A photo <small class="font-normal text-gray-400">(optional)</small></label><input type="file" id="acDPhoto" accept="image/*" capture="environment" class="text-sm">'
        + '<div class="ac-macts"><button type="button" class="btn btn-white" data-close>Cancel</button><button type="button" class="btn btn-primary" id="acDOk">Mark done</button></div>', (box) => {
        box.querySelector('#acDWho')?.addEventListener('click', (e) => { const b = e.target.closest('[data-w]'); if (b) b.setAttribute('aria-pressed', String(b.getAttribute('aria-pressed') !== 'true')); });
        box.querySelector('#acDOk').addEventListener('click', async (e) => {
            const fd = new FormData();
            fd.append('date', box.querySelector('#acDDate').value || today());
            fd.append('used', box.querySelector('#acDUsed').value); fd.append('note', box.querySelector('#acDNote').value);
            box.querySelectorAll('#acDWho [aria-pressed="true"]').forEach((b) => fd.append('workers[]', b.dataset.w));
            const ph = box.querySelector('#acDPhoto').files[0]; if (ph) fd.append('photo', ph);
            e.target.disabled = true; e.target.textContent = 'Saving…';
            try { await window.api(U.done(t.id), { method: 'POST', body: fd }); closeModal(); card.classList.add('is-leaving'); setTimeout(() => load(true).then(scrollEnd), 380); }
            catch (err) { window.toast?.(err.message, 'error'); e.target.disabled = false; e.target.textContent = 'Mark done'; }
        });
    });
    const cantModal = (t, card) => modal('<h3>🙅 Can\'t do it today?</h3><p class="ac-msub">' + esc(t.title) + '. Say why and Anee moves it to the best day near it' + (t.critical ? ': this one matters for the yield, so she keeps it close.' : '.') + '</p>'
        + '<div class="ac-reasons" id="acCWhy">' + F.cant.map((c) => '<button type="button" class="ac-reason" data-r="' + c.key + '" aria-pressed="false"><span>' + c.emoji + '</span>' + esc(c.label) + '</button>').join('') + '</div>'
        + '<span class="ac-lab">Not before</span><div class="ac-pills" id="acCDays">' + [[0, 'Anee decides'], [1, 'Tomorrow'], [2, 'In 2 days'], [3, 'In 3 days'], [7, 'Next week']].map(([d, l]) => '<button type="button" class="ac-pill" data-d="' + d + '" aria-pressed="' + (d === 0) + '">' + l + '</button>').join('') + '</div>'
        + '<label class="ac-lab" for="acCNote">Anything to add <small class="font-normal text-gray-400">(optional)</small></label><textarea id="acCNote" class="form-textarea w-full" rows="2" maxlength="1000"></textarea>'
        + '<div class="ac-macts"><button type="button" class="btn btn-white" data-close>Cancel</button><button type="button" class="btn btn-primary" id="acCOk" disabled>Move it</button></div>', (box) => {
        box.querySelector('#acCWhy').addEventListener('click', (e) => { const b = e.target.closest('[data-r]'); if (!b) return; box.querySelectorAll('[data-r]').forEach((x) => x.setAttribute('aria-pressed', String(x === b))); box.querySelector('#acCOk').disabled = false; });
        box.querySelector('#acCDays').addEventListener('click', (e) => { const b = e.target.closest('[data-d]'); if (!b) return; box.querySelectorAll('[data-d]').forEach((x) => x.setAttribute('aria-pressed', String(x === b))); });
        box.querySelector('#acCOk').addEventListener('click', async (e) => {
            const r = box.querySelector('[data-r][aria-pressed="true"]'), d = Number(box.querySelector('[data-d][aria-pressed="true"]')?.dataset.d || 0);
            e.target.disabled = true; e.target.textContent = 'Moving…';
            try { const res = await window.api(U.cant(t.id), { method: 'POST', body: { reason: r.dataset.r, note: box.querySelector('#acCNote').value, days: d || null } }); closeModal(); card.classList.add('is-leaving'); window.toast?.(res.message, 'success'); setTimeout(() => load(true).then(scrollEnd), 380); }
            catch (err) { window.toast?.(err.message, 'error'); e.target.disabled = false; e.target.textContent = 'Move it'; }
        });
    });
    const scrollEnd = () => requestAnimationFrame(() => { const t = $('acToday'); const last = t ? t.previousElementSibling : null; (last || t)?.scrollIntoView({ block: 'center', behavior: still() ? 'auto' : 'smooth' }); });

    $('acSafeBtn').addEventListener('click', () => modal('<h3>🛡️ Safety and mixing</h3><p class="ac-msub">Anee checks every spray against these. Read the product label too: it is the law.</p>'
        + ((F.plan.safety || []).length ? '<div class="ac-safe"><ul>' + F.plan.safety.map((s) => '<li>' + esc(s) + '</li>').join('') + '</ul></div>' : '<p class="text-sm text-gray-500">No safety notes yet.</p>')
        + ((F.plan.compatibility || []).length ? '<span class="ac-lab">Do not mix</span><ul class="text-sm text-gray-700 list-disc ml-5">' + F.plan.compatibility.map((s) => '<li>' + esc(s) + '</li>').join('') + '</ul>' : '')
        + '<div class="ac-macts"><button type="button" class="btn btn-primary" data-close>Got it</button></div>'));
    $('acLogBtn').addEventListener('click', async () => {
        modal('<h3>📖 Season log</h3><p class="ac-msub">Loading…</p>');
        try {
            const r = await window.api(U.log), L = r.data;
            $('acModalBox').innerHTML = '<h3>📖 Season log</h3><p class="ac-msub">' + L.counts.done + ' tasks done, ' + L.counts.moved + ' moved, ' + L.counts.observations + ' observations. Kept for the post harvest report.</p>'
                + L.days.map((d) => '<div class="ac-log-day"><h4>' + esc(d.label) + '</h4>' + d.rows.map((x) => '<div class="ac-log-row"><i>' + esc(x.at) + '</i><span>' + (x.role === 'farmer' ? '<b>You</b> ' : '<b>Anee</b> ') + (x.lot ? '(' + esc(x.lot) + ') ' : '') + esc(x.text) + '</span></div>').join('') + '</div>').join('')
                + '<div class="ac-macts"><button type="button" class="btn btn-primary" data-close>Close</button></div>';
            $('acModalBox').querySelector('[data-close]').addEventListener('click', closeModal);
        } catch (err) { window.toast?.(err.message, 'error'); }
    });

    /* ---- the composer ---- */
    let mode = 'ask', photos = [], video = null, frames = [], voice = null, rec = null, recChunks = [], recStream = null, recT0 = 0;
    const setMode = (m) => {
        mode = m;
        document.querySelectorAll('.ac-mode').forEach((b) => b.setAttribute('aria-pressed', String(b.dataset.mode === m)));
        $('acText').placeholder = m === 'ask' ? 'Ask Anee anything about your season' : 'What do you see? Like: yellow leaves on the lower part, patches near the canal';
        $('acLotPick').hidden = m !== 'observe' || !(F && F.lots.length > 1);
    };
    document.querySelector('.ac-modes').addEventListener('click', (e) => { const b = e.target.closest('[data-mode]'); if (b) setMode(b.dataset.mode); });
    const paintAtt = () => {
        $('acAtt').innerHTML = photos.map((p, i) => '<span><img src="' + p.url + '" alt=""><button type="button" data-rm="p' + i + '">✕</button></span>').join('')
            + (video ? '<span>' + (frames[0] ? '<img src="' + frames[0] + '" alt="">' : '') + '🎥 Video<button type="button" data-rm="v">✕</button></span>' : '')
            + (voice ? '<span>🎙️ ' + voice.secs + 's voice note<button type="button" data-rm="a">✕</button></span>' : '');
        if (photos.length || video || voice) setMode('observe');
    };
    $('acAtt').addEventListener('click', (e) => { const b = e.target.closest('[data-rm]'); if (!b) return; const k = b.dataset.rm; if (k === 'v') { video = null; frames = []; } else if (k === 'a') voice = null; else photos.splice(Number(k.slice(1)), 1); paintAtt(); });
    $('acPhoto').addEventListener('click', () => $('acPhotoIn').click());
    $('acVideo').addEventListener('click', () => $('acVideoIn').click());
    $('acPhotoIn').addEventListener('change', () => { [...$('acPhotoIn').files].slice(0, 3 - photos.length).forEach((f) => photos.push({ file: f, url: URL.createObjectURL(f) })); $('acPhotoIn').value = ''; paintAtt(); });
    // Three frames from the clip, for Anee's eyes; the clip itself is kept in the log.
    const grabFrames = (file) => new Promise((resolve) => {
        const v = document.createElement('video'); v.muted = true; v.playsInline = true; v.preload = 'auto'; v.src = URL.createObjectURL(file);
        const out = []; let idx = 0, at = [];
        const snap = () => { const c = document.createElement('canvas'); const w = Math.min(960, v.videoWidth || 640); c.width = w; c.height = Math.round(w * (v.videoHeight || 360) / (v.videoWidth || 640)); c.getContext('2d').drawImage(v, 0, 0, c.width, c.height); out.push(c.toDataURL('image/jpeg', .8)); };
        v.addEventListener('loadedmetadata', () => { const d = v.duration || 1; at = [d * .15, d * .5, d * .85]; v.currentTime = at[0]; });
        v.addEventListener('seeked', () => { try { snap(); } catch (_) {} idx++; if (idx < at.length) v.currentTime = at[idx]; else resolve(out); });
        v.addEventListener('error', () => resolve(out));
        setTimeout(() => resolve(out), 9000);
    });
    $('acVideoIn').addEventListener('change', async () => {
        const f = $('acVideoIn').files[0]; $('acVideoIn').value = ''; if (!f) return;
        if (f.size > 60 * 1024 * 1024) return window.toast?.('That video is over 60 MB. Record a shorter one (10 to 20 seconds is plenty).', 'error');
        video = f; frames = []; paintAtt(); frames = await grabFrames(f); paintAtt();
    });
    $('acVoice').addEventListener('click', async () => {
        if (rec && rec.state === 'recording') { rec.stop(); return; }
        if (!navigator.mediaDevices || !window.MediaRecorder) return window.toast?.('This browser cannot record audio.', 'error');
        try { recStream = await navigator.mediaDevices.getUserMedia({ audio: true }); } catch (_) { return window.toast?.('Microphone blocked. Allow it for this site.', 'error'); }
        const mime = ['audio/webm;codecs=opus', 'audio/webm', 'audio/mp4'].find((t) => MediaRecorder.isTypeSupported(t)) || '';
        rec = new MediaRecorder(recStream, mime ? { mimeType: mime } : undefined); recChunks = [];
        rec.ondataavailable = (e) => { if (e.data && e.data.size) recChunks.push(e.data); };
        rec.onstop = () => { recStream.getTracks().forEach((t) => t.stop()); $('acVoice').classList.remove('is-rec'); $('acVoice').textContent = '🎙️';
            const type = rec.mimeType || 'audio/webm'; voice = { blob: new Blob(recChunks, { type }), ext: type.includes('mp4') ? 'm4a' : 'webm', secs: Math.round((Date.now() - recT0) / 1000) }; paintAtt(); };
        rec.start(250); recT0 = Date.now(); $('acVoice').classList.add('is-rec'); $('acVoice').textContent = '⏹';
        setTimeout(() => { if (rec && rec.state === 'recording') rec.stop(); }, 180000);
    });
    $('acText').addEventListener('input', () => { const t = $('acText'); t.style.height = 'auto'; t.style.height = Math.min(128, t.scrollHeight) + 'px'; });
    const typing = () => { $('acToday')?.insertAdjacentHTML('beforebegin', '<div class="ac-msg" id="acTyping"><span class="ac-av"><img src="' + FACE + '" alt=""></span><div class="ac-bub"><span class="ac-typing"><i></i><i></i><i></i></span></div></div>'); scrollEnd(); };
    $('acSend').addEventListener('click', async () => {
        const text = $('acText').value.trim();
        if (mode === 'ask' && !text) return;
        if (mode === 'observe' && !text && !photos.length && !video && !voice) return window.toast?.('Add a photo, a video, a voice note or a few words.', 'error');
        $('acSend').disabled = true;
        const mine = '<div class="ac-msg is-me"><div class="ac-bub">' + esc(text || (voice ? '🎙️ Voice note' : '📷 From the field')) + (photos.length ? '<div class="ac-media">' + photos.map((p) => '<img src="' + p.url + '" alt="">').join('') + '</div>' : '') + '</div></div>';
        $('acToday') ? $('acToday').insertAdjacentHTML('beforebegin', mine) : $('acFeed').insertAdjacentHTML('beforeend', mine);
        typing();
        try {
            if (mode === 'ask') await window.api(U.ask, { method: 'POST', body: { text } });
            else {
                const fd = new FormData();
                fd.append('text', text); if ($('acLotPick').value) fd.append('lotId', $('acLotPick').value);
                photos.forEach((p) => fd.append('photos[]', p.file));
                frames.forEach((f) => fd.append('frames[]', f));
                if (video) fd.append('video', video);
                if (voice) fd.append('audio', voice.blob, 'voice.' + voice.ext);
                await window.api(U.observe, { method: 'POST', body: fd });
            }
            $('acText').value = ''; $('acText').style.height = ''; photos = []; video = null; frames = []; voice = null; paintAtt(); setMode('ask');
        } catch (err) { window.toast?.(err.message, 'error'); }
        $('acSend').disabled = false;
        await load(true); scrollEnd();
    });
    $('acText').addEventListener('keydown', (e) => { if (e.key === 'Enter' && !e.shiftKey && !matchMedia('(hover: none)').matches) { e.preventDefault(); $('acSend').click(); } });
    document.addEventListener('visibilitychange', () => { if (!document.hidden && F) load(true); });

    load();
})();
</script>
@endpush
