{{-- Which hat, before the app opens.

     One account is often three things: a farm of your own, work on someone
     else's, and for a few people the admin site as well. The app cannot show
     all three at once — a worker's screen is scoped to their boss's farm —
     so it asks, once, and remembers for the session. --}}
@extends('layouts.public')

@section('title', 'Choose how to continue')

{{-- This page is the first thing a member sees after logging in, and the
     login page they just left wore their saved theme. Coming out of a dark
     login into a white chooser and then into a dark app was a flash of the
     wrong page in the middle of the way in. Declared at the top level, like
     the login page does, because the layout tests it in <head>. --}}
@section('honours-theme-cookie', true)

@push('head')
<style>
    .ch-wrap { min-height: 100dvh; display: flex; align-items: center; justify-content: center; padding: 1.5rem 1rem; }
    .ch-card { width: min(34rem, 100%); }
    .ch-hello { text-align: center; margin-bottom: 1.5rem; }
    /* The face at the top is the member's own, when they have one; the
       initials only where they never uploaded a picture. */
    .ch-ring { width: 4.5rem; height: 4.5rem; margin: 0 auto .9rem; border-radius: 999px; overflow: hidden;
        display: flex; align-items: center; justify-content: center; font-size: 1.5rem; font-weight: 800;
        color: #fff; background: linear-gradient(135deg, #6b9f3d, #3d6823);
        box-shadow: 0 12px 30px rgb(74 124 42 / .35); }
    .ch-ring img { width: 100%; height: 100%; object-fit: cover; display: block; }
    .ch-name { font-size: 1.35rem; font-weight: 800; color: var(--color-gray-900); line-height: 1.25; }
    .ch-sub { font-size: .88rem; color: var(--color-gray-500); margin-top: .3rem; }

    .ch-list { display: grid; gap: .65rem; }
    .ch-opt { display: flex; align-items: center; gap: .85rem; width: 100%; text-align: left;
        padding: .95rem 1rem; border-radius: 1rem; border: 1.5px solid var(--color-gray-200);
        background: var(--color-white); cursor: pointer;
        transition: transform .28s cubic-bezier(.22,1,.36,1), border-color .28s cubic-bezier(.22,1,.36,1), box-shadow .28s cubic-bezier(.22,1,.36,1); }
    .ch-opt:hover, .ch-opt:focus-visible { border-color: #a8cc7e; transform: translateY(-2px);
        box-shadow: 0 10px 26px rgb(17 24 39 / .08); outline: none; }
    /* THE FACE ON EACH HAT, not a glyph.
       People know their bosses by face long before they read the name, and
       a house and a person told the two hats apart only by convention. Your
       own farm wears your own face, a farm you work on wears its owner's,
       and the tinted disc with initials is what stands in where nobody ever
       uploaded one — the same fallback the app uses everywhere it draws a
       person. Round, because a face is round; the glyph's soft square was
       the shape of an icon. */
    .ch-ico { flex: none; width: 2.9rem; height: 2.9rem; border-radius: 999px; overflow: hidden;
        display: flex; align-items: center; justify-content: center;
        font-size: .95rem; font-weight: 800; letter-spacing: .01em;
        box-shadow: inset 0 0 0 1.5px rgb(255 255 255 / .6); }
    .ch-ico img { width: 100%; height: 100%; object-fit: cover; display: block; }
    .ch-own .ch-ico { background: var(--color-brand-100, #eaf4dd); color: var(--color-brand-800, #3d6823); }
    .ch-worker .ch-ico { background: var(--color-blue-100, #e0f2fe); color: var(--color-blue-800, #075985); }
    .ch-txt { min-width: 0; flex: 1; }
    .ch-title { display: block; font-weight: 700; font-size: .96rem; color: var(--color-gray-900); }
    .ch-detail { display: block; font-size: .78rem; color: var(--color-gray-500); margin-top: .1rem; }
    .ch-go { flex: none; color: var(--color-gray-300); }
    .ch-opt:hover .ch-go { color: #6b9f3d; }

    /* SIGNING OUT IS A BUTTON, THE SAME WEIGHT AS THE WAY IN.
       It was an underlined word inside a line of small grey print, which
       is how a link to the terms is dressed, not a thing somebody on a
       shared phone needs to find and press. The green is the brand's own,
       moving slowly across the button the way the welcome ring is lit -
       one gradient laid wider than the button and eased back and forth,
       so it reads as alive rather than as a flat block. Reduced motion
       holds it still. */
    .ch-signout { margin-top: 1.25rem; width: 100%; min-height: 3.25rem; padding: .875rem 1.75rem;
        display: inline-flex; align-items: center; justify-content: center; gap: .6rem;
        border: 0; border-radius: 1rem; cursor: pointer; font: inherit; font-size: 1rem; font-weight: 700; color: #fff;
        background: linear-gradient(115deg, #3d6823 0%, #6b9f3d 35%, #4a7c2a 60%, #86b556 85%, #3d6823 100%);
        background-size: 260% 100%; background-position: 0% 50%;
        box-shadow: 0 10px 26px rgb(74 124 42 / .28);
        animation: chSweep 7s ease-in-out infinite;
        transition: transform .28s cubic-bezier(.22,1,.36,1), box-shadow .28s cubic-bezier(.22,1,.36,1); }
    .ch-signout:hover, .ch-signout:focus-visible { transform: translateY(-2px);
        box-shadow: 0 14px 32px rgb(74 124 42 / .36); outline: none; }
    .ch-signout:active { transform: translateY(0) scale(.985); }
    .ch-signout svg { width: 1.15rem; height: 1.15rem; flex: none; }
    @keyframes chSweep { 0%, 100% { background-position: 0% 50%; } 50% { background-position: 100% 50%; } }
    .ch-foot { text-align: center; margin-top: .9rem; font-size: .78rem; color: var(--color-gray-400); }

    /* The admin door: present, but plainly not one of the farms. */
    .ch-admin-door { display: flex; align-items: center; gap: .6rem; margin-top: 1rem;
        padding: .7rem .85rem; border-radius: .9rem; font-size: .78rem; line-height: 1.5;
        color: #92400e; background: #fffbeb; border: 1px dashed #fcd34d; text-decoration: none; }
    .ch-admin-door svg { width: 1.1rem; height: 1.1rem; flex: none; }
    .ch-admin-door b { font-weight: 800; }
    html.dark .ch-admin-door { background: rgb(180 83 9 / .16); border-color: rgb(180 83 9 / .5); color: #fcd34d; }

    html.dark .ch-opt { background: #151b12; border-color: #2b3a1c; }
    html.dark .ch-opt:hover, html.dark .ch-opt:focus-visible { border-color: #5f8a3a; box-shadow: 0 10px 26px rgb(0 0 0 / .45); }
    html.dark .ch-title { color: #e8efe1; }
    html.dark .ch-name { color: #e8efe1; }
    html.dark .ch-ico { box-shadow: inset 0 0 0 1.5px rgb(255 255 255 / .12); }
    html.dark .ch-own .ch-ico { background: #22301a; color: #b9d99a; }
    html.dark .ch-worker .ch-ico { background: #1b283e; color: #a6c9f5; }
    html.dark .ch-signout { box-shadow: 0 10px 26px rgb(0 0 0 / .45); }

    @media (prefers-reduced-motion: reduce) {
        .ch-opt, .ch-signout { transition: none; }
        .ch-opt:hover, .ch-signout:hover { transform: none; }
        .ch-signout { animation: none; }
    }
</style>
@endpush

@section('content')
<div class="ch-wrap">
    <div class="ch-card app-fade-in">
        <div class="ch-hello">
            <div class="ch-ring">
                @if (filled($user->avatarPath))
                    <img src="{{ \App\Support\MediaStore::url($user->avatarPath) }}" alt="" data-avatar-fallback data-initials="{{ $user->initials ?: '?' }}">
                @else
                    {{ $user->initials ?? '?' }}
                @endif
            </div>
            <p class="ch-name">Welcome back, {{ $user->firstName ?: 'there' }}.</p>
            <p class="ch-sub">This account is more than one thing. How are you working today?</p>
        </div>

        <div class="ch-list">
            @foreach ($hats as $hat)
                <form method="POST" action="{{ route('account.choose.apply') }}">
                    @csrf
                    <input type="hidden" name="hat" value="{{ $hat['key'] }}">
                    <button type="submit" class="ch-opt ch-{{ $hat['kind'] }}">
                        <span class="ch-ico" aria-hidden="true">
                            @if (! empty($hat['avatar']))
                                <img src="{{ $hat['avatar'] }}" alt="" data-avatar-fallback data-initials="{{ $hat['initials'] ?? '?' }}">
                            @else
                                {{ $hat['initials'] ?? '?' }}
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

        <form method="POST" action="{{ route('logout') }}">
            @csrf
            <button type="submit" class="ch-signout">
                <svg fill="none" stroke="currentColor" stroke-width="2.2" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" d="M15 16l4-4m0 0l-4-4m4 4H9m4 8H6a2 2 0 01-2-2V6a2 2 0 012-2h7"/></svg>
                Sign out
            </button>
        </form>
        <p class="ch-foot">You can change this any time from the account menu.</p>
    </div>
</div>
@endsection
