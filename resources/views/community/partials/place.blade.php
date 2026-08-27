{{-- Where somebody farms, said the same way everywhere.

     The red pin the co-farmer requests panel has always used, rather than the
     📍 emoji the rest of the app reached for: an emoji is drawn by whatever
     font the phone happens to have, so the same line was a flat red pin on
     one handset, a rounded 3D one on the next, and a black outline on a
     desktop — three different marks for one fact.

     Expects: $place (a string; nothing is drawn if it is empty). --}}
@if (filled($place ?? null))
    <span class="place-pin">
        <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" aria-hidden="true"><path stroke-linecap="round" stroke-linejoin="round" d="M17.657 16.657L13.414 20.9a2 2 0 01-2.827 0l-4.244-4.243a8 8 0 1111.314 0z"/><path stroke-linecap="round" stroke-linejoin="round" d="M15 11a3 3 0 11-6 0 3 3 0 016 0z"/></svg>{{ $place }}</span>
@endif
