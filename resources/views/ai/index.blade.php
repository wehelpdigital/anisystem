@extends('layouts.app')

@section('title', $settings->assistantName)
@section('page-title', 'AI Technician')
@section('page-subtitle', 'Crop questions, answered')

@push('head')
    <style>
        .ai-shell { display: flex; flex-direction: column; min-height: calc(100dvh - 14rem); }
        .ai-thread { display: flex; flex-direction: column; gap: 1rem; padding-bottom: 1rem; }

        .ai-turn { display: flex; gap: .65rem; }
        .ai-turn.is-me { flex-direction: row-reverse; }
        .ai-face {
            width: 2.25rem; height: 2.25rem; border-radius: 999px; flex-shrink: 0; overflow: hidden;
            display: flex; align-items: center; justify-content: center;
            background: var(--color-brand-50); color: var(--color-brand-700); font-size: .7rem; font-weight: 800;
        }
        .ai-face img { width: 100%; height: 100%; object-fit: cover; }
        .ai-turn.is-me .ai-face { background: var(--color-brand-600); color: #fff; }

        .ai-bubble {
            max-width: min(46rem, 82%);
            border-radius: 1rem; padding: .7rem .9rem; font-size: .9rem; line-height: 1.55;
            background: var(--tl-surface, #fff); border: 1px solid var(--tl-border, #eef0f3);
        }
        .ai-turn.is-me .ai-bubble {
            background: var(--color-brand-600); border-color: var(--color-brand-600); color: #fff;
        }
        html.dark .ai-bubble { --tl-surface: #191d23; --tl-border: #2c323b; }
        .ai-bubble p { margin: .35rem 0; }
        .ai-bubble p:first-child { margin-top: 0; }
        .ai-bubble p:last-child { margin-bottom: 0; }
        .ai-bubble ul { list-style: disc; padding-left: 1.15rem; margin: .35rem 0; }
        .ai-bubble ol { list-style: decimal; padding-left: 1.35rem; margin: .35rem 0; }
        .ai-bubble li { margin: .15rem 0; }
        .ai-bubble strong { font-weight: 700; }
        .ai-bubble img { max-width: 100%; max-height: 240px; border-radius: .6rem; margin-top: .4rem; }
        .ai-cost { font-size: 10.5px; font-weight: 700; opacity: .6; margin-top: .4rem; }

        /* Composer pinned above the mobile tab bar */
        .ai-composer {
            position: sticky; bottom: 0; z-index: 20;
            background: var(--color-gray-50); padding-top: .6rem;
            padding-bottom: calc(.6rem + env(safe-area-inset-bottom));
        }
        .ai-composer-inner {
            border: 1px solid var(--color-gray-200); border-radius: 1.1rem;
            background: var(--color-white); padding: .5rem;
        }
        #aiInput { resize: none; max-height: 9rem; }

        /* Typing indicator */
        .ai-dots { display: inline-flex; gap: .22rem; align-items: center; height: 1.2rem; }
        .ai-dots i {
            width: .38rem; height: .38rem; border-radius: 999px; background: currentColor; opacity: .35;
            animation: ai-dot 1.1s ease-in-out infinite;
        }
        .ai-dots i:nth-child(2) { animation-delay: .18s; }
        .ai-dots i:nth-child(3) { animation-delay: .36s; }
        @keyframes ai-dot { 0%, 60%, 100% { opacity: .25; transform: translateY(0); } 30% { opacity: .9; transform: translateY(-2px); } }
        @media (prefers-reduced-motion: reduce) { .ai-dots i { animation: none; opacity: .5; } }

        .ai-suggest {
            border: 1px dashed var(--color-gray-300); border-radius: .8rem;
            padding: .55rem .8rem; font-size: .82rem; font-weight: 600; text-align: left;
        }
    </style>
@endpush

@section('content')
<div class="ai-shell">

    {{-- Balance + controls --}}
    <div class="flex items-center justify-between gap-2 mb-4">
        <div class="flex items-center gap-2 min-w-0">
            <span class="ai-face">
                @if ($settings->avatarPath)
                    <img src="{{ \Illuminate\Support\Facades\Storage::disk('public')->url($settings->avatarPath) }}" alt="">
                @else
                    <svg class="w-5 h-5" fill="none" stroke="currentColor" stroke-width="1.8" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" d="M12 3v2m0 0a7 7 0 017 7v3a3 3 0 01-3 3H8a3 3 0 01-3-3v-3a7 7 0 017-7zM9 12h.01M15 12h.01M9.5 17h5"/></svg>
                @endif
            </span>
            <div class="min-w-0">
                <p class="font-bold text-gray-900 text-sm truncate">{{ $settings->assistantName }}</p>
                <p class="text-xs text-gray-500">Crop questions only</p>
            </div>
        </div>
        <div class="flex items-center gap-2 shrink-0">
            <a href="{{ route('ai.credits') }}" class="btn btn-white btn-sm" title="AI Credits">
                <svg class="w-4 h-4 text-accent-500" viewBox="0 0 20 20" fill="currentColor"><path d="M10 2a8 8 0 100 16 8 8 0 000-16zm.75 4.5v.63a2.5 2.5 0 01.2 4.84v.78a.75.75 0 01-1.5 0v-.75a2.6 2.6 0 01-1.83-1.1.75.75 0 011.24-.84c.24.35.63.57 1.09.57.6 0 1.05-.36 1.05-.83 0-.44-.3-.7-1.2-.95-1.13-.32-2.05-.8-2.05-2.05a2.2 2.2 0 011.5-2.03V6.5a.75.75 0 011.5 0z"/></svg>
                <span id="aiBalance">{{ rtrim(rtrim(number_format($balance, 2), '0'), '.') }}</span>
            </a>
            <button type="button" class="btn btn-white btn-sm" id="aiHistoryBtn" title="Past questions">
                <svg class="w-4 h-4" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" d="M4 6h16M4 12h16M4 18h16"/></svg>
            </button>
        </div>
    </div>

    @if (! $settings->isUsable())
        <div class="card p-6 text-center mb-4">
            <svg class="w-12 h-12 mx-auto text-gray-300" fill="none" stroke="currentColor" stroke-width="1.5" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" d="M12 9v3.75m9-.75a9 9 0 11-18 0 9 9 0 0118 0zm-9 3.75h.008v.008H12v-.008z"/></svg>
            <p class="font-semibold text-gray-700 mt-3">The AI Technician is not switched on yet</p>
            <p class="text-sm text-gray-500 mt-1">It will appear here as soon as it is configured.</p>
        </div>
    @endif

    {{-- Out of credits --}}
    <div class="card p-4 mb-4 border-l-4 border-accent-500 {{ $balance > 0 ? 'hidden' : '' }}" id="aiNoCredits">
        <p class="font-bold text-gray-900">You have no AI Credits left</p>
        <p class="text-sm text-gray-500 mt-1">
            Questions cost about 4 credits, or 7 with a photo. Top up to keep asking.
        </p>
        <a href="{{ route('ai.credits') }}" class="btn btn-primary btn-sm mt-3">Get AI Credits</a>
    </div>

    {{-- Thread --}}
    <div class="ai-thread grow" id="aiThread">
        @forelse ($messages as $m)
            <div class="ai-turn {{ $m->role === 'user' ? 'is-me' : '' }}">
                <span class="ai-face">
                    @if ($m->role === 'user')
                        {{ auth()->user()->initials }}
                    @elseif ($settings->avatarPath)
                        <img src="{{ \Illuminate\Support\Facades\Storage::disk('public')->url($settings->avatarPath) }}" alt="">
                    @else
                        <svg class="w-5 h-5" fill="none" stroke="currentColor" stroke-width="1.8" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" d="M12 3v2m0 0a7 7 0 017 7v3a3 3 0 01-3 3H8a3 3 0 01-3-3v-3a7 7 0 017-7zM9 12h.01M15 12h.01M9.5 17h5"/></svg>
                    @endif
                </span>
                <div class="ai-bubble">
                    {!! \App\Support\AiMarkdown::toHtml($m->content) !!}
                    @if ($m->imagePath)
                        <img src="{{ \Illuminate\Support\Facades\Storage::disk('public')->url($m->imagePath) }}" alt="Attached photo">
                    @endif
                    @if ($m->role === 'assistant' && (float) $m->creditsCharged > 0)
                        <p class="ai-cost">{{ rtrim(rtrim(number_format((float) $m->creditsCharged, 2), '0'), '.') }} credits</p>
                    @endif
                </div>
            </div>
        @empty
            <div class="text-center py-6" id="aiWelcome">
                <span class="ai-face mx-auto" style="width:3.5rem;height:3.5rem">
                    @if ($settings->avatarPath)
                        <img src="{{ \Illuminate\Support\Facades\Storage::disk('public')->url($settings->avatarPath) }}" alt="">
                    @else
                        <svg class="w-8 h-8" fill="none" stroke="currentColor" stroke-width="1.6" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" d="M12 3v2m0 0a7 7 0 017 7v3a3 3 0 01-3 3H8a3 3 0 01-3-3v-3a7 7 0 017-7zM9 12h.01M15 12h.01M9.5 17h5"/></svg>
                    @endif
                </span>
                <p class="font-bold text-gray-900 mt-3">Ask me about your crop</p>
                <p class="text-sm text-gray-500 mt-1 max-w-sm mx-auto">
                    Fertiliser rates, pests and diseases, water, timing, harvest and storage.
                    Snap a photo of a problem leaf and I will take a look.
                </p>
                <div class="grid gap-2 mt-5 max-w-md mx-auto">
                    <button type="button" class="ai-suggest js-suggest">My rice leaves are yellowing at the tips 25 days after sowing. What should I check?</button>
                    <button type="button" class="ai-suggest js-suggest">How much urea per hectare for the first top dressing on inbred rice?</button>
                    <button type="button" class="ai-suggest js-suggest">When should I stop irrigating before harvest?</button>
                </div>
            </div>
        @endforelse
    </div>

    {{-- Composer --}}
    <div class="ai-composer">
        <div id="aiPhotoChip" class="hidden mb-2 flex items-center gap-2 text-xs font-semibold text-gray-600">
            <img src="" alt="" class="w-10 h-10 rounded-lg object-cover" id="aiPhotoThumb">
            <span>Photo attached</span>
            <button type="button" class="text-red-600 font-bold" id="aiPhotoRemove">Remove</button>
        </div>
        <div class="ai-composer-inner">
            <textarea id="aiInput" class="form-textarea border-0 shadow-none focus:ring-0 p-2" rows="1"
                      maxlength="4000" placeholder="Ask about your crop…"
                      {{ $settings->isUsable() ? '' : 'disabled' }}></textarea>
            <div class="flex items-center justify-between gap-2 mt-1">
                <div class="flex items-center gap-1">
                    <label class="icon-btn cursor-pointer" title="Attach a photo" aria-label="Attach a photo">
                        <svg class="w-5 h-5" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" d="M3 9a2 2 0 012-2h.93a2 2 0 001.664-.89l.812-1.22A2 2 0 0110.07 4h3.86a2 2 0 011.664.89l.812 1.22A2 2 0 0018.07 7H19a2 2 0 012 2v9a2 2 0 01-2 2H5a2 2 0 01-2-2V9z"/><path stroke-linecap="round" stroke-linejoin="round" d="M15 13a3 3 0 11-6 0 3 3 0 016 0z"/></svg>
                        <input type="file" id="aiPhoto" accept="image/*" capture="environment" class="hidden">
                    </label>
                    @if ($schedules->isNotEmpty())
                        <select id="aiSchedule" class="form-select text-xs py-1.5 pl-2 pr-7 w-auto max-w-40" title="Which plan is this about?">
                            <option value="">No plan</option>
                            @foreach ($schedules as $s)
                                <option value="{{ $s->id }}" @selected($conversation && $conversation->croppingScheduleId == $s->id)>{{ \Illuminate\Support\Str::limit($s->title, 26) }}</option>
                            @endforeach
                        </select>
                    @endif
                </div>
                <button type="button" class="btn btn-primary btn-sm" id="aiSendBtn" {{ $settings->isUsable() ? '' : 'disabled' }}>
                    Ask
                    <svg class="w-4 h-4" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" d="M5 12h14m0 0l-6-6m6 6l-6 6"/></svg>
                </button>
            </div>
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
        <button type="button" class="w-full flex items-center gap-3 rounded-xl px-3 py-3 text-left font-semibold text-brand-700 hover:bg-gray-50" id="aiNewBtn">
            <svg class="w-5 h-5" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" d="M12 4v16m8-8H4"/></svg>
            Start a new question
        </button>
        @foreach ($conversations as $c)
            <div class="flex items-center gap-1">
                <a href="{{ route('ai.index', ['c' => $c->id]) }}"
                   class="grow min-w-0 rounded-xl px-3 py-3 font-semibold text-gray-700 hover:bg-gray-50 {{ $conversation && $conversation->id === $c->id ? 'bg-brand-50 text-brand-700' : '' }}">
                    <span class="block truncate">{{ $c->title }}</span>
                    <span class="block text-xs font-normal text-gray-400">{{ $c->updated_at?->diffForHumans() }}</span>
                </a>
                <button type="button" class="icon-btn text-red-600 shrink-0 js-del-convo" data-id="{{ $c->id }}" aria-label="Delete conversation">
                    <svg class="w-4 h-4" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" d="M19 7l-.867 12.142A2 2 0 0116.138 21H7.862a2 2 0 01-1.995-1.858L5 7m5 4v6m4-6v6m1-10V4a1 1 0 00-1-1h-4a1 1 0 00-1 1v3M4 7h16"/></svg>
                </button>
            </div>
        @endforeach
        @if ($conversations->isEmpty())
            <p class="text-sm text-gray-500 text-center py-6">Nothing yet.</p>
        @endif
    </div>
</div>
@endpush

@push('scripts')
<script>
(() => {
const __init = () => {
    const URLS = {
        ask: @json(route('ai.ask')),
        photo: @json(route('ai.photo')),
        newConvo: @json(route('ai.conversation.new')),
        delConvo: (id) => @json(route('ai.conversation.delete')) + '?id=' + id,
    };
    const AVATAR = @json($settings->avatarPath ? \Illuminate\Support\Facades\Storage::disk('public')->url($settings->avatarPath) : null);
    const MY_INITIALS = @json(auth()->user()->initials);
    const BOT_SVG = '<svg class="w-5 h-5" fill="none" stroke="currentColor" stroke-width="1.8" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" d="M12 3v2m0 0a7 7 0 017 7v3a3 3 0 01-3 3H8a3 3 0 01-3-3v-3a7 7 0 017-7zM9 12h.01M15 12h.01M9.5 17h5"/></svg>';

    let conversationId = @json($conversation->id ?? null);
    let photoPath = null;
    let busy = false;

    const byId = (id) => document.getElementById(id);
    const thread = byId('aiThread');

    const face = (isMe) => isMe
        ? escapeHtml(MY_INITIALS)
        : (AVATAR ? `<img src="${escapeHtml(AVATAR)}" alt="">` : BOT_SVG);

    /**
     * The model answers in light markdown. Everything is escaped first, then a
     * small allow-list of bold / bullets / numbered lists / paragraphs is put
     * back — so nothing the model emits can inject markup.
     */
    function renderAnswer(text) {
        const esc = escapeHtml(text || '');
        const lines = esc.split(/\r?\n/);
        let html = '';
        let list = null;

        const closeList = () => { if (list) { html += `</${list}>`; list = null; } };

        for (const raw of lines) {
            const line = raw.trim();
            if (!line) { closeList(); continue; }

            const bullet = line.match(/^[-*•]\s+(.*)$/);
            const numbered = line.match(/^(\d+)[.)]\s+(.*)$/);

            if (bullet) {
                if (list !== 'ul') { closeList(); html += '<ul>'; list = 'ul'; }
                html += '<li>' + inline(bullet[1]) + '</li>';
            } else if (numbered) {
                if (list !== 'ol') { closeList(); html += '<ol>'; list = 'ol'; }
                html += '<li>' + inline(numbered[2]) + '</li>';
            } else {
                closeList();
                html += '<p>' + inline(line) + '</p>';
            }
        }
        closeList();
        return html || '<p></p>';
    }
    const inline = (s) => s
        .replace(/\*\*([^*]+)\*\*/g, '<strong>$1</strong>')
        .replace(/(^|\s)\*([^*]+)\*(?=\s|$|[.,;:!?])/g, '$1<em>$2</em>');

    function addTurn(isMe, html, imageUrl, costLine) {
        byId('aiWelcome')?.remove();
        const el = document.createElement('div');
        el.className = 'ai-turn' + (isMe ? ' is-me' : '');
        el.innerHTML = `
            <span class="ai-face">${face(isMe)}</span>
            <div class="ai-bubble">
                ${html}
                ${imageUrl ? `<img src="${escapeHtml(imageUrl)}" alt="Attached photo">` : ''}
                ${costLine ? `<p class="ai-cost">${escapeHtml(costLine)}</p>` : ''}
            </div>`;
        thread.appendChild(el);
        el.scrollIntoView({ behavior: 'smooth', block: 'end' });
        return el;
    }

    function setBalance(value) {
        const pretty = String(Math.round(value * 100) / 100);
        byId('aiBalance').textContent = pretty;
        byId('aiNoCredits').classList.toggle('hidden', value > 0);
    }

    // Textarea grows with the message, up to the CSS max-height.
    const input = byId('aiInput');
    input?.addEventListener('input', () => {
        input.style.height = 'auto';
        input.style.height = Math.min(input.scrollHeight, 144) + 'px';
    });
    input?.addEventListener('keydown', (e) => {
        // Enter sends on a desktop keyboard; Shift+Enter always adds a line.
        if (e.key === 'Enter' && !e.shiftKey && window.matchMedia('(min-width: 768px)').matches) {
            e.preventDefault();
            send();
        }
    });

    document.querySelectorAll('.js-suggest').forEach((btn) => {
        btn.addEventListener('click', () => {
            input.value = btn.textContent.trim();
            input.dispatchEvent(new Event('input'));
            input.focus();
        });
    });

    /* ---- Photo ---- */
    byId('aiPhoto')?.addEventListener('change', async (e) => {
        const file = e.target.files && e.target.files[0];
        if (!file) return;
        const form = new FormData();
        form.append('image', file);
        try {
            const res = await api(URLS.photo, { method: 'POST', body: form });
            photoPath = res.data.path;
            byId('aiPhotoThumb').src = res.data.url;
            byId('aiPhotoChip').classList.remove('hidden');
            byId('aiPhotoChip').classList.add('flex');
        } catch (err) {
            toast(err.message, 'error');
        } finally {
            e.target.value = '';
        }
    });
    byId('aiPhotoRemove')?.addEventListener('click', () => {
        photoPath = null;
        byId('aiPhotoChip').classList.add('hidden');
        byId('aiPhotoChip').classList.remove('flex');
    });

    /* ---- Ask ---- */
    async function send() {
        if (busy) return;
        const message = input.value.trim();
        if (!message) { toast('Type a question first.', 'error'); return; }

        busy = true;
        byId('aiSendBtn').disabled = true;
        const myPhoto = photoPath ? byId('aiPhotoThumb').src : null;
        addTurn(true, '<p>' + escapeHtml(message).replace(/\r?\n/g, '<br>') + '</p>', myPhoto);
        input.value = '';
        input.style.height = 'auto';

        const thinking = addTurn(false, '<span class="ai-dots"><i></i><i></i><i></i></span>');

        try {
            const res = await api(URLS.ask, {
                method: 'POST',
                body: {
                    message,
                    conversationId,
                    imagePath: photoPath,
                    scheduleId: byId('aiSchedule')?.value || null,
                },
            });
            conversationId = res.data.conversationId;
            thinking.querySelector('.ai-bubble').innerHTML =
                renderAnswer(res.data.answer.content)
                + `<p class="ai-cost">${escapeHtml(String(Math.round(res.data.answer.creditsCharged * 100) / 100))} credits</p>`;
            setBalance(res.data.balance);
            byId('aiPhotoRemove').click();
        } catch (err) {
            thinking.remove();
            if (err.data && err.data.outOfCredits) {
                setBalance(err.data.balance || 0);
                addTurn(false, '<p>' + escapeHtml(err.message) + '</p>'
                    + `<p style="margin-top:.5rem"><a class="btn btn-primary btn-sm" href="${escapeHtml(@json(route('ai.credits')))}">Get AI Credits</a></p>`);
            } else {
                addTurn(false, '<p>' + escapeHtml(err.message) + '</p>');
            }
            // Give the question back so it is not lost.
            input.value = message;
            input.dispatchEvent(new Event('input'));
        } finally {
            busy = false;
            byId('aiSendBtn').disabled = false;
            input.focus();
        }
    }
    byId('aiSendBtn')?.addEventListener('click', send);

    /* ---- Conversations ---- */
    byId('aiHistoryBtn')?.addEventListener('click', () => openSheet('aiHistorySheet'));
    byId('aiNewBtn')?.addEventListener('click', async () => {
        try {
            const res = await api(URLS.newConvo, { method: 'POST', body: { scheduleId: byId('aiSchedule')?.value || null } });
            window.location.href = '{{ route('ai.index') }}?c=' + res.data.conversationId;
        } catch (err) {
            toast(err.message, 'error');
        }
    });
    document.addEventListener('click', async (e) => {
        const btn = e.target.closest('.js-del-convo');
        if (!btn) return;
        const ok = await confirmAction({
            title: 'Delete this conversation?',
            message: 'The questions and answers in it will be removed.',
            detail: 'Credits already spent are not refunded.',
            confirmText: 'Delete',
        });
        if (!ok) return;
        try {
            await api(URLS.delConvo(btn.dataset.id), { method: 'DELETE' });
            window.location.href = '{{ route('ai.index') }}';
        } catch (err) {
            toast(err.message, 'error');
        }
    });

    thread?.lastElementChild?.scrollIntoView({ block: 'end' });
};
    if (document.readyState === 'loading') document.addEventListener('DOMContentLoaded', __init, { once: true });
    else __init();
})();
</script>
@endpush
