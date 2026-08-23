{{-- What a worker may open, module by module.

     One panel, one row shape, two controls. A row is: a mark, what it is,
     what it means, and the answer on the right. The answer is either a
     switch (they have it or they do not) or a three-way segment (no access /
     view only / can edit) — a level cannot honestly be a switch, and a
     dropdown for it hid the answer behind a tap.

     The schedule itself leads, because everything under it depends on it,
     and community access closes the list, because it is the one thing here
     that is not about the farm at all.

     Drawn twice on this page — once in "Give access", once in a worker's own
     login sheet — so it is a partial, and the ids are prefixed rather than
     written out. Expects: $p, the prefix ('wl' or 'grant').

     Every level keeps a hidden input under the segment carrying the same id
     the sheet's JS has always read, so nothing outside this file had to learn
     a new way to ask. The keys are the ones WorkerGrant::MODULES knows;
     window.workerRights paints this block by the same names and the
     middleware gates the routes by them. One vocabulary end to end. --}}
@php
    // level rows: [id suffix, mark, label, hint]
    $wrLevels = [
        ['Access', '🗓️', 'The schedule', 'The plan itself — activities, lots, workers, the calendar.'],
        ['NotesAccess', '📝', 'Notes', 'Day notes, and the photos and videos filed with them.'],
        ['ReportsAccess', '📊', 'Reports', 'Labour and revenue reports for this farm.'],
    ];
    $wrSwitches = [
        ['MapsAccess', '🗺️', 'Maps', 'Field maps, traces and saved maps.'],
        ['DrawAccess', '✏️', 'Draw', 'The drawing module and its saved pictures.'],
        ['AiAccess', '🤖', 'AI Technician', 'Asking the technician — answers are paid from your credits.'],
        ['CameraAccess', '📷', 'Camera', 'Taking photos and filing them on this farm.'],
        ['VideoAccess', '🎥', 'Video record', 'Recording clips and attaching them.'],
    ];
@endphp
<div class="wr-block" data-wr-block>
    <p class="wr-head">What they can open</p>

    @foreach ($wrLevels as [$key, $mark, $label, $hint])
        <div class="wr-row">
            <span class="wr-mark">{{ $mark }}</span>
            <span class="wr-what">
                <b>{{ $label }}</b>
                <i>{{ $hint }}</i>
            </span>
            {{-- The hidden input is the answer; the segment is how it is
                 given. Both directions go through it, so a sheet that sets
                 .value still repaints these buttons. --}}
            <input type="hidden" id="{{ $p }}{{ $key }}" class="wr-level" value="view">
            <span class="wr-seg" role="group" aria-label="{{ $label }} access" data-wr-seg="{{ $p }}{{ $key }}">
                <button type="button" data-wr-val="none" title="No access">None</button>
                <button type="button" data-wr-val="view" title="View only">View</button>
                <button type="button" data-wr-val="edit" title="Can edit &amp; create">Edit</button>
            </span>
        </div>
    @endforeach

    @foreach ($wrSwitches as [$key, $mark, $label, $hint])
        <label class="wr-row wr-switch" for="{{ $p }}{{ $key }}">
            <span class="wr-mark">{{ $mark }}</span>
            <span class="wr-what">
                <b>{{ $label }}</b>
                <i>{{ $hint }}</i>
            </span>
            <input type="checkbox" id="{{ $p }}{{ $key }}" class="wr-check">
            <span class="wr-toggle" aria-hidden="true"></span>
        </label>
    @endforeach

    <label class="wr-row wr-switch" for="{{ $p }}Community">
        <span class="wr-mark">🌾</span>
        <span class="wr-what">
            <b>Community</b>
            <i>Their own profile, and posting in the community.</i>
        </span>
        <input type="checkbox" id="{{ $p }}Community" class="wr-check" checked>
        <span class="wr-toggle" aria-hidden="true"></span>
    </label>

    <p class="wr-foot">A worker with <strong>no schedule access</strong> has none of the modules above it — they belong to the farm they cannot see.</p>
</div>

@once
@push('scripts')
<script>
/* The segments, and nothing else.
 *
 * A level is a hidden input; these buttons are one way of writing to it and
 * the sheet's own JS is the other. Both end at the same input, and a change
 * event is what makes the buttons agree with it — which is why the sheets
 * dispatch one after they paint a grant. */
(function workerRightSegments() {
    const paint = (seg) => {
        const input = document.getElementById(seg.getAttribute('data-wr-seg'));
        if (!input) return;
        const value = input.value || 'view';
        seg.querySelectorAll('[data-wr-val]').forEach((b) => {
            const on = b.getAttribute('data-wr-val') === value;
            b.classList.toggle('is-on', on);
            b.setAttribute('aria-pressed', on ? 'true' : 'false');
        });
        // A row whose answer is "none" is dimmed, so a list of eight can be
        // read at a glance instead of one row at a time.
        seg.closest('.wr-row')?.classList.toggle('is-off', value === 'none');
    };
    const paintAll = () => document.querySelectorAll('[data-wr-seg]').forEach(paint);

    document.addEventListener('click', (e) => {
        const btn = e.target.closest('[data-wr-val]');
        if (!btn) return;
        const seg = btn.closest('[data-wr-seg]');
        const input = document.getElementById(seg.getAttribute('data-wr-seg'));
        if (!input) return;
        input.value = btn.getAttribute('data-wr-val');
        paint(seg);
        input.dispatchEvent(new Event('input', { bubbles: true }));
    });

    // Set from the outside — a sheet loading a worker's saved grant.
    document.addEventListener('change', (e) => {
        if (e.target.classList?.contains('wr-level')) {
            const seg = document.querySelector('[data-wr-seg="' + e.target.id + '"]');
            if (seg) paint(seg);
        }
    });

    paintAll();
    // The sheets are in the page from the start, but a module screen can be
    // injected later; a cheap re-paint on open costs nothing.
    document.addEventListener('sm:sheet-opened', paintAll);
})();
</script>
@endpush
@endonce
