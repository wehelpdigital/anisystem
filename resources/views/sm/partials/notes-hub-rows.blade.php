{{-- One stretch of the notes shelf. Rendered on its own for the phone's
     scroller, and inside the page for the first load — so both roads paint
     exactly the same card. Expects $notes. --}}
@foreach ($notes as $note)
    <div class="nh-card{{ $note['body'] || ! empty($note['media']) || $note['imageUrl'] ? '' : ' is-bare' }}"
        data-note-type="{{ $note['type'] }}" data-note-id="{{ $note['id'] }}">
        <button type="button" class="nh-head" title="Tap to open or fold this note">
            <svg class="nh-chev" fill="none" stroke="currentColor" stroke-width="2.5" viewBox="0 0 24 24" aria-hidden="true"><path stroke-linecap="round" stroke-linejoin="round" d="M9 5l7 7-7 7"/></svg>
            <span class="nh-headmain">
                <span class="nh-title">{{ $note['title'] }}</span>
                <span class="nh-meta">
                    <span class="nh-tag {{ $note['type'] }}">{{ $note['type'] }}</span>
                    <span class="nh-where">{{ $note['address'] }}</span>
                    <span class="nh-when">{{ $note['when'] }}</span>
                </span>
            </span>
        </button>

        <div class="nh-fold"><div class="nh-fold-inner">
            <div class="nh-body">
                @if ($note['body'])
                    <div class="text-sm text-gray-700 rich-text whitespace-pre-line break-words">{!! \App\Support\CommunityText::safeHtml($note['body']) !!}</div>
                @endif
                @if ($note['imageUrl'])
                    @include('sm.partials.note-attachments', ['media' => [
                        ['type' => 'image', 'url' => $note['imageUrl']],
                    ]])
                @endif
                @if (! empty($note['media']))
                    @include('sm.partials.note-attachments', ['media' => $note['media']])
                @endif

                <div class="nh-acts">
                    @if ($note['url'])
                        <a href="{{ $note['url'] }}" class="nh-act">
                            <svg fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" d="M10 6H6a2 2 0 00-2 2v10a2 2 0 002 2h10a2 2 0 002-2v-4M14 4h6m0 0v6m0-6L10 14"/></svg>
                            Open where it lives
                        </a>
                    @endif
                    @if ($note['type'] === 'global')
                        <button type="button" class="nh-act is-danger nh-del" data-id="{{ $note['id'] }}">
                            <svg fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" d="M6 7h12M9 7V5h6v2M8 7l1 12h6l1-12"/></svg>
                            Delete
                        </button>
                    @endif
                </div>
            </div>
        </div></div>
    </div>
@endforeach
