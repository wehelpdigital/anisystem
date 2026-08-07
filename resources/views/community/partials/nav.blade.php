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
<style> .community-nav .chip.is-selected { box-shadow: 0 2px 8px -2px rgb(74 124 42 / .45); } </style>
<div class="scroll-chips mb-4 community-nav">
    @foreach ($tabs as $key => [$label, $url])
        @if (($active ?? '') === $key)
            <span class="chip is-selected shrink-0">{{ $label }}</span>
        @else
            <a href="{{ $url }}" class="chip shrink-0">{{ $label }}</a>
        @endif
    @endforeach
</div>
