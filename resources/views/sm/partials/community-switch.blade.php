{{--
    Reusable "Share to Community" horizontal switch. Toggles a schedule's
    public visibility through the existing community.publish endpoint.
    Expects: $schedule (id, isPublic). Include on the activities toolbar and
    the schedule hub. Style + JS render once per page via @once.
--}}
@php $csPublic = (bool) ($schedule->isPublic ?? false); @endphp

<div class="community-switch inline-flex items-center gap-2 shrink-0" data-schedule-id="{{ $schedule->id }}"
     data-publish-url="{{ route('community.publish') }}" data-view-url="{{ route('community.show', ['id' => $schedule->id]) }}">
    <button type="button" class="cs-toggle {{ $csPublic ? 'is-on' : '' }}" role="switch"
            aria-checked="{{ $csPublic ? 'true' : 'false' }}" aria-label="Share this plan to the Community" title="Share this plan to the Community">
        <span class="cs-knob"></span>
    </button>
    <span class="cs-label text-sm font-semibold text-gray-600 whitespace-nowrap">Share to Community</span>
</div>

@once
@push('head')
<style>
    .cs-toggle { position: relative; width: 2.5rem; height: 1.4rem; border-radius: 999px; background: #d1d5db; transition: background .18s ease; flex-shrink: 0; }
    .cs-toggle .cs-knob { position: absolute; top: 0.15rem; left: 0.15rem; width: 1.1rem; height: 1.1rem; border-radius: 999px; background: #fff; box-shadow: 0 1px 2px rgba(0,0,0,.25); transition: transform .18s ease; }
    .cs-toggle.is-on { background: #4a7c2a; }
    .cs-toggle.is-on .cs-knob { transform: translateX(1.1rem); }
    .cs-toggle.is-busy { opacity: .6; pointer-events: none; }
    html.dark .cs-toggle { background: #3a4152; }
    html.dark .cs-toggle.is-on { background: #4a7c2a; }
</style>
@endpush
@push('scripts')
<script>
document.addEventListener('click', async (e) => {
    const btn = e.target.closest('.community-switch .cs-toggle');
    if (!btn) return;
    const wrap = btn.closest('.community-switch');
    const scheduleId = wrap.getAttribute('data-schedule-id');
    const publishUrl = wrap.getAttribute('data-publish-url');
    const viewUrl = wrap.getAttribute('data-view-url');
    const turningOn = !btn.classList.contains('is-on');

    btn.classList.add('is-busy');
    try {
        const res = await fetch(publishUrl, {
            method: 'POST',
            headers: {
                'X-CSRF-TOKEN': document.querySelector('meta[name=csrf-token]').content,
                'Content-Type': 'application/json',
                Accept: 'application/json',
            },
            body: JSON.stringify({ scheduleId, isPublic: turningOn }),
        });
        const data = await res.json().catch(() => ({}));
        if (res.ok && data.success) {
            btn.classList.toggle('is-on', turningOn);
            btn.setAttribute('aria-checked', turningOn ? 'true' : 'false');
            if (turningOn && viewUrl) {
                toast('Shared to the Community.');
            } else {
                toast('Removed from the Community.');
            }
        } else {
            const reason = (data.data && data.data.reasons && data.data.reasons[0]) || data.message || 'Could not update sharing.';
            toast(reason, 'error');
        }
    } catch (_) {
        toast('Network error — try again.', 'error');
    } finally {
        btn.classList.remove('is-busy');
    }
});
</script>
@endpush
@endonce
