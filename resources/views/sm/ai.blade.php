@extends(request()->boolean('partial') ? 'layouts.partial' : 'layouts.app')

@section('title', 'AI Technician — ' . $schedule->title)
@section('page-title', 'AI Technician')
@section('page-subtitle', $schedule->title)
@section('back', route('sm.hub', ['id' => $schedule->id]))

@push('head')
    <style>
        /* Real-chat layout: fixed height column, own scroll, pinned composer. */
        .aichat { display: flex; flex-direction: column; height: calc(100dvh - 12.5rem); min-height: 24rem; }
        .aichat-thread { flex: 1 1 auto; overflow-y: auto; padding: .25rem .1rem 1rem; scroll-behavior: smooth; }
        .aichat-day { text-align: center; margin: .5rem 0; }
        .aichat-day span { font-size: 11px; font-weight: 700; color: var(--tl-text-faint, #9ca3af); background: var(--color-gray-100); padding: .15rem .7rem; border-radius: 999px; }

        .aimsg { display: flex; gap: .55rem; margin-bottom: .7rem; align-items: flex-end; }
        .aimsg.me { flex-direction: row-reverse; }
        .aimsg-face {
            width: 1.9rem; height: 1.9rem; border-radius: 999px; flex-shrink: 0; overflow: hidden;
            display: flex; align-items: center; justify-content: center; margin-bottom: .1rem;
            background: var(--color-brand-50); color: var(--color-brand-700); font-size: .62rem; font-weight: 800;
        }
        .aimsg-face img { width: 100%; height: 100%; object-fit: cover; }
        .aimsg.me .aimsg-face { background: var(--color-brand-600); color: #fff; }

        .aibubble {
            max-width: 80%; border-radius: 1.15rem; padding: .55rem .85rem; font-size: .9rem; line-height: 1.5;
            background: var(--color-white); border: 1px solid var(--color-gray-200);
            border-bottom-left-radius: .3rem;
        }
        .aimsg.me .aibubble {
            background: var(--color-brand-600); border-color: var(--color-brand-600); color: #fff;
            border-bottom-left-radius: 1.15rem; border-bottom-right-radius: .3rem;
        }
        .aibubble p { margin: .3rem 0; } .aibubble p:first-child { margin-top: 0; } .aibubble p:last-child { margin-bottom: 0; }
        .aibubble ul { list-style: disc; padding-left: 1.1rem; margin: .3rem 0; }
        .aibubble ol { list-style: decimal; padding-left: 1.3rem; margin: .3rem 0; }
        .aibubble strong { font-weight: 700; }
        .aibubble img { max-width: 100%; max-height: 220px; border-radius: .55rem; margin-top: .35rem; }
        .aibubble-cost { font-size: 10px; font-weight: 700; opacity: .55; margin-top: .35rem; }

        .aichat-composer { flex-shrink: 0; padding-top: .5rem; }
        .aichat-box { border: 1px solid var(--color-gray-200); border-radius: 1.3rem; background: var(--color-white); padding: .35rem .35rem .35rem .5rem; }
        #aiText { resize: none; max-height: 7rem; }

        .aidots { display: inline-flex; gap: .2rem; align-items: center; height: 1.1rem; }
        .aidots i { width: .35rem; height: .35rem; border-radius: 999px; background: currentColor; opacity: .35; animation: aidot 1.1s ease-in-out infinite; }
        .aidots i:nth-child(2) { animation-delay: .18s; } .aidots i:nth-child(3) { animation-delay: .36s; }
        @keyframes aidot { 0%,60%,100% { opacity:.25; transform: translateY(0); } 30% { opacity:.9; transform: translateY(-2px); } }

        .aisuggest { border: 1px dashed var(--color-gray-300); border-radius: .8rem; padding: .5rem .75rem; font-size: .82rem; font-weight: 600; text-align: left; }
    </style>
@endpush

@section('content')
@include('sm.partials.module-header', ['schedule' => $schedule, 'module' => 'ai'])

<div class="aichat">
    {{-- Bar: identity + balance + history + new --}}
    <div class="flex items-center justify-between gap-2 mb-2">
        <div class="flex items-center gap-2 min-w-0">
            <span class="aimsg-face" style="width:2.1rem;height:2.1rem">
                @if ($settings->avatarPath)
                    <img src="{{ \Illuminate\Support\Facades\Storage::disk('public')->url($settings->avatarPath) }}" alt="">
                @else
                    <svg class="w-4 h-4" fill="none" stroke="currentColor" stroke-width="1.8" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" d="M12 3v2m0 0a7 7 0 017 7v3a3 3 0 01-3 3H8a3 3 0 01-3-3v-3a7 7 0 017-7zM9 12h.01M15 12h.01M9.5 17h5"/></svg>
                @endif
            </span>
            <div class="min-w-0">
                <p class="font-bold text-gray-900 text-sm truncate leading-tight">{{ $settings->assistantName }}</p>
                <p class="text-xs text-gray-500">Crop questions for this plan</p>
            </div>
        </div>
        <div class="flex items-center gap-1.5 shrink-0">
            <a href="{{ route('ai.credits') }}" class="btn btn-white btn-sm" title="AI Credits">
                <svg class="w-4 h-4 text-accent-500" viewBox="0 0 20 20" fill="currentColor"><path d="M10 2a8 8 0 100 16 8 8 0 000-16zm.75 4.5v.63a2.5 2.5 0 01.2 4.84v.78a.75.75 0 01-1.5 0v-.75a2.6 2.6 0 01-1.83-1.1.75.75 0 011.24-.84c.24.35.63.57 1.09.57.6 0 1.05-.36 1.05-.83 0-.44-.3-.7-1.2-.95-1.13-.32-2.05-.8-2.05-2.05a2.2 2.2 0 011.5-2.03V6.5a.75.75 0 011.5 0z"/></svg>
                <span id="aiBalance">{{ rtrim(rtrim(number_format($balance, 2), '0'), '.') }}</span>
            </a>
            <button type="button" class="btn btn-white btn-sm" id="aiNewChatBtn" title="New question">
                <svg class="w-4 h-4" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" d="M12 4v16m8-8H4"/></svg>
            </button>
            <button type="button" class="btn btn-white btn-sm" id="aiHistoryBtn" title="Past questions">
                <svg class="w-4 h-4" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" d="M12 8v4l3 2m6-2a9 9 0 11-18 0 9 9 0 0118 0z"/></svg>
            </button>
        </div>
    </div>

    @unless ($settings->isUsable())
        <div class="card p-4 mb-2 border-l-4 border-accent-500">
            <p class="font-bold text-gray-900">The AI Technician is not switched on yet</p>
            <p class="text-sm text-gray-500 mt-0.5">It will appear here as soon as it is configured.</p>
        </div>
    @endunless

    <div class="card p-4 mb-2 border-l-4 border-accent-500 {{ $balance > 0 ? 'hidden' : '' }}" id="aiNoCredits">
        <p class="font-bold text-gray-900">You have no AI Credits left</p>
        <p class="text-sm text-gray-500 mt-0.5">A question costs about 4 credits, or 7 with a photo.</p>
        <a href="{{ route('ai.credits') }}" class="btn btn-primary btn-sm mt-2">Get AI Credits</a>
    </div>

    {{-- Thread --}}
    <div class="aichat-thread" id="aiThread">
        @forelse ($messages as $m)
            <div class="aimsg {{ $m->role === 'user' ? 'me' : '' }}">
                <span class="aimsg-face">
                    @if ($m->role === 'user')
                        {{ auth()->user()->initials }}
                    @elseif ($settings->avatarPath)
                        <img src="{{ \Illuminate\Support\Facades\Storage::disk('public')->url($settings->avatarPath) }}" alt="">
                    @else
                        <svg class="w-4 h-4" fill="none" stroke="currentColor" stroke-width="1.8" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" d="M12 3v2m0 0a7 7 0 017 7v3a3 3 0 01-3 3H8a3 3 0 01-3-3v-3a7 7 0 017-7zM9 12h.01M15 12h.01M9.5 17h5"/></svg>
                    @endif
                </span>
                <div class="aibubble">
                    {!! \App\Support\AiMarkdown::toHtml($m->content) !!}
                    @if ($m->imagePath)
                        <img src="{{ \Illuminate\Support\Facades\Storage::disk('public')->url($m->imagePath) }}" alt="">
                    @endif
                    @if ($m->role === 'assistant' && (float) $m->creditsCharged > 0)
                        <p class="aibubble-cost">{{ rtrim(rtrim(number_format((float) $m->creditsCharged, 2), '0'), '.') }} credits</p>
                    @endif
                </div>
            </div>
        @empty
            <div class="text-center py-4" id="aiWelcome">
                <span class="aimsg-face mx-auto" style="width:3.25rem;height:3.25rem">
                    @if ($settings->avatarPath)
                        <img src="{{ \Illuminate\Support\Facades\Storage::disk('public')->url($settings->avatarPath) }}" alt="">
                    @else
                        <svg class="w-7 h-7" fill="none" stroke="currentColor" stroke-width="1.6" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" d="M12 3v2m0 0a7 7 0 017 7v3a3 3 0 01-3 3H8a3 3 0 01-3-3v-3a7 7 0 017-7zM9 12h.01M15 12h.01M9.5 17h5"/></svg>
                    @endif
                </span>
                <p class="font-bold text-gray-900 mt-2">Ask about {{ \Illuminate\Support\Str::limit($schedule->cropType ?: 'this crop', 24) }}</p>
                <p class="text-sm text-gray-500 mt-1 max-w-sm mx-auto">Fertiliser rates, pests, water, timing, harvest. Snap a leaf and I'll take a look.</p>
                <div class="grid gap-2 mt-4 max-w-md mx-auto">
                    <button type="button" class="aisuggest js-suggest">My leaves are yellowing at the tips — what should I check?</button>
                    <button type="button" class="aisuggest js-suggest">How much urea per hectare for the first top dressing?</button>
                    <button type="button" class="aisuggest js-suggest">When should I stop irrigating before harvest?</button>
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
        <div class="aichat-box flex items-end gap-1">
            <label class="icon-btn cursor-pointer shrink-0" title="Attach a photo" aria-label="Attach a photo">
                <svg class="w-5 h-5" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" d="M3 9a2 2 0 012-2h.93a2 2 0 001.664-.89l.812-1.22A2 2 0 0110.07 4h3.86a2 2 0 011.664.89l.812 1.22A2 2 0 0018.07 7H19a2 2 0 012 2v9a2 2 0 01-2 2H5a2 2 0 01-2-2V9z"/><path stroke-linecap="round" stroke-linejoin="round" d="M15 13a3 3 0 11-6 0 3 3 0 016 0z"/></svg>
                <input type="file" id="aiPhoto" accept="image/*" capture="environment" class="hidden">
            </label>
            <textarea id="aiText" rows="1" class="form-textarea border-0! shadow-none! focus:ring-0! p-2 grow bg-transparent!"
                      maxlength="4000" placeholder="Ask about your crop…" {{ $settings->isUsable() ? '' : 'disabled' }}></textarea>
            <button type="button" class="w-9 h-9 rounded-full bg-brand-600 text-white flex items-center justify-center shrink-0 disabled:opacity-40" id="aiSendBtn" {{ $settings->isUsable() ? '' : 'disabled' }} aria-label="Send">
                <svg class="w-4 h-4" fill="none" stroke="currentColor" stroke-width="2.2" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" d="M5 12h14m0 0l-6-6m6 6l-6 6"/></svg>
            </button>
        </div>
    </div>
</div>
@endsection

@push('sheets')
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
    };
    const AVATAR = @json($settings->avatarPath ? \Illuminate\Support\Facades\Storage::disk('public')->url($settings->avatarPath) : null);
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
    document.querySelectorAll('.js-suggest').forEach((b) => b.addEventListener('click', () => { input.value = b.textContent.trim(); input.dispatchEvent(new Event('input')); input.focus(); }));

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
                addTurn(false, '<p>' + escapeHtml(err.message) + '</p><p style="margin-top:.5rem"><a class="btn btn-primary btn-sm" href="' + escapeHtml(@json(route('ai.credits'))) + '">Get AI Credits</a></p>');
            } else { addTurn(false, '<p>' + escapeHtml(err.message) + '</p>'); }
            input.value = message; input.dispatchEvent(new Event('input'));
        } finally { busy = false; byId('aiSendBtn').disabled = false; input.focus(); }
    }
    byId('aiSendBtn')?.addEventListener('click', send);

    /* history + conversations */
    byId('aiHistoryBtn')?.addEventListener('click', () => openSheet('aiHistorySheet'));
    async function startNew() {
        try { const res = await api(URLS.newConvo, { method: 'POST', body: { scheduleId: SCHEDULE_ID } }); location.href = URLS.page + '&c=' + res.data.conversationId; }
        catch (err) { toast(err.message, 'error'); }
    }
    byId('aiNewChatBtn')?.addEventListener('click', startNew);
    byId('aiNewFromSheet')?.addEventListener('click', startNew);

    // Inside the SPA these are real links; let them navigate the shell.
    document.addEventListener('click', async (e) => {
        const del = e.target.closest('.js-ai-del');
        if (del) {
            e.preventDefault();
            const ok = await confirmAction({ title: 'Delete this conversation?', message: 'Its questions and answers are removed.', detail: 'Credits already spent are not refunded.', confirmText: 'Delete' });
            if (!ok) return;
            try { await api(URLS.delConvo(del.dataset.id), { method: 'DELETE' }); del.closest('.flex').remove(); if (String(del.dataset.id) === String(conversationId)) location.href = URLS.page; }
            catch (err) { toast(err.message, 'error'); }
        }
    });

    scrollDown();
};
    if (document.readyState === 'loading') document.addEventListener('DOMContentLoaded', __init, { once: true });
    else __init();
})();
</script>
@endpush
