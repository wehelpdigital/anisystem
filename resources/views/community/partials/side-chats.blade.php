{{-- Rail: the people you last spoke with, newest first. Expects: $chats
     (CommunityRail::recentChats — user, lastBody, lastAt, unread). A row is
     the same door every Message button in the community uses (js-open-dm):
     it opens that person's window in the messenger dock, right here, so a
     reply is one tap away from whatever page you were reading. "See all" is
     the dock's own launcher; plaza-rail (the slot) wires it, because this
     card arrives by fetch and a script pushed from here would never run. --}}
<div class="card p-3 mb-3" data-rail-chats>
    <div class="flex items-center justify-between mb-1">
        <h3 class="text-sm font-bold text-gray-900" style="font-family:var(--font-heading)">💬 Recent Chats</h3>
        <button type="button" class="text-xs font-semibold text-brand-700 hover:text-brand-800" data-rail-chats-all>See all</button>
    </div>
    @forelse ($chats as $c)
        <button type="button" class="rail-row rail-chat js-open-dm"
                data-dm-user="{{ $c['user']->id }}" data-dm-name="{{ $c['user']->full_name }}"
                title="Open your chat with {{ $c['user']->full_name }}">
            <span class="rail-face">@include('community.partials.avatar', ['user' => $c['user'], 'size' => 'avatar-md', 'link' => false])</span>
            <span class="min-w-0 grow">
                <span class="rail-name">{{ $c['user']->full_name }}</span>
                <span class="rail-line {{ $c['unread'] ? 'is-unread' : '' }}">{{ $c['lastBody'] ?: '…' }}</span>
            </span>
            <span class="rail-when">
                @if ($c['lastAt'])<span>{{ $c['lastAt'] }}</span>@endif
                @if ($c['unread'])<i class="rail-dot" aria-label="{{ $c['unread'] }} unread">{{ $c['unread'] > 9 ? '9+' : $c['unread'] }}</i>@endif
            </span>
        </button>
    @empty
        <p class="text-xs text-gray-400 py-2">Wala pang usapan — say hi to a co-farmer.</p>
    @endforelse
</div>
