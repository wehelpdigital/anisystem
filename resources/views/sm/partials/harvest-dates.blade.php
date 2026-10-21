{{-- Harvest dates (2026-10-12): the finish flag on the board's header bar
     (where NPK Plus was) opens this. Each lot's harvest by the calendar (day
     zero plus its days to maturity, plus the farmer's delay counter) and as
     Realign by Anee reads it (her shift moves the harvest by as many days).
     Trees are harvested by season, not by a date, and say so.

     Set as harvested (2026-10-22, the owner): each lot's row offers it. It
     opens the harvest record (#hvRecordSheet) on top of this sheet: the day,
     and every harvest question Observations asks (PostHarvestFields::HARVEST:
     the yield, the quality, the drying and storage, the sale). Saving files
     each part answered as its own observation on the lot, marks the lot
     harvested (LotController::harvest) and lands back here, the row now
     saying so. "Not harvested yet" takes the mark back, with the
     observations it wrote. --}}
@php
    [$hvZero] = \App\Support\LotCalendar::effectiveAnchors($schedule);
    $hvToday = now('Asia/Manila')->startOfDay();
    $hvRows = $schedule->lots->map(function ($lot) use ($hvZero, $hvToday) {
        $crop = $lot->crop;
        $row = ['id' => (int) $lot->id, 'name' => $lot->lotName, 'crop' => $crop ? str_replace([' — ', ' / '], [', ', ' or '], \App\Support\CropStages::label($crop) ?: $crop) : 'No crop yet',
            'icon' => $crop ? \App\Support\CropStages::icon($crop) : '🌱', 'tree' => \App\Support\CropStages::isPerennial($crop),
            'orig' => null, 'real' => null, 'shift' => (int) ($lot->growthShiftDays ?? 0), 'realigned' => (bool) $lot->growthRealignedAt,
            'delay' => max(0, (int) ($lot->delayDays ?? 0)), 'pct' => null, 'harvested' => $lot->harvestedAt];
        $zero = $hvZero[$lot->id] ?? null;
        $maturity = $lot->maturityDays();
        if ($row['tree'] || ! $zero || ! $maturity) {
            return $row;
        }
        $orig = $zero->copy()->startOfDay()->addDays($maturity + $row['delay']);
        $row['orig'] = $orig;
        $row['real'] = $row['realigned'] ? $orig->copy()->subDays($row['shift']) : null;
        $day = $zero->copy()->startOfDay()->diffInDays($hvToday, false);
        $row['pct'] = max(0, min(100, (int) round($day / max(1, $maturity) * 100)));

        return $row;
    });
    $hvWhen = function ($d) use ($hvToday) {
        if (! $d) {
            return '';
        }
        $n = (int) $hvToday->diffInDays($d, false);

        return $n > 1 ? 'in ' . $n . ' days' : ($n === 1 ? 'tomorrow' : ($n === 0 ? 'today' : ($n === -1 ? 'yesterday' : abs($n) . ' days ago')));
    };
    // Two pens: the lot is the plan's, the record is the Observations
    // module's. A closed season takes no marks either way.
    $hvMay = \App\Support\WorkerContext::canEdit() && \App\Support\WorkerContext::canWriteModule('observations') && ! $schedule->isLocked();
    $hvSeeObs = \App\Support\WorkerContext::canUseModule('observations');
    $hvOwner = ! \App\Support\WorkerContext::inWorkerContext();
    $hvAllIn = $hvRows->isNotEmpty() && $hvRows->every(fn ($r) => $r['harvested'] !== null);
    $hvSections = [
        'yield' => ['🌾', 'The harvest', 'What came off the field.'],
        'quality' => ['✨', 'Quality', 'How good it was.'],
        'storage' => ['🏬', 'Drying and storage', 'How it was dried and where it is kept.'],
        'market' => ['💰', 'Selling and price', 'What was sold, and for how much.'],
    ];
@endphp
<style>
    .hv-list { display: grid; gap: .65rem; }
    .hv-row { padding: .85rem .9rem; border-radius: 1.1rem; background: var(--color-white); border: 1px solid var(--color-gray-200);
        animation: hvIn .34s cubic-bezier(.22,1,.36,1) both; animation-delay: calc(var(--i) * 50ms);
        transition: border-color .28s cubic-bezier(.22,1,.36,1), background .28s cubic-bezier(.22,1,.36,1); }
    @keyframes hvIn { from { opacity: 0; transform: translateY(8px); } }
    .hv-top { display: flex; align-items: center; gap: .6rem; }
    .hv-top i { flex: none; width: 2.3rem; height: 2.3rem; border-radius: .8rem; display: grid; place-items: center; font-style: normal; font-size: 1.2rem; background: var(--color-gray-50); }
    .hv-top > span:not(.hv-tag) { min-width: 0; flex: 1 1 auto; }
    .hv-top b { display: block; font-weight: 800; color: var(--color-gray-900); line-height: 1.25; }
    .hv-top small { display: block; font-size: .78rem; color: var(--color-gray-500); }
    .hv-dates { margin-top: .65rem; display: grid; gap: .45rem; grid-template-columns: repeat(2, minmax(0, 1fr)); }
    .hv-d { padding: .5rem .6rem; border-radius: .8rem; background: var(--color-gray-50); border: 1px solid var(--color-gray-100); }
    .hv-d span { display: block; font-size: .66rem; font-weight: 800; letter-spacing: .06em; text-transform: uppercase; color: var(--color-gray-500); }
    .hv-d b { display: block; margin-top: .1rem; font-size: .95rem; font-weight: 800; color: var(--color-gray-900); }
    .hv-d small { font-size: .74rem; color: var(--color-gray-500); }
    .hv-d.is-anee { background: #fff8e1; border-color: #f5d98a; }
    .hv-d.is-anee span { color: #8a5a00; }
    .hv-d.is-none b { font-size: .8rem; font-weight: 700; color: var(--color-gray-500); }
    .hv-bar { margin-top: .6rem; height: .4rem; border-radius: 999px; background: var(--color-gray-100); overflow: hidden; }
    .hv-bar span { display: block; height: 100%; border-radius: inherit; background: linear-gradient(90deg, #86b556, #f5c518); transform-origin: left;
        animation: hvGrow .9s cubic-bezier(.22,1,.36,1) both; animation-delay: calc(var(--i) * 50ms + .15s); }
    @keyframes hvGrow { from { transform: scaleX(0); } }
    .hv-note { margin-top: .5rem; font-size: .78rem; line-height: 1.5; color: var(--color-gray-500); }
    .hv-foot { margin-top: .8rem; font-size: .78rem; line-height: 1.55; color: var(--color-gray-500); }

    /* Harvested: the dates step aside for the day it came in. */
    .hv-tag { flex: none; display: inline-flex; align-items: center; gap: .25rem; padding: .2rem .55rem; border-radius: 999px;
        font-size: .7rem; font-weight: 800; color: #1f6b32; background: #e8f5e9; }
    .hv-done { margin-top: .65rem; padding: .6rem .7rem; border-radius: .8rem; background: #f1f8ea; border: 1px solid #cfe5b8;
        font-size: .82rem; line-height: 1.45; color: #2f4a1c; }
    .hv-done b { font-weight: 800; }
    .hv-done a { display: inline-block; margin-top: .2rem; font-weight: 700; color: #3d6823; text-decoration: underline; text-underline-offset: 2px; }
    .hv-row.is-harvested { border-color: #cfe5b8; }
    .hv-row.is-harvested .hv-grow, .hv-row:not(.is-harvested) .hv-done, .hv-row:not(.is-harvested) .hv-tag,
    .hv-row.is-harvested [data-hv-mark], .hv-row:not(.is-harvested) [data-hv-undo] { display: none; }
    .hv-row.is-harvested .hv-done, .hv-row.is-harvested .hv-tag { animation: hvIn .28s cubic-bezier(.22,1,.36,1) both; }
    .hv-acts { margin-top: .65rem; display: flex; gap: .45rem; }
    .hv-mark { flex: 1 1 auto; display: inline-flex; align-items: center; justify-content: center; gap: .4rem; padding: .55rem .8rem;
        border: 0; border-radius: .8rem; font-weight: 800; font-size: .85rem; color: #3b2f00; background: #f5c518;
        box-shadow: 0 6px 16px -10px rgb(201 154 6 / .9); cursor: pointer;
        transition: background .28s cubic-bezier(.22,1,.36,1), transform .28s cubic-bezier(.22,1,.36,1); }
    .hv-mark:hover { background: #ffd23f; transform: translateY(-1px); }
    .hv-mark svg { width: 1rem; height: 1rem; }
    .hv-undo { padding: .4rem .7rem; border-radius: .7rem; font-size: .76rem; font-weight: 700; color: var(--color-gray-600);
        background: transparent; border: 1px solid var(--color-gray-200); cursor: pointer; transition: background .28s cubic-bezier(.22,1,.36,1); }
    .hv-undo:hover { background: var(--color-gray-50); }
    .hv-allin { margin-top: .8rem; padding: .65rem .75rem; border-radius: .9rem; background: #fffbeb; border: 1px solid #fde68a;
        font-size: .8rem; line-height: 1.5; color: #78350f; }
    .hv-allin a { font-weight: 800; color: #78350f; text-decoration: underline; text-underline-offset: 2px; }

    /* The harvest record. */
    .hvr-sec { padding: .8rem .85rem; border: 1px solid var(--color-gray-200); border-radius: 1rem; background: var(--color-white); }
    .hvr-sec + .hvr-sec { margin-top: .65rem; }
    .hvr-h { display: flex; align-items: center; gap: .55rem; margin-bottom: .6rem; }
    .hvr-h i { flex: none; width: 2rem; height: 2rem; border-radius: .65rem; display: grid; place-items: center; font-style: normal; background: var(--color-brand-50, #f1f8ea); }
    .hvr-h b { display: block; font-weight: 800; color: var(--color-gray-900); line-height: 1.2; }
    .hvr-h small { display: block; font-size: .74rem; color: var(--color-gray-500); }
    .hvr-h em { margin-left: auto; font-style: normal; font-size: .66rem; font-weight: 800; letter-spacing: .05em; text-transform: uppercase; color: var(--color-gray-400); }
    .hvr-grid { display: grid; gap: .6rem; grid-template-columns: repeat(2, minmax(0, 1fr)); }
    .hvr-grid .is-wide { grid-column: 1 / -1; }
    .hvr-grid .form-label { font-size: .76rem; margin-bottom: .2rem; }
    .hvr-chips { display: flex; flex-wrap: wrap; gap: .35rem; }
    .hvr-chips button { padding: .35rem .65rem; border-radius: 999px; font-size: .76rem; font-weight: 700; color: var(--color-gray-600);
        background: var(--color-gray-50); border: 1px solid var(--color-gray-200); cursor: pointer;
        transition: background .28s cubic-bezier(.22,1,.36,1), border-color .28s cubic-bezier(.22,1,.36,1), color .28s cubic-bezier(.22,1,.36,1); }
    .hvr-chips button.is-on { background: #e8f5e9; border-color: #86b556; color: #1f6b32; }
    .hvr-need { color: #dc2626; }
    html.dark .hv-row, html.dark .hvr-sec { background: #1a1d23; border-color: #2a2f38; }
    html.dark .hv-d, html.dark .hv-top i { background: #20242c; border-color: #2a2f38; }
    html.dark .hv-d.is-anee { background: #2a2210; border-color: #5c4a24; }
    html.dark .hv-top b, html.dark .hv-d b, html.dark .hvr-h b { color: #e5e7eb; }
    html.dark .hv-tag { background: #173a20; color: #8fdca5; }
    html.dark .hv-done { background: #16261a; border-color: #2d4a2a; color: #cfe3bd; }
    html.dark .hv-done a { color: #a8d38a; }
    html.dark .hv-row.is-harvested { border-color: #2d4a2a; }
    html.dark .hv-allin { background: rgb(180 83 9 / .14); border-color: rgb(180 83 9 / .35); color: #eec155; }
    html.dark .hv-allin a { color: #fcd34d; }
    html.dark .hvr-h i { background: #20242c; }
    html.dark .hvr-chips button { background: #20242c; border-color: #2a2f38; color: #cdd8c0; }
    html.dark .hvr-chips button.is-on { background: #173a20; border-color: #4a7c2a; color: #8fdca5; }
    html.dark .hv-undo { border-color: #2a2f38; color: #cdd8c0; }
    @media (max-width: 360px) { .hvr-grid { grid-template-columns: minmax(0, 1fr); } }
    @media (prefers-reduced-motion: reduce) {
        .hv-row, .hv-bar span, .hv-row.is-harvested .hv-done, .hv-row.is-harvested .hv-tag { animation: none; }
        .hv-mark, .hv-undo, .hvr-chips button, .hv-row { transition: none; }
    }
</style>
<div class="sheet hidden" id="harvestSheet" style="--sheet-width:32rem" aria-labelledby="hvTitle">
    <div class="sheet-handle"></div>
    <div class="sheet-header">
        <h3 class="sheet-title flex items-center gap-2" id="hvTitle">
            <svg class="w-6 h-6" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.9" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true"><path d="M5 21V3.5"/><path d="M5 4h13l-2.2 4L18 12H5"/><path d="M9.3 4v8M13.6 4v8M5 8h11.8" stroke-width="1.2"/></svg>
            Harvest dates
        </h3>
        <button type="button" data-sheet-close class="btn-ghost p-2 rounded-full" aria-label="Close">✕</button>
    </div>
    <div class="sheet-body">
        <div class="hv-list">
            @forelse ($hvRows as $i => $r)
                <div class="hv-row {{ $r['harvested'] ? 'is-harvested' : '' }}" style="--i: {{ $i }}" data-hv-lot="{{ $r['id'] }}" data-hv-name="{{ $r['name'] }}">
                    <div class="hv-top">
                        <i aria-hidden="true">{{ $r['icon'] }}</i><span><b>{{ $r['name'] }}</b><small>{{ $r['crop'] }}</small></span>
                        <span class="hv-tag">✓ Harvested</span>
                    </div>
                    <div class="hv-grow">
                        @if ($r['tree'])
                            <p class="hv-note">A tree crop: it is harvested by its fruiting season each year, not by a date from planting.</p>
                        @elseif (! $r['orig'])
                            <p class="hv-note">No planting date yet. Set the day zero (sowing, transplanting or planting) and the harvest date shows here.</p>
                        @else
                            <div class="hv-dates">
                                <div class="hv-d"><span>Original</span><b>{{ $r['orig']->format('M j, Y') }}</b><small>{{ $hvWhen($r['orig']) }}</small></div>
                                @if ($r['realigned'])
                                    <div class="hv-d is-anee"><span>Realigned by Anee</span><b>{{ $r['real']->format('M j, Y') }}</b>
                                        <small>{{ $hvWhen($r['real']) }}{{ $r['shift'] ? ', ' . abs($r['shift']) . ' days ' . ($r['shift'] > 0 ? 'earlier' : 'later') : ', same day' }}</small></div>
                                @else
                                    <div class="hv-d is-anee is-none"><span>Realigned by Anee</span><b>Not read yet</b><small>Realign by Anee in the growth module</small></div>
                                @endif
                            </div>
                            <div class="hv-bar" aria-label="{{ $r['pct'] }}% of the way to harvest"><span style="width: {{ $r['pct'] }}%"></span></div>
                            @if ($r['delay'])<p class="hv-note">Counts the {{ $r['delay'] }} days your delay counter says the crop is behind.</p>@endif
                        @endif
                    </div>
                    <div class="hv-done">
                        <b>Harvested on <span data-hv-says>{{ $r['harvested']?->format('M j, Y') }}</span>.</b>
                        The yield and the rest of its harvest record are in Observations.
                        @if ($hvSeeObs)
                            <br><a href="{{ route('sm.activities', ['id' => $schedule->id, 'module' => 'post-harvest']) }}" data-hv-obs>See it in Observations</a>
                        @endif
                    </div>
                    @if ($hvMay)
                        <div class="hv-acts">
                            <button type="button" class="hv-mark" data-hv-mark>
                                <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.4" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true"><path d="M5 13l4 4L19 7"/></svg>
                                Set as harvested
                            </button>
                            <button type="button" class="hv-undo" data-hv-undo>Not harvested yet</button>
                        </div>
                    @endif
                </div>
            @empty
                <p class="hv-note">No lots in this season yet.</p>
            @endforelse
        </div>
        @if ($hvOwner && ! $schedule->isLocked())
            <p class="hv-allin {{ $hvAllIn ? '' : 'hidden' }}" id="hvAllIn">
                Every lot is harvested. When the work is done, close the season in the
                <a href="{{ route('sm.hub', ['id' => $schedule->id]) }}">Hub</a>. The Anee Season Report can also read one lot at a time now.
            </p>
        @endif
        <p class="hv-foot">The original date is day zero plus the crop's days to maturity. The realigned date moves it by as many days as Anee found the crop ahead or behind when she read it. Both are a guide: check the grain or fruit before you harvest.</p>
    </div>
</div>

@if ($hvMay)
<div class="sheet hidden" id="hvRecordSheet" style="--sheet-width:34rem" aria-labelledby="hvRecordTitle">
    <div class="sheet-handle"></div>
    <div class="sheet-header">
        <h3 class="sheet-title" id="hvRecordTitle">Harvest record</h3>
        <button type="button" data-sheet-close class="btn-ghost p-2 rounded-full" aria-label="Close">✕</button>
    </div>
    <div class="sheet-body">
        <p class="text-sm text-gray-500 mb-3">Fill in what you know. Each part you answer is saved in Observations on this lot, and the lot is marked harvested.</p>
        <div class="mb-3">
            <label class="form-label" for="hvDate">Day it was harvested <span class="hvr-need">*</span></label>
            @include('partials.date-tag', ['id' => 'hvDate', 'empty' => 'Pick the day'])
        </div>
        @foreach ($hvSections as $cat => [$icon, $title, $hint])
            <div class="hvr-sec" data-hv-sec="{{ $cat }}">
                <div class="hvr-h">
                    <i aria-hidden="true">{{ $icon }}</i>
                    <span><b>{{ $title }}</b><small>{{ $hint }}</small></span>
                    @if ($cat !== 'yield')<em>Optional</em>@endif
                </div>
                <div class="hvr-grid">
                    @foreach (\App\Support\PostHarvestFields::for($cat) as $f)
                        @php
                            $hvId = 'hv_' . $cat . '_' . $f['key'];
                            $hvNeed = $cat === 'yield' && in_array($f['key'], ['yieldAmount', 'yieldUnit'], true);
                            $hvWide = in_array($f['type'], ['select', 'text', 'textarea'], true);
                            $hvSuffix = $f['type'] === 'percent' ? ' (%)' : ($f['type'] === 'money' ? ' (' . \App\Support\Region::symbol() . ')' : '');
                        @endphp
                        <div class="{{ $hvWide ? 'is-wide' : '' }}">
                            <label class="form-label" @if ($f['type'] !== 'select') for="{{ $hvId }}" @endif>{{ $f['label'] }}{{ $hvSuffix }}@if ($hvNeed) <span class="hvr-need">*</span>@endif</label>
                            @if ($f['type'] === 'select')
                                <div class="hvr-chips" role="group" aria-label="{{ $f['label'] }}" data-hv-f="{{ $cat }}.{{ $f['key'] }}" data-value="">
                                    @foreach ($f['options'] as $v => $label)
                                        <button type="button" data-v="{{ $v }}" aria-pressed="false">{{ $label }}</button>
                                    @endforeach
                                </div>
                            @elseif ($f['type'] === 'textarea')
                                <textarea id="{{ $hvId }}" class="form-textarea" rows="3" maxlength="2000" data-hv-f="{{ $cat }}.{{ $f['key'] }}" placeholder="{{ $f['placeholder'] }}"></textarea>
                            @elseif (in_array($f['type'], ['number', 'money', 'percent'], true))
                                <input type="number" id="{{ $hvId }}" class="form-input" inputmode="decimal" min="0" step="{{ $f['type'] === 'percent' ? '0.1' : '0.01' }}" @if ($f['type'] === 'percent') max="100" @endif
                                       data-hv-f="{{ $cat }}.{{ $f['key'] }}" placeholder="{{ $f['placeholder'] }}">
                            @elseif ($f['type'] === 'unit')
                                <input type="text" id="{{ $hvId }}" class="form-input" maxlength="24" list="hvUnitOptions" data-hv-f="{{ $cat }}.{{ $f['key'] }}" placeholder="{{ $f['placeholder'] }}">
                            @else
                                <input type="text" id="{{ $hvId }}" class="form-input" maxlength="191" data-hv-f="{{ $cat }}.{{ $f['key'] }}" placeholder="{{ $f['placeholder'] }}">
                            @endif
                        </div>
                    @endforeach
                    @if ($cat === 'yield')
                        <div class="is-wide">
                            <label class="form-label" for="hvNotes">Anything else about this harvest</label>
                            <textarea id="hvNotes" class="form-textarea" rows="2" maxlength="5000" placeholder="e.g. harvested by machine, two days of rain before"></textarea>
                        </div>
                    @endif
                </div>
            </div>
        @endforeach
        <datalist id="hvUnitOptions">
            <option value="kg"></option>
            <option value="sacks"></option>
            @if (\App\Support\Region::ph())<option value="cavans"></option>@else<option value="bushels"></option>@endif
            <option value="tons"></option>
            <option value="pieces"></option>
        </datalist>
    </div>
    <div class="sheet-footer">
        <button type="button" class="btn btn-ghost" data-sheet-close>Back</button>
        <button type="button" class="btn btn-primary" id="hvSaveBtn">Save and mark harvested</button>
    </div>
</div>
@endif
<script>
(() => {
    document.getElementById('harvestDatesBtn')?.addEventListener('click', () => window.openSheet('harvestSheet'));
    if (window.__hvRecordBooted) return;
    window.__hvRecordBooted = true;

    const HV = {
        harvest: @json(route('sm.lots.harvest') . '?scheduleId=' . $schedule->id),
        undo: @json(route('sm.lots.harvest.undo') . '?scheduleId=' . $schedule->id),
    };
    const $ = (id) => document.getElementById(id);
    let lotId = null;
    let lotRow = null;

    /* Local today, not UTC's: a harvest recorded at 7 in the morning in
       Manila is that day's, not yesterday's. */
    const today = () => {
        const d = new Date();
        return d.getFullYear() + '-' + String(d.getMonth() + 1).padStart(2, '0') + '-' + String(d.getDate()).padStart(2, '0');
    };

    function allIn() {
        const rows = document.querySelectorAll('#harvestSheet [data-hv-lot]');
        const note = $('hvAllIn');
        if (note) note.classList.toggle('hidden', !rows.length || ![...rows].every((r) => r.classList.contains('is-harvested')));
    }

    function paint(row, d) {
        if (!row) return;
        row.classList.toggle('is-harvested', !!d.harvestedAt);
        const says = row.querySelector('[data-hv-says]');
        if (says) says.textContent = d.harvestedSays || '';
        allIn();
    }

    function openRecord(row) {
        lotRow = row;
        lotId = Number(row.dataset.hvLot);
        const sheet = $('hvRecordSheet');
        if (!sheet) return;
        $('hvRecordTitle').textContent = 'Harvest of ' + (row.dataset.hvName || 'this lot');
        sheet.querySelectorAll('input[data-hv-f], textarea[data-hv-f]').forEach((el) => { el.value = ''; });
        sheet.querySelectorAll('.hvr-chips').forEach((g) => {
            g.dataset.value = '';
            g.querySelectorAll('button').forEach((b) => { b.classList.remove('is-on'); b.setAttribute('aria-pressed', 'false'); });
        });
        $('hvNotes').value = '';
        $('hvDate').value = today();
        $('hvDate').dispatchEvent(new Event('change', { bubbles: true }));
        const sold = $('hv_market_yieldUnit');
        if (sold) sold.placeholder = 'kg, sacks, tons';
        // On top of the dates, not instead of them: closing it lands back there.
        window.openSheet('hvRecordSheet');
    }

    function answers() {
        const out = {};
        document.querySelectorAll('#hvRecordSheet [data-hv-f]').forEach((el) => {
            const [cat, key] = el.dataset.hvF.split('.');
            const v = (el.classList.contains('hvr-chips') ? el.dataset.value : el.value || '').trim();
            if (v === '') return;
            (out[cat] = out[cat] || {})[key] = v;
        });
        return out;
    }

    document.addEventListener('input', (e) => {
        // A sale is in the harvest's unit unless said otherwise.
        if (e.target.id === 'hv_yield_yieldUnit') {
            const sold = $('hv_market_yieldUnit');
            if (sold) sold.placeholder = e.target.value.trim() || 'kg, sacks, tons';
        }
    });

    document.addEventListener('click', async (e) => {
        const chip = e.target.closest('#hvRecordSheet .hvr-chips button');
        if (chip) {
            const group = chip.closest('.hvr-chips');
            const on = group.dataset.value === chip.dataset.v ? '' : chip.dataset.v;
            group.dataset.value = on;
            group.querySelectorAll('button').forEach((b) => {
                const is = b.dataset.v === on;
                b.classList.toggle('is-on', is);
                b.setAttribute('aria-pressed', is ? 'true' : 'false');
            });
            return;
        }

        const mark = e.target.closest('[data-hv-mark]');
        if (mark) { openRecord(mark.closest('[data-hv-lot]')); return; }

        const obs = e.target.closest('[data-hv-obs]');
        if (obs && typeof window.smShowModule === 'function') {
            e.preventDefault();
            window.closeSheet('harvestSheet');
            window.smShowModule('post-harvest');
            return;
        }

        const undo = e.target.closest('[data-hv-undo]');
        if (undo) {
            const row = undo.closest('[data-hv-lot]');
            const name = row.dataset.hvName || 'This lot';
            const ok = await window.confirmAction({
                title: 'Not harvested yet?',
                message: name + ' goes back to growing.',
                detail: 'The observations its harvest record saved are removed from Observations. Anything written there by hand stays.',
                confirmText: 'Take it back',
            });
            if (!ok) return;
            undo.disabled = true;
            try {
                const res = await window.api(HV.undo, { method: 'POST', body: { lotId: Number(row.dataset.hvLot) } });
                paint(row, res.data || {});
                window.toast?.(res.message);
            } catch (err) {
                window.toast?.(err.message, 'error');
            } finally {
                undo.disabled = false;
            }
            return;
        }

        if (e.target.closest('#hvSaveBtn')) {
            const btn = $('hvSaveBtn');
            const a = answers();
            if (!$('hvDate').value) { window.toast?.('Pick the day it was harvested.', 'error'); return; }
            if (!(a.yield && Number(a.yield.yieldAmount) > 0)) { window.toast?.('Say how much was harvested.', 'error'); $('hv_yield_yieldAmount')?.focus(); return; }
            if (!a.yield.yieldUnit) { window.toast?.('Say what the harvest was measured in.', 'error'); $('hv_yield_yieldUnit')?.focus(); return; }
            btn.disabled = true;
            try {
                const res = await window.api(HV.harvest, { method: 'POST', body: {
                    lotId, harvestedAt: $('hvDate').value, notes: $('hvNotes').value.trim() || null, answers: a,
                } });
                window.closeSheet('hvRecordSheet');
                paint(lotRow, res.data || {});
                window.toast?.(res.message);
            } catch (err) {
                window.toast?.(err.message, 'error');
            } finally {
                btn.disabled = false;
            }
        }
    });
})();
</script>
