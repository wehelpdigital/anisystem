{{-- A run of group posts. Reused by the group page, "load more", and the
     just-posted append. Expects: $posts (collection), $group. --}}
@foreach ($posts as $post)
    @php
        $canDelete = (int) $post->userId === (int) auth()->id()
            || (int) $group->createdByUserId === (int) auth()->id();
    @endphp
    <article class="card p-4 mb-3 group-post" data-post-id="{{ $post->id }}">
        <header class="flex items-start gap-3">
            <span class="w-9 h-9 rounded-full bg-brand-600 text-white text-xs font-bold flex items-center justify-center shrink-0">
                {{ optional($post->author)->initials ?: '?' }}
            </span>
            <div class="min-w-0 grow">
                <p class="font-semibold text-gray-900 text-sm leading-tight">{{ optional($post->author)->full_name ?: 'Member' }}</p>
                <p class="text-xs text-gray-400">{{ $post->created_at?->diffForHumans() }}</p>
            </div>
            @if ($canDelete)
                <button type="button" class="post-delete-btn text-gray-300 hover:text-red-500 p-1 -mr-1 shrink-0" data-post-id="{{ $post->id }}" aria-label="Delete post">
                    <svg class="w-4 h-4" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" d="M19 7l-.9 12.1a2 2 0 01-2 1.9H7.9a2 2 0 01-2-1.9L5 7m3 0V5a2 2 0 012-2h4a2 2 0 012 2v2m-11 0h16"/></svg>
                </button>
            @endif
        </header>

        <div class="mt-2">
            @if ($post->title)
                <h3 class="font-bold text-gray-900 leading-snug">{{ $post->title }}</h3>
            @endif
            <p class="text-sm text-gray-700 mt-1 whitespace-pre-line break-words">{{ $post->body }}</p>
            @if ($post->imagePath)
                <div class="mt-2"><img src="{{ \Illuminate\Support\Facades\Storage::disk('public')->url($post->imagePath) }}" alt="Attachment" loading="lazy" class="rounded-lg max-h-72 w-auto"></div>
            @endif
        </div>

        <div class="mt-3 pt-3 border-t border-gray-100 space-y-2 post-replies">
            @foreach ($post->replies->sortBy('id') as $reply)
                @include('community.groups.partials.reply', ['reply' => $reply])
            @endforeach
        </div>

        <form class="post-reply-form flex items-center gap-2 mt-2" data-post-id="{{ $post->id }}">
            <input type="text" class="form-input grow" placeholder="Write a reply…" maxlength="4000" required>
            <button type="submit" class="btn btn-primary btn-sm shrink-0">Reply</button>
        </form>
    </article>
@endforeach
