{{-- Pre-entry picker for the Collab Room: choose which team members join this
     room. Everyone is checked by default; unchecking then "Open" navigates to
     the room with ?members=<ids>. Only rendered when there ARE other members;
     otherwise the Collab Room button stays a plain link. Expects: $schedule. --}}
@php
    $ceMembers = \App\Support\ScheduleTeam::members($schedule);
    $ceMeId = (int) auth()->id();
    $ceOwnerId = (int) $schedule->anisystemUserId;
    $ceOthers = $ceMembers->filter(fn ($m) => (int) $m->id !== $ceMeId)->values();
@endphp
@if ($ceOthers->isNotEmpty())
<div class="ce-modal hidden" id="collabEnterModal" aria-hidden="true">
    <div class="ce-card">
        <div class="ce-head">
            <span class="ce-title">Who's joining the Collab Room?</span>
            <button type="button" class="ce-x" data-ce-close aria-label="Close">
                <svg class="w-5 h-5" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" d="M6 18L18 6M6 6l12 12"/></svg>
            </button>
        </div>
        <p class="ce-hint">Everyone's included by default — uncheck anyone who shouldn't be in this room, then open it.</p>
        <div class="ce-tools">
            <button type="button" class="ce-tool" data-ce-all>Select all</button>
            <button type="button" class="ce-tool" data-ce-none>Clear all</button>
        </div>
        <div class="ce-list">
            @foreach ($ceOthers as $m)
                <label class="ce-item">
                    <input type="checkbox" class="ce-check" value="{{ (int) $m->id }}" checked>
                    <span class="ce-face">
                        @if ($m->avatarPath)<img src="{{ \App\Support\MediaStore::url($m->avatarPath) }}" alt="">@else{{ $m->initials }}@endif
                    </span>
                    <span class="ce-name">{{ $m->full_name }}@if ((int) $m->id === $ceOwnerId)<span class="ce-tag">owner</span>@endif</span>
                    <span class="ce-mark"><svg class="w-3.5 h-3.5" fill="none" stroke="currentColor" stroke-width="3" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" d="M5 13l4 4L19 7"/></svg></span>
                </label>
            @endforeach
        </div>
        {{-- One way in. The ✕ in the corner and a tap on the backdrop both
             already close this, so a third door was taking half the width of
             the only thing anyone came here to press. The running count went
             with it: the ticks say who is coming, and "3 members + you" said
             the same thing again in numbers. --}}
        <div class="ce-actions">
            <button type="button" class="ce-btn primary" data-ce-continue data-url="{{ route('sm.collab', ['id' => $schedule->id]) }}">
                <span class="ce-spin hidden" id="ceSpin"></span>
                <span>Open Collab Room</span>
            </button>
        </div>
    </div>
</div>

<style>
    .ce-modal { position: fixed; inset: 0; z-index: 250; display: flex; align-items: center; justify-content: center; padding: 1rem; background: rgb(17 24 39 / .55); }
    .ce-modal.hidden { display: none; }
    .ce-card { width: min(28rem, 100%); max-height: 88vh; display: flex; flex-direction: column; background: var(--color-white); border-radius: 1rem; padding: 1.1rem 1.2rem 1.1rem; box-shadow: 0 24px 60px rgb(0 0 0 / .4); animation: ceIn .28s cubic-bezier(.22,1,.36,1) both; }
    @keyframes ceIn { from { opacity: 0; transform: translateY(12px) scale(.98); } to { opacity: 1; transform: none; } }
    @keyframes ceSpin { to { transform: rotate(360deg); } }
    .ce-head { display: flex; align-items: center; justify-content: space-between; margin-bottom: .35rem; }
    .ce-title { font-family: var(--font-heading); font-weight: 800; font-size: 1.02rem; color: var(--color-gray-900); }
    .ce-x { width: 2rem; height: 2rem; border-radius: .5rem; display: inline-flex; align-items: center; justify-content: center; color: var(--color-gray-500); }
    .ce-x:hover { background: var(--color-gray-100); }
    .ce-hint { font-size: .8rem; color: var(--color-gray-500); line-height: 1.5; margin-bottom: .7rem; }
    .ce-tools { display: flex; gap: .4rem; margin-bottom: .5rem; }
    .ce-tool { font-size: .72rem; font-weight: 700; color: var(--color-brand-700); background: var(--color-brand-50); padding: .25rem .55rem; border-radius: .5rem; }
    .ce-tool:hover { background: var(--color-brand-100); }
    /* The names live in a box of their own, and the rows stripe.
       A bare column of labels floating on the card gave nothing to run the
       eye along; a banded list does, and the border says where the choosing
       starts and stops. */
    .ce-list { flex: 1 1 auto; overflow-y: auto; display: flex; flex-direction: column;
        border: 1px solid var(--color-gray-200); border-radius: .8rem; overflow-x: hidden;
        background: var(--color-white); scrollbar-width: thin; }
    .ce-item { display: flex; align-items: center; gap: .6rem; padding: .55rem .7rem;
        cursor: pointer; transition: background .15s ease; }
    .ce-item + .ce-item { border-top: 1px solid var(--color-gray-100); }
    .ce-item:nth-child(even) { background: var(--color-gray-50); }
    .ce-item:hover { background: var(--color-brand-50); }
    .ce-check { position: absolute; opacity: 0; width: 0; height: 0; }
    .ce-face { width: 2.1rem; height: 2.1rem; border-radius: 999px; flex-shrink: 0; display: flex; align-items: center; justify-content: center; font-size: .7rem; font-weight: 800; background: var(--color-brand-50); color: var(--color-brand-700); overflow: hidden; }
    .ce-face img { width: 100%; height: 100%; object-fit: cover; }
    .ce-name { flex: 1 1 auto; min-width: 0; font-size: .9rem; font-weight: 600; color: var(--color-gray-800); overflow: hidden; text-overflow: ellipsis; white-space: nowrap; }
    .ce-tag { font-size: .62rem; font-weight: 800; text-transform: uppercase; letter-spacing: .03em; color: var(--color-brand-700); background: var(--color-brand-50); padding: .05rem .35rem; border-radius: 999px; margin-left: .4rem; }
    .ce-mark { width: 1.5rem; height: 1.5rem; border-radius: 999px; flex-shrink: 0; display: flex; align-items: center; justify-content: center; color: transparent; background: var(--color-gray-100); border: 1.5px solid var(--color-gray-200); transition: background .15s ease, color .15s ease, border-color .15s ease; }
    .ce-check:checked + .ce-face + .ce-name + .ce-mark { background: var(--color-brand-600); border-color: var(--color-brand-600); color: #fff; }
    .ce-actions { display: flex; margin-top: .9rem; }
    .ce-btn { display: inline-flex; align-items: center; justify-content: center; gap: .4rem;
        width: 100%; padding: .7rem .9rem; border-radius: .7rem; font-weight: 700; font-size: .9rem; }
    .ce-btn.primary { color: #fff; background: linear-gradient(140deg, #6b9f3d, #3d6823); }
    .ce-btn.primary:disabled { opacity: .65; }
    .ce-spin { width: 1rem; height: 1rem; border-radius: 999px; border: 2px solid rgb(255 255 255 / .4); border-top-color: #fff; animation: ceSpin .7s linear infinite; }
    .ce-spin.hidden { display: none; }
    html.dark .ce-list { background: #151b12; border-color: #2b3a1c; }
    html.dark .ce-item + .ce-item { border-top-color: #2b3a1c; }
    html.dark .ce-item:nth-child(even) { background: rgb(107 159 61 / .07); }
    html.dark .ce-item:hover { background: rgb(107 159 61 / .16); }
    @media (prefers-reduced-motion: reduce) { .ce-card { animation: none; } .ce-spin { animation-duration: 1.4s; } }
</style>

<script>
(() => {
    const modal = document.getElementById('collabEnterModal');
    if (!modal || modal.dataset.bound) return;
    modal.dataset.bound = '1';
    const checks = () => [...modal.querySelectorAll('.ce-check')];
    const open = () => modal.classList.remove('hidden');
    const close = () => modal.classList.add('hidden');

    /* The Collab Room buttons open this picker instead of navigating in.
     *
     * Delegated, not bound one by one at parse time. On the hub this partial
     * is included ABOVE the tile that carries data-collab-open, so the
     * querySelectorAll ran before the button existed and bound to nothing —
     * the tile simply navigated and nobody was ever asked who was joining.
     * A listener on the document cannot be early. */
    document.addEventListener('click', (e) => {
        const trigger = e.target.closest && e.target.closest('[data-collab-open]');
        if (!trigger) return;
        e.preventDefault();
        open();
    });
    modal.querySelectorAll('[data-ce-close]').forEach((b) => b.addEventListener('click', close));
    modal.addEventListener('click', (e) => { if (e.target === modal) close(); });
    document.addEventListener('keydown', (e) => { if (e.key === 'Escape' && !modal.classList.contains('hidden')) close(); });
    modal.querySelector('[data-ce-all]').addEventListener('click', () => { checks().forEach((c) => c.checked = true); });
    modal.querySelector('[data-ce-none]').addEventListener('click', () => { checks().forEach((c) => c.checked = false); });

    modal.querySelector('[data-ce-continue]').addEventListener('click', (e) => {
        const btn = e.currentTarget;
        const ids = checks().filter((c) => c.checked).map((c) => c.value);
        const base = btn.getAttribute('data-url');
        const url = ids.length ? base + (base.includes('?') ? '&' : '?') + 'members=' + encodeURIComponent(ids.join(',')) : base;
        document.getElementById('ceSpin').classList.remove('hidden');
        btn.disabled = true;
        window.location.href = url;
    });
})();
</script>
@endif
