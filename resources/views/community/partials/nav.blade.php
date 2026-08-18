{{-- Community section tabs, shared by every community page.
     Expects: $active (wall | plans | groups | members | cofarmers | profile). --}}
@php
    $tabs = [
        'wall' => ['🏠 Wall', route('community.index')],
        'plans' => ['🌾 Shared Plans', route('community.plans')],
        'groups' => ['💬 Discussions', route('community.groups.index')],
        'blog' => ['📰 Tech Blog', route('community.blog')],
        'members' => ['🧑‍🌾 Members', route('community.connect.members')],
        'cofarmers' => ['🤝 My Co-Farmers', route('community.cofarmers')],
        'profile' => ['🙍 Profile', route('community.connect.profile', ['userId' => auth()->id()])],
    ];
@endphp
<style>
    /* The page you are on wears the app's drifting header green (gradSweep
       lives in the layout), so the nav and the messenger speak one language. */
    .community-nav .chip.is-selected { border-color:transparent; color:#fff;
        background:linear-gradient(120deg, #3d6823, #6b9f3d 35%, #4a7c2a 60%, #2f5219 85%, #3d6823);
        background-size:240% 240%; animation:gradSweep 12s ease-in-out infinite alternate;
        box-shadow: 0 2px 8px -2px rgb(74 124 42 / .45); }
    @media (prefers-reduced-motion: reduce) { .community-nav .chip.is-selected { animation:none; } }
</style>
<div class="scroll-chips mb-4 community-nav">
    @foreach ($tabs as $key => [$label, $url])
        @if (($active ?? '') === $key)
            <span class="chip is-selected shrink-0">{{ $label }}</span>
        @else
            <a href="{{ $url }}" class="chip shrink-0">{{ $label }}</a>
        @endif
    @endforeach
</div>
