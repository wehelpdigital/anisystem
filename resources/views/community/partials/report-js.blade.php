@once
{{-- "This does not belong here."

     Any control with data-report="<type>:<id>" opens this. It asks for a
     reason, takes an optional sentence, and says thank you — nothing about
     the post changes, because a report is an opinion until somebody at the
     house agrees with it. --}}
<div class="sheet hidden" id="reportSheet" style="--sheet-width:26rem">
    <div class="sheet-handle"></div>
    <div class="sheet-header">
        <h3 class="sheet-title">Report this</h3>
        <button type="button" data-sheet-close class="btn-ghost p-2 rounded-full" aria-label="Close">✕</button>
    </div>
    <div class="sheet-body">
        <p class="rp-lead">What is wrong with it? The team sees this — the person who posted it does not.</p>
        <div class="rp-reasons" id="rpReasons">
            @foreach (\App\Models\CommunityReport::reasons() as $key => $label)
                <button type="button" class="rp-reason" data-reason="{{ $key }}">{{ $label }}</button>
            @endforeach
        </div>
        <label class="rp-more">
            <span>Anything else the team should know? <i>(optional)</i></span>
            <textarea id="rpDetails" rows="2" maxlength="1000" class="form-textarea" placeholder="Say a little more…"></textarea>
        </label>
    </div>
    <div class="sheet-footer">
        <button type="button" class="btn btn-ghost" data-sheet-close>Cancel</button>
        <button type="button" class="btn btn-danger" id="rpSend" disabled>Send report</button>
    </div>
</div>

<style>
    .rp-lead { font-size: .82rem; line-height: 1.5; color: var(--color-gray-500); margin-bottom: .6rem; }
    .rp-reasons { display: flex; flex-direction: column; gap: .35rem; }
    .rp-reason { text-align: left; padding: .55rem .7rem; border-radius: .7rem; cursor: pointer;
        border: 1px solid var(--color-gray-200); background: var(--color-white);
        font-size: .85rem; font-weight: 600; color: var(--color-gray-700);
        transition: background var(--dur) var(--ease-house), border-color var(--dur) var(--ease-house); }
    .rp-reason:hover { background: var(--color-gray-100); }
    .rp-reason.is-on { border-color: #ef4444; background: #fef2f2; color: #b91c1c; font-weight: 700; }
    .rp-more { display: block; margin-top: .7rem; font-size: .78rem; font-weight: 700; color: var(--color-gray-600); }
    .rp-more i { font-style: normal; font-weight: 500; color: var(--color-gray-400); }
    .rp-more textarea { margin-top: .3rem; }
    /* The door itself, wherever it is hung. */
    .rp-door { border: 0; background: transparent; cursor: pointer; padding: .25rem;
        color: var(--color-gray-300); line-height: 0; border-radius: .5rem;
        transition: color var(--dur) var(--ease-house), background var(--dur) var(--ease-house); }
    .rp-door svg { width: 1.05rem; height: 1.05rem; }
    .rp-door:hover { color: #dc2626; background: #fef2f2; }
    @media (prefers-reduced-motion: reduce) { .rp-reason, .rp-door { transition: none; } }
</style>

<script>
(function plazaReport() {
    if (window.__plazaReportBound) return;
    window.__plazaReportBound = true;

    const URL_REPORT = @json(route('community.report'));
    let target = null, reason = null;

    const sheet = () => document.getElementById('reportSheet');

    function reset() {
        reason = null;
        document.querySelectorAll('#rpReasons .rp-reason').forEach((b) => b.classList.remove('is-on'));
        const more = document.getElementById('rpDetails');
        if (more) more.value = '';
        const send = document.getElementById('rpSend');
        if (send) { send.disabled = true; send.textContent = 'Send report'; }
    }

    document.addEventListener('click', (e) => {
        const door = e.target.closest('[data-report]');
        if (door) {
            e.preventDefault();
            const [type, id] = String(door.getAttribute('data-report')).split(':');
            if (!type || !id) return;
            target = { type, id };
            reset();
            window.openSheet?.('reportSheet');
            return;
        }

        const pick = e.target.closest('#rpReasons .rp-reason');
        if (pick) {
            reason = pick.getAttribute('data-reason');
            document.querySelectorAll('#rpReasons .rp-reason').forEach((b) => b.classList.toggle('is-on', b === pick));
            const send = document.getElementById('rpSend');
            if (send) send.disabled = false;
            return;
        }
    });

    document.getElementById('rpSend')?.addEventListener('click', async (e) => {
        // Captured before the await: currentTarget is null on the far side.
        const btn = e.currentTarget;
        if (!target || !reason) return;
        btn.disabled = true;
        btn.textContent = 'Sending…';
        try {
            const res = await fetch(URL_REPORT, {
                method: 'POST',
                headers: {
                    'X-CSRF-TOKEN': document.querySelector('meta[name=csrf-token]')?.content || '',
                    'Content-Type': 'application/json',
                    Accept: 'application/json',
                },
                credentials: 'same-origin',
                body: JSON.stringify({
                    type: target.type,
                    id: parseInt(target.id, 10),
                    reason,
                    details: document.getElementById('rpDetails')?.value || '',
                }),
            });
            const data = await res.json();
            window.toast?.(data.message, data.success ? 'success' : 'error');
            if (data.success) window.closeSheet?.('reportSheet');
        } catch (_) {
            window.toast?.('Could not send that — try again.', 'error');
        } finally {
            btn.disabled = false;
            btn.textContent = 'Send report';
        }
    });

    document.addEventListener('sm:sheet-closed', (e) => {
        if (e.detail && e.detail.id === 'reportSheet') { target = null; reset(); }
    });
})();
</script>
@endonce
