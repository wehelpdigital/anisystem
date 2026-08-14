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
            {{-- The band the owner dragged into place, not the middle the
                 browser would have guessed. --}}
            <div class="-mx-5 -mt-5 mb-4 h-32 sm:h-44 rounded-t-xl bg-gray-100 bg-cover bg-no-repeat"
                 style="background-image:url('{{ \App\Support\MediaStore::url($member->coverPath) }}'); background-position: 50% {{ (int) ($member->coverPos ?? 50) }}%"></div>
        @endif
        <div class="flex items-start gap-4">
            <span class="status-avatar inline-block shrink-0 relative" style="width:4rem;height:4rem;" data-self="{{ $isSelf ? 1 : 0 }}">
                @include('community.partials.avatar', ['user' => $member, 'size' => 'avatar-lg', 'link' => false, 'showOnline' => true])
                {{-- Thought bubble floating over the profile pic. --}}
                <span class="status-bubble {{ filled($member->statusBubble) ? '' : 'is-empty' }}" id="statusBubble"
                      @if ($isSelf) role="button" tabindex="0" title="Set your status" data-status-bubble @endif><span class="status-bubble-text" @if ($isSelf) data-status-text @endif>{{ $member->statusBubble ?: ($isSelf ? "💭 What's on your mind?" : '') }}</span></span>
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
    @include('community.partials.status-modal')
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


</script>
@endpush
