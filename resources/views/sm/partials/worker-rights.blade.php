{{-- What a worker may open, module by module.

     One panel, one row shape, two controls. A row is: a mark, what it is,
     what it means, and the answer on the right. The answer is either a
     switch (they have it or they do not) or a three-way segment (no access /
     view only / can edit) — a level cannot honestly be a switch, and a
     dropdown for it hid the answer behind a tap.

     The schedule itself leads, because everything under it depends on it.

     There is no community row. A worker standing in somebody else's farm
     reaches the community through their OWN account — every worker login is
     a full free account with its own feed, its own co-farmers and its own
     name on a post — so a switch here would have been an owner deciding
     whether somebody else may have friends.

     A partial with prefixed ids: it was once drawn twice on the page (a
     "Give access" card at the top, since removed — a login is given from
     the worker's own card now) and the prefix costs nothing to keep.
     Expects: $p, the prefix. Since access is set per cropping schedule
     (2026-10-22) it is drawn in two halves on Global Workers: the rights of
     one access setting ('ga', with 'tools' => false) and the login's own
     home screen tools ('gl', with 'only' => 'tools'). Its CSS and
     window.workerRights (read/paint by prefix) live here too, so any page
     that includes it gets the whole panel.

     Every level keeps a hidden input under the segment carrying the same id
     the sheet's JS has always read, so nothing outside this file had to learn
     a new way to ask. The keys are the ones WorkerGrant::MODULES knows;
     window.workerRights paints this block by the same names and the
     middleware gates the routes by them. One vocabulary end to end. --}}
@php
    // level rows: [id suffix, mark, label, hint]
    $wrLevels = [
        ['Access', '🗓️', 'Activities', 'The daily plan: activities board, lots and calendar.'],
        ['NotesAccess', '📝', 'Notes', 'Day notes and their photos and videos.'],
        ['ReportsAccess', '📊', 'Reports', 'Labor and money reports for this farm.'],
        ['InventoryAccess', '📦', 'Inventory', 'Items on hand, stock moves and what they cost.'],
        ['ObservationsAccess', '🔍', 'Observations', 'Harvest yields, prices, problems and lessons for next season.'],
        ['MoneyAccess', '💰', 'Money', 'Task costs, worker pay, the day totals, extra expenses and income, and the money reports.'],
        ['MapsAccess', '🗺️', 'Maps', 'Field and lot maps. View only, or draw and save too.'],
        ['DrawAccess', '✏️', 'Draw', 'Drawings and saved pictures. View only, or draw new ones.'],
    ];
    $wrSwitches = [
        // Anee's row wears her face, not a robot: a mark with a '/' in it
        // is an image path, and the loop below knows the difference.
        // Every Anee run on this farm, not only the chat (2026-10-22): Realign, the fertilizer reading,
        // Crop Protocol and the other analyses all charge the owner and all ask this switch.
        ['AiAccess', 'images/anee/avatar-160.jpg', 'Anee', 'Chat with Anee and run her readings and analyses here. They use YOUR credits, not theirs.'],
        ['CameraAccess', '📷', 'Camera', 'Take photos and save them to this farm.'],
        ['VideoAccess', '🎥', 'Record video', 'Record clips and attach them.'],
        // Its own switch: speaking a note is what a farmer does with their
        // hands full, and an owner may want that without lending a camera.
        ['VoiceAccess', '🎙️', 'Voice notes', 'Speak a note instead of typing it.'],
    ];
@endphp
@php
    $wrOnlyTools = ($only ?? null) === 'tools';
    $wrWithTools = $wrOnlyTools || ($tools ?? true);
@endphp
<div class="wr-block{{ $wrOnlyTools ? ' is-tools-only' : '' }}" data-wr-block>
    @unless ($wrOnlyTools)
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
                 .value still repaints these buttons. The shed, the season's
                 observations, the maps and the drawing pad start shut; the
                 older levels start readable — the same defaults
                 workerRights.paint uses. --}}
            <input type="hidden" id="{{ $p }}{{ $key }}" class="wr-level"
                   value="{{ in_array($key, ['InventoryAccess', 'ObservationsAccess', 'MoneyAccess', 'MapsAccess', 'DrawAccess'], true) ? 'none' : 'view' }}">
            <span class="wr-seg" role="group" aria-label="{{ $label }} access" data-wr-seg="{{ $p }}{{ $key }}">
                {{-- Activities has no "None": a worker with no eyes on the
                     plan is not a worker on this farm — the least they get
                     is a look. The other modules keep their closed door. --}}
                @if ($key !== 'Access')
                    <button type="button" data-wr-val="none" title="No access">None</button>
                @endif
                <button type="button" data-wr-val="view" title="View only">View</button>
                <button type="button" data-wr-val="edit" title="Can edit and create">Edit</button>
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
    @endunless

    @if ($wrWithTools)
    {{-- THE HOME SCREEN'S TOOLS (2026-10-22, the owner: "in the workers add
         also management switches for the tools in the home or dashboard").
         A switch per tile of Global and Quick Tools, folded behind one row so
         the panel above stays a page long. A tool switched off leaves their
         dashboard on this farm and its address answers no (WorkerTools, the
         one list). Every tool starts on, as every login always had them.
         The hidden level is how All on and All off save: the pages save on a
         real tap of a switch or on input from a .wr-level, and a bulk change
         is neither tap. --}}
    {{-- Its own heading on the login (2026-10-22, the owner: "provide option as well for the access
         of the tools in the dashboard. have that option in the global level"): a login question, the
         same in every season, apart from the access settings that are season by season. --}}
    @if ($wrOnlyTools)
        <p class="wr-head">Dashboard tools · for the whole farm</p>
    @endif
    <div class="wr-tools" data-wr-tools="{{ $p }}">
        <button type="button" class="wr-row wr-tools-head" aria-expanded="false" aria-controls="{{ $p }}ToolsFold">
            <span class="wr-mark"><img src="{{ asset('images/icons/tool-box.png') }}" alt=""></span>
            <span class="wr-what">
                <b>Global and Quick Tools <span class="wr-tools-count" data-wr-tools-count></span></b>
                <i>Which tools on your dashboard they can open, in every season. Tap to choose. On their own farm they have all of theirs.</i>
            </span>
            <svg class="wr-tools-chev" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.4" aria-hidden="true"><path stroke-linecap="round" stroke-linejoin="round" d="M6 9l6 6 6-6"/></svg>
        </button>
        <input type="hidden" class="wr-level" id="{{ $p }}ToolsMark" value="">
        <div class="wr-tools-fold" id="{{ $p }}ToolsFold"><div class="wr-tools-in">
            <div class="wr-tools-bar">
                <button type="button" data-wr-tools-all="1">All on</button>
                <button type="button" data-wr-tools-all="0">All off</button>
            </div>
            @foreach (\App\Support\WorkerTools::GROUPS as $wtGroup => $wtGroupName)
                <p class="wr-tools-group">{{ $wtGroupName }}</p>
                @foreach (\App\Support\WorkerTools::all() as $wtKey => [$wtName, $wtIn, $wtIcon])
                    @continue($wtIn !== $wtGroup)
                    <label class="wr-row wr-switch wr-tool" for="{{ $p }}Tool-{{ $wtKey }}">
                        <span class="wr-mark"><img src="{{ asset($wtIcon) }}" alt=""></span>
                        <span class="wr-what"><b>{{ $wtName }}</b></span>
                        <input type="checkbox" id="{{ $p }}Tool-{{ $wtKey }}" class="wr-check" data-wr-tool="{{ $wtKey }}" checked>
                        <span class="wr-toggle" aria-hidden="true"></span>
                    </label>
                @endforeach
            @endforeach
            <p class="wr-tools-note">Quick Capture, Quick Record and Quick Voice follow the Camera, Record video and Voice notes switches of their access. Compare Reports also needs Reports. Maps and Draw here are the dashboard pages; a season's maps and drawings follow its Maps and Draw.</p>
        </div></div>
    </div>
    @endif

    @unless ($wrOnlyTools)
    {{-- WHOSE CREDITS. The one thing about this panel that costs money, said
         where the switches are rather than discovered on a statement. --}}
    <p class="wr-credits">
        <span class="wr-credits-ico" aria-hidden="true">💳</span>
        <span><b>Anee uses your credits, not theirs.</b>
        When workers use Anee on your farm (chat, Realign, fertilizer
        readings, Crop Protocol and the other analyses), <strong>your</strong>
        account pays. Turn off <strong>Anee</strong> to stop this, and set
        <strong>Reports</strong> to None or View to stop the season reports.
        On their own farm, they pay.</span>
    </p>

    <p class="wr-foot">Every worker can at least <strong>view Activities</strong>. You choose the rest. Changes save by themselves.</p>
    @endunless
</div>

@once
@push('head')
<style>
    /* The rights panel's look, kept with the panel (2026-10-18): it was in
       the season Workers page's own head, so the same panel drawn on Global
       Workers arrived unstyled. */
    /* A row per module, the same shape whether the answer
       is a choice of three or a yes/no. */
    .wr-block { border:1px solid var(--color-gray-200); border-radius:1rem; background:var(--color-white); overflow:hidden; }
    .wr-head { padding:.6rem .85rem; font-size:.7rem; font-weight:800; letter-spacing:.04em;
        text-transform:uppercase; color:var(--color-gray-400); border-bottom:1px solid var(--color-gray-100); }
    .wr-row { display:flex; align-items:center; gap:.7rem; padding:.6rem .85rem;
        border-bottom:1px solid var(--color-gray-100); margin:0; }
    .wr-row:last-of-type { border-bottom:0; }
    .wr-switch { cursor:pointer; user-select:none; }
    .wr-mark { flex:none; width:2rem; height:2rem; border-radius:.6rem; display:flex;
        align-items:center; justify-content:center; font-size:1rem; background:var(--color-brand-50); }
    .wr-mark img { width:1.25rem; height:1.25rem; object-fit:contain; }
    /* A face fills its chip edge to edge; icons float inside theirs. */
    .wr-mark img.wr-face { width:2rem; height:2rem; border-radius:.6rem; object-fit:cover; }
    .wr-what { min-width:0; flex:1 1 auto; }
    .wr-what b { display:block; font-size:.82rem; font-weight:700; color:var(--color-gray-900); line-height:1.25; }
    .wr-what i { display:block; font-style:normal; font-size:.7rem; line-height:1.35; color:var(--color-gray-500); }

    /* --- The answer on the right, in two shapes ---
       A switch for the things you have or do not, a three-way segment for the
       things you can read or also write. Both are the same height and sit in
       the same place, so a column of eight reads as one list rather than as
       four controls that happen to be stacked. */
    .wr-seg { flex:none; display:inline-flex; padding:2px; gap:2px; border-radius:.65rem;
        background:var(--color-gray-100); border:1px solid var(--color-gray-200); }
    .wr-seg button { min-width:3.1rem; padding:.28rem .45rem; border:0; border-radius:.5rem;
        font-size:.7rem; font-weight:700; color:var(--color-gray-500); background:transparent;
        cursor:pointer; transition:background .28s var(--ease-house, cubic-bezier(.22,1,.36,1)), color .28s var(--ease-house, cubic-bezier(.22,1,.36,1)); }
    .wr-seg button:hover { color:var(--color-gray-700); }
    .wr-seg button.is-on { background:var(--color-white); color:var(--color-brand-700);
        box-shadow:0 1px 3px rgb(0 0 0 / .12); }
    .wr-seg button[data-wr-val="none"].is-on { color:var(--color-gray-600); }
    /* Explicit paint in the dark: the token-inverted tints left the chosen
       answer nearly invisible, and an access level you cannot read is an
       access level you cannot trust. */
    html.dark .wr-seg { background:#151b12; border-color:#2b3a1c; }
    html.dark .wr-seg button { color:#93a48a; }
    html.dark .wr-seg button:hover { color:#cfe3bd; }
    html.dark .wr-seg button.is-on { background:#3f5626; color:#e8efe1; box-shadow:0 1px 3px rgb(0 0 0 / .4); }
    html.dark .wr-seg button[data-wr-val="none"].is-on { background:#3a3f36; color:#e8efe1; }

    /* A row that is switched off says so quietly rather than looking broken. */
    .wr-row.is-off .wr-mark, .wr-row.is-off .wr-what { opacity:.55; }

    /* The switch: a real checkbox, moved off screen, wearing this. */
    .wr-check { position:absolute; opacity:0; width:0; height:0; }
    .wr-toggle { flex:none; position:relative; width:2.6rem; height:1.5rem; border-radius:999px;
        background:var(--color-gray-200); border:1px solid var(--color-gray-300);
        transition:background .28s var(--ease-house, cubic-bezier(.22,1,.36,1)), border-color .28s var(--ease-house, cubic-bezier(.22,1,.36,1)); }
    .wr-toggle::after { content:''; position:absolute; top:2px; left:2px; width:1.1rem; height:1.1rem;
        border-radius:999px; background:var(--color-white); box-shadow:0 1px 3px rgb(0 0 0 / .25);
        transition:transform .28s var(--ease-house, cubic-bezier(.22,1,.36,1)); }
    .wr-check:checked + .wr-toggle { background:var(--color-brand-500); border-color:var(--color-brand-500); }
    .wr-check:checked + .wr-toggle::after { transform:translateX(1.1rem); }
    .wr-check:focus-visible + .wr-toggle { outline:2px solid var(--color-brand-500); outline-offset:2px; }
    .wr-switch:has(.wr-check:not(:checked)) .wr-mark,
    .wr-switch:has(.wr-check:not(:checked)) .wr-what { opacity:.55; }
    html.dark .wr-toggle { background:rgb(255 255 255 / .12); border-color:rgb(255 255 255 / .16); }
    @media (prefers-reduced-motion: reduce) {
        .wr-seg button, .wr-toggle, .wr-toggle::after { transition:none; }
    }
    .wr-foot { padding:.6rem .85rem; font-size:.7rem; line-height:1.4; color:var(--color-gray-400);
        border-top:1px solid var(--color-gray-100); background:var(--color-gray-50); }
    html.dark .wr-foot { background:rgb(255 255 255 / .03); }
    /* Whose credits. Amber rather than grey: it is the one line in this
       panel about money leaving an account, and it is read once. */
    .wr-credits { display:flex; gap:.5rem; padding:.65rem .85rem; font-size:.72rem;
        line-height:1.45; color:#92400e; border-top:1px solid #fde68a; background:#fffbeb; }
    .wr-credits b { color:#78350f; }
    .wr-credits-ico { flex:none; font-size:.95rem; line-height:1.35; }
    html.dark .wr-credits { background:rgb(180 83 9 / .14); border-color:rgb(180 83 9 / .35); color:#eec155; }
    html.dark .wr-credits b { color:#fcd34d; }
    /* The home screen's tools: one row that folds the list open, smoothly. */
    .wr-tools { border-top:1px solid var(--color-gray-100); }
    .wr-block.is-tools-only .wr-tools { border-top:0; }
    .wr-tools-head { width:100%; text-align:left; background:none; border:0; border-bottom:0; cursor:pointer;
        transition:background-color .28s var(--ease-house, cubic-bezier(.22,1,.36,1)); }
    @media (hover:hover) { .wr-tools-head:hover { background:var(--color-gray-50); } html.dark .wr-tools-head:hover { background:rgb(255 255 255 / .03); } }
    .wr-tools-head:focus-visible { outline:2px solid var(--color-brand-500); outline-offset:-2px; }
    .wr-tools-count { display:inline-block; margin-left:.3rem; padding:.05rem .45rem; border-radius:999px; font-size:.66rem;
        font-weight:800; vertical-align:.08em; color:var(--color-brand-700); background:var(--color-brand-50); }
    .wr-tools-count.is-some { color:#92400e; background:#fef3c7; }
    html.dark .wr-tools-count { color:#cfe3bd; background:#2b3a1c; }
    html.dark .wr-tools-count.is-some { color:#fcd34d; background:#3a2a0c; }
    .wr-tools-chev { flex:none; width:1.05rem; height:1.05rem; color:var(--color-gray-400);
        transform:rotate(-90deg); transition:transform .28s var(--ease-house, cubic-bezier(.22,1,.36,1)); }
    .wr-tools.is-open .wr-tools-chev { transform:none; }
    .wr-tools-fold { display:grid; grid-template-rows:0fr; opacity:0;
        transition:grid-template-rows .28s var(--ease-house, cubic-bezier(.22,1,.36,1)), opacity .28s var(--ease-house, cubic-bezier(.22,1,.36,1)); }
    .wr-tools.is-open .wr-tools-fold { grid-template-rows:1fr; opacity:1; }
    .wr-tools-in { min-height:0; overflow:hidden; visibility:hidden; transition:visibility 0s linear .28s; }
    .wr-tools.is-open .wr-tools-in { visibility:visible; transition:none; }
    .wr-tools-bar { display:flex; gap:.4rem; padding:.15rem .85rem .5rem; }
    .wr-tools-bar button { padding:.28rem .7rem; border-radius:999px; font-size:.7rem; font-weight:700; cursor:pointer;
        color:var(--color-gray-600); background:var(--color-gray-100); border:1px solid var(--color-gray-200);
        transition:background-color .28s var(--ease-house, cubic-bezier(.22,1,.36,1)), color .28s var(--ease-house, cubic-bezier(.22,1,.36,1)); }
    .wr-tools-bar button:hover { color:var(--color-brand-700); background:var(--color-brand-50); }
    html.dark .wr-tools-bar button { color:#b7c9a3; background:#151b12; border-color:#2b3a1c; }
    .wr-tools-group { padding:.55rem .85rem .2rem; font-size:.64rem; font-weight:800; letter-spacing:.05em;
        text-transform:uppercase; color:var(--color-gray-400); }
    .wr-tool { padding-top:.4rem; padding-bottom:.4rem; }
    .wr-tool .wr-mark { width:1.75rem; height:1.75rem; }
    .wr-tool .wr-mark img { width:1.1rem; height:1.1rem; }
    .wr-tool .wr-what b { font-weight:600; }
    .wr-tools-note { padding:.55rem .85rem .7rem; font-size:.7rem; line-height:1.4; color:var(--color-gray-500); }
    @media (prefers-reduced-motion: reduce) {
        .wr-tools-fold, .wr-tools-chev, .wr-tools-in, .wr-tools-head, .wr-tools-bar button { transition:none; }
    }
    @media (max-width:480px) {
        /* The three-way answers drop their select onto its own line; a yes/no
           does not -- letting those wrap put the box on a line of its own,
           left-aligned under the words, reading as a tick for nothing. */
        /* The answer stays on the row on a phone too: wrapping it under the
           name made a list of eight twice as tall and no clearer. */
        .wr-row { flex-wrap:nowrap; }
        .wr-seg button { min-width:2.6rem; padding-left:.3rem; padding-right:.3rem; }
        .wr-what i { display:none; }
        /* The dashboard tools row keeps its line: it is the one row that says what tapping it opens. */
        .wr-tools-head .wr-what i { display:block; }
    }
</style>
@endpush
@push('scripts')
<script>
/* Read and paint the module switches, by prefix.
 *
 * The prefix outlives the second form that once shared these rights (the
 * "Give access" card at the top of the page, gone now that a login is given
 * from the worker's own card): a right that is read by one name and written
 * by another is how a permission ends up not applying, so the keys are the
 * grant's own column names either way. */
window.workerRights = (() => {
    const LEVELS = ['notesAccess', 'reportsAccess', 'inventoryAccess', 'observationsAccess', 'moneyAccess', 'mapsAccess', 'drawAccess'];
    const SWITCHES = ['aiAccess', 'cameraAccess', 'videoAccess', 'voiceAccess'];
    const id = (p, key) => p + key.charAt(0).toUpperCase() + key.slice(1);
    // The home screen's tools: switched off is what the grant keeps (toolsOff).
    const toolBoxes = (p) => Array.from(document.querySelectorAll('[data-wr-tools="' + p + '"] [data-wr-tool]'));
    const countTools = (p) => {
        const boxes = toolBoxes(p);
        const on = boxes.filter((b) => b.checked).length;
        const tag = document.querySelector('[data-wr-tools="' + p + '"] [data-wr-tools-count]');
        if (!tag) return;
        tag.textContent = on === boxes.length ? 'All on' : (on + ' of ' + boxes.length + ' on');
        tag.classList.toggle('is-some', on !== boxes.length);
    };
    return {
        read(p) {
            const out = {};
            LEVELS.forEach((k) => { const el = document.getElementById(id(p, k)); if (el) out[k] = el.value; });
            SWITCHES.forEach((k) => { const el = document.getElementById(id(p, k)); if (el) out[k] = el.checked ? 1 : 0; });
            const boxes = toolBoxes(p);
            if (boxes.length) out.toolsOff = boxes.filter((b) => !b.checked).map((b) => b.getAttribute('data-wr-tool'));
            return out;
        },
        countTools,
        /* No grant yet means a new worker, and a new worker starts where the
         * app has always put them: able to read the farm, with the owner's
         * tools closed until the owner opens them. */
        paint(p, grant) {
            LEVELS.forEach((k) => {
                const el = document.getElementById(id(p, k));
                if (!el) return;
                // The shed, the observations, the maps and the drawing pad
                // start shut for a new login; the older levels start readable,
                // which is where the app has always put them.
                el.value = (grant && grant[k])
                    || (['inventoryAccess', 'observationsAccess', 'moneyAccess', 'mapsAccess', 'drawAccess'].includes(k) ? 'none' : 'view');
                // The level is a hidden input under a segmented control; the
                // buttons learn what it says from this.
                el.dispatchEvent(new Event('change', { bubbles: true }));
            });
            SWITCHES.forEach((k) => {
                const el = document.getElementById(id(p, k));
                if (el) el.checked = !!(grant && grant[k]);
            });
            // Every tool on unless this login has it switched off.
            const off = (grant && Array.isArray(grant.toolsOff)) ? grant.toolsOff : [];
            toolBoxes(p).forEach((b) => { b.checked = !off.includes(b.getAttribute('data-wr-tool')); });
            countTools(p);
        },
    };
})();

/* The tools list: its fold, its count, and All on / All off. A bulk change
 * saves through the hidden level beside it (an 'input' from a .wr-level is
 * what both pages save on), since it is not a tap on any one switch. */
(function workerToolSwitches() {
    document.querySelectorAll('[data-wr-tools]').forEach((box) => window.workerRights.countTools(box.getAttribute('data-wr-tools')));
    document.addEventListener('click', (e) => {
        const head = e.target.closest('.wr-tools-head');
        if (head) {
            const box = head.closest('[data-wr-tools]');
            const open = !box.classList.contains('is-open');
            box.classList.toggle('is-open', open);
            head.setAttribute('aria-expanded', open ? 'true' : 'false');
            return;
        }
        const all = e.target.closest('[data-wr-tools-all]');
        if (!all) return;
        const box = all.closest('[data-wr-tools]');
        const on = all.getAttribute('data-wr-tools-all') === '1';
        box.querySelectorAll('[data-wr-tool]').forEach((b) => { b.checked = on; });
        window.workerRights.countTools(box.getAttribute('data-wr-tools'));
        box.querySelector('input.wr-level')?.dispatchEvent(new Event('input', { bubbles: true }));
    });
    document.addEventListener('change', (e) => {
        const box = e.target.closest?.('[data-wr-tools]');
        if (box && e.target.hasAttribute('data-wr-tool')) window.workerRights.countTools(box.getAttribute('data-wr-tools'));
    });
})();
</script>
@endpush
@endonce

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
