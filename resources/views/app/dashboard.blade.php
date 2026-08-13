@extends('layouts.app')

@section('title', 'Dashboard')
@section('page-title', 'Dashboard')
@section('page-subtitle', 'Your farm at a glance')

{{-- Reuse the shared community design system so the avatars / hues on this
     page match the plaza exactly (extend the partial, don't reinvent). --}}
@push('head')
@include('community.partials.plaza-css')
<style>
    /* Feed + sidebar shell (mirrors the wall page): the co-farmer feed is the
       main column, AI + discussions ride a sticky rail on wide screens and fold
       below the feed on tablet/mobile. */
    .dash-shell { display: grid; grid-template-columns: 1fr; gap: 1.25rem; align-items: start; }
    @media (min-width: 1024px) {
        .dash-shell { grid-template-columns: minmax(0, 1fr) 20rem; }
        .dash-side { position: sticky; top: 5rem; }
    }
    /* Weather: shimmering skeleton while it loads, then the forecast fades up. */
    :root { --wx-sk1: #edf0f3; --wx-sk2: #dfe4ea; }
    html.dark { --wx-sk1: #1b2417; --wx-sk2: #27331d; }
    .wx-loading { margin-top: .5rem; padding-top: .5rem; border-top: 1px solid var(--color-gray-100); }
    html.dark .wx-loading { border-color: #2b3a1c; }
    .wx-skel { background: linear-gradient(90deg, var(--wx-sk1) 25%, var(--wx-sk2) 50%, var(--wx-sk1) 75%);
        background-size: 200% 100%; border-radius: .5rem; animation: wxShimmer 1.3s linear infinite; }
    .wx-skel-line { height: .72rem; width: 45%; border-radius: 999px; margin-bottom: .55rem; }
    .wx-skel-row { display: flex; gap: .3rem; }
    .wx-skel-cell { flex: 1 1 0; height: 3.7rem; }
    @keyframes wxShimmer { from { background-position: 200% 0; } to { background-position: -200% 0; } }
    .dash-wx.wx-ready > * { animation: wxReveal .45s cubic-bezier(.22, 1, .36, 1) both; }
    @keyframes wxReveal { from { opacity: 0; transform: translateY(7px); } to { opacity: 1; transform: none; } }
    @media (prefers-reduced-motion: reduce) {
        .wx-skel { animation: none; opacity: .6; }
        .dash-wx.wx-ready > * { animation: none; }
    }
    /* "Today" weather cell — no box, just green text (the theme doesn't remap
       the green ramp, so both modes are set explicitly here). */
    .wx-today-label { color: #15803d; }
    html.dark .wx-today-label { color: #86efac; }
    {{-- .wall-act composer buttons live in plaza-css (shared). --}}
</style>
@endpush

@section('content')
@php
    $isSuperAdmin = $user->isSuperAdmin();   // mother-site admin — full access, no plan needed
    $status = $subscription?->effective_status;
    $daysRemaining = $subscription?->daysRemaining();
    $isActive = $status === \App\Models\Subscription::STATUS_ACTIVE;
    $expiringSoon = $isActive && $daysRemaining !== null && $daysRemaining <= 7;

    $scheduleBadge = fn (?string $s) => match ($s) {
        'draft' => ['badge-gray', 'Draft'],
        'setup' => ['badge-yellow', 'Setting up'],
        'generated' => ['badge-green', 'Generated'],
        'completed' => ['badge-blue', 'Completed'],
        'archived' => ['badge-gray', 'Archived'],
        default => ['badge-gray', ucfirst((string) $s)],
    };

    $snippet = fn (?string $body, int $len = 90) => \Illuminate\Support\Str::limit(strip_tags((string) $body), $len);
@endphp

<div class="space-y-5 md:space-y-6">

    {{-- Greeting card --}}
    <div class="card overflow-hidden">
        <div class="card-body bg-gradient-to-br from-brand-600 to-brand-800 !rounded-2xl text-white">
            <div class="flex items-start justify-between gap-3">
                <div class="min-w-0">
                    <h2 class="text-xl md:text-2xl font-bold">Magandang araw, {{ $user->firstName }}!</h2>
                    <p class="text-sm text-brand-100 mt-1">Ready to plan a productive cropping season?</p>
                </div>
                <div class="shrink-0 text-right">
                    @if ($isSuperAdmin)
                        <span class="inline-flex items-center gap-1.5 rounded-full bg-white/15 text-white px-3 py-1.5 text-xs font-bold">🛡️ Admin access</span>
                    @elseif ($isActive)
                        <span class="inline-flex items-center gap-1.5 rounded-full px-3 py-1.5 text-xs font-bold {{ $expiringSoon ? 'bg-accent-500 text-ink' : 'bg-white/15 text-white' }}">
                            @if ($expiringSoon)
                                ⚠️ {{ $daysRemaining }} {{ \Illuminate\Support\Str::plural('day', (int) $daysRemaining) }} left
                            @else
                                {{ $daysRemaining }} {{ \Illuminate\Support\Str::plural('day', (int) $daysRemaining) }} left
                            @endif
                        </span>
                    @elseif ($status === 'pending')
                        <span class="inline-flex items-center rounded-full bg-accent-500 text-ink px-3 py-1.5 text-xs font-bold">Verification pending</span>
                    @else
                        <a href="{{ route('purchase.plans') }}" class="inline-flex items-center rounded-full bg-accent-500 text-ink px-3 py-1.5 text-xs font-bold hover:bg-accent-600 transition">Subscribe now</a>
                    @endif
                </div>
            </div>
            @if ($expiringSoon)
                <a href="{{ route('purchase.plans') }}" class="mt-3 inline-flex items-center gap-1 text-sm font-semibold text-accent-300 hover:text-accent-400">
                    Renew your subscription before it expires
                    <svg class="w-4 h-4" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" d="M9 5l7 7-7 7"/></svg>
                </a>
            @endif
        </div>
    </div>

    {{-- Stat tiles --}}
    <div class="grid grid-cols-3 gap-3 md:gap-4">
        <div class="card">
            <div class="card-body !p-3.5 md:!p-5 text-center">
                <p class="text-2xl md:text-3xl font-extrabold text-brand-700">{{ number_format($scheduleCount) }}</p>
                <p class="text-[11px] md:text-sm font-semibold text-gray-500 mt-0.5">My Schedules</p>
            </div>
        </div>
        <div class="card">
            <div class="card-body !p-3.5 md:!p-5 text-center">
                <p class="text-sm md:text-lg font-extrabold text-gray-900 leading-tight pt-1.5 md:pt-2 truncate">
                    {{ $isSuperAdmin ? 'Admin' : ($isActive ? $subscription->planName : '—') }}
                </p>
                <p class="text-[11px] md:text-sm font-semibold text-gray-500 mt-1">Active Plan</p>
            </div>
        </div>
        <div class="card">
            <div class="card-body !p-3.5 md:!p-5 text-center">
                <p class="text-2xl md:text-3xl font-extrabold {{ $expiringSoon ? 'text-orange-600' : 'text-brand-700' }}">
                    {{ $isSuperAdmin ? '∞' : ($isActive && $daysRemaining !== null ? number_format($daysRemaining) : '—') }}
                </p>
                <p class="text-[11px] md:text-sm font-semibold text-gray-500 mt-0.5">Days Remaining</p>
            </div>
        </div>
    </div>

    {{-- My Cropping Schedules (top — the primary workspace) --}}
    <div>
        <div class="flex items-center justify-between gap-3 mb-3 px-1">
            <h2 class="text-base md:text-lg font-bold text-gray-900">🌾 My Cropping Schedules</h2>
            @if ($latestSchedules->isNotEmpty())
                <a href="{{ route('sm.index') }}" class="text-sm font-bold text-brand-700 hover:underline shrink-0">View all</a>
            @endif
        </div>

        @if ($latestSchedules->isEmpty())
            <div class="card">
                <div class="card-body text-center py-10 md:py-14">
                    <svg class="w-24 h-24 mx-auto mb-4 text-brand-300" fill="none" stroke="currentColor" stroke-width="1.3" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" d="M12 21v-8"/>
                        <path stroke-linecap="round" stroke-linejoin="round" d="M12 13c0-3 2-5.5 5.5-5.5.2 2.8-1.6 5.5-5.5 5.5z"/>
                        <path stroke-linecap="round" stroke-linejoin="round" d="M12 13c0-3-2-5.5-5.5-5.5C6.3 10.3 8.1 13 12 13z"/>
                        <path stroke-linecap="round" stroke-linejoin="round" d="M4 21h16"/>
                    </svg>
                    @if ($scheduleCount > 0)
                        <h3 class="text-lg font-bold text-gray-900">No active schedules</h3>
                        <p class="text-sm text-gray-500 mt-1 max-w-sm mx-auto">
                            None of your schedules have activities within 6 months of today. Open all schedules to review or update them.
                        </p>
                        <div class="flex flex-wrap items-center justify-center gap-2 mt-5">
                            <a href="{{ route('sm.index') }}" class="btn btn-white btn-lg">View all schedules</a>
                            <a href="{{ route('sm.create') }}" class="btn btn-primary btn-lg">+ New Schedule</a>
                        </div>
                    @else
                        <h3 class="text-lg font-bold text-gray-900">Plant your first schedule</h3>
                        <p class="text-sm text-gray-500 mt-1 max-w-xs mx-auto">
                            Create a cropping schedule to plan your lots, workers, activities and irrigation for the season.
                        </p>
                        <a href="{{ route('sm.create') }}" class="btn btn-primary btn-lg mt-5">+ New Cropping Schedule</a>
                    @endif
                </div>
            </div>
        @else
            {{-- One active schedule fills the row; two or more go side by side. --}}
            <div class="grid gap-3 {{ $latestSchedules->count() > 1 ? 'sm:grid-cols-2' : 'grid-cols-1' }}">
                @foreach ($latestSchedules as $schedule)
                    @php
                        [$sBadge, $sLabel] = $scheduleBadge($schedule->status);
                        $next = $scheduleNext[$schedule->id] ?? null;
                    @endphp
                    <div class="card card-hover">
                        <div class="card-body !p-4 flex flex-col gap-3 h-full">
                            <h3 class="font-bold text-gray-900 leading-snug">{{ $schedule->title }}</h3>

                            {{-- What's next for THIS schedule: today's activities, or
                                 the nearest upcoming day's. --}}
                            @if ($next)
                                <a href="{{ route('sm.activities', ['id' => $schedule->id]) }}"
                                   class="block rounded-xl border px-3 py-2.5 transition hover:shadow-sm {{ $next['isToday'] ? 'border-brand-200 bg-brand-50/70 hover:bg-brand-50' : 'border-gray-100 bg-gray-50/70 hover:bg-gray-50' }}">
                                    <div class="flex items-center gap-1.5 mb-1">
                                        <span class="text-xs font-bold {{ $next['isToday'] ? 'text-brand-700' : 'text-gray-600' }}">
                                            {{ $next['isToday'] ? '📅 Due today' : '📅 ' . $next['date']->format('D, M j') }}
                                        </span>
                                        @unless ($next['isToday'])
                                            <span class="text-[11px] font-semibold text-gray-400">· in {{ $next['daysAway'] }} {{ \Illuminate\Support\Str::plural('day', $next['daysAway']) }}</span>
                                        @endunless
                                    </div>
                                    <ul class="space-y-1">
                                        @foreach ($next['activities']->take(2) as $act)
                                            <li class="flex items-start gap-2 text-sm text-gray-800 leading-snug">
                                                <span class="w-1.5 h-1.5 rounded-full {{ $next['isToday'] ? 'bg-brand-500' : 'bg-gray-400' }} mt-1.5 shrink-0"></span>
                                                <span class="min-w-0 line-clamp-1 font-medium">{{ $act->activityTitle }}</span>
                                            </li>
                                        @endforeach
                                    </ul>
                                    @if ($next['moreCount'] > 0)
                                        <p class="text-[11px] font-semibold text-gray-400 mt-1 pl-3.5">+{{ $next['moreCount'] }} more that day</p>
                                    @endif
                                </a>
                            @else
                                <p class="text-xs text-gray-400 italic">No upcoming activities</p>
                            @endif

                            {{-- Local weather for this schedule's lot location(s).
                                 Filled by JS from the deduped /app/weather feed. --}}
                            @if ($scheduleHasLocation[$schedule->id] ?? false)
                                <div data-weather-for="{{ $schedule->id }}" class="dash-wx">
                                    <div class="wx-loading" role="status" aria-label="Loading">
                                        <div class="wx-skel wx-skel-line"></div>
                                        <div class="wx-skel-row">
                                            <div class="wx-skel wx-skel-cell"></div>
                                            <div class="wx-skel wx-skel-cell"></div>
                                            <div class="wx-skel wx-skel-cell"></div>
                                            <div class="wx-skel wx-skel-cell"></div>
                                            <div class="wx-skel wx-skel-cell"></div>
                                        </div>
                                    </div>
                                </div>
                            @else
                                <a href="{{ route('sm.lots', ['id' => $schedule->id]) }}" class="inline-flex items-center gap-1 text-[11px] font-semibold text-brand-600 hover:text-brand-700">
                                    <svg class="w-3.5 h-3.5" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" d="M17.657 16.657L13.414 20.9a2 2 0 01-2.827 0l-4.244-4.243a8 8 0 1111.314 0z"/><path stroke-linecap="round" stroke-linejoin="round" d="M15 11a3 3 0 11-6 0 3 3 0 016 0z"/></svg>
                                    Add a lot address for local weather
                                </a>
                            @endif

                            <div class="flex items-center justify-between gap-3 mt-auto pt-1">
                                <div class="min-w-0 flex items-center gap-2 flex-wrap">
                                    <p class="text-xs text-gray-500 shrink-0">Created {{ $schedule->created_at?->format('M j, Y') }}</p>
                                    <span class="badge {{ $sBadge }} shrink-0">{{ $sLabel }}</span>
                                </div>
                                <a href="{{ route('sm.hub', ['id' => $schedule->id]) }}" class="btn btn-outline btn-sm shrink-0">Open</a>
                            </div>
                        </div>
                    </div>
                @endforeach
            </div>
            <a href="{{ route('sm.create') }}" class="btn btn-primary btn-lg w-full mt-4">+ New Cropping Schedule</a>
        @endif
    </div>

    {{-- ===================== Feed + sidebar shell ===================== --}}
    <div class="dash-shell">

        {{-- MAIN COLUMN: support (if any) + your co-farmers' wall feed --}}
        <div class="min-w-0 space-y-5 md:space-y-6">

            @if ($openTickets->isNotEmpty())
                <section class="card">
                    <div class="card-body !p-4">
                        <div class="flex items-center justify-between gap-2 mb-2">
                            <h2 class="text-sm md:text-base font-bold text-gray-900">🛟 Open support tickets</h2>
                            <a href="{{ route('support.index') }}" class="text-xs font-semibold text-brand-600 hover:text-brand-700 shrink-0">All tickets →</a>
                        </div>
                        <div class="divide-y divide-gray-100">
                            @foreach ($openTickets as $ticket)
                                @php $tAnswered = $ticket->status === 'answered'; $tWhen = $ticket->lastReplyAt ?? $ticket->created_at; @endphp
                                <a href="{{ route('support.show', ['id' => $ticket->id]) }}" class="block px-1 py-2.5 hover:bg-gray-50 transition">
                                    <div class="flex items-start justify-between gap-2">
                                        <p class="font-semibold text-gray-900 text-sm leading-snug min-w-0 line-clamp-1">{{ $ticket->subject }}</p>
                                        <span class="badge {{ $tAnswered ? 'badge-green' : 'badge-yellow' }} shrink-0">{{ $tAnswered ? 'Answered' : 'Open' }}</span>
                                    </div>
                                    <p class="text-xs text-gray-400 mt-0.5">
                                        @if ($ticket->category)<span class="font-semibold text-gray-500">{{ ucfirst($ticket->category) }}</span> · @endif
                                        Last reply {{ optional($tWhen)->diffForHumans() }}
                                    </p>
                                </a>
                            @endforeach
                        </div>
                    </div>
                </section>
            @endif

            <div>
                <div class="flex items-center justify-between gap-3 mb-3 px-1">
                    <h2 class="text-base md:text-lg font-bold text-gray-900">🌾 Community Wall</h2>
                    <a href="{{ route('community.index') }}" class="text-sm font-bold text-brand-700 hover:underline shrink-0">See more</a>
                </div>

                {{-- Post to your wall — appears here, in /app/community and on your
                     profile wall (they're one wall). Supports photo + video. --}}
                <div id="dashComposer" data-video-host class="card mb-5">
                    <div class="card-body !p-3">
                        <div class="flex items-start gap-2.5">
                            <span class="avatar avatar-md {{ \App\Support\CommunityAvatar::hue(auth()->user()->full_name ?? '?') }} shrink-0">{{ auth()->user()->initials ?? '?' }}</span>
                            <div class="min-w-0 grow">
                                <textarea id="dashPostBody" data-mentionable data-preview="#dashPreview" rows="2" maxlength="5000" class="form-textarea w-full" placeholder="Share something with your co-farmers…"></textarea>
                                <div id="dashPreview" class="cp-preview" style="display:none"><span class="cp-label">Preview</span><div class="cp-body"></div></div>

                                <div id="dashImageChip" class="hidden mt-2 items-center gap-2 text-xs font-semibold text-gray-600">
                                    <img src="" id="dashImageThumb" class="w-10 h-10 rounded-lg object-cover" alt="">
                                    <button type="button" id="dashImageClear" class="text-red-600 font-bold">Remove photo</button>
                                </div>
                                <span class="js-video-chip mt-2 items-center gap-2 text-xs font-semibold text-gray-600" style="display:none">
                                    <span class="js-video-name"></span>
                                    <button type="button" class="js-video-clear text-red-600 font-bold">Remove</button>
                                </span>

                                <div class="flex items-center justify-between gap-2 mt-2 flex-wrap">
                                    <div class="flex items-center gap-1 flex-wrap">
                                        <label class="wall-act cursor-pointer" title="Add a photo" aria-label="Add a photo">
                                            <svg class="w-5 h-5 text-emerald-500 shrink-0" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" d="M4 16l4.586-4.586a2 2 0 012.828 0L16 16m-2-2l1.586-1.586a2 2 0 012.828 0L20 14m-6-6h.01M6 20h12a2 2 0 002-2V6a2 2 0 00-2-2H6a2 2 0 00-2 2v12a2 2 0 002 2z"/></svg>
                                            <input type="file" id="dashImage" accept="image/*" class="hidden">
                                        </label>
                                        <button type="button" class="wall-act js-video-attach" title="Upload a video" aria-label="Upload a video">
                                            <svg class="w-5 h-5 text-blue-500 shrink-0" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" d="M15 10l4.553-2.276A1 1 0 0121 8.618v6.764a1 1 0 01-1.447.894L15 14M5 18h8a2 2 0 002-2V8a2 2 0 00-2-2H5a2 2 0 00-2 2v8a2 2 0 002 2z"/></svg>
                                        </button>
                                        <button type="button" class="wall-act js-video-record" title="Record a video" aria-label="Record a video">
                                            <svg class="w-5 h-5 text-red-500 shrink-0" fill="none" viewBox="0 0 24 24"><circle cx="12" cy="12" r="9" stroke="currentColor" stroke-width="2"/><circle cx="12" cy="12" r="4.5" fill="currentColor"/></svg>
                                        </button>
                                        <input type="file" class="js-video-file hidden" accept="video/*">
                                        <button type="button" class="wall-act js-emoji-btn" data-target="dashPostBody" title="Add an emoji" aria-label="Add an emoji">
                                            <svg class="w-5 h-5 text-amber-500 shrink-0" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" d="M14.828 14.828a4 4 0 01-5.656 0M9 10h.01M15 10h.01M21 12a9 9 0 11-18 0 9 9 0 0118 0z"/></svg>
                                        </button>
                                    </div>
                                    <button type="button" id="dashPostBtn" class="btn btn-primary btn-sm shrink-0">Post</button>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>

                <div id="dashWallFeed" data-animate-list>
                    @foreach ($connectedWall as $post)
                        @include('community.partials.feed-post', ['post' => $post, 'friendIds' => $friendIds])
                    @endforeach
                </div>
                <a href="{{ route('community.index') }}" id="dashWallEmpty" class="card card-hover block {{ $connectedWall->isEmpty() ? '' : 'hidden' }}">
                    <div class="card-body text-center py-8">
                        <div class="text-3xl mb-2">🌱</div>
                        <h3 class="font-bold text-gray-900">Meet your co-farmers</h3>
                        <p class="text-sm text-gray-500 mt-1 max-w-sm mx-auto">Share an update above, or connect with other farmers to see their posts here.</p>
                    </div>
                </a>
            </div>

        </div>

        {{-- SIDEBAR: AI Technician + Latest Discussions --}}
        <aside class="dash-side space-y-4">

            @if ($canUseAi)
                <section class="card">
                    <div class="card-body !p-4">
                        <div class="flex items-center justify-between gap-2 mb-2">
                            <div class="flex items-baseline gap-2 min-w-0">
                                <h2 class="text-sm font-bold text-gray-900 shrink-0">🤖 AI Technician</h2>
                                <a href="{{ route('ai.credits') }}" class="text-xs font-semibold text-brand-600 hover:text-brand-700 whitespace-nowrap" title="Buy AI credits">⚡ {{ number_format((int) $aiBalance) }}</a>
                            </div>
                            <a href="{{ route('ai.index') }}" class="text-xs font-semibold text-brand-600 hover:text-brand-700 shrink-0">Open →</a>
                        </div>
                        @if ($recentChats->isEmpty())
                            <a href="{{ route('ai.index') }}" class="flex items-center gap-3 px-1 py-2 rounded-lg hover:bg-gray-50 transition">
                                <span class="text-2xl leading-none">💬</span>
                                <div class="min-w-0">
                                    <p class="font-bold text-gray-900 text-sm">Start a conversation</p>
                                    <p class="text-xs text-gray-500">Ask about pests, fertilizer, planting…</p>
                                </div>
                            </a>
                        @else
                            <div class="divide-y divide-gray-100">
                                @foreach ($recentChats as $chat)
                                    <a href="{{ route('ai.index', ['c' => $chat->id, 'scheduleId' => $chat->croppingScheduleId]) }}" class="flex items-start gap-2.5 px-1 py-2.5 hover:bg-gray-50 transition">
                                        <span class="text-lg leading-none mt-0.5">💬</span>
                                        <div class="min-w-0">
                                            <p class="font-semibold text-gray-900 text-sm leading-snug line-clamp-1">{{ $chat->title ?: 'Untitled chat' }}</p>
                                            <p class="text-xs text-gray-400">Updated {{ $chat->updated_at?->diffForHumans() }}</p>
                                        </div>
                                    </a>
                                @endforeach
                            </div>
                        @endif
                    </div>
                </section>
            @endif

            <section class="card">
                <div class="card-body !p-4">
                    <div class="flex items-center justify-between gap-2 mb-2">
                        <h2 class="text-sm font-bold text-gray-900">💬 Latest Discussions</h2>
                        <a href="{{ route('community.groups.index') }}" class="text-xs font-semibold text-brand-600 hover:text-brand-700 shrink-0">See more →</a>
                    </div>
                    <div class="divide-y divide-gray-100">
                        @forelse ($latestDiscussions as $d)
                            <a href="{{ route('community.groups.show', ['id' => $d->groupId]) }}" class="block px-1 py-2.5 hover:bg-gray-50 transition">
                                <p class="text-xs text-gray-400 leading-tight truncate">
                                    <span class="font-semibold text-brand-700">{{ optional($d->group)->name }}</span>
                                    · {{ optional($d->author)->full_name }}
                                </p>
                                @if ($d->title)
                                    <p class="font-bold text-gray-900 text-sm leading-snug mt-0.5 line-clamp-2">{{ $d->title }}</p>
                                @else
                                    <p class="text-sm text-gray-700 leading-snug mt-0.5 line-clamp-2">{{ $snippet($d->body) }}</p>
                                @endif
                                <p class="text-xs text-gray-400 mt-1">
                                    💬 {{ $d->replies?->count() ?? 0 }} {{ \Illuminate\Support\Str::plural('reply', $d->replies?->count() ?? 0) }}
                                    · {{ $d->created_at?->diffForHumans() }}
                                </p>
                            </a>
                        @empty
                            <a href="{{ route('community.groups.index') }}" class="block px-1 py-4 text-center text-sm text-gray-400 hover:text-brand-700">Join a discussion group →</a>
                        @endforelse
                    </div>
                </div>
            </section>

        </aside>

    </div>

    {{-- Latest from the Technician's Blog --}}
    @if (!empty($latestBlog) && $latestBlog->isNotEmpty())
        <div>
            <div class="flex items-center justify-between gap-3 mb-3 px-1">
                <h2 class="text-base md:text-lg font-bold text-gray-900">📰 From the Technician's Blog</h2>
                <a href="{{ route('community.blog') }}" class="text-xs font-semibold text-brand-600 hover:text-brand-700 shrink-0">See all →</a>
            </div>
            <div class="grid grid-cols-1 sm:grid-cols-3 gap-3">
                @foreach ($latestBlog as $article)
                    <a href="{{ route('community.blog.show', ['id' => $article->id]) }}" class="card card-hover overflow-hidden block">
                        <div style="aspect-ratio:16/9;background:linear-gradient(120deg,var(--color-brand-100),var(--color-brand-50));overflow:hidden;">
                            @if ($article->coverUrl())
                                <img src="{{ $article->coverUrl() }}" alt="" loading="lazy" class="w-full h-full object-cover">
                            @else
                                <div class="w-full h-full flex items-center justify-center text-3xl">🌾</div>
                            @endif
                        </div>
                        <div class="p-3">
                            <span class="font-bold text-gray-900 text-sm leading-tight block">{{ \Illuminate\Support\Str::limit($article->title, 60) }}</span>
                            @if ($article->publishedAt)<span class="text-xs text-gray-400">{{ $article->publishedAt->format('M j, Y') }}</span>@endif
                        </div>
                    </a>
                @endforeach
            </div>
        </div>
    @endif

</div>

@include('community.partials.wall-comments-modal')
@endsection

@push('scripts')
{{-- The co-farmer feed reuses the wall's post cards, so it needs the same
     community JS: reactions, comment form, emoji, photo, mentions, modal. --}}
@include('community.partials.emoji-js')
@include('community.partials.lightbox-js')
@include('community.partials.comment-tools-js')
@include('community.partials.react-js')
@include('community.partials.mention-js')
@include('community.partials.wall-comment-js')
@include('community.partials.video-js')
@include('community.partials.composer-preview-js')

{{-- Dashboard wall composer — posts to your own wall (the same wall shown in
     /app/community and on your profile). Photo + video (upload or record). --}}
<script>
(() => {
    const host = document.getElementById('dashComposer');
    if (!host) return;
    const body = document.getElementById('dashPostBody');
    const btn = document.getElementById('dashPostBtn');
    const feed = document.getElementById('dashWallFeed');
    const empty = document.getElementById('dashWallEmpty');
    const imageInput = document.getElementById('dashImage');
    const imgChip = document.getElementById('dashImageChip');
    const POST_URL = @json(route('community.wall.post', ['userId' => auth()->id()]));
    const csrf = () => document.querySelector('meta[name=csrf-token]')?.content || '';

    // Photo chip
    imageInput?.addEventListener('change', () => {
        const f = imageInput.files[0];
        if (f) { document.getElementById('dashImageThumb').src = URL.createObjectURL(f); imgChip.style.display = 'flex'; }
        else imgChip.style.display = 'none';
    });
    document.getElementById('dashImageClear')?.addEventListener('click', () => { imageInput.value = ''; imgChip.style.display = 'none'; });

    // Grow the textarea with content.
    body?.addEventListener('input', () => { body.style.height = 'auto'; body.style.height = Math.min(body.scrollHeight, 200) + 'px'; });

    btn?.addEventListener('click', async () => {
        const text = body.value.trim();
        const img = imageInput.files[0];
        const vid = window.plazaVideoFile ? window.plazaVideoFile(host) : null;
        if (!text && !img && !vid) { toast('Write something or add a photo/video.', 'error'); return; }
        const prev = btn.innerHTML;
        btn.disabled = true;
        btn.textContent = vid ? 'Uploading…' : 'Posting…';
        try {
            const fd = new FormData();
            if (text) fd.append('body', text);
            if (img) fd.append('image', img);
            if (vid) fd.append('video', vid);
            fd.append('render', 'feed');
            const res = await fetch(POST_URL, { method: 'POST', headers: { 'X-CSRF-TOKEN': csrf(), Accept: 'application/json' }, body: fd });
            const data = await res.json();
            if (!data.success) { toast(data.message || 'Could not post.', 'error'); return; }
            if (feed && data.data?.html) {
                feed.insertAdjacentHTML('afterbegin', data.data.html);
                const added = feed.firstElementChild;
                if (added) { added.classList.add('plaza-comment-enter'); added.addEventListener('animationend', () => added.classList.remove('plaza-comment-enter'), { once: true }); }
            }
            if (empty) empty.classList.add('hidden');
            body.value = ''; body.style.height = 'auto';
            imageInput.value = ''; imgChip.style.display = 'none';
            window.plazaClearVideo && window.plazaClearVideo(host);
            toast('Posted to your wall.');
        } catch (_) { toast('Network error — try again.', 'error'); }
        finally { btn.disabled = false; btn.innerHTML = prev; }
    });
})();
</script>

{{-- Per-schedule weather: one deduped fetch fills every schedule card's
     [data-weather-for] slot. Fetched after paint so Open-Meteo never blocks the
     dashboard; each card degrades to nothing on failure. --}}
<script>
(() => {
    const slots = Array.from(document.querySelectorAll('[data-weather-for]'));
    if (!slots.length) return;
    const esc = window.escapeHtml || ((s) => String(s == null ? '' : s));

    // The forecast always starts on today, so day index 0 is today — mark it by
    // index rather than a (possibly cached) flag, so the green marker is reliable.
    function dayCell(d, isToday) {
        const today = isToday || d.isToday;
        // No box — today is simply shown in green (theme-aware via .wx-today-label).
        return `<div class="flex-1 min-w-0 text-center rounded-lg px-1 py-1.5">
            <p class="text-[10px] font-bold ${today ? 'wx-today-label' : 'text-gray-500'} truncate">${esc(today ? 'Today' : d.dow)}</p>
            <div class="text-xl leading-none my-0.5" title="${esc(d.text)}">${d.emoji}</div>
            <p class="text-[11px] font-bold ${today ? 'wx-today-label' : 'text-gray-800'}">${d.max != null ? d.max + '°' : '–'}<span class="text-gray-400 font-medium">${d.min != null ? '/' + d.min + '°' : ''}</span></p>
            ${d.pop != null ? `<p class="text-[9px] font-semibold text-blue-500">💧${d.pop}%</p>` : ''}
        </div>`;
    }
    function locBlock(loc) {
        if (!loc) return '';
        if (loc.ok === false) {
            return `<p class="text-[11px] text-gray-400 mt-2 pt-2 border-t border-gray-100">🌦️ Weather unavailable for ${esc(loc.place || 'this location')}</p>`;
        }
        return `<div class="mt-2 pt-2 border-t border-gray-100">
            <p class="text-[11px] font-semibold text-gray-500 mb-1.5 truncate">📍 ${esc(loc.place)}</p>
            <div class="flex gap-1">${loc.days.map((d, i) => dayCell(d, i === 0)).join('')}</div>
        </div>`;
    }

    async function load() {
        let data;
        try {
            const res = await fetch(@json(route('app.weather')), { headers: { Accept: 'application/json' }, credentials: 'same-origin' });
            data = await res.json();
        } catch (_) { slots.forEach((s) => { s.style.display = 'none'; }); return; }

        if (!data || !data.success || !data.data) { slots.forEach((s) => { s.style.display = 'none'; }); return; }
        const locations = data.data.locations || {};
        const schedules = data.data.schedules || {};

        slots.forEach((slot) => {
            const id = slot.getAttribute('data-weather-for');
            const keys = schedules[id] || [];
            if (!keys.length) { slot.style.display = 'none'; return; }
            const html = keys.map((k) => locBlock(locations[k])).join('');
            if (html) { slot.innerHTML = html; slot.classList.add('wx-ready'); }   // fades the forecast up
            else slot.style.display = 'none';
        });
    }
    load();
})();
</script>
@endpush
