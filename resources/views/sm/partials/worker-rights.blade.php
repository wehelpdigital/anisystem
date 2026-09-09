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
        ['Access', '🗓️', 'Activities', 'The day to day plan: the activities board, lots and the calendar.'],
        ['NotesAccess', '📝', 'Notes', 'Day notes, and the photos and videos filed with them.'],
        ['ReportsAccess', '📊', 'Reports', 'Labour and money reports for this farm.'],
        ['InventoryAccess', '📦', 'Inventory', 'Items on hand, stock moves and what they cost.'],
        ['MapsAccess', '🗺️', 'Maps', 'Field maps, lot maps and saved maps — view them, or draw and save too.'],
        ['DrawAccess', '✏️', 'Draw', 'The drawing module and its saved pictures — view them, or draw new ones.'],
    ];
    $wrSwitches = [
        // Anee's row wears her face, not a robot: a mark with a '/' in it
        // is an image path, and the loop below knows the difference.
        ['AiAccess', 'images/anee/avatar-160.jpg', 'Chat Anee', 'Asking Anee questions. Answers are paid from your credits.'],
        ['CameraAccess', '📷', 'Camera', 'Taking photos and filing them on this farm.'],
        ['VideoAccess', '🎥', 'Video record', 'Recording clips and attaching them.'],
        // Its own switch: speaking a note is what a farmer does with their
        // hands full, and an owner may want that without lending a camera.
        ['VoiceAccess', '🎙️', 'Voice record', 'Speaking a note instead of typing it.'],
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
                 .value still repaints these buttons. The shed, the maps and
                 the drawing pad start shut; the older levels start readable —
                 the same defaults workerRights.paint uses. --}}
            <input type="hidden" id="{{ $p }}{{ $key }}" class="wr-level"
                   value="{{ in_array($key, ['InventoryAccess', 'MapsAccess', 'DrawAccess'], true) ? 'none' : 'view' }}">
            <span class="wr-seg" role="group" aria-label="{{ $label }} access" data-wr-seg="{{ $p }}{{ $key }}">
                {{-- Activities has no "None": a worker with no eyes on the
                     plan is not a worker on this farm — the least they get
                     is a look. The other modules keep their closed door. --}}
                @if ($key !== 'Access')
                    <button type="button" data-wr-val="none" title="No access">None</button>
                @endif
                <button type="button" data-wr-val="view" title="View only">View</button>
                <button type="button" data-wr-val="edit" title="Can edit &amp; create">Edit</button>
            </span>
        </div>
    @endforeach

    @foreach ($wrSwitches as [$key, $mark, $label, $hint])
        <label class="wr-row wr-switch" for="{{ $p }}{{ $key }}">
            <span class="wr-mark">
                @if (str_contains($mark, '/'))
                    <img src="{{ asset($mark) }}" class="wr-face" alt="">
                @else
                    {{ $mark }}
                @endif
            </span>
            <span class="wr-what">
                <b>{{ $label }}</b>
                <i>{{ $hint }}</i>
            </span>
            <input type="checkbox" id="{{ $p }}{{ $key }}" class="wr-check">
            <span class="wr-toggle" aria-hidden="true"></span>
        </label>
    @endforeach

    <label class="wr-row wr-switch" for="{{ $p }}Community">
        <span class="wr-mark"><img src="{{ asset('images/social-media.png') }}" alt=""></span>
        <span class="wr-what">
            <b>Community</b>
            <i>Their own profile, and posting in the community.</i>
        </span>
        <input type="checkbox" id="{{ $p }}Community" class="wr-check" checked>
        <span class="wr-toggle" aria-hidden="true"></span>
    </label>

    <p class="wr-foot">Every worker can at least <strong>view Activities</strong> — the plan is the farm's common ground. The other doors are yours to open. Changes here save on their own.</p>
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
