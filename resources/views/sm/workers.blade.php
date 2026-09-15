@extends(request()->boolean('partial') ? 'layouts.partial' : 'layouts.app')

@section('title', 'Workers — ' . $schedule->title)
@section('page-title', 'Workers')
@section('page-subtitle', $schedule->title)
@section('help-key', 'workers')
@section('back', route('sm.hub', ['id' => $schedule->id]))

@section('content')
    @php $canWorkerLogins = auth()->user()->canWorkerAccounts(); @endphp
    @include('sm.partials.module-header', ['schedule' => $schedule, 'module' => 'workers'])
    @include('sm.partials.tag-picker')

    <div>
        {{-- No "Give access" card up here any more: a login is given from
             the worker's own card (its login panel, or the Add Worker
             sheet's account tab), so the roster is the one place it is
             asked. What stays is the word for a plan that cannot. --}}
        @unless (auth()->user()->canWorkerAccounts())
            <div class="card p-4 mb-4 border-amber-200">
                <p class="text-sm text-gray-700"><strong>🔒 Worker logins</strong> are a <strong>Boss/Lifetime</strong> feature. <a href="{{ route('account.subscription') }}" class="text-brand-600 font-semibold">Upgrade</a> to give workers their own login and email notifications.</p>
            </div>
        @endunless

        <div class="flex flex-col sm:flex-row sm:items-center sm:justify-between gap-3 mb-4">
            <p class="text-sm text-gray-500">
                <span id="workerCount" class="font-bold text-gray-900">0</span> <span id="workerCountLabel">workers</span> on this schedule
            </p>
            <button type="button" class="btn btn-primary w-full sm:w-auto shrink-0" data-add-worker>
                <svg class="w-5 h-5" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" d="M12 5v14m-7-7h14"/></svg>
                Add Worker
            </button>
        </div>

        {{-- Full-width responsive grid — one card per worker. Kept separate from
             the empty state below so renderList()'s innerHTML reset can't wipe it. --}}
        <div id="workersList" class="grid gap-3 sm:grid-cols-2 xl:grid-cols-3" data-animate-list></div>

        <div id="workersEmpty" class="card hidden">
            <div class="card-body text-center py-12">
                <div class="mx-auto w-14 h-14 rounded-2xl bg-brand-50 flex items-center justify-center mb-3">
                    <svg class="w-7 h-7 text-brand-600" fill="none" stroke="currentColor" stroke-width="1.8" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" d="M17 20h5v-1a4 4 0 00-4-4h-1M9 11a4 4 0 100-8 4 4 0 000 8zm8 0a3 3 0 100-6M2 20v-1a5 5 0 015-5h4a5 5 0 015 5v1H2z"/></svg>
                </div>
                <h2 class="font-bold text-gray-900 mb-1">No workers yet</h2>
                <p class="text-sm text-gray-500 mb-4">Add the people who will work this schedule. Their cost, skills and off days feed labor costs and assignments.</p>
                <button type="button" class="btn btn-primary" data-add-worker>Add your first worker</button>
            </div>
        </div>
    </div>

    {{-- The team chat float, the same one the Collab Room wears: the chat
         button on a worker's card opens the PM panel right here instead of
         walking the page off to the community. Renders itself only when the
         schedule has a real team. --}}
    @include('sm.partials.schedule-chat-float', ['schedule' => $schedule])

@endsection

@push('sheets')
{{-- Add / edit worker --}}
<div class="sheet hidden" id="workerSheet" style="--sheet-width:36rem">
    <div class="sheet-handle"></div>
    <div class="sheet-header">
        <h3 class="sheet-title" id="workerSheetTitle">Add Worker</h3>
        <button type="button" data-sheet-close class="btn-ghost p-2 rounded-full" aria-label="Close">✕</button>
    </div>
    <div class="sheet-body space-y-4">
        <input type="hidden" id="workerId" value="">

        {{-- Two ways in. A worker who already has an anee.io login is picked
             out by their email and arrives with the name their account
             carries; anyone else is written down here by hand. The strip
             only shows for a new worker — an existing card is one card. --}}
        <div class="wa-tabs" id="workerTabs" role="tablist" aria-label="How to add this worker">
            <button type="button" class="wa-tab is-on" data-wtab="new" role="tab" aria-selected="true">New worker</button>
            <button type="button" class="wa-tab" data-wtab="account" role="tab" aria-selected="false">Has an anee.io account</button>
        </div>

        <div id="workerAccountPane" class="wa-pane space-y-4" hidden>
            <p class="text-sm text-gray-500">Somebody who already logs in to anee.io joins as one of your workers with the name and email their account carries.</p>
            <div>
                <label for="waEmail" class="form-label">Their account email <span class="text-red-500">*</span></label>
                {{-- No Find button: the answer arrives as the address is
                     typed, once it is a whole one. The spinner in the corner
                     says the question is out. --}}
                <div class="wa-field">
                    <input type="email" id="waEmail" maxlength="191" class="form-input" placeholder="e.g. juan@email.com" autocomplete="off" inputmode="email">
                    <span class="wa-spin" id="waSpin" hidden aria-hidden="true"></span>
                </div>
                <p class="form-hint">The exact email they sign in with — it is looked up as you type.</p>
            </div>
            {{-- What the search found: a face and a name, or a plain no. --}}
            <div id="waResult" hidden></div>
        </div>

        <div id="workerNewPane" class="wa-pane space-y-4">
        <div>
            <label for="workerName" class="form-label">Worker Name <span class="text-red-500">*</span></label>
            <input type="text" id="workerName" maxlength="255" class="form-input" placeholder="e.g. Juan Dela Cruz">
        </div>

        <div class="grid grid-cols-2 gap-3">
            <div>
                <label for="workerEmail" class="form-label">Email <span class="text-gray-400 font-normal">(optional)</span></label>
                <input type="email" id="workerEmail" maxlength="255" class="form-input" placeholder="e.g. juan@email.com">
            </div>
            <div>
                <label for="workerPhone" class="form-label">Phone <span class="text-gray-400 font-normal">(optional)</span></label>
                <input type="tel" id="workerPhone" maxlength="32" class="form-input" placeholder="e.g. 0917 123 4567">
            </div>
        </div>
        {{-- What the farm already knows about the address, said as it is
             typed (see wlEmailAsk): a card on this season already carrying
             it (a duplicate, and the save is refused), the same person on
             another of your seasons (their facts, one tap to reuse), or an
             anee.io account (the other door links their login). --}}
        <div id="wlEmailSay" class="wl-email-say" hidden></div>
        <p class="form-hint -mt-2">Email is used to send this worker today's or tomorrow's plan from Quick Share.</p>

        {{-- A new face the phonebook has not met.
             Offered, never assumed: the row unrolls only once the typed
             email is a real one AND no contact of yours already carries it,
             and it rolls away again the moment either stops being true. --}}
        <label class="wl-tocontact" id="wlToContact" hidden>
            <input type="checkbox" id="wlToContactBox" checked>
            <span class="wl-tocontact-box" aria-hidden="true">
                <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="3.2"><path stroke-linecap="round" stroke-linejoin="round" d="M5 13l4 4L19 7"/></svg>
            </span>
            <span class="wl-tocontact-say">
                <b>Add to my Contact List too</b>
                <i>This email is not in your phonebook yet. Saved with a <em>Worker</em> tag, so next season you can find them.</i>
            </span>
        </label>

        <div>
            <label for="workerCost" class="form-label">Cost / Half Day</label>
            <div class="relative">
                <span class="absolute left-4 top-1/2 -translate-y-1/2 text-gray-500 font-semibold pointer-events-none">₱</span>
                <input type="number" id="workerCost" min="0" step="0.01" class="form-input pl-9!" placeholder="0.00">
            </div>
        </div>

        <div>
            <span class="form-label">Skills</span>
            <div id="workerSkills" data-chip-group class="flex flex-wrap gap-2">
                @foreach (\App\Models\AsScheduleWorker::SKILLS as $slug => $label)
                    <button type="button" class="chip" data-value="{{ $slug }}">{{ $label }}</button>
                @endforeach
            </div>
        </div>

        <div>
            <label for="workerNotes" class="form-label">Notes</label>
            <textarea id="workerNotes" rows="3" maxlength="2000" class="form-textarea" placeholder="Anything worth remembering about this worker…"></textarea>
        </div>

        <div>
            <span class="form-label">Tags</span>
            <div class="tp-mount" data-tags data-tags-kind="worker" id="workerTagsMount"></div>
        </div>

        @if ($canWorkerLogins)
        {{-- Login access: give an already-added worker their own login by
             sending a registration link OR setting a password for them. Shows
             only when editing an existing worker. --}}
        <div id="workerLoginSection" class="hidden mt-1 pt-4 border-t border-gray-100">
            <div class="rounded-2xl border border-gray-200 bg-gray-50/60 p-4 space-y-4">
                <div class="flex items-center gap-2.5">
                    <span class="w-9 h-9 rounded-xl bg-brand-50 flex items-center justify-center shrink-0 text-lg">🔑</span>
                    <div class="min-w-0 grow">
                        <p class="font-bold text-gray-900 text-sm">Login access</p>
                        <p class="text-xs text-gray-500" id="wlStatus">No login yet.</p>
                    </div>
                    {{-- Revoke lives in the card's corner as one quiet icon —
                         the confirm sheet still stands between the tap and
                         the act. --}}
                    <button type="button" id="wlRevoke"
                            class="hidden shrink-0 w-9 h-9 rounded-full text-red-500 hover:bg-red-50"
                            title="Revoke access" aria-label="Revoke this worker's login access">
                        <svg class="w-5 h-5" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" d="M17 16l4-4m0 0l-4-4m4 4H9m4 8H7a2 2 0 01-2-2V6a2 2 0 012-2h6"/></svg>
                    </button>
                </div>

                {{-- Writing down what happened is not the same act as changing
                     what is supposed to happen, and the same is true of every
                     module below: a worker who may only look at the plan can
                     still be the right person to record the day, or to keep
                     the maps, and not the right person to spend the farm's AI
                     credits. Each answer stands on its own. --}}
                {{-- What they can open only makes sense once there is a they
                     to open it. Before a login exists these switches are
                     answers to a question nobody has asked — and worse, they
                     look like settings that are already in force. The panel
                     arrives with the login. --}}
                <div id="wlRightsWrap" class="hidden">
                    @include('sm.partials.worker-rights', ['p' => 'wl'])
                </div>
                <p id="wlNoLoginSay" class="text-xs text-gray-500 leading-relaxed">
                    Send a registration link or set a password below. Once this worker
                    can log in, you choose what they are allowed to open.
                </p>

{{-- Community access is a row in the rights panel above. --}}

                <div class="flex flex-col sm:flex-row items-stretch sm:items-center gap-2">
                    {{-- One of these two, never both: a worker who has not
                         registered needs the way in, and one who has needs the
                         way to a new password. paintLogin picks. --}}
                    <button type="button" id="wlSendLink" class="btn btn-white btn-sm w-full sm:w-auto justify-center">✉️ Send registration link</button>
                    <button type="button" id="wlSendPwLink" class="btn btn-white btn-sm w-full sm:w-auto justify-center hidden">✉️ Send password change link</button>
                    <button type="button" id="wlSetPwToggle" class="btn btn-white btn-sm w-full sm:w-auto justify-center">🔒 Set a password</button>
                </div>

                {{-- The password, in its own card. It unfolds on the house
                     easing rather than popping in, and folds away the same
                     road backwards. --}}
                <div id="wlPwFold" class="wl-fold" aria-hidden="true">
                    <div class="wl-fold-in">
                        <div class="rounded-2xl border border-brand-100 bg-white p-4 space-y-3">
                            <p class="font-bold text-gray-900 text-sm flex items-center gap-2">🔒 Password for this worker</p>
                            <div>
                                <label class="form-label" for="wlPassword">New password</label>
                                <input type="password" id="wlPassword" class="form-input" placeholder="At least 8 characters" autocomplete="new-password">
                                {{-- The strength bar: how much of a password this is. --}}
                                <div class="wl-strength mt-2" aria-hidden="true"><i id="wlPwBar"></i></div>
                                <p class="text-xs mt-1 font-semibold text-gray-400" id="wlPwSay">At least 8 characters.</p>
                            </div>
                            <div>
                                <label class="form-label" for="wlPassword2">Repeat the password</label>
                                <input type="password" id="wlPassword2" class="form-input" placeholder="The same password again" autocomplete="new-password">
                                <p class="form-error hidden" id="wlPwMatchSay">The two passwords don't match yet.</p>
                            </div>
                            <button type="button" id="wlCreateLogin" class="btn btn-primary w-full">Create login</button>
                            <p class="form-hint">Share the email above + this password so they can sign in.</p>
                        </div>
                    </div>
                </div>

                <p class="form-hint mt-0!">Uses the worker's <strong>email</strong> above. Add one if it's blank.</p>
            </div>
        </div>
        @endif
        </div>
    </div>
    <div class="sheet-footer">
        {{-- No Cancel: the ✕ in the header already is one. One button per
             door: the second stays shut until the search has found somebody
             who is not on the roster yet. --}}
        <button type="button" id="saveWorkerBtn" class="btn btn-primary w-full">Save Worker</button>
        <button type="button" id="addAccountBtn" class="btn btn-primary w-full" hidden disabled>Add as worker</button>
    </div>
</div>

{{-- Availability rules --}}
<div class="sheet hidden" id="rulesSheet" style="--sheet-width:34rem">
    <div class="sheet-handle"></div>
    <div class="sheet-header">
        <h3 class="sheet-title" id="rulesSheetTitle">Availability Rules</h3>
        <button type="button" data-sheet-close class="btn-ghost p-2 rounded-full" aria-label="Close">✕</button>
    </div>
    <div class="sheet-body space-y-5">
        <input type="hidden" id="rulesWorkerId" value="">

        <div>
            <span class="form-label">Weekly off days</span>
            <p class="form-hint mt-0! mb-2">Tap the days this worker is NOT available.</p>
            <div id="rulesDayGroup" data-chip-group class="flex flex-wrap gap-2">
                @foreach (['Sun', 'Mon', 'Tue', 'Wed', 'Thu', 'Fri', 'Sat'] as $i => $day)
                    <button type="button" class="chip px-3!" data-value="{{ $i }}" data-day="{{ $i }}">{{ $day }}</button>
                @endforeach
            </div>
        </div>

        <div>
            <span class="form-label">Specific off dates</span>
            <div class="flex gap-2 mb-3">
                @include('partials.date-tag', ['id' => 'rulesDateInput', 'empty' => 'Pick a date'])
                <button type="button" id="rulesAddDateBtn" class="btn btn-white shrink-0">Add</button>
            </div>
            <div id="offDatesList" class="flex flex-wrap gap-2"></div>
            <p id="offDatesEmpty" class="text-sm text-gray-400">No off dates added.</p>
        </div>
    </div>
    <div class="sheet-footer">
        <button type="button" class="btn btn-ghost" data-sheet-close>Cancel</button>
        <button type="button" id="saveRulesBtn" class="btn btn-primary">Save Rules</button>
    </div>
</div>
@endpush

@push('head')
<style>
    /* The revoke icon in the login card's corner. Its own display rule so
       the `hidden` utility always wins the argument. */
    #wlRevoke { display: flex; align-items: center; justify-content: center; }
    #wlRevoke.hidden { display: none; }

    /* The password card's fold: a grid row growing from nothing, so the
       card is exactly as tall as itself and the animation has a real end. */
    .wl-fold { display: grid; grid-template-rows: 0fr;
        transition: grid-template-rows .28s cubic-bezier(.22,1,.36,1); }
    .wl-fold.is-open { grid-template-rows: 1fr; }
    .wl-fold-in { overflow: hidden; min-height: 0; }
    .wl-fold .wl-fold-in > div { margin-top: .25rem; }

    /* The strength bar: how much of a password this is, in one glance. */
    .wl-strength { height: .45rem; border-radius: 999px; background: var(--color-gray-100); overflow: hidden; }
    .wl-strength i { display: block; height: 100%; width: 0; border-radius: inherit; background: #ef4444;
        transition: width .28s cubic-bezier(.22,1,.36,1), background .28s cubic-bezier(.22,1,.36,1); }

    @media (prefers-reduced-motion: reduce) {
        .wl-fold, .wl-strength i { transition: none; }
    }

    /* The rights block: a row per module, the same shape whether the answer
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
        cursor:pointer; transition:background .28s var(--ease-house), color .28s var(--ease-house); }
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
        transition:background .28s var(--ease-house), border-color .28s var(--ease-house); }
    .wr-toggle::after { content:''; position:absolute; top:2px; left:2px; width:1.1rem; height:1.1rem;
        border-radius:999px; background:var(--color-white); box-shadow:0 1px 3px rgb(0 0 0 / .25);
        transition:transform .28s var(--ease-house); }
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
    /* "ADD TO MY CONTACT LIST TOO" — the row that unrolls.
       It is an offer, not a field, so it arrives the way an offer should:
       the strip grows to its own height and fades up rather than appearing
       between two things that were already there. max-height carries the
       growth (a fixed ceiling well over what the copy needs), because that
       is the one way a fold closes on every phone this app has met. */
    .wl-tocontact { display:flex; align-items:flex-start; gap:.6rem; cursor:pointer;
        margin-top:-.35rem; padding:.7rem .8rem; border-radius:.85rem;
        border:1px solid var(--color-brand-200); background:var(--color-brand-50);
        overflow:hidden; max-height:9rem; opacity:1;
        transition:max-height .32s cubic-bezier(.22,1,.36,1),
            opacity .28s cubic-bezier(.22,1,.36,1),
            padding .28s cubic-bezier(.22,1,.36,1),
            margin .28s cubic-bezier(.22,1,.36,1),
            border-color .28s cubic-bezier(.22,1,.36,1); }
    /* Rolled away: everything that takes vertical room goes to nothing, so
       the fields above and below close the gap instead of jumping. */
    .wl-tocontact.is-away { max-height:0; opacity:0; padding-top:0; padding-bottom:0;
        margin-top:-.35rem; margin-bottom:-1rem; border-color:transparent; }
    .wl-tocontact input { position:absolute; opacity:0; width:0; height:0; }
    .wl-tocontact-box { flex:none; width:1.25rem; height:1.25rem; border-radius:.4rem; margin-top:.1rem;
        display:flex; align-items:center; justify-content:center;
        border:2px solid var(--color-brand-300); background:var(--color-white); color:#fff;
        transition:background .28s cubic-bezier(.22,1,.36,1), border-color .28s cubic-bezier(.22,1,.36,1); }
    .wl-tocontact-box svg { width:.8rem; height:.8rem; opacity:0; transform:scale(.5);
        transition:opacity .28s cubic-bezier(.22,1,.36,1), transform .28s cubic-bezier(.22,1,.36,1); }
    .wl-tocontact input:checked + .wl-tocontact-box { background:var(--color-brand-600); border-color:var(--color-brand-600); }
    .wl-tocontact input:checked + .wl-tocontact-box svg { opacity:1; transform:none; }
    .wl-tocontact input:focus-visible + .wl-tocontact-box { outline:2px solid var(--color-brand-400); outline-offset:2px; }
    .wl-tocontact-say { min-width:0; }
    .wl-tocontact-say b { display:block; font-size:.82rem; font-weight:800; color:var(--color-brand-800); }
    .wl-tocontact-say i { display:block; font-style:normal; font-size:.72rem; line-height:1.45;
        color:var(--color-brand-700); opacity:.85; margin-top:.1rem; }
    .wl-tocontact-say em { font-style:normal; font-weight:800; }
    html.dark .wl-tocontact { background:rgb(107 159 61 / .12); border-color:#2f4d24; }
    html.dark .wl-tocontact-say b { color:#cfe6b8; }
    html.dark .wl-tocontact-say i { color:#a5c97e; }

    /* THE TWO DOORS INTO ADD WORKER. The same segmented shape the rights
       panel uses, stretched to the sheet's width so the two read as one
       control with two halves rather than two buttons. */
    .wa-tabs { display:grid; grid-template-columns:1fr 1fr; padding:3px; gap:3px; border-radius:.8rem;
        background:var(--color-gray-100); border:1px solid var(--color-gray-200); }
    .wa-tab { padding:.5rem .6rem; border:0; border-radius:.6rem; font-size:.8rem; font-weight:700;
        color:var(--color-gray-500); background:transparent; cursor:pointer;
        transition:background .28s var(--ease-house), color .28s var(--ease-house), box-shadow .28s var(--ease-house); }
    .wa-tab:hover { color:var(--color-gray-700); }
    .wa-tab.is-on { background:var(--color-white); color:var(--color-brand-700); box-shadow:0 1px 3px rgb(0 0 0 / .12); }
    html.dark .wa-tabs { background:#151b12; border-color:#2b3a1c; }
    html.dark .wa-tab { color:#93a48a; }
    html.dark .wa-tab:hover { color:#cfe3bd; }
    html.dark .wa-tab.is-on { background:#3f5626; color:#e8efe1; box-shadow:0 1px 3px rgb(0 0 0 / .4); }
    /* A pane arrives rather than appears: a short rise on the house curve. */
    .wa-pane.is-arriving { animation:wa-arrive .34s var(--ease-house) both; }
    @keyframes wa-arrive { from { opacity:0; transform:translateY(8px); } to { opacity:1; transform:none; } }

    /* WHO THE EMAIL BELONGS TO. A face, a name, and the one line that says
       what adding them will mean. */
    .wa-card { display:flex; align-items:center; gap:.75rem; padding:.8rem .9rem; border-radius:1rem;
        border:1px solid var(--color-brand-200); background:var(--color-brand-50); }
    .wa-face { flex:none; width:2.75rem; height:2.75rem; border-radius:999px; overflow:hidden;
        display:flex; align-items:center; justify-content:center; font-weight:800; font-size:.95rem;
        background:var(--color-brand-100); color:var(--color-brand-700); }
    .wa-face img { width:100%; height:100%; object-fit:cover; }
    .wa-who { min-width:0; flex:1; }
    .wa-who b { display:block; color:var(--color-gray-900); font-size:.95rem; line-height:1.2; }
    .wa-who span { display:block; color:var(--color-gray-500); font-size:.78rem; overflow-wrap:anywhere; }
    .wa-say { margin-top:.6rem; font-size:.8rem; line-height:1.45; color:var(--color-gray-600); }
    .wa-say.is-stop { color:#b45309; font-weight:600; }
    .wa-none { padding:.8rem .9rem; border-radius:1rem; border:1px dashed var(--color-gray-300);
        font-size:.85rem; color:var(--color-gray-600); }
    .wa-none button { margin-top:.5rem; }
    .wa-field { position:relative; }
    .wa-field .form-input { padding-right:2.4rem; }
    .wa-spin { position:absolute; right:.85rem; top:50%; width:1rem; height:1rem; margin-top:-.5rem;
        border-radius:999px; border:2px solid var(--color-brand-200); border-top-color:var(--color-brand-600);
        animation:waSpin .8s linear infinite; }
    @keyframes waSpin { to { transform:rotate(360deg); } }
    /* THE ADDRESS, KNOWN. A line under the email field: the colour says
       whether it is a stop (already on this season), a hand (the same
       person elsewhere, or an account), and the button beside it does the
       one thing worth doing about it. Unrolls the way the phonebook offer
       does. */
    .wl-email-say { display:flex; flex-wrap:wrap; align-items:center; gap:.5rem .75rem;
        margin-top:-.35rem; padding:.6rem .8rem; border-radius:.85rem; font-size:.78rem; line-height:1.45;
        border:1px solid var(--color-brand-200); background:var(--color-brand-50); color:var(--color-gray-700);
        animation:wa-arrive .3s var(--ease-house) both; }
    .wl-email-say b { color:var(--color-gray-900); }
    .wl-email-say .btn { margin-left:auto; }
    .wl-email-say.is-stop { border-color:#f7d4cf; background:#fdf0ee; color:#9a1d13; }
    .wl-email-say.is-stop b { color:#7a1a12; }
    .wl-email-say.is-note { border-color:var(--color-gray-200); background:var(--color-gray-50); }
    html.dark .wl-email-say { background:rgb(107 159 61 / .12); border-color:#2f4d24; color:#cfe6b8; }
    html.dark .wl-email-say b { color:#e8efe1; }
    html.dark .wl-email-say.is-stop { background:rgb(180 35 24 / .14); border-color:rgb(243 165 156 / .28); color:#f3a59c; }
    html.dark .wl-email-say.is-stop b { color:#ffd3cc; }
    html.dark .wl-email-say.is-note { background:#151b12; border-color:#2b3a1c; color:#a5b89a; }
    @media (prefers-reduced-motion:reduce) { .wa-spin { animation:none; } .wl-email-say { animation:none; } }
    html.dark .wa-card { background:rgb(107 159 61 / .12); border-color:#2f4d24; }
    html.dark .wa-face { background:#2f4d24; color:#cfe6b8; }
    html.dark .wa-who b { color:#e8efe1; }
    html.dark .wa-who span, html.dark .wa-say, html.dark .wa-none { color:#a5b89a; }
    html.dark .wa-say.is-stop { color:#f5c26b; }
    html.dark .wa-none { border-color:#2b3a1c; }
    @media (prefers-reduced-motion:reduce) {
        .wa-tab { transition:none; }
        .wa-pane.is-arriving { animation:none; }
    }
    @media (prefers-reduced-motion:reduce) {
        .wl-tocontact, .wl-tocontact-box, .wl-tocontact-box svg { transition:none; }
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
    }
</style>
@endpush

@push('scripts')
@php
    $jsWorkers = $schedule->workers->map(fn ($w) => [
        'id' => $w->id,
        'workerName' => $w->workerName,
        'email' => $w->email,
        'phone' => $w->phone,
        'costPerHalfDay' => $w->costPerHalfDay,
        'priority' => (int) $w->priority,
        'skills' => $w->skills ?? [],
        'notes' => $w->notes,
        'offDays' => $w->offDays->pluck('dayOfWeek')->map(fn ($d) => (int) $d)->values(),
        'offDates' => $w->offDates->map(fn ($d) => $d->offDate->format('Y-m-d'))->values(),
        'login' => ($grantByWorker ?? [])[$w->id] ?? null,
    ])->values();
@endphp
<script>
/* Read and paint the module switches, by prefix.
 *
 * The prefix outlives the second form that once shared these rights (the
 * "Give access" card at the top of the page, gone now that a login is given
 * from the worker's own card): a right that is read by one name and written
 * by another is how a permission ends up not applying, so the keys are the
 * grant's own column names either way. */
window.workerRights = (() => {
    const LEVELS = ['notesAccess', 'reportsAccess', 'inventoryAccess', 'mapsAccess', 'drawAccess'];
    const SWITCHES = ['aiAccess', 'cameraAccess', 'videoAccess', 'voiceAccess'];
    const id = (p, key) => p + key.charAt(0).toUpperCase() + key.slice(1);
    return {
        read(p) {
            const out = {};
            LEVELS.forEach((k) => { const el = document.getElementById(id(p, k)); if (el) out[k] = el.value; });
            SWITCHES.forEach((k) => { const el = document.getElementById(id(p, k)); if (el) out[k] = el.checked ? 1 : 0; });
            return out;
        },
        /* No grant yet means a new worker, and a new worker starts where the
         * app has always put them: able to read the farm, with the owner's
         * tools closed until the owner opens them. */
        paint(p, grant) {
            LEVELS.forEach((k) => {
                const el = document.getElementById(id(p, k));
                if (!el) return;
                // The shed, the maps and the drawing pad start shut for a new
                // login; the older levels start readable, which is where the
                // app has always put them.
                el.value = (grant && grant[k])
                    || (['inventoryAccess', 'mapsAccess', 'drawAccess'].includes(k) ? 'none' : 'view');
                // The level is a hidden input under a segmented control; the
                // buttons learn what it says from this.
                el.dispatchEvent(new Event('change', { bubbles: true }));
            });
            SWITCHES.forEach((k) => {
                const el = document.getElementById(id(p, k));
                if (el) el.checked = !!(grant && grant[k]);
            });
        },
    };
})();
(() => {
const __init = () => {
    const SCHEDULE_ID = {{ $schedule->id }};
    const SKILLS = @json(\App\Models\AsScheduleWorker::SKILLS);
    const DAY_NAMES = ['Sun', 'Mon', 'Tue', 'Wed', 'Thu', 'Fri', 'Sat'];
    // The house chat mark — the same picture every chat door now wears.
    const CHAT_ICON = @json(asset('images/icons/chat.png'));

    let WORKERS = @json($jsWorkers);
    const CAN_LOGINS = @json($canWorkerLogins);
    let editingWorker = null;   // the worker whose sheet is open (for login controls)

    const list = document.getElementById('workersList');
    const empty = document.getElementById('workersEmpty');

    function loginPillHtml(login) {
        if (!login) return '';
        if (login.status === 'active') return '<span class="inline-flex items-center gap-1 text-xs font-semibold text-green-700 bg-green-50 border border-green-100 rounded-full px-2 py-0.5">🔑 Login</span>';
        if (login.status === 'pending') return '<span class="inline-flex items-center gap-1 text-xs font-semibold text-amber-700 bg-amber-50 border border-amber-100 rounded-full px-2 py-0.5">🔑 Invite sent</span>';
        return '';
    }
    // PM a worker who has a login: the same floating chat the Collab Room
    // wears, opened right here. The page carries the float itself now, so
    // this never has to leave for the community to say hello.
    function openWorkerPm(userId, name) {
        if (typeof window.scheduleTeamPm === 'function') window.scheduleTeamPm(userId, name);
        else toast('Chat opens once this schedule has a team: a worker with their own login.', 'info');
    }

    const fmtDate = (iso) => new Date(`${iso}T00:00:00`).toLocaleDateString('en-US', { month: 'short', day: 'numeric', year: 'numeric' });

    function offRulesSummary(w) {
        const parts = [];
        if ((w.offDays || []).length) {
            parts.push('Off: ' + [...w.offDays].sort((a, b) => a - b).map((d) => DAY_NAMES[d]).join(', '));
        }
        if ((w.offDates || []).length) {
            parts.push(`${w.offDates.length} off ${w.offDates.length === 1 ? 'date' : 'dates'}`);
        }
        return parts.length ? parts.join(' · ') : 'No off rules';
    }

    // Initials for the avatar (first letters of up to two name words).
    function workerInitials(name) {
        const parts = String(name || '').trim().split(/\s+/).filter(Boolean);
        const ini = parts.slice(0, 2).map((p) => p[0] || '').join('');
        return (ini || '?').toUpperCase();
    }

    function workerCardHtml(w) {
        const skills = (w.skills || [])
            .map((s) => `<span class="badge badge-gray">${escapeHtml(SKILLS[s] || s)}</span>`)
            .join(' ');
        const hue = ((Number(w.id) || 0) * 137) % 360;   // golden-angle → stable, distinct per worker
        const offRules = offRulesSummary(w);
        const contactLine = (icon, value) => `<p class="text-xs text-gray-500 flex items-center gap-1.5 truncate"><svg class="w-3.5 h-3.5 shrink-0 text-gray-400" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24">${icon}</svg><span class="truncate">${escapeHtml(value)}</span></p>`;

        return `
            <div class="card-body h-full flex flex-col py-4! gap-3">
                <div class="flex items-start gap-3">
                    <span class="w-11 h-11 rounded-xl flex items-center justify-center text-white font-bold text-sm shrink-0" style="background:hsl(${hue}, 55%, 45%)" aria-hidden="true">${escapeHtml(workerInitials(w.workerName))}</span>
                    <div class="min-w-0 grow">
                        <div class="flex items-center gap-2 flex-wrap">
                            <h3 class="font-bold text-gray-900 truncate">${escapeHtml(w.workerName)}</h3>
                            ${CAN_LOGINS ? loginPillHtml(w.login) : ''}
                        </div>
                        <p class="text-sm text-gray-600 mt-0.5"><span class="font-semibold text-gray-900">${fmtPeso(w.costPerHalfDay)}</span> <span class="text-gray-400">/ half day</span></p>
                    </div>
                </div>

                <div class="min-w-0 grow space-y-1.5">
                    ${w.email ? contactLine('<path stroke-linecap="round" stroke-linejoin="round" d="M3 8l7.89 5.26a2 2 0 002.22 0L21 8M5 19h14a2 2 0 002-2V7a2 2 0 00-2-2H5a2 2 0 00-2 2v10a2 2 0 002 2z"/>', w.email) : ''}
                    ${w.phone ? contactLine('<path stroke-linecap="round" stroke-linejoin="round" d="M3 5a2 2 0 012-2h3.28a1 1 0 01.948.684l1.498 4.493a1 1 0 01-.502 1.21l-2.257 1.13a11.042 11.042 0 005.516 5.516l1.13-2.257a1 1 0 011.21-.502l4.493 1.498a1 1 0 01.684.949V19a2 2 0 01-2 2h-1C9.716 21 3 14.284 3 6V5z"/>', w.phone) : ''}
                    ${skills ? `<div class="flex flex-wrap gap-1.5 pt-0.5">${skills}</div>` : ''}
                    <p class="text-xs ${offRules === 'No off rules' ? 'text-gray-400' : 'text-orange-700 font-medium'} off-rules-line flex items-center gap-1.5"><svg class="w-3.5 h-3.5 shrink-0 ${offRules === 'No off rules' ? 'text-gray-300' : 'text-orange-400'}" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24"><rect x="3" y="4" width="18" height="17" rx="2"/><path stroke-linecap="round" d="M16 2v4M8 2v4M3 10h18"/></svg><span class="truncate">${escapeHtml(offRules)}</span></p>
                    ${w.notes ? `<p class="text-xs text-gray-500 pt-0.5 line-clamp-2">${escapeHtml(w.notes)}</p>` : ''}
                </div>

                <div class="flex items-center gap-1.5 pt-3 border-t border-gray-100">
                    ${w.login && w.login.workerUserId ? `<button type="button" class="btn btn-white btn-sm px-2.5!" data-pm-worker="${w.login.workerUserId}" data-pm-name="${escapeHtml(w.workerName)}" title="Message ${escapeHtml(w.workerName)}" aria-label="Message ${escapeHtml(w.workerName)}"><img src="${CHAT_ICON}" alt="" class="w-4.5 h-4.5" style="object-fit:contain"></button>` : ''}
                    <button type="button" class="btn btn-white btn-sm" data-rules-worker="${w.id}">Rules</button>
                    <button type="button" class="btn btn-white btn-sm" data-edit-worker="${w.id}">Edit</button>
                    <button type="button" class="btn btn-ghost btn-sm px-2.5! text-red-500 hover:bg-red-50! ml-auto" data-delete-worker="${w.id}" aria-label="Delete worker">
                        <svg class="w-4.5 h-4.5" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" d="M3 6h18M19 6v14a2 2 0 01-2 2H7a2 2 0 01-2-2V6M8 6V4a2 2 0 012-2h4a2 2 0 012 2v2M10 11v6M14 11v6"/></svg>
                    </button>
                </div>
            </div>`;
    }

    function renderList() {
        WORKERS.sort((a, b) => (a.priority - b.priority) || String(a.workerName).localeCompare(String(b.workerName)));
        list.innerHTML = '';
        WORKERS.forEach((w) => {
            const card = document.createElement('div');
            card.className = 'card h-full';   // h-full → equal-height cards across a grid row
            card.dataset.workerCard = w.id;
            card.innerHTML = workerCardHtml(w);
            list.appendChild(card);
        });
        empty.classList.toggle('hidden', WORKERS.length > 0);
        const countEl = document.getElementById('workerCount');
        if (countEl) countEl.textContent = WORKERS.length;
        const labelEl = document.getElementById('workerCountLabel');
        if (labelEl) labelEl.textContent = WORKERS.length === 1 ? 'worker' : 'workers';
    }

    /* ---------------- Worker sheet ---------------- */

    function openWorkerSheet(w = null) {
        document.getElementById('workerSheetTitle').textContent = w ? 'Edit Worker' : 'Add Worker';
        document.getElementById('workerId').value = w ? w.id : '';
        document.getElementById('workerName').value = w ? (w.workerName || '') : '';
        document.getElementById('workerEmail').value = w ? (w.email || '') : '';
        document.getElementById('workerPhone').value = w ? (w.phone || '') : '';
        document.getElementById('workerCost').value = w ? (parseFloat(w.costPerHalfDay) || 0) : '';
        document.getElementById('workerNotes').value = w ? (w.notes || '') : '';
        const selected = (w?.skills || []).map(String);
        document.querySelectorAll('#workerSkills .chip').forEach((c) => {
            c.classList.toggle('is-selected', selected.includes(c.getAttribute('data-value')));
        });
        // The word-tags this worker wears (the shared picker).
        const tagsMount = document.getElementById('workerTagsMount');
        if (window.smTags && tagsMount) {
            window.smTags.mount(tagsMount);
            if (w && w.id) window.smTags.load(tagsMount, 'worker', w.id);
            else window.smTags.clear(tagsMount);
        }
        // Login controls only make sense for a saved worker (needs an id to link).
        editingWorker = w;
        paintLogin(w);
        toContactReset();
        wlEmailReset();
        // Two doors for a new worker; an existing card is one card.
        document.getElementById('workerTabs').hidden = !!w;
        waReset();
        showWorkerTab('new', false);
        openSheet('workerSheet');
    }

    /* ---------------- The second door: an anee.io account ----------------
     *
     * The owner types the email the person signs in with, the server says
     * whether an account is behind it, and the card shows who. Adding them
     * writes a worker card from the account's own name and email, and —
     * where the plan allows logins — links the account to this farm at once
     * with the same starting rights a new login always gets (read the plan,
     * the owner's tools shut), changeable from their card afterwards. */
    const ACCOUNT_URL = @json(route('sm.workers.account'));
    const GRANT_URL = @json(route('sm.workers.access.grant'));
    let waFound = null;        // what the last search answered, or null
    let waSeq = 0;             // a slow answer about an older email is ignored

    function showWorkerTab(which, animate = true) {
        document.querySelectorAll('#workerTabs .wa-tab').forEach((t) => {
            const on = t.dataset.wtab === which;
            t.classList.toggle('is-on', on);
            t.setAttribute('aria-selected', on ? 'true' : 'false');
        });
        const panes = { new: document.getElementById('workerNewPane'), account: document.getElementById('workerAccountPane') };
        Object.entries(panes).forEach(([key, pane]) => {
            const on = key === which;
            pane.classList.remove('is-arriving');
            if (on && pane.hidden && animate) {
                pane.hidden = false;
                void pane.offsetWidth;
                pane.classList.add('is-arriving');
                pane.addEventListener('animationend', () => pane.classList.remove('is-arriving'), { once: true });
            } else {
                pane.hidden = !on;
            }
        });
        const onAccount = which === 'account';
        document.getElementById('saveWorkerBtn').hidden = onAccount;
        const addBtn = document.getElementById('addAccountBtn');
        addBtn.hidden = !onAccount;
        addBtn.disabled = !(waFound && waFound.found && !waFound.onRoster);
        if (onAccount) window.smFocus?.('waEmail');
    }

    document.getElementById('workerTabs').addEventListener('click', (e) => {
        const t = e.target.closest('.wa-tab');
        if (t) showWorkerTab(t.dataset.wtab);
    });

    function waReset() {
        waSeq++;
        waFound = null;
        document.getElementById('waEmail').value = '';
        const out = document.getElementById('waResult');
        out.hidden = true;
        out.innerHTML = '';
        document.getElementById('addAccountBtn').disabled = true;
    }

    function waPaint(d) {
        const out = document.getElementById('waResult');
        if (d.self) {
            out.innerHTML = `<p class="wa-say is-stop">That is your own account — you are the owner here, not a worker.</p>`;
            out.hidden = false;
            return;
        }
        if (!d.found) {
            out.innerHTML = `<div class="wa-none">No anee.io account signs in with that email.
                <button type="button" class="btn btn-white btn-sm" id="waAsNew">Add them as a new worker instead</button></div>`;
            out.hidden = false;
            document.getElementById('waAsNew').addEventListener('click', () => {
                // Their email carries over, so it is not typed twice.
                document.getElementById('workerEmail').value = document.getElementById('waEmail').value.trim();
                document.getElementById('workerEmail').dispatchEvent(new Event('input', { bubbles: true }));
                showWorkerTab('new');
                window.smFocus?.('workerName');
            });
            return;
        }
        const a = d.account;
        const face = a.avatar ? `<img src="${escapeHtml(a.avatar)}" alt="">` : escapeHtml(a.initials || '·');
        let say, stop = false;
        if (d.onRoster) { say = `${escapeHtml(a.name)} is already a worker on this schedule.`; stop = true; }
        else if (d.login === 'active') say = 'Already has access to your farm — they are added as a worker with the rights you gave them.';
        else if (CAN_LOGINS) say = 'Added as a worker, and their account can open this farm with view access. Change what they may open from their card afterwards.';
        else say = 'Added as a worker with this name and email. Worker logins come with the Boss plan.';
        out.innerHTML = `<div class="wa-card">
                <span class="wa-face">${face}</span>
                <span class="wa-who"><b>${escapeHtml(a.name)}</b><span>${escapeHtml(a.email)}</span>${a.since ? `<span>On anee.io since ${escapeHtml(a.since)}</span>` : ''}</span>
            </div>
            <p class="wa-say${stop ? ' is-stop' : ''}">${say}</p>`;
        out.hidden = false;
    }

    const EMAIL_RX = /^[^@\s]+@[^@\s]+\.[^@\s]+$/;
    let waDebounce;
    async function waFind() {
        const email = document.getElementById('waEmail').value.trim();
        if (!EMAIL_RX.test(email)) return;
        const spin = document.getElementById('waSpin');
        const mine = ++waSeq;
        spin.hidden = false;
        try {
            const res = await api(`${ACCOUNT_URL}?scheduleId=${SCHEDULE_ID}&email=${encodeURIComponent(email)}`);
            if (mine !== waSeq) return;
            waFound = res.data;
            waPaint(res.data);
            document.getElementById('addAccountBtn').disabled = !(waFound.found && !waFound.self && !waFound.onRoster);
        } catch (err) {
            if (mine !== waSeq) return;
            waFound = null;
            document.getElementById('addAccountBtn').disabled = true;
            toast(err.message, 'error');
        } finally { if (mine === waSeq) spin.hidden = true; }
    }
    document.getElementById('waEmail').addEventListener('keydown', (e) => { if (e.key === 'Enter') { e.preventDefault(); clearTimeout(waDebounce); waFind(); } });
    /* Asked as the address is typed, once it is a whole one, after the
       typing pauses. A changed email is a new question, so the old answer
       goes at once -- it must not add the wrong person. */
    document.getElementById('waEmail').addEventListener('input', () => {
        waSeq++;
        waFound = null;
        document.getElementById('addAccountBtn').disabled = true;
        const out = document.getElementById('waResult');
        out.hidden = true; out.innerHTML = '';
        document.getElementById('waSpin').hidden = true;
        clearTimeout(waDebounce);
        if (EMAIL_RX.test(document.getElementById('waEmail').value.trim())) waDebounce = setTimeout(waFind, 450);
    });

    /* ---------------- The address, known: the new-worker form ------------
     *
     * One person works for several owners and on several seasons, and a
     * card typed fresh each time is how they end up as three slightly
     * different people. So as the email is typed here the same question
     * the account tab asks is asked, and the answer sits under the field:
     * a card on THIS season already carrying it is a duplicate (said in
     * red, and the save is refused until it changes); the same person on
     * another of your seasons is one tap from being reused as they are;
     * an anee.io account is one tap from the other door, which links
     * their login. Editing a card excludes the card itself. */
    let wlEmailSeq = 0;
    let wlEmailDebounce;
    let wlEmailBlocked = false;
    const wlEmailBox = () => document.getElementById('wlEmailSay');

    function wlEmailReset() {
        clearTimeout(wlEmailDebounce);
        wlEmailSeq++;
        wlEmailBlocked = false;
        const box = wlEmailBox();
        if (box) { box.hidden = true; box.innerHTML = ''; box.className = 'wl-email-say'; }
        document.getElementById('saveWorkerBtn').disabled = false;
    }

    function wlEmailPaint(d, editing) {
        const box = wlEmailBox();
        if (!box) return;
        wlEmailBlocked = false;
        let html = '';
        let tone = '';
        if (d.onRoster) {
            tone = 'is-stop';
            wlEmailBlocked = true;
            html = `<span><b>${escapeHtml(d.onRosterName || 'Somebody')}</b> is already on this schedule with that email.</span>
                <button type="button" class="btn btn-white btn-sm" data-wl-open="${d.onRoster}">Open their card</button>`;
        } else if (d.self) {
            tone = 'is-note';
            html = `<span>That is your own email. A card for yourself needs no email — the plan is already yours.</span>`;
        } else if (d.found && !editing) {
            html = `<span><b>${escapeHtml(d.account.name)}</b> signs in to anee.io with that email. Add them from the account tab and their login links to this farm.</span>
                <button type="button" class="btn btn-primary btn-sm" data-wl-account>Use their account</button>`;
        } else if (d.found && editing) {
            tone = 'is-note';
            html = `<span>That email signs in to <b>${escapeHtml(d.account.name)}</b>'s anee.io account${d.login === 'active' ? ', which already has access to your farm' : ''}.</span>`;
        } else if (d.elsewhere) {
            html = `<span>You already have <b>${escapeHtml(d.elsewhere.name)}</b> on <b>${escapeHtml(d.elsewhere.scheduleTitle)}</b>.</span>
                <button type="button" class="btn btn-white btn-sm" data-wl-reuse>Use the same details</button>`;
        }
        document.getElementById('saveWorkerBtn').disabled = wlEmailBlocked;
        if (!html) { box.hidden = true; box.innerHTML = ''; return; }
        box.className = 'wl-email-say' + (tone ? ' ' + tone : '');
        box.innerHTML = html;
        box.hidden = false;
        box.querySelector('[data-wl-open]')?.addEventListener('click', () => {
            const twin = WORKERS.find((w) => String(w.id) === String(d.onRoster));
            closeSheet('workerSheet');
            if (twin) setTimeout(() => openWorkerSheet(twin), 350);
        });
        box.querySelector('[data-wl-account]')?.addEventListener('click', () => {
            // The address carries over, and the other door asks its question at once.
            const wa = document.getElementById('waEmail');
            wa.value = document.getElementById('workerEmail').value.trim();
            showWorkerTab('account');
            wa.dispatchEvent(new Event('input', { bubbles: true }));
        });
        box.querySelector('[data-wl-reuse]')?.addEventListener('click', () => {
            const e = d.elsewhere;
            document.getElementById('workerName').value = e.name || document.getElementById('workerName').value;
            if (e.phone && !document.getElementById('workerPhone').value.trim()) document.getElementById('workerPhone').value = e.phone;
            if (e.costPerHalfDay && !document.getElementById('workerCost').value) document.getElementById('workerCost').value = e.costPerHalfDay;
            const skills = (e.skills || []).map(String);
            if (skills.length) document.querySelectorAll('#workerSkills .chip').forEach((c) => c.classList.toggle('is-selected', skills.includes(c.getAttribute('data-value'))));
            toast(`${e.name}'s details from ${e.scheduleTitle} are filled in.`);
            box.hidden = true;
        });
    }

    async function wlEmailAsk() {
        const box = wlEmailBox();
        if (!box) return;
        const email = document.getElementById('workerEmail').value.trim();
        const editingId = document.getElementById('workerId').value;
        if (!EMAIL_RX.test(email)) { wlEmailReset(); return; }
        const mine = ++wlEmailSeq;
        try {
            const res = await api(`${ACCOUNT_URL}?scheduleId=${SCHEDULE_ID}&email=${encodeURIComponent(email)}${editingId ? '&exclude=' + encodeURIComponent(editingId) : ''}`);
            if (mine !== wlEmailSeq) return;
            wlEmailPaint(res.data, !!editingId);
        } catch (_) {
            if (mine === wlEmailSeq) { wlEmailBlocked = false; document.getElementById('saveWorkerBtn').disabled = false; }
        }
    }
    document.getElementById('workerEmail').addEventListener('input', () => {
        clearTimeout(wlEmailDebounce);
        wlEmailSeq++;
        // The old answer is about an old address: it goes at once, and the
        // save is free again until the new answer says otherwise.
        wlEmailBlocked = false;
        document.getElementById('saveWorkerBtn').disabled = false;
        wlEmailDebounce = setTimeout(wlEmailAsk, 450);
    });

    document.getElementById('addAccountBtn').addEventListener('click', async (e) => {
        const btn = e.currentTarget;
        if (!waFound || !waFound.found || waFound.onRoster) return;
        const a = waFound.account;
        btn.disabled = true;
        try {
            const res = await api(`{{ route('sm.workers.store') }}?scheduleId=${SCHEDULE_ID}`, { method: 'POST', body: { accountUserId: a.id } });
            /* The login. An account the farm already granted keeps the
               rights the owner chose — the card simply shows them. A new one
               is linked now, with a new login's starting rights, and the
               grant's own reply is what the toast says. Best-effort past the
               card: a login that could not be linked is a line, not a
               failure of the worker, who is on the roster either way. */
            let login = waFound.grant || null;
            let said = res.message;
            if (CAN_LOGINS && !login) {
                try {
                    const g = await api(GRANT_URL, { method: 'POST', body: { scheduleWorkerId: res.data.id, email: a.email, scheduleAccess: 'view' } });
                    login = g.data && g.data.grant;
                    said = g.message || said;
                } catch (err) {
                    toast('Added as a worker, but their login could not be linked: ' + err.message, 'error');
                }
            }
            WORKERS.push({
                id: res.data.id,
                workerName: res.data.workerName,
                email: res.data.email,
                phone: res.data.phone,
                costPerHalfDay: res.data.costPerHalfDay,
                priority: Number(res.data.priority) || 1,
                skills: res.data.skills || [],
                notes: res.data.notes,
                offDays: [], offDates: [],
                login,
            });
            renderList();
            closeSheet('workerSheet');
            toast(said);
            window.smSpot?.('[data-worker-card="' + res.data.id + '"]');
        } catch (err) {
            toast(err.message, 'error');
            btn.disabled = false;
        }
    });

    /* ------------- "Add to my Contact List too" -------------
     *
     * Only ever offered for a NEW worker, and only once the typed email is
     * both valid and unknown to the phonebook. Asking the server on every
     * keystroke would be a request per letter, so it waits for the typing to
     * stop; and every answer is stamped with the email it was about, because
     * a slow reply about "juan@" must not decide the row for "juana@".
     */
    const TO_CONTACT_URL = @json(route('contacts.lookup'));
    let toContactSeq = 0;
    let toContactDebounce;

    function toContactRow() { return document.getElementById('wlToContact'); }

    function toContactShow(on) {
        const row = toContactRow();
        if (!row) return;
        if (on) {
            // hidden must come off before the class, or there is nothing
            // laid out for the height to animate from.
            row.hidden = false;
            row.classList.add('is-away');
            requestAnimationFrame(() => requestAnimationFrame(() => row.classList.remove('is-away')));
        } else if (!row.hidden) {
            row.classList.add('is-away');
            setTimeout(() => { if (row.classList.contains('is-away')) row.hidden = true; }, 340);
        }
    }

    function toContactReset() {
        const row = toContactRow();
        if (!row) return;
        clearTimeout(toContactDebounce);
        toContactSeq++;
        row.hidden = true;
        row.classList.add('is-away');
        document.getElementById('wlToContactBox').checked = true;
    }

    async function toContactAsk() {
        const row = toContactRow();
        if (!row) return;
        // Editing an existing worker is not the moment to file anybody.
        if (document.getElementById('workerId').value) { toContactShow(false); return; }
        const email = document.getElementById('workerEmail').value.trim();
        if (!/^[^@\s]+@[^@\s]+\.[^@\s]+$/.test(email)) { toContactShow(false); return; }
        const mine = ++toContactSeq;
        try {
            const res = await api(TO_CONTACT_URL + '?email=' + encodeURIComponent(email));
            if (mine !== toContactSeq) return;
            toContactShow(!res.data.exists);
        } catch (_) {
            if (mine === toContactSeq) toContactShow(false);
        }
    }

    document.getElementById('workerEmail').addEventListener('input', () => {
        clearTimeout(toContactDebounce);
        toContactDebounce = setTimeout(toContactAsk, 450);
    });

    /* ---------------- Worker login controls ---------------- */

    function paintLogin(w) {
        const sec = document.getElementById('workerLoginSection');
        if (!sec) return;   // not the boss / no login controls rendered
        const show = !!(w && w.id);
        sec.classList.toggle('hidden', !show);
        if (!show) return;
        const login = w.login || null;
        const statusEl = document.getElementById('wlStatus');
        if (login && login.status === 'active') statusEl.textContent = 'Active. This worker can log in.';
        else if (login && login.status === 'pending') statusEl.textContent = 'Invite sent. Waiting for them to set a password.';
        else statusEl.textContent = 'No login yet.';
        const wlAccess = document.getElementById('wlAccess');
        // Activities has no "None" any more: a legacy grant that still says
        // it is read as view, and the next save writes the honest word.
        const schedAccess = (login && login.scheduleAccess) || 'view';
        wlAccess.value = schedAccess === 'none' ? 'view' : schedAccess;
        wlAccess.dispatchEvent(new Event('change', { bubbles: true }));
        // No community switch any more: a worker reaches the community through
        // their own account, never through the farm they are working in.
        window.workerRights.paint('wl', login);
        // The rights panel belongs to a login, not to a worker: no login, no
        // question to answer, and a line saying how to get one instead.
        document.getElementById('wlRightsWrap')?.classList.toggle('hidden', !login);
        document.getElementById('wlNoLoginSay')?.classList.toggle('hidden', !!login);
        document.getElementById('wlRevoke').classList.toggle('hidden', !login);
        /* A registration link is only a link for somebody who has not
           registered. Once the grant names a real account, the button becomes
           the one an owner actually reaches for — a link to change the
           password, usually because the worker has forgotten it. */
        const hasLogin = !!(login && login.workerUserId);
        document.getElementById('wlSendLink')?.classList.toggle('hidden', hasLogin);
        document.getElementById('wlSendPwLink')?.classList.toggle('hidden', !hasLogin);
        document.getElementById('wlPwFold').classList.remove('is-open');
        document.getElementById('wlPassword').value = '';
        document.getElementById('wlPassword2').value = '';
        paintPwStrength('');
        document.getElementById('wlPwMatchSay').classList.add('hidden');
    }

    /* ---- how much of a password this is ---- */
    function pwScore(pw) {
        if (pw.length < 8) return 0;
        let s = 1;
        if (pw.length >= 12) s++;
        if (/[a-z]/.test(pw) && /[A-Z]/.test(pw)) s++;
        if (/\d/.test(pw)) s++;
        if (/[^A-Za-z0-9]/.test(pw)) s++;
        return Math.min(4, s);
    }
    function paintPwStrength(pw) {
        const bar = document.getElementById('wlPwBar');
        const say = document.getElementById('wlPwSay');
        if (!bar || !say) return;
        if (!pw) {
            bar.style.width = '0';
            say.textContent = 'At least 8 characters.';
            say.style.color = '';
            return;
        }
        const s = pwScore(pw);
        const looks = [
            ['12%', '#ef4444', 'Too short — 8 characters minimum.'],
            ['30%', '#ef4444', 'Weak. Longer is stronger.'],
            ['55%', '#f59e0b', 'Okay. Mix in capitals or numbers.'],
            ['80%', '#84cc16', 'Strong.'],
            ['100%', '#16a34a', 'Very strong.'],
        ][s];
        bar.style.width = looks[0];
        bar.style.background = looks[1];
        say.textContent = looks[2];
        say.style.color = looks[1];
    }
    document.getElementById('wlPassword')?.addEventListener('input', (e) => {
        paintPwStrength(e.target.value);
        checkPwMatch();
    });
    document.getElementById('wlPassword2')?.addEventListener('input', checkPwMatch);
    function checkPwMatch() {
        const a = document.getElementById('wlPassword').value;
        const b = document.getElementById('wlPassword2').value;
        // Only complain once they have started the second field.
        document.getElementById('wlPwMatchSay').classList.toggle('hidden', !b || a === b);
    }

    /* ---- rights save themselves the moment they change ----
       The old shape only sent the switches with "Send link" / "Create
       login", so flipping a right on an existing login changed nothing.
       Now any real tap on the panel saves after a short breath. */
    let rightsTimer = null;
    function queueRightsSave() {
        if (!editingWorker || !editingWorker.login || !editingWorker.login.id) return;
        clearTimeout(rightsTimer);
        rightsTimer = setTimeout(saveRightsNow, 450);
    }
    async function saveRightsNow() {
        const login = editingWorker && editingWorker.login;
        if (!login || !login.id) return;
        try {
            const res = await api(@json(route('sm.workers.access.rights')), { method: 'POST', body: {
                id: login.id,
                scheduleAccess: document.getElementById('wlAccess').value,
                communityAccess: 1,   // vestigial: the grant column is no longer asked
                ...window.workerRights.read('wl'),
            } });
            editingWorker.login = (res.data && res.data.grant) || editingWorker.login;
            renderList();
            toast(res.message || 'Access updated.');
        } catch (err) {
            toast(err.message, 'error');
            paintLogin(editingWorker);   // repaint the truth the server kept
        }
    }
    // 'input' on a level fires only from a real segment tap (paint uses
    // 'change'); checkboxes are guarded by isTrusted so programmatic
    // repaints never save.
    document.getElementById('wlRightsWrap')?.addEventListener('input', (e) => {
        if (e.target.classList?.contains('wr-level')) queueRightsSave();
    });
    document.getElementById('wlRightsWrap')?.addEventListener('change', (e) => {
        if (e.isTrusted && e.target.classList?.contains('wr-check')) queueRightsSave();
    });

    function applyGrant(grant) {
        if (!editingWorker) return;
        editingWorker.login = grant || null;   // editingWorker is a live ref in WORKERS
        paintLogin(editingWorker);
        renderList();
    }
    const loginEmail = () => (document.getElementById('workerEmail').value || '').trim();

    document.getElementById('wlSendLink')?.addEventListener('click', async (e) => {
        const email = loginEmail();
        if (!email) { toast('Add the worker\'s email above first.', 'error'); document.getElementById('workerEmail').focus(); return; }
        const btn = e.currentTarget; btn.disabled = true;
        try {
            const res = await api(@json(route('sm.workers.access.grant')), { method: 'POST', body: {
                scheduleWorkerId: editingWorker && editingWorker.id,
                email,
                scheduleAccess: document.getElementById('wlAccess').value,
                communityAccess: 1,   // vestigial: the grant column is no longer asked
                ...window.workerRights.read('wl'),
            } });
            toast(res.message);
            applyGrant(res.data && res.data.grant);
        } catch (err) { toast(err.message, 'error'); } finally { btn.disabled = false; }
    });

    /* The link for a worker who already has a login. Asked first, because it
       lands in somebody's inbox: an owner tapping the row to read it should
       not send mail by brushing past. */
    document.getElementById('wlSendPwLink')?.addEventListener('click', async (e) => {
        // Taken BEFORE the confirm: currentTarget is only set while the event
        // is being dispatched, and after the await it is null. Reading it
        // there threw, and the link was never asked for -- "nothing happens".
        const btn = e.currentTarget;
        const grantId = editingWorker && editingWorker.login && editingWorker.login.id;
        if (!grantId) { toast('This worker has no login yet.', 'error'); return; }
        const name = (editingWorker && editingWorker.workerName) || 'this worker';
        const ok = window.confirmAction ? await window.confirmAction({
            title: 'Send a password change link?',
            message: name + ' gets an email with a link to pick a new password. Their current one keeps working until they do.',
            confirmText: 'Send the link',
            confirmClass: 'btn-primary',
        }) : true;
        if (!ok) return;
        btn.disabled = true;
        try {
            const res = await api(@json(route('sm.workers.access.password-link')), {
                method: 'POST', body: { id: grantId },
            });
            toast(res.message);
        } catch (err) { toast(err.message, 'error'); } finally { btn.disabled = false; }
    });

    document.getElementById('wlSetPwToggle')?.addEventListener('click', () => {
        const fold = document.getElementById('wlPwFold');
        const opening = !fold.classList.contains('is-open');
        fold.classList.toggle('is-open', opening);
        fold.setAttribute('aria-hidden', opening ? 'false' : 'true');
        if (opening) setTimeout(() => document.getElementById('wlPassword').focus(), 300);
    });

    document.getElementById('wlCreateLogin')?.addEventListener('click', async (e) => {
        const email = loginEmail();
        if (!email) { toast('Add the worker\'s email above first.', 'error'); document.getElementById('workerEmail').focus(); return; }
        const pw = document.getElementById('wlPassword').value;
        if (pw.length < 8) { toast('Password must be at least 8 characters.', 'error'); return; }
        if (pw !== document.getElementById('wlPassword2').value) {
            toast('The two passwords don\'t match — repeat the same one below.', 'error');
            document.getElementById('wlPassword2').focus();
            return;
        }
        const btn = e.currentTarget; btn.disabled = true;
        try {
            const res = await api(@json(route('sm.workers.access.password')), { method: 'POST', body: {
                scheduleWorkerId: editingWorker && editingWorker.id,
                name: editingWorker && editingWorker.workerName,
                email, password: pw,
                scheduleAccess: document.getElementById('wlAccess').value,
                communityAccess: 1,   // vestigial: the grant column is no longer asked
                ...window.workerRights.read('wl'),
            } });
            toast(res.message);
            applyGrant(res.data && res.data.grant);
        } catch (err) { toast(err.message, 'error'); } finally { btn.disabled = false; }
    });

    document.getElementById('wlRevoke')?.addEventListener('click', async (e) => {
        // Captured before the question is asked: `currentTarget` is null on
        // the far side of an await, and this used to throw instead of
        // revoking anything.
        const btn = e.currentTarget;
        if (!editingWorker || !editingWorker.login || !editingWorker.login.id) return;
        const ok = await confirmAction({ title: 'Revoke access?', message: 'This worker will no longer be able to log in.', confirmText: 'Revoke' });
        if (!ok) return;
        btn.disabled = true;
        try {
            const res = await api(@json(route('sm.workers.access.revoke')), { method: 'DELETE', body: { id: editingWorker.login.id } });
            toast(res.message);
            applyGrant(null);
        } catch (err) { toast(err.message, 'error'); } finally { btn.disabled = false; }
    });

    document.getElementById('saveWorkerBtn').addEventListener('click', async (e) => {
        const btn = e.currentTarget;
        const id = document.getElementById('workerId').value;
        const body = {
            workerName: document.getElementById('workerName').value.trim(),
            email: document.getElementById('workerEmail').value.trim() || null,
            phone: document.getElementById('workerPhone').value.trim() || null,
            costPerHalfDay: document.getElementById('workerCost').value || 0,
            skills: chipValues(document.getElementById('workerSkills')),
            notes: document.getElementById('workerNotes').value || null,
            // Always sent, even empty, so removing every tag clears them.
            tags: window.smTags ? window.smTags.value(document.getElementById('workerTagsMount')) : [],
        };

        if (!body.workerName) {
            toast('Worker name is required.', 'error');
            document.getElementById('workerName').focus();
            return;
        }
        if (wlEmailBlocked) {
            toast('Somebody on this schedule already has that email — open their card instead.', 'error');
            document.getElementById('workerEmail').focus();
            return;
        }

        const url = id
            ? `{{ route('sm.workers.update') }}?scheduleId=${SCHEDULE_ID}&id=${id}`
            : `{{ route('sm.workers.store') }}?scheduleId=${SCHEDULE_ID}`;

        btn.disabled = true;
        try {
            const res = await api(url, { method: id ? 'PUT' : 'POST', body });
            toast(res.message);
            const prev = WORKERS.find((w) => String(w.id) === String(res.data.id));
            const saved = {
                id: res.data.id,
                workerName: res.data.workerName,
                email: res.data.email,
                phone: res.data.phone,
                costPerHalfDay: res.data.costPerHalfDay,
                priority: Number(res.data.priority) || 1,
                skills: res.data.skills || [],
                notes: res.data.notes,
                offDays: prev ? prev.offDays : [],
                offDates: prev ? prev.offDates : [],
                login: prev ? prev.login : null,
            };
            if (prev) {
                WORKERS[WORKERS.indexOf(prev)] = saved;
            } else {
                WORKERS.push(saved);
            }
            renderList();
            closeSheet('workerSheet');
            // Filed in the phonebook too, if the offer above was left ticked.
            // Deliberately after the worker is saved and NOT awaited into the
            // same try: a phonebook that refuses must not make it look as
            // though the worker failed to save.
            if (!id) fileAsContact(body);
        } catch (err) {
            toast(err.message, 'error');
        } finally {
            btn.disabled = false;
        }
    });

    async function fileAsContact(body) {
        const row = toContactRow();
        if (!row || row.hidden || !document.getElementById('wlToContactBox').checked) return;
        try {
            await api(@json(route('contacts.store')), {
                method: 'POST',
                body: {
                    name: body.workerName,
                    emails: body.email ? [body.email] : [],
                    phones: body.phone ? [body.phone] : [],
                    tags: ['Worker'],
                },
            });
            toast(body.workerName + ' is in your Contact List too.');
        } catch (_) {
            // The worker is saved either way; a phonebook that would not take
            // them is worth a word, not an alarm.
            toast('Saved the worker, but could not add them to your contacts.', 'error');
        }
    }

    /* ---------------- Rules sheet ---------------- */

    let offDatesState = [];

    function renderOffDates() {
        const wrap = document.getElementById('offDatesList');
        wrap.innerHTML = offDatesState
            .map((d) => `
                <span class="badge badge-orange py-1.5! px-3! text-sm!">
                    ${escapeHtml(fmtDate(d))}
                    <button type="button" class="ml-1 font-bold" data-remove-off-date="${escapeHtml(d)}" aria-label="Remove ${escapeHtml(d)}">✕</button>
                </span>`)
            .join('');
        document.getElementById('offDatesEmpty').classList.toggle('hidden', offDatesState.length > 0);
    }

    async function openRulesSheet(worker) {
        document.getElementById('rulesWorkerId').value = worker.id;
        document.getElementById('rulesSheetTitle').textContent = `Rules for ${worker.workerName}`;
        document.getElementById('rulesDateInput').value = '';

        // Prefill from local state, then refresh from the server.
        let offDays = worker.offDays || [];
        offDatesState = [...(worker.offDates || [])].sort();
        applyDayPills(offDays);
        renderOffDates();
        openSheet('rulesSheet');

        try {
            const res = await api(`{{ route('sm.workers.rules') }}?scheduleId=${SCHEDULE_ID}&id=${worker.id}`);
            offDays = (res.data.offDays || []).map(Number);
            offDatesState = (res.data.offDates || [])
                .map((r) => String(r.offDate).substring(0, 10))
                .sort();
            applyDayPills(offDays);
            renderOffDates();
        } catch (err) {
            toast(err.message, 'error');
        }
    }

    function applyDayPills(offDays) {
        const set = (offDays || []).map(Number);
        document.querySelectorAll('#rulesDayGroup .chip').forEach((c) => {
            c.classList.toggle('is-selected', set.includes(Number(c.getAttribute('data-day'))));
        });
    }

    document.getElementById('rulesAddDateBtn').addEventListener('click', () => {
        const input = document.getElementById('rulesDateInput');
        const v = input.value;
        if (!v) return;
        if (!offDatesState.includes(v)) {
            offDatesState.push(v);
            offDatesState.sort();
            renderOffDates();
        }
        input.value = '';
    });

    document.getElementById('offDatesList').addEventListener('click', (e) => {
        const btn = e.target.closest('[data-remove-off-date]');
        if (!btn) return;
        offDatesState = offDatesState.filter((d) => d !== btn.getAttribute('data-remove-off-date'));
        renderOffDates();
    });

    document.getElementById('saveRulesBtn').addEventListener('click', async (e) => {
        const btn = e.currentTarget;
        const id = document.getElementById('rulesWorkerId').value;
        const offDays = chipValues(document.getElementById('rulesDayGroup')).map(Number);

        btn.disabled = true;
        try {
            const res = await api(`{{ route('sm.workers.rules.save') }}?scheduleId=${SCHEDULE_ID}&id=${id}`, {
                method: 'POST',
                body: { offDays, offDates: offDatesState },
            });
            toast(res.message);
            const w = WORKERS.find((x) => String(x.id) === String(id));
            if (w) {
                w.offDays = offDays;
                w.offDates = [...offDatesState];
                renderList();
            }
            closeSheet('rulesSheet');
        } catch (err) {
            toast(err.message, 'error');
        } finally {
            btn.disabled = false;
        }
    });

    /* ---------------- List actions ---------------- */

    document.addEventListener('click', async (e) => {
        if (e.target.closest('[data-add-worker]')) {
            openWorkerSheet();
            return;
        }

        const pmBtn = e.target.closest('[data-pm-worker]');
        if (pmBtn) {
            openWorkerPm(pmBtn.getAttribute('data-pm-worker'), pmBtn.getAttribute('data-pm-name') || 'Worker');
            return;
        }

        const editBtn = e.target.closest('[data-edit-worker]');
        if (editBtn) {
            const w = WORKERS.find((x) => String(x.id) === editBtn.getAttribute('data-edit-worker'));
            if (w) openWorkerSheet(w);
            return;
        }

        const rulesBtn = e.target.closest('[data-rules-worker]');
        if (rulesBtn) {
            const w = WORKERS.find((x) => String(x.id) === rulesBtn.getAttribute('data-rules-worker'));
            if (w) openRulesSheet(w);
            return;
        }

        const delBtn = e.target.closest('[data-delete-worker]');
        if (delBtn) {
            const id = delBtn.getAttribute('data-delete-worker');
            const w = WORKERS.find((x) => String(x.id) === id);
            const ok = await confirmAction({
                title: 'Delete worker?',
                message: `"${w?.workerName || 'This worker'}" will be removed from the schedule.`,
                detail: 'Existing assignments tied to them are preserved.',
                confirmText: 'Delete',
            });
            if (!ok) return;
            try {
                const res = await api(`{{ route('sm.workers.destroy') }}?scheduleId=${SCHEDULE_ID}&id=${id}`, { method: 'DELETE' });
                toast(res.message);
                WORKERS = WORKERS.filter((x) => String(x.id) !== id);
                renderList();
            } catch (err) {
                toast(err.message, 'error');
            }
        }
    });

    renderList();

    // A tag shelf names one worker — land on their card. The fallback is
    // the server-rendered query: in the shell the pane is fetched with the
    // deep link while the address bar keeps the shell's own URL.
    {
        const spot = new URLSearchParams(location.search).get('open') || @json(request()->query('open'));
        if (spot) window.smSpot?.('[data-worker-card="' + String(spot).replace(/[^\d]/g, '') + '"]');
    }
};
    // First load: wait for app.js (deferred) to define the globals.
    // SPA injection: document is already complete, so run now.
    if (document.readyState === 'loading') document.addEventListener('DOMContentLoaded', __init, { once: true });
    else __init();
})();
</script>

@endpush
