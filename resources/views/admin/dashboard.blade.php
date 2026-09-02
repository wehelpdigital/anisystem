@extends('layouts.admin')

@section('title', 'Dashboard')
@section('subtitle', 'How the platform is doing')

@section('content')
    {{-- The numbers first, the shapes under them. Skeletons hold the layout
         while the figures travel, so loading looks like loading rather than
         like an empty platform. --}}
    <div class="ad-stats" id="dashStats">
        @for ($i = 0; $i < 4; $i++)
            <div class="card ad-stat"><div class="ad-skel h-6 w-16 mb-2"></div><div class="ad-skel h-3 w-24"></div></div>
        @endfor
    </div>

    <div class="grid gap-3 md:grid-cols-2 mt-3">
        <div class="card p-4">
            <p class="font-bold text-gray-900 dark:text-gray-100 text-sm">New clients</p>
            <p class="text-xs text-gray-400 mb-1">Registrations, last 12 months</p>
            <div class="ch-wrap" id="chartClients"><div class="ad-skel w-full h-24"></div></div>
        </div>
        <div class="card p-4">
            <p class="font-bold text-gray-900 dark:text-gray-100 text-sm">Sales</p>
            <p class="text-xs text-gray-400 mb-1">Paid subscriptions per month (&#8369;)</p>
            <div class="ch-wrap" id="chartSales"><div class="ad-skel w-full h-24"></div></div>
        </div>
    </div>
@endsection

@push('scripts')
<script>
(() => {
    const $id = (x) => document.getElementById(x);
    const money = (n) => '₱' + Number(n || 0).toLocaleString('en-PH', { maximumFractionDigits: 0 });
    const short = (n) => n >= 1000 ? (Math.round(n / 100) / 10) + 'k' : String(Math.round(n));

    /** Twelve months of divs. The tallest month sets the scale. */
    function bars(el, months, isMoney) {
        const max = Math.max(1, ...months.map((m) => m.value));
        el.innerHTML = months.map((m) => `
            <div class="ch-col">
                <div class="ch-bar" style="height:${Math.max(2, Math.round((m.value / max) * 100))}%">
                    ${m.value > 0 ? `<i>${isMoney ? short(m.value) : m.value}</i>` : ''}
                </div>
                <span class="ch-lbl">${m.label}</span>
            </div>`).join('');
    }

    async function load() {
        try {
            const res = await api('{{ route('admin.data.overview') }}', { method: 'GET' });
            const d = res.data;
            $id('dashStats').innerHTML = `
                <div class="card ad-stat"><b>${d.clients.toLocaleString()}</b><span>Clients</span>
                    ${d.clientsThisMonth ? `<small>+${d.clientsThisMonth} this month</small>` : ''}</div>
                <div class="card ad-stat"><b>${d.activeSubscriptions.toLocaleString()}</b><span>Active subscriptions</span></div>
                <div class="card ad-stat"><b>${money(d.salesThisMonth)}</b><span>Sales this month</span></div>
                <div class="card ad-stat"><b>${d.openTickets.toLocaleString()}</b><span>Open tickets</span>
                    ${d.creditsSpentThisMonth ? `<small>${d.creditsSpentThisMonth} AI credits used</small>` : ''}</div>`;
            bars($id('chartClients'), d.registrationsByMonth, false);
            bars($id('chartSales'), d.salesByMonth, true);
        } catch (err) { toast(err.message, 'error'); }
    }

    if (document.readyState === 'loading') document.addEventListener('DOMContentLoaded', load, { once: true });
    else load();
})();
</script>
@endpush
