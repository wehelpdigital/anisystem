{{-- The clips on an answer that carries more than one.

     The same slider the pictures use, and for the same reason: three films
     stacked down a comment turn a thread into a scroll. One at a time, a
     thumb pushes them along, a count and dots say where you are.

     What it does NOT do is move on its own. A row of photographs can drift;
     a film cannot, because the moment one is playing the slider has become
     the thing interrupting it. The auto-advance skips anything marked this
     way.

     Expects: $clips — a list of ['video' => path, 'poster' => path|null]. --}}
@php
    $ccClips = array_values(array_filter((array) ($clips ?? []), fn ($c) => ! empty($c['video'])));
@endphp
@if (count($ccClips) > 1)
    <div class="post-carousel pc-clips{{ ($mini ?? false) ? ' pc-mini' : '' }}" data-shots data-noauto>
        <div class="pc-track">
            @foreach ($ccClips as $ccClip)
                <div class="pc-slide">
                    @include('community.partials.video-embed', [
                        'src' => $ccClip['video'],
                        'poster' => $ccClip['poster'] ?? null,
                    ])
                </div>
            @endforeach
        </div>
        <span class="pc-count" aria-hidden="true"><b>1</b>/{{ count($ccClips) }}</span>
        <div class="pc-dots" aria-hidden="true">
            @foreach ($ccClips as $ccI => $ccClip)
                <span class="pc-dot{{ $ccI === 0 ? ' is-on' : '' }}"></span>
            @endforeach
        </div>
    </div>
@elseif (count($ccClips) === 1)
    @include('community.partials.video-embed', [
        'src' => $ccClips[0]['video'],
        'poster' => $ccClips[0]['poster'] ?? null,
    ])
@endif
