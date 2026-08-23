{{-- Community section nav, shared by every community page.
     One hamburger + one chat seat, the way the schedule shell does it: the
     button wears the section you are standing in, the sheet holds the rest.
     Expects: $active (wall | groups | blog | members | cofarmers | requests | profile). --}}
@php
    // Icons are inline paths (same shape as the schedule's modules sheet) so a
    // section can be added here without touching an icon font or a sprite.
    // What is new since this member last looked. Asked once here, because
    // every community page draws this bar.
    $unread = app(\App\Services\CommunityUnreadService::class);
    $badges = [
        'groups' => $unread->discussionTotal(),
        'blog' => $unread->blogCount(),
        'requests' => $unread->requestCount(),
    ];
    $badgeTotal = array_sum($badges);

    $sections = [
        'wall' => [
            'label' => 'Wall', 'short' => 'Wall',
            'url' => route('community.index'),
            'icon' => 'M3 12l9-9 9 9M5 10v10h14V10',
        ],
        'groups' => [
            'label' => 'Discussions', 'short' => 'Discussions',
            'url' => route('community.groups.index'),
            'icon' => 'M8 12h.01M12 12h.01M16 12h.01M21 12c0 4.418-4.03 8-9 8a9.9 9.9 0 01-4.29-.94L3 20l1.05-3.15A7.6 7.6 0 013 12c0-4.418 4.03-8 9-8s9 3.582 9 8z',
        ],
        'blog' => [
            'label' => 'Tech Blog', 'short' => 'Blog',
            'url' => route('community.blog'),
            'icon' => 'M19 20H5a2 2 0 01-2-2V6a2 2 0 012-2h9l7 7v7a2 2 0 01-2 2zM7 9h5M7 13h9M7 17h9',
        ],
        'members' => [
            'label' => 'Members', 'short' => 'Members',
            'url' => route('community.connect.members'),
            'icon' => 'M17 20h5v-2a4 4 0 00-3-3.87M9 20H4v-2a4 4 0 013-3.87m6-1.13a4 4 0 10-4-4 4 4 0 004 4z',
        ],
        'cofarmers' => [
            'label' => 'My Co-Farmers', 'short' => 'Co-Farmers',
            'url' => route('community.cofarmers'),
            'icon' => 'M12 21c-4.5 0-8-2.5-8-5.5V13a3 3 0 013-3h10a3 3 0 013 3v2.5c0 3-3.5 5.5-8 5.5zM9 7a3 3 0 106 0 3 3 0 00-6 0z',
        ],
        'requests' => [
            'label' => 'Requests', 'short' => 'Requests',
            'url' => route('community.connect.requests'),
            'icon' => 'M16 21v-2a4 4 0 00-4-4H6a4 4 0 00-4 4v2M9 11a4 4 0 100-8 4 4 0 000 8zM19 8v6M22 11h-6',
        ],
        'profile' => [
            'label' => 'Profile', 'short' => 'Profile',
            'url' => route('community.connect.profile', ['userId' => auth()->id()]),
            'icon' => 'M5.1 19a7 7 0 0113.8 0M12 11a4 4 0 100-8 4 4 0 000 8z',
        ],
    ];

    // The page's own $active is the answer, except where one blade serves two
    // sections (Requests renders with $active = 'members'); there the URL is
    // the more truthful witness, so it gets first say.
    $here = rtrim(url()->current(), '/');
    $currentKey = null;
    foreach ($sections as $key => $section) {
        if (rtrim($section['url'], '/') === $here) { $currentKey = $key; break; }
    }
    if ($currentKey === null && isset($sections[$active ?? ''])) {
        $currentKey = $active;
    }
    // An unknown section (a page still passing a retired key) must not claim a
    // row in the sheet, so the button says where you are in general terms.
    $currentShort = $currentKey ? $sections[$currentKey]['short'] : 'Sections';
@endphp
<style>
    /* One line, always: the row never wraps, and it is the section name that
       gives way (ellipsis) rather than the chat button falling to line two. */
    /* It follows you down the page.
       These buttons are how you leave the wall, and on a long wall they were
       four screens behind you. Stuck under the app bar, with the page's own
       background behind them so the posts do not show through, and bled to
       the gutters so the bar is a bar rather than a floating strip. */
    .community-nav { position:sticky; top:3.5rem; z-index:25;
        /* Flush under the app bar, not floating a page-margin below it.
           The page opens with padding (py-4, py-8 from md up) and the bar was
           sitting under all of it: a band of empty background between two
           bars, which is what a bar is supposed to prevent. The negative top
           margin is exactly that padding, so the two meet — and when the bar
           finally sticks, nothing moves, because it is already where it
           sticks to. Under it, a hair of air rather than a gap: the block
           below brings its own. */
        margin:-1rem calc(var(--plaza-gutter, 1rem) * -1) .35rem;
        padding:.5rem var(--plaza-gutter, 1rem);
        background:var(--color-gray-50);
        display:flex; align-items:center; gap:.5rem; flex-wrap:nowrap; }
    @media (min-width:768px) { .community-nav { top:4rem; margin-top:-2rem; } }
    html.dark .community-nav { background:#14171c; }
    .cn-hamburger { min-width:0; }
    .cn-hamburger svg { flex-shrink:0; }
    .cn-current { min-width:0; overflow:hidden; text-overflow:ellipsis; white-space:nowrap; }
    /* The prefix is desktop-only room; a phone needs the section name alone. */
    @media (max-width:639px) { .cn-prefix { display:none; } }
    /* Where the messenger dock seats its launcher. Empty on pages that carry
       no dock — and :empty keeps the row's gap from paying for a chair that
       nobody sat in. */
    .cn-seat { display:inline-flex; align-items:center; flex-shrink:0; }
    .cn-seat:empty { display:none; }
    .cn-caret { flex-shrink:0; opacity:.5; }
    /* The same small red circle the bell uses, so "new" reads the same
       everywhere in the app. */
    .cn-dot { display:inline-flex; align-items:center; justify-content:center; flex:none;
        min-width:1.15rem; height:1.15rem; padding:0 .3rem; border-radius:999px;
        background:#ef4444; color:#fff; font-size:.625rem; font-weight:800; line-height:1; }
    .cn-row-dot { margin-left:auto; }
    .cn-saved { flex-shrink:0; }
    /* The word stays at every width. A bookmark is the one icon nobody agrees
       on — save, read later, favourite — and the row has the room. */

    /* The three controls above the wall wear the app's colour, not the grey
       of the button they sit in: this row is the community's own furniture. */
    .cn-hamburger .cn-icon,
    .cn-saved svg,
    .cn-seat .msgr-launcher.is-seated svg { color:var(--color-brand-600); }
    html.dark .cn-hamburger .cn-icon,
    html.dark .cn-saved svg,
    html.dark .cn-seat .msgr-launcher.is-seated svg { color:var(--color-brand-500); }

    .cn-row { display:flex; align-items:center; gap:.75rem; width:100%; padding:.75rem;
        border-radius:.75rem; font-weight:600; color:#374151; text-decoration:none;
        transition:background-color .28s cubic-bezier(.22,1,.36,1); }
    .cn-row:hover { background:#f9fafb; }
    .cn-ico { width:2.25rem; height:2.25rem; border-radius:.75rem; flex-shrink:0;
        display:flex; align-items:center; justify-content:center;
        background:var(--color-brand-50, #eef4e6); color:var(--color-brand-600, #4a7c2a); }
    .cn-ico svg { width:1.25rem; height:1.25rem; }
    .cn-row-label { flex:1; min-width:0; }
    .cn-check { width:1rem; height:1rem; flex-shrink:0; color:var(--color-brand-600, #4a7c2a); }
    /* The section you are standing in wears the header green, the same tell
       the old selected chip carried. */
    .cn-row.is-current { color:#fff;
        background:linear-gradient(120deg, #3d6823, #6b9f3d 35%, #4a7c2a 60%, #2f5219 85%, #3d6823);
        background-size:240% 240%; animation:gradSweep 12s ease-in-out infinite alternate; }
    .cn-row.is-current .cn-ico { background:rgb(255 255 255 / .18); color:#fff; }
    .cn-row.is-current .cn-check { color:#fff; }
    html.dark .cn-row { color:#dbe6cf; }
    html.dark .cn-row:hover { background:rgb(255 255 255 / .07); }
    html.dark .cn-ico { background:rgb(255 255 255 / .08); color:#a7c98a; }
    @media (prefers-reduced-motion: reduce) {
        .cn-row { transition:none; }
        .cn-row.is-current { animation:none; }
    }
</style>
<div class="community-nav">
    <button type="button" class="btn btn-white btn-sm cn-hamburger" data-sheet-open="communitySectionsSheet"
            aria-haspopup="dialog" title="Community sections">
        {{-- The section you are in wears its own mark, so the button says
             where you are twice over — the lines alone said only "menu". --}}
        <svg class="w-4 h-4 cn-icon" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" d="{{ $sections[$active]['icon'] ?? 'M4 6h16M4 12h16M4 18h16' }}"/></svg>
        <span class="cn-current"><span class="cn-prefix">Community &middot; </span>{{ $currentShort }}</span>
        @if ($badgeTotal > 0)
            {{-- Closed, the button still says there is something inside. --}}
            <span class="cn-dot" aria-label="{{ $badgeTotal }} new">{{ $badgeTotal > 99 ? '99+' : $badgeTotal }}</span>
        @endif
        <svg class="w-3.5 h-3.5 cn-caret" fill="none" stroke="currentColor" stroke-width="2.4" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" d="M19 9l-7 7-7-7"/></svg>
    </button>
    {{-- Everything you kept, one tap from everywhere you might keep something. --}}
    <a href="{{ route('community.saved') }}" class="btn btn-white btn-sm cn-saved" title="Saved posts" aria-label="Saved posts">
        <svg class="w-4 h-4" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" d="M5 5a2 2 0 012-2h10a2 2 0 012 2v16l-7-4-7 4V5z"/></svg>
        <span class="cn-saved-word">Saved</span>
    </a>
    {{-- The messenger dock moves its launcher in here on arrival, so the chat
         button sits beside the sections instead of floating over the page. --}}
    <span id="msgrSeat" class="cn-seat" aria-live="polite"></span>
</div>

<div class="sheet hidden" id="communitySectionsSheet" style="--sheet-width:24rem">
    <div class="sheet-handle"></div>
    <div class="sheet-header">
        <h3 class="sheet-title">Community</h3>
        <button type="button" data-sheet-close class="btn-ghost p-2 rounded-full -mr-1" aria-label="Close">
            <svg class="w-5 h-5" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" d="M6 6l12 12M18 6L6 18"/></svg>
        </button>
    </div>
    <div class="sheet-body space-y-1">
        @foreach ($sections as $key => $section)
            <a href="{{ $section['url'] }}" class="cn-row{{ $key === $currentKey ? ' is-current' : '' }}"
               @if ($key === $currentKey) aria-current="page" @endif>
                <span class="cn-ico">
                    <svg fill="none" stroke="currentColor" stroke-width="1.8" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" d="{{ $section['icon'] }}"/></svg>
                </span>
                <span class="cn-row-label">{{ $section['label'] }}</span>
                {{-- Which section the news is actually in, so the total on the
                     button can be acted on rather than just noticed. --}}
                @if (($badges[$key] ?? 0) > 0)
                    <span class="cn-dot cn-row-dot">{{ $badges[$key] > 99 ? '99+' : $badges[$key] }}</span>
                @endif
                @if ($key === $currentKey)
                    <svg class="cn-check" fill="none" stroke="currentColor" stroke-width="3" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" d="M5 13l4 4L19 7"/></svg>
                @endif
            </a>
        @endforeach
    </div>
</div>
