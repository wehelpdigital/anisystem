{{-- One stretch of the notebook. Rendered on its own for the phone's
     scroller and inside the page for the first load, so both roads paint the
     same card. Expects $notes (a page of ['kind' => note|day|inote, 'm' =>
     model] rows), $schedule, $mapSaves.

     The shelf reads like Global Notes scoped to one season: every card
     folds to its name, wears a tag saying where it lives (the notebook, or
     a day on the board), and a day's note links back to the day. --}}
@foreach ($notes as $noteRow)
@php $rowKind = $noteRow['kind'] ?? 'note'; $n = $noteRow['m'] ?? $noteRow; @endphp
@if ($rowKind === 'note')
    @php
        $mediaItems = [];
        $editorMedia = [];
        if (filled($n->imagePath)) {
            $mediaItems[] = ['type' => 'image', 'url' => \App\Support\MediaStore::url($n->imagePath)];
        }
        // The module's OWN url, not the shell's ?module=maps: inside the
        // Activities shell a link is matched by its base path, and
        // /app/sm-activities matches the Activities module itself — so the
        // shell answered a tap on "View map" by showing the board.
        // A note the map save created points at its own snapshot, so
        // "View map" lands on the map that note is about.
        $saveIdForNote = ($mapSaves ?? collect())->firstWhere('noteId', $n->id)['id'] ?? null;
        // A picture the team whiteboard saved, known by the name that save
        // writes. Same idea as the map tag: the note is the record, the
        // badge is where it came from.
        $fromBoard = collect(is_array($n->media) ? $n->media : [])
            ->contains(fn ($m) => (bool) preg_match('~/board-[A-Za-z0-9]+\.png$~', (string) ($m['path'] ?? '')));
        // The picture stays on the note for everyone; only the "open it in the
        // Maps module" link is withheld, because that module is not a worker's
        // to open — better no link than one that lands on "no access".
        $mapUrl = \App\Support\WorkerContext::inWorkerContext() ? null : ($saveIdForNote
            ? route('sm.maps', ['id' => $schedule->id, 'save' => $saveIdForNote])
            : route('sm.maps', ['id' => $schedule->id]));
        foreach ((is_array($n->media) ? $n->media : []) as $mIndex => $m) {
            if (empty($m['path'])) continue;
            // Maps saved before they announced themselves are recognised by
            // the filename the map save writes, so old notes get the link
            // too rather than a thumbnail that may no longer resolve.
            $isMap = ($m['type'] ?? '') === 'map'
                || (bool) preg_match('~/map-[A-Za-z0-9]+\.png$~', (string) $m['path']);
            $mediaItems[] = [
                'type' => $isMap ? 'map' : ($m['type'] ?? 'image'),
                'url' => \App\Support\MediaStore::url($m['path']),
                'posterUrl' => ! empty($m['poster']) ? \App\Support\MediaStore::url($m['poster']) : null,
                // A recording's own name — the chip wears it instead of the
                // word "Video", so three clips on one note read apart.
                'title' => $m['title'] ?? null,
                'mapUrl' => $isMap ? $mapUrl : null,
                // A drawing opens where it can be changed, not where it can
                // be squinted at: the Draw module, on this exact drawing.
                'drawUrl' => ($m['type'] ?? '') === 'drawing'
                    ? route('sm.draw', ['id' => $schedule->id, 'open' => $n->id . ':' . $mIndex])
                    : null,
            ];
            // What the EDITOR needs, which is not what a thumbnail needs:
            // the stored path and a drawing's strokes.
            $editorMedia[] = [
                'type' => $isMap ? 'map' : ($m['type'] ?? 'image'),
                'path' => $m['path'],
                'strokes' => $m['strokes'] ?? null,
                'title' => $m['title'] ?? null,
                'description' => $m['description'] ?? null,
                'url' => \App\Support\MediaStore::url($m['path']),
                'poster' => $m['poster'] ?? null,
                'posterUrl' => ! empty($m['poster']) ? \App\Support\MediaStore::url($m['poster']) : null,
                'mapUrl' => $isMap ? $mapUrl : null,
            ];
        }
        // Every card carries its own note. The page used to hand the editor a
        // map of the notes it had rendered, which stopped being the notes on
        // screen the moment a second page arrived — and editing one of those
        // opened a blank sheet and saved a duplicate.
        $cardNote = [
            'fromMap' => (bool) $saveIdForNote,
            'fromDraw' => $fromBoard,
            'id' => $n->id,
            'title' => $n->title,
            'body' => $n->body,
            'imagePath' => $n->imagePath,
            'imageUrl' => $n->imagePath ? \App\Support\MediaStore::url($n->imagePath) : null,
            'media' => $editorMedia ?? [],
        ];
        $editorMedia = [];
    @endphp
    <div class="card p-4 note-card" data-id="{{ $n->id }}" data-note="{{ json_encode($cardNote) }}">
        <div class="note-head flex items-start justify-between gap-3">
            <div class="flex items-start gap-2 min-w-0 grow">
                <svg class="note-chevron w-4 h-4 mt-1 shrink-0" fill="none" stroke="currentColor" stroke-width="2.5" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" d="M9 5l7 7-7 7"/></svg>
                <div class="min-w-0 grow">
                    <h3 class="font-bold text-gray-900 leading-snug js-title">{{ $n->title }}</h3>
                    @if ($saveIdForNote)
                        <span class="badge badge-green note-origin">Team map</span>
                    @endif
                    @if ($fromBoard)
                        <span class="badge badge-blue note-origin">Team drawing</span>
                    @endif
                    {{-- Where this note lives, the way the Global Notes shelf
                         says it — here the scope is just this one season. --}}
                    <p class="note-meta js-time">
                        <span class="note-kindtag is-notebook">Notebook</span>
                        <span class="note-when">{{ $n->updated_at?->diffForHumans() }}</span>
                    </p>
                </div>
            </div>
            @php $mayNote = \App\Support\WorkerContext::canWriteModule('notes'); @endphp
            <div class="flex gap-1 shrink-0 note-acts">
                @if ($mayNote)
                <button type="button" class="btn btn-sm btn-ghost js-edit" aria-label="Edit note">
                    <svg class="w-4.5 h-4.5" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" d="M11 5H6a2 2 0 00-2 2v11a2 2 0 002 2h11a2 2 0 002-2v-5m-1.414-9.414a2 2 0 112.828 2.828L11.828 15H9v-2.828l8.586-8.586z"/></svg>
                </button>
                <button type="button" class="btn btn-sm btn-ghost text-red-600 js-delete" aria-label="Delete note">
                    <svg class="w-4.5 h-4.5" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" d="M3 6h18M19 6v14a2 2 0 01-2 2H7a2 2 0 01-2-2V6M8 6V4a2 2 0 012-2h4a2 2 0 012 2v2M10 11v6M14 11v6"/></svg>
                </button>
                @endif
            </div>
        </div>
        <div class="note-fold"><div class="note-fold-inner js-note-body-wrap">
            @if (filled($n->body))
                <div class="note-body mt-2">{!! $n->body !!}</div>
            @endif
            @include('sm.partials.note-attachments', ['media' => $mediaItems])
        </div></div>
    </div>
@else
    @php
        // A note pinned to a day on the board — read here, edited there.
        $isDate = $rowKind === 'day';
        $dayDate = ($isDate ? $n->noteDate : $n->noteDate)?->format('M j, Y');
        $dayTitle = ! $isDate && filled($n->title ?? null) ? $n->title : ('Day note — ' . ($dayDate ?: 'the board'));
        $dayBody = $isDate ? $n->noteContent : $n->content;
        $dayMedia = collect(is_array($n->media) ? $n->media : [])->filter(fn ($m) => ! empty($m['path']))->map(fn ($m) => [
            'type' => $m['type'] ?? 'image',
            'url' => \App\Support\MediaStore::url($m['path']),
            'posterUrl' => ! empty($m['poster']) ? \App\Support\MediaStore::url($m['poster']) : null,
            'title' => $m['title'] ?? null,
        ])->values()->all();
        $dayId = ($isDate ? 'd' : 'i') . $n->id;
    @endphp
    <div class="card p-4 note-card is-collapsed" data-id="{{ $dayId }}">
        <div class="note-head flex items-start justify-between gap-3">
            <div class="flex items-start gap-2 min-w-0 grow">
                <svg class="note-chevron w-4 h-4 mt-1 shrink-0" fill="none" stroke="currentColor" stroke-width="2.5" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" d="M9 5l7 7-7 7"/></svg>
                <div class="min-w-0 grow">
                    <h3 class="font-bold text-gray-900 leading-snug js-title">{{ $dayTitle }}</h3>
                    <p class="note-meta js-time">
                        <span class="note-kindtag is-day">Day</span>
                        <span class="note-when">{{ $dayDate }}{{ $dayDate ? ' · ' : '' }}{{ $n->updated_at?->diffForHumans() }}</span>
                    </p>
                </div>
            </div>
        </div>
        <div class="note-fold"><div class="note-fold-inner js-note-body-wrap">
            @if (filled($dayBody))
                <div class="note-body mt-2 whitespace-pre-line break-words">{!! \App\Support\CommunityText::safeHtml($dayBody) !!}</div>
            @endif
            @include('sm.partials.note-attachments', ['media' => $dayMedia])
            <div class="note-lives">
                <a href="{{ route('sm.activities', ['id' => $schedule->id]) }}" class="note-lives-act">
                    <svg fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" d="M10 6H6a2 2 0 00-2 2v10a2 2 0 002 2h10a2 2 0 002-2v-4M14 4h6m0 0v6m0-6L10 14"/></svg>
                    Open where it lives
                </a>
            </div>
        </div></div>
    </div>
@endif
@endforeach
