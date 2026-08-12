@extends('layouts.app')

@section('title', 'Discussions — Community')
@section('page-title', 'Community')
@section('page-subtitle', 'Talk crops with other farmers')
@section('back', route('community.index'))

@php use App\Support\CommunityAvatar; @endphp

@push('head')
@include('community.partials.plaza-css')
@endpush

@section('content')
@include('community.partials.nav', ['active' => 'groups'])

<div class="flex items-center justify-between gap-3 mb-4">
    <p class="text-sm text-gray-500">Sali ka sa usapan — post questions, share what works.</p>
    <button type="button" id="createGroupBtn" class="btn btn-primary btn-sm shrink-0">
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
        @foreach ($groups as $g)
            @php $hue = CommunityAvatar::hue($g->name); @endphp
            <div class="card card-hover flex flex-col overflow-hidden" data-group-card="{{ $g->id }}">
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
                    <div class="flex items-center gap-2 mt-auto">
                        <a href="{{ route('community.groups.show', ['id' => $g->id]) }}"
                           class="btn btn-white flex-1 btn-open {{ $g->joined ? 'is-promoted' : '' }}">Open</a>
                        <button type="button" class="btn btn-primary shrink-0 group-join-btn {{ $g->joined ? 'hidden' : '' }}" data-group-id="{{ $g->id }}">Join</button>
                    </div>
                </div>
            </div>
        @endforeach
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

    // Join from a card: the Join button folds away while Open turns green —
    // the primary color visibly travels from one to the other.
    document.addEventListener('click', async (e) => {
        const btn = e.target.closest('.group-join-btn');
        if (!btn || btn.dataset.busy) return;
        btn.dataset.busy = '1';
        const id = btn.getAttribute('data-group-id');
        const card = btn.closest('[data-group-card]');
        btn.style.opacity = '.6';
        try {
            const res = await fetch(`/app/community/groups/${id}/join`, { method: 'POST', headers: jsonHeaders });
            const data = await res.json();
            if (data.success) {
                toast(data.message);
                btn.textContent = '✓';
                btn.style.opacity = '';
                const finish = () => {
                    btn.classList.add('hidden');
                    card.querySelector('.group-joined-tag')?.classList.remove('hidden');
                };
                if (reduceMotion) { finish(); card.querySelector('.btn-open')?.classList.add('is-promoted'); }
                else {
                    setTimeout(() => {
                        btn.classList.add('is-morphing');
                        card.querySelector('.btn-open')?.classList.add('is-promoted');
                        btn.addEventListener('transitionend', finish, { once: true });
                        setTimeout(finish, 500);   // safety if transitionend is missed
                    }, 300);
                }
            } else { toast(data.message, 'error'); btn.style.opacity = ''; }
        } catch (_) { toast('Network error — try again.', 'error'); btn.style.opacity = ''; }
        finally { delete btn.dataset.busy; }
    });
});
</script>
@endpush
