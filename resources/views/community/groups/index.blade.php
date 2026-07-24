@extends('layouts.app')

@section('title', 'Groups — Community')
@section('page-title', 'Groups')
@section('page-subtitle', 'Talk crops with other farmers')
@section('back', route('community.index'))

@section('content')
<div class="flex items-center justify-between gap-3 mb-4">
    <p class="text-sm text-gray-500">Join a group to post topics and reply.</p>
    <button type="button" id="createGroupBtn" class="btn btn-primary btn-sm shrink-0">
        <svg class="w-4 h-4" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" d="M12 5v14m-7-7h14"/></svg>
        New Group
    </button>
</div>

@if ($groups->isEmpty())
    <div class="card">
        <div class="card-body text-center py-14">
            <div class="mx-auto w-16 h-16 rounded-2xl bg-brand-50 flex items-center justify-center mb-4">
                <svg class="w-8 h-8 text-brand-600" fill="none" stroke="currentColor" stroke-width="1.8" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" d="M17 20h5v-1a4 4 0 00-4-4h-1M9 11a4 4 0 100-8 4 4 0 000 8zm8 0a3 3 0 100-6M2 20v-1a5 5 0 015-5h4a5 5 0 015 5v1H2z"/></svg>
            </div>
            <h2 class="text-lg font-bold text-gray-900 mb-1">No groups yet</h2>
            <p class="text-sm text-gray-500 mb-5">Start the first one and invite fellow growers to talk.</p>
        </div>
    </div>
@else
    <div class="grid gap-3 sm:grid-cols-2 stagger-children" id="groupsGrid">
        @foreach ($groups as $g)
            <div class="card card-hover flex flex-col" data-group-card="{{ $g->id }}">
                <div class="card-body flex flex-col grow">
                    <a href="{{ route('community.groups.show', ['id' => $g->id]) }}" class="min-w-0">
                        <h3 class="font-bold text-gray-900 leading-snug">{{ $g->name }}</h3>
                    </a>
                    @if ($g->description)
                        <p class="text-sm text-gray-500 mt-1 line-clamp-2">{{ $g->description }}</p>
                    @endif
                    <div class="flex items-center gap-4 text-xs text-gray-500 font-medium mt-3 mb-3">
                        <span>{{ $g->member_count }} {{ \Illuminate\Support\Str::plural('member', $g->member_count) }}</span>
                        <span>{{ $g->post_count }} {{ \Illuminate\Support\Str::plural('post', $g->post_count) }}</span>
                    </div>
                    <div class="flex items-center gap-2 mt-auto">
                        <a href="{{ route('community.groups.show', ['id' => $g->id]) }}" class="btn btn-white flex-1">Open</a>
                        <button type="button" class="btn btn-primary shrink-0 group-join-btn {{ $g->joined ? 'hidden' : '' }}" data-group-id="{{ $g->id }}">Join</button>
                        <span class="badge badge-green shrink-0 group-joined-tag {{ $g->joined ? '' : 'hidden' }}" data-group-id="{{ $g->id }}">Joined</span>
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
        <h3 class="sheet-title">New group</h3>
        <button type="button" data-sheet-close class="btn-ghost p-2 rounded-full" aria-label="Close">✕</button>
    </div>
    <div class="sheet-body space-y-3">
        <div>
            <label class="form-label" for="groupName">Group name</label>
            <input type="text" id="groupName" class="form-input" maxlength="150" placeholder="e.g. Rice Growers of Central Luzon">
        </div>
        <div>
            <label class="form-label" for="groupDesc">Description <span class="text-gray-400 font-normal">(optional)</span></label>
            <textarea id="groupDesc" class="form-textarea" rows="3" maxlength="500" placeholder="What's this group about?"></textarea>
        </div>
    </div>
    <div class="sheet-footer">
        <button type="button" class="btn btn-ghost" data-sheet-close>Cancel</button>
        <button type="button" class="btn btn-primary" id="createGroupSave">Create group</button>
    </div>
</div>
@endsection

@push('scripts')
<script>
document.addEventListener('DOMContentLoaded', () => {
    const CSRF = document.querySelector('meta[name=csrf-token]').content;
    const jsonHeaders = { 'X-CSRF-TOKEN': CSRF, 'Content-Type': 'application/json', Accept: 'application/json' };

    document.getElementById('createGroupBtn')?.addEventListener('click', () => openSheet('createGroupSheet'));

    document.getElementById('createGroupSave')?.addEventListener('click', async (e) => {
        const name = document.getElementById('groupName').value.trim();
        const description = document.getElementById('groupDesc').value.trim();
        if (!name) { toast('Give your group a name.', 'error'); return; }
        e.currentTarget.disabled = true;
        try {
            const res = await fetch(@json(route('community.groups.store')), { method: 'POST', headers: jsonHeaders, body: JSON.stringify({ name, description }) });
            const data = await res.json();
            if (data.success) { toast(data.message); window.location = data.data.url; }
            else toast(data.message || 'Could not create group.', 'error');
        } catch (_) { toast('Network error — try again.', 'error'); }
        finally { e.currentTarget.disabled = false; }
    });

    // Join from a card.
    document.addEventListener('click', async (e) => {
        const btn = e.target.closest('.group-join-btn');
        if (!btn) return;
        const id = btn.getAttribute('data-group-id');
        btn.disabled = true;
        try {
            const res = await fetch(`/app/community/groups/${id}/join`, { method: 'POST', headers: jsonHeaders });
            const data = await res.json();
            if (data.success) {
                toast(data.message);
                btn.classList.add('hidden');
                document.querySelector(`.group-joined-tag[data-group-id="${id}"]`)?.classList.remove('hidden');
            } else toast(data.message, 'error');
        } catch (_) { toast('Network error — try again.', 'error'); }
        finally { btn.disabled = false; }
    });
});
</script>
@endpush
