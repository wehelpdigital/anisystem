{{-- Harvest dates (2026-10-12): the finish flag on the board's header bar
     (where NPK Plus was) opens this. Each lot's harvest by the calendar (day
     zero plus its days to maturity, plus the farmer's delay counter) and as
     Realign by Anee reads it (her shift moves the harvest by as many days).
     Trees are harvested by season, not by a date, and say so. --}}
@php
    [$hvZero] = \App\Support\LotCalendar::effectiveAnchors($schedule);
    $hvToday = now('Asia/Manila')->startOfDay();
    $hvRows = $schedule->lots->map(function ($lot) use ($hvZero, $hvToday) {
        $crop = $lot->crop;
        $row = ['name' => $lot->lotName, 'crop' => $crop ? str_replace([' — ', ' / '], [', ', ' or '], \App\Support\CropStages::label($crop) ?: $crop) : 'No crop yet',
            'icon' => $crop ? \App\Support\CropStages::icon($crop) : '🌱', 'tree' => \App\Support\CropStages::isPerennial($crop),
            'orig' => null, 'real' => null, 'shift' => (int) ($lot->growthShiftDays ?? 0), 'realigned' => (bool) $lot->growthRealignedAt,
            'delay' => max(0, (int) ($lot->delayDays ?? 0)), 'pct' => null];
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
@endphp
<style>
    .hv-list { display: grid; gap: .65rem; }
    .hv-row { padding: .85rem .9rem; border-radius: 1.1rem; background: var(--color-white); border: 1px solid var(--color-gray-200);
        animation: hvIn .34s cubic-bezier(.22,1,.36,1) both; animation-delay: calc(var(--i) * 50ms); }
    @keyframes hvIn { from { opacity: 0; transform: translateY(8px); } }
    .hv-top { display: flex; align-items: center; gap: .6rem; }
    .hv-top i { flex: none; width: 2.3rem; height: 2.3rem; border-radius: .8rem; display: grid; place-items: center; font-style: normal; font-size: 1.2rem; background: var(--color-gray-50); }
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
    html.dark .hv-row { background: #1a1d23; border-color: #2a2f38; }
    html.dark .hv-d, html.dark .hv-top i { background: #20242c; border-color: #2a2f38; }
    html.dark .hv-d.is-anee { background: #2a2210; border-color: #5c4a24; }
    html.dark .hv-top b, html.dark .hv-d b { color: #e5e7eb; }
    @media (prefers-reduced-motion: reduce) { .hv-row, .hv-bar span { animation: none; } }
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
                <div class="hv-row" style="--i: {{ $i }}">
                    <div class="hv-top"><i aria-hidden="true">{{ $r['icon'] }}</i><span><b>{{ $r['name'] }}</b><small>{{ $r['crop'] }}</small></span></div>
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
            @empty
                <p class="hv-note">No lots in this season yet.</p>
            @endforelse
        </div>
        <p class="hv-foot">The original date is day zero plus the crop's days to maturity. The realigned date moves it by as many days as Anee found the crop ahead or behind when she read it. Both are a guide: check the grain or fruit before you harvest.</p>
    </div>
</div>
<script>
    document.getElementById('harvestDatesBtn')?.addEventListener('click', () => window.openSheet('harvestSheet'));
</script>
