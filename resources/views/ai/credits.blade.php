@extends('layouts.app')

@section('title', 'My Credits')
@section('page-title', 'My Credits')
@section('page-subtitle', 'What Anee has cost, and how to buy more')
@section('back', route('app.dashboard'))

@push('head')
<style>
    /* TWO TABS, one page. Links, so the tab is in the URL and survives a
       reload and a pagination link. */
    .acct-tabs { display: flex; gap: .35rem; margin-bottom: 1.1rem;
        border-bottom: 1px solid var(--color-gray-200); }
    .acct-tab { display: inline-flex; align-items: center; gap: .4rem;
        padding: .55rem .8rem; font-weight: 700; font-size: .88rem;
        color: var(--color-gray-500); border-bottom: 2px solid transparent;
        margin-bottom: -1px; white-space: nowrap; }
    .acct-tab:hover { color: var(--color-gray-700); }
    .acct-tab.is-active { color: var(--color-brand-700); border-bottom-color: var(--color-brand-600); }
    .acct-tab-n { font-size: .68rem; font-weight: 800; padding: .05rem .38rem;
        border-radius: 999px; background: var(--color-gray-100); color: var(--color-gray-500); }
    .acct-tab.is-active .acct-tab-n { background: var(--color-brand-50); color: var(--color-brand-700); }

    /* THE BALANCE, as a coin on a green field; the two buttons under it are
       the page's two tabs said as verbs. */
    .cr-hero { border-radius: 1.25rem; padding: 1.15rem 1.25rem; color: #fff;
        background: linear-gradient(135deg, #3d6823 0%, #2f5219 100%); position: relative; overflow: hidden; }
    .cr-hero::after { content: ''; position: absolute; right: -2.5rem; top: -2.5rem; width: 10rem; height: 10rem; border-radius: 999px;
        background: radial-gradient(circle, rgba(240,180,41,.35), transparent 65%); }
    .cr-hero-row { display: flex; align-items: center; gap: .9rem; position: relative; }
    .cr-coin { flex: none; width: 3.2rem; height: 3.2rem; }
    .cr-hero-k { font-size: .74rem; font-weight: 700; text-transform: uppercase; letter-spacing: .06em; color: #cfe6b8; }
    .cr-hero-n { font-size: 2rem; font-weight: 800; line-height: 1.1; font-variant-numeric: tabular-nums; }
    .cr-hero-n small { font-size: .95rem; font-weight: 700; color: #cfe6b8; margin-left: .25rem; }
    .cr-hero-p { font-size: .8rem; color: #d5e3c5; margin-top: .3rem; line-height: 1.45; position: relative; }
    .cr-hero-acts { display: flex; flex-wrap: wrap; gap: .5rem; margin-top: .9rem; position: relative; }
    .cr-hero-acts .btn { padding: .5rem .95rem; font-size: .84rem; }

    /* ONE MOVEMENT. What it was for, when, and what was left afterwards. */
    .cl-row { display: flex; align-items: center; gap: .7rem;
        padding: .7rem .9rem; border-bottom: 1px solid var(--color-gray-100); }
    .cl-row:last-child { border-bottom: 0; }
    .cl-face { width: 1.9rem; height: 1.9rem; flex: none; border-radius: 999px;
        display: flex; align-items: center; justify-content: center;
        font-weight: 800; font-size: .95rem; }
    .cl-face.is-out { background: #fff7ed; color: #b45309; }
    .cl-face.is-in { background: var(--color-brand-50); color: var(--color-brand-700); }
    .cl-mid { flex: 1 1 auto; min-width: 0; }
    .cl-what { display: block; font-size: .87rem; font-weight: 700; color: var(--color-gray-900);
        overflow: hidden; text-overflow: ellipsis; white-space: nowrap; }
    .cl-when { display: block; font-size: .73rem; color: var(--color-gray-400); }
    .cl-num { flex: none; text-align: right; }
    .cl-num b { display: block; font-size: .92rem; font-weight: 800; }
    .cl-num b.is-out { color: #b45309; }
    .cl-num b.is-in { color: var(--color-brand-700); }
    .cl-num small { display: block; font-size: .7rem; color: var(--color-gray-400); }

    /* THE PACKS. */
    .cr-packs { display: grid; gap: .8rem; grid-template-columns: minmax(0, 1fr); }
    @media (min-width: 640px) { .cr-packs { grid-template-columns: repeat(3, minmax(0, 1fr)); } }
    .cr-pack { display: flex; flex-direction: column; position: relative; }
    .cr-pack.is-best { border-color: var(--color-brand-500); box-shadow: 0 0 0 2px var(--color-brand-100); }
    .cr-pack-n { font-size: 1.6rem; font-weight: 800; color: var(--color-brand-700); line-height: 1.1; }
    .cr-pack-n small { font-size: .82rem; font-weight: 700; color: var(--color-gray-500); margin-left: .2rem; }

    /* THE PLAN THAT LETS YOU SPEND -- shown where the packs would be. */
    .cr-sell { display: flex; gap: .9rem; align-items: flex-start; }
    .cr-sell .e { flex: none; width: 2.9rem; height: 2.9rem; border-radius: 1rem; display: inline-flex; align-items: center; justify-content: center;
        background: var(--color-brand-50); color: var(--color-brand-700); }
    .cr-sell .e svg { width: 1.4rem; height: 1.4rem; }
    .cr-sell b { display: block; font-family: var(--font-heading); font-size: 1.02rem; color: var(--color-gray-900); }
    .cr-sell p { font-size: .84rem; line-height: 1.55; color: var(--color-gray-600); margin-top: .25rem; }
    .cr-sell-price { display: inline-flex; align-items: baseline; gap: .25rem; margin-top: .5rem; font-weight: 800; color: var(--color-brand-700); font-size: 1.1rem; }
    .cr-sell-price small { font-size: .74rem; font-weight: 700; color: var(--color-gray-500); }

    html.dark .acct-tabs { border-color: #2b3423; }
    html.dark .cl-row { border-color: #2b3423; }
    html.dark .cl-what { color: #e8efe1; }
    html.dark .cl-face.is-out { background: #2c2213; }
    html.dark .cr-sell b { color: #e8efe1; }
    html.dark .cr-sell p { color: #b7c2ad; }
    html.dark .cr-sell .e { background: #22301a; color: #a5c97e; }
</style>
@endpush

@section('content')
@php
    $spent = (int) floor((float) $balance);
    $anee = config('tiers.libreAnee');
    $textCost = $settings ? (int) ceil($settings->creditsPerInputK + $settings->creditsPerOutputK * 0.6) : 0;
    $photoCost = $settings ? (int) ceil($settings->creditsPerImage) : 0;
@endphp

<div class="max-w-4xl mx-auto space-y-5">

    {{-- THE BALANCE --}}
    <div class="cr-hero">
        <div class="cr-hero-row">
            <svg class="cr-coin" viewBox="0 0 24 24" aria-hidden="true"><circle cx="12" cy="12" r="9.2" fill="#f0b429" stroke="#c98a12" stroke-width="1.6"/><circle cx="12" cy="12" r="5" fill="none" stroke="#c98a12" stroke-width="1.3" opacity=".75"/></svg>
            <div class="min-w-0">
                <p class="cr-hero-k">AI credits on hand</p>
                @if ($unlimited)
                    <p class="cr-hero-n">Unlimited</p>
                @else
                    <p class="cr-hero-n">{{ number_format($spent) }}<small>credits</small></p>
                @endif
            </div>
        </div>
        <p class="cr-hero-p">
            @if ($unlimited)
                This account runs the platform and is never charged — but every credit Anee would have spent is still written in the log, so the house knows what she costs.
            @elseif ($settings)
                A text question costs about {{ $textCost }} credits; a photo adds about {{ $photoCost }}. The analyses have flat prices, said before anything is spent.
            @endif
        </p>
        <div class="cr-hero-acts">
            <a href="{{ route('ai.credits', ['tab' => 'buy']) }}" class="btn btn-accent">Buy credits</a>
            @if ($canBuy)
                <a href="{{ route('ai.home') }}" class="btn btn-white">Ask Anee</a>
            @endif
        </div>
    </div>

    @if ($pending)
        <div class="card p-4 border-l-4 border-accent-500">
            <p class="font-bold text-gray-900">Order {{ $pending->orderNumber }} is awaiting verification</p>
            <p class="text-sm text-gray-500 mt-1">
                {{ $pending->credits }} credits ({{ $pending->packName }}) will be added once your {{ \App\Support\Region::payMethod() }} payment is confirmed.
                This is usually within a few hours.
            </p>
        </div>
    @endif

    {{-- THE TABS --}}
    <div class="acct-tabs" role="tablist">
        <a href="{{ route('ai.credits') }}" class="acct-tab {{ $tab === 'log' ? 'is-active' : '' }}" role="tab" aria-selected="{{ $tab === 'log' ? 'true' : 'false' }}">
            Credits log
            @if ($ledger->total() > 0)<span class="acct-tab-n">{{ $ledger->total() }}</span>@endif
        </a>
        <a href="{{ route('ai.credits', ['tab' => 'buy']) }}" class="acct-tab {{ $tab === 'buy' ? 'is-active' : '' }}" role="tab" aria-selected="{{ $tab === 'buy' ? 'true' : 'false' }}">Buy credits</a>
    </div>

    {{-- ============================ THE LOG ============================
         Every AI answer already wrote a row here — the balance IS the sum of
         these, never a stored number — so this is the record being read at
         last rather than a new one being kept. --}}
    <div class="space-y-5 {{ $tab === 'log' ? '' : 'hidden' }}">
        @if ($ledger->isEmpty())
            <div class="card"><div class="card-body text-center py-10">
                <div class="mx-auto w-14 h-14 rounded-2xl bg-brand-50 flex items-center justify-center mb-3">
                    <svg class="w-7 h-7 text-brand-600" fill="none" stroke="currentColor" stroke-width="1.8" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" d="M9 12h6m-6 4h6m2 5H7a2 2 0 01-2-2V5a2 2 0 012-2h5.586a1 1 0 01.707.293l5.414 5.414a1 1 0 01.293.707V19a2 2 0 01-2 2z"/></svg>
                </div>
                <h3 class="font-bold text-gray-900 mb-1">Nothing spent yet</h3>
                <p class="text-sm text-gray-500">Ask Anee a question, or run an analysis, and what it cost will show up here.</p>
            </div></div>
        @else
            <div class="card"><div class="card-body !p-0">
                @foreach ($ledger as $row)
                    @php $d = (float) $row->delta; $out = $d < 0; @endphp
                    <div class="cl-row">
                        <span class="cl-face {{ $out ? 'is-out' : 'is-in' }}">{{ $out ? '−' : '+' }}</span>
                        <span class="cl-mid">
                            <span class="cl-what">{{ $row->reason ?: ($out ? 'AI usage' : 'Credits added') }}</span>
                            <span class="cl-when">
                                {{ $row->created_at?->format('M j, Y · g:ia') }}
                                @if ($row->source) · {{ ucfirst($row->source) }} @endif
                            </span>
                        </span>
                        <span class="cl-num">
                            <b class="{{ $out ? 'is-out' : 'is-in' }}">{{ $out ? '−' : '+' }}{{ number_format(abs($d), 2) }}</b>
                            {{-- What the balance stood at afterwards, as recorded — read, not
                                 recomputed. An unlimited account has no balance to stand at. --}}
                            <small>{{ ! $unlimited && $row->balanceAfter !== null ? number_format((float) $row->balanceAfter, 2) . ' left' : '' }}</small>
                        </span>
                    </div>
                @endforeach
            </div></div>
            @if ($ledger->hasPages())
                <div class="px-1">{{ $ledger->links() }}</div>
            @endif
        @endif
    </div>

    {{-- ============================ BUY ============================ --}}
    <div class="space-y-5 {{ $tab === 'buy' ? '' : 'hidden' }}">
        @if (! $canBuy)
            {{-- No Anee on this plan: the packs would buy nothing that can be
                 spent, so the plan that opens her stands here instead. --}}
            <div class="card p-5">
                <div class="cr-sell">
                    <span class="e"><svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.2" stroke-linecap="round" stroke-linejoin="round"><rect x="4" y="10.5" width="16" height="10" rx="2.5"/><path d="M8 10.5V7.5a4 4 0 0 1 8 0v3"/></svg></span>
                    <div class="min-w-0">
                        <b>Credits are spent with Anee, and Anee comes with {{ $anee['name'] ?? 'Libre + Anee' }}</b>
                        <p>Your <strong>{{ config('tiers.' . $tier . '.name') ?? ucfirst($tier) }}</strong> plan does not include her.
                            {{ $anee['name'] ?? 'Libre + Anee' }} is your plan exactly as it is, plus the chat, the four analyses, Realign and the credit shop
                            @if (! $unlimited && $spent > 0) — and the {{ number_format($spent) }} credits already waiting in your account become yours to spend. @else . @endif
                        </p>
                        <span class="cr-sell-price">{{ \App\Support\Region::priceTag(\App\Support\Region::tierPrice('libreAnee')) }}<small>/ month</small></span>
                        <div class="mt-4 flex flex-wrap gap-2">
                            <a href="{{ route('purchase.plans', ['plan' => 'libre-anee']) }}" class="btn btn-primary">Add Anee</a>
                            <a href="{{ route('account.subscription') }}" class="btn btn-white">See all plans</a>
                        </div>
                    </div>
                </div>
            </div>
        @elseif ($unlimited)
            <div class="card p-6 text-center">
                <p class="font-bold text-gray-900">Nothing to buy</p>
                <p class="text-sm text-gray-500 mt-1">This account runs the platform and is never charged.</p>
            </div>
        @else
            @php $best = $packs->sortBy(fn ($p) => $p->credits > 0 ? \App\Support\Region::packPrice($p) / $p->credits : PHP_FLOAT_MAX)->first(); @endphp
            <div class="cr-packs">
                @foreach ($packs as $pack)
                    @php $packPrice = \App\Support\Region::packPrice($pack); $isBest = $best && $best->id === $pack->id; @endphp
                    <div class="card p-4 cr-pack {{ $isBest ? 'is-best' : '' }}">
                        @if ($isBest)<span class="badge badge-green self-start mb-2">Best value</span>@endif
                        <h4 class="font-bold text-gray-900">{{ $pack->packName }}</h4>
                        <p class="cr-pack-n mt-1">{{ number_format($pack->credits) }}<small>credits</small></p>
                        <p class="text-lg font-extrabold text-gray-900 mt-1">{{ \App\Support\Region::money($packPrice) }}</p>
                        <p class="text-xs text-gray-500 mt-0.5">{{ \App\Support\Region::money($pack->credits > 0 ? $packPrice / $pack->credits : 0, 3) }} per credit</p>
                        @if ($pack->description)
                            <p class="text-sm text-gray-500 mt-2 grow">{{ $pack->description }}</p>
                        @endif
                        <a href="{{ route('ai.credits.payment', $pack->packKey) }}" class="btn {{ $isBest ? 'btn-primary' : 'btn-outline' }} mt-4">Buy</a>
                    </div>
                @endforeach
            </div>
            <p class="text-xs text-gray-500 px-1">Paid via {{ \App\Support\Region::payMethod() }} and verified by our team, usually within a few hours. Credits never expire.</p>
        @endif
    </div>
</div>
@endsection
