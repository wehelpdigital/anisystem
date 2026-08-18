@extends('layouts.app')

@section('title', 'Discussions — Community')
@section('page-title', 'Community')
@section('page-subtitle', 'Talk crops with other farmers')
@section('back', route('community.index'))

@php use App\Support\CommunityAvatar; @endphp

@push('head')
@include('community.partials.plaza-css')
<style>
    /* The section head is one row wherever the two halves fit: the copy takes
       whatever is left, the button keeps its own width and never squeezes the
       heading into a narrow column beside it. Below that width the button
       wraps to a full-width line instead of a stub in the corner. */
    .disc-head { display:flex; align-items:center; gap:.75rem; flex-wrap:wrap; margin-bottom:1rem; }
    .disc-head-copy { flex:1 1 8rem; min-width:0; }
    .disc-head-title { font-family:var(--font-heading); font-size:1rem; font-weight:800; line-height:1.2;
        color:var(--color-gray-900); }
    .disc-head-sub { font-size:.78rem; line-height:1.35; color:var(--color-gray-500); margin-top:.1rem; }
    .disc-head-btn { flex:0 0 auto; margin-left:auto; }
    @media (max-width:22.4rem) {
        .disc-head-btn { width:100%; margin-left:0; justify-content:center; }
    }

    /* One action per card: Join until you are in, Open once you are. They
       swap in place, so the card never grows or shifts under the thumb. */
    .disc-act { margin-top:auto; }
    .disc-act .btn { width:100%; }
    .disc-act .is-off { display:none; }
    .disc-join { transition:opacity var(--dur) var(--ease-house), transform var(--dur) var(--ease-house); }
    .disc-join.is-going { opacity:0; transform:scale(.96); pointer-events:none; }
    @keyframes discSwap { from { opacity:0; transform:scale(.96); } to { opacity:1; transform:none; } }
    .disc-open.is-arriving { animation:discSwap var(--dur) var(--ease-house); }

    /* The tail of the list: a button, a loader, or the end of the road —
       never two of them at once (the wall's shape, in this page's words). */
    .disc-tail { text-align:center; margin-top:.75rem; padding-bottom:.5rem; }
    .disc-tail[hidden] { display:none; }
    .disc-spin { display:flex; align-items:center; justify-content:center; gap:.35rem; padding:.9rem 0; }
    .disc-spin i { display:block; width:.45rem; height:.45rem; border-radius:9999px;
        background:var(--color-brand-400); animation:discDot 1s cubic-bezier(.22,1,.36,1) infinite; }
    .disc-spin i:nth-child(2) { animation-delay:.12s; }
    .disc-spin i:nth-child(3) { animation-delay:.24s; }
    @keyframes discDot { 0%,100% { opacity:.25; transform:translateY(0); } 50% { opacity:1; transform:translateY(-.25rem); } }
    .disc-end { font-size:.78rem; font-weight:600; color:var(--color-gray-400); padding:1rem 0 .4rem; }
    .disc-spin[hidden], .disc-end[hidden] { display:none; }

    /* Cards past the first page wait off-stage and arrive a page at a time. */
    .disc-card.is-paged-out { display:none; }
    .disc-card.is-paged-in { animation:discSwap .32s var(--ease-house) both; }

    @media (prefers-reduced-motion: reduce) {
        .disc-join, .disc-open.is-arriving, .disc-card.is-paged-in { transition:none; animation:none; }
        /* A loader that stops looks like a page that broke; slow it instead. */
        .disc-spin i { animation-duration:2.6s; }
    }
</style>
@endpush

@section('content')
@include('community.partials.nav', ['active' => 'groups'])

<div class="disc-head">
    <div class="disc-head-copy">
        <h2 class="disc-head-title">Sali ka sa usapan</h2>
        <p class="disc-head-sub">Post questions, share what works.</p>
    </div>
    <button type="button" id="createGroupBtn" class="btn btn-primary btn-sm disc-head-btn">
        <svg class="w-4 h-4" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" d="M12 5v14m-7-7h14"/></svg>
        New Discussion
    </button>
</div>

@if ($groups->isEmpty())
    <div class="card">
        <div class="card-body text-center py-14">
            <div class="empty-tile">👥</div>
            <h2 class="text-lg font-bold text-gray-900 mb-1" style="font-family:var(--font-heading)">Wala pang discussions</h2>
            <p class="text-sm text-gray-500 mb-5">Ikaw ang mag-umpisa — invite kapwa magsasaka to talk shop.</p>
            <button type="button" class="btn btn-primary" onclick="document.getElementById('createGroupBtn').click()">Start the first discussion</button>
        </div>
    </div>
@else
    @php $discPerPage = 8; @endphp
    <div class="grid gap-3 sm:grid-cols-2 stagger-children" id="groupsGrid">
        @foreach ($groups as $i => $g)
            @php $hue = CommunityAvatar::hue($g->name); @endphp
            <div class="card card-hover disc-card flex flex-col overflow-hidden {{ $i >= $discPerPage ? 'is-paged-out' : '' }}" data-group-card="{{ $g->id }}">
                <div class="group-cap {{ $hue }}"></div>
                <div class="card-body flex flex-col grow pt-4!">
                    <div class="flex items-start gap-3 min-w-0">
                        <span class="avatar avatar-md avatar-sq overflow-hidden {{ $hue }}">@if ($g->coverImagePath)<img src="{{ \App\Support\MediaStore::url($g->coverImagePath) }}" alt="" class="w-full h-full object-cover">@else{{ CommunityAvatar::monogram($g->name) }}@endif</span>
                        <a href="{{ route('community.groups.show', ['id' => $g->id]) }}" class="min-w-0 grow">
                            <h3 class="font-bold text-gray-900 leading-snug" style="font-family:var(--font-heading)">{{ $g->name }}
                                <span class="badge badge-green group-joined-tag align-middle {{ $g->joined ? '' : 'hidden' }}" data-group-id="{{ $g->id }}">Joined</span>
                            </h3>
                        </a>
                    </div>
                    @if ($g->description)
                        <p class="text-sm text-gray-500 mt-2 line-clamp-2 min-h-[2.5rem]">{{ $g->description }}</p>
                    @else
                        <p class="mt-2 min-h-[2.5rem]"></p>
                    @endif
                    <div class="flex items-center gap-3 text-xs text-gray-500 font-semibold mt-2 mb-3">
                        <span>🧑‍🌾 {{ $g->member_count }} {{ \Illuminate\Support\Str::plural('member', $g->member_count) }}</span>
                        <span>💬 {{ $g->post_count }} {{ \Illuminate\Support\Str::plural('post', $g->post_count) }}</span>
                    </div>
                    {{-- "Open" is a promise you can only keep for a member; for
                         everyone else the honest word is Join. --}}
                    <div class="disc-act">
                        <a href="{{ route('community.groups.show', ['id' => $g->id]) }}"
                           class="btn btn-primary disc-open {{ $g->joined ? '' : 'is-off' }}">Open</a>
                        <button type="button" class="btn btn-primary disc-join {{ $g->joined ? 'is-off' : '' }}"
                                data-group-id="{{ $g->id }}">Join</button>
                    </div>
                </div>
            </div>
        @endforeach
    </div>

    <div class="disc-tail" id="discTail" @if ($groups->count() <= $discPerPage) hidden @endif>
        <button type="button" id="discMore" class="btn btn-white btn-sm" data-infinite>Show more discussions</button>
        <div class="disc-spin" id="discSpin" role="status" aria-label="Loading more discussions" hidden><i></i><i></i><i></i></div>
        <p class="disc-end" id="discEnd" hidden>🌾 Iyan na ang lahat ng usapan.</p>
    </div>
@endif

{{-- Create group sheet --}}
<div class="sheet hidden" id="createGroupSheet" style="--sheet-width:28rem">
    <div class="sheet-handle"></div>
    <div class="sheet-header">
        <h3 class="sheet-title">New discussion</h3>
        <button type="button" data-sheet-close class="btn-ghost p-2 rounded-full" aria-label="Close">✕</button>
    </div>
    <div class="sheet-body space-y-3">
        <div class="flex flex-col items-center gap-2">
            <label class="cursor-pointer" title="Upload a discussion photo">
                <span class="avatar avatar-lg avatar-sq av-h7 overflow-hidden" id="groupMonogramPreview" style="width:4.5rem;height:4.5rem;">?</span>
                <input type="file" id="groupImage" accept="image/jpeg,image/png,image/webp" class="hidden">
            </label>
            <label for="groupImage" class="text-xs font-semibold text-brand-700 hover:text-brand-800 cursor-pointer">Upload discussion photo</label>
        </div>
        <div>
            <label class="form-label" for="groupName">Discussion name</label>
            <input type="text" id="groupName" class="form-input" maxlength="150" placeholder="e.g. Rice Growers of Central Luzon">
            <p class="form-hint">Tip: pangalanan mo per crop o per lugar — "Palay — Nueva Ecija".</p>
        </div>
        <div>
            <label class="form-label" for="groupDesc">Description <span class="text-gray-400 font-normal">(optional)</span></label>
            <textarea id="groupDesc" class="form-textarea" rows="3" maxlength="500" placeholder="What's this discussion about?"></textarea>
        </div>
    </div>
    <div class="sheet-footer">
        <button type="button" class="btn btn-ghost" data-sheet-close>Cancel</button>
        <button type="button" class="btn btn-primary" id="createGroupSave">Create discussion</button>
    </div>
</div>
@endsection

@push('scripts')
@include('community.partials.infinite-js')
<script>
document.addEventListener('DOMContentLoaded', () => {
    const CSRF = document.querySelector('meta[name=csrf-token]').content;
    const jsonHeaders = { 'X-CSRF-TOKEN': CSRF, 'Content-Type': 'application/json', Accept: 'application/json' };
    const reduceMotion = window.matchMedia('(prefers-reduced-motion: reduce)').matches;

    document.getElementById('createGroupBtn')?.addEventListener('click', () => openSheet('createGroupSheet'));

    // Live monogram preview — mirrors the PHP crc32 hue formula.
    const crcTable = (() => {
        const t = [];
        for (let n = 0; n < 256; n++) { let c = n; for (let k = 0; k < 8; k++) c = c & 1 ? 0xEDB88320 ^ (c >>> 1) : c >>> 1; t[n] = c >>> 0; }
        return t;
    })();
    const crc32str = (s) => {
        const bytes = new TextEncoder().encode(s);
        let c = 0xFFFFFFFF;
        for (const b of bytes) c = crcTable[(c ^ b) & 0xFF] ^ (c >>> 8);
        return (c ^ 0xFFFFFFFF) >>> 0;
    };
    let groupImageChosen = false;
    document.getElementById('groupName')?.addEventListener('input', (e) => {
        if (groupImageChosen) return;   // don't overwrite a chosen photo with the monogram
        const name = e.target.value.trim();
        const prev = document.getElementById('groupMonogramPreview');
        const mono = name ? name.split(/\s+/).map((w) => w[0]).filter(Boolean).slice(0, 2).join('').toUpperCase() : '?';
        prev.textContent = mono || '?';
        prev.className = 'avatar avatar-lg avatar-sq overflow-hidden av-h' + (name ? crc32str(name.toLowerCase()) % 8 : 7);
    });
    document.getElementById('groupImage')?.addEventListener('change', (e) => {
        const f = e.target.files[0];
        if (!f) return;
        groupImageChosen = true;
        const prev = document.getElementById('groupMonogramPreview');
        prev.textContent = '';
        prev.innerHTML = `<img src="${URL.createObjectURL(f)}" alt="" class="w-full h-full object-cover">`;
    });

    document.getElementById('createGroupSave')?.addEventListener('click', async (e) => {
        const name = document.getElementById('groupName').value.trim();
        const description = document.getElementById('groupDesc').value.trim();
        const img = document.getElementById('groupImage').files[0];
        if (!name) { toast('Give your discussion a name.', 'error'); return; }
        const fd = new FormData();
        fd.append('name', name);
        if (description) fd.append('description', description);
        if (img) fd.append('image', img);
        e.currentTarget.disabled = true;
        try {
            const res = await fetch(@json(route('community.groups.store')), { method: 'POST', headers: { 'X-CSRF-TOKEN': CSRF, Accept: 'application/json' }, body: fd });
            const data = await res.json();
            if (data.success) { toast(data.message); window.location = data.data.url; }
            else toast(data.message || 'Could not create discussion.', 'error');
        } catch (_) { toast('Network error — try again.', 'error'); }
        finally { e.currentTarget.disabled = false; }
    });

    /* ---------------- Join from a card ----------------
       The card's one action changes word rather than the reader learning a
       new place to tap: Join fades out and Open arrives where it stood. */
    document.addEventListener('click', async (e) => {
        const btn = e.target.closest('.disc-join');
        if (!btn || btn.dataset.busy) return;
        btn.dataset.busy = '1';
        const id = btn.getAttribute('data-group-id');
        const card = btn.closest('[data-group-card]');
        const open = card?.querySelector('.disc-open');
        btn.style.opacity = '.6';
        try {
            const res = await fetch(`/app/community/groups/${id}/join`, { method: 'POST', headers: jsonHeaders });
            const data = await res.json();
            if (data.success) {
                toast(data.message);
                btn.textContent = '✓';
                btn.style.opacity = '';
                const finish = () => {
                    btn.classList.add('is-off');
                    btn.classList.remove('is-going');
                    open?.classList.remove('is-off');
                    if (!reduceMotion) {
                        open?.classList.add('is-arriving');
                        open?.addEventListener('animationend', () => open.classList.remove('is-arriving'), { once: true });
                    }
                    card?.querySelector('.group-joined-tag')?.classList.remove('hidden');
                };
                if (reduceMotion) finish();
                else {
                    setTimeout(() => {
                        btn.classList.add('is-going');
                        btn.addEventListener('transitionend', finish, { once: true });
                        setTimeout(finish, 500);   // safety if transitionend is missed
                    }, 300);
                }
            } else { toast(data.message, 'error'); btn.style.opacity = ''; }
        } catch (_) { toast('Network error — try again.', 'error'); btn.style.opacity = ''; }
        finally { delete btn.dataset.busy; }
    });

    /* ---------------- Scroll pagination ----------------
       Every group already comes down with the page (the list is small and the
       index has no JSON page endpoint), so a "page" here is a reveal, not a
       fetch. The reader still meets the wall's contract: one page at a time,
       a loader while it turns, one latch so nothing turns twice, and a plain
       line when the list ends. */
    const PER_PAGE = 8;
    const grid = document.getElementById('groupsGrid');
    const tail = document.getElementById('discTail');
    const moreBtn = document.getElementById('discMore');
    const spin = document.getElementById('discSpin');
    const endNote = document.getElementById('discEnd');
    let loading = false;
    let done = false;

    const pending = () => (grid ? Array.from(grid.querySelectorAll('.disc-card.is-paged-out')) : []);

    function finish() {
        done = true;
        moreBtn?.remove();
        if (spin) spin.hidden = true;
        if (endNote) endNote.hidden = false;
    }

    function revealPage() {
        if (!grid || done || loading || !moreBtn || moreBtn.disabled) return;
        const batch = pending().slice(0, PER_PAGE);
        if (!batch.length) { finish(); return; }
        loading = true;
        moreBtn.disabled = true;
        moreBtn.hidden = true;
        if (spin) spin.hidden = false;
        // A beat on the loader so the page turn reads as one, then the cards
        // land staggered the way the first page did.
        setTimeout(() => {
            batch.forEach((el, i) => {
                el.classList.remove('is-paged-out');
                if (!reduceMotion) {
                    el.classList.add('is-paged-in');
                    el.style.animationDelay = Math.min(i * 45, 300) + 'ms';
                    el.addEventListener('animationend', () => { el.classList.remove('is-paged-in'); el.style.animationDelay = ''; }, { once: true });
                }
            });
            if (spin) spin.hidden = true;
            loading = false;
            if (!pending().length) { finish(); return; }
            moreBtn.disabled = false;
            moreBtn.hidden = false;
            setTimeout(nearTail, 0);   // still near the bottom? keep going
        }, reduceMotion ? 0 : 220);
    }

    // 700px of runway, the margin the shared observer uses, so the next cards
    // are already there when the reader arrives.
    function nearTail() {
        if (!moreBtn || done || loading || moreBtn.hidden || moreBtn.disabled) return;
        if (moreBtn.getBoundingClientRect().top < window.innerHeight + 700) revealPage();
    }
    /* Throttled on the clock rather than requestAnimationFrame: a tab that is
       not painting never delivers the frame, and the list would stop looking. */
    let lastLook = 0;
    function onScroll() {
        const now = Date.now();
        if (now - lastLook < 100) return;
        lastLook = now;
        nearTail();
    }
    if (tail && !tail.hidden) {
        moreBtn?.addEventListener('click', revealPage);
        window.addEventListener('scroll', onScroll, { passive: true });
        window.addEventListener('resize', onScroll, { passive: true });
        nearTail();   // a short list can end with the tail already in view
    }
});
</script>
@endpush
