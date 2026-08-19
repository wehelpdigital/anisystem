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
    .gb-well { display:flex; align-items:center; justify-content:center; overflow:hidden;
        width:100%; height:6rem; border-radius:.75rem; cursor:pointer; text-align:center;
        background:var(--color-gray-100); border:1px dashed var(--color-gray-300); }
    .gb-well i { font-style:normal; font-size:.75rem; font-weight:600; color:var(--color-gray-400); padding:0 .75rem; }
    .gb-well img { width:100%; height:100%; object-fit:cover; }
    .disc-spin { display:flex; align-items:center; justify-content:center; gap:.35rem; padding:.9rem 0; }
    .disc-spin i { display:block; width:.45rem; height:.45rem; border-radius:9999px;
        background:var(--color-brand-400); animation:discDot 1s cubic-bezier(.22,1,.36,1) infinite; }
    .disc-spin i:nth-child(2) { animation-delay:.12s; }
    .disc-spin i:nth-child(3) { animation-delay:.24s; }
    @keyframes discDot { 0%,100% { opacity:.25; transform:translateY(0); } 50% { opacity:1; transform:translateY(-.25rem); } }
    .disc-end { font-size:.78rem; font-weight:600; color:var(--color-gray-400); padding:1rem 0 .4rem; }
    .disc-spin[hidden], .disc-end[hidden] { display:none; }

    /* Cards past the first page wait off-stage and arrive a page at a time. */
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
    <div class="grid gap-3 sm:grid-cols-2 stagger-children" id="groupsGrid">
        @include('community.groups.partials.cards', ['groups' => $groups])
    </div>

    <div class="disc-tail" id="discTail" @unless ($hasMore) hidden @endunless>
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
        <div>
            {{-- The wide picture at the top of the room. Asked for here so a
                 new discussion opens looking like somewhere, instead of having
                 to be edited the moment it is made. --}}
            <label class="form-label" for="groupBanner">Cover photo <span class="text-gray-400 font-normal">(optional)</span></label>
            <label class="gb-well" id="groupBannerPreview">
                <i>Add a wide photo for the top of the discussion</i>
                <input type="file" id="groupBanner" accept="image/jpeg,image/png,image/webp" class="hidden">
            </label>
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
    // Shown before it is sent: a wrong pick is caught here, not after a save.
    document.getElementById('groupBanner')?.addEventListener('change', (e) => {
        const f = e.target.files && e.target.files[0];
        if (!f) return;
        const box = document.getElementById('groupBannerPreview');
        const url = URL.createObjectURL(f);
        box.querySelector('i')?.remove();
        const old = box.querySelector('img');
        if (old) old.remove();
        const img = document.createElement('img');
        img.src = url;
        box.prepend(img);
    });

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
        const banner = document.getElementById('groupBanner')?.files[0];
        if (banner) fd.append('banner', banner);
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
       A page is a fetch, not a reveal. The list used to ship every discussion
       in the first response and merely uncover them as the reader scrolled,
       which is the cost pagination exists to avoid: a farm with three hundred
       usapan paid for all of them to see eight. The server now sends one
       screenful and says whether another exists.

       One page in flight at a time, a loader while it turns, a plain line at
       the end — and a failure stops the automatic pull: a scroll handler that
       retries on every frame turns one dead network into a storm, so the
       button comes back and waits to be asked. */
    const PAGE_URL = @json(route('community.groups.page'));
    const grid = document.getElementById('groupsGrid');
    const tail = document.getElementById('discTail');
    const moreBtn = document.getElementById('discMore');
    const spin = document.getElementById('discSpin');
    const endNote = document.getElementById('discEnd');
    let nextPage = 2;
    let loading = false;
    let done = false;
    let autoPull = true;

    function finish() {
        done = true;
        moreBtn?.remove();
        if (spin) spin.hidden = true;
        if (endNote) endNote.hidden = false;
    }

    function land(el, i) {
        grid.appendChild(el);
        if (reduceMotion) return;
        el.classList.add('is-paged-in');
        el.style.animationDelay = Math.min(i * 45, 300) + 'ms';
        el.addEventListener('animationend', () => {
            el.classList.remove('is-paged-in');
            el.style.animationDelay = '';
        }, { once: true });
    }

    async function loadPage() {
        if (!grid || done || loading) return;
        loading = true;
        if (moreBtn) { moreBtn.disabled = true; moreBtn.hidden = true; }
        if (spin) spin.hidden = false;
        try {
            const res = await fetch(PAGE_URL + '?page=' + nextPage, {
                headers: { Accept: 'application/json' },
                credentials: 'same-origin',
            });
            if (!res.ok) throw new Error('HTTP ' + res.status);
            const data = (await res.json()).data || {};
            const holder = document.createElement('div');
            holder.innerHTML = data.html || '';
            const fresh = Array.from(holder.children);
            fresh.forEach(land);
            nextPage = data.nextPage || nextPage + 1;
            autoPull = true;
            if (spin) spin.hidden = true;
            loading = false;
            if (!fresh.length || !data.hasMore) { finish(); return; }
            if (moreBtn) { moreBtn.disabled = false; moreBtn.hidden = false; }
            setTimeout(nearTail, 0);   // still near the bottom? keep going
        } catch (e) {
            loading = false;
            // Hand the next page back to the reader rather than to the scroll.
            autoPull = false;
            if (spin) spin.hidden = true;
            if (moreBtn) {
                moreBtn.disabled = false;
                moreBtn.hidden = false;
                moreBtn.textContent = 'Try again';
            }
            if (window.toast) toast('Could not load more discussions.', 'error');
        }
    }

    // 700px of runway, the margin the shared observer uses, so the next cards
    // are already there when the reader arrives.
    function nearTail() {
        if (!moreBtn || done || loading || !autoPull || moreBtn.hidden || moreBtn.disabled) return;
        if (moreBtn.getBoundingClientRect().top < window.innerHeight + 700) loadPage();
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
        moreBtn?.addEventListener('click', () => { autoPull = true; loadPage(); });
        window.addEventListener('scroll', onScroll, { passive: true });
        window.addEventListener('resize', onScroll, { passive: true });
        nearTail();   // a short list can end with the tail already in view
    }
});
</script>
@endpush
