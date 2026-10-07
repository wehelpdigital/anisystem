{{-- One drawn trend at the foot of a truth card (2026-10-07): six bars,
     three years ago to two years ahead, with an arrow that draws itself
     along their tops in the trend's direction. A picture of the point the
     card makes in words, not data: no numbers on it. Expects $cls (is-up,
     is-down or is-way), $label and $heights (six percentages). --}}
@php $stYear = (int) now()->timezone('Asia/Manila')->format('Y'); @endphp
<div class="hp-sum-trend {{ $cls }}" aria-hidden="true">
    <div class="hp-sum-plot" data-trend>
        <span class="hp-sum-bars">
            @foreach ($heights as $k => $h)
                <i class="{{ $k === 3 ? 'is-now' : ($k > 3 ? 'is-ahead' : '') }}" style="--h: {{ $h }}%; --k: {{ $k }}"></i>
            @endforeach
        </span>
        <svg class="hp-sum-arrow"></svg>
    </div>
    <span class="hp-sum-years">
        @foreach ($heights as $k => $h)
            <b class="{{ $k === 3 ? 'is-now' : ($k > 3 ? 'is-ahead' : '') }}">{{ $k === 3 ? 'Now' : $stYear + $k - 3 }}</b>
        @endforeach
    </span>
    <small>{{ $label }}</small>
</div>
@once
@push('scripts')
<script>
(() => {
    /* The arrow along the bars' tops: drawn in the plot's own pixels (so the
       head is never squashed), redrawn when the width changes, and drawn in
       once the card has come into view. */
    const NS = 'http://www.w3.org/2000/svg';
    const build = (plot) => {
        const svg = plot.querySelector('.hp-sum-arrow');
        const bars = [...plot.querySelectorAll('.hp-sum-bars i')];
        const box = plot.getBoundingClientRect();
        if (!box.width || !bars.length) return;
        const pts = bars.map((b) => {
            const r = b.getBoundingClientRect();
            const h = parseFloat(b.style.getPropertyValue('--h')) / 100;
            const inner = plot.querySelector('.hp-sum-bars').getBoundingClientRect();
            // The bar's full height, not its current (still growing) one.
            const top = inner.bottom - (inner.height - 7) * h - box.top;
            return [r.left - box.left + r.width / 2, top - 9];
        });
        const d = pts.map((p, i) => (i ? 'L' : 'M') + p[0].toFixed(1) + ' ' + p[1].toFixed(1)).join(' ');
        const [a, b] = [pts[pts.length - 2], pts[pts.length - 1]];
        const ang = Math.atan2(b[1] - a[1], b[0] - a[0]);
        const head = (len, spread) => [ang + Math.PI - spread, ang + Math.PI + spread]
            .map((t) => (b[0] + Math.cos(t) * len).toFixed(1) + ' ' + (b[1] + Math.sin(t) * len).toFixed(1));
        const [h1, h2] = head(10, .55);
        svg.setAttribute('viewBox', `0 0 ${box.width} ${box.height}`);
        svg.innerHTML = `<path class="ln" d="${d}" pathLength="1"/><path class="fl" d="${d}"/><path class="hd" d="M${h1} L${b[0].toFixed(1)} ${b[1].toFixed(1)} L${h2}"/>`;
    };
    const plots = [...document.querySelectorAll('[data-trend]')];
    const all = () => plots.forEach(build);
    all();
    if (document.fonts && document.fonts.ready) document.fonts.ready.then(all);
    let w = innerWidth;
    addEventListener('resize', () => { if (innerWidth !== w) { w = innerWidth; all(); } });
    const io = 'IntersectionObserver' in window ? new IntersectionObserver((es) => es.forEach((e) => {
        if (e.isIntersecting) { e.target.classList.add('is-drawn'); io.unobserve(e.target); }
    }), { threshold: .5 }) : null;
    plots.forEach((p) => (io ? io.observe(p) : p.classList.add('is-drawn')));
})();
</script>
@endpush
@endonce
