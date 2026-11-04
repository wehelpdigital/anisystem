{{-- Add a map or drawing of your own to a cropping schedule (2026-11-05, the
     owner: "an option to add a map or drawing that is in the global level to
     a cropping schedule level"). One sheet for both pages: Maps and Draw call
     window.toSeason.open({ what, title, listUrl, addUrl, body }).

     A map is LINKED (the season uses the same map, see MapAccess); a drawing
     is COPIED into the season's notebook. The list comes from the server: the
     open seasons you may add to, each saying whether it already has it. --}}
@include('partials.tag-sheet-css')
<div class="sheet hidden" id="toSeasonSheet" style="--sheet-width:28rem">
    <div class="sheet-handle"></div>
    <div class="sheet-header">
        <h3 class="sheet-title" id="toSeasonTitle">Add to a cropping schedule</h3>
        <button type="button" data-sheet-close class="btn-ghost p-2 rounded-full" aria-label="Close">✕</button>
    </div>
    <div class="sheet-body">
        <p class="text-sm text-gray-500 mb-3" id="toSeasonSay"></p>
        <div class="dt-rows" id="toSeasonList"></div>
    </div>
</div>
<script>
    window.toSeason = window.toSeason || (() => {
        let job = null;
        const esc = window.escapeHtml || ((s) => String(s ?? '')
            .replace(/&/g, '&amp;').replace(/</g, '&lt;').replace(/>/g, '&gt;').replace(/"/g, '&quot;'));
        const TICK = '<svg class="dt-row-tick" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.6"><path stroke-linecap="round" stroke-linejoin="round" d="M5 13l4 4L19 7"/></svg>';
        const list = () => document.getElementById('toSeasonList');

        function row(s) {
            return `<button type="button" class="dt-row${s.in ? ' is-on' : ''}" data-season="${s.id}">`
                + '<span class="dt-row-e">🌱</span>'
                + `<span class="dt-row-body"><b>${esc(s.title)}</b><i>${s.in ? 'Already in this season' : 'Tap to add'}</i></span>`
                + TICK + '</button>';
        }

        async function open(opts) {
            job = opts;
            document.getElementById('toSeasonTitle').textContent = 'Add “' + (opts.title || (opts.what === 'map' ? 'Map' : 'Drawing')) + '” to a cropping schedule';
            document.getElementById('toSeasonSay').textContent = opts.what === 'map'
                ? 'The season uses this same map: its team sees it on the season\'s Maps. It stays yours, here too.'
                : 'A copy goes into the season\'s Notes, where its team sees it on Notes and Draw. Yours stays here.';
            list().innerHTML = '<p class="text-sm text-gray-400">Loading your cropping schedules…</p>';
            window.openSheet('toSeasonSheet');
            try {
                const r = await api(opts.listUrl);
                const seasons = (r.data && r.data.seasons) || [];
                list().innerHTML = seasons.length
                    ? seasons.map(row).join('')
                    : '<p class="text-sm text-gray-500">No open cropping schedule to add it to. A closed or archived season takes nothing new.</p>';
            } catch (err) {
                list().innerHTML = '<p class="text-sm text-red-600">' + esc(err.message || 'Could not load your cropping schedules.') + '</p>';
            }
        }

        document.addEventListener('click', async (e) => {
            const b = e.target.closest('#toSeasonList [data-season]');
            if (!b || !job) return;
            if (b.classList.contains('is-on')) { toast('It is already in this season.'); return; }
            if (b.disabled) return;
            b.disabled = true;
            b.querySelector('i').textContent = 'Adding…';
            try {
                const r = await api(job.addUrl, { method: 'POST', body: Object.assign({ seasonId: parseInt(b.dataset.season, 10) }, job.body) });
                b.classList.add('is-on');
                b.querySelector('i').textContent = 'Already in this season';
                toast(r.message || 'Added.');
                job.onDone?.(r);
            } catch (err) {
                b.querySelector('i').textContent = 'Tap to add';
                toast(err.message || 'Could not add it.', 'error');
            } finally { b.disabled = false; }
        });

        return { open };
    })();
</script>
