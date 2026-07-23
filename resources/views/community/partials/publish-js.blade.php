<script>
(() => {
const __init = () => {
    const URLS = { publish: @json(route('community.publish')) };
    const fld = (id) => document.getElementById(id);

    function openPublishSheet(id, title, summary = '', region = '') {
        fld('publishScheduleId').value = id;
        fld('publishScheduleTitle').textContent = title || 'This plan';
        fld('publishSummary').value = summary || '';
        fld('publishRegion').value = region || '';
        openSheet('publishSheet');
    }
    window.openPublishSheet = openPublishSheet;

    document.addEventListener('click', (e) => {
        const btn = e.target.closest('.js-publish');
        if (!btn) return;
        openPublishSheet(btn.dataset.id, btn.dataset.title, btn.dataset.summary, btn.dataset.region);
    });

    fld('publishConfirmBtn')?.addEventListener('click', async (e) => {
        const btn = e.currentTarget;
        const id = fld('publishScheduleId').value;
        if (!id) return;
        btn.disabled = true;
        try {
            const res = await api(URLS.publish, {
                method: 'POST',
                body: {
                    scheduleId: id,
                    isPublic: 1,
                    publicSummary: fld('publishSummary').value.trim() || null,
                    publicRegion: fld('publishRegion').value.trim() || null,
                },
            });
            toast(res.message);
            closeSheet('publishSheet');
            setTimeout(() => window.location.reload(), 700);
        } catch (err) {
            // The API answers a not-ready plan with the specific reasons.
            const reasons = err.data && err.data.reasons;
            toast(reasons && reasons.length ? reasons[0] : err.message, 'error');
        } finally {
            btn.disabled = false;
        }
    });

    // Unpublish, wherever the button appears.
    document.addEventListener('click', async (e) => {
        const btn = e.target.closest('.js-unpublish');
        if (!btn) return;
        const ok = await confirmAction({
            title: 'Remove from the Community?',
            message: '"' + (btn.dataset.title || 'This plan') + '" will no longer be readable by other members.',
            detail: 'Comments and ratings are kept in case you share it again.',
            confirmText: 'Remove',
        });
        if (!ok) return;
        try {
            const res = await api(URLS.publish, { method: 'POST', body: { scheduleId: btn.dataset.id, isPublic: 0 } });
            toast(res.message);
            setTimeout(() => window.location.reload(), 700);
        } catch (err) {
            toast(err.message, 'error');
        }
    });
};
    if (document.readyState === 'loading') document.addEventListener('DOMContentLoaded', __init, { once: true });
    else __init();
})();
</script>
