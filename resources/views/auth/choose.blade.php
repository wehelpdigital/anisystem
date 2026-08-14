{{-- Which hat, before the app opens.

     One account is often three things: a farm of your own, work on someone
     else's, and for a few people the admin site as well. The app cannot show
     all three at once — a worker's screen is scoped to their boss's farm —
     so it asks, once, and remembers for the session. --}}
@extends('layouts.public')

@section('title', 'Choose how to continue')

@push('head')
<style>
    .ch-wrap { min-height: 100dvh; display: flex; align-items: center; justify-content: center; padding: 1.5rem 1rem; }
    .ch-card { width: min(34rem, 100%); }
    .ch-hello { text-align: center; margin-bottom: 1.5rem; }
    .ch-ring { width: 4.5rem; height: 4.5rem; margin: 0 auto .9rem; border-radius: 999px;
        display: flex; align-items: center; justify-content: center; font-size: 1.5rem; font-weight: 800;
        color: #fff; background: linear-gradient(135deg, #6b9f3d, #3d6823);
        box-shadow: 0 12px 30px rgb(74 124 42 / .35); }
    .ch-name { font-size: 1.35rem; font-weight: 800; color: var(--color-gray-900); line-height: 1.25; }
    .ch-sub { font-size: .88rem; color: var(--color-gray-500); margin-top: .3rem; }

    .ch-list { display: grid; gap: .65rem; }
    .ch-opt { display: flex; align-items: center; gap: .85rem; width: 100%; text-align: left;
        padding: .95rem 1rem; border-radius: 1rem; border: 1.5px solid var(--color-gray-200);
        background: var(--color-white); cursor: pointer;
        transition: transform .28s cubic-bezier(.22,1,.36,1), border-color .28s cubic-bezier(.22,1,.36,1), box-shadow .28s cubic-bezier(.22,1,.36,1); }
    .ch-opt:hover, .ch-opt:focus-visible { border-color: #a8cc7e; transform: translateY(-2px);
        box-shadow: 0 10px 26px rgb(17 24 39 / .08); outline: none; }
    .ch-ico { flex: none; width: 2.75rem; height: 2.75rem; border-radius: .85rem;
        display: flex; align-items: center; justify-content: center; }
    .ch-ico svg { width: 1.35rem; height: 1.35rem; }
    .ch-own .ch-ico { background: #eaf4dd; color: #3d6823; }
    .ch-worker .ch-ico { background: #e0f2fe; color: #075985; }
    .ch-admin .ch-ico { background: #fef3c7; color: #92400e; }
    .ch-txt { min-width: 0; flex: 1; }
    .ch-title { display: block; font-weight: 700; font-size: .96rem; color: var(--color-gray-900); }
    .ch-detail { display: block; font-size: .78rem; color: var(--color-gray-500); margin-top: .1rem; }
    .ch-go { flex: none; color: var(--color-gray-300); }
    .ch-opt:hover .ch-go { color: #6b9f3d; }
    .ch-foot { text-align: center; margin-top: 1.4rem; font-size: .78rem; color: var(--color-gray-400); }
    .ch-foot a, .ch-foot button { color: var(--color-gray-500); font-weight: 600; text-decoration: underline; }
    /* The admin door: present, but plainly not one of the farms. */
    .ch-admin-door { display: flex; align-items: center; gap: .6rem; margin-top: 1rem;
        padding: .7rem .85rem; border-radius: .9rem; font-size: .78rem; line-height: 1.5;
        color: #92400e; background: #fffbeb; border: 1px dashed #fcd34d; text-decoration: none; }
    .ch-admin-door svg { width: 1.1rem; height: 1.1rem; flex: none; }
    .ch-admin-door b { font-weight: 800; }
    html.dark .ch-admin-door { background: rgb(180 83 9 / .16); border-color: rgb(180 83 9 / .5); color: #fcd34d; }

    html.dark .ch-opt { background: #151b12; border-color: #2b3a1c; }
    html.dark .ch-title { color: #e8efe1; }
    html.dark .ch-name { color: #e8efe1; }

    @media (prefers-reduced-motion: reduce) { .ch-opt { transition: none; } .ch-opt:hover { transform: none; } }
</style>
@endpush

@section('content')
<div class="ch-wrap">
    <div class="ch-card app-fade-in">
        <div class="ch-hello">
            <div class="ch-ring">{{ $user->initials ?? '?' }}</div>
            <p class="ch-name">Welcome back, {{ $user->firstName ?: 'there' }}.</p>
            <p class="ch-sub">This account is more than one thing. How are you working today?</p>
        </div>

        <div class="ch-list">
            @foreach ($hats as $hat)
                <form method="POST" action="{{ route('account.choose.apply') }}">
                    @csrf
                    <input type="hidden" name="hat" value="{{ $hat['key'] }}">
                    <button type="submit" class="ch-opt ch-{{ $hat['kind'] }}">
                        <span class="ch-ico">
                            @if ($hat['kind'] === 'own')
                                <svg fill="none" stroke="currentColor" stroke-width="1.9" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" d="M3 11l9-7 9 7"/><path stroke-linecap="round" stroke-linejoin="round" d="M5 10v9a1 1 0 001 1h12a1 1 0 001-1v-9"/><path stroke-linecap="round" stroke-linejoin="round" d="M10 20v-5h4v5"/></svg>
                            @else
                                <svg fill="none" stroke="currentColor" stroke-width="1.9" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" d="M12 12a4 4 0 100-8 4 4 0 000 8z"/><path stroke-linecap="round" stroke-linejoin="round" d="M4 21c0-3.3 3.6-6 8-6s8 2.7 8 6"/></svg>
                            @endif
                        </span>
                        <span class="ch-txt">
                            <span class="ch-title">{{ $hat['title'] }}</span>
                            <span class="ch-detail">{{ $hat['detail'] }}</span>
                        </span>
                        <svg class="ch-go w-5 h-5" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" d="M9 5l7 7-7 7"/></svg>
                    </button>
                </form>
            @endforeach
        </div>

        @if (! empty($adminUrl))
            {{-- Not a hat. Administering the site and farming your own land
                 are both true at once, so this is a door to the other site
                 rather than a choice against the farms above. --}}
            <a class="ch-admin-door" href="{{ $adminUrl }}" target="_blank" rel="noopener">
                <svg fill="none" stroke="currentColor" stroke-width="1.9" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" d="M12 3l7 3v6c0 4.2-2.9 7.6-7 9-4.1-1.4-7-4.8-7-9V6l7-3z"/><path stroke-linecap="round" stroke-linejoin="round" d="M9.5 12l1.8 1.8L15 10"/></svg>
                <span>You also administer the site — <b>open the admin site</b> in a new tab. Your own farm stays as you left it.</span>
            </a>
        @endif

        <p class="ch-foot">
            You can change this any time from the account menu.
            <form method="POST" action="{{ route('logout') }}" class="inline">@csrf
                <button type="submit">Sign out</button>
            </form>
        </p>
    </div>
</div>
@endsection
