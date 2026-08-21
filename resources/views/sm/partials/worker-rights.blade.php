{{-- What a worker may open, module by module.

     Two shapes, because the modules are two shapes. Notes and Reports are
     places you can read or add to, so they take the same words the schedule
     does. Maps, Draw, the AI Technician, the camera and the recorder are
     things you either have or you do not.

     Drawn twice on this page — once in "Give access", once in a worker's own
     login sheet — so it is a partial, and the ids are prefixed rather than
     written out. Expects: $p, the prefix ('wl' or 'grant').

     The keys here are the ones WorkerGrant::MODULES knows; window.workerRights
     reads and paints this block by the same names, and the middleware gates
     the routes by them. One vocabulary end to end, so a switch cannot end up
     being a switch for nothing. --}}
<div class="wr-block">
    <p class="wr-head">What they can open</p>

    <div class="wr-row">
        <span class="wr-mark">📝</span>
        <span class="wr-what">
            <b>Notes</b>
            <i>Day notes, and the photos and videos filed with them.</i>
        </span>
        <select id="{{ $p }}NotesAccess" class="form-select wr-pick" aria-label="Notes access">
            <option value="edit">Can edit &amp; create</option>
            <option value="view">View only</option>
            <option value="none">No access</option>
        </select>
    </div>

    <div class="wr-row">
        <span class="wr-mark">📊</span>
        <span class="wr-what">
            <b>Reports</b>
            <i>Labour and revenue reports for this farm.</i>
        </span>
        <select id="{{ $p }}ReportsAccess" class="form-select wr-pick" aria-label="Reports access">
            <option value="edit">Can edit &amp; create</option>
            <option value="view">View only</option>
            <option value="none">No access</option>
        </select>
    </div>

    @foreach ([
        ['MapsAccess', '🗺️', 'Maps', 'Field maps, traces and saved maps.'],
        ['DrawAccess', '✏️', 'Draw', 'The drawing module and its saved pictures.'],
        ['AiAccess', '🤖', 'AI Technician', 'Asking the technician — answers are paid for from your credits.'],
        ['CameraAccess', '📷', 'Camera', 'Taking photos and filing them on this farm.'],
        ['VideoAccess', '🎥', 'Video record', 'Recording clips and attaching them.'],
    ] as [$key, $mark, $label, $hint])
        <label class="wr-row wr-switch" for="{{ $p }}{{ $key }}">
            <span class="wr-mark">{{ $mark }}</span>
            <span class="wr-what">
                <b>{{ $label }}</b>
                <i>{{ $hint }}</i>
            </span>
            <input type="checkbox" id="{{ $p }}{{ $key }}" class="wr-check">
        </label>
    @endforeach

    <p class="wr-foot">A worker with <strong>no schedule access</strong> has none of these — the modules belong to the farm they cannot see.</p>
</div>
