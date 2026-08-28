@extends(request()->boolean('partial') ? 'layouts.partial' : 'layouts.app')

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

       What is different is everything around it. This is not a room: there is
       nobody here to share a position with, nothing to clear for everybody,
       and no shelf of the team's plans. It is one lot, one question, and one
       Save. */
    .lm-wrap { display: flex; flex-direction: column; gap: .75rem; }

    /* What to do, said once, at the top. A map with a pin tool already out
       and no explanation is a screen people back out of. */
    .lm-say { display: flex; align-items: flex-start; gap: .7rem;
        padding: .8rem .95rem; border-radius: 1rem;
        background: var(--color-brand-50); border: 1px solid var(--color-brand-100); }
    .lm-say svg { width: 1.3rem; height: 1.3rem; flex: 0 0 auto; color: var(--color-brand-700); margin-top: .05rem; }
    .lm-say b { display: block; font-size: .88rem; font-weight: 800; color: #2f5219; line-height: 1.3; }
    .lm-say i { display: block; font-style: normal; font-size: .78rem; line-height: 1.5;
        color: var(--color-gray-600); margin-top: .2rem; }
    html.dark .lm-say { background: rgb(107 159 61 / .12); border-color: #2b3a1c; }
    html.dark .lm-say b { color: #bfe19a; }
    html.dark .lm-say i { color: #a8bd93; }
    .lm-say.is-done { background: rgb(107 159 61 / .16); border-color: var(--color-brand-300); }

    /* The map itself, in a card of its own rather than filling a tab. */
    .lm-stage { border-radius: 1rem; overflow: hidden;
        border: 1px solid var(--color-gray-200); background: var(--color-white); }
    html.dark .lm-stage { border-color: #2b3a1c; background: #151b12; }
    .lm-stage .cmap-map { height: min(60vh, 30rem); }

    /* Save is the errand's end, so it is a real button under the map rather
       than an icon in a toolbar of eleven. */
    .lm-foot { display: flex; align-items: center; gap: .6rem; }
    .lm-foot .btn { flex: 1 1 auto; }
    .lm-pinned { font-size: .76rem; font-weight: 700; color: var(--color-gray-500);
        font-family: ui-monospace, SFMono-Regular, Menlo, monospace; }
    html.dark .lm-pinned { color: #a8bd93; }
</style>
@endpush

@section('content')
<div class="lm-wrap">
    <div class="lm-say" id="lmSay">
        <svg fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24" aria-hidden="true"><path stroke-linecap="round" stroke-linejoin="round" d="M12 21s7-7.5 7-12a7 7 0 10-14 0c0 4.5 7 12 7 12z"/><circle cx="12" cy="9" r="2.4"/></svg>
        <span>
            <b id="lmSayHead">{{ $lot->isPinned() ? 'This lot is on the map' : 'Tap the map where this lot is' }}</b>
            <i id="lmSayBody">{{ $lot->isPinned()
                ? 'Drag the pin if it is not quite right. Draw the boundary too if you like, then Save.'
                : 'The pin tool is already out. Draw the boundary too if you like — the same tools the drawing module has — then Save.' }}</i>
        </span>
    </div>

    <div class="lm-stage">
        {{-- 'lot' mode: same engine, none of the room. --}}
        @include('sm.partials.schedule-map', ['schedule' => $schedule, 'mapChrome' => 'lot'])
    </div>

    {{-- Both of these are drawn whether or not the lot has a place yet, and
         hidden until it does — a lot pinned a moment ago is in exactly the
         same state as one pinned last week, and the page should not need
         reloading to agree. --}}
    <div class="lm-foot">
        <button type="button" class="btn btn-primary" id="lmSave">Save this lot's map</button>
        <a class="btn btn-white" id="lmOpen" href="{{ $lot->isPinned() ? $lot->mapsHref() : '#' }}"
           target="_blank" rel="noopener" @unless ($lot->isPinned()) hidden @endunless>Open in Maps</a>
    </div>
    <p class="lm-pinned" id="lmAt" @unless ($lot->isPinned()) hidden @endunless>{{ $lot->isPinned()
        ? number_format((float) $lot->pinLat, 6) . ', ' . number_format((float) $lot->pinLng, 6) : '' }}</p>
</div>
@endsection

@push('scripts')
<script>
(() => {
    /* Save, said once.
     *
     * The engine already knows how to write a map — a picture into the
     * Gallery, a note in the notebook, a reopenable snapshot on the shelf —
     * behind a sheet that asks for a name and a description. On a lot's map
     * there is nothing to name: it is this lot's map, and it is called after
     * the lot. So the sheet is filled in and sent from here, and the farmer
     * sees one button.
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

    document.getElementById('lmSave')?.addEventListener('click', () => {
        const name = document.getElementById('cmapSaveName');
        const desc = document.getElementById('cmapSaveDesc');
        const go = document.getElementById('cmapSaveGo') || document.getElementById('cmapSaveBtn');
        if (!go) { window.toast?.('The map is still loading — try again in a moment.', 'error'); return; }
        if (name && !name.value.trim()) name.value = LOT.name;
        if (desc && !desc.value.trim()) desc.value = 'The map for ' + LOT.name + '.';
        go.click();
    });

    /* The errand's line at the top follows the pin, the same way the banner
       inside the map used to — that banner is gone here, because this page
       has a header of its own and two of them saying the same thing is one
       too many. */
    window.addEventListener('anee:lot-pinned', (e) => {
        const d = e.detail || {};
        const head = document.getElementById('lmSayHead');
        const body = document.getElementById('lmSayBody');
        const say = document.getElementById('lmSay');
        if (d.ok === false) {
            // The pin is on the map; the lot record did not take it. Say so
            // here, because on this page there is nowhere else it could be
            // said.
            if (body) body.textContent = 'The pin is on the map, but the lot did not take it — try dragging it once more.';
            return;
        }
        if (head) head.textContent = 'Pinned';
        if (body) body.textContent = 'Drag it if it is not quite right, and Save when you are done.';
        say?.classList.add('is-done');
        const at = document.getElementById('lmAt');
        if (at && d.at) { at.textContent = d.at; at.hidden = false; }
        const open = document.getElementById('lmOpen');
        if (open && d.href) { open.href = d.href; open.hidden = false; }
    });
})();
</script>
@endpush
