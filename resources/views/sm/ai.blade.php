@extends(request()->boolean('partial') ? 'layouts.partial' : 'layouts.app')

@section('title', 'AI Technician — ' . $schedule->title)
@section('page-title', 'AI Technician')
@section('page-subtitle', $schedule->title)
@section('help-key', 'ai')
@section('back', route('sm.hub', ['id' => $schedule->id]))

@push('head')
    <style>
        /* ===== AI Technician — "Field Advisor" =========================
           Everything paints with the theme vars, so html.dark's variable
           repoint restyles the page for free. Literal hex only where a
           gradient must stay identical in both modes (green sends/bubbles)
           or where a repointed var would break contrast. */

        /* Layout bones: sessions rail + chat column at full page width. */
        .ai-shell { display: grid; grid-template-columns: minmax(0, 1fr); gap: 1rem; }
        @media (min-width: 1024px) { .ai-shell { grid-template-columns: 17rem minmax(0, 1fr); } }
        .ai-sessions { display: none; }
        @media (min-width: 1024px) {
            .ai-sessions { display: flex; flex-direction: column; gap: .3rem; height: calc(100dvh - 11rem); min-height: min(26rem, 60dvh);
                overflow-y: auto; scrollbar-width: thin; scrollbar-color: var(--color-gray-300) transparent;
                border: 1px solid var(--color-gray-100); border-radius: 1rem; background: var(--color-white);
                padding: .6rem; box-shadow: var(--shadow-card); }
        }
        .ai-session-row { display: flex; align-items: center; gap: .25rem; border-radius: .7rem; padding: .4rem .55rem;
            font-size: .85rem; font-weight: 600; color: var(--color-gray-700); transition: background-color .15s ease; }
        .ai-session-row:hover { background: var(--color-gray-100); }
        .ai-session-row.is-active { background: var(--color-brand-50); color: var(--color-brand-800); }
        .ai-session-row .t { flex: 1; min-width: 0; overflow: hidden; text-overflow: ellipsis; white-space: nowrap; }
        .ai-session-row .meta { font-size: .65rem; color: var(--color-gray-400); font-weight: 600; display: block; }
        .ai-session-act { width: 1.7rem; height: 1.7rem; border-radius: .45rem; display: inline-flex; align-items: center;
            justify-content: center; color: var(--color-gray-400); flex-shrink: 0; opacity: 0; transition: opacity .15s ease; }
        .ai-session-row:hover .ai-session-act, .ai-session-row.is-active .ai-session-act { opacity: 1; }
        .ai-session-act:hover { background: var(--color-gray-200); color: var(--color-gray-700); }
        .ai-session-rename { width: 100%; font-size: .85rem; padding: .2rem .4rem; border-radius: .45rem;
            border: 1px solid var(--color-brand-400); background: var(--color-white); color: var(--color-gray-900); outline: none; }

        .aichat { display: flex; flex-direction: column; height: calc(100dvh - 11rem); min-height: min(26rem, 60dvh); width: 100%; }
        /* Mobile: clear the fixed bottom tab bar so the composer + hint stay visible. */
        @media (max-width: 767px) { .aichat { height: calc(100dvh - 13.5rem); min-height: min(22rem, 55dvh); } }
        .aichat-thread { flex: 1 1 auto; overflow-y: auto; padding: .5rem .25rem 1.25rem; scroll-behavior: smooth; display: flex; flex-direction: column; scrollbar-width: thin; scrollbar-color: var(--color-gray-300) transparent; }
        .aichat-thread::-webkit-scrollbar { width: 6px; }
        .aichat-thread::-webkit-scrollbar-track { background: transparent; }
        .aichat-thread::-webkit-scrollbar-thumb { background: var(--color-gray-300); border-radius: 999px; }
        /* Welcome hero centers; a short conversation grows up from the composer. */
        #aiWelcome { margin: auto 0; }
        .aimsg:first-child { margin-top: auto; }

        .aichat-day { display: flex; align-items: center; gap: .75rem; margin: .75rem 0; text-align: center; }
        .aichat-day::before, .aichat-day::after { content: ""; flex: 1; height: 1px; background: var(--color-gray-200); }
        .aichat-day span { font-size: .69rem; font-weight: 700; letter-spacing: .06em; text-transform: uppercase; color: var(--color-gray-400); }

        /* ===== Masthead ===== */
        .ai-head { display: flex; align-items: center; justify-content: space-between; gap: .75rem; padding: .7rem .9rem; margin-bottom: .75rem; border-radius: 1.25rem; border: 1px solid var(--color-brand-100); background: linear-gradient(115deg, var(--color-brand-50) 0%, var(--color-white) 70%); }
        .ai-avatar { position: relative; flex-shrink: 0; }
        .ai-avatar .aimsg-face { width: 2.75rem; height: 2.75rem; box-shadow: 0 0 0 2px var(--color-white), 0 0 0 4px var(--color-brand-200); }
        .ai-avatar::after { content: ""; position: absolute; right: -1px; bottom: -1px; width: .75rem; height: .75rem; border-radius: 999px; background: var(--color-brand-500); border: 2.5px solid var(--color-white); }
        .ai-head-name { font-family: var(--font-heading); font-weight: 700; font-size: 1.02rem; line-height: 1.15; color: var(--color-gray-900); }
        .ai-role { display: inline-flex; align-items: center; margin-top: .2rem; padding: .1rem .55rem; border-radius: 999px; font-size: .68rem; font-weight: 800; letter-spacing: .05em; text-transform: uppercase; white-space: nowrap; color: var(--color-brand-700); background: var(--color-brand-100); }
        /* Literal amber: accent-700 fails contrast on the cream wash in light mode. */
        .ai-credits { display: inline-flex; align-items: center; gap: .35rem; min-height: 2.5rem; padding: .3rem .8rem; border-radius: 999px; background: rgb(245 197 24 / .16); color: #8a6100; font-weight: 800; font-size: .92rem; font-variant-numeric: tabular-nums; transition: background .15s ease; }
        .ai-credits:hover { background: rgb(245 197 24 / .26); }
        .ai-credits:focus-visible, .aisuggest:focus-visible, #aiSendBtn:focus-visible, .ai-cam:focus-visible { outline: 2px solid var(--color-brand-500); outline-offset: 2px; }
        .ai-sq { width: 2.5rem; height: 2.5rem; min-height: 2.5rem; padding: 0; }
        /* Narrow phones: let the controls wrap under the full-width name row. */
        @media (max-width: 479px) {
            .ai-head { flex-wrap: wrap; row-gap: .5rem; padding: .6rem .75rem; }
            .ai-head > div:last-child { margin-left: auto; }
        }

        /* ===== Welcome hero ===== */
        .ai-hello { text-align: center; padding: 2rem 1.25rem 1.25rem; border-radius: 1.5rem; background: radial-gradient(120% 90% at 50% 0%, var(--color-brand-50) 0%, transparent 70%); }
        .ai-hello .aimsg-face { width: 4.5rem; height: 4.5rem; background: linear-gradient(150deg, #6b9f3d, #3d6823); color: #fff; box-shadow: 0 0 0 3px var(--color-white), 0 0 0 5px var(--color-brand-200), 0 10px 24px -8px rgb(74 124 42 / .45); animation: aiFloatIdle 5s ease-in-out infinite; }
        .ai-hello h2 { font-family: var(--font-heading); font-size: 1.4rem; font-weight: 700; margin-top: 1rem; color: var(--color-gray-900); }
        .ai-hello .sub { font-size: 1rem; color: var(--color-gray-500); margin-top: .4rem; max-width: 26rem; margin-inline: auto; line-height: 1.6; }
        .ai-caps { display: flex; flex-wrap: wrap; justify-content: center; gap: .4rem; margin-top: .9rem; }
        .ai-cap { display: inline-flex; align-items: center; gap: .35rem; padding: .3rem .7rem; border-radius: 999px; font-size: .8rem; font-weight: 700; color: var(--color-brand-700); background: var(--color-brand-50); border: 1px solid var(--color-brand-100); }
        .ai-overline { font-size: .7rem; font-weight: 800; letter-spacing: .09em; text-transform: uppercase; color: var(--color-gray-400); margin: 1.6rem 0 .6rem; }
        @keyframes aiFloatIdle { 0%, 100% { transform: translateY(0); } 50% { transform: translateY(-5px); } }

        /* ===== Suggestion cards ===== */
        .aisuggest { display: flex; align-items: center; gap: .75rem; width: 100%; min-height: 3.5rem; padding: .75rem 1rem; text-align: left; border: 1px solid var(--color-gray-200); border-radius: 1rem; background: var(--color-white); box-shadow: var(--shadow-card); font-size: .97rem; font-weight: 700; color: var(--color-gray-800); cursor: pointer; transition: transform .18s cubic-bezier(.22,1,.36,1), border-color .18s ease, box-shadow .18s ease; animation: aiRise .3s ease both; }
        .aisuggest:nth-child(2) { animation-delay: .06s; }
        .aisuggest:nth-child(3) { animation-delay: .12s; }
        .aisuggest:hover { transform: translateY(-1px); border-color: var(--color-brand-300); box-shadow: var(--shadow-card-lg); }
        .aisuggest:active { transform: scale(.98); }
        .aisuggest .ic { width: 2.25rem; height: 2.25rem; border-radius: .75rem; flex-shrink: 0; display: flex; align-items: center; justify-content: center; background: var(--color-brand-50); color: var(--color-brand-700); }
        .aisuggest .t { flex: 1 1 auto; min-width: 0; }
        .aisuggest .go { margin-left: auto; flex-shrink: 0; color: var(--color-gray-400); transition: transform .18s ease, color .18s ease; }
        .aisuggest:hover .go { transform: translateX(3px); color: var(--color-brand-600); }

        /* ===== Turns ===== */
        .aimsg { display: flex; gap: .65rem; margin-bottom: 1rem; align-items: flex-end; animation: aiRise .28s cubic-bezier(.22,1,.36,1) both; }
        .aimsg.me { flex-direction: row-reverse; }
        @keyframes aiRise { from { opacity: 0; transform: translateY(8px); } to { opacity: 1; transform: none; } }
        .aimsg-face {
            width: 2.4rem; height: 2.4rem; border-radius: 999px; flex-shrink: 0; overflow: hidden;
            display: flex; align-items: center; justify-content: center; margin-bottom: .1rem;
            background: var(--color-brand-50); color: var(--color-brand-700); font-size: .78rem; font-weight: 800;
        }
        .aimsg-face img { width: 100%; height: 100%; object-fit: cover; }
        .aimsg.me .aimsg-face { background: var(--color-brand-600); color: #fff; }
        /* The in-thread assistant face carries the masthead ring — same character. */
        .aimsg:not(.me) .aimsg-face { box-shadow: 0 0 0 2px var(--color-white), 0 0 0 3px var(--color-brand-200); }

        /* Assistant "writes" on a sheet; the user "sends" a green slip. */
        .aibubble { max-width: min(82%, 34rem); padding: .8rem 1.05rem; font-size: 1.02rem; line-height: 1.62; background: var(--color-white); border: 1px solid var(--color-gray-100); border-radius: 1.15rem 1.15rem 1.15rem .35rem; box-shadow: 0 1px 2px rgb(26 26 26 / .06), 0 3px 10px -4px rgb(26 26 26 / .08); }
        /* Literal hex: var(--color-brand-700) repoints to a bright green in dark
           mode and would sink the white text. */
        .aimsg.me .aibubble { background: linear-gradient(135deg, #4a7c2a, #3d6823); border-color: transparent; color: #fff; border-radius: 1.15rem 1.15rem .35rem 1.15rem; box-shadow: 0 3px 12px -4px rgb(45 80 22 / .45); }
        .aibubble p { margin: .4rem 0; } .aibubble p:first-child { margin-top: 0; } .aibubble p:last-child { margin-bottom: 0; }
        .aibubble ul { list-style: disc; padding-left: 1.25rem; margin: .4rem 0; }
        .aibubble ol { list-style: decimal; padding-left: 1.4rem; margin: .4rem 0; }
        .aibubble li { margin: .2rem 0; }
        .aibubble strong { font-weight: 700; }
        .aibubble img { max-width: 100%; max-height: 260px; border-radius: .6rem; margin-top: .4rem; }
        .aibubble-cost { display: inline-flex; align-items: center; gap: .3rem; margin-top: .55rem; padding: .12rem .55rem; border-radius: 999px; font-size: .69rem; font-weight: 800; font-variant-numeric: tabular-nums; color: #8a6100; background: rgb(245 197 24 / .15); }
        .aibubble-cost::before { content: ""; width: .4rem; height: .4rem; border-radius: 999px; background: var(--color-accent-500); }
        .aimsg.me .aibubble-cost { background: rgb(255 255 255 / .2); color: #fff; }
        .aimsg.me .aibubble-cost::before { background: #fff; }

        /* ===== Typing dots ===== */
        .aidots { display: inline-flex; gap: .25rem; align-items: center; height: 1.2rem; }
        .aidots i { width: .42rem; height: .42rem; border-radius: 999px; background: var(--color-brand-500); opacity: .35; animation: aidot .9s cubic-bezier(.4,0,.2,1) infinite; }
        .aidots i:nth-child(2) { animation-delay: .15s; } .aidots i:nth-child(3) { animation-delay: .3s; }
        @keyframes aidot { 0%, 60%, 100% { opacity: .3; transform: translateY(0); } 30% { opacity: 1; transform: translateY(-3px); } }

        /* ===== Composer dock ===== */
        .aichat-composer { flex-shrink: 0; padding: .6rem 0 .1rem; background: linear-gradient(to top, var(--color-gray-50) 70%, transparent); }
        .aichat-box { display: flex; align-items: flex-end; gap: .4rem; padding: .45rem; border-radius: 1.35rem; background: var(--color-white); border: 1.5px solid var(--color-gray-200); box-shadow: var(--shadow-card-lg); transition: border-color .15s ease, box-shadow .15s ease; }
        .aichat-box:focus-within { border-color: var(--color-brand-500); box-shadow: 0 0 0 3px rgb(107 159 61 / .18), var(--shadow-card-lg); }
        .ai-cam { width: 2.75rem; height: 2.75rem; border-radius: 1rem; display: flex; align-items: center; justify-content: center; flex-shrink: 0; background: var(--color-brand-50); color: var(--color-brand-700); cursor: pointer; transition: background .15s ease; }
        .ai-cam:hover { background: var(--color-brand-100); }
        #aiText { resize: none; max-height: 8rem; font-size: 1rem; }
        #aiSendBtn { width: 2.9rem; height: 2.9rem; border-radius: 999px; background: linear-gradient(140deg, #6b9f3d, #3d6823); box-shadow: 0 4px 12px -3px rgb(45 80 22 / .5); transition: transform .15s ease; }
        #aiSendBtn:hover:not(:disabled) { transform: scale(1.06); }
        #aiSendBtn:active:not(:disabled) { transform: scale(.94); }
        .ai-hint { text-align: center; font-size: .72rem; font-weight: 600; color: var(--color-gray-500); margin-top: .4rem; }
        #aiPhotoChip { background: var(--color-gray-100); border-radius: .75rem; padding: .35rem .6rem; }
        #aiPhotoChip.hidden { display: none; }
        #aiPhotoThumb { width: 2.5rem; height: 2.5rem; border-radius: .5rem; object-fit: cover; box-shadow: 0 0 0 2px var(--color-brand-200); }
        #aiPhotoRemove { min-height: 2.25rem; padding: 0 .6rem; border-radius: .5rem; }

        /* Out-of-credits purchase card, rendered as an assistant turn. */
        .aibubble.is-buy { border-color: rgb(245 197 24 / .4); background: linear-gradient(115deg, rgb(245 197 24 / .14), rgb(245 197 24 / .04)), var(--color-white); }
        .ai-buyc { display: flex; gap: .8rem; align-items: flex-start; }
        .ai-buyc .ico { width: 2.4rem; height: 2.4rem; border-radius: .85rem; flex-shrink: 0; display: flex; align-items: center; justify-content: center; background: var(--color-accent-500); color: #1a1a1a; }
        .ai-buyc h3 { font-family: var(--font-heading); font-size: 1rem; font-weight: 700; color: var(--color-gray-900); }
        .ai-buyc p { font-size: .88rem; color: var(--color-gray-600); }

        /* ===== Notices: warm field notes (accent = money/attention) ===== */
        .ai-note { display: flex; gap: .8rem; align-items: flex-start; margin-bottom: .75rem; padding: 1rem 1.1rem; border-radius: 1.25rem; border: 1px solid rgb(245 197 24 / .4); background: linear-gradient(115deg, rgb(245 197 24 / .14), rgb(245 197 24 / .04)), var(--color-white); }
        .ai-note.hidden { display: none; }
        /* Literal ink: var(--color-ink) flips near-white in dark mode. */
        .ai-note .ico { width: 2.4rem; height: 2.4rem; border-radius: .85rem; flex-shrink: 0; display: flex; align-items: center; justify-content: center; background: var(--color-accent-500); color: #1a1a1a; }
        .ai-note h3 { font-family: var(--font-heading); font-size: 1rem; font-weight: 700; color: var(--color-gray-900); }
        .ai-note p { font-size: .85rem; color: var(--color-gray-600); margin-top: .15rem; }

        /* ===== Dark mode: the var repoint does 90%; a few nudges finish it ===== */
        html.dark .aibubble { box-shadow: 0 1px 2px rgb(0 0 0 / .35), 0 3px 10px -4px rgb(0 0 0 / .4); }
        html.dark .aibubble-cost { color: var(--color-accent-400); }
        html.dark .aimsg.me .aibubble-cost { color: #fff; }
        html.dark .ai-credits { color: var(--color-accent-400); }
        html.dark .aichat-box:focus-within { box-shadow: 0 0 0 3px rgb(124 184 79 / .22), var(--shadow-card-lg); }
        html.dark .ai-note { border-color: rgb(245 197 24 / .25); background: linear-gradient(115deg, rgb(245 197 24 / .10), rgb(245 197 24 / .03)), var(--color-white); }
        html.dark .aibubble.is-buy { border-color: rgb(245 197 24 / .25); background: linear-gradient(115deg, rgb(245 197 24 / .10), rgb(245 197 24 / .03)), var(--color-white); }
        html.dark .ai-hello .aimsg-face { box-shadow: 0 0 0 3px var(--color-white), 0 0 0 5px var(--color-brand-200), 0 10px 24px -8px rgb(0 0 0 / .6); }

        @media (prefers-reduced-motion: reduce) {
            .aimsg, .aisuggest, .ai-hello .aimsg-face { animation: none; }
            .aisuggest, .aisuggest .go, .aichat-box, #aiSendBtn, .ai-credits { transition: none; }
        }
    </style>
@endpush

@section('content')
@include('sm.partials.module-header', ['schedule' => $schedule, 'module' => 'ai'])

<div class="ai-shell">
    {{-- Chat sessions rail (desktop; phones use the history sheet) --}}
    <aside class="ai-sessions" id="aiSessions">
        <div class="flex items-center justify-between px-1 pb-1">
            <span class="text-xs font-bold text-gray-400 uppercase tracking-wide">Chats</span>
            <button type="button" id="aiSideNewBtn" class="icon-btn" title="New chat">
                <svg class="w-4 h-4" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" d="M12 4v16m8-8H4"/></svg>
            </button>
        </div>
        @forelse ($conversations as $c)
            <div class="ai-session-row {{ $conversation && $conversation->id === $c->id ? 'is-active' : '' }}" data-convo-row="{{ $c->id }}">
                <a href="{{ route('sm.ai', ['id' => $schedule->id, 'c' => $c->id]) }}" class="t js-ai-convo" data-c="{{ $c->id }}">
                    <span data-session-title>{{ $c->title }}</span>
                    <span class="meta">{{ $c->updated_at?->diffForHumans() }}</span>
                    @if ($c->link_label)<span class="meta" style="color:var(--color-brand-600)">{{ $c->link_label }}</span>@endif
                </a>
                <button type="button" class="ai-session-act js-side-rename" data-id="{{ $c->id }}" title="Rename chat" aria-label="Rename chat">
                    <svg class="w-3.5 h-3.5" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" d="M11 5H6a2 2 0 00-2 2v11a2 2 0 002 2h11a2 2 0 002-2v-5m-1.414-9.414a2 2 0 112.828 2.828L11.828 15H9v-2.828l8.586-8.586z"/></svg>
                </button>
                <button type="button" class="ai-session-act js-ai-del" data-id="{{ $c->id }}" title="Delete chat" aria-label="Delete chat">
                    <svg class="w-3.5 h-3.5" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" d="M19 7l-.867 12.142A2 2 0 0116.138 21H7.862a2 2 0 01-1.995-1.858L5 7m5 4v6m4-6v6m1-10V4a1 1 0 00-1-1h-4a1 1 0 00-1 1v3M4 7h16"/></svg>
                </button>
            </div>
        @empty
            <p class="text-xs text-gray-400 px-1 py-2">No chats yet — ask your first question and it names itself.</p>
        @endforelse
    </aside>

<div class="aichat">
    {{-- Masthead: identity + wallet + history + new --}}
    <div class="ai-head">
        <div class="flex items-center gap-3 min-w-0">
            <div class="ai-avatar">
                <span class="aimsg-face">
                    @if ($settings->avatarPath)
                        <img src="{{ \App\Support\MediaStore::url($settings->avatarPath) }}" alt="">
                    @else
                        <svg class="w-5 h-5" fill="none" stroke="currentColor" stroke-width="1.8" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" d="M12 3v2m0 0a7 7 0 017 7v3a3 3 0 01-3 3H8a3 3 0 01-3-3v-3a7 7 0 017-7zM9 12h.01M15 12h.01M9.5 17h5"/></svg>
                    @endif
                </span>
            </div>
            <div class="min-w-0">
                <p class="ai-head-name truncate">{{ $settings->assistantName }}</p>
                <span class="ai-role">Crop Technician</span>
            </div>
        </div>
        <div class="flex items-center gap-1.5 shrink-0">
            <a href="{{ route('ai.credits') }}" class="ai-credits" title="AI Credits" aria-label="AI credits balance">
                <svg class="w-4 h-4 text-accent-500" viewBox="0 0 20 20" fill="currentColor"><path d="M10 2a8 8 0 100 16 8 8 0 000-16zm.75 4.5v.63a2.5 2.5 0 01.2 4.84v.78a.75.75 0 01-1.5 0v-.75a2.6 2.6 0 01-1.83-1.1.75.75 0 011.24-.84c.24.35.63.57 1.09.57.6 0 1.05-.36 1.05-.83 0-.44-.3-.7-1.2-.95-1.13-.32-2.05-.8-2.05-2.05a2.2 2.2 0 011.5-2.03V6.5a.75.75 0 011.5 0z"/></svg>
                <span id="aiBalance">{{ rtrim(rtrim(number_format($balance, 2), '0'), '.') }}</span>
            </a>
            <button type="button" class="btn btn-white btn-sm ai-sq" id="aiNewChatBtn" title="New question" aria-label="Start a new question">
                <svg class="w-4 h-4" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" d="M12 4v16m8-8H4"/></svg>
            </button>
            <button type="button" class="btn btn-white btn-sm ai-sq" id="aiHistoryBtn" title="Past questions" aria-label="Past questions">
                <svg class="w-4 h-4" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" d="M12 8v4l3 2m6-2a9 9 0 11-18 0 9 9 0 0118 0z"/></svg>
            </button>
            <button type="button" class="btn btn-white btn-sm ai-sq {{ $conversation && $conversation->link_label ? 'text-brand-700' : '' }}" id="aiLinkBtn" title="Link this chat to a day or activity" aria-label="Link to a day or activity">
                <svg class="w-4 h-4" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" d="M13.83 10.17a4 4 0 010 5.66l-3 3a4 4 0 11-5.66-5.66l1.5-1.5m6.33-1.83a4 4 0 000-5.66l-1.5-1.5"/></svg>
            </button>
        </div>
    </div>

    {{-- Chip: the day/activity this chat is pinned to (kept in the AI's focus). --}}
    <div id="aiLinkChip" class="{{ $conversation && $conversation->link_label ? 'flex' : 'hidden' }} items-center gap-2 -mt-1 mb-2 text-sm">
        <span class="inline-flex items-center gap-1.5 rounded-full bg-brand-50 border border-brand-100 text-brand-800 font-semibold px-3 py-1">
            <span id="aiLinkChipText">{{ $conversation?->link_label }}</span>
            <button type="button" id="aiLinkChipClear" class="text-brand-500 hover:text-red-600 font-bold" aria-label="Remove link">✕</button>
        </span>
        <span class="text-xs text-gray-400">This chat is focused here.</span>
    </div>

    @unless ($settings->isUsable())
        <div class="ai-note">
            <span class="ico">
                <svg class="w-5 h-5" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" d="M18.36 6.64a9 9 0 11-12.73 0M12 2v10"/></svg>
            </span>
            <div>
                <h3>The AI Technician is not switched on yet</h3>
                <p>It will appear here as soon as it is configured.</p>
            </div>
        </div>
    @endunless

    <div class="ai-note {{ $balance > 0 ? 'hidden' : '' }}" id="aiNoCredits">
        <span class="ico">
            <svg class="w-5 h-5" viewBox="0 0 20 20" fill="currentColor"><path d="M10 2a8 8 0 100 16 8 8 0 000-16zm.75 4.5v.63a2.5 2.5 0 01.2 4.84v.78a.75.75 0 01-1.5 0v-.75a2.6 2.6 0 01-1.83-1.1.75.75 0 011.24-.84c.24.35.63.57 1.09.57.6 0 1.05-.36 1.05-.83 0-.44-.3-.7-1.2-.95-1.13-.32-2.05-.8-2.05-2.05a2.2 2.2 0 011.5-2.03V6.5a.75.75 0 011.5 0z"/></svg>
        </span>
        <div>
            <h3>You have no AI Credits left</h3>
            <p>A question costs about 4 credits, or 7 with a photo.</p>
            <a href="{{ route('ai.credits') }}" class="btn btn-accent btn-sm mt-2">Get AI Credits</a>
        </div>
    </div>

    {{-- Thread --}}
    <div class="aichat-thread" id="aiThread">
        @forelse ($messages as $m)
            <div class="aimsg {{ $m->role === 'user' ? 'me' : '' }}">
                <span class="aimsg-face">
                    @if ($m->role === 'user')
                        {{ auth()->user()->initials }}
                    @elseif ($settings->avatarPath)
                        <img src="{{ \App\Support\MediaStore::url($settings->avatarPath) }}" alt="">
                    @else
                        <svg class="w-4 h-4" fill="none" stroke="currentColor" stroke-width="1.8" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" d="M12 3v2m0 0a7 7 0 017 7v3a3 3 0 01-3 3H8a3 3 0 01-3-3v-3a7 7 0 017-7zM9 12h.01M15 12h.01M9.5 17h5"/></svg>
                    @endif
                </span>
                <div class="aibubble">
                    {!! \App\Support\AiMarkdown::toHtml($m->content) !!}
                    @if ($m->imagePath)
                        <img src="{{ \App\Support\MediaStore::url($m->imagePath) }}" alt="">
                    @endif
                    @if ($m->role === 'assistant' && (float) $m->creditsCharged > 0)
                        <p class="aibubble-cost">{{ rtrim(rtrim(number_format((float) $m->creditsCharged, 2), '0'), '.') }} credits</p>
                    @endif
                </div>
            </div>
        @empty
            <div class="ai-hello" id="aiWelcome">
                <span class="aimsg-face mx-auto">
                    @if ($settings->avatarPath)
                        <img src="{{ \App\Support\MediaStore::url($settings->avatarPath) }}" alt="">
                    @else
                        <svg class="w-9 h-9" fill="none" stroke="currentColor" stroke-width="1.6" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" d="M12 3v2m0 0a7 7 0 017 7v3a3 3 0 01-3 3H8a3 3 0 01-3-3v-3a7 7 0 017-7zM9 12h.01M15 12h.01M9.5 17h5"/></svg>
                    @endif
                </span>
                <h2>Magandang araw! Ask me about {{ \Illuminate\Support\Str::limit($schedule->cropType ?: 'this crop', 24) }}</h2>
                <p class="sub">Fertiliser rates, pests, water, timing, harvest. Snap a leaf and I'll take a look.</p>
                <div class="ai-caps">
                    <span class="ai-cap">
                        <svg class="w-4 h-4" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" d="M5 21c.5-4.5 2.5-15 16-17-.5 13.5-8 16-12 16-1.33 0-2.67 0-4 1zm0 0c2-6 5-10 10-12"/></svg>
                        Leaf check
                    </span>
                    <span class="ai-cap">
                        <svg class="w-4 h-4" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" d="M12 3s6 6.5 6 11a6 6 0 11-12 0c0-4.5 6-11 6-11z"/></svg>
                        Water &amp; fertiliser
                    </span>
                    <span class="ai-cap">
                        <svg class="w-4 h-4" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" d="M8 7V3m8 4V3m-9 8h10M5 21h14a2 2 0 002-2V7a2 2 0 00-2-2H5a2 2 0 00-2 2v12a2 2 0 002 2z"/></svg>
                        Timing
                    </span>
                </div>
                <p class="ai-overline">Try one of these</p>
                <div class="grid gap-2.5 max-w-md mx-auto">
                    <button type="button" class="aisuggest js-suggest">
                        <span class="ic" aria-hidden="true"><svg class="w-5 h-5" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" d="M5 21c.5-4.5 2.5-15 16-17-.5 13.5-8 16-12 16-1.33 0-2.67 0-4 1zm0 0c2-6 5-10 10-12"/></svg></span>
                        <span class="t">My leaves are yellowing at the tips — what should I check?</span>
                        <svg class="go w-4 h-4" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" d="M9 5l7 7-7 7"/></svg>
                    </button>
                    <button type="button" class="aisuggest js-suggest">
                        <span class="ic" aria-hidden="true"><svg class="w-5 h-5" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" d="M3 6l3 1m0 0l-3 9a5.002 5.002 0 006.001 0M6 7l3 9M6 7l6-2m6 2l3-1m-3 1l-3 9a5.002 5.002 0 006.001 0M18 7l3 9m-3-9l-6-2m0-2v2m0 16V5m0 16H9m3 0h3"/></svg></span>
                        <span class="t">How much urea per hectare for the first top dressing?</span>
                        <svg class="go w-4 h-4" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" d="M9 5l7 7-7 7"/></svg>
                    </button>
                    <button type="button" class="aisuggest js-suggest">
                        <span class="ic" aria-hidden="true"><svg class="w-5 h-5" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" d="M12 3s6 6.5 6 11a6 6 0 11-12 0c0-4.5 6-11 6-11z"/></svg></span>
                        <span class="t">When should I stop irrigating before harvest?</span>
                        <svg class="go w-4 h-4" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" d="M9 5l7 7-7 7"/></svg>
                    </button>
                </div>
            </div>
        @endforelse
    </div>

    {{-- Composer --}}
    <div class="aichat-composer">
        <div id="aiPhotoChip" class="hidden mb-1.5 flex items-center gap-2 text-xs font-semibold text-gray-600">
            <img src="" alt="" class="w-9 h-9 rounded-lg object-cover" id="aiPhotoThumb">
            <span>Photo attached</span>
            <button type="button" class="text-red-600 font-bold" id="aiPhotoRemove">Remove</button>
        </div>
        <div class="aichat-box">
            <label class="ai-cam shrink-0" title="Attach a photo" aria-label="Attach a photo">
                <svg class="w-5 h-5" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" d="M3 9a2 2 0 012-2h.93a2 2 0 001.664-.89l.812-1.22A2 2 0 0110.07 4h3.86a2 2 0 011.664.89l.812 1.22A2 2 0 0018.07 7H19a2 2 0 012 2v9a2 2 0 01-2 2H5a2 2 0 01-2-2V9z"/><path stroke-linecap="round" stroke-linejoin="round" d="M15 13a3 3 0 11-6 0 3 3 0 016 0z"/></svg>
                <input type="file" id="aiPhoto" accept="image/*" capture="environment" class="hidden">
            </label>
            <textarea id="aiText" rows="1" class="form-textarea border-0! shadow-none! focus:ring-0! p-2 grow bg-transparent!"
                      maxlength="4000" placeholder="Ask about your crop…" {{ $settings->isUsable() ? '' : 'disabled' }}></textarea>
            <button type="button" class="rounded-full text-white flex items-center justify-center shrink-0 disabled:opacity-40" id="aiSendBtn" {{ $settings->isUsable() ? '' : 'disabled' }} aria-label="Send">
                <svg class="w-5 h-5" fill="none" stroke="currentColor" stroke-width="2.2" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" d="M5 12h14m0 0l-6-6m6 6l-6 6"/></svg>
            </button>
        </div>
        <p class="ai-hint">≈ 4 credits per answer · 7 with a photo</p>
    </div>
</div>
</div>
@endsection

@push('sheets')
@php
    use Illuminate\Support\Carbon as AiCarbon;
    $aiDays = $schedule->activities
        ->filter(fn ($a) => $a->targetDate)
        ->map(fn ($a) => AiCarbon::parse($a->targetDate)->format('Y-m-d'))
        ->unique()->sort()->values();
    $aiActs = $schedule->activities
        ->filter(fn ($a) => $a->targetDate)
        ->sortBy(fn ($a) => AiCarbon::parse($a->targetDate)->format('Y-m-d') . str_pad((string) (int) $a->sequenceOrder, 6, '0', STR_PAD_LEFT))
        ->map(fn ($a) => [
            'id' => $a->id,
            'title' => $a->activityTitle,
            'date' => AiCarbon::parse($a->targetDate)->format('M j, Y'),
        ])->values();
@endphp
<div class="sheet hidden" id="aiLinkSheet" style="--sheet-width:26rem">
    <div class="sheet-handle"></div>
    <div class="sheet-header">
        <h3 class="sheet-title">Link this chat</h3>
        <button type="button" data-sheet-close class="btn-ghost p-2 rounded-full" aria-label="Close">✕</button>
    </div>
    <div class="sheet-body space-y-4">
        <p class="text-sm text-gray-600">Pin this conversation to a day or a specific activity of <strong>{{ $schedule->title }}</strong>. The AI keeps that in focus when it answers.</p>
        <div>
            <label class="form-label" for="aiLinkDate">A day</label>
            <div class="flex gap-2">
                <select id="aiLinkDate" class="form-select grow">
                    <option value="">Choose a day…</option>
                    @foreach ($aiDays as $d)
                        <option value="{{ $d }}">{{ AiCarbon::parse($d)->format('D, M j, Y') }}</option>
                    @endforeach
                </select>
                <button type="button" id="aiLinkDayBtn" class="btn btn-primary shrink-0">Pin day</button>
            </div>
        </div>
        <div>
            <label class="form-label" for="aiLinkActivity">An activity</label>
            <div class="flex gap-2">
                <select id="aiLinkActivity" class="form-select grow">
                    <option value="">Choose an activity…</option>
                    @foreach ($aiActs as $a)
                        <option value="{{ $a['id'] }}">{{ $a['date'] }} — {{ \Illuminate\Support\Str::limit($a['title'], 44) }}</option>
                    @endforeach
                </select>
                <button type="button" id="aiLinkActBtn" class="btn btn-primary shrink-0">Pin activity</button>
            </div>
        </div>
        @if ($aiDays->isEmpty())
            <p class="text-sm text-gray-400">This plan has no dated activities yet.</p>
        @endif
    </div>
    <div class="sheet-footer">
        <button type="button" id="aiLinkRemoveBtn" class="btn btn-danger-outline mr-auto">Remove link</button>
        <button type="button" class="btn btn-ghost" data-sheet-close>Close</button>
    </div>
</div>
<div class="sheet hidden" id="aiHistorySheet" style="--sheet-width:26rem">
    <div class="sheet-handle"></div>
    <div class="sheet-header">
        <h3 class="sheet-title">Past questions</h3>
        <button type="button" data-sheet-close class="btn-ghost p-2 rounded-full" aria-label="Close">✕</button>
    </div>
    <div class="sheet-body space-y-1">
        <button type="button" class="w-full flex items-center gap-3 rounded-xl px-3 py-3 text-left font-semibold text-brand-700 hover:bg-gray-50" id="aiNewFromSheet">
            <svg class="w-5 h-5" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" d="M12 4v16m8-8H4"/></svg>
            Start a new question
        </button>
        @foreach ($conversations as $c)
            <div class="flex items-center gap-1">
                <a href="{{ route('sm.ai', ['id' => $schedule->id, 'c' => $c->id]) }}"
                   class="grow min-w-0 rounded-xl px-3 py-3 font-semibold text-gray-700 hover:bg-gray-50 {{ $conversation && $conversation->id === $c->id ? 'bg-brand-50 text-brand-700' : '' }} js-ai-convo" data-c="{{ $c->id }}">
                    <span class="block truncate">{{ $c->title }}</span>
                    <span class="block text-xs font-normal text-gray-400">{{ $c->updated_at?->diffForHumans() }}</span>
                    @if ($c->link_label)<span class="block text-xs font-normal text-brand-600">{{ $c->link_label }}</span>@endif
                </a>
                <button type="button" class="icon-btn text-red-600 shrink-0 js-ai-del" data-id="{{ $c->id }}" aria-label="Delete conversation">
                    <svg class="w-4 h-4" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" d="M19 7l-.867 12.142A2 2 0 0116.138 21H7.862a2 2 0 01-1.995-1.858L5 7m5 4v6m4-6v6m1-10V4a1 1 0 00-1-1h-4a1 1 0 00-1 1v3M4 7h16"/></svg>
                </button>
            </div>
        @endforeach
        @if ($conversations->isEmpty())
            <p class="text-sm text-gray-500 text-center py-6">No questions yet for this plan.</p>
        @endif
    </div>
</div>
@endpush

@push('scripts')
<script>
(() => {
const __init = () => {
    const SCHEDULE_ID = @json($schedule->id);
    const URLS = {
        ask: @json(route('ai.ask')),
        photo: @json(route('ai.photo')),
        newConvo: @json(route('ai.conversation.new')),
        delConvo: (id) => @json(route('ai.conversation.delete')) + '?id=' + id,
        page: @json(route('sm.ai', ['id' => $schedule->id])),
        credits: @json(route('ai.credits')),
        rename: @json(route('ai.conversation.rename')),
        link: @json(route('ai.conversation.link')),
    };
    const COIN = '<svg class="w-5 h-5" viewBox="0 0 20 20" fill="currentColor"><path d="M10 2a8 8 0 100 16 8 8 0 000-16zm.75 4.5v.63a2.5 2.5 0 01.2 4.84v.78a.75.75 0 01-1.5 0v-.75a2.6 2.6 0 01-1.83-1.1.75.75 0 011.24-.84c.24.35.63.57 1.09.57.6 0 1.05-.36 1.05-.83 0-.44-.3-.7-1.2-.95-1.13-.32-2.05-.8-2.05-2.05a2.2 2.2 0 011.5-2.03V6.5a.75.75 0 011.5 0z"/></svg>';
    const buyCard = (msg) => `<div class="ai-buyc"><span class="ico">${COIN}</span><div><h3>You're out of AI Credits</h3><p>${escapeHtml(msg)}</p><a class="btn btn-accent btn-sm mt-2" href="${escapeHtml(URLS.credits)}">Purchase AI credits</a></div></div>`;
    const AVATAR = @json($settings->avatarPath ? \App\Support\MediaStore::url($settings->avatarPath) : null);
    const MY = @json(auth()->user()->initials);
    const BOT = '<svg class="w-4 h-4" fill="none" stroke="currentColor" stroke-width="1.8" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" d="M12 3v2m0 0a7 7 0 017 7v3a3 3 0 01-3 3H8a3 3 0 01-3-3v-3a7 7 0 017-7zM9 12h.01M15 12h.01M9.5 17h5"/></svg>';

    let conversationId = @json($conversation->id ?? null);
    let photoPath = null;
    let busy = false;

    const byId = (id) => document.getElementById(id);
    const thread = byId('aiThread');
    const face = (me) => me ? escapeHtml(MY) : (AVATAR ? `<img src="${escapeHtml(AVATAR)}" alt="">` : BOT);

    /** Light markdown -> safe HTML (escape-first allow-list). */
    function render(text) {
        const esc = escapeHtml(text || '');
        const lines = esc.split(/\r?\n/); let html = ''; let list = null;
        const close = () => { if (list) { html += `</${list}>`; list = null; } };
        const inline = (s) => s.replace(/\*\*([^*]+)\*\*/g, '<strong>$1</strong>').replace(/(^|\s)\*([^*]+)\*(?=\s|$|[.,;:!?])/g, '$1<em>$2</em>');
        for (const raw of lines) {
            const line = raw.trim();
            if (!line) { close(); continue; }
            const b = line.match(/^[-*•]\s+(.*)$/), n = line.match(/^\d+[.)]\s+(.*)$/);
            if (b) { if (list !== 'ul') { close(); html += '<ul>'; list = 'ul'; } html += '<li>' + inline(b[1]) + '</li>'; }
            else if (n) { if (list !== 'ol') { close(); html += '<ol>'; list = 'ol'; } html += '<li>' + inline(n[1]) + '</li>'; }
            else { close(); html += '<p>' + inline(line) + '</p>'; }
        }
        close(); return html || '<p></p>';
    }

    function scrollDown() { thread.scrollTop = thread.scrollHeight; }

    function addTurn(me, html, imageUrl, cost) {
        byId('aiWelcome')?.remove();
        const el = document.createElement('div');
        el.className = 'aimsg' + (me ? ' me' : '');
        el.innerHTML = `<span class="aimsg-face">${face(me)}</span><div class="aibubble">${html}${imageUrl ? `<img src="${escapeHtml(imageUrl)}" alt="">` : ''}${cost ? `<p class="aibubble-cost">${escapeHtml(cost)}</p>` : ''}</div>`;
        thread.appendChild(el);
        scrollDown();
        return el;
    }

    function setBalance(v) {
        byId('aiBalance').textContent = String(Math.round(v * 100) / 100);
        byId('aiNoCredits').classList.toggle('hidden', v > 0);
    }

    const input = byId('aiText');
    input?.addEventListener('input', () => { input.style.height = 'auto'; input.style.height = Math.min(input.scrollHeight, 112) + 'px'; });
    input?.addEventListener('keydown', (e) => {
        if (e.key === 'Enter' && !e.shiftKey && window.matchMedia('(min-width: 768px)').matches) { e.preventDefault(); send(); }
    });
    document.querySelectorAll('.js-suggest').forEach((b) => b.addEventListener('click', () => { input.value = (b.querySelector('.t')?.textContent || b.textContent).trim(); input.dispatchEvent(new Event('input')); input.focus(); }));

    byId('aiPhoto')?.addEventListener('change', async (e) => {
        const file = e.target.files && e.target.files[0]; if (!file) return;
        const form = new FormData(); form.append('image', file);
        try {
            const res = await api(URLS.photo, { method: 'POST', body: form });
            photoPath = res.data.path; byId('aiPhotoThumb').src = res.data.url;
            byId('aiPhotoChip').classList.remove('hidden'); byId('aiPhotoChip').classList.add('flex');
        } catch (err) { toast(err.message, 'error'); } finally { e.target.value = ''; }
    });
    byId('aiPhotoRemove')?.addEventListener('click', () => { photoPath = null; byId('aiPhotoChip').classList.add('hidden'); byId('aiPhotoChip').classList.remove('flex'); });

    async function send() {
        if (busy) return;
        const message = input.value.trim();
        if (!message) { toast('Type a question first.', 'error'); return; }
        busy = true; byId('aiSendBtn').disabled = true;
        addTurn(true, '<p>' + escapeHtml(message).replace(/\r?\n/g, '<br>') + '</p>', photoPath ? byId('aiPhotoThumb').src : null);
        input.value = ''; input.style.height = 'auto';
        const thinking = addTurn(false, '<span class="aidots"><i></i><i></i><i></i></span>');
        try {
            const res = await api(URLS.ask, { method: 'POST', body: { message, conversationId, imagePath: photoPath, scheduleId: SCHEDULE_ID } });
            conversationId = res.data.conversationId;
            thinking.querySelector('.aibubble').innerHTML = render(res.data.answer.content) + `<p class="aibubble-cost">${escapeHtml(String(Math.round(res.data.answer.creditsCharged * 100) / 100))} credits</p>`;
            setBalance(res.data.balance); byId('aiPhotoRemove').click(); scrollDown();
        } catch (err) {
            thinking.remove();
            if (err.data && err.data.outOfCredits) {
                setBalance(err.data.balance || 0);
                addTurn(false, buyCard(err.message)).querySelector('.aibubble').classList.add('is-buy');
            } else { addTurn(false, '<p>' + escapeHtml(err.message) + '</p>'); }
            input.value = message; input.dispatchEvent(new Event('input'));
        } finally { busy = false; byId('aiSendBtn').disabled = false; input.focus(); }
    }
    byId('aiSendBtn')?.addEventListener('click', send);

    /* Link this chat to a day or an activity of the schedule. */
    byId('aiLinkBtn')?.addEventListener('click', () => openSheet('aiLinkSheet'));
    function updateLinkChip(label) {
        const chip = byId('aiLinkChip');
        const btn = byId('aiLinkBtn');
        if (label) {
            byId('aiLinkChipText').textContent = label;
            chip.classList.remove('hidden'); chip.classList.add('flex');
            btn?.classList.add('text-brand-700');
        } else {
            chip.classList.add('hidden'); chip.classList.remove('flex');
            btn?.classList.remove('text-brand-700');
        }
    }
    async function ensureConversation() {
        if (conversationId) return conversationId;
        const res = await api(URLS.newConvo, { method: 'POST', body: { scheduleId: SCHEDULE_ID } });
        conversationId = res.data.conversationId;
        return conversationId;
    }
    async function saveLink(linkType, extra) {
        try {
            await ensureConversation();
            const res = await api(URLS.link, { method: 'POST', body: Object.assign({ conversationId, linkType }, extra || {}) });
            updateLinkChip(res.data.linkLabel || '');
            toast(res.message);
            closeSheet('aiLinkSheet');
        } catch (err) { toast(err.message, 'error'); }
    }
    byId('aiLinkDayBtn')?.addEventListener('click', () => {
        const d = byId('aiLinkDate').value;
        if (!d) { toast('Choose a day.', 'error'); return; }
        saveLink('date', { linkedDate: d });
    });
    byId('aiLinkActBtn')?.addEventListener('click', () => {
        const a = byId('aiLinkActivity').value;
        if (!a) { toast('Choose an activity.', 'error'); return; }
        saveLink('activity', { linkedActivityId: a });
    });
    byId('aiLinkRemoveBtn')?.addEventListener('click', () => saveLink('none'));
    byId('aiLinkChipClear')?.addEventListener('click', () => saveLink('none'));

    /* history + conversations */
    byId('aiHistoryBtn')?.addEventListener('click', () => openSheet('aiHistorySheet'));
    async function startNew() {
        try { const res = await api(URLS.newConvo, { method: 'POST', body: { scheduleId: SCHEDULE_ID } }); location.href = URLS.page + '&c=' + res.data.conversationId; }
        catch (err) { toast(err.message, 'error'); }
    }
    byId('aiNewChatBtn')?.addEventListener('click', startNew);
    byId('aiNewFromSheet')?.addEventListener('click', startNew);
    byId('aiSideNewBtn')?.addEventListener('click', startNew);

    // Inline rename in the sessions rail.
    document.addEventListener('click', (e) => {
        const btn = e.target.closest('.js-side-rename');
        if (!btn) return;
        e.preventDefault();
        const row = btn.closest('.ai-session-row');
        const titleEl = row.querySelector('[data-session-title]');
        if (!titleEl || row.querySelector('.ai-session-rename')) return;
        const input = document.createElement('input');
        input.type = 'text';
        input.maxLength = 60;
        input.value = titleEl.textContent.trim();
        input.className = 'ai-session-rename';
        titleEl.style.display = 'none';
        titleEl.after(input);
        input.focus();
        input.select();
        let done = false;
        const finish = async (save) => {
            if (done) return;
            done = true;
            const next = input.value.trim();
            input.remove();
            titleEl.style.display = '';
            if (!save || !next || next === titleEl.textContent.trim()) return;
            try {
                const res = await api(URLS.rename, { method: 'POST', body: { id: btn.dataset.id, title: next } });
                titleEl.textContent = res.data.title;
                toast('Chat renamed.');
            } catch (err) { toast(err.message, 'error'); }
        };
        input.addEventListener('keydown', (ev) => {
            if (ev.key === 'Enter') finish(true);
            if (ev.key === 'Escape') finish(false);
        });
        input.addEventListener('blur', () => finish(true));
    });

    // Inside the SPA these are real links; let them navigate the shell.
    document.addEventListener('click', async (e) => {
        const del = e.target.closest('.js-ai-del');
        if (del) {
            e.preventDefault();
            const ok = await confirmAction({ title: 'Delete this conversation?', message: 'Its questions and answers are removed.', detail: 'Credits already spent are not refunded.', confirmText: 'Delete' });
            if (!ok) return;
            try { await api(URLS.delConvo(del.dataset.id), { method: 'DELETE' }); del.closest('.ai-session-row, .flex').remove(); if (String(del.dataset.id) === String(conversationId)) location.href = URLS.page; }
            catch (err) { toast(err.message, 'error'); }
        }
    });

    // Only auto-scroll when there is a conversation; keep the welcome hero in view.
    if (!byId('aiWelcome')) scrollDown();
};
    if (document.readyState === 'loading') document.addEventListener('DOMContentLoaded', __init, { once: true });
    else __init();
})();
</script>
@endpush
