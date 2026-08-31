@extends(request()->boolean('partial') ? 'layouts.partial' : 'layouts.app')

{{-- A map is the one screen that wants the whole phone, so the tab bar steps
     aside the way it does for the Collab Room and the Maps module. --}}
@section('body-class', 'hide-tabbar lotmap-open')

@section('title', 'Map — ' . $lot->lotName)
@section('page-title', $lot->lotName)
@section('page-subtitle', 'Where this lot is')
@section('help-key', 'lots')
@section('back', route('sm.lots', ['id' => $schedule->id]))

@push('head')
<style>
    /* ONE LOT'S MAP.

       The same drawing engine the Collab Room uses — there is one of those in
       this app and there is going to go on being one, because a second copy
       of three thousand lines of Google Maps, undo stacks, measurement labels
       and touch handling means finding every future bug in it twice.

       What is different is everything around it, and by now that is almost
       nothing: no team heading, no live-position sharing, no clear-for-
       everybody, no shelf of the team's plans, and no card explaining the
       errand. One lot, one question, and a Save in the same row as the tools.

       The page does not scroll. A map that can be dragged inside a page that
       can be dragged means one of the two is wrong, and it is the page. */
    body.lotmap-open { overflow: hidden; }
    body.lotmap-open main { padding-top: .5rem; padding-bottom: 0; }
    /* The footer belongs to pages you read, not to a map that fills the
       screen — and with the page not scrolling it could never be reached. */
    body.lotmap-open footer { display: none; }

    .lm-stage { border-radius: 1rem; overflow: hidden;
        border: 1px solid var(--color-gray-200); background: var(--color-white); }
    html.dark .lm-stage { border-color: #2b3a1c; background: #151b12; }
    /* Everything the page is not: the header, the stage's own frame and the
       small breath above it. What is left is the map. */
    .lm-stage .cmap-map { height: calc(100dvh - 8.5rem); min-height: 18rem; }
    @media (min-width: 48rem) {
        .lm-stage .cmap-map { height: calc(100dvh - 10rem); }
    }
</style>
@endpush

@section('content')
<div class="lm-stage">
    {{-- 'lot' mode: same engine, none of the room. --}}
    @include('sm.partials.schedule-map', ['schedule' => $schedule, 'mapChrome' => 'lot'])
</div>
@endsection

@push('scripts')
<script>
(() => {
    /* Saving, asked once.
     *
     * The engine already knows how to write a map — a picture into the
     * Gallery, a note in the notebook, a reopenable snapshot on the shelf —
     * behind a sheet that asks for a name and a description. On a lot's map
     * there is nothing to name: it is this lot's map and it is called after
     * the lot. So the sheet is filled in and sent from here, and the farmer
     * sees one button in the tools row and one question before it writes.
     */
    const LOT = @json(['id' => $lot->id, 'name' => $lot->lotName]);

    /* Start the map.
     *
     * The engine waits to be asked — the Collab Room asks when its tab is
     * opened and the Maps module when you step onto its stage, so no quota is
     * spent on a room nobody looks at. This page IS the map, so it asks
     * straight away, once the partial's own script has had a chance to define
     * the function.
     */
    const boot = () => {
        if (typeof window.initCollabMap === 'function') { window.initCollabMap(); return true; }

        return false;
    };
    if (!boot()) document.addEventListener('DOMContentLoaded', boot);

    const save = () => {
        const name = document.getElementById('cmapSaveName');
        const desc = document.getElementById('cmapSaveDesc');
        const go = document.getElementById('cmapSaveGo') || document.getElementById('cmapSaveBtn');
        if (!go) { window.toast?.('The map is still loading — try again in a moment.', 'error'); return; }
        if (name && !name.value.trim()) name.value = LOT.name;
        if (desc && !desc.value.trim()) desc.value = 'The map for ' + LOT.name + '.';
        go.click();
    };

    document.getElementById('cmapLotSave')?.addEventListener('click', () => {
        // Asked, because saving files a picture into the Gallery and a note
        // into the notebook, and neither is a thing to do by accident.
        if (window.confirmAction) {
            Promise.resolve(window.confirmAction({
                title: 'Save this location?',
                message: 'The map for ' + LOT.name + ' is filed in the notebook, with its picture in the Gallery.',
                confirmText: 'Save',
                confirmClass: 'btn-primary',
            })).then((ok) => { if (ok) save(); });

            return;
        }
        if (window.confirm('Save the map for ' + LOT.name + '?')) save();
    });
})();
</script>
@endpush
