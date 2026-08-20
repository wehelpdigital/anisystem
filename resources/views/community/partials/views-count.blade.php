{{-- How many have looked at a thing, wherever that is worth telling.

     The same eye the post's action row draws, so a count means the same on a
     card, in a room's header and under a topic. Not a button: nothing happens
     when you press it.

     Expects: $kind (post|topic|group), $id, $count. --}}
<span class="v-eye" title="Times this has been looked at">
    <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.9" aria-hidden="true"><path stroke-linecap="round" stroke-linejoin="round" d="M2.5 12S6 5.5 12 5.5 21.5 12 21.5 12 18 18.5 12 18.5 2.5 12 2.5 12z"/><circle cx="12" cy="12" r="3"/></svg>
    <span data-view-count="{{ $kind }}:{{ $id }}">{{ (int) $count }}</span>
</span>
