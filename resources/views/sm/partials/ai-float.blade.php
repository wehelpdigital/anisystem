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
    // Same free-rider rule as the full pages: an account that is never
    // charged is never shown a price, a balance, or a purchase card.
    $aiFloatUnlimited = app(\App\Services\AiCreditService::class)->unlimited((int) auth()->id());
    // The menu's "save onto a task" picker — rendered with the page, since
    // the float already knows its schedule and tasks change rarely enough.
    $aiFloatTasks = \App\Models\AsScheduleActivity::query()
        ->where('croppingScheduleId', $schedule->id)
        ->orderByDesc('targetDate')
        ->limit(30)
        ->get(['id', 'activityTitle', 'targetDate']);
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
                @unless ($aiFloatUnlimited)
                <a href="{{ route('ai.credits') }}" class="ai-float-credits">
                    <svg class="w-3 h-3" viewBox="0 0 20 20" fill="currentColor"><path d="M10 2a8 8 0 100 16 8 8 0 000-16zm.75 4.5v.63a2.5 2.5 0 01.2 4.84v.78a.75.75 0 01-1.5 0v-.75a2.6 2.6 0 01-1.83-1.1.75.75 0 011.24-.84c.24.35.63.57 1.09.57.6 0 1.05-.36 1.05-.83 0-.44-.3-.7-1.2-.95-1.13-.32-2.05-.8-2.05-2.05a2.2 2.2 0 011.5-2.03V6.5a.75.75 0 011.5 0z"/></svg>
                    <span id="aiFloatBalance">{{ rtrim(rtrim(number_format($aiFloatBalance, 2), '0'), '.') }}</span>&nbsp;credits
                </a>
                @endunless
            </div>
            <div class="relative shrink-0">
                <button type="button" id="aiFloatMenuBtn" class="ai-float-icon" title="Chat options" aria-label="Chat options" aria-haspopup="menu">
                    <svg class="w-4 h-4" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" d="M12 6h.01M12 12h.01M12 18h.01"/></svg>
                </button>
                {{-- The pocket version of the AI page's session menu: the
                     past, a fresh start, and two ways to file this chat. --}}
                <div id="aiFloatMenu" class="ai-float-attmenu is-belowright hidden">
                    <button type="button" id="aiFloatOldChats">
                        <svg class="w-4 h-4" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" d="M12 8v4l3 2m6-2a9 9 0 11-18 0 9 9 0 0118 0z"/></svg>
                        Old chats
                    </button>
                    <button type="button" id="aiFloatNewChat">
                        <svg class="w-4 h-4" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" d="M12 4v16m8-8H4"/></svg>
                        New session
                    </button>
                    <button type="button" id="aiFloatToTask">
                        <svg class="w-4 h-4" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" d="M9 5H7a2 2 0 00-2 2v12a2 2 0 002 2h10a2 2 0 002-2V7a2 2 0 00-2-2h-2M9 5a2 2 0 002 2h2a2 2 0 002-2M9 5a2 2 0 012-2h2a2 2 0 012 2m-6 9l2 2 4-4"/></svg>
                        Attach to a task
                    </button>
                    <button type="button" id="aiFloatToNote">
                        <svg class="w-4 h-4" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" d="M11 5H6a2 2 0 00-2 2v11a2 2 0 002 2h11a2 2 0 002-2v-5m-1.4-9.4a2 2 0 112.8 2.8L11 15l-4 1 1-4 8.6-8.4z"/></svg>
                        Save as a new note
                    </button>
                </div>
                {{-- Which task: rendered with the page, shown on ask. --}}
                <div id="aiFloatTaskMenu" class="ai-float-attmenu is-belowright is-tall hidden">
                    @forelse ($aiFloatTasks as $t)
                        <button type="button" data-task="{{ $t->id }}">
                            <svg class="w-4 h-4" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" d="M9 12l2 2 4-4m6 2a9 9 0 11-18 0 9 9 0 0118 0z"/></svg>
                            <span class="min-w-0"><span class="block truncate">{{ \Illuminate\Support\Str::limit($t->activityTitle ?: 'Task', 34) }}</span>
                            <span class="block text-[0.62rem] text-gray-400">{{ $t->targetDate ? \Illuminate\Support\Carbon::parse($t->targetDate)->format('M j, Y') : 'no set date' }}</span></span>
                        </button>
                    @empty
                        <p class="px-2 py-2 text-xs text-gray-400">No tasks on this schedule yet.</p>
                    @endforelse
                </div>
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

        @unless ($aiFloatUnlimited)
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
        @endunless

        <div class="ai-float-composer">
            <div id="aiFloatChips" class="ai-float-chips hidden"></div>
            <p class="ai-float-est hidden" id="aiFloatEst" aria-live="polite"></p>
            <div id="aiFloatBusy" class="ai-float-busyline hidden" role="status">
                <span class="sp" aria-hidden="true"></span><span class="tx">Attaching photo…</span>
            </div>
            <div class="ai-float-box">
                <div class="relative shrink-0">
                    <button type="button" id="aiFloatAttach" class="ai-float-cam" title="Attach photos" aria-label="Attach photos">
                        <svg class="w-5 h-5" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" d="M3 9a2 2 0 012-2h.93a2 2 0 001.664-.89l.812-1.22A2 2 0 0110.07 4h3.86a2 2 0 011.664.89l.812 1.22A2 2 0 0018.07 7H19a2 2 0 012 2v9a2 2 0 01-2 2H5a2 2 0 01-2-2V9z"/><path stroke-linecap="round" stroke-linejoin="round" d="M15 13a3 3 0 11-6 0 3 3 0 016 0z"/></svg>
                    </button>
                    {{-- The same three doors the full pages offer, popover-sized
                         because the float is: a bottom sheet under a floating
                         panel would bury the panel it serves. --}}
                    <div id="aiFloatAttachMenu" class="ai-float-attmenu hidden">
                        <button type="button" id="aiFloatAttUpload">
                            <svg class="w-4 h-4" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" d="M4 16v2a2 2 0 002 2h12a2 2 0 002-2v-2M12 4v12m0-12L7 9m5-5l5 5"/></svg>
                            Upload photos
                        </button>
                        <button type="button" id="aiFloatAttCamera">
                            <svg class="w-4 h-4" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" d="M3 9a2 2 0 012-2h.93a2 2 0 001.664-.89l.812-1.22A2 2 0 0110.07 4h3.86a2 2 0 011.664.89l.812 1.22A2 2 0 0018.07 7H19a2 2 0 012 2v9a2 2 0 01-2 2H5a2 2 0 01-2-2V9z"/><path stroke-linecap="round" stroke-linejoin="round" d="M15 13a3 3 0 11-6 0 3 3 0 016 0z"/></svg>
                            Take a photo
                        </button>
                        <button type="button" id="aiFloatAttGallery" class="hidden">
                            <svg class="w-4 h-4" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" d="M4 5h16v14H4V5z"/><path stroke-linecap="round" stroke-linejoin="round" d="M4 15l4-4 4 4 3-3 5 5"/></svg>
                            From the gallery
                        </button>
                    </div>
                    <input type="file" id="aiFloatPhotoFiles" accept="image/*" multiple class="hidden">
                    <input type="file" id="aiFloatPhotoCam" accept="image/*" capture="environment" class="hidden">
                </div>
                <textarea id="aiFloatText" rows="1" maxlength="4000" placeholder="Ask about your crop…"></textarea>
                <button type="button" id="aiFloatSend" class="ai-float-send" aria-label="Send">
                    <svg class="w-4 h-4" fill="none" stroke="currentColor" stroke-width="2.2" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" d="M5 12h14m0 0l-6-6m6 6l-6 6"/></svg>
                </button>
            </div>
        </div>
    </div>
</div>

{{-- The float promises a gallery door, so it brings the picker along rather
     than hoping the page happened to. Outside the panel on purpose: the
     panel animates with transforms, and a transform ancestor traps the
     sheet's position:fixed. @once — pages already carrying it are unchanged. --}}
@include('sm.partials.media-picker')

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
    /* A whispered clock, not a shout. */
    .ai-float-msg .b .when { display: block; font-size: .6rem; font-weight: 600; opacity: .55; margin-top: .25rem; text-align: right; font-variant-numeric: tabular-nums; }

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
    .ai-float-chips { display: flex; flex-wrap: wrap; gap: .35rem; margin-bottom: .4rem; }
    .ai-float-est { font-size: .66rem; font-weight: 700; color: var(--color-gray-400); margin-bottom: .3rem; }
    .ai-float-est.hidden { display: none; }
    .ai-float-busyline { display: flex; align-items: center; gap: .35rem; margin-bottom: .35rem;
        font-size: .68rem; font-weight: 700; color: var(--color-brand-700); }
    .ai-float-busyline.hidden { display: none; }
    .ai-float-busyline .sp { width: .7rem; height: .7rem; border-radius: 999px; flex-shrink: 0;
        border: 2px solid var(--color-brand-200); border-top-color: var(--color-brand-600);
        animation: aiFloatSpin .8s linear infinite; }
    .ai-float-chip.is-busy { background: linear-gradient(100deg, var(--color-gray-100) 40%, var(--color-gray-200) 50%, var(--color-gray-100) 60%);
        background-size: 200% 100%; animation: aiFloatChipShimmer 1.2s linear infinite; }
    @keyframes aiFloatChipShimmer { to { background-position: -200% 0; } }
    .ai-float-chips.hidden { display: none; }
    .ai-float-chip { position: relative; width: 2.6rem; height: 2.6rem; border-radius: .6rem; overflow: hidden;
        box-shadow: 0 0 0 2px var(--color-brand-200); animation: aiFloatChipIn .28s cubic-bezier(.22,1,.36,1); }
    .ai-float-chip img { width: 100%; height: 100%; object-fit: cover; display: block; }
    .ai-float-chip .x { position: absolute; top: 1px; right: 1px; width: 1.1rem; height: 1.1rem; border-radius: 999px;
        display: flex; align-items: center; justify-content: center; background: rgb(0 0 0 / .55); color: #fff;
        font-size: .7rem; line-height: 1; border: none; cursor: pointer; }
    /* Mid-upload the picture is a promise, not a path — say so, and hold the
       remove until there is something on the server to remove. */
    .ai-float-chip.is-busy img { opacity: .45; }
    .ai-float-chip.is-busy .x { display: none; }
    .ai-float-chip.is-busy::after { content: ''; position: absolute; inset: 0; margin: auto; width: 1rem; height: 1rem;
        border: 2px solid rgb(255 255 255 / .5); border-top-color: #fff; border-radius: 999px; animation: aiFloatSpin .8s linear infinite; }
    @keyframes aiFloatChipIn { from { opacity: 0; transform: scale(.7); } to { opacity: 1; transform: scale(1); } }
    @keyframes aiFloatSpin { to { transform: rotate(360deg); } }
    .ai-float-attmenu { position: absolute; bottom: calc(100% + .4rem); left: 0; z-index: 5; min-width: 11.5rem;
        background: var(--color-white); border: 1px solid var(--color-gray-200); border-radius: .8rem;
        box-shadow: 0 10px 28px rgb(0 0 0 / .16); padding: .3rem; display: flex; flex-direction: column;
        transform-origin: bottom left; animation: aiFloatMenuIn .28s cubic-bezier(.22,1,.36,1); }
    .ai-float-attmenu.hidden { display: none; }
    .ai-float-attmenu button { display: flex; align-items: center; gap: .5rem; width: 100%; padding: .5rem .6rem;
        border: none; background: none; border-radius: .55rem; font-size: .8rem; font-weight: 600;
        color: var(--color-gray-700); cursor: pointer; text-align: left; }
    .ai-float-attmenu button:hover { background: var(--color-brand-50); color: var(--color-brand-700); }
    .ai-float-attmenu button.hidden { display: none; }
    .ai-float-attmenu button svg { color: var(--color-brand-600); flex-shrink: 0; }
    @keyframes aiFloatMenuIn { from { opacity: 0; transform: scale(.9) translateY(4px); } to { opacity: 1; transform: none; } }
    /* The header's menus hang below their button, right-aligned — above the
       composer's, which grows upward. */
    .ai-float-attmenu.is-belowright { bottom: auto; top: calc(100% + .4rem); left: auto; right: 0; transform-origin: top right; }
    .ai-float-attmenu.is-tall { max-height: 14rem; overflow-y: auto; }
    /* An old chat offered as a row: title above, its age whispered under. */
    .ai-float-convo { display: flex; flex-direction: column; gap: .1rem; width: 100%; padding: .55rem .7rem;
        border: 1px solid var(--color-gray-200); border-radius: .8rem; background: var(--color-white);
        text-align: left; cursor: pointer; margin-bottom: .4rem; }
    .ai-float-convo:hover { border-color: var(--color-brand-300); background: var(--color-brand-50); }
    .ai-float-convo .t { font-size: .82rem; font-weight: 700; color: var(--color-gray-800);
        overflow: hidden; text-overflow: ellipsis; white-space: nowrap; }
    .ai-float-convo .w { font-size: .68rem; color: var(--color-gray-400); }
    /* Replayed history arrives settled — entrance animations are for news. */
    .ai-float-thread.is-replay .ai-float-msg { animation: none; }
    .ai-float-box { display: flex; align-items: flex-end; gap: .25rem; border: 1.5px solid var(--color-gray-200); border-radius: 1.1rem; padding: .25rem .25rem .25rem .4rem; background: var(--color-white); transition: border-color .15s ease, box-shadow .15s ease; }
    .ai-float-box:focus-within { border-color: var(--color-brand-500); box-shadow: 0 0 0 3px rgb(107 159 61 / .18); }
    .ai-float-cam { width: 2.25rem; height: 2.25rem; border-radius: .75rem; display: flex; align-items: center; justify-content: center; flex-shrink: 0; background: var(--color-brand-50); color: var(--color-brand-700); cursor: pointer; transition: background .15s ease; }
    .ai-float-cam:hover { background: var(--color-brand-100); }
    #aiFloatText { resize: none; border: 0; outline: none; background: transparent; flex: 1 1 auto; max-height: 6rem; padding: .4rem .25rem; font-size: .95rem; color: inherit; }
    .ai-float-send { width: 2.25rem; height: 2.25rem; border-radius: 999px; background: linear-gradient(140deg, #6b9f3d, #3d6823); color: #fff; display: flex; align-items: center; justify-content: center; flex-shrink: 0; box-shadow: 0 2px 8px -2px rgb(45 80 22 / .5); transition: transform .15s ease, opacity .15s ease; }
    .ai-float-send:hover:not(:disabled) { transform: scale(1.06); }
    .ai-float-send:active:not(:disabled) { transform: scale(.92); }
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
        .ai-float-fab, .ai-float-msg, .ai-float-panel, .ai-float-head,
        .ai-float-chip, .ai-float-attmenu { animation: none; }
        .ai-float-sug, .ai-float-box, .ai-float-send { transition: none; }
        /* Slowed, not stopped — the pulse is the message that work is happening. */
        .ai-float-dots i { animation-duration: 1.8s; }
        .ai-float-busyline .sp { animation-duration: 1.6s; }
        .ai-float-chip.is-busy { animation: none; }
        .ai-float-chip.is-busy::after { animation-duration: 1.6s; }
    }

    /* ---- Green identity ----
       The panel wore the page's white-on-white; against a busy schedule board
       it disappeared. The header takes the technician's green (same gradient
       as the fab and send button) and the border rings the panel in it, so
       the chat reads as one thing at a glance. Later in the sheet than the
       rules it overrides, deliberately. */
    .ai-float-panel { border: 2px solid #4a7c2a; }
    /* Kept slowly on the move (gradSweep tide, layout) so the open chat reads
       as live — the same drifting header language the messenger wears. */
    .ai-float-head { background: linear-gradient(120deg, #3d6823, #6b9f3d 35%, #4a7c2a 60%, #2f5219 85%, #3d6823);
        background-size: 240% 240%; animation: gradSweep 12s ease-in-out infinite alternate;
        border-bottom-color: transparent; }
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
            attach: @json(route('ai.photo.existing')),
            credits: @json(route('ai.credits')),
            convos: @json(route('ai.conversations')),
            transcript: @json(route('ai.transcript')),
            toNote: @json(route('ai.conversation.note')),
        };
        const UNLIMITED = @json($aiFloatUnlimited);
        @php
        // Precomputed: @json splits on commas (value, flags, depth) and an
        // inline array literal compiles to truncated, unparseable PHP.
        $aiPriceCard = ['inK' => (float) $aiFloatSettings->creditsPerInputK, 'outK' => (float) $aiFloatSettings->creditsPerOutputK, 'img' => (float) $aiFloatSettings->creditsPerImage, 'halfOut' => (int) $aiFloatSettings->maxOutputTokens / 2];
    @endphp
    const PRICE = @json($aiPriceCard);
        // The bill quoted before it is run up — the server's pre-flight
        // estimate, mirrored, repriced on every keystroke and chip.
        const sayEstimate = () => {
            const el = $('aiFloatEst');
            if (!el) return;
            const msg = ($('aiFloatText')?.value || '').trim();
            const shots = chips.children.length;
            if (!msg && !shots) { el.classList.add('hidden'); return; }
            const tin = Math.ceil(msg.length / 4) + 900;
            const cost = Math.max(.01, Math.round((tin / 1000 * PRICE.inK + PRICE.halfOut / 1000 * PRICE.outK + shots * PRICE.img) * 100) / 100);
            el.textContent = `≈ ${cost} credits for this question`;
            el.classList.remove('hidden');
        };
        const SCHEDULE_ID = @json($schedule->id);
        const AVATAR = @json($aiFloatAvatar);
        const MY = @json(auth()->user()->initials ?? '');
        const BOT = '<svg class="w-4 h-4" fill="none" stroke="currentColor" stroke-width="1.8" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" d="M12 3v2m0 0a7 7 0 017 7v3a3 3 0 01-3 3H8a3 3 0 01-3-3v-3a7 7 0 017-7z"/></svg>';
        const COIN = '<svg class="w-4 h-4" viewBox="0 0 20 20" fill="currentColor"><path d="M10 2a8 8 0 100 16 8 8 0 000-16zm.75 4.5v.63a2.5 2.5 0 01.2 4.84v.78a.75.75 0 01-1.5 0v-.75a2.6 2.6 0 01-1.83-1.1.75.75 0 011.24-.84c.24.35.63.57 1.09.57.6 0 1.05-.36 1.05-.83 0-.44-.3-.7-1.2-.95-1.13-.32-2.05-.8-2.05-2.05a2.2 2.2 0 011.5-2.03V6.5a.75.75 0 011.5 0z"/></svg>';
        const buyCard = (msg) => `<div class="ai-buyc"><span class="ico">${COIN}</span><div><h3>You're out of AI Credits</h3><p>${escapeHtml(msg)}</p><a class="btn btn-accent btn-sm mt-2" href="${escapeHtml(URLS.credits)}">Purchase AI credits</a></div></div>`;
        let conversationId = null, busy = false, uploadsBusy = 0;
        const sayBusy = () => {
            const line = $('aiFloatBusy');
            if (!line) return;
            line.classList.toggle('hidden', uploadsBusy === 0);
            line.querySelector('.tx').textContent = uploadsBusy > 1 ? `Attaching ${uploadsBusy} photos…` : 'Attaching photo…';
        };
        const MAX_SHOTS = 6;

        const face = (me) => me ? escapeHtml(MY) : (AVATAR ? `<img src="${escapeHtml(AVATAR)}" alt="">` : BOT);
        const scrollDown = () => { thread.scrollTop = thread.scrollHeight; };
        const nowStamp = () => new Date().toLocaleTimeString([], { hour: 'numeric', minute: '2-digit' });

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
        function addTurn(me, html, imageUrls, cost, stamped) {
            $('aiFloatWelcome')?.remove();
            const el = document.createElement('div');
            el.className = 'ai-float-msg' + (me ? ' me' : '');
            const shots = (Array.isArray(imageUrls) ? imageUrls : (imageUrls ? [imageUrls] : []))
                .map((u) => `<img src="${escapeHtml(u)}" alt="">`).join('');
            el.innerHTML = `<span class="ai-float-face">${face(me)}</span><div class="b">${html}${shots}${cost ? `<p class="cost">${escapeHtml(cost)}</p>` : ''}${stamped ? `<time class="when">${escapeHtml(nowStamp())}</time>` : ''}</div>`;
            thread.appendChild(el); scrollDown(); return el;
        }
        function setBalance(v) {
            const el = $('aiFloatBalance'); if (el) el.textContent = String(Math.round(v * 100) / 100);
            // A failed send already answers with a purchase card in the thread,
            // right where the user is looking — the banner on top of it said
            // the same thing twice. It still covers opening the chat when the
            // credits were already gone, where no card exists yet. (The banner
            // is not rendered at all for accounts that ride free.)
            const saidInChat = !!thread.querySelector('.is-buy');
            $('aiFloatNoCredits')?.classList.toggle('hidden', v > 0 || saidInChat);
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
        // Delegated, because "new session" re-inserts the welcome markup and
        // fresh nodes would arrive deaf to listeners bound at init.
        panel.addEventListener('click', (e) => {
            const b = e.target.closest('.js-float-suggest');
            if (!b) return;
            input.value = (b.querySelector('.t')?.textContent || b.textContent).trim();
            input.dispatchEvent(new Event('input')); input.focus();
        });
        input?.addEventListener('input', () => { input.style.height = 'auto'; input.style.height = Math.min(input.scrollHeight, 96) + 'px';  sayEstimate(); });
        input?.addEventListener('keydown', (e) => {
            if (e.key === 'Enter' && !e.shiftKey && window.matchMedia('(min-width: 768px)').matches) { e.preventDefault(); send(); }
        });

        /* ---- Attached photos: a strip of chips, up to six, from any of the
                three doors the full pages offer. Same contract too — a chip
                without data-path is still uploading and cannot be sent. ---- */
        const chips = $('aiFloatChips');
        const chipCount = () => chips.children.length;
        const roomForAnother = () => {
            if (chipCount() < MAX_SHOTS) return true;
            toast(`Up to ${MAX_SHOTS} photos per question.`, 'error');
            return false;
        };
        const syncChips = () => chips.classList.toggle('hidden', chipCount() === 0);
        const attachedPaths = () => [...chips.children].map((c) => c.dataset.path).filter(Boolean);
        const attachedScheds = () => [...chips.children].filter((c) => c.dataset.path)
            .map((c) => c.dataset.sched ? parseInt(c.dataset.sched, 10) : null);
        const attachedUrls = () => [...chips.children].map((c) => c.querySelector('img')?.src).filter(Boolean);
        function addChip(url) {
            const chip = document.createElement('span');
            chip.className = 'ai-float-chip is-busy';
            chip.innerHTML = `<img src="${escapeHtml(url)}" alt=""><button type="button" class="x" aria-label="Remove photo">✕</button>`;
            chip.querySelector('.x').addEventListener('click', () => dropChip(chip));
            chips.appendChild(chip); syncChips(); sayEstimate();
            return chip;
        }
        function dropChip(chip) {
            if (chip._blob) URL.revokeObjectURL(chip._blob);
            chip.remove(); syncChips(); sayEstimate();
        }
        function clearPhotos() { [...chips.children].forEach(dropChip); }
        function uploadOne(file) {
            if (!file || !(file.type || '').startsWith('image/')) return;
            if (!roomForAnother()) return;
            const preview = URL.createObjectURL(file);
            const chip = addChip(preview);
            chip._blob = preview;
            uploadsBusy++; sayBusy();
            const form = new FormData(); form.append('image', file);
            api(URLS.photo, { method: 'POST', body: form })
                .then((res) => { chip.dataset.path = res.data.path; chip.classList.remove('is-busy'); })
                .catch((err) => { toast(err.message, 'error'); dropChip(chip); })
                .finally(() => { uploadsBusy--; sayBusy(); });
        }
        $('aiFloatPhotoFiles')?.addEventListener('change', (e) => { [...(e.target.files || [])].forEach(uploadOne); e.target.value = ''; });
        $('aiFloatPhotoCam')?.addEventListener('change', (e) => { [...(e.target.files || [])].forEach(uploadOne); e.target.value = ''; });

        /* The chooser popover. The gallery door opens only where the season
           picker travelled with the page — the messenger's rule. */
        const attMenu = $('aiFloatAttachMenu');
        const canGallery = () => typeof window.smPickMedia === 'function' && SCHEDULE_ID > 0;
        const closeMenu = () => attMenu?.classList.add('hidden');
        $('aiFloatAttach')?.addEventListener('click', (e) => {
            e.stopPropagation();
            $('aiFloatAttGallery')?.classList.toggle('hidden', !canGallery());
            attMenu?.classList.toggle('hidden');
        });
        document.addEventListener('click', (e) => { if (attMenu && !attMenu.classList.contains('hidden') && !attMenu.contains(e.target)) closeMenu(); });

        /* ---- The session menu: the past, a fresh start, two filings. ---- */
        const WELCOME_HTML = thread.innerHTML;   // the welcome, kept for "new session"
        const sessMenu = $('aiFloatMenu'), taskMenu = $('aiFloatTaskMenu');
        const closeSessMenus = () => { sessMenu?.classList.add('hidden'); taskMenu?.classList.add('hidden'); };
        $('aiFloatMenuBtn')?.addEventListener('click', (e) => {
            e.stopPropagation();
            taskMenu?.classList.add('hidden');
            sessMenu?.classList.toggle('hidden');
        });
        document.addEventListener('click', (e) => {
            if (sessMenu && !sessMenu.classList.contains('hidden') && !sessMenu.contains(e.target)) sessMenu.classList.add('hidden');
            if (taskMenu && !taskMenu.classList.contains('hidden') && !taskMenu.contains(e.target)) taskMenu.classList.add('hidden');
        });

        $('aiFloatNewChat')?.addEventListener('click', () => {
            closeSessMenus();
            conversationId = null;
            clearPhotos();
            thread.innerHTML = WELCOME_HTML;
            window.toast?.('Fresh session — ask away.');
        });

        $('aiFloatOldChats')?.addEventListener('click', async () => {
            closeSessMenus();
            thread.innerHTML = '<span class="ai-float-dots" style="margin:1rem auto"><i></i><i></i><i></i></span>';
            try {
                const res = await api(URLS.convos);
                const rows = (res.data && res.data.conversations) || [];
                if (!rows.length) { thread.innerHTML = WELCOME_HTML; toast('No past chats yet.'); return; }
                thread.innerHTML = '<p class="text-xs font-bold text-gray-400 mb-2" style="text-transform:uppercase;letter-spacing:.04em">Old chats — pick one to continue</p>'
                    + rows.map((c) => `<button type="button" class="ai-float-convo" data-convo="${c.id}"><span class="t">${escapeHtml(c.title)}</span><span class="w">${escapeHtml(c.when || '')}</span></button>`).join('');
            } catch (err) { thread.innerHTML = WELCOME_HTML; toast(err.message, 'error'); }
        });

        thread.addEventListener('click', async (e) => {
            const row = e.target.closest('.ai-float-convo');
            if (!row) return;
            thread.innerHTML = '<span class="ai-float-dots" style="margin:1rem auto"><i></i><i></i><i></i></span>';
            try {
                const res = await api(URLS.transcript + '?conversationId=' + row.dataset.convo);
                conversationId = res.data.conversationId;
                thread.innerHTML = '';
                // Replayed history arrives settled — entrances are for news.
                thread.classList.add('is-replay');
                (res.data.messages || []).forEach((m) => addTurn(m.role === 'user',
                    m.role === 'user' ? '<p>' + escapeHtml(m.content).replace(/\r?\n/g, '<br>') + '</p>' : render(m.content),
                    m.images || [], null, false));
                thread.classList.remove('is-replay');
                scrollDown();
            } catch (err) { thread.innerHTML = WELCOME_HTML; toast(err.message, 'error'); }
        });

        async function fileAway(activityId) {
            if (!conversationId) { toast('Nothing to save yet — ask something first, or open an old chat.', 'error'); return; }
            try {
                const res = await api(URLS.toNote, { method: 'POST', body: { conversationId, scheduleId: SCHEDULE_ID, activityId: activityId || null } });
                toast(res.message || 'Saved.');
            } catch (err) { toast(err.message, 'error'); }
        }
        $('aiFloatToNote')?.addEventListener('click', () => { closeSessMenus(); fileAway(null); });
        $('aiFloatToTask')?.addEventListener('click', (e) => {
            e.stopPropagation();
            if (!conversationId) { closeSessMenus(); toast('Nothing to save yet — ask something first, or open an old chat.', 'error'); return; }
            sessMenu?.classList.add('hidden');
            taskMenu?.classList.remove('hidden');
        });
        taskMenu?.addEventListener('click', (e) => {
            const b = e.target.closest('[data-task]');
            if (!b) return;
            taskMenu.classList.add('hidden');
            fileAway(parseInt(b.dataset.task, 10));
        });
        $('aiFloatAttUpload')?.addEventListener('click', () => { closeMenu(); $('aiFloatPhotoFiles')?.click(); });
        $('aiFloatAttCamera')?.addEventListener('click', () => { closeMenu(); $('aiFloatPhotoCam')?.click(); });
        $('aiFloatAttGallery')?.addEventListener('click', () => {
            closeMenu();
            if (!canGallery()) return;
            window.smPickMedia({
                scheduleId: SCHEDULE_ID,
                kinds: 'image',
                title: 'Attach from the gallery',
                // Several at once - up to the room the strip has left.
                multiple: true,
                max: MAX_SHOTS - chipCount(),
                onPick: (item) => {
                    // A reference, not a copy — the ask endpoint honours the
                    // picker's own list, so the chip lands done, instantly.
                    if (!item || !item.url || !item.path || !roomForAnother()) return;
                    const chip = addChip(item.url);
                    chip.dataset.path = item.path;
                    chip.dataset.sched = String(SCHEDULE_ID);
                    chip.classList.remove('is-busy');
                    sayEstimate();
                },
            });
        });

        /* "Ask the AI about this", from a picture the app is already showing.
         *
         * The lightbox has copied it into this user's own AI folder by the
         * time it gets here, so all that is left is to hang it on the
         * composer and open it. This bubble follows the reader around the
         * schedule pages, which is why the hook lives here as well as in the
         * Collab Room's tab — whichever is on the page answers. */
        window.smAskAiAbout = function (item) {
            if (!item || !item.path || !roomForAnother()) return;
            // Already copied into this user's own AI folder — a done chip.
            const chip = addChip(item.url);
            chip.dataset.path = item.path;
            chip.classList.remove('is-busy');
            openPanel(true);
            window.smFocus?.($('aiFloatText'), { delay: 160 });
            window.toast?.('Photo attached — what would you like to ask about it?');
        };

        async function send() {
            if (busy) return;
            if (uploadsBusy > 0) { toast('Wait a moment — a photo is still uploading.', 'error'); return; }
            const message = (input.value || '').trim();
            if (!message) { toast('Type a question first.', 'error'); return; }
            busy = true;
            const sendBtn = $('aiFloatSend');
            sendBtn.disabled = true; sendBtn.setAttribute('aria-label', 'Sending');
            const myPaths = attachedPaths();
            addTurn(true, '<p>' + escapeHtml(message).replace(/\r?\n/g, '<br>') + '</p>', attachedUrls(), null, true);
            input.value = ''; input.style.height = 'auto'; sayEstimate();
            const thinking = addTurn(false, '<span class="ai-float-dots"><i></i><i></i><i></i></span>');
            try {
                const res = await api(URLS.ask, { method: 'POST', body: { message, conversationId, imagePaths: myPaths, imageScheduleIds: attachedScheds(), scheduleId: SCHEDULE_ID } });
                conversationId = res.data.conversationId;
                // Chips leave the moment the send is known good.
                clearPhotos();
                const costLine = UNLIMITED ? '' : `<p class="cost">${escapeHtml(String(Math.round(res.data.answer.creditsCharged * 100) / 100))} credits</p>`;
                thinking.querySelector('.b').innerHTML = render(res.data.answer.content) + costLine + `<time class="when">${escapeHtml(nowStamp())}</time>`;
                setBalance(res.data.balance); scrollDown();
            } catch (err) {
                thinking.remove();
                if (err.data && err.data.outOfCredits) {
                    // Card first: setBalance keeps the banner down when the
                    // thread already carries the purchase card.
                    addTurn(false, buyCard(err.message)).querySelector('.b').classList.add('is-buy');
                    setBalance(err.data.balance || 0);
                } else { addTurn(false, '<p>' + escapeHtml(err.message) + '</p>'); }
                // Kept on purpose - said out loud so a failed send never reads
                // as "sent but not cleared".
                if (chipCount()) toast('Your photos are still attached, ready for the retry.');
                input.value = message; input.dispatchEvent(new Event('input'));
            } finally { busy = false; sendBtn.disabled = false; sendBtn.setAttribute('aria-label', 'Send'); input.focus(); }
        }
        $('aiFloatSend')?.addEventListener('click', send);
    };
    if (document.readyState === 'loading') document.addEventListener('DOMContentLoaded', init, { once: true });
    else init();
})();
</script>
@endif
