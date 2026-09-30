@extends('layouts.app')

@section('title', 'My Subscription')
@section('page-title', 'My Subscription')
@section('page-subtitle', 'Your plan, the plans, and your payments')
@section('back', route('account.index'))

@php
    use App\Support\ManualPay;
    use App\Support\Region;

    $currentTier = $user->planTier();
    $rankNow = ManualPay::rank($currentTier);
    $isAdmin = $currentTier === 'admin';
    $isWorker = \App\Support\WorkerContext::inWorkerContext();
    $status = $active ? 'active' : $subscription?->effective_status;
    $daysRemaining = $active?->daysRemaining();
    $expiringSoon = $active && $daysRemaining !== null && $daysRemaining <= 7 && $upcoming->isEmpty();

    $badgeFor = fn (?string $s) => match ($s) {
        'active' => ['badge-green', 'Active'],
        'pending' => ['badge-yellow', 'Awaiting verification'],
        'suspended' => ['badge-orange', 'Suspended'],
        'cancelled' => ['badge-gray', 'Cancelled'],
        'rejected' => ['badge-red', 'Payment rejected'],
        'expired' => ['badge-red', 'Expired'],
        default => ['badge-gray', ucfirst((string) $s)],
    };
    $orderBadge = fn (string $s) => match ($s) {
        'approved' => 'badge-green',
        'review' => 'badge-yellow',
        'awaiting' => 'badge-gray',
        'rejected', 'revoked' => 'badge-red',
        default => 'badge-gray',
    };
@endphp

@push('head')
<style>
    /* The plans, each with its own button; the switch picks the period. */
    .sp-toggle { display: inline-grid; grid-template-columns: 1fr 1fr; gap: .25rem; padding: .25rem; border-radius: 999px; background: #eef3e8; position: relative; }
    .sp-toggle button { position: relative; z-index: 1; padding: .45rem 1rem; border-radius: 999px; font-size: .85rem; font-weight: 800; color: #4b5563; transition: color .28s cubic-bezier(.22,1,.36,1); }
    .sp-toggle button.is-on { color: #14210c; }
    .sp-toggle i { position: absolute; top: .25rem; bottom: .25rem; left: .25rem; width: calc(50% - .375rem); border-radius: 999px; background: #fff;
        box-shadow: 0 6px 16px -10px rgb(20 33 12 / .5); transition: transform .28s cubic-bezier(.22,1,.36,1); }
    .sp-toggle.is-year i { transform: translateX(calc(100% + .25rem)); }
    .sp-save { font-size: .7rem; font-weight: 900; color: #2f7a1d; background: #e6f4da; padding: .1rem .45rem; border-radius: 999px; margin-left: .3rem; }
    .sp-grid { display: grid; gap: .8rem; }
    @media (min-width: 640px) { .sp-grid { grid-template-columns: repeat(2, minmax(0, 1fr)); } }
    @media (min-width: 1200px) { .sp-grid { grid-template-columns: repeat(4, minmax(0, 1fr)); } }
    .sp-card { display: flex; flex-direction: column; padding: 1.1rem 1.1rem 1rem; transition: box-shadow .28s cubic-bezier(.22,1,.36,1), transform .28s cubic-bezier(.22,1,.36,1); }
    .sp-card.is-current { box-shadow: 0 0 0 2px #4a7c2a, 0 14px 30px -24px rgb(20 33 12 / .5); }
    .sp-card .price { margin-top: .35rem; min-height: 2.6rem; position: relative; }
    .sp-card .price > span { display: block; transition: opacity .28s cubic-bezier(.22,1,.36,1), transform .28s cubic-bezier(.22,1,.36,1); }
    .sp-card .price > .y { position: absolute; inset: 0; opacity: 0; transform: translateY(6px); pointer-events: none; }
    .sp-grid.is-year .sp-card .price > .m { opacity: 0; transform: translateY(-6px); }
    .sp-grid.is-year .sp-card .price > .y { opacity: 1; transform: none; }
    .sp-card .price b { font-size: 1.55rem; font-weight: 900; color: #14210c; }
    .sp-card .price small { font-size: .78rem; font-weight: 700; color: #9ca3af; }
    .sp-card ul { margin-top: .8rem; display: grid; gap: .35rem; }
    .sp-card li { display: flex; gap: .4rem; font-size: .8rem; color: #4b5563; line-height: 1.4; }
    .sp-card li.x { color: #9ca3af; }
    .sp-buy { margin-top: auto; padding-top: 1rem; }
    .sp-buy .btn { width: 100%; }
    .sp-buy .y { display: none; }
    .sp-grid.is-year .sp-buy .m { display: none; }
    .sp-grid.is-year .sp-buy .y { display: inline-flex; }
    .sp-buy p { margin-top: .45rem; font-size: .74rem; color: #6b7280; text-align: center; line-height: 1.4; }
    .sp-note { display: flex; gap: .6rem; align-items: flex-start; padding: .85rem 1rem; border-radius: 1rem; font-size: .9rem; line-height: 1.5; }
    .sp-note svg { flex: none; width: 1.2rem; height: 1.2rem; margin-top: .1rem; }
    .sp-note.is-wait { background: #fff7e0; border: 1px solid #f6dc9a; color: #7a5200; }
    .sp-order { display: flex; align-items: center; gap: .75rem; padding: .85rem 1rem; transition: background .28s cubic-bezier(.22,1,.36,1); }
    .sp-order:hover { background: #f8faf5; }
    .sp-order + .sp-order { border-top: 1px solid #eef2ea; }
    html.dark .sp-toggle { background: #1b2616; }
    html.dark .sp-toggle i { background: #2c3b24; }
    html.dark .sp-toggle button.is-on, html.dark .sp-card .price b { color: #f3f4f6; }
    html.dark .sp-order:hover { background: #1b2616; }
    html.dark .sp-card li { color: #d1d5db; }
    html.dark .sp-card li.x { color: #6b7280; }
    html.dark .sp-order + .sp-order { border-color: #2d3a27; }
    @media (prefers-reduced-motion: reduce) { .sp-toggle i, .sp-toggle button, .sp-card, .sp-card .price > span, .sp-order { transition: none; } }
</style>
@endpush

@section('content')
    {{-- The free plan carries an advertisement here too: the page that ends them. --}}
    @include('partials.ad-slot', ['placement' => 'upgrade'])

<div class="max-w-5xl mx-auto space-y-5">

    @if ($locked)
        <div class="rounded-2xl border-2 border-red-300 bg-red-50 p-4 sm:p-5">
            <h2 class="font-bold text-red-800">Your access is locked</h2>
            <p class="text-sm text-red-700 mt-1">
                @if ($status === 'suspended')
                    Your subscription has been suspended. Please contact support@anee.io so we can help you restore access.
                @else
                    Choose a plan below to unlock the app. Your data is safe and waiting for you.
                @endif
            </p>
        </div>
    @endif

    {{-- A plan payment waiting for a person. --}}
    @if ($reviewPlan)
        <a href="{{ route('checkout', ['order' => $reviewPlan->orderNumber]) }}" class="sp-note is-wait block">
            <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path stroke-linecap="round" stroke-linejoin="round" d="M12 8v4l3 2m6-2a9 9 0 11-18 0 9 9 0 0118 0z"/></svg>
            <span><b>Your payment for {{ $reviewPlan->itemName }} is being reviewed.</b> Usually within {{ ManualPay::settings()['reviewHours'] }} hours; it switches on by itself when approved. Tap to see the order.</span>
        </a>
    @endif

    {{-- ============ YOUR PLAN ============ --}}
    <div class="card">
        <div class="card-body">
            <div class="flex items-start justify-between gap-3 mb-3">
                <h2 class="text-lg font-bold text-gray-900">Your plan</h2>
                @if ($active)<span class="badge badge-green">Active</span>@elseif ($isAdmin)<span class="badge badge-green">Admin</span>@else<span class="badge badge-gray">Free</span>@endif
            </div>
            @if ($isAdmin)
                <p class="text-sm text-gray-600">This account runs the platform: everything is open, and nothing is ever charged.</p>
            @elseif ($active)
                @php
                    $totalDays = max(1, (int) ($active->startsAt?->copy()->startOfDay()->diffInDays($active->expiresAt) ?? $active->durationDays));
                    $pct = max(0, min(100, (int) round(($daysRemaining ?? 0) / $totalDays * 100)));
                @endphp
                <div class="flex items-end justify-between gap-3">
                    <div class="min-w-0">
                        <p class="text-xl font-bold text-gray-900">{{ config('tiers.' . $currentTier . '.name') ?? $active->planName }}</p>
                        <p class="text-xs text-gray-500 mt-0.5">{{ $active->planName }}@if ($active->orderNumber) · {{ $active->orderNumber }}@endif</p>
                    </div>
                    <p class="text-sm font-bold {{ $expiringSoon ? 'text-orange-600' : 'text-brand-700' }} whitespace-nowrap">{{ $daysRemaining }} {{ \Illuminate\Support\Str::plural('day', (int) $daysRemaining) }} left</p>
                </div>
                <div class="h-2.5 rounded-full bg-gray-100 overflow-hidden mt-3">
                    <div class="h-full rounded-full {{ $expiringSoon ? 'bg-orange-500' : 'bg-brand-600' }}" style="width: {{ $pct }}%"></div>
                </div>
                <p class="text-xs text-gray-500 mt-2">Until {{ $active->expiresAt?->format('M j, Y') }}.@if ($expiringSoon) <b class="text-orange-600">Renew below so you do not lose access mid-season.</b>@endif</p>
                @foreach ($upcoming as $next)
                    <div class="mt-3 rounded-xl bg-brand-50 border border-brand-100 px-3 py-2.5 text-sm">
                        <span class="font-semibold text-brand-800">Next: {{ $next->planName }}</span>
                        <span class="text-gray-600">· {{ $next->startsAt->format('M j') }} – {{ $next->expiresAt->format('M j, Y') }}</span>
                    </div>
                @endforeach
            @else
                <p class="text-xl font-bold text-gray-900">Libre</p>
                <p class="text-sm text-gray-500 mt-0.5">Free forever. Pick a plan below whenever the farm needs more.</p>
                @if ($status === 'expired' && $subscription?->expiresAt)
                    <p class="text-xs text-gray-500 mt-2">Your {{ $subscription->planName }} ended {{ $subscription->expiresAt->format('M j, Y') }}. Your data is all still here.</p>
                @endif
            @endif

            {{-- Storage: the tier's cap and what the account's uploads (farm AND community) claim. --}}
            @php
                $storageCapGb = \App\Support\Tier::limit('storageGb');
                $storageUsedGb = round(\App\Support\Tier::storageUsed() / 1073741824, 2);
                $storagePct = $storageCapGb ? min(100, (int) round($storageUsedGb / $storageCapGb * 100)) : 0;
            @endphp
            <div class="rounded-xl bg-gray-50 px-3 py-2.5 mt-4 text-sm">
                <div class="flex items-center justify-between gap-3">
                    <p class="text-xs text-gray-500">Storage used <span class="text-gray-400">(photos, clips, files — farm and community)</span></p>
                    <p class="font-semibold text-gray-800 whitespace-nowrap">{{ $storageUsedGb }} GB <span class="text-gray-400 font-medium">/ {{ $storageCapGb === null ? 'unlimited' : $storageCapGb . ' GB' }}</span></p>
                </div>
                @if ($storageCapGb !== null)
                    <div class="mt-2 h-2 rounded-full bg-gray-200 overflow-hidden">
                        <div class="h-full rounded-full {{ $storagePct >= 90 ? 'bg-red-500' : 'bg-brand-600' }}" style="width: {{ max(2, $storagePct) }}%"></div>
                    </div>
                @endif
            </div>
        </div>
    </div>

    {{-- ============ THE PLANS ============ --}}
    <div>
        <div class="flex flex-wrap items-end justify-between gap-3 mb-3 px-1">
            <div>
                <h2 class="text-base font-bold text-gray-900">Plans</h2>
                <p class="text-sm text-gray-500">{{ Region::ph() ? 'Pay by GCash or bank transfer.' : 'Pay by PayPal.' }} Nothing renews by itself.</p>
            </div>
            <div class="sp-toggle" id="spToggle" role="tablist" aria-label="Billing period">
                <i aria-hidden="true"></i>
                <button type="button" class="is-on" data-period="month" role="tab" aria-selected="true">Monthly</button>
                <button type="button" data-period="year" role="tab" aria-selected="false">Yearly</button>
            </div>
        </div>
        <div class="sp-grid" id="spGrid">
            @foreach (config('tiers') as $key => $tier)
                @continue($key === 'admin')
                @php
                    $month = Region::tierPrice($key, 'month');
                    $year = Region::tierPrice($key, 'year');
                    $save = $month && $year ? (int) round((1 - $year / ($month * 12)) * 100) : 0;
                    $rank = ManualPay::rank($key);
                    $isCurrent = $currentTier === $key;
                @endphp
                <div class="card sp-card {{ $isCurrent ? 'is-current' : '' }}">
                    <div class="flex items-center justify-between gap-2">
                        <span class="font-bold text-gray-900" style="font-family:var(--font-heading)">{{ $tier['name'] }}</span>
                        @if ($isCurrent)<span class="badge badge-green">Your plan</span>@endif
                    </div>
                    <div class="price">
                        @if (! $month)
                            <span><b>Free</b></span>
                        @else
                            <span class="m"><b>{{ Region::priceTag($month) }}</b><small> / month</small></span>
                            <span class="y">@if ($year)<b>{{ Region::priceTag($year) }}</b><small> / year</small>@if ($save > 0)<span class="sp-save">Save {{ $save }}%</span>@endif @else<b>{{ Region::priceTag($month) }}</b><small> / month</small>@endif</span>
                        @endif
                    </div>
                    <p class="text-xs text-gray-500 mt-1">{{ $tier['tagline'] }}</p>
                    <ul>
                        @foreach ($tier['features'] as $f)<li><span class="text-brand-600">✓</span><span>{{ $f }}</span></li>@endforeach
                        @foreach ($tier['excludes'] as $f)<li class="x"><span>✕</span><span>{{ $f }}</span></li>@endforeach
                    </ul>
                    @if ($month && ! $isAdmin && ! $isWorker)
                        <div class="sp-buy">
                            @php
                                $label = $rank > $rankNow ? ($currentTier === 'libre' ? 'Get ' : 'Upgrade to ') . $tier['name'] : ($rank === $rankNow ? 'Renew ' . $tier['name'] : 'Get ' . $tier['name']);
                                $style = $rank >= $rankNow ? 'btn-primary' : 'btn-outline';
                            @endphp
                            @if ($reviewPlan)
                                <button type="button" class="btn btn-outline" disabled>Payment being reviewed</button>
                            @else
                                <a href="{{ route('checkout', ['item' => $key . ':month']) }}" class="btn {{ $style }} m">{{ $label }}</a>
                                <a href="{{ route('checkout', ['item' => $key . ':' . ($year ? 'year' : 'month')]) }}" class="btn {{ $style }} y">{{ $label }}</a>
                                @if ($rank < $rankNow && $rankNow > 0)
                                    <p>Starts when your {{ config('tiers.' . $currentTier . '.name') }} plan ends.</p>
                                @elseif ($rank === $rankNow && $active)
                                    <p>Adds time after {{ ($upcoming->last() ?? $active)->expiresAt?->format('M j, Y') }}.</p>
                                @elseif ($rank > $rankNow && $rankNow > 0)
                                    <p>Starts as soon as it is approved.</p>
                                @endif
                            @endif
                        </div>
                    @elseif ($month && $isWorker)
                        <div class="sp-buy"><p>You are working on a farm's plan. Plans are bought by the farm's owner.</p></div>
                    @endif
                </div>
            @endforeach
        </div>
    </div>

    {{-- ============ YOUR PAYMENTS ============ --}}
    @if ($orders->isNotEmpty())
        <div>
            <h2 class="text-base font-bold text-gray-900 mb-3 px-1">Your payments</h2>
            <div class="card overflow-hidden">
                @foreach ($orders as $o)
                    <a href="{{ route('checkout', ['order' => $o->orderNumber]) }}" class="sp-order">
                        <div class="min-w-0 grow">
                            <p class="font-semibold text-gray-900 truncate">{{ $o->itemName }}</p>
                            <p class="text-xs text-gray-500 mt-0.5">{{ $o->orderNumber }} · {{ $o->methodLabel() }} · {{ $o->created_at?->timezone('Asia/Manila')->format('M j, Y') }}</p>
                        </div>
                        <div class="text-right shrink-0">
                            <span class="badge {{ $orderBadge($o->status) }}">{{ $o->statusLabel() }}</span>
                            <p class="text-xs font-semibold text-gray-600 mt-1">{{ Region::money((float) $o->total) }}</p>
                        </div>
                    </a>
                @endforeach
            </div>
        </div>
    @endif

    {{-- Plans from before the checkout (and any given by an admin). --}}
    @if ($history->count() > 0)
        <details class="card">
            <summary class="card-body !py-3.5 cursor-pointer font-bold text-gray-900">Plan history <span class="text-gray-400 font-medium">({{ $history->count() }})</span></summary>
            <div class="px-4 pb-4 space-y-2">
                @foreach ($history as $row)
                    @php [$hBadge, $hLabel] = $badgeFor($row->effective_status); @endphp
                    <div class="flex items-center justify-between gap-3 rounded-xl bg-gray-50 px-3 py-2.5">
                        <div class="min-w-0">
                            <p class="font-semibold text-gray-900 truncate text-sm">{{ $row->planName }}</p>
                            <p class="text-xs text-gray-500 mt-0.5">
                                @if ($row->orderNumber) {{ $row->orderNumber }} · @endif
                                @if ($row->startsAt && $row->expiresAt) {{ $row->startsAt->format('M j, Y') }} – {{ $row->expiresAt->format('M j, Y') }} @else {{ $row->created_at?->format('M j, Y') }} @endif
                            </p>
                        </div>
                        <div class="text-right shrink-0">
                            <span class="badge {{ $hBadge }}">{{ $hLabel }}</span>
                            <p class="text-xs font-semibold text-gray-600 mt-1">{{ Region::money($row->price) }}</p>
                        </div>
                    </div>
                @endforeach
            </div>
        </details>
    @endif
</div>
@endsection

@push('scripts')
<script>
(() => {
    const toggle = document.getElementById('spToggle');
    const grid = document.getElementById('spGrid');
    if (!toggle || !grid) return;
    toggle.addEventListener('click', (e) => {
        const b = e.target.closest('[data-period]');
        if (!b) return;
        const year = b.dataset.period === 'year';
        toggle.classList.toggle('is-year', year);
        grid.classList.toggle('is-year', year);
        toggle.querySelectorAll('[data-period]').forEach((x) => { const on = x === b; x.classList.toggle('is-on', on); x.setAttribute('aria-selected', on ? 'true' : 'false'); });
    });
})();
</script>
@endpush
