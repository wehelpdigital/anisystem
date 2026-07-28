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
        ? \Illuminate\Support\Facades\Storage::disk('public')->url($aiFloatSettings->avatarPath)
        : null;
@endphp
@if ($aiFloatSettings && $aiFloatSettings->isUsable())
<div id="aiFloat" class="ai-float">
    <button type="button" id="aiFloatFab" class="ai-float-fab" aria-label="Ask the AI Technician" title="Ask the AI Technician">
        @if ($aiFloatAvatar)
            <img src="{{ $aiFloatAvatar }}" alt="">
        @else
            <svg class="w-7 h-7" fill="none" stroke="currentColor" stroke-width="1.8" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" d="M12 3v2m0 0a7 7 0 017 7v3a3 3 0 01-3 3H8a3 3 0 01-3-3v-3a7 7 0 017-7zM9 12h.01M15 12h.01M9.5 17h5"/></svg>
        @endif
    </button>

    <div id="aiFloatPanel" class="ai-float-panel hidden">
        <div class="ai-float-head">
            <span class="ai-float-face">
                @if ($aiFloatAvatar)<img src="{{ $aiFloatAvatar }}" alt="">@else<svg class="w-4 h-4" fill="none" stroke="currentColor" stroke-width="1.8" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" d="M12 3v2m0 0a7 7 0 017 7v3a3 3 0 01-3 3H8a3 3 0 01-3-3v-3a7 7 0 017-7z"/></svg>@endif
            </span>
            <div class="min-w-0 grow">
                <p class="font-bold text-gray-900 text-sm leading-tight truncate">{{ $aiFloatSettings->assistantName }}</p>
                <a href="{{ route('ai.credits') }}" class="text-xs text-gray-500 hover:text-brand-700">
                    <span id="aiFloatBalance">{{ rtrim(rtrim(number_format($aiFloatBalance, 2), '0'), '.') }}</span> credits
                </a>
            </div>
            <a href="{{ route('sm.ai', ['id' => $schedule->id]) }}" class="ai-float-icon" title="Open full chat" aria-label="Open full chat">
                <svg class="w-4 h-4" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" d="M4 8V4m0 0h4M4 4l5 5m11-1V4m0 0h-4m4 0l-5 5M4 16v4m0 0h4m-4 0l5-5m11 5l-5-5m5 5v-4m0 4h-4"/></svg>
            </a>
            <button type="button" id="aiFloatClose" class="ai-float-icon" aria-label="Close">
                <svg class="w-4 h-4" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" d="M6 18L18 6M6 6l12 12"/></svg>
            </button>
        </div>

        <div class="ai-float-thread" id="aiFloatThread">
            <div class="ai-float-welcome" id="aiFloatWelcome">
                <p class="font-semibold text-gray-800">Ask about {{ \Illuminate\Support\Str::limit($schedule->cropType ?: 'this crop', 24) }}</p>
                <p class="text-sm text-gray-500 mt-1">Fertiliser rates, pests, water, timing — or snap a leaf.</p>
            </div>
        </div>

        <div class="ai-float-nocredits hidden" id="aiFloatNoCredits">
            <p class="text-sm font-semibold text-gray-900">You're out of AI credits</p>
            <p class="text-xs text-gray-500 mt-0.5">Purchase AI chat credits to keep asking.</p>
            <a href="{{ route('ai.credits') }}" class="btn btn-primary btn-sm mt-2">Purchase AI credits</a>
        </div>

        <div class="ai-float-composer">
            <div id="aiFloatPhotoChip" class="ai-float-photochip hidden">
                <img src="" alt="" id="aiFloatPhotoThumb"><span class="grow">Photo attached</span>
                <button type="button" id="aiFloatPhotoRemove" class="text-red-600 font-bold">Remove</button>
            </div>
            <div class="ai-float-box">
                <label class="ai-float-icon cursor-pointer shrink-0" title="Attach a photo">
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
    .ai-float { position: fixed; right: 1rem; bottom: 5.5rem; z-index: 60; }
    @media (min-width: 768px) { .ai-float { bottom: 1.25rem; right: 1.25rem; } }
    .ai-float-fab {
        width: 3.5rem; height: 3.5rem; border-radius: 999px; overflow: hidden;
        display: flex; align-items: center; justify-content: center;
        background: var(--color-brand-600); color: #fff; box-shadow: 0 6px 20px rgba(0,0,0,.22);
        transition: transform .15s ease, filter .15s ease;
    }
    .ai-float-fab:hover { filter: brightness(1.05); } .ai-float-fab:active { transform: scale(.95); }
    .ai-float-fab img { width: 100%; height: 100%; object-fit: cover; }

    .ai-float-panel {
        position: absolute; right: 0; bottom: 4.25rem; width: min(24rem, calc(100vw - 2rem));
        height: min(32rem, calc(100dvh - 9rem)); display: flex; flex-direction: column;
        background: var(--color-white); border: 1px solid var(--color-gray-200);
        border-radius: 1.1rem; box-shadow: 0 16px 44px rgba(0,0,0,.24); overflow: hidden;
        animation: aiFloatIn .18s ease both;
    }
    @keyframes aiFloatIn { from { opacity: 0; transform: translateY(10px) scale(.98); } to { opacity: 1; transform: none; } }

    .ai-float-head { display: flex; align-items: center; gap: .5rem; padding: .6rem .75rem; border-bottom: 1px solid var(--color-gray-100); }
    .ai-float-face { width: 2rem; height: 2rem; border-radius: 999px; overflow: hidden; flex-shrink: 0; display: flex; align-items: center; justify-content: center; background: var(--color-brand-50); color: var(--color-brand-700); }
    .ai-float-face img { width: 100%; height: 100%; object-fit: cover; }
    .ai-float-icon { display: inline-flex; align-items: center; justify-content: center; width: 2rem; height: 2rem; border-radius: .55rem; color: #6b7280; flex-shrink: 0; }
    .ai-float-icon:hover { background: var(--color-gray-100); color: #374151; }

    .ai-float-thread { flex: 1 1 auto; overflow-y: auto; padding: .75rem; scroll-behavior: smooth; }
    .ai-float-welcome { text-align: center; padding: 1.25rem .5rem; }
    .ai-float-msg { display: flex; gap: .45rem; margin-bottom: .6rem; align-items: flex-end; }
    .ai-float-msg.me { flex-direction: row-reverse; }
    .ai-float-msg .b { max-width: 84%; border-radius: 1rem; padding: .5rem .75rem; font-size: .92rem; line-height: 1.5; background: var(--color-gray-50); border: 1px solid var(--color-gray-200); border-bottom-left-radius: .3rem; }
    .ai-float-msg.me .b { background: var(--color-brand-600); color: #fff; border-color: var(--color-brand-600); border-bottom-left-radius: 1rem; border-bottom-right-radius: .3rem; }
    .ai-float-msg .b p { margin: .25rem 0; } .ai-float-msg .b p:first-child { margin-top: 0; } .ai-float-msg .b p:last-child { margin-bottom: 0; }
    .ai-float-msg .b ul { list-style: disc; padding-left: 1.1rem; margin: .25rem 0; }
    .ai-float-msg .b ol { list-style: decimal; padding-left: 1.25rem; margin: .25rem 0; }
    .ai-float-msg .b img { max-width: 100%; max-height: 180px; border-radius: .5rem; margin-top: .3rem; }
    .ai-float-msg .b .cost { font-size: 10px; font-weight: 700; opacity: .55; margin-top: .3rem; }

    .ai-float-nocredits { margin: 0 .75rem .5rem; padding: .7rem .8rem; border: 1px solid #f3d9a6; background: #fef7e8; border-radius: .7rem; }
    .ai-float-composer { flex-shrink: 0; padding: .6rem .75rem .75rem; border-top: 1px solid var(--color-gray-100); }
    .ai-float-photochip { display: flex; align-items: center; gap: .4rem; font-size: .72rem; font-weight: 600; color: #6b7280; margin-bottom: .4rem; }
    .ai-float-photochip img { width: 1.9rem; height: 1.9rem; border-radius: .4rem; object-fit: cover; }
    .ai-float-box { display: flex; align-items: flex-end; gap: .25rem; border: 1px solid var(--color-gray-200); border-radius: 1.1rem; padding: .25rem .25rem .25rem .4rem; }
    #aiFloatText { resize: none; border: 0; outline: none; background: transparent; flex: 1 1 auto; max-height: 6rem; padding: .4rem .25rem; font-size: .95rem; color: inherit; }
    .ai-float-send { width: 2.25rem; height: 2.25rem; border-radius: 999px; background: var(--color-brand-600); color: #fff; display: flex; align-items: center; justify-content: center; flex-shrink: 0; }
    .ai-float-send:disabled { opacity: .4; }
    .ai-float-dots { display: inline-flex; gap: .2rem; align-items: center; height: 1rem; }
    .ai-float-dots i { width: .35rem; height: .35rem; border-radius: 999px; background: currentColor; opacity: .35; animation: aidot 1.1s ease-in-out infinite; }
    .ai-float-dots i:nth-child(2) { animation-delay: .18s; } .ai-float-dots i:nth-child(3) { animation-delay: .36s; }
    html.dark .ai-float-panel { background: #171b2e; border-color: #2a3050; }
    html.dark .ai-float-msg .b { background: #1f2440; border-color: #2a3050; color: #d6ddf5; }
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
            $('aiFloatNoCredits').classList.toggle('hidden', v > 0);
        }

        const openPanel = (open) => {
            panel.classList.toggle('hidden', !open);
            if (open) setTimeout(() => $('aiFloatText')?.focus(), 60);
        };
        fab.addEventListener('click', () => openPanel(panel.classList.contains('hidden')));
        $('aiFloatClose')?.addEventListener('click', () => openPanel(false));

        const input = $('aiFloatText');
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
                    setBalance(err.data.balance || 0);
                    addTurn(false, `<p>${escapeHtml(err.message)}</p><p style="margin-top:.4rem"><a class="btn btn-primary btn-sm" href="${escapeHtml(URLS.credits)}">Purchase AI credits</a></p>`);
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
