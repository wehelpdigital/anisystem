{{-- One stretch of the notebook. Rendered on its own for the phone's
     scroller and inside the page for the first load, so both roads paint the
     same card. Expects $notes, $schedule, $mapSaves. --}}
@foreach ($notes as $n)
    @php
        $mediaItems = [];
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
        $mapUrl = $saveIdForNote
            ? route('sm.maps', ['id' => $schedule->id, 'save' => $saveIdForNote])
            : route('sm.maps', ['id' => $schedule->id]);
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
                'mapUrl' => $isMap ? $mapUrl : null,
                // A drawing opens where it can be changed, not where it can
                // be squinted at: the Draw module, on this exact drawing.
                'drawUrl' => ($m['type'] ?? '') === 'drawing'
                    ? route('sm.draw', ['id' => $schedule->id, 'open' => $n->id . ':' . $mIndex])
                    : null,
            ];
        }
    @endphp
    <div class="card p-4 note-card" data-id="{{ $n->id }}">
        <div class="note-head flex items-start justify-between gap-3">
            <div class="flex items-start gap-2 min-w-0 grow">
                <svg class="note-chevron w-4 h-4 mt-1 shrink-0" fill="none" stroke="currentColor" stroke-width="2.5" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" d="M9 5l7 7-7 7"/></svg>
                {{-- Every note wears the note icon, drawings included. A
                     drawing note used to arrive with neither icon nor text
                     and read as something else entirely. --}}
                <span class="note-ico" aria-hidden="true">
                    <svg fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" d="M9 12h6m-6 4h6m2 5H7a2 2 0 01-2-2V5a2 2 0 012-2h5.586a1 1 0 01.707.293l5.414 5.414a1 1 0 01.293.707V19a2 2 0 01-2 2z"/></svg>
                </span>
                <div class="min-w-0 grow">
                    <h3 class="font-bold text-gray-900 leading-snug js-title">{{ $n->title }}</h3>
                    @if ($saveIdForNote)
                        {{-- Where this note came from. The card that used to
                             list saved maps separately said the same thing
                             twice; the note is the record, this is its
                             provenance. --}}
                        <span class="badge badge-green note-origin">Team map</span>
                    @endif
                    @if ($fromBoard)
                        <span class="badge badge-blue note-origin">Team drawing</span>
                    @endif
                    <p class="text-xs text-gray-400 mt-0.5 js-time">{{ $n->updated_at?->diffForHumans() }}</p>
                </div>
            </div>
            <div class="flex gap-1 shrink-0 note-acts">
                <button type="button" class="btn btn-sm btn-ghost js-edit" aria-label="Edit note">
                    <svg class="w-4.5 h-4.5" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" d="M11 5H6a2 2 0 00-2 2v11a2 2 0 002 2h11a2 2 0 002-2v-5m-1.414-9.414a2 2 0 112.828 2.828L11.828 15H9v-2.828l8.586-8.586z"/></svg>
                </button>
                <button type="button" class="btn btn-sm btn-ghost text-red-600 js-delete" aria-label="Delete note">
                    <svg class="w-4.5 h-4.5" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" d="M19 7l-.867 12.142A2 2 0 0116.138 21H7.862a2 2 0 01-1.995-1.858L5 7m5 4v6m4-6v6m1-10V4a1 1 0 00-1-1h-4a1 1 0 00-1 1v3M4 7h16"/></svg>
                </button>
            </div>
        </div>
        <div class="note-fold"><div class="note-fold-inner js-note-body-wrap">
            @if (filled($n->body))
                <div class="note-body mt-2">{!! $n->body !!}</div>
            @endif
            @include('sm.partials.note-attachments', ['media' => $mediaItems])
        </div></div>
    </div>
@endforeach
