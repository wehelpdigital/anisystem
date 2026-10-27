@extends('layouts.app')
@section('title', 'Global Workers')
{{-- A tool page: no bottom tab bar on a phone and no footer (2026-10-15, the owner). --}}
@section('body-class', 'hide-tabbar no-footer')
@section('page-title', 'Global Workers')
@section('page-subtitle', 'Your crew, for every season')
@section('back', \App\Support\BackTo::url(route('app.dashboard')))

{{-- Global Workers (2026-10-12): the farm's own roster above the seasons.
     Each person once (name, contact, usual pay, skills, weekdays off), added
     to any season in a tap, where they become that season's card. See
     App\Http\Controllers\GlobalWorkerController. --}}
@push('head')
<style>
    .gw-hero { position: relative; overflow: hidden; display: flex; align-items: center; gap: 1rem; padding: 1.05rem 1.15rem; border-radius: 1.3rem; color: #e8f1de;
        background: radial-gradient(120% 140% at 100% 0%, #4a7c2a 0%, #1d3310 55%, #0f1d08 100%); }
    .gw-hero img { flex: none; width: 3.2rem; height: 3.2rem; padding: .45rem; border-radius: 1rem; background: rgb(255 255 255 / .12); animation: gwBob 4s ease-in-out infinite; }
    @keyframes gwBob { 50% { transform: translateY(-3px) rotate(-2deg); } }
    .gw-hero h2 { font-family: var(--font-heading); font-weight: 800; font-size: 1.15rem; color: #fff; }
    .gw-hero p { margin-top: .2rem; font-size: .84rem; line-height: 1.5; color: #c9dcb5; }
    .gw-bar { display: flex; flex-wrap: wrap; align-items: center; gap: .6rem; margin: 1rem 0; }
    .gw-search { flex: 1 1 14rem; position: relative; }
    .gw-search input { width: 100%; padding-left: 2.3rem; }
    .gw-search svg { position: absolute; left: .75rem; top: 50%; translate: 0 -50%; width: 1rem; height: 1rem; color: var(--color-gray-400); }
    .gw-grid { display: grid; gap: .8rem; }
    @media (min-width: 768px) { .gw-grid { grid-template-columns: repeat(2, minmax(0, 1fr)); } }
    .gw-card { padding: .95rem 1rem; border-radius: 1.2rem; background: var(--color-white); border: 1px solid var(--color-gray-200);
        animation: gwRise .34s cubic-bezier(.22,1,.36,1) both; animation-delay: calc(var(--i) * 40ms); transition: border-color .28s cubic-bezier(.22,1,.36,1), box-shadow .28s cubic-bezier(.22,1,.36,1); }
    .gw-card:hover { border-color: var(--color-brand-300, #a8cc7e); box-shadow: 0 16px 32px -26px rgb(15 23 42 / .5); }
    @keyframes gwRise { from { opacity: 0; transform: translateY(8px); } }
    .gw-top { display: flex; align-items: center; gap: .7rem; }
    .gw-av { flex: none; width: 2.6rem; height: 2.6rem; border-radius: 999px; display: grid; place-items: center; font-weight: 800; color: #fff; background: hsl(var(--h) 45% 38%); }
    .gw-who { flex: 1; min-width: 0; }
    .gw-who b { display: block; font-weight: 800; color: var(--color-gray-900); }
    .gw-who small { display: block; font-size: .78rem; color: var(--color-gray-500); overflow: hidden; text-overflow: ellipsis; white-space: nowrap; }
    .gw-pay { flex: none; text-align: right; font-size: .8rem; font-weight: 800; color: var(--color-brand-700); }
    .gw-pay small { display: block; font-size: .66rem; font-weight: 700; color: var(--color-gray-500); }
    .gw-chips { display: flex; flex-wrap: wrap; gap: .3rem; margin-top: .6rem; }
    .gw-chip { padding: .16rem .55rem; border-radius: 999px; font-size: .7rem; font-weight: 700; color: var(--color-gray-700); background: var(--color-gray-100); }
    .gw-chip.is-off { color: #8a5a00; background: #fff4d6; }
    .gw-chip.is-half { color: #2d5016; background: #eef6e4; }
    .gw-chip.is-season { color: var(--color-brand-700); background: #eaf4dc; text-decoration: none; }
    .gw-acts { display: flex; flex-wrap: wrap; gap: .4rem; margin-top: .75rem; }
    .gw-acts .btn { padding: .35rem .7rem; font-size: .78rem; }
    .gw-empty { padding: 2rem 1rem; text-align: center; border-radius: 1.2rem; border: 1px dashed var(--color-gray-300); color: var(--color-gray-500); }
    .gw-pills { display: flex; flex-wrap: wrap; gap: .35rem; }
    .gw-pill { padding: .4rem .7rem; border-radius: 999px; font-size: .8rem; font-weight: 700; color: var(--color-gray-600); background: var(--color-gray-100);
        transition: background-color .28s cubic-bezier(.22,1,.36,1), color .28s cubic-bezier(.22,1,.36,1); }
    .gw-pill[aria-pressed="true"] { color: #fff; background: var(--color-brand-600); }
    .gw-seasons { display: grid; gap: .4rem; }
    .gw-srow { display: flex; align-items: center; gap: .6rem; width: 100%; padding: .6rem .7rem; border-radius: .9rem; text-align: left; background: var(--color-gray-50); border: 1px solid var(--color-gray-100); }
    .gw-srow b { flex: 1; font-weight: 700; color: var(--color-gray-900); }
    .gw-srow em { font-style: normal; font-size: .74rem; font-weight: 800; color: var(--color-brand-700); }
    .gw-srow[disabled] { opacity: .6; cursor: default; }
    html.dark .gw-card, html.dark .gw-srow { background: #1a1d23; border-color: #2a2f38; }
    html.dark .gw-who b, html.dark .gw-srow b { color: #e5e7eb; }
    html.dark .gw-chip { background: #20242c; color: #cbd5e1; } html.dark .gw-chip.is-season { background: #1c2616; color: #c5e09f; } html.dark .gw-chip.is-off { background: #2a2210; color: #f3d9a4; } html.dark .gw-chip.is-half { background: #1c2616; color: #c5e09f; }
    @media (prefers-reduced-motion: reduce) { .gw-hero img, .gw-card { animation: none; } .gw-card, .gw-pill { transition: none; } }

    /* Login access (2026-10-18, the owner): the same login a season card gives,
       given here for the person. The strip on each card is a tag: it opens the
       login sheet, and says where the login stands without opening it. */
    .gw-login { display: flex; align-items: center; gap: .6rem; width: 100%; margin-top: .7rem; padding: .5rem .65rem; border-radius: .95rem; text-align: left;
        border: 1px dashed var(--color-gray-300); background: var(--color-gray-50); cursor: pointer;
        transition: border-color .28s cubic-bezier(.22,1,.36,1), background-color .28s cubic-bezier(.22,1,.36,1), transform .28s cubic-bezier(.22,1,.36,1); }
    .gw-login:hover { transform: translateY(-1px); border-color: var(--color-brand-300, #a8cc7e); }
    .gw-login:focus-visible { outline: 2px solid var(--color-brand-500); outline-offset: 2px; }
    .gw-login-ico { flex: none; width: 2rem; height: 2rem; border-radius: .7rem; display: grid; place-items: center; color: var(--color-gray-500); background: var(--color-gray-100); }
    .gw-login-ico svg { width: 1.05rem; height: 1.05rem; }
    .gw-login-t { flex: 1; min-width: 0; }
    .gw-login-t b { display: block; font-size: .8rem; font-weight: 800; color: var(--color-gray-900); }
    .gw-login-t small { display: block; font-size: .72rem; color: var(--color-gray-500); overflow: hidden; text-overflow: ellipsis; white-space: nowrap; }
    .gw-login-go { flex: none; font-size: .74rem; font-weight: 800; color: var(--color-brand-700); white-space: nowrap; }
    .gw-login.is-active { border-style: solid; border-color: #cfe3b4; background: #f3f9ea; }
    .gw-login.is-active .gw-login-ico { color: #3f6d1f; background: #e1f0cc; }
    .gw-login.is-pending { border-style: solid; border-color: #f3dc9b; background: #fffaeb; }
    .gw-login.is-pending .gw-login-ico { color: #8a5a00; background: #fff0c2; }
    .gw-login.is-locked .gw-login-go { color: var(--color-gray-500); }
    html.dark .gw-login.is-active { background: #17210f; border-color: #2f4d24; }
    html.dark .gw-login.is-active .gw-login-ico { background: #26391a; color: #c5e09f; }
    html.dark .gw-login.is-active .gw-login-t b { color: #e8efe1; }
    html.dark .gw-login.is-pending { background: #241d0c; border-color: #4a3a12; }
    html.dark .gw-login.is-pending .gw-login-ico { background: #3a2e10; color: #f3d9a4; }
    html.dark .gw-login.is-pending .gw-login-t b { color: #f6ead0; }
    .gw-lock-note { margin-bottom: 1rem; padding: .8rem 1rem; border-radius: 1.1rem; font-size: .85rem; line-height: 1.5; color: var(--color-gray-700); background: var(--color-white); border: 1px solid #f3dc9b; }

    /* The login sheet. */
    .gl-head { display: flex; align-items: center; gap: .65rem; }
    .gl-head-ico { flex: none; width: 2.4rem; height: 2.4rem; border-radius: .85rem; display: grid; place-items: center; color: var(--color-brand-700); background: var(--color-brand-50); }
    .gl-head-ico svg { width: 1.2rem; height: 1.2rem; }
    .gl-head-t { flex: 1; min-width: 0; }
    .gl-head-t b { display: block; font-weight: 800; color: var(--color-gray-900); }
    .gl-head-t small { display: block; font-size: .78rem; color: var(--color-gray-500); }
    .gl-revoke { flex: none; display: grid; place-items: center; width: 2.4rem; height: 2.4rem; border-radius: 999px; color: #dc2626; transition: background-color .28s cubic-bezier(.22,1,.36,1); }
    .gl-revoke:hover { background: rgb(220 38 38 / .1); }
    .gl-revoke[hidden] { display: none; }
    .gl-revoke svg { width: 1.2rem; height: 1.2rem; }
    .gl-reach { padding: .65rem .8rem; border-radius: .9rem; font-size: .78rem; line-height: 1.5; color: var(--color-gray-600); background: var(--color-gray-50); border: 1px solid var(--color-gray-100); }
    .gl-reach b { color: var(--color-gray-900); }
    .gl-email-fixed { font-size: .82rem; color: var(--color-gray-600); }
    .gl-email-fixed b { color: var(--color-gray-900); word-break: break-all; }
    .gl-email-fixed[hidden], #glEmailWrap[hidden] { display: none; }
    .gl-btns { display: flex; flex-direction: column; gap: .5rem; }
    @media (min-width: 640px) { .gl-btns { flex-direction: row; flex-wrap: wrap; } }
    .gl-btns .btn { justify-content: center; animation: glIn .28s cubic-bezier(.22,1,.36,1) both; }
    .gl-btns .btn[hidden] { display: none; }
    @keyframes glIn { from { opacity: 0; transform: translateY(4px); } }
    /* Yellow is the important call to action (the owner, 2026-10-17). */
    .btn.gl-cta { color: #3b2f00; background: #f5c518; border-color: #f5c518; box-shadow: 0 10px 22px -14px rgb(245 197 24 / .9); }
    .btn.gl-cta:hover { background: #ffd23f; border-color: #ffd23f; }
    .btn.gl-cta:disabled { opacity: .6; }
    /* Folds: the rights panel arrives with the login, the password card on a tap.
       The rows grow from nothing on the house easing; shut, they are inert. */
    .gl-fold { display: grid; grid-template-rows: 0fr; opacity: 0; visibility: hidden;
        transition: grid-template-rows .28s cubic-bezier(.22,1,.36,1), opacity .28s cubic-bezier(.22,1,.36,1), visibility 0s linear .28s; }
    .gl-fold.is-open { grid-template-rows: 1fr; opacity: 1; visibility: visible; transition-delay: 0s, 0s, 0s; }
    .gl-fold-in { overflow: hidden; min-height: 0; }
    .gl-say { font-size: .78rem; line-height: 1.5; color: var(--color-gray-500); }
    .gl-say[hidden] { display: none; }
    .gl-strength { height: .45rem; border-radius: 999px; background: var(--color-gray-100); overflow: hidden; }
    .gl-strength i { display: block; height: 100%; width: 0; border-radius: inherit; background: #ef4444;
        transition: width .28s cubic-bezier(.22,1,.36,1), background .28s cubic-bezier(.22,1,.36,1); }
    @media (prefers-reduced-motion: reduce) {
        .gw-login, .gl-revoke, .gl-fold, .gl-strength i { transition: none; }
        .gl-btns .btn { animation: none; }
    }

    /* WHERE THEY CAN WORK (2026-10-22): the login's access settings, one row
       each, opened to choose what they can open there. */
    .ga-box { padding: .85rem; border-radius: 1.1rem; border: 1px solid var(--color-gray-200); background: var(--color-gray-50); }
    .ga-top { display: flex; align-items: center; gap: .6rem; }
    .ga-top-t { flex: 1; min-width: 0; }
    .ga-top-t b { display: block; font-weight: 800; color: var(--color-gray-900); }
    .ga-top-t small { display: block; font-size: .74rem; color: var(--color-gray-500); }
    .ga-intro { margin-top: .45rem; font-size: .76rem; line-height: 1.5; color: var(--color-gray-500); }
    .ga-intro b { color: var(--color-gray-700); }
    .ga-list { display: grid; gap: .45rem; margin-top: .65rem; }
    .ga-row { display: flex; align-items: center; gap: .65rem; width: 100%; padding: .6rem .7rem; border-radius: .9rem; text-align: left; cursor: pointer;
        background: var(--color-white); border: 1px solid var(--color-gray-200); animation: gaIn .3s cubic-bezier(.22,1,.36,1) both; animation-delay: calc(var(--i) * 40ms);
        transition: border-color .28s cubic-bezier(.22,1,.36,1), transform .28s cubic-bezier(.22,1,.36,1); }
    .ga-row:hover { border-color: var(--color-brand-300, #a8cc7e); transform: translateY(-1px); }
    .ga-row:focus-visible { outline: 2px solid var(--color-brand-500); outline-offset: 2px; }
    @keyframes gaIn { from { opacity: 0; transform: translateY(5px); } }
    .ga-ico { flex: none; width: 2.1rem; height: 2.1rem; border-radius: .7rem; display: grid; place-items: center; color: #3f6d1f; background: #e8f3da; }
    .ga-row.is-all .ga-ico { color: #8a5a00; background: #fff0c2; }
    .ga-row.is-gone .ga-ico { color: var(--color-gray-400); background: var(--color-gray-100); }
    .ga-ico svg { width: 1.05rem; height: 1.05rem; }
    .ga-t { flex: 1; min-width: 0; }
    .ga-t b { display: block; font-size: .84rem; font-weight: 800; color: var(--color-gray-900); }
    .ga-t small { display: block; font-size: .72rem; line-height: 1.4; color: var(--color-gray-500); }
    .ga-go { flex: none; width: 1rem; height: 1rem; color: var(--color-gray-400); }
    .ga-none { padding: .7rem .8rem; border-radius: .9rem; border: 1px dashed var(--color-gray-300); font-size: .78rem; line-height: 1.5; color: var(--color-gray-600); background: var(--color-white); }
    .ga-want { display: flex; flex-wrap: wrap; align-items: center; gap: .5rem .75rem; margin-top: .65rem; padding: .6rem .75rem; border-radius: .9rem; font-size: .78rem; line-height: 1.45;
        color: #7a4b00; background: #fff8e1; border: 1px solid #f3dc9b; animation: gaIn .3s cubic-bezier(.22,1,.36,1) both; }
    .ga-want[hidden] { display: none; }
    .ga-want .btn { margin-left: auto; }
    .ga-say { font-size: .8rem; line-height: 1.5; color: var(--color-gray-600); }
    .ga-say b { color: var(--color-gray-900); }
    .btn.ga-remove { color: #dc2626; }
    .gs-say { display: block; font-size: .72rem; font-weight: 600; color: var(--color-gray-500); }
    html.dark .ga-box { background: #151b12; border-color: #2b3a1c; }
    html.dark .ga-row, html.dark .ga-none { background: #1a1d23; border-color: #2a2f38; }
    html.dark .ga-top-t b, html.dark .ga-t b, html.dark .ga-say b, html.dark .ga-intro b { color: #e8efe1; }
    html.dark .ga-ico { background: #26391a; color: #c5e09f; }
    html.dark .ga-row.is-all .ga-ico { background: #3a2e10; color: #f3d9a4; }
    html.dark .ga-want { background: #241d0c; border-color: #4a3a12; color: #f3d9a4; }
    @media (prefers-reduced-motion: reduce) { .ga-row, .ga-want { animation: none; transition: none; } }
</style>
@endpush

@section('content')
<div class="max-w-5xl mx-auto">
    <div class="gw-hero">
        <img src="{{ asset('images/friends.png') }}" alt="">
        <div><h2>Global Workers</h2><p>Your crew in one list. Add someone once, with their pay, skills and the weekdays they do not work, then put them on any season in a tap. Each season keeps its own record of their work and pay.@if ($canLogins) Give anyone their own login here, and choose where they can work: all cropping schedules, or season by season.@endif</p></div>
    </div>
    {{-- The same words the season Workers page says for a plan without logins. --}}
    @unless ($canLogins)
        <p class="gw-lock-note mt-4">🔒 <strong>Worker logins</strong> come with <strong>{{ \App\Support\Tier::withPlan(\App\Support\Tier::unlocksAt('workerLogins')) }}</strong>. <a href="{{ route('account.subscription') }}" class="text-brand-600 font-semibold">Upgrade</a> to give workers their own login and email notifications.</p>
    @endunless
    <div class="gw-bar">
        <label class="gw-search"><span class="sr-only">Find a worker</span>
            <svg fill="none" stroke="currentColor" stroke-width="2.2" viewBox="0 0 24 24" aria-hidden="true"><circle cx="11" cy="11" r="6.5"/><path stroke-linecap="round" d="M20 20l-4.2-4.2"/></svg>
            <input type="search" id="gwQ" class="form-input" placeholder="Find a worker" autocomplete="off">
        </label>
        <button type="button" class="btn btn-primary" id="gwAdd">+ Add a worker</button>
    </div>
    <div class="gw-grid" id="gwGrid"><div class="gw-empty">Loading your workers…</div></div>
</div>

{{-- Add or edit a worker. --}}
<div class="sheet hidden" id="gwSheet" style="--sheet-width:32rem">
    <div class="sheet-handle"></div>
    <div class="sheet-header">
        <h3 class="sheet-title" id="gwSheetTitle">Add a worker</h3>
        <button type="button" data-sheet-close class="btn-ghost p-2 rounded-full" aria-label="Close">✕</button>
    </div>
    <div class="sheet-body space-y-4">
        <div><label class="form-label" for="gwName">Name <span class="text-red-500">*</span></label><input type="text" id="gwName" maxlength="255" class="form-input" placeholder="e.g. Juan Dela Cruz"></div>
        <div class="grid grid-cols-2 gap-3">
            <div><label class="form-label" for="gwEmail">Email <span class="text-gray-400 font-normal">(optional)</span></label><input type="email" id="gwEmail" maxlength="255" class="form-input"></div>
            <div><label class="form-label" for="gwPhone">Phone <span class="text-gray-400 font-normal">(optional)</span></label><input type="tel" id="gwPhone" maxlength="32" class="form-input"></div>
        </div>
        <div><label class="form-label" for="gwCost">Usual pay per half day</label>
            <div class="relative"><span class="absolute left-4 top-1/2 -translate-y-1/2 text-gray-500 font-semibold pointer-events-none">{{ \App\Support\Region::symbol() }}</span>
                <input type="number" id="gwCost" min="0" step="0.01" class="form-input pl-9!" placeholder="0.00"></div>
            <p class="form-hint">A season starts from this; each season can pay its own.</p></div>
        <div><span class="form-label">Skills</span><div class="gw-pills" id="gwSkills">
            @foreach ($skills as $k => $label)<button type="button" class="gw-pill" data-skill="{{ $k }}" aria-pressed="false">{{ $label }}</button>@endforeach
        </div></div>
        <div><span class="form-label">Days they work</span><div id="gwOff"></div>
            <p class="form-hint">Tap a day to set it: a whole day, the morning only, the afternoon only, or a day off. A change here reaches every season they are in, so the plan gives them work only when they come.</p></div>
        <div><label class="form-label" for="gwNotes">Notes</label><textarea id="gwNotes" rows="2" maxlength="2000" class="form-input"></textarea></div>
    </div>
    <div class="sheet-footer"><button type="button" class="btn btn-primary w-full" id="gwSave">Save worker</button></div>
</div>

{{-- Onto a season. --}}
<div class="sheet hidden" id="gwSeasonSheet" style="--sheet-width:28rem">
    <div class="sheet-handle"></div>
    <div class="sheet-header">
        <h3 class="sheet-title" id="gwSeasonTitle">Add to a season</h3>
        <button type="button" data-sheet-close class="btn-ghost p-2 rounded-full" aria-label="Close">✕</button>
    </div>
    <div class="sheet-body"><div class="gw-seasons" id="gwSeasonList"></div></div>
</div>

@if ($canLogins)
{{-- Login access, for the person (2026-10-18, the owner), and since
     2026-10-22 the one place it is given: Global Workers is the farm's worker
     module, and a season's Workers page only picks people from it. The sheet
     is the login (an address and a way in), then WHERE THEY CAN WORK: access
     settings for all cropping schedules or for one season, each opened to
     choose what they can open there (WorkerAccess), then the login's own home
     screen tools. --}}
<div class="sheet hidden" id="gwLoginSheet" style="--sheet-width:34rem">
    <div class="sheet-handle"></div>
    <div class="sheet-header">
        <h3 class="sheet-title" id="glTitle">Login access</h3>
        <button type="button" data-sheet-close class="btn-ghost p-2 rounded-full" aria-label="Close">✕</button>
    </div>
    <div class="sheet-body space-y-4">
        <div class="gl-head">
            <span class="gl-head-ico" aria-hidden="true"><svg fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24"><circle cx="8" cy="15" r="4"/><path stroke-linecap="round" stroke-linejoin="round" d="M11 12l9-9m-3 3l3 3m-6 0l2 2"/></svg></span>
            <span class="gl-head-t"><b>Login</b><small id="glStatus">No login yet.</small></span>
            <button type="button" id="glRevoke" class="gl-revoke" hidden title="Remove login" aria-label="Remove this worker's login">
                <svg fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" d="M17 16l4-4m0 0l-4-4m4 4H9m4 8H7a2 2 0 01-2-2V6a2 2 0 012-2h6"/></svg>
            </button>
        </div>

        <div id="glEmailWrap">
            <label class="form-label" for="glEmail">Their email</label>
            <input type="email" id="glEmail" maxlength="191" class="form-input" placeholder="The email they sign in with" autocomplete="off">
            <p class="form-hint">They sign in with this email. If their card has none, it is saved on their card too.</p>
        </div>
        <p class="gl-email-fixed" id="glEmailFixed" hidden>Signs in with <b id="glEmailShown"></b></p>
        <p id="glNoLoginSay" class="gl-say">Send a registration link or set a password below. Then choose where they can work.</p>

        <div class="gl-btns">
            {{-- One of these two, never both: a worker who has not registered
                 needs the way in, and one who has needs the way to a new
                 password. paintLogin picks. --}}
            <button type="button" id="glSendLink" class="btn btn-sm gl-cta">✉️ Send registration link</button>
            <button type="button" id="glSendPwLink" class="btn btn-white btn-sm" hidden>✉️ Send password change link</button>
            <button type="button" id="glSetPwToggle" class="btn btn-white btn-sm">🔒 Set a password</button>
        </div>

        <div id="glPwFold" class="gl-fold" aria-hidden="true" inert>
            <div class="gl-fold-in">
                <div class="rounded-2xl border border-brand-100 bg-white p-4 space-y-3 mt-1">
                    <p class="font-bold text-gray-900 text-sm flex items-center gap-2">🔒 Password for this worker</p>
                    <div>
                        <label class="form-label" for="glPassword">New password</label>
                        <input type="password" id="glPassword" class="form-input" placeholder="At least 8 characters" autocomplete="new-password">
                        <div class="gl-strength mt-2" aria-hidden="true"><i id="glPwBar"></i></div>
                        <p class="text-xs mt-1 font-semibold text-gray-400" id="glPwSay">At least 8 characters.</p>
                    </div>
                    <div>
                        <label class="form-label" for="glPassword2">Repeat password</label>
                        <input type="password" id="glPassword2" class="form-input" placeholder="Type it again" autocomplete="new-password">
                        <p class="form-error hidden" id="glPwMatchSay">Passwords do not match yet.</p>
                    </div>
                    <button type="button" id="glCreateLogin" class="btn gl-cta w-full justify-center">Create login</button>
                    <p class="form-hint">Give them their email and this password to sign in.</p>
                </div>
            </div>
        </div>

        {{-- WHERE THEY CAN WORK (2026-10-22): arrives with the login. --}}
        <div id="glAccessWrap" class="gl-fold" aria-hidden="true" inert>
            <div class="gl-fold-in">
                <div class="ga-box">
                    <div class="ga-top">
                        <span class="ga-top-t"><b>Where they can work</b><small id="glReachSay"></small></span>
                        <button type="button" class="btn btn-sm gl-cta" id="glAddAccess">+ Add access</button>
                    </div>
                    <p class="ga-intro">Give them <b>all cropping schedules</b>, one season, or both. A season with its own access follows it; the rest follow all cropping schedules, or stay closed without it.</p>
                    <div id="glWant" class="ga-want" hidden></div>
                    <div id="glAccessList" class="ga-list"></div>
                </div>
                <div class="mt-4">@include('sm.partials.worker-rights', ['p' => 'gl', 'only' => 'tools'])</div>
            </div>
        </div>
    </div>
</div>

{{-- Add access: all cropping schedules, or one season. --}}
<div class="sheet hidden" id="gwScopeSheet" style="--sheet-width:28rem">
    <div class="sheet-handle"></div>
    <div class="sheet-header">
        <h3 class="sheet-title">Add access</h3>
        <button type="button" data-sheet-close class="btn-ghost p-2 rounded-full" aria-label="Close">✕</button>
    </div>
    <div class="sheet-body space-y-3">
        <p class="gl-say">Where can <b id="gsWho"></b> work? Then choose what they can open there.</p>
        <div class="gw-seasons" id="gsList"></div>
    </div>
</div>

{{-- One access setting, opened: what they can open there. Saves as it is tapped. --}}
<div class="sheet hidden" id="gwAccessSheet" style="--sheet-width:34rem">
    <div class="sheet-handle"></div>
    <div class="sheet-header">
        <h3 class="sheet-title" id="gaTitle">Access</h3>
        <button type="button" data-sheet-close class="btn-ghost p-2 rounded-full" aria-label="Close">✕</button>
    </div>
    <div class="sheet-body space-y-3">
        <p class="ga-say" id="gaSay"></p>
        <div id="gaRightsWrap">@include('sm.partials.worker-rights', ['p' => 'ga', 'tools' => false])</div>
    </div>
    <div class="sheet-footer">
        <button type="button" class="btn btn-white w-full ga-remove" id="gaRemove">Remove this access</button>
    </div>
</div>
@endif
@endsection

@push('scripts')
@include('partials.work-days')
<script>
(() => {
    const U = {
        list: @json(route('workers.global.list')), store: @json(route('workers.global.store')),
        one: (id) => @json(url('/app/workers')) + '/' + id, season: (id) => @json(url('/app/workers')) + '/' + id + '/season',
        seasonWorkers: (id) => @json(route('sm.workers')) + '?id=' + id,
    };
    const SEASONS = @json($seasons->map(fn ($s) => ['id' => (int) $s->id, 'title' => $s->title, 'closed' => $s->isLocked()])->values());
    const SKILLS = @json($skills);
    const DOW = ['Sun', 'Mon', 'Tue', 'Wed', 'Thu', 'Fri', 'Sat'];
    const MONEY = @json(\App\Support\Region::symbol());
    const esc = (s) => String(s ?? '').replace(/[&<>"']/g, (c) => ({ '&': '&amp;', '<': '&lt;', '>': '&gt;', '"': '&quot;', "'": '&#39;' }[c]));
    const $ = (id) => document.getElementById(id);
    let ROWS = [], editing = null, seasonFor = null;
    // Logins: the season card's endpoints, the plan's gate and its words.
    const CAN_LOGINS = @json($canLogins);
    const LOGINS_RUNG = @json(\App\Support\Tier::unlocksAt('workerLogins'));
    const LOGINS_PLAN = @json(\App\Support\Tier::withPlan(\App\Support\Tier::unlocksAt('workerLogins')));
    const A = { grant: @json(route('sm.workers.access.grant')), password: @json(route('sm.workers.access.password')), rights: @json(route('sm.workers.access.rights')),
        link: @json(route('sm.workers.access.password-link')), revoke: @json(route('sm.workers.access.revoke')),
        // Access by cropping schedule, or for all (2026-10-22).
        scopeStore: @json(route('sm.workers.access.scope.store')), scopeUpdate: @json(route('sm.workers.access.scope.update')), scopeDestroy: @json(route('sm.workers.access.scope.destroy')) };
    const KEY_SVG = '<svg fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24" aria-hidden="true"><circle cx="8" cy="15" r="4"/><path stroke-linecap="round" stroke-linejoin="round" d="M11 12l9-9m-3 3l3 3m-6 0l2 2"/></svg>';
    const LOCK_SVG = '<svg fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24" aria-hidden="true"><rect x="5" y="11" width="14" height="10" rx="2"/><path stroke-linecap="round" d="M8 11V7a4 4 0 018 0v4"/></svg>';
    // The login strip on a card: a tag that opens the login sheet.
    const loginStrip = (g) => {
        if (!CAN_LOGINS) {
            return `<button type="button" class="gw-login is-locked" data-login="${g.id}" data-tier-lock="${esc(LOGINS_RUNG)}" data-lock-say="${esc('Worker logins and access levels come with ' + LOGINS_PLAN + '.')}">
                <span class="gw-login-ico">${LOCK_SVG}</span><span class="gw-login-t"><b>Login access</b><small>Comes with ${esc(LOGINS_PLAN)}</small></span><span class="gw-login-go">Upgrade</span></button>`;
        }
        const l = g.login;
        const [cls, title, say, go] = !l ? ['', 'No login yet', 'Give them their own login to your farm', 'Give a login']
            : l.status === 'active' ? ['is-active', 'Can log in', ((l.access || []).length ? 'Opens ' + (l.reach || '') : 'Choose where they can work')
                + ((l.toolsOff || []).length ? ' · ' + l.toolsOff.length + ' dashboard ' + (l.toolsOff.length === 1 ? 'tool' : 'tools') + ' off' : ''), 'Manage']
            : ['is-pending', 'Invite sent', 'Waiting for them to set a password', 'Manage'];
        return `<button type="button" class="gw-login ${cls}" data-login="${g.id}" aria-label="Login access for ${esc(g.workerName)}: ${esc(title)}">
            <span class="gw-login-ico">${KEY_SVG}</span><span class="gw-login-t"><b>${esc(title)}</b><small>${esc(say)}</small></span><span class="gw-login-go">${esc(go)}</span></button>`;
    };

    const card = (g, i) => `<div class="gw-card" style="--i:${i}">
        <div class="gw-top"><span class="gw-av" style="--h:${(g.id * 137) % 360}">${esc((g.workerName || '?').trim().charAt(0).toUpperCase())}</span>
            <span class="gw-who"><b>${esc(g.workerName)}</b><small>${esc([g.phone, g.email].filter(Boolean).join(' · ') || 'No contact yet')}</small></span>
            <span class="gw-pay">${g.costPerHalfDay ? esc(MONEY + Number(g.costPerHalfDay).toLocaleString()) : 'No pay set'}<small>a half day</small></span></div>
        <div class="gw-chips">${(g.skills || []).map((k) => `<span class="gw-chip">${esc(SKILLS[k] || k)}</span>`).join('')}${(g.offWeekdays || []).map((d) => `<span class="gw-chip is-off">Off ${DOW[d]}</span>`).join('')}${Object.entries(window.workDays.partsOf(g.dayParts)).filter(([d]) => !(g.offWeekdays || []).map(Number).includes(Number(d))).map(([d, p]) => `<span class="gw-chip is-half">${DOW[d]} ${p === 'am' ? 'AM' : 'PM'} only</span>`).join('')}</div>
        <div class="gw-chips">${(g.seasons || []).length ? g.seasons.map((s) => `<a class="gw-chip is-season" href="${U.seasonWorkers(s.id)}">${esc(s.title)}</a>`).join('') : '<span class="gw-chip">Not on a season yet</span>'}</div>
        ${loginStrip(g)}
        <div class="gw-acts"><button type="button" class="btn btn-primary" data-act="season" data-id="${g.id}">Add to a season</button>
            <button type="button" class="btn btn-white" data-act="edit" data-id="${g.id}">Edit</button>
            <button type="button" class="btn btn-white" data-act="remove" data-id="${g.id}">Remove</button></div></div>`;
    const paint = () => {
        const q = $('gwQ').value.trim().toLowerCase();
        const rows = ROWS.filter((g) => !q || (g.workerName + ' ' + (g.phone || '') + ' ' + (g.email || '')).toLowerCase().includes(q));
        $('gwGrid').innerHTML = rows.length ? rows.map(card).join('') : `<div class="gw-empty">${ROWS.length ? 'No worker by that name.' : 'No workers yet. Add your crew once and put them on any season.'}</div>`;
    };
    const load = async () => { try { ROWS = (await window.api(U.list)).data || []; } catch (e) { ROWS = []; } paint(); openDeep?.(); };
    $('gwQ').addEventListener('input', paint);

    const pills = (box, attr, on) => box.querySelectorAll('[' + attr + ']').forEach((b) => b.setAttribute('aria-pressed', String(on.includes(isNaN(b.getAttribute(attr)) ? b.getAttribute(attr) : Number(b.getAttribute(attr))))));
    $('gwSkills').addEventListener('click', (e) => { const b = e.target.closest('.gw-pill'); if (b) b.setAttribute('aria-pressed', String(b.getAttribute('aria-pressed') !== 'true')); });
    const openEdit = (g) => {
        editing = g;
        $('gwSheetTitle').textContent = g ? 'Edit ' + g.workerName : 'Add a worker';
        $('gwName').value = g?.workerName || ''; $('gwEmail').value = g?.email || ''; $('gwPhone').value = g?.phone || '';
        $('gwCost').value = g?.costPerHalfDay || ''; $('gwNotes').value = g?.notes || '';
        pills($('gwSkills'), 'data-skill', g?.skills || []);
        $('gwOff').innerHTML = window.workDays.html(g?.offWeekdays || [], g?.dayParts || {}, g?.workerName || '');
        window.openSheet('gwSheet');
    };
    $('gwAdd').addEventListener('click', () => openEdit(null));
    $('gwSave').addEventListener('click', async () => {
        const body = { workerName: $('gwName').value.trim(), email: $('gwEmail').value.trim(), phone: $('gwPhone').value.trim(), costPerHalfDay: $('gwCost').value, notes: $('gwNotes').value,
            skills: [...$('gwSkills').querySelectorAll('[aria-pressed="true"]')].map((b) => b.dataset.skill) };
        const week = window.workDays.read($('gwOff').querySelector('[data-wd]'));
        body.offWeekdays = week.off; body.dayParts = week.parts;
        if (!body.workerName) return window.toast?.('Give the worker a name.', 'error');
        $('gwSave').disabled = true;
        try {
            const r = await window.api(editing ? U.one(editing.id) : U.store, { method: editing ? 'PUT' : 'POST', body });
            window.closeSheet('gwSheet'); window.toast?.(r.message, 'success'); await load();
        } catch (e) { window.toast?.(e.message, 'error'); } finally { $('gwSave').disabled = false; }
    });
    const openSeasons = (g) => {
        seasonFor = g;
        $('gwSeasonTitle').textContent = 'Add ' + g.workerName + ' to a season';
        const inIds = (g.seasons || []).map((s) => s.id);
        const open = SEASONS.filter((s) => !s.closed);
        $('gwSeasonList').innerHTML = open.length ? open.map((s) => `<button type="button" class="gw-srow" data-season="${s.id}" ${inIds.includes(s.id) ? 'disabled' : ''}><b>${esc(s.title)}</b><em>${inIds.includes(s.id) ? 'Already in' : 'Add'}</em></button>`).join('')
            : '<p class="text-sm text-gray-500">' + (SEASONS.length ? 'Your seasons are all closed. Reopen one or make a new season, then add your workers to it.' : 'No seasons yet. Make a season first, then add your workers to it.') + '</p>';
        window.openSheet('gwSeasonSheet');
    };
    $('gwSeasonList').addEventListener('click', async (e) => {
        const b = e.target.closest('[data-season]');
        if (!b || b.disabled || !seasonFor) return;
        b.disabled = true;
        try {
            const r = await window.api(U.season(seasonFor.id), { method: 'POST', body: { scheduleId: Number(b.dataset.season) } });
            window.closeSheet('gwSeasonSheet'); window.toast?.(r.message, 'success'); await load();
        } catch (err) { window.toast?.(err.message, 'error'); b.disabled = false; }
    });
    $('gwGrid').addEventListener('click', async (e) => {
        const tag = e.target.closest('[data-login]');
        if (tag && CAN_LOGINS) {
            const who = ROWS.find((x) => String(x.id) === tag.dataset.login);
            if (who) openLogin(who);
            return;
        }
        const b = e.target.closest('[data-act]');
        if (!b) return;
        const g = ROWS.find((x) => String(x.id) === b.dataset.id);
        if (!g) return;
        if (b.dataset.act === 'edit') openEdit(g);
        else if (b.dataset.act === 'season') openSeasons(g);
        else if (b.dataset.act === 'remove') {
            if (!confirm('Take ' + g.workerName + ' off your roster? The seasons they worked keep their record.')) return;
            try { const r = await window.api(U.one(g.id), { method: 'DELETE' }); window.toast?.(r.message, 'success'); await load(); } catch (err) { window.toast?.(err.message, 'error'); }
        }
    });
    /* ---------------- Login access ----------------
     *
     * The farm's one login for the person (it names this roster person,
     * WorkerGrant::findFor), and since 2026-10-22 the one place it is given:
     * the login, then where they can work (access settings for all cropping
     * schedules or one season, WorkerAccess), then the login's own home
     * screen tools. A season's Workers page links here. */
    let loginFor = null;          // the roster row whose login sheet is open (a live ref in ROWS)
    let accessFor = null;         // the access setting open in #gwAccessSheet
    let wantSeason = null;        // a season the page was opened for (?season=), offered first
    let rightsTimer = null, accessTimer = null;
    const EMAIL_RX = /^[^\s@]+@[^\s@]+\.[^\s@]+$/;
    const fold = (el, open) => {
        if (!el) return;
        el.classList.toggle('is-open', open);
        el.setAttribute('aria-hidden', open ? 'false' : 'true');
        if (open) el.removeAttribute('inert'); else el.setAttribute('inert', '');
    };
    const repaintStrip = (g) => {
        const el = document.querySelector('[data-login="' + g.id + '"]');
        if (el) el.outerHTML = loginStrip(g);
    };
    const seasonTitle = (id) => (SEASONS.find((s) => Number(s.id) === Number(id)) || {}).title || 'this season';
    const ALL_ICO = '<svg fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24" aria-hidden="true"><rect x="3" y="4" width="7" height="7" rx="1.5"/><rect x="14" y="4" width="7" height="7" rx="1.5"/><rect x="3" y="14" width="7" height="7" rx="1.5"/><rect x="14" y="14" width="7" height="7" rx="1.5"/></svg>';
    const ONE_ICO = '<svg fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24" aria-hidden="true"><rect x="3" y="5" width="18" height="16" rx="2"/><path stroke-linecap="round" d="M16 3v4M8 3v4M3 10h18"/></svg>';

    function paintAccessList() {
        const g = loginFor, l = g && g.login;
        const box = $('glAccessList');
        if (!l || !box) return;
        const rows = l.access || [];
        $('glReachSay').textContent = rows.length ? 'Opens ' + (l.reach || '') + '.' : 'No access yet.';
        box.innerHTML = rows.length ? rows.map((a, i) => `<button type="button" class="ga-row${a.all ? ' is-all' : ''}${a.gone ? ' is-gone' : ''}" data-access="${a.id}" style="--i:${i}">
                <span class="ga-ico">${a.all ? ALL_ICO : ONE_ICO}</span>
                <span class="ga-t"><b>${esc(a.title)}</b><small>${esc(a.gone ? 'This season was deleted. Remove this access.' : a.summary)}</small></span>
                <svg class="ga-go" fill="none" stroke="currentColor" stroke-width="2.4" viewBox="0 0 24 24" aria-hidden="true"><path stroke-linecap="round" stroke-linejoin="round" d="M9 5l7 7-7 7"/></svg></button>`).join('')
            : '<p class="ga-none">They can log in but cannot open any season yet. Tap <b>+ Add access</b> to choose where they work.</p>';
        // Opened from a season's Workers page: that season first, when it has nothing yet.
        const want = $('glWant');
        const covered = wantSeason && rows.some((a) => Number(a.scheduleId) === Number(wantSeason));
        if (wantSeason && !covered && SEASONS.some((s) => Number(s.id) === Number(wantSeason))) {
            const viaAll = rows.some((a) => a.all);
            want.innerHTML = `<span>${viaAll ? 'In <b>' + esc(seasonTitle(wantSeason)) + '</b> they follow all cropping schedules.' : 'No access to <b>' + esc(seasonTitle(wantSeason)) + '</b> yet.'}</span>
                <button type="button" class="btn btn-sm btn-white" data-want="${wantSeason}">${viaAll ? 'Set this season apart' : 'Give access to it'}</button>`;
            want.hidden = false;
        } else {
            want.hidden = true;
            want.innerHTML = '';
        }
    }
    function paintLogin() {
        const g = loginFor;
        if (!g) return;
        const l = g.login || null;
        $('glTitle').textContent = 'Login for ' + g.workerName;
        $('glStatus').textContent = l && l.status === 'active' ? 'Active. This worker can log in.'
            : l && l.status === 'pending' ? 'Invite sent. Waiting for them to set a password.' : 'No login yet.';
        // The address is the login's once there is one: changing it would be a second login.
        $('glEmailWrap').hidden = !!l;
        $('glEmailFixed').hidden = !l;
        $('glEmailShown').textContent = l ? (l.email || g.email || '') : '';
        if (!l) $('glEmail').value = g.email || '';
        // The login's own home screen tools; the rights live in its access settings.
        window.workerRights.paint('gl', l);
        fold($('glAccessWrap'), !!l);
        paintAccessList();
        $('glNoLoginSay').hidden = !!l;
        $('glRevoke').hidden = !l;
        const hasAccount = !!(l && l.workerUserId);
        $('glSendLink').hidden = hasAccount;
        $('glSendPwLink').hidden = !hasAccount;
        // An account's password is never overwritten (the server keeps it), so
        // once there is one the way to a new password is the link above.
        $('glSetPwToggle').hidden = hasAccount;
        fold($('glPwFold'), false);
        $('glPassword').value = ''; $('glPassword2').value = '';
        paintPwStrength('');
        $('glPwMatchSay').classList.add('hidden');
    }
    function openLogin(g, season = null) {
        clearTimeout(rightsTimer);
        loginFor = g;
        wantSeason = season;
        paintLogin();
        window.openSheet('gwLoginSheet');
    }
    function applyGrant(grant) {
        if (!loginFor) return;
        loginFor.login = grant || null;
        paintLogin();
        repaintStrip(loginFor);
    }
    // The login itself carries no rights now; the home screen tools ride along so a new login keeps what the panel shows.
    const body = () => ({ communityAccess: 1, ...window.workerRights.read('gl') });
    const loginEmail = () => (loginFor && loginFor.login ? loginFor.login.email : $('glEmail').value.trim());
    const askEmail = () => {
        const email = loginEmail();
        if (EMAIL_RX.test(email)) return email;
        window.toast?.(email ? 'That email does not look right. Check it and try again.' : 'Add their email first.', 'error');
        $('glEmail').focus();
        return null;
    };
    // A roster card with no email learns the one the login was given to.
    async function keepEmail(g, email) {
        if (g.email || !email) return;
        try {
            await window.api(U.one(g.id), { method: 'PUT', body: { workerName: g.workerName, email, phone: g.phone || '', costPerHalfDay: g.costPerHalfDay || 0,
                notes: g.notes || '', skills: g.skills || [], offWeekdays: g.offWeekdays || [], dayParts: g.dayParts || {} } });
            g.email = email;
            const card = document.querySelector('[data-login="' + g.id + '"]')?.closest('.gw-card');
            const contact = card?.querySelector('.gw-who small');
            if (contact) contact.textContent = [g.phone, g.email].filter(Boolean).join(' · ');
        } catch (e) { /* the login stands either way; the card keeps what it had */ }
    }

    /* ---- how much of a password this is (the season sheet's own measure) ---- */
    function pwScore(pw) {
        if (pw.length < 8) return 0;
        let s = 1;
        if (pw.length >= 12) s++;
        if (/[a-z]/.test(pw) && /[A-Z]/.test(pw)) s++;
        if (/\d/.test(pw)) s++;
        if (/[^A-Za-z0-9]/.test(pw)) s++;
        return Math.min(4, s);
    }
    function paintPwStrength(pw) {
        const bar = $('glPwBar'), say = $('glPwSay');
        if (!bar || !say) return;
        if (!pw) { bar.style.width = '0'; say.textContent = 'At least 8 characters.'; say.style.color = ''; return; }
        const looks = [
            ['12%', '#ef4444', 'Too short. Use at least 8 characters.'],
            ['30%', '#ef4444', 'Weak. Longer is stronger.'],
            ['55%', '#f59e0b', 'Okay. Mix in capitals or numbers.'],
            ['80%', '#84cc16', 'Strong.'],
            ['100%', '#16a34a', 'Very strong.'],
        ][pwScore(pw)];
        bar.style.width = looks[0]; bar.style.background = looks[1]; say.textContent = looks[2]; say.style.color = looks[1];
    }
    const checkPwMatch = () => { const b = $('glPassword2').value; $('glPwMatchSay').classList.toggle('hidden', !b || $('glPassword').value === b); };

    /* ---------------- One access setting ---------------- */
    function paintAccessSheet() {
        const a = accessFor, g = loginFor;
        if (!a || !g) return;
        $('gaTitle').textContent = a.all ? 'All cropping schedules' : a.title;
        $('gaSay').innerHTML = a.all
            ? 'What <b>' + esc(g.workerName) + '</b> can open in every cropping schedule of your farm, now and to come. A season with its own access follows that one instead.'
            : 'What <b>' + esc(g.workerName) + '</b> can open in <b>' + esc(a.title) + '</b>. This comes first there; '
                + ((g.login.access || []).some((x) => x.all) ? 'the other seasons follow all cropping schedules.' : 'a season without its own access stays closed to them.');
        // Activities has no "None": a setting is a place to work.
        const sched = a.scheduleAccess === 'edit' ? 'edit' : 'view';
        $('gaAccess').value = sched;
        $('gaAccess').dispatchEvent(new Event('change', { bubbles: true }));
        window.workerRights.paint('ga', a);
    }
    function openAccess(id) {
        const a = ((loginFor && loginFor.login && loginFor.login.access) || []).find((x) => String(x.id) === String(id));
        if (!a) return;
        clearTimeout(accessTimer);
        accessFor = a;
        paintAccessSheet();
        window.openSheet('gwAccessSheet');
    }
    async function addAccess(scope, btn) {
        const g = loginFor, l = g && g.login;
        if (!l) return;
        if (btn) btn.disabled = true;
        try {
            const res = await window.api(A.scopeStore, { method: 'POST', body: { grantId: l.id, scope } });
            applyGrant(res.data && res.data.grant);
            window.closeSheet?.('gwScopeSheet');
            window.toast?.(res.message);
            setTimeout(() => openAccess(res.data.accessId), 260);
        } catch (err) {
            window.toast?.(err.message, 'error');
            if (err.data && err.data.accessId) { window.closeSheet?.('gwScopeSheet'); setTimeout(() => openAccess(err.data.accessId), 260); }
        } finally { if (btn) btn.disabled = false; }
    }
    function openScopes() {
        const g = loginFor, l = g && g.login;
        if (!l) return;
        $('gsWho').textContent = g.workerName;
        const has = (l.access || []).map((a) => (a.all ? 'all' : String(a.scheduleId)));
        const rows = [{ id: 'all', title: 'All cropping schedules', say: 'Every season of your farm, now and to come' }]
            .concat(SEASONS.map((s) => ({ id: String(s.id), title: s.title, say: s.closed ? 'Closed season' : 'This season only' })));
        $('gsList').innerHTML = rows.map((r) => `<button type="button" class="gw-srow" data-scope="${esc(r.id)}" ${has.includes(r.id) ? 'disabled' : ''}>
                <b>${esc(r.title)}<small class="gs-say">${esc(r.say)}</small></b><em>${has.includes(r.id) ? 'Added' : 'Add'}</em></button>`).join('')
            + (SEASONS.length ? '' : '<p class="text-sm text-gray-500">No seasons yet. All cropping schedules covers the ones you make later.</p>');
        window.openSheet('gwScopeSheet');
    }

    if (CAN_LOGINS) {
        $('glPassword').addEventListener('input', (e) => { paintPwStrength(e.target.value); checkPwMatch(); });
        $('glPassword2').addEventListener('input', checkPwMatch);

        /* The home screen tools save themselves a breath after a real tap: a
           switch counts only when a hand moved it, and All on / All off say so
           through the hidden level beside them. */
        const queueRights = () => {
            if (!loginFor || !loginFor.login || !loginFor.login.id) return;
            clearTimeout(rightsTimer);
            rightsTimer = setTimeout(saveRights, 450);
        };
        async function saveRights() {
            const g = loginFor, l = g && g.login;
            if (!l || !l.id) return;
            try {
                const res = await window.api(A.rights, { method: 'POST', body: { id: l.id, ...window.workerRights.read('gl') } });
                g.login = Object.assign({}, l, (res.data && res.data.grant) || {});
                repaintStrip(g);
                window.toast?.(res.message || 'Saved.');
            } catch (err) {
                window.toast?.(err.message, 'error');
                if (loginFor === g) paintLogin();   // repaint the truth the server kept
            }
        }
        $('glAccessWrap').addEventListener('input', (e) => { if (e.target.classList?.contains('wr-level')) queueRights(); });
        $('glAccessWrap').addEventListener('change', (e) => { if (e.isTrusted && e.target.classList?.contains('wr-check')) queueRights(); });

        // The access list: open one, add one, or the season this page was opened for.
        $('glAccessList').addEventListener('click', (e) => { const r = e.target.closest('[data-access]'); if (r) openAccess(r.dataset.access); });
        $('glAddAccess').addEventListener('click', openScopes);
        $('glWant').addEventListener('click', (e) => { const b = e.target.closest('[data-want]'); if (b) addAccess(b.dataset.want, b); });
        $('gsList').addEventListener('click', (e) => { const b = e.target.closest('[data-scope]'); if (b && !b.disabled) addAccess(b.dataset.scope, b); });

        /* One setting's rights save as they are tapped, as the panel always did. */
        const queueAccess = () => {
            if (!accessFor) return;
            clearTimeout(accessTimer);
            accessTimer = setTimeout(saveAccess, 450);
        };
        async function saveAccess() {
            const a = accessFor, g = loginFor;
            if (!a || !g) return;
            try {
                const res = await window.api(A.scopeUpdate, { method: 'POST', body: { id: a.id, scheduleAccess: $('gaAccess').value, ...window.workerRights.read('ga') } });
                g.login = (res.data && res.data.grant) || g.login;
                accessFor = (g.login.access || []).find((x) => x.id === a.id) || a;
                paintAccessList();
                repaintStrip(g);
                window.toast?.(res.message || 'Access updated.');
            } catch (err) {
                window.toast?.(err.message, 'error');
                paintAccessSheet();   // repaint the truth the server kept
            }
        }
        $('gaRightsWrap').addEventListener('input', (e) => { if (e.target.classList?.contains('wr-level')) queueAccess(); });
        $('gaRightsWrap').addEventListener('change', (e) => { if (e.isTrusted && e.target.classList?.contains('wr-check')) queueAccess(); });

        $('gaRemove').addEventListener('click', async (e) => {
            const btn = e.currentTarget, a = accessFor, g = loginFor;
            if (!a || !g) return;
            const ok = window.confirmAction ? await window.confirmAction({
                title: 'Remove this access?',
                message: a.all
                    ? g.workerName + ' can no longer open every season. Seasons with their own access keep it.'
                    : ((g.login.access || []).some((x) => x.all) ? 'In ' + a.title + ', ' + g.workerName + ' goes back to what all cropping schedules gives.' : g.workerName + ' can no longer open ' + a.title + '.'),
                confirmText: 'Remove',
            }) : confirm('Remove this access?');
            if (!ok) return;
            btn.disabled = true;
            try {
                const res = await window.api(A.scopeDestroy, { method: 'DELETE', body: { id: a.id } });
                accessFor = null;
                applyGrant(res.data && res.data.grant);
                window.closeSheet?.('gwAccessSheet');
                window.toast?.(res.message);
            } catch (err) { window.toast?.(err.message, 'error'); } finally { btn.disabled = false; }
        });

        $('glSendLink').addEventListener('click', async (e) => {
            const btn = e.currentTarget, g = loginFor;
            const email = askEmail();
            if (!email || !g) return;
            btn.disabled = true;
            try {
                const res = await window.api(A.grant, { method: 'POST', body: { globalWorkerId: g.id, email, ...body() } });
                window.toast?.(res.message);
                if (loginFor === g) applyGrant(res.data && res.data.grant); else g.login = (res.data && res.data.grant) || null;
                keepEmail(g, email);
            } catch (err) { window.toast?.(err.message, 'error'); } finally { btn.disabled = false; }
        });

        // Asked first: it lands in somebody's inbox. The button is taken before the await.
        $('glSendPwLink').addEventListener('click', async (e) => {
            const btn = e.currentTarget, g = loginFor;
            const id = g && g.login && g.login.id;
            if (!id) { window.toast?.('This worker has no login yet.', 'error'); return; }
            const ok = window.confirmAction ? await window.confirmAction({
                title: 'Send a password change link?',
                message: g.workerName + ' gets an email to pick a new password. The old one works until they change it.',
                confirmText: 'Send the link',
                confirmClass: 'btn-primary',
            }) : true;
            if (!ok) return;
            btn.disabled = true;
            try { const res = await window.api(A.link, { method: 'POST', body: { id } }); window.toast?.(res.message); }
            catch (err) { window.toast?.(err.message, 'error'); } finally { btn.disabled = false; }
        });

        $('glSetPwToggle').addEventListener('click', () => {
            const box = $('glPwFold'), opening = !box.classList.contains('is-open');
            fold(box, opening);
            if (opening) setTimeout(() => $('glPassword').focus(), 300);
        });

        $('glCreateLogin').addEventListener('click', async (e) => {
            const btn = e.currentTarget, g = loginFor;
            const email = askEmail();
            if (!email || !g) return;
            const pw = $('glPassword').value;
            if (pw.length < 8) { window.toast?.('Password must be at least 8 characters.', 'error'); return; }
            if (pw !== $('glPassword2').value) { window.toast?.('Passwords do not match. Type the same one again below.', 'error'); $('glPassword2').focus(); return; }
            btn.disabled = true;
            try {
                const res = await window.api(A.password, { method: 'POST', body: { globalWorkerId: g.id, name: g.workerName, email, password: pw, ...body() } });
                window.toast?.(res.message);
                if (loginFor === g) applyGrant(res.data && res.data.grant); else g.login = (res.data && res.data.grant) || null;
                keepEmail(g, email);
            } catch (err) { window.toast?.(err.message, 'error'); } finally { btn.disabled = false; }
        });

        $('glRevoke').addEventListener('click', async (e) => {
            const btn = e.currentTarget, g = loginFor;
            const id = g && g.login && g.login.id;
            if (!id) return;
            const ok = window.confirmAction ? await window.confirmAction({ title: 'Remove login?', message: g.workerName + ' will no longer be able to log in to your farm. Their access settings go too.', confirmText: 'Remove' })
                : confirm('Remove ' + g.workerName + "'s login?");
            if (!ok) return;
            btn.disabled = true;
            try {
                const res = await window.api(A.revoke, { method: 'DELETE', body: { id } });
                window.toast?.(res.message);
                if (loginFor === g) applyGrant(null); else g.login = null;
            } catch (err) { window.toast?.(err.message, 'error'); } finally { btn.disabled = false; }
        });
    }

    /* Opened from a season's Workers page (?login=person&season=id): that
       person's login sheet, with that season offered first. */
    const DEEP = new URLSearchParams(location.search);
    const openDeep = () => {
        const who = DEEP.get('login');
        if (!who || !CAN_LOGINS) return;
        const g = ROWS.find((x) => String(x.id) === String(who));
        if (g) openLogin(g, DEEP.get('season') ? Number(DEEP.get('season')) : null);
    };

    // The page's own script runs before app.js (a module, run after parsing) has made window.api:
    // asked too early, the list call threw and the roster showed empty (2026-10-18, the owner).
    if (window.api) load(); else document.addEventListener('DOMContentLoaded', load, { once: true });
})();
</script>
@endpush
