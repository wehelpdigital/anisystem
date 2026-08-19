{{-- The post somebody shared, quoted inside theirs.

     A share carries the original rather than copying it, so what is quoted
     here is always what its author currently says — edit the original and
     every share of it follows.

     One partial, because there are two wall cards: the community feed's and
     the one on a member's own profile. The profile card never drew this at
     all, so a shared post there was somebody's comment about a post nobody
     could see.

     Expects: $shared (a CommunityWallPost, with author). --}}
<a class="fp-shared" href="{{ route('community.index') }}#wallpost-{{ $shared->id }}">
    <span class="fp-shared-head">
        @if ($shared->author?->avatarPath)
            {{-- A face whose file has gone leaves a broken glyph in the middle
                 of the quote; the initials are the better answer. --}}
            <img src="{{ \App\Support\MediaStore::url($shared->author->avatarPath) }}" alt=""
                 onerror="this.insertAdjacentHTML('afterend', '<i>{{ $shared->author?->initials ?: '?' }}</i>'); this.remove();">
        @else
            <i>{{ $shared->author?->initials ?: '?' }}</i>
        @endif
        <b>{{ $shared->author?->full_name ?: 'A farmer' }}</b>
        <em>{{ $shared->created_at?->diffForHumans() }}</em>
    </span>
    @if (trim((string) $shared->body) !== '')
        <span class="fp-shared-body">{{ \Illuminate\Support\Str::limit(strip_tags($shared->body), 220) }}</span>
    @endif
    @if ($shared->imagePath)
        <img class="fp-shared-img" src="{{ \App\Support\MediaStore::url($shared->imagePath) }}" alt="" loading="lazy"
             onerror="this.remove()">
    @endif
    @if ($shared->videoPath ?? null)
        {{-- A shared clip says it is one rather than trying to play here; the
             original is one tap away and plays where it lives. --}}
        <span class="fp-shared-clip">🎬 A video — open the post to watch it</span>
    @endif
</a>
