{{--
    Floating AI-technician chat, available on every schedule page.
    Self-contained: pulls the AI settings + credit balance itself and talks to
    the same ai.ask endpoint (real credit tracking + out-of-credits prompt).
    Expects: $schedule.
--}}
@php
    $aiFloatSettings = \App\Models\AiSetting::current();
    $aiFloatBalance = $aiFloatSettings
        ? app(\App\Services\AiCreditService::class)->balance(auth()->id())
        : 0;
    $aiFloatAvatar = $aiFloatSettings && $aiFloatSettings->avatarPath
        ? \App\Support\MediaStore::url($aiFloatSettings->avatarPath)
        : null;
@endphp
@if ($aiFloatSettings && $aiFloatSettings->isUsable())
<div id="aiFloat" class="ai-float{{ request('module') === 'ai' ? ' ai-float-off' : '' }}">
    <button type="button" id="aiFloatFab" class="ai-float-fab" aria-label="Ask the AI Technician" title="Ask the AI Technician">
        @if ($aiFloatAvatar)
            <img src="{{ $aiFloatAvatar }}" alt="">
        @else
            <svg class="w-7 h-7" fill="none" stroke="currentColor" stroke-width="1.8" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" d="M12 3v2m0 0a7 7 0 017 7v3a3 3 0 01-3 3H8a3 3 0 01-3-3v-3a7 7 0 017-7zM9 12h.01M15 12h.01M9.5 17h5"/></svg>
        @endif
    </button>

    <div id="aiFloatPanel" class="ai-float-panel hidden">
        <div class="ai-float-head">
            <span class="ai-float-avatar">
                <span class="ai-float-face">
                    @if ($aiFloatAvatar)<img src="{{ $aiFloatAvatar }}" alt="">@else<svg class="w-4 h-4" fill="none" stroke="currentColor" stroke-width="1.8" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" d="M12 3v2m0 0a7 7 0 017 7v3a3 3 0 01-3 3H8a3 3 0 01-3-3v-3a7 7 0 017-7z"/></svg>@endif
                </span>
            </span>
            <div class="min-w-0 grow">
                <p class="ai-float-name truncate">{{ $aiFloatSettings->assistantName }}</p>
                <a href="{{ route('ai.credits') }}" class="ai-float-credits">
                    <svg class="w-3 h-3" viewBox="0 0 20 20" fill="currentColor"><path d="M10 2a8 8 0 100 16 8 8 0 000-16zm.75 4.5v.63a2.5 2.5 0 01.2 4.84v.78a.75.75 0 01-1.5 0v-.75a2.6 2.6 0 01-1.83-1.1.75.75 0 011.24-.84c.24.35.63.57 1.09.57.6 0 1.05-.36 1.05-.83 0-.44-.3-.7-1.2-.95-1.13-.32-2.05-.8-2.05-2.05a2.2 2.2 0 011.5-2.03V6.5a.75.75 0 011.5 0z"/></svg>
                    <span id="aiFloatBalance">{{ rtrim(rtrim(number_format($aiFloatBalance, 2), '0'), '.') }}</span>&nbsp;credits
                </a>
            </div>
            <button type="button" id="aiFloatClose" class="ai-float-icon" aria-label="Close">
                <svg class="w-4 h-4" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" d="M6 18L18 6M6 6l12 12"/></svg>
            </button>
        </div>

        <div class="ai-float-thread" id="aiFloatThread">
            <div class="ai-float-welcome" id="aiFloatWelcome">
                <span class="ai-float-hero">
                    @if ($aiFloatAvatar)<img src="{{ $aiFloatAvatar }}" alt="">@else<svg class="w-6 h-6" fill="none" stroke="currentColor" stroke-width="1.6" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" d="M12 3v2m0 0a7 7 0 017 7v3a3 3 0 01-3 3H8a3 3 0 01-3-3v-3a7 7 0 017-7zM9 12h.01M15 12h.01M9.5 17h5"/></svg>@endif
                </span>
                <p class="font-semibold text-gray-800 mt-2">Ask about {{ \Illuminate\Support\Str::limit($schedule->cropType ?: 'this crop', 24) }}</p>
                <p class="text-sm text-gray-500 mt-1">Fertiliser rates, pests, water, timing — or snap a leaf.</p>
                <button type="button" class="ai-float-sug js-float-suggest">
                    <span class="ic" aria-hidden="true"><svg class="w-4 h-4" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" d="M5 21c.5-4.5 2.5-15 16-17-.5 13.5-8 16-12 16-1.33 0-2.67 0-4 1zm0 0c2-6 5-10 10-12"/></svg></span>
                    <span class="t">My leaves are yellowing at the tips — what should I check?</span>
                </button>
                <button type="button" class="ai-float-sug js-float-suggest">
                    <span class="ic" aria-hidden="true"><svg class="w-4 h-4" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" d="M12 3s6 6.5 6 11a6 6 0 11-12 0c0-4.5 6-11 6-11z"/></svg></span>
                    <span class="t">Is it a good time to irrigate today?</span>
                </button>
            </div>
        </div>

        <div class="ai-float-nocredits hidden" id="aiFloatNoCredits">
            <span class="ico">
                <svg class="w-4 h-4" viewBox="0 0 20 20" fill="currentColor"><path d="M10 2a8 8 0 100 16 8 8 0 000-16zm.75 4.5v.63a2.5 2.5 0 01.2 4.84v.78a.75.75 0 01-1.5 0v-.75a2.6 2.6 0 01-1.83-1.1.75.75 0 011.24-.84c.24.35.63.57 1.09.57.6 0 1.05-.36 1.05-.83 0-.44-.3-.7-1.2-.95-1.13-.32-2.05-.8-2.05-2.05a2.2 2.2 0 011.5-2.03V6.5a.75.75 0 011.5 0z"/></svg>
            </span>
            <div>
                <p class="text-sm font-semibold text-gray-900">You're out of AI credits</p>
                <p class="text-xs text-gray-500 mt-0.5">Purchase AI chat credits to keep asking.</p>
                <a href="{{ route('ai.credits') }}" class="btn btn-accent btn-sm mt-2">Purchase AI credits</a>
            </div>
        </div>

        <div class="ai-float-composer">
            <div id="aiFloatPhotoChip" class="ai-float-photochip hidden">
                <img src="" alt="" id="aiFloatPhotoThumb"><span class="grow">Photo attached</span>
                <button type="button" id="aiFloatPhotoRemove" class="text-red-600 font-bold">Remove</button>
            </div>
            <div class="ai-float-box">
                <label class="ai-float-cam shrink-0" title="Attach a photo">
                    <svg class="w-5 h-5" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" d="M3 9a2 2 0 012-2h.93a2 2 0 001.664-.89l.812-1.22A2 2 0 0110.07 4h3.86a2 2 0 011.664.89l.812 1.22A2 2 0 0018.07 7H19a2 2 0 012 2v9a2 2 0 01-2 2H5a2 2 0 01-2-2V9z"/><path stroke-linecap="round" stroke-linejoin="round" d="M15 13a3 3 0 11-6 0 3 3 0 016 0z"/></svg>
                    <input type="file" id="aiFloatPhoto" accept="image/*" capture="environment" class="hidden">
                </label>
                <textarea id="aiFloatText" rows="1" maxlength="4000" placeholder="Ask about your crop…"></textarea>
                <button type="button" id="aiFloatSend" class="ai-float-send" aria-label="Send">
                    <svg class="w-4 h-4" fill="none" stroke="currentColor" stroke-width="2.2" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" d="M5 12h14m0 0l-6-6m6 6l-6 6"/></svg>
                </button>
            </div>
        </div>
    </div>
</div>

<style>
    /* Same "Field Advisor" language as the full AI page, pocket-sized.
       Theme vars only — html.dark's variable repoint restyles it for free. */
    .ai-float { position: fixed; right: 1rem; bottom: 5.5rem; z-index: 60; }
    /* The activities shell adds this while the AI module itself is showing. */
    .ai-float.ai-float-off { display: none; }
    @media (min-width: 768px) { .ai-float { bottom: 1.25rem; right: 1.25rem; } }
    .ai-float-fab {
        width: 3.5rem; height: 3.5rem; border-radius: 999px; overflow: hidden;
        display: flex; align-items: center; justify-content: center;
        background: linear-gradient(140deg, #6b9f3d, #3d6823); color: #fff;
        box-shadow: 0 0 0 2px var(--color-white), 0 0 0 4px rgb(74 124 42 / .35), 0 6px 20px rgba(0,0,0,.22);
        transition: transform .15s ease, filter .15s ease;
        animation: aiFabPulse .9s ease 1;
    }
    .ai-float-fab:hover { filter: brightness(1.05); } .ai-float-fab:active { transform: scale(.95); }
    .ai-float-fab img { width: 100%; height: 100%; object-fit: cover; }
    @keyframes aiFabPulse {
        from { box-shadow: 0 0 0 2px var(--color-white), 0 0 0 0 rgb(74 124 42 / .45), 0 6px 20px rgba(0,0,0,.22); }
        to { box-shadow: 0 0 0 2px var(--color-white), 0 0 0 14px rgb(74 124 42 / 0), 0 6px 20px rgba(0,0,0,.22); }
    }

    .ai-float-panel {
        position: absolute; right: 0; bottom: 4.25rem; width: min(24rem, calc(100vw - 2rem));
        height: min(32rem, calc(100dvh - 9rem)); display: flex; flex-direction: column;
        background: var(--color-white); border: 1px solid var(--color-gray-200);
        border-radius: 1.1rem; box-shadow: 0 16px 44px rgba(0,0,0,.24); overflow: hidden;
        animation: aiFloatIn .28s cubic-bezier(.22,1,.36,1) both;
    }
    /* This inline sheet loads after Tailwind, so re-assert the hidden toggle
       or `display:flex` above would win and the ✕ could never close the panel. */
    .ai-float-panel.hidden { display: none; }
    @keyframes aiFloatIn { from { opacity: 0; transform: translateY(10px) scale(.98); } to { opacity: 1; transform: none; } }

    .ai-float-head { display: flex; align-items: center; gap: .5rem; padding: .6rem .75rem; border-bottom: 1px solid var(--color-brand-100); background: linear-gradient(115deg, var(--color-brand-50), var(--color-white) 70%); }
    .ai-float-avatar { position: relative; flex-shrink: 0; }
    .ai-float-avatar .ai-float-face { box-shadow: 0 0 0 2px var(--color-white), 0 0 0 3px var(--color-brand-200); }
    .ai-float-avatar::after { content: ""; position: absolute; right: -1px; bottom: -1px; width: .6rem; height: .6rem; border-radius: 999px; background: var(--color-brand-500); border: 2px solid var(--color-white); }
    .ai-float-face { width: 2rem; height: 2rem; border-radius: 999px; overflow: hidden; flex-shrink: 0; display: flex; align-items: center; justify-content: center; background: var(--color-brand-50); color: var(--color-brand-700); }
    .ai-float-face img { width: 100%; height: 100%; object-fit: cover; }
    .ai-float-name { font-family: var(--font-heading); font-weight: 700; font-size: .95rem; line-height: 1.2; color: var(--color-gray-900); }
    /* Literal amber: accent-700 fails contrast on the cream wash in light mode. */
    .ai-float-credits { display: inline-flex; align-items: center; gap: .25rem; margin-top: .1rem; padding: .1rem .5rem; border-radius: 999px; background: rgb(245 197 24 / .16); color: #8a6100; font-size: .72rem; font-weight: 800; font-variant-numeric: tabular-nums; }
    .ai-float-credits:hover { background: rgb(245 197 24 / .26); }
    .ai-float-icon { display: inline-flex; align-items: center; justify-content: center; width: 2rem; height: 2rem; border-radius: .55rem; color: var(--color-gray-500); flex-shrink: 0; }
    .ai-float-icon:hover { background: var(--color-gray-100); color: var(--color-gray-700); }

    .ai-float-thread { flex: 1 1 auto; overflow-y: auto; padding: .75rem; scroll-behavior: smooth; display: flex; flex-direction: column; scrollbar-width: thin; scrollbar-color: var(--color-gray-300) transparent; }
    .ai-float-thread::-webkit-scrollbar { width: 5px; }
    .ai-float-thread::-webkit-scrollbar-track { background: transparent; }
    .ai-float-thread::-webkit-scrollbar-thumb { background: var(--color-gray-300); border-radius: 999px; }
    /* Welcome centers; a short conversation grows up from the composer. */
    #aiFloatWelcome { margin: auto 0; }
    .ai-float-msg:first-child { margin-top: auto; }
    /* Hide the launcher while the panel is open — no doubled controls in the corner. */
    .ai-float.is-open .ai-float-fab { display: none; }
    .ai-float-welcome { text-align: center; padding: 1.25rem .5rem .75rem; }
    .ai-float-hero { width: 3rem; height: 3rem; border-radius: 999px; overflow: hidden; margin: 0 auto; display: flex; align-items: center; justify-content: center; background: linear-gradient(150deg, #6b9f3d, #3d6823); color: #fff; box-shadow: 0 0 0 2px var(--color-white), 0 0 0 4px var(--color-brand-200); }
    .ai-float-hero img { width: 100%; height: 100%; object-fit: cover; }
    .ai-float-sug { display: flex; align-items: center; gap: .6rem; width: 100%; margin-top: .9rem; padding: .6rem .75rem; text-align: left; border: 1px solid var(--color-gray-200); border-radius: .9rem; background: var(--color-white); box-shadow: var(--shadow-card); font-size: .9rem; font-weight: 700; color: var(--color-gray-800); cursor: pointer; transition: transform .18s ease, border-color .18s ease; }
    .ai-float-sug:hover { transform: translateY(-1px); border-color: var(--color-brand-300); }
    .ai-float-sug .ic { width: 1.9rem; height: 1.9rem; border-radius: .6rem; flex-shrink: 0; display: flex; align-items: center; justify-content: center; background: var(--color-brand-50); color: var(--color-brand-700); }
    .ai-float-sug .t { flex: 1 1 auto; min-width: 0; }

    .ai-float-msg { display: flex; gap: .45rem; margin-bottom: .6rem; align-items: flex-end; animation: aiRise .28s cubic-bezier(.22,1,.36,1) both; }
    .ai-float-msg.me { flex-direction: row-reverse; }
    @keyframes aiRise { from { opacity: 0; transform: translateY(8px); } to { opacity: 1; transform: none; } }
    .ai-float-msg .b { max-width: 84%; padding: .5rem .75rem; font-size: .92rem; line-height: 1.5; background: var(--color-white); border: 1px solid var(--color-gray-100); border-radius: .9rem .9rem .9rem .25rem; box-shadow: 0 1px 2px rgb(26 26 26 / .06); }
    /* Literal hex: brand-700 repoints bright in dark mode and would sink white text. */
    .ai-float-msg.me .b { background: linear-gradient(135deg, #4a7c2a, #3d6823); color: #fff; border-color: transparent; border-radius: .9rem .9rem .25rem .9rem; }
    .ai-float-msg .b p { margin: .25rem 0; } .ai-float-msg .b p:first-child { margin-top: 0; } .ai-float-msg .b p:last-child { margin-bottom: 0; }
    .ai-float-msg .b ul { list-style: disc; padding-left: 1.1rem; margin: .25rem 0; }
    .ai-float-msg .b ol { list-style: decimal; padding-left: 1.25rem; margin: .25rem 0; }
    .ai-float-msg .b img { max-width: 100%; max-height: 180px; border-radius: .5rem; margin-top: .3rem; }
    .ai-float-msg .b .cost { display: inline-flex; align-items: center; gap: .25rem; margin-top: .35rem; padding: .1rem .45rem; border-radius: 999px; font-size: .62rem; font-weight: 800; font-variant-numeric: tabular-nums; color: #8a6100; background: rgb(245 197 24 / .15); }
    .ai-float-msg .b .cost::before { content: ""; width: .32rem; height: .32rem; border-radius: 999px; background: var(--color-accent-500); }
    .ai-float-msg.me .b .cost { background: rgb(255 255 255 / .2); color: #fff; }
    .ai-float-msg.me .b .cost::before { background: #fff; }

    /* Out-of-credits purchase card, rendered as an assistant turn. */
    .ai-float-msg .b.is-buy { border-color: rgb(245 197 24 / .4); background: linear-gradient(115deg, rgb(245 197 24 / .14), rgb(245 197 24 / .04)), var(--color-white); }
    .ai-buyc { display: flex; gap: .6rem; align-items: flex-start; }
    .ai-buyc .ico { width: 2rem; height: 2rem; border-radius: .7rem; flex-shrink: 0; display: flex; align-items: center; justify-content: center; background: var(--color-accent-500); color: #1a1a1a; }
    .ai-buyc h3 { font-family: var(--font-heading); font-size: .92rem; font-weight: 700; color: var(--color-gray-900); }
    .ai-buyc p { font-size: .8rem; color: var(--color-gray-600); }

    .ai-float-nocredits { display: flex; gap: .6rem; align-items: flex-start; margin: 0 .75rem .5rem; padding: .7rem .8rem; border: 1px solid rgb(245 197 24 / .4); background: linear-gradient(115deg, rgb(245 197 24 / .14), rgb(245 197 24 / .04)), var(--color-white); border-radius: .9rem; }
    .ai-float-nocredits.hidden { display: none; }
    /* Literal ink: var(--color-ink) flips near-white in dark mode. */
    .ai-float-nocredits .ico { width: 2rem; height: 2rem; border-radius: .7rem; flex-shrink: 0; display: flex; align-items: center; justify-content: center; background: var(--color-accent-500); color: #1a1a1a; }

    .ai-float-composer { flex-shrink: 0; padding: .6rem .75rem .75rem; border-top: 1px solid var(--color-gray-100); }
    .ai-float-photochip { display: flex; align-items: center; gap: .4rem; font-size: .72rem; font-weight: 600; color: var(--color-gray-500); margin-bottom: .4rem; background: var(--color-gray-100); border-radius: .6rem; padding: .3rem .5rem; }
    .ai-float-photochip.hidden { display: none; }
    .ai-float-photochip img { width: 1.9rem; height: 1.9rem; border-radius: .4rem; object-fit: cover; box-shadow: 0 0 0 2px var(--color-brand-200); }
    .ai-float-box { display: flex; align-items: flex-end; gap: .25rem; border: 1.5px solid var(--color-gray-200); border-radius: 1.1rem; padding: .25rem .25rem .25rem .4rem; background: var(--color-white); transition: border-color .15s ease, box-shadow .15s ease; }
    .ai-float-box:focus-within { border-color: var(--color-brand-500); box-shadow: 0 0 0 3px rgb(107 159 61 / .18); }
    .ai-float-cam { width: 2.25rem; height: 2.25rem; border-radius: .75rem; display: flex; align-items: center; justify-content: center; flex-shrink: 0; background: var(--color-brand-50); color: var(--color-brand-700); cursor: pointer; transition: background .15s ease; }
    .ai-float-cam:hover { background: var(--color-brand-100); }
    #aiFloatText { resize: none; border: 0; outline: none; background: transparent; flex: 1 1 auto; max-height: 6rem; padding: .4rem .25rem; font-size: .95rem; color: inherit; }
    .ai-float-send { width: 2.25rem; height: 2.25rem; border-radius: 999px; background: linear-gradient(140deg, #6b9f3d, #3d6823); color: #fff; display: flex; align-items: center; justify-content: center; flex-shrink: 0; box-shadow: 0 2px 8px -2px rgb(45 80 22 / .5); transition: transform .15s ease; }
    .ai-float-send:hover:not(:disabled) { transform: scale(1.06); }
    .ai-float-send:disabled { opacity: .4; }
    .ai-float-dots { display: inline-flex; gap: .2rem; align-items: center; height: 1rem; }
    .ai-float-dots i { width: .35rem; height: .35rem; border-radius: 999px; background: var(--color-brand-500); opacity: .35; animation: aidot .9s cubic-bezier(.4,0,.2,1) infinite; }
    .ai-float-dots i:nth-child(2) { animation-delay: .15s; } .ai-float-dots i:nth-child(3) { animation-delay: .3s; }
    /* The full page defines these too, but the float renders on pages that
       never load that stylesheet — without this the dots freeze. */
    @keyframes aidot { 0%, 60%, 100% { opacity: .3; transform: translateY(0); } 30% { opacity: 1; transform: translateY(-3px); } }

    html.dark .ai-float-credits { color: var(--color-accent-400); }
    html.dark .ai-float-msg .b .cost { color: var(--color-accent-400); }
    html.dark .ai-float-msg.me .b .cost { color: #fff; }
    html.dark .ai-float-box:focus-within { box-shadow: 0 0 0 3px rgb(124 184 79 / .22); }
    html.dark .ai-float-nocredits { border-color: rgb(245 197 24 / .25); background: linear-gradient(115deg, rgb(245 197 24 / .10), rgb(245 197 24 / .03)), var(--color-white); }
    html.dark .ai-float-msg .b.is-buy { border-color: rgb(245 197 24 / .25); background: linear-gradient(115deg, rgb(245 197 24 / .10), rgb(245 197 24 / .03)), var(--color-white); }

    @media (prefers-reduced-motion: reduce) {
        .ai-float-fab, .ai-float-msg, .ai-float-panel { animation: none; }
        .ai-float-sug, .ai-float-box, .ai-float-send { transition: none; }
    }

    /* ---- Green identity ----
       The panel wore the page's white-on-white; against a busy schedule board
       it disappeared. The header takes the technician's green (same gradient
       as the fab and send button) and the border rings the panel in it, so
       the chat reads as one thing at a glance. Later in the sheet than the
       rules it overrides, deliberately. */
    .ai-float-panel { border: 2px solid #4a7c2a; }
    .ai-float-head { background: linear-gradient(140deg, #6b9f3d, #3d6823); border-bottom-color: transparent; }
    .ai-float-head .ai-float-name { color: #fff; }
    .ai-float-head .ai-float-credits { background: rgb(255 255 255 / .2); color: #fff; }
    .ai-float-head .ai-float-credits:hover { background: rgb(255 255 255 / .3); }
    .ai-float-head .ai-float-icon { color: rgb(255 255 255 / .85); }
    .ai-float-head .ai-float-icon:hover { background: rgb(255 255 255 / .18); color: #fff; }
    .ai-float-head .ai-float-avatar::after { border-color: #4a7c2a; background: var(--color-accent-500); }
    html.dark .ai-float-head .ai-float-credits { color: #fff; }

    /* ---- Phones: the chat takes the whole screen ----
       A 24rem bubble over the activities board was cramped and half-covered.
       Fullscreen with a dimmed backdrop behind it (visible in the safe-area
       fringes) makes it a place you are IN rather than a widget in the way.
       position:fixed escapes the .ai-float container, which stays where it
       is; its z-index still scopes the whole thing above the page. */
    @media (max-width: 767px) {
        .ai-float.is-open { z-index: 200; }
        .ai-float.is-open::before { content: ''; position: fixed; inset: 0; background: rgb(15 23 42 / .5); }
        .ai-float-panel {
            position: fixed; inset: 0; width: auto; height: auto; max-height: none;
            border-radius: 0;
        }
        /* The bubble stays out of the activities board entirely — the Tools
           menu opens the chat there. Other schedule pages keep the bubble. */
        body.act-module-open .ai-float-fab { display: none; }
    }

    /* ---- Appear / disappear ----
       Opening already rode aiFloatIn; closing snapped shut because .hidden is
       display:none and no animation survives that. is-closing holds the panel
       visible just long enough for the exit to play (openPanel removes it and
       adds .hidden after). The overlay fades with the same rhythm. */
    .ai-float.is-closing .ai-float-panel { animation: aiFloatOut .22s ease both; }
    @keyframes aiFloatOut { to { opacity: 0; transform: translateY(12px) scale(.98); } }
    .ai-float.is-open::before { animation: aiOverlayIn .28s ease both; }
    .ai-float.is-closing::before { animation: aiOverlayOut .22s ease both; }
    @keyframes aiOverlayIn { from { opacity: 0; } }
    @keyframes aiOverlayOut { to { opacity: 0; } }
    @media (prefers-reduced-motion: reduce) {
        .ai-float.is-closing .ai-float-panel,
        .ai-float.is-open::before,
        .ai-float.is-closing::before { animation: none; }
    }
</style>

<script>
(() => {
    const init = () => {
        const $ = (id) => document.getElementById(id);
        const fab = $('aiFloatFab'), panel = $('aiFloatPanel'), thread = $('aiFloatThread');
        if (!fab || !panel) return;

        const URLS = {
            ask: @json(route('ai.ask')),
            photo: @json(route('ai.photo')),
            credits: @json(route('ai.credits')),
        };
        const SCHEDULE_ID = @json($schedule->id);
        const AVATAR = @json($aiFloatAvatar);
        const MY = @json(auth()->user()->initials ?? '');
        const BOT = '<svg class="w-4 h-4" fill="none" stroke="currentColor" stroke-width="1.8" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" d="M12 3v2m0 0a7 7 0 017 7v3a3 3 0 01-3 3H8a3 3 0 01-3-3v-3a7 7 0 017-7z"/></svg>';
        const COIN = '<svg class="w-4 h-4" viewBox="0 0 20 20" fill="currentColor"><path d="M10 2a8 8 0 100 16 8 8 0 000-16zm.75 4.5v.63a2.5 2.5 0 01.2 4.84v.78a.75.75 0 01-1.5 0v-.75a2.6 2.6 0 01-1.83-1.1.75.75 0 011.24-.84c.24.35.63.57 1.09.57.6 0 1.05-.36 1.05-.83 0-.44-.3-.7-1.2-.95-1.13-.32-2.05-.8-2.05-2.05a2.2 2.2 0 011.5-2.03V6.5a.75.75 0 011.5 0z"/></svg>';
        const buyCard = (msg) => `<div class="ai-buyc"><span class="ico">${COIN}</span><div><h3>You're out of AI Credits</h3><p>${escapeHtml(msg)}</p><a class="btn btn-accent btn-sm mt-2" href="${escapeHtml(URLS.credits)}">Purchase AI credits</a></div></div>`;
        let conversationId = null, photoPath = null, busy = false;

        const face = (me) => me ? escapeHtml(MY) : (AVATAR ? `<img src="${escapeHtml(AVATAR)}" alt="">` : BOT);
        const scrollDown = () => { thread.scrollTop = thread.scrollHeight; };

        function render(text) {
            const esc = escapeHtml(text || '');
            const lines = esc.split(/\r?\n/); let html = ''; let list = null;
            const close = () => { if (list) { html += `</${list}>`; list = null; } };
            const inline = (s) => s.replace(/\*\*([^*]+)\*\*/g, '<strong>$1</strong>');
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
        function addTurn(me, html, imageUrl, cost) {
            $('aiFloatWelcome')?.remove();
            const el = document.createElement('div');
            el.className = 'ai-float-msg' + (me ? ' me' : '');
            el.innerHTML = `<span class="ai-float-face">${face(me)}</span><div class="b">${html}${imageUrl ? `<img src="${escapeHtml(imageUrl)}" alt="">` : ''}${cost ? `<p class="cost">${escapeHtml(cost)}</p>` : ''}</div>`;
            thread.appendChild(el); scrollDown(); return el;
        }
        function setBalance(v) {
            const el = $('aiFloatBalance'); if (el) el.textContent = String(Math.round(v * 100) / 100);
            // A failed send already answers with a purchase card in the thread,
            // right where the user is looking — the banner on top of it said
            // the same thing twice. It still covers opening the chat when the
            // credits were already gone, where no card exists yet.
            const saidInChat = !!thread.querySelector('.is-buy');
            $('aiFloatNoCredits').classList.toggle('hidden', v > 0 || saidInChat);
        }

        let closeTimer = null;
        const openPanel = (open) => {
            const root = $('aiFloat');
            clearTimeout(closeTimer);
            if (open) {
                root?.classList.remove('is-closing');
                panel.classList.remove('hidden');
                root?.classList.add('is-open');
                // On a phone, focusing on open throws the keyboard over the
                // chat before a word has been read. A tap still opens it.
                if (!window.matchMedia('(pointer: coarse)').matches) {
                    window.smFocus($('aiFloatText'), { delay: 60 });
                }
                return;
            }
            if (panel.classList.contains('hidden')) return;
            // .hidden is display:none, which no exit animation survives — so
            // is-closing plays the exit first and the hide lands after it.
            if (window.matchMedia('(prefers-reduced-motion: reduce)').matches) {
                panel.classList.add('hidden');
                root?.classList.remove('is-open');
                return;
            }
            root?.classList.add('is-closing');
            closeTimer = setTimeout(() => {
                panel.classList.add('hidden');
                root?.classList.remove('is-open', 'is-closing');
            }, 230);
        };
        fab.addEventListener('click', () => openPanel(panel.classList.contains('hidden')));
        $('aiFloatClose')?.addEventListener('click', () => openPanel(false));

        const input = $('aiFloatText');
        panel.querySelectorAll('.js-float-suggest').forEach((b) => b.addEventListener('click', () => {
            input.value = (b.querySelector('.t')?.textContent || b.textContent).trim();
            input.dispatchEvent(new Event('input')); input.focus();
        }));
        input?.addEventListener('input', () => { input.style.height = 'auto'; input.style.height = Math.min(input.scrollHeight, 96) + 'px'; });
        input?.addEventListener('keydown', (e) => {
            if (e.key === 'Enter' && !e.shiftKey && window.matchMedia('(min-width: 768px)').matches) { e.preventDefault(); send(); }
        });

        $('aiFloatPhoto')?.addEventListener('change', async (e) => {
            const file = e.target.files && e.target.files[0]; if (!file) return;
            const form = new FormData(); form.append('image', file);
            try {
                const res = await api(URLS.photo, { method: 'POST', body: form });
                photoPath = res.data.path; $('aiFloatPhotoThumb').src = res.data.url;
                $('aiFloatPhotoChip').classList.remove('hidden');
            } catch (err) { toast(err.message, 'error'); } finally { e.target.value = ''; }
        });
        $('aiFloatPhotoRemove')?.addEventListener('click', () => { photoPath = null; $('aiFloatPhotoChip').classList.add('hidden'); });

        async function send() {
            if (busy) return;
            const message = (input.value || '').trim();
            if (!message) { toast('Type a question first.', 'error'); return; }
            busy = true; $('aiFloatSend').disabled = true;
            addTurn(true, '<p>' + escapeHtml(message).replace(/\r?\n/g, '<br>') + '</p>', photoPath ? $('aiFloatPhotoThumb').src : null);
            input.value = ''; input.style.height = 'auto';
            const thinking = addTurn(false, '<span class="ai-float-dots"><i></i><i></i><i></i></span>');
            try {
                const res = await api(URLS.ask, { method: 'POST', body: { message, conversationId, imagePath: photoPath, scheduleId: SCHEDULE_ID } });
                conversationId = res.data.conversationId;
                thinking.querySelector('.b').innerHTML = render(res.data.answer.content) + `<p class="cost">${escapeHtml(String(Math.round(res.data.answer.creditsCharged * 100) / 100))} credits</p>`;
                setBalance(res.data.balance); photoPath = null; $('aiFloatPhotoChip').classList.add('hidden'); scrollDown();
            } catch (err) {
                thinking.remove();
                if (err.data && err.data.outOfCredits) {
                    // Card first: setBalance keeps the banner down when the
                    // thread already carries the purchase card.
                    addTurn(false, buyCard(err.message)).querySelector('.b').classList.add('is-buy');
                    setBalance(err.data.balance || 0);
                } else { addTurn(false, '<p>' + escapeHtml(err.message) + '</p>'); }
                input.value = message; input.dispatchEvent(new Event('input'));
            } finally { busy = false; $('aiFloatSend').disabled = false; input.focus(); }
        }
        $('aiFloatSend')?.addEventListener('click', send);
    };
    if (document.readyState === 'loading') document.addEventListener('DOMContentLoaded', init, { once: true });
    else init();
})();
</script>
@endif
