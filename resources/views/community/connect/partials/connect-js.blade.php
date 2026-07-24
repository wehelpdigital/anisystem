{{-- Delegated connect/accept/decline/remove handling. Include once per page. --}}
@once
@push('scripts')
<script>
(() => {
    const CSRF = document.querySelector('meta[name=csrf-token]').content;
    const headers = { 'X-CSRF-TOKEN': CSRF, Accept: 'application/json' };

    function actionHtml(status, id) {
        if (status === 'none') return `<button type="button" class="btn btn-primary btn-sm conn-btn" data-action="connect">Connect</button>`;
        if (status === 'pending_out') return `<button type="button" class="btn btn-white btn-sm conn-btn" data-action="disconnect">Requested</button>`;
        if (status === 'pending_in') return `<button type="button" class="btn btn-primary btn-sm conn-btn" data-action="accept">Accept</button><button type="button" class="btn btn-white btn-sm conn-btn" data-action="decline">Decline</button>`;
        if (status === 'connected') return `<span class="badge badge-green">Connected</span><button type="button" class="btn btn-ghost btn-sm text-gray-400 conn-btn" data-action="disconnect" title="Remove connection">✕</button>`;
        return '';
    }

    const routes = {
        connect: (id) => ({ url: `/app/community/members/${id}/connect`, method: 'POST' }),
        accept: (id) => ({ url: `/app/community/members/${id}/accept`, method: 'POST' }),
        decline: (id) => ({ url: `/app/community/members/${id}/decline`, method: 'POST' }),
        disconnect: (id) => ({ url: `/app/community/members/${id}/connection`, method: 'DELETE' }),
    };

    document.addEventListener('click', async (e) => {
        const btn = e.target.closest('.conn-btn');
        if (!btn) return;
        const wrap = btn.closest('.conn-action');
        const id = wrap.getAttribute('data-member-id');
        const action = btn.getAttribute('data-action');
        const spec = routes[action];
        if (!spec) return;
        const { url, method } = spec(id);
        wrap.querySelectorAll('button').forEach((b) => (b.disabled = true));
        try {
            const res = await fetch(url, { method, headers });
            const data = await res.json();
            if (data.success) {
                wrap.setAttribute('data-status', data.data.status);
                wrap.innerHTML = actionHtml(data.data.status, id);
                toast(data.message);
            } else {
                toast(data.message, 'error');
                wrap.querySelectorAll('button').forEach((b) => (b.disabled = false));
            }
        } catch (_) {
            toast('Network error — try again.', 'error');
            wrap.querySelectorAll('button').forEach((b) => (b.disabled = false));
        }
    });
})();
</script>
@endpush
@endonce
