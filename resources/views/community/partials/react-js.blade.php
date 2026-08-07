{{-- Delegated, optimistic reaction handler for every .react-btn on the page.
     Guarded singleton — include once per page (feed, wall, groups). Posts to
     community.react and reconciles with the server's fresh counts. --}}
<script>
(function () {
    if (window.__plazaReactBound) return;
    window.__plazaReactBound = true;

    const REACT_URL = @json(route('community.react'));
    const csrf = () => document.querySelector('meta[name=csrf-token]')?.content || '';
    const reduce = window.matchMedia('(prefers-reduced-motion: reduce)').matches;
    const say = (m, t) => { if (typeof window.toast === 'function') window.toast(m, t); };

    document.addEventListener('click', async (e) => {
        const btn = e.target.closest('.react-btn');
        if (!btn || btn.dataset.busy) return;
        btn.dataset.busy = '1';
        const bar = btn.closest('.react-bar');

        const paint = (counts, mine) => {
            bar.querySelectorAll('.react-btn').forEach((b) => {
                const k = b.dataset.react;
                const n = counts && counts[k] ? counts[k] : 0;
                const cEl = b.querySelector('.react-count');
                const prev = cEl.textContent;
                cEl.textContent = n > 0 ? String(n) : '';
                if (cEl.textContent !== prev && !reduce) { cEl.classList.remove('tick'); void cEl.offsetWidth; cEl.classList.add('tick'); }
                b.classList.toggle('is-mine', mine === k);
                b.setAttribute('aria-pressed', mine === k ? 'true' : 'false');
            });
        };

        // Snapshot current state for an optimistic flip + rollback on failure.
        const current = {};
        let mine = null;
        bar.querySelectorAll('.react-btn').forEach((b) => {
            current[b.dataset.react] = parseInt(b.querySelector('.react-count').textContent || '0', 10);
            if (b.classList.contains('is-mine')) mine = b.dataset.react;
        });
        const k = btn.dataset.react;
        const next = { ...current };
        let nextMine;
        if (mine === k) { next[k] = Math.max(0, next[k] - 1); nextMine = null; }
        else { if (mine) next[mine] = Math.max(0, next[mine] - 1); next[k] = (next[k] || 0) + 1; nextMine = k; }
        paint(next, nextMine);
        if (nextMine && !reduce) { btn.classList.remove('just-reacted'); void btn.offsetWidth; btn.classList.add('just-reacted'); }

        try {
            const res = await fetch(REACT_URL, {
                method: 'POST',
                headers: { 'X-CSRF-TOKEN': csrf(), 'Content-Type': 'application/json', Accept: 'application/json' },
                body: JSON.stringify({ targetType: btn.dataset.targetType, targetId: btn.dataset.targetId, reaction: k }),
                credentials: 'same-origin',
            });
            const d = await res.json();
            if (d.success) paint(d.data.counts || {}, d.data.mine);
            else { paint(current, mine); say(d.message || 'Could not react.', 'error'); }
        } catch (_) { paint(current, mine); say('Network error — try again.', 'error'); }
        finally { delete btn.dataset.busy; }
    });
})();
</script>
