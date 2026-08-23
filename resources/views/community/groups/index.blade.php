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
    /* Stacked first, paired only where there is honestly room.
       Side-by-side held all the way down to 358px, which on a real phone left
       "Sali ka sa usapan" wrapping into a two-line column beside a button —
       the two-column look the owner has now flagged twice. The heading takes
       its own line, the button takes a full one under it, and they share a
       row from 30rem up where both fit without either bending. */
    .disc-head { display:flex; flex-direction:column; align-items:stretch; gap:.6rem; margin-bottom:1rem; }
    .disc-head-copy { min-width:0; }
    .disc-head-title { font-family:var(--font-heading); font-size:1.05rem; font-weight:800; line-height:1.25;
        color:var(--color-gray-900); }
    .disc-head-sub { font-size:.8rem; line-height:1.4; color:var(--color-gray-500); margin-top:.15rem; }
    .disc-head-btn { width:100%; justify-content:center; }
    @media (min-width:30rem) {
        .disc-head { flex-direction:row; align-items:center; gap:.75rem; }
        .disc-head-copy { flex:1 1 auto; }
        .disc-head-btn { width:auto; flex:0 0 auto; margin-left:auto; }
    }

    /* The cover and the face. A coloured stripe told a reader nothing about
       the room; a picture of it tells them whether they want to walk in. */
    .disc-card { overflow: visible; }
    .dc-top { position: relative; }
    .dc-cover { height: 6.5rem; overflow: hidden; border-radius: 1rem 1rem 0 0; }
    .dc-cover img { width: 100%; height: 100%; object-fit: cover; display: block; }
    .dc-face { position: absolute; left: .9rem; bottom: -1.35rem;
        display: flex; align-items: center; justify-content: center;
        width: 3.5rem; height: 3.5rem; border-radius: .95rem; overflow: hidden;
        border: 3px solid var(--color-white); background: var(--color-white);
        font-family: var(--font-heading); font-weight: 800; color: #fff;
        box-shadow: 0 10px 22px -14px rgb(0 0 0 / .8); text-decoration: none; }
    .dc-face img { width: 100%; height: 100%; object-fit: cover; }
    /* Room for the face, which stands half off the cover. */
    .dc-body { padding-top: 1.85rem !important; }

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
    /* The same red circle the nav and the bell use. */
    .disc-new { display:inline-flex; align-items:center; justify-content:center;
        min-width:1.15rem; height:1.15rem; padding:0 .3rem; border-radius:999px;
        background:#ef4444; color:#fff; font-size:.625rem; font-weight:800;
        line-height:1; vertical-align:middle; margin-left:.25rem; }
    .gb-well { display:flex; align-items:center; justify-content:center; overflow:hidden;
        width:100%; height:5rem; border-radius:.75rem; cursor:pointer; text-align:center;
        background:var(--color-gray-100); border:1px dashed var(--color-gray-300);
        transition:border-color var(--dur) var(--ease-house), background var(--dur) var(--ease-house); }
    .gb-well:hover { border-color:var(--color-brand-400); background:var(--color-brand-50); }
    .gb-well i { font-style:normal; font-size:.75rem; font-weight:600; color:var(--color-gray-400);
        padding:0 .75rem 1.25rem; }
    .gb-well img { width:100%; height:100%; object-fit:cover; }
    /* Missing, after the save was refused: the ask, made visible. */
    .gb-well.is-wanted, .gb-face.is-wanted { border-color:#ef4444; border-style:solid; }

    .gb-tip { margin-top:.15rem; line-height:1.35; }
    .gb-req { font-size:.62rem; font-weight:800; letter-spacing:.04em; text-transform:uppercase;
        color:var(--color-brand-700); background:var(--color-brand-50);
        padding:.1rem .4rem; border-radius:999px; margin-left:.25rem; }
    /* The face sits half over the cover above it, the way it will in the
       room — so the sheet shows what is being made, not two form fields. */
    /* flex-end, not center: the face hangs up over the cover, and its words
       belong under the well rather than across the middle of it. */
    .gb-face-row { display:flex; align-items:flex-end; gap:.75rem; margin-top:-2.25rem; }
    .gb-face-row > div { padding-bottom:.1rem; }
    .gb-face { position:relative; flex:0 0 auto; width:4.5rem; height:4.5rem; border-radius:1.15rem;
        overflow:hidden; cursor:pointer; display:flex; align-items:center; justify-content:center;
        border:3px solid var(--color-white); background:var(--color-gray-200);
        box-shadow:0 10px 22px -14px rgb(0 0 0 / .8); }
    .gb-face img { width:100%; height:100%; object-fit:cover; }
    .gb-face-mono { font-family:var(--font-heading); font-weight:800; font-size:1.2rem; color:var(--color-gray-500); }
    .gb-face-cam { position:absolute; right:.15rem; bottom:.15rem; width:1.35rem; height:1.35rem;
        border-radius:999px; background:var(--color-brand-600); color:#fff;
        display:flex; align-items:center; justify-content:center; }
    .gb-face-cam svg { width:.85rem; height:.85rem; }
    .gb-face-lbl { font-size:.85rem; font-weight:700; color:var(--color-gray-900); }
    .gb-face-sub { font-size:.72rem; color:var(--color-gray-400); }

    /* One row per way in. The AI composer's rows, in this page's words —
       that sheet's class is scoped to its own page. */
    .gb-src { display:flex; align-items:center; gap:.75rem; width:100%; padding:.7rem .8rem;
        border:0; border-radius:.85rem; background:transparent; text-align:left; cursor:pointer;
        font-size:.9rem; font-weight:700; color:var(--color-gray-800);
        transition:background var(--dur) var(--ease-house); }
    .gb-src:hover { background:var(--color-gray-100); }
    .gb-src .ic { width:2.4rem; height:2.4rem; border-radius:.8rem; flex-shrink:0;
        display:flex; align-items:center; justify-content:center;
        background:var(--color-brand-50); color:var(--color-brand-700); }
    .gb-src .ic svg { width:1.25rem; height:1.25rem; }
    .gb-src .sub { display:block; font-size:.72rem; font-weight:600; color:var(--color-gray-400); }
    @media (prefers-reduced-motion: reduce) { .gb-well, .gb-src { transition:none; } }
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

{{-- An empty room and an empty answer are different things: a search that
     matched nothing keeps its field, or there is no way back to the list. --}}
@if ($groups->isEmpty() && ($q ?? '') === '')
    <div class="card">
        <div class="card-body text-center py-14">
            <div class="empty-tile">👥</div>
            <h2 class="text-lg font-bold text-gray-900 mb-1" style="font-family:var(--font-heading)">Wala pang discussions</h2>
            <p class="text-sm text-gray-500 mb-5">Ikaw ang mag-umpisa — invite kapwa magsasaka to talk shop.</p>
            <button type="button" class="btn btn-primary" onclick="document.getElementById('createGroupBtn').click()">Start the first discussion</button>
        </div>
    </div>
@else
    @include('community.partials.live-search', [
        'id' => 'discFind',
        'value' => $q ?? '',
        'placeholder' => 'Search discussions…',
        'label' => 'Search discussions',
    ])

    <div class="grid gap-3 sm:grid-cols-2 stagger-children" id="groupsGrid">
        @include('community.groups.partials.cards', ['groups' => $groups])
    </div>

    <div class="card p-8 text-center" id="discNone" @unless ($groups->isEmpty()) hidden @endunless>
        <div class="empty-tile">🔎</div>
        <p class="font-bold text-gray-900" style="font-family:var(--font-heading)">Walang tugma</p>
        <p class="text-sm text-gray-500 mt-1">No discussion matches that. Try one word instead of a phrase.</p>
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
    <div class="sheet-body space-y-2.5">
        {{-- Both pictures, up front. A room made without them is a coloured
             square with two letters in it, and the one making it is the only
             person who can fix that. --}}
        <div>
            <label class="form-label">Cover photo <span class="gb-req">required</span></label>
            <button type="button" class="gb-well" id="groupBannerPreview" data-pic="banner">
                <i>Add a wide photo for the top of the discussion</i>
            </button>
        </div>
        <div class="gb-face-row">
            <button type="button" class="gb-face" id="groupMonogramPreview" data-pic="image">
                <span class="gb-face-mono">?</span>
                <span class="gb-face-cam" aria-hidden="true">
                    <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path stroke-linecap="round" stroke-linejoin="round" d="M3 9a2 2 0 012-2h.93a2 2 0 001.664-.89l.812-1.22A2 2 0 0110.07 4h3.86a2 2 0 011.664.89l.812 1.22A2 2 0 0018.07 7H19a2 2 0 012 2v9a2 2 0 01-2 2H5a2 2 0 01-2-2V9z"/><path stroke-linecap="round" stroke-linejoin="round" d="M15 13a3 3 0 11-6 0 3 3 0 016 0z"/></svg>
                </span>
            </button>
            <div class="min-w-0">
                <p class="gb-face-lbl">Discussion photo <span class="gb-req">required</span></p>
                <p class="gb-face-sub">The room's face, wherever it is listed.</p>
            </div>
        </div>
        <div>
            <label class="form-label" for="groupName">Discussion name</label>
            <input type="text" id="groupName" class="form-input" maxlength="150" placeholder="e.g. Rice Growers of Central Luzon">
            <p class="form-hint gb-tip">Tip: pangalanan mo per crop o per lugar — "Palay — Nueva Ecija".</p>
        </div>
        <div>
            <label class="form-label" for="groupDesc">Description <span class="text-gray-400 font-normal">(optional)</span></label>
            <textarea id="groupDesc" class="form-textarea" rows="2" maxlength="500" placeholder="What's this discussion about?"></textarea>
        </div>
        {{-- The three doors, shared by both pictures: whichever well was
             tapped is the one the pick lands in. --}}
        <input type="file" id="groupPicFile" accept="image/jpeg,image/png,image/webp" class="hidden">
        <input type="file" id="groupPicCam" accept="image/*" capture="environment" class="hidden">
    </div>
    <div class="sheet-footer">
        <button type="button" class="btn btn-ghost" data-sheet-close>Cancel</button>
        <button type="button" class="btn btn-primary" id="createGroupSave">Create discussion</button>
    </div>
</div>
{{-- Where a picture comes from. The same three the AI composer, the
     messenger and the notes all offer, in the same order. --}}
<div class="sheet hidden" id="groupPicSheet" style="--sheet-width:24rem">
    <div class="sheet-handle"></div>
    <div class="sheet-header">
        <h3 class="sheet-title" id="groupPicTitle">Add a photo</h3>
        <button type="button" data-sheet-close class="btn-ghost p-2 rounded-full" aria-label="Close">✕</button>
    </div>
    <div class="sheet-body space-y-1">
        <button type="button" class="gb-src" data-src="cam">
            <span class="ic"><svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path stroke-linecap="round" stroke-linejoin="round" d="M3 9a2 2 0 012-2h.93a2 2 0 001.664-.89l.812-1.22A2 2 0 0110.07 4h3.86a2 2 0 011.664.89l.812 1.22A2 2 0 0018.07 7H19a2 2 0 012 2v9a2 2 0 01-2 2H5a2 2 0 01-2-2V9z"/><path stroke-linecap="round" stroke-linejoin="round" d="M15 13a3 3 0 11-6 0 3 3 0 016 0z"/></svg></span>
            <span>Take a photo<span class="sub">Use the camera now</span></span>
        </button>
        <button type="button" class="gb-src" data-src="file">
            <span class="ic"><svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path stroke-linecap="round" stroke-linejoin="round" d="M4 16l4.586-4.586a2 2 0 012.828 0L16 16m-2-2l1.586-1.586a2 2 0 012.828 0L20 14m-6-6h.01M6 20h12a2 2 0 002-2V6a2 2 0 00-2-2H6a2 2 0 00-2 2v12a2 2 0 002 2z"/></svg></span>
            <span>From this phone<span class="sub">Pick a picture you already have</span></span>
        </button>
        <button type="button" class="gb-src" data-src="gallery">
            <span class="ic"><svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path stroke-linecap="round" stroke-linejoin="round" d="M4 7h3l2-3h6l2 3h3v13H4V7z"/><path stroke-linecap="round" stroke-linejoin="round" d="M8 13l2.5-2.5L14 14l2-2 2 2"/></svg></span>
            <span>From the AniSenso gallery<span class="sub">A photo your seasons already keep</span></span>
        </button>
    </div>
</div>
{{-- The gallery sheet itself. @once inside, so including it here is safe. --}}
@include('sm.partials.media-picker')
@endsection

@push('scripts')
{{-- Rooms are looked at too: the same counter the wall uses. --}}
@include('community.partials.views-js')
@include('community.partials.infinite-js')
<script>
document.addEventListener('DOMContentLoaded', () => {
    const CSRF = document.querySelector('meta[name=csrf-token]').content;
    const jsonHeaders = { 'X-CSRF-TOKEN': CSRF, 'Content-Type': 'application/json', Accept: 'application/json' };
    const reduceMotion = window.matchMedia('(prefers-reduced-motion: reduce)').matches;

    document.getElementById('createGroupBtn')?.addEventListener('click', () => openSheet('createGroupSheet'));

    /* ---------------- the two pictures ----------------
       A camera file, a phone file and a gallery path are three different
       things; what the save needs to know is only which picture each one is.
       So one object holds both, and every door writes into it. */
    const pics = { image: null, banner: null };   // {file} | {path, url}
    let picking = null;                           // which well opened the sheet

    function showPic(which, url) {
        const box = document.getElementById(which === 'banner' ? 'groupBannerPreview' : 'groupMonogramPreview');
        if (!box) return;
        box.classList.remove('is-wanted');
        box.querySelector('i')?.remove();
        box.querySelector('.gb-face-mono')?.remove();
        box.querySelector('img')?.remove();
        const img = document.createElement('img');
        img.src = url;
        img.alt = '';
        box.prepend(img);
    }

    function tookFile(f) {
        if (!f || !picking) return;
        pics[picking] = { file: f };
        showPic(picking, URL.createObjectURL(f));
        if (picking === 'image') groupImageChosen = true;
        picking = null;
    }

    // Either well opens the same three doors; the well that was tapped is
    // remembered, so the pick knows where to land.
    document.querySelectorAll('[data-pic]').forEach((well) => {
        well.addEventListener('click', () => {
            picking = well.getAttribute('data-pic');
            const title = document.getElementById('groupPicTitle');
            if (title) title.textContent = picking === 'banner' ? 'Add a cover photo' : 'Add the discussion photo';
            openSheet('groupPicSheet');
        });
    });

    document.querySelectorAll('.gb-src').forEach((row) => {
        row.addEventListener('click', () => {
            const src = row.getAttribute('data-src');
            window.closeSheet && window.closeSheet('groupPicSheet');
            if (src === 'cam') { document.getElementById('groupPicCam')?.click(); return; }
            if (src === 'file') { document.getElementById('groupPicFile')?.click(); return; }
            // The gallery every season keeps, asked across all of them: a
            // farmer choosing a room's picture is remembering a photo, not a
            // schedule.
            const which = picking;
            if (!window.smPickMedia) { toast('The gallery is not available here.', 'error'); return; }
            window.smPickMedia({
                allSchedules: true,
                kinds: 'image',
                title: 'Choose from your gallery',
                onPick: (item) => {
                    if (!which || !item) return;
                    pics[which] = { path: item.path, url: item.url };
                    showPic(which, item.url);
                    if (which === 'image') groupImageChosen = true;
                    picking = null;
                },
            });
        });
    });

    // Shown before it is sent: a wrong pick is caught here, not after a save.
    document.getElementById('groupPicFile')?.addEventListener('change', (e) => { tookFile(e.target.files && e.target.files[0]); e.target.value = ''; });
    document.getElementById('groupPicCam')?.addEventListener('change', (e) => { tookFile(e.target.files && e.target.files[0]); e.target.value = ''; });

    let groupImageChosen = false;

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
    document.getElementById('groupName')?.addEventListener('input', (e) => {
        if (groupImageChosen) return;   // don't overwrite a chosen photo with the monogram
        const name = e.target.value.trim();
        const mono = document.querySelector('#groupMonogramPreview .gb-face-mono');
        if (!mono) return;
        mono.textContent = (name ? name.split(/\s+/).map((w) => w[0]).filter(Boolean).slice(0, 2).join('').toUpperCase() : '?') || '?';
        const face = document.getElementById('groupMonogramPreview');
        face.className = 'gb-face av-h' + (name ? crc32str(name.toLowerCase()) % 8 : 7);
    });

    document.getElementById('createGroupSave')?.addEventListener('click', async (e) => {
        // Captured now: `currentTarget` is null once this handler awaits.
        const saveBtn = e.currentTarget;
        const name = document.getElementById('groupName').value.trim();
        const description = document.getElementById('groupDesc').value.trim();
        if (!name) { toast('Give your discussion a name.', 'error'); return; }
        /* Both pictures, and said plainly: the room is going to be listed
           beside rooms that have them. */
        const missing = ['image', 'banner'].filter((k) => !pics[k]);
        if (missing.length) {
            missing.forEach((k) => document.getElementById(k === 'banner' ? 'groupBannerPreview' : 'groupMonogramPreview')?.classList.add('is-wanted'));
            toast(missing.length === 2
                ? 'Add a discussion photo and a cover photo.'
                : (missing[0] === 'banner' ? 'Add a cover photo.' : 'Add a discussion photo.'), 'error');
            return;
        }
        const fd = new FormData();
        fd.append('name', name);
        if (description) fd.append('description', description);
        // A file goes up; a gallery pick goes up as the path it already has.
        [['image', 'imagePath'], ['banner', 'bannerPath']].forEach(([key, pathKey]) => {
            const pic = pics[key];
            if (pic.file) fd.append(key, pic.file);
            else if (pic.path) fd.append(pathKey, pic.path);
        });
        saveBtn.disabled = true;
        try {
            const res = await fetch(@json(route('community.groups.store')), { method: 'POST', headers: { 'X-CSRF-TOKEN': CSRF, Accept: 'application/json' }, body: fd });
            const data = await res.json();
            if (data.success) { toast(data.message); window.location = data.data.url; }
            else toast(data.message || 'Could not create discussion.', 'error');
        } catch (_) { toast('Network error — try again.', 'error'); }
        finally { saveBtn.disabled = false; }
    });

    /* ---------------- Join from a card ----------------
       The card's one action changes word rather than the reader learning a
       new place to tap: Join fades out and Open arrives where it stood. */
    document.addEventListener('click', async (e) => {
        const btn = e.target.closest('.disc-join');
        if (!btn || btn.dataset.busy) return;
        /* Asked first: joining puts your name in a room other people can
           see, and a tap on a card in a scrolling list is easy to make by
           accident. */
        const name = btn.getAttribute('data-name') || 'this discussion';
        const ok = await (window.confirmAction ? window.confirmAction({
            title: 'Join ' + name + '?',
            message: 'You will see its topics on your wall, and the others there will see you as a member.',
            confirmText: 'Join',
            confirmClass: 'btn-primary',
        }) : Promise.resolve(true));
        if (!ok) return;
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
    const findEl = document.getElementById('discFind');
    const noneCard = document.getElementById('discNone');
    const findNote = document.getElementById('discFindNote');
    let nextPage = 2;
    let loading = false;
    let done = false;
    let autoPull = true;
    let query = (findEl?.value || '').trim();

    /* Hidden, not removed. A search is another first page, and a button that
       was deleted when the unfiltered list ran out has nothing to come back
       to when the answer is longer than one screen. */
    function finish() {
        done = true;
        if (moreBtn) { moreBtn.hidden = true; moreBtn.disabled = true; }
        if (spin) spin.hidden = true;
        if (endNote) endNote.hidden = false;
    }

    const pageUrl = (page) => PAGE_URL + '?page=' + page
        + (query ? '&q=' + encodeURIComponent(query) : '');

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
            const res = await fetch(pageUrl(nextPage), {
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
    /* Back-button arrivals.
       A browser restores this page exactly as it was left — so a room joined
       on its own page still says Join here, which is the "it says I am joined
       but the button is still Join" the owner hit. Nothing else on the page
       can tell; ask the server what is true. */
    window.addEventListener('pageshow', (ev) => {
        if (!ev.persisted) return;
        fetch(@json(route('community.groups.mine')), { headers: { Accept: 'application/json' }, credentials: 'same-origin' })
            .then((r) => r.json())
            .then((j) => {
                const mine = new Set(((j.data && j.data.groupIds) || []).map(Number));
                document.querySelectorAll('[data-group-card]').forEach((card) => {
                    const joined = mine.has(Number(card.getAttribute('data-group-card')));
                    card.querySelector('.disc-join')?.classList.toggle('is-off', joined);
                    card.querySelector('.disc-open')?.classList.toggle('is-off', !joined);
                    card.querySelector('.group-joined-tag')?.classList.toggle('hidden', !joined);
                });
            })
            .catch(() => { /* leave the page as it was rather than break it */ });
    });

    /* ---------------- Search ----------------
       The same endpoint the pagination uses, asked for page one with the
       words on it. Everything the reader can see about the list — the cards,
       the tail, the "nothing matched" card, the line under the field — is put
       back to a first page here, because that is exactly what arrived. */
    function say(count, hasMore) {
        if (!findNote) return;
        if (!query) { findNote.hidden = true; findNote.textContent = ''; return; }
        findNote.hidden = false;
        if (!count) { findNote.innerHTML = 'Walang tugma sa <b></b>.'; }
        else { findNote.innerHTML = (hasMore ? 'First ' : '') + count + ' '
            + (count === 1 ? 'discussion' : 'discussions') + ' matching <b></b>.'; }
        // The words go in as text, never as markup — they came from a keyboard.
        findNote.querySelector('b').textContent = '“' + query + '”';
    }

    async function search(q) {
        if (!grid) return;
        query = q;
        // A shared link, a reload and a back button should all show what is
        // on the screen right now.
        try {
            const url = new URL(window.location.href);
            if (q) url.searchParams.set('q', q); else url.searchParams.delete('q');
            history.replaceState(null, '', url);
        } catch (_) { /* an address bar that will not be written is not a failure */ }

        loading = true;
        try {
            const res = await fetch(pageUrl(1), {
                headers: { Accept: 'application/json' },
                credentials: 'same-origin',
            });
            if (!res.ok) throw new Error('HTTP ' + res.status);
            const data = (await res.json()).data || {};
            grid.innerHTML = data.html || '';
            const count = grid.children.length;
            if (noneCard) noneCard.hidden = count > 0;
            nextPage = data.nextPage || 2;
            done = !data.hasMore;
            autoPull = true;
            if (spin) spin.hidden = true;
            if (endNote) endNote.hidden = true;
            if (moreBtn) {
                moreBtn.hidden = !data.hasMore;
                moreBtn.disabled = false;
                moreBtn.textContent = 'Show more discussions';
            }
            if (tail) tail.hidden = !data.hasMore;
            say(count, !!data.hasMore);
        } catch (_) {
            if (window.toast) toast('Could not search just now.', 'error');
        } finally {
            loading = false;
            setTimeout(nearTail, 0);
        }
    }

    if (findEl) {
        window.plazaLiveSearch?.(findEl, search);
        if (query) say(grid ? grid.children.length : 0, !!(tail && !tail.hidden));
    }

    moreBtn?.addEventListener('click', () => { autoPull = true; loadPage(); });
    window.addEventListener('scroll', onScroll, { passive: true });
    window.addEventListener('resize', onScroll, { passive: true });
    if (tail && !tail.hidden) nearTail();   // a short list can end with the tail already in view
});
</script>
@endpush
