@extends('layouts.app')

@section('title', $member->full_name . ' — Community')
@section('page-title', 'Community')
@section('page-subtitle', $member->full_name)
@section('back', route('community.connect.members'))

@push('head')
@include('community.partials.plaza-css')
@endpush

@section('content')
<div>
    @include('community.partials.nav', ['active' => (int) $member->id === (int) auth()->id() ? 'profile' : 'members'])

    {{-- Profile header --}}
    <div class="card p-5 mb-4">
        @if (filled($member->coverPath))
            <div class="-mx-5 -mt-5 mb-4 h-32 sm:h-44 rounded-t-xl bg-gray-100 bg-center bg-cover"
                 style="background-image:url('{{ \App\Support\MediaStore::url($member->coverPath) }}')"></div>
        @endif
        <div class="flex items-start gap-4">
            <span class="status-avatar inline-block shrink-0 relative" style="width:4rem;height:4rem;" data-self="{{ $isSelf ? 1 : 0 }}">
                @include('community.partials.avatar', ['user' => $member, 'size' => 'avatar-lg', 'link' => false, 'showOnline' => true])
                {{-- Thought bubble floating over the profile pic. --}}
                <span class="status-bubble {{ filled($member->statusBubble) ? '' : 'is-empty' }}" id="statusBubble"
                      @if ($isSelf) role="button" tabindex="0" title="Set your status" @endif><span class="status-bubble-text">{{ $member->statusBubble ?: ($isSelf ? "💭 What's on your mind?" : '') }}</span></span>
            </span>
            <div class="min-w-0 grow">
                <h2 class="text-xl font-bold text-gray-900 leading-tight">{{ $member->full_name }}</h2>
                @if (filled($member->headline))
                    <p class="text-sm text-gray-600 font-medium mt-0.5">{{ $member->headline }}</p>
                @endif
                @if (filled($member->location))
                    <p class="text-sm text-gray-500 mt-0.5 flex items-center gap-1">
                        <svg class="w-4 h-4 text-gray-400" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" d="M17.657 16.657L13.414 20.9a2 2 0 01-2.827 0l-4.244-4.243a8 8 0 1111.314 0z"/><path stroke-linecap="round" stroke-linejoin="round" d="M15 11a3 3 0 11-6 0 3 3 0 016 0z"/></svg>
                        {{ $member->location }}
                    </p>
                @endif
                <div class="flex items-center gap-4 text-xs text-gray-500 font-medium mt-2">
                    <span>{{ $connectionCount }} {{ \Illuminate\Support\Str::plural('connection', $connectionCount) }}</span>
                    <span>{{ $plans->count() }} shared {{ \Illuminate\Support\Str::plural('plan', $plans->count()) }}</span>
                </div>
            </div>
        </div>

        @if (filled($member->bio))
            <p class="text-sm text-gray-700 mt-4 whitespace-pre-line">{{ $member->bio }}</p>
        @endif

        @php
            $farmChips = array_filter([
                filled($member->profession) ? '💼 ' . $member->profession : null,
                filled($member->yearsFarming) ? '👨‍🌾 ' . $member->yearsFarming . ' ' . \Illuminate\Support\Str::plural('year', (int) $member->yearsFarming) . ' farming' : null,
                filled($member->farmSize) ? '📏 ' . $member->farmSize : null,
                filled($member->cropsGrown) ? '🌾 ' . $member->cropsGrown : null,
                filled($member->farmingMethod) ? '🧑‍🔬 ' . $member->farmingMethod : null,
            ]);
        @endphp
        @if (!empty($farmChips))
            <div class="flex flex-wrap gap-2 mt-4">
                @foreach ($farmChips as $chip)
                    <span class="inline-flex items-center text-xs font-semibold text-brand-800 bg-brand-50 border border-brand-100 rounded-full px-3 py-1">{{ $chip }}</span>
                @endforeach
            </div>
        @endif

        {{-- Action buttons — Message leads on the left. --}}
        <div class="flex flex-wrap items-center gap-2 mt-4 pt-4 border-t border-gray-100">
            @if ($isSelf)
                <a href="{{ route('account.index') }}" class="btn btn-white btn-sm">✏️ Edit profile</a>
            @else
                @if ($member->allowMessages)
                    <button type="button" class="btn btn-primary btn-sm js-open-dm" data-dm-user="{{ $member->id }}" data-dm-name="{{ $member->full_name }}">
                        💬 Message
                    </button>
                @endif
                @include('community.connect.partials.action', ['status' => $status, 'memberId' => $member->id])
            @endif
        </div>
    </div>

    {{-- Wall | Shared Plans tabs --}}
    <div class="profile-tabs flex gap-1 p-1 rounded-xl bg-gray-100 mb-4" role="tablist" id="profileTabs">
        <button type="button" class="profile-tab is-active" data-tab="wall" aria-selected="true">Wall</button>
        <button type="button" class="profile-tab" data-tab="photos" aria-selected="false">Photos <span class="text-xs opacity-70">({{ $photos->count() }})</span></button>
        <button type="button" class="profile-tab" data-tab="videos" aria-selected="false">Videos <span class="text-xs opacity-70">({{ $videos->count() }})</span></button>
        <button type="button" class="profile-tab" data-tab="plans" aria-selected="false">Shared Plans <span class="text-xs opacity-70">({{ $plans->count() }})</span></button>
    </div>

    <div data-tab-panel="wall">
        @include('community.connect.partials.wall', ['member' => $member, 'isSelf' => $isSelf])
    </div>

    <div data-tab-panel="plans" class="hidden">
        <div class="card p-4">
            <h3 class="font-bold text-gray-900 mb-2">Shared plans</h3>
            @if ($plans->isNotEmpty())
                <div class="space-y-2">
                    @foreach ($plans as $plan)
                        <a href="{{ route('community.show', ['id' => $plan->id]) }}" class="flex items-center justify-between gap-2 p-2.5 rounded-lg hover:bg-gray-50 transition">
                            <span class="min-w-0">
                                <span class="block font-semibold text-gray-900 text-sm truncate">{{ $plan->title }}</span>
                                @if ($plan->cropType)<span class="block text-xs text-gray-500">{{ $plan->cropType }}@if($plan->publicRegion) · {{ $plan->publicRegion }}@endif</span>@endif
                            </span>
                            <svg class="w-4 h-4 text-gray-300 shrink-0" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" d="M9 5l7 7-7 7"/></svg>
                        </a>
                    @endforeach
                </div>
            @else
                <p class="text-sm text-gray-400 py-4 text-center">{{ $isSelf ? 'You have' : $member->firstName . ' has' }} not shared any plans yet.</p>
            @endif
        </div>
    </div>

    <div data-tab-panel="photos" class="hidden">
        <div class="card p-4">
            <div class="flex items-center justify-between gap-3 mb-3">
                <h3 class="font-bold text-gray-900">Photos</h3>
                @if ($isSelf)
                    <label class="btn btn-primary btn-sm cursor-pointer mb-0">
                        <svg class="w-4 h-4" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" d="M12 4v16m8-8H4"/></svg>
                        Add photos
                        <input type="file" id="profilePhotoInput" accept="image/jpeg,image/png,image/webp" multiple class="hidden">
                    </label>
                @endif
            </div>
            <div class="profile-photos-grid {{ $photos->isEmpty() ? 'hidden' : '' }}" id="profilePhotosGrid">
                @foreach ($photos as $photo)
                    @include('community.connect.partials.photo-tile', ['item' => $photo])
                @endforeach
            </div>
            <p class="text-sm text-gray-400 py-6 text-center {{ $photos->isNotEmpty() ? 'hidden' : '' }}" id="profilePhotosEmpty">
                {{ $isSelf ? 'Add photos of your farm, harvest, or yourself — tap “Add photos”.' : $member->firstName . ' has not added any photos yet.' }}
            </p>
        </div>
    </div>

    <div data-tab-panel="videos" class="hidden">
        <div class="card p-4">
            <div class="flex items-center justify-between gap-3 mb-3">
                <h3 class="font-bold text-gray-900">Videos</h3>
                @if ($isSelf)
                    <label class="btn btn-primary btn-sm cursor-pointer mb-0" id="profileVideoAddBtn">
                        <svg class="w-4 h-4" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" d="M12 4v16m8-8H4"/></svg>
                        Add video
                        <input type="file" id="profileVideoInput" accept="video/mp4,video/quicktime,video/webm,video/x-matroska,video/x-msvideo,video/3gpp,video/x-m4v" class="hidden">
                    </label>
                @endif
            </div>
            @if ($isSelf)
                <div class="profile-video-uploading hidden" id="profileVideoUploading">
                    <span class="profile-video-spin" aria-hidden="true"></span>
                    <span>Uploading &amp; compressing your video… this can take a moment for longer clips.</span>
                </div>
            @endif
            <div class="profile-videos-grid {{ $videos->isEmpty() ? 'hidden' : '' }}" id="profileVideosGrid">
                @foreach ($videos as $video)
                    @include('community.connect.partials.video-tile', ['item' => $video])
                @endforeach
            </div>
            <p class="text-sm text-gray-400 py-6 text-center {{ $videos->isNotEmpty() ? 'hidden' : '' }}" id="profileVideosEmpty">
                {{ $isSelf ? 'Share a short clip of your farm or harvest — tap “Add video”. It’s compressed automatically.' : $member->firstName . ' has not added any videos yet.' }}
            </p>
        </div>
    </div>
</div>

@if ($isSelf)
{{-- Status composer modal (replaces the old prompt()). --}}
<div id="statusModal" class="plaza-modal hidden" role="dialog" aria-modal="true" aria-label="Set your status">
    <div class="plaza-modal-backdrop" data-close-status></div>
    <div class="plaza-modal-card" style="max-width:24rem">
        <div class="plaza-modal-head">
            <p class="font-bold text-gray-900">What are you thinking now?</p>
            <button type="button" class="btn-ghost rounded-full w-8 h-8 flex items-center justify-center text-gray-400 hover:text-gray-700" data-close-status aria-label="Close">✕</button>
        </div>
        <div class="plaza-modal-body">
            <div class="flex items-center gap-2">
                <input type="text" id="statusInput" class="form-input grow" maxlength="60" placeholder="e.g. Aani na! 🌾 · Waiting for rain · Nagtatanim ng palay">
                <button type="button" class="emoji-btn js-emoji-btn shrink-0" data-target="statusInput" aria-label="Add an emoji" title="Emoji">
                    <svg class="w-5 h-5" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" d="M14.828 14.828a4 4 0 01-5.656 0M9 10h.01M15 10h.01M21 12a9 9 0 11-18 0 9 9 0 0118 0z"/></svg>
                </button>
            </div>
            <div class="flex items-center justify-between mt-1.5">
                <p class="text-xs text-gray-400">Floats as a thought bubble over your photo. Leave blank and Clear to remove it.</p>
                <span id="statusCount" class="text-xs text-gray-400 font-medium shrink-0 ml-2 tabular-nums">0/60</span>
            </div>
        </div>
        <div class="plaza-modal-foot flex items-center justify-between">
            <button type="button" id="statusClear" class="btn btn-ghost btn-sm text-red-500 hover:bg-red-50">Clear status</button>
            <div class="flex gap-2">
                <button type="button" class="btn btn-white btn-sm" data-close-status>Cancel</button>
                <button type="button" id="statusSave" class="btn btn-primary btn-sm">Save</button>
            </div>
        </div>
    </div>
</div>
@endif
@endsection

@push('head')
<style>
    .profile-tab { flex:1; padding:.55rem .75rem; border:0; background:transparent; border-radius:.6rem;
        font-size:.9rem; font-weight:600; color:#5b6472; cursor:pointer; transition:background .2s ease, color .2s ease, box-shadow .2s ease; }
    .profile-tab.is-active { background:#fff; color:#1f2937; box-shadow:0 1px 2px rgba(0,0,0,.08); }
    html.dark .profile-tabs { background:#1c2136; }
    html.dark .profile-tab.is-active { background:#2a3050; color:#e5e9f5; }
    [data-tab-panel].hidden { display:none; }

    /* Chat bubble above the profile pic, with a tail pointing down at the photo. */
    .status-bubble { position:absolute; bottom:calc(100% + .3rem); left:0; right:auto;
        max-width:12rem;
        background:#fff; border:1px solid #e5e7eb; border-radius:.7rem; padding:.2rem .6rem;
        box-shadow:0 3px 10px rgba(0,0,0,.12); z-index:2;
        transition:transform .15s var(--ease-house,cubic-bezier(.22,1,.36,1)), box-shadow .15s ease; }
    .status-bubble-text { display:block; white-space:nowrap; overflow:hidden; text-overflow:ellipsis;
        font-size:.7rem; font-weight:600; color:#374151; }
    .status-bubble::after { content:''; position:absolute; left:.9rem; bottom:-.3rem;
        width:0; height:0; border-left:.3rem solid transparent; border-right:.3rem solid transparent;
        border-top:.32rem solid #fff; filter:drop-shadow(0 2px 1px rgba(0,0,0,.05)); }
    .status-avatar[data-self="1"] .status-bubble { cursor:pointer; }
    .status-avatar[data-self="1"] .status-bubble:hover { transform:translateY(-1px); box-shadow:0 6px 16px rgba(0,0,0,.18); }
    /* Empty own-profile state: a noticeable brand-tinted invite. */
    .status-avatar[data-self="1"] .status-bubble.is-empty { background:var(--color-brand-50);
        border-color:var(--color-brand-300); border-style:dashed; }
    .status-avatar[data-self="1"] .status-bubble.is-empty .status-bubble-text { color:var(--color-brand-700); font-weight:700; }
    .status-avatar[data-self="1"] .status-bubble.is-empty::after { border-top-color:var(--color-brand-50); }
    .status-avatar[data-self="0"] .status-bubble.is-empty { display:none; }
    html.dark .status-bubble { background:#232a1c; border-color:#3a4a2c; }
    html.dark .status-bubble-text { color:#dbe6cf; }
    html.dark .status-bubble::after { border-top-color:#232a1c; }
    html.dark .status-avatar[data-self="1"] .status-bubble.is-empty { background:#1c2a12; border-color:#3a5a1c; }
    html.dark .status-avatar[data-self="1"] .status-bubble.is-empty .status-bubble-text { color:#a7d977; }
    html.dark .status-avatar[data-self="1"] .status-bubble.is-empty::after { border-top-color:#1c2a12; }
</style>
@endpush

@include('community.connect.partials.connect-js')
@push('scripts')
@include('community.partials.emoji-js')
<script>
document.getElementById('profileTabs')?.addEventListener('click', (e) => {
    const btn = e.target.closest('.profile-tab');
    if (!btn) return;
    const tab = btn.getAttribute('data-tab');
    document.querySelectorAll('#profileTabs .profile-tab').forEach((b) => {
        const on = b === btn;
        b.classList.toggle('is-active', on);
        b.setAttribute('aria-selected', on ? 'true' : 'false');
    });
    document.querySelectorAll('[data-tab-panel]').forEach((p) => {
        p.classList.toggle('hidden', p.getAttribute('data-tab-panel') !== tab);
    });
});

@if ($isSelf)
{{-- Status composer modal — open from the thought bubble, save/clear to the API. --}}
(function () {
    const bubble = document.getElementById('statusBubble');
    const modal = document.getElementById('statusModal');
    const input = document.getElementById('statusInput');
    if (!bubble || !modal || !input) return;
    const bubbleText = bubble.querySelector('.status-bubble-text') || bubble;
    const EMPTY_LABEL = "💭 What's on your mind?";
    const MAXLEN = 60; // keep the bubble to one tidy line; server enforces the same.
    const countEl = document.getElementById('statusCount');
    const updateCount = () => { if (countEl) countEl.textContent = input.value.length + '/' + MAXLEN; };
    input.addEventListener('input', updateCount);

    const open = () => {
        input.value = bubble.classList.contains('is-empty') ? '' : bubbleText.textContent.trim();
        updateCount();
        modal.classList.remove('hidden');
        void modal.offsetWidth;
        modal.classList.add('is-open');
        document.body.style.overflow = 'hidden';
        window.smFocus(input, { delay: 60 });
    };
    const close = () => {
        modal.classList.remove('is-open');
        document.body.style.overflow = '';
        setTimeout(() => modal.classList.add('hidden'), 250);
    };

    bubble.addEventListener('click', open);
    bubble.addEventListener('keydown', (e) => { if (e.key === 'Enter' || e.key === ' ') { e.preventDefault(); open(); } });
    modal.addEventListener('click', (e) => { if (e.target.closest('[data-close-status]')) close(); });
    document.addEventListener('keydown', (e) => { if (e.key === 'Escape' && !modal.classList.contains('hidden')) close(); });

    async function save(val) {
        try {
            const res = await fetch(@json(route('community.status.update')), {
                method: 'POST',
                headers: { 'X-CSRF-TOKEN': document.querySelector('meta[name=csrf-token]').content, 'Content-Type': 'application/json', Accept: 'application/json' },
                body: JSON.stringify({ statusBubble: val }),
            });
            const data = await res.json();
            if (data.success) {
                if (val) { bubbleText.textContent = val; bubble.classList.remove('is-empty'); }
                else { bubbleText.textContent = EMPTY_LABEL; bubble.classList.add('is-empty'); }
                close();
                if (window.toast) toast(data.message);
            } else if (window.toast) toast(data.message || 'Could not update.', 'error');
        } catch (_) { if (window.toast) toast('Network error — try again.', 'error'); }
    }

    document.getElementById('statusSave')?.addEventListener('click', () => save(input.value.trim().slice(0, MAXLEN)));
    input.addEventListener('keydown', (e) => { if (e.key === 'Enter') { e.preventDefault(); save(input.value.trim().slice(0, MAXLEN)); } });
    document.getElementById('statusClear')?.addEventListener('click', () => save(''));
})();

// Profile photo album — upload + delete (self only).
(function () {
    const input = document.getElementById('profilePhotoInput');
    const grid = document.getElementById('profilePhotosGrid');
    const empty = document.getElementById('profilePhotosEmpty');
    const tabBtn = document.querySelector('#profileTabs .profile-tab[data-tab="photos"]');
    const csrf = () => document.querySelector('meta[name=csrf-token]').content;
    function bumpCount(delta) {
        const s = tabBtn && tabBtn.querySelector('span');
        if (!s) return;
        const n = Math.max(0, (parseInt(s.textContent.replace(/\D/g, ''), 10) || 0) + delta);
        s.textContent = '(' + n + ')';
    }
    input && input.addEventListener('change', async () => {
        if (!input.files.length) return;
        const fd = new FormData();
        [...input.files].forEach((f) => fd.append('photos[]', f));
        input.value = '';
        try {
            const res = await fetch(@json(route('community.profile.photos.store')), { method: 'POST', headers: { 'X-CSRF-TOKEN': csrf(), Accept: 'application/json' }, body: fd });
            const data = await res.json();
            if (data.success) {
                grid.insertAdjacentHTML('afterbegin', data.data.html);
                grid.classList.remove('hidden');
                empty && empty.classList.add('hidden');
                bumpCount((data.data.html.match(/profile-photo-tile/g) || []).length);
                if (window.toast) toast(data.message);
            } else if (window.toast) toast(data.message || 'Could not upload.', 'error');
        } catch (_) { if (window.toast) toast('Network error — try again.', 'error'); }
    });
    document.addEventListener('click', async (e) => {
        const btn = e.target.closest('.js-photo-delete');
        if (!btn) return;
        const ok = (typeof confirmAction === 'function')
            ? await confirmAction({ title: 'Delete photo?', message: 'This removes it from your album.', confirmText: 'Delete' })
            : confirm('Delete this photo?');
        if (!ok) return;
        try {
            const res = await fetch('/app/community/profile/photos/' + btn.getAttribute('data-photo-id'), { method: 'DELETE', headers: { 'X-CSRF-TOKEN': csrf(), Accept: 'application/json' } });
            const data = await res.json();
            if (data.success) {
                btn.closest('.profile-photo-tile')?.remove();
                bumpCount(-1);
                if (!grid.querySelector('.profile-photo-tile')) { grid.classList.add('hidden'); empty && empty.classList.remove('hidden'); }
                if (window.toast) toast(data.message);
            } else if (window.toast) toast(data.message, 'error');
        } catch (_) { if (window.toast) toast('Network error — try again.', 'error'); }
    });
})();

// Profile video album — upload (compressed server-side) + delete (self only).
(function () {
    const input = document.getElementById('profileVideoInput');
    const grid = document.getElementById('profileVideosGrid');
    const empty = document.getElementById('profileVideosEmpty');
    const uploading = document.getElementById('profileVideoUploading');
    const addBtn = document.getElementById('profileVideoAddBtn');
    const tabBtn = document.querySelector('#profileTabs .profile-tab[data-tab="videos"]');
    const csrf = () => document.querySelector('meta[name=csrf-token]').content;
    const MAX = 300 * 1024 * 1024; // 300 MB, matches the server cap.

    function bumpCount(delta) {
        const s = tabBtn && tabBtn.querySelector('span');
        if (!s) return;
        const n = Math.max(0, (parseInt(s.textContent.replace(/\D/g, ''), 10) || 0) + delta);
        s.textContent = '(' + n + ')';
    }
    function setBusy(on) {
        uploading && uploading.classList.toggle('hidden', !on);
        if (addBtn) { addBtn.classList.toggle('opacity-50', on); addBtn.classList.toggle('pointer-events-none', on); }
    }

    input && input.addEventListener('change', async () => {
        const file = input.files && input.files[0];
        if (!file) return;
        if (!/^video\//.test(file.type) && !/\.(mp4|mov|webm|mkv|avi|3gp|m4v)$/i.test(file.name)) {
            if (window.toast) toast('Please choose a video file.', 'error'); input.value = ''; return;
        }
        if (file.size > MAX) {
            if (window.toast) toast('Video is larger than 300 MB.', 'error'); input.value = ''; return;
        }
        const fd = new FormData();
        fd.append('video', file);
        input.value = '';
        setBusy(true);
        try {
            const res = await fetch(@json(route('community.profile.videos.store')), { method: 'POST', headers: { 'X-CSRF-TOKEN': csrf(), Accept: 'application/json' }, body: fd });
            const data = await res.json();
            if (data.success) {
                grid.insertAdjacentHTML('afterbegin', data.data.html);
                grid.classList.remove('hidden');
                empty && empty.classList.add('hidden');
                bumpCount(1);
                if (window.toast) toast(data.message);
            } else if (window.toast) toast(data.message || 'Could not upload.', 'error');
        } catch (_) { if (window.toast) toast('Network error — try again.', 'error'); }
        finally { setBusy(false); }
    });

    document.addEventListener('click', async (e) => {
        const btn = e.target.closest('.js-video-delete');
        if (!btn) return;
        const ok = (typeof confirmAction === 'function')
            ? await confirmAction({ title: 'Delete video?', message: 'This removes it from your album.', confirmText: 'Delete' })
            : confirm('Delete this video?');
        if (!ok) return;
        try {
            const res = await fetch('/app/community/profile/videos/' + btn.getAttribute('data-video-id'), { method: 'DELETE', headers: { 'X-CSRF-TOKEN': csrf(), Accept: 'application/json' } });
            const data = await res.json();
            if (data.success) {
                btn.closest('.profile-video-tile')?.remove();
                bumpCount(-1);
                if (!grid.querySelector('.profile-video-tile')) { grid.classList.add('hidden'); empty && empty.classList.remove('hidden'); }
                if (window.toast) toast(data.message);
            } else if (window.toast) toast(data.message, 'error');
        } catch (_) { if (window.toast) toast('Network error — try again.', 'error'); }
    });
})();
@endif
</script>
@endpush
