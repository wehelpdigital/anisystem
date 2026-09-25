{{-- A PAGE SAYS WHICH TUTORIALS IT MAY SHOW, AND WHICH ONE IS ON NOW.

     @include('partials.tutorial-offer', ['keys' => ['dashboard'], 'auto' => 'dashboard'])

     `keys` are looked up in config/tutorials.php and handed to the card with
     their words and clips. `auto` (optional) is offered as soon as the
     card's engine is ready; a page that decides later -- the Activities
     shell, as it swaps rooms; a quick tool, as its sheet opens -- pushes onto
     window.aneeTutorialQueue itself. Pass 'auto' => null when including
     from inside another view: @include hands down the parent's variables,
     and a stray $auto there would be offered.

     @include('partials.tutorial-offer', ['byRoute' => true])

     The layout's form: the key is whatever config('tutorials.routes') lists
     for the current route, offered on load; nothing at all for a route not
     listed.

     "Never again" is the card's cookie, read in the browser. The only query
     here is for the keys old enough to have an account row from before the
     cookie (tutorials.account_keys); a page asking only for newer ones runs
     none.

     Order-proof: this may run before or after the engine in the layout. --}}
@auth
@php
    if (! empty($byRoute)) {
        $__key = \App\Http\Controllers\TutorialController::forRoute(optional(request()->route())->getName());
        $__keys = $__key ? [$__key] : [];
        $__auto = $__key;
    } else {
        $__keys = (array) ($keys ?? []);
        $__auto = $auto ?? null;
    }
    $__tut = $__keys ? \App\Http\Controllers\TutorialController::dataFor($__keys) : ['items' => [], 'seen' => []];
@endphp
@if ($__tut['items'])
<script>
(() => {
    const d = (window.ANEE_TUTORIALS ||= { items: {}, seen: [] });
    Object.assign(d.items, @json($__tut['items']));
    @json($__tut['seen']).forEach((k) => { if (!d.seen.includes(k)) d.seen.push(k); });
    @if (! empty($__auto))
    (window.aneeTutorialQueue ||= []).push(@json($__auto));
    @endif
})();
</script>
@endif
@endauth
