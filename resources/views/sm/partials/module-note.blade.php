{{-- One line saying how this module fits with the others.

     Notes, Drawings, Maps and the Gallery all hold pieces of the same
     season, and a grower who has just found the same picture in two places
     is right to wonder which one is the real one. The answer is always the
     same — the record is the note, everything else is a view onto it — and
     saying so once per module costs a line and saves the question.

     Expects $say (the sentence) and optionally $sayLink = ['label' => …,
     'href' => …]. --}}
@once
    @push('head')
        <style>
            .mod-say { display: flex; align-items: flex-start; gap: .5rem; margin-bottom: .85rem;
                padding: .6rem .75rem; border-radius: .8rem; font-size: .78rem; line-height: 1.5;
                color: #4a6b34; background: #f3f8ec; border: 1px solid #d9e8c4; }
            .mod-say svg { width: .95rem; height: .95rem; flex: none; margin-top: .1rem; }
            .mod-say a { font-weight: 700; text-decoration: underline; }
            html.dark .mod-say { background: rgb(107 159 61 / .12); border-color: #2b3a1c; color: #a8bd93; }
        </style>
    @endpush
@endonce

<p class="mod-say">
    <svg fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24"><circle cx="12" cy="12" r="9"/><path stroke-linecap="round" stroke-linejoin="round" d="M12 11v5m0-8h.01"/></svg>
    <span>{{ $say }}@if (! empty($sayLink)) <a href="{{ $sayLink['href'] }}">{{ $sayLink['label'] }}</a>@endif</span>
</p>
