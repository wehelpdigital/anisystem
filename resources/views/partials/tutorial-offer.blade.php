{{-- A PAGE SAYS WHICH TUTORIALS IT MAY SHOW, AND WHICH ONE IS ON NOW.

     @include('partials.tutorial-offer', ['keys' => ['dashboard'], 'auto' => 'dashboard'])

     `keys` are looked up in config/tutorials.php and, for the signed-in
     person, checked against what they have already told to stay closed --
     one small query, only here, only for these keys. `auto` (optional) is
     offered as soon as the card's engine is ready; a page that decides
     later -- the Activities shell, as it swaps rooms -- pushes onto
     window.aneeTutorialQueue itself.

     Order-proof: this may run before or after the engine in the layout. --}}
@auth
@php
    $__tut = \App\Http\Controllers\TutorialController::dataFor((array) ($keys ?? []));
@endphp
@if ($__tut['items'])
<script>
(() => {
    const d = (window.ANEE_TUTORIALS ||= { items: {}, seen: [] });
    Object.assign(d.items, @json($__tut['items']));
    @json($__tut['seen']).forEach((k) => { if (!d.seen.includes(k)) d.seen.push(k); });
    @if (! empty($auto))
    (window.aneeTutorialQueue ||= []).push(@json($auto));
    @endif
})();
</script>
@endif
@endauth
