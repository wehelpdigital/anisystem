@extends('layouts.app')

@section('title', 'Checkout')
@section('page-title', 'Checkout')
@section('page-subtitle', $order ? 'Order ' . $order->orderNumber : ($item['name'] ?? ''))
@section('back', ($item['kind'] ?? $order?->kind) === 'credits' ? route('ai.credits', ['tab' => 'buy']) : route('account.subscription'))

@php
    /* Orders paid by hand (2026-09-30): one page, four steps -- how to pay,
       pay, send the proof, the outcome. Opened on an item (?item=) it starts
       at step one; opened on an order (?order=) it resumes where that order
       stands. Every write is CheckoutController's; see OrderService. */
    $money = fn ($v) => \App\Support\Region::money((float) $v);
    $isPlan = ($item['kind'] ?? $order?->kind) === 'plan';
    $gcashFee = (float) $pay['gcashFee'];
    $aiPlan = $isPlan && $pay['aiAutoApprove'];
    $tierCfg = $isPlan && $item ? (array) config('tiers.' . $item['tier']) : [];
    $rankNow = \App\Support\ManualPay::rank($current);
    $rankNew = $isPlan && $item ? \App\Support\ManualPay::rank($item['tier']) : 0;
@endphp

@push('head')
<style>
    .co { max-width: 34rem; margin: 0 auto; }
    .co-dots { display: flex; align-items: center; justify-content: center; gap: .45rem; margin: .25rem 0 1rem; }
    .co-dots i { width: .5rem; height: .5rem; border-radius: 999px; background: #d6e0cc; transition: width .28s cubic-bezier(.22,1,.36,1), background .28s cubic-bezier(.22,1,.36,1); }
    .co-dots i.is-on { width: 1.6rem; background: #4a7c2a; }
    .co-dots i.is-done { background: #8fbf5f; }
    .co-sum { display: flex; align-items: center; gap: .85rem; padding: 1rem 1.1rem; }
    .co-sum .ic { flex: none; width: 2.8rem; height: 2.8rem; border-radius: .9rem; display: grid; place-items: center; background: #eef6e6; font-size: 1.35rem; }
    .co-sum b { display: block; color: #14210c; font-weight: 800; line-height: 1.25; }
    .co-sum small { display: block; color: #6b7280; font-size: .8rem; margin-top: .1rem; }
    .co-sum .amt { margin-left: auto; text-align: right; font-weight: 900; color: #14210c; white-space: nowrap; }
    .co-step { display: none; }
    /* One motion only: the next step fades in where it stands. It used to
       slide in sideways while the page scrolled up to it, two movements at
       once, which on a phone read as the screen lurching left then up. The
       page now jumps to the top while the step is still invisible (see
       show()), so all anybody sees is the step appearing. */
    .co-step.is-on { display: block; animation: coIn .26s cubic-bezier(.22,1,.36,1); }
    @keyframes coIn { from { opacity: 0; } to { opacity: 1; } }
    @media (prefers-reduced-motion: reduce) { .co-step.is-on { animation: none; } }
    .co-h { font-family: var(--font-heading); font-weight: 800; font-size: 1.2rem; color: #14210c; }
    .co-sub { color: #6b7280; font-size: .9rem; margin-top: .2rem; }
    /* The ways to pay */
    .co-methods { display: grid; gap: .65rem; margin-top: 1rem; }
    .co-method { display: flex; align-items: center; gap: .85rem; width: 100%; text-align: left; padding: .95rem 1rem; border-radius: 1.1rem;
        background: #fff; border: 2px solid #e5ebdf; transition: border-color .28s cubic-bezier(.22,1,.36,1), box-shadow .28s cubic-bezier(.22,1,.36,1), transform .28s cubic-bezier(.22,1,.36,1); }
    .co-method:hover:not(:disabled) { transform: translateY(-1px); box-shadow: 0 12px 26px -20px rgb(20 33 12 / .5); }
    .co-method.is-picked { border-color: #4a7c2a; box-shadow: 0 0 0 4px rgb(74 124 42 / .12); }
    .co-method:disabled { opacity: .55; cursor: not-allowed; }
    .co-method .logo { flex: none; width: 2.8rem; height: 2.8rem; border-radius: .85rem; display: grid; place-items: center; font-weight: 900; color: #fff; font-size: 1.15rem; }
    .co-method .logo.gcash { background: #0a58f5; }
    .co-method .logo.bank { background: #2d5016; }
    .co-method .logo.paypal { background: #003087; }
    .co-method b { display: block; color: #14210c; font-weight: 800; }
    .co-method small { display: block; color: #6b7280; font-size: .8rem; line-height: 1.4; margin-top: .1rem; }
    .co-method .tick { margin-left: auto; flex: none; width: 1.5rem; height: 1.5rem; border-radius: 999px; border: 2px solid #cfd8c7; display: grid; place-items: center;
        transition: background .28s cubic-bezier(.22,1,.36,1), border-color .28s cubic-bezier(.22,1,.36,1); }
    .co-method.is-picked .tick { background: #4a7c2a; border-color: #4a7c2a; }
    .co-method .tick svg { width: .9rem; height: .9rem; color: #fff; opacity: 0; transition: opacity .2s; }
    .co-method.is-picked .tick svg { opacity: 1; }
    .co-lines { margin-top: 1rem; border-radius: 1rem; background: #f7faf3; padding: .8rem 1rem; font-size: .92rem; }
    .co-lines div { display: flex; justify-content: space-between; gap: 1rem; padding: .2rem 0; color: #4b5563; }
    .co-lines .tot { border-top: 1px dashed #cfdcc2; margin-top: .35rem; padding-top: .55rem; color: #14210c; font-weight: 900; font-size: 1.05rem; }
    .co-lines [hidden] { display: none !important; }
    /* Paying */
    .co-amount { text-align: center; margin-top: .8rem; }
    .co-amount .n { font-family: var(--font-heading); font-weight: 900; font-size: clamp(2.2rem, 9vw, 2.8rem); color: #14210c; line-height: 1.05; letter-spacing: -.02em; }
    .co-warn { display: flex; gap: .6rem; align-items: flex-start; margin-top: .9rem; padding: .75rem .9rem; border-radius: .95rem; background: #fff7e6; border: 1px solid #f6d58e; color: #7a4b00; font-size: .87rem; line-height: 1.45; }
    .co-warn svg { flex: none; width: 1.2rem; height: 1.2rem; margin-top: .05rem; }
    .co-acct { margin-top: 1rem; border-radius: 1.1rem; border: 1px solid #e5ebdf; background: #fff; }
    .co-acct-row { display: flex; align-items: center; gap: .75rem; padding: .8rem 1rem; }
    .co-acct-row + .co-acct-row { border-top: 1px solid #eef2ea; }
    .co-acct-row small { display: block; font-size: .72rem; font-weight: 800; letter-spacing: .05em; text-transform: uppercase; color: #9ca3af; }
    .co-acct-row b { display: block; color: #14210c; font-weight: 800; font-size: 1.02rem; word-break: break-word; }
    .co-copy { margin-left: auto; flex: none; display: inline-flex; align-items: center; gap: .3rem; padding: .4rem .7rem; border-radius: .7rem; font-size: .8rem; font-weight: 800;
        color: #2d5016; background: #eef6e6; transition: background .28s cubic-bezier(.22,1,.36,1); }
    .co-copy:hover { background: #dfeecf; }
    .co-copy.is-done { background: #4a7c2a; color: #fff; }
    .co-copy svg { width: .95rem; height: .95rem; }
    .co-qr { display: flex; flex-direction: column; align-items: center; gap: .6rem; padding: 1rem; }
    .co-qr img { width: min(15rem, 70vw); border-radius: 1rem; box-shadow: 0 14px 30px -22px rgb(0 0 0 / .45); cursor: zoom-in; }
    .co-how { margin-top: 1rem; display: grid; gap: .5rem; counter-reset: h; }
    .co-how li { display: flex; gap: .65rem; align-items: flex-start; font-size: .9rem; color: #374151; line-height: 1.5; }
    .co-how li::before { counter-increment: h; content: counter(h); flex: none; width: 1.45rem; height: 1.45rem; border-radius: 999px; display: grid; place-items: center;
        background: #eef6e6; color: #2d5016; font-size: .78rem; font-weight: 900; margin-top: .05rem; }
    /* The proof */
    .co-seg { display: grid; grid-template-columns: repeat(3, minmax(0, 1fr)); gap: .3rem; padding: .3rem; border-radius: 1rem; background: #f1f5ec; margin-top: 1rem; }
    .co-seg button { padding: .6rem .3rem; border-radius: .75rem; font-size: .84rem; font-weight: 800; color: #4b5563; transition: background .28s cubic-bezier(.22,1,.36,1), color .28s cubic-bezier(.22,1,.36,1), box-shadow .28s cubic-bezier(.22,1,.36,1); }
    .co-seg button.is-on { background: #fff; color: #14210c; box-shadow: 0 6px 16px -10px rgb(20 33 12 / .5); }
    .co-drop { margin-top: .9rem; display: flex; flex-direction: column; align-items: center; justify-content: center; gap: .45rem; min-height: 11rem; padding: 1.2rem;
        border: 2px dashed #cfdcc2; border-radius: 1.2rem; background: #fbfdf8; text-align: center; cursor: pointer; transition: border-color .28s cubic-bezier(.22,1,.36,1), background .28s cubic-bezier(.22,1,.36,1); }
    .co-drop:hover, .co-drop.is-over { border-color: #4a7c2a; background: #f3f9ec; }
    .co-drop svg { width: 2.2rem; height: 2.2rem; color: #4a7c2a; }
    .co-drop b { color: #14210c; }
    .co-drop small { color: #6b7280; }
    .co-prev { display: none; margin-top: .9rem; position: relative; border-radius: 1.1rem; overflow: hidden; border: 1px solid #e5ebdf; background: #fff; }
    .co-prev.is-on { display: block; animation: coIn .32s cubic-bezier(.22,1,.36,1); }
    .co-prev img { display: block; width: 100%; max-height: 22rem; object-fit: contain; background: #f4f6f2; }
    .co-prev .pdf { display: flex; align-items: center; gap: .7rem; padding: 1rem; }
    .co-prev .pdf span { width: 2.6rem; height: 2.6rem; border-radius: .8rem; display: grid; place-items: center; background: #fee2e2; color: #b91c1c; font-weight: 900; font-size: .75rem; }
    .co-prev .x { position: absolute; top: .5rem; right: .5rem; width: 2rem; height: 2rem; border-radius: 999px; background: rgb(0 0 0 / .55); color: #fff; display: grid; place-items: center; }
    .co-field { margin-top: .9rem; }
    .co-field label { display: block; font-size: .82rem; font-weight: 800; color: #374151; margin-bottom: .35rem; }
    .co-hint { margin-top: .9rem; display: flex; gap: .55rem; align-items: flex-start; padding: .7rem .85rem; border-radius: .95rem; background: #eef6e6; color: #2d5016; font-size: .86rem; line-height: 1.45; }
    .co-hint svg { flex: none; width: 1.15rem; height: 1.15rem; margin-top: .05rem; }
    .co-actions { display: grid; gap: .55rem; margin-top: 1.25rem; }
    /* The outcome */
    .co-out { text-align: center; padding: 1.5rem 1.1rem 1.25rem; }
    .co-badge { width: 5rem; height: 5rem; margin: 0 auto; border-radius: 999px; display: grid; place-items: center; }
    .co-badge svg { width: 2.6rem; height: 2.6rem; }
    .co-badge.ok { background: #e6f4da; color: #2f7a1d; animation: coPop .5s cubic-bezier(.22,1.6,.36,1); }
    .co-badge.wait { background: #fff4d6; color: #b7791f; }
    .co-badge.no { background: #fde8e8; color: #c0392b; }
    .co-badge.wait svg { animation: coTurn 2.4s ease-in-out infinite; }
    @keyframes coPop { from { transform: scale(.4); opacity: 0; } to { transform: none; opacity: 1; } }
    @keyframes coTurn { 0%, 40% { transform: rotate(0); } 55%, 100% { transform: rotate(180deg); } }
    .co-out h2 { font-family: var(--font-heading); font-weight: 800; font-size: 1.35rem; color: #14210c; margin-top: 1rem; line-height: 1.25; }
    .co-out p { color: #4b5563; margin-top: .45rem; line-height: 1.55; }
    .co-facts { margin: 1.1rem auto 0; max-width: 22rem; text-align: left; border-radius: 1rem; background: #f7faf3; padding: .75rem 1rem; font-size: .9rem; }
    .co-facts div { display: flex; justify-content: space-between; gap: 1rem; padding: .22rem 0; color: #4b5563; }
    .co-facts b { color: #14210c; text-align: right; }
    .co-lightbox { position: fixed; inset: 0; z-index: 90; display: grid; place-items: center; background: rgb(0 0 0 / .8); padding: 1.2rem; opacity: 0; pointer-events: none; transition: opacity .28s cubic-bezier(.22,1,.36,1); }
    .co-lightbox.is-on { opacity: 1; pointer-events: auto; }
    .co-lightbox img { max-width: 100%; max-height: 88vh; border-radius: 1rem; background: #fff; }
    html.dark .co-method, html.dark .co-acct, html.dark .co-prev { background: var(--tl-surface, #1f2937); border-color: #374151; }
    html.dark .co-lines, html.dark .co-facts, html.dark .co-drop { background: #1b2616; border-color: #3b4a33; }
    html.dark .co-h, html.dark .co-sum b, html.dark .co-method b, html.dark .co-acct-row b, html.dark .co-amount .n, html.dark .co-out h2, html.dark .co-lines .tot, html.dark .co-facts b, html.dark .co-drop b, html.dark .co-sum .amt { color: #f3f4f6; }
    html.dark .co-seg { background: #1b2616; }
    html.dark .co-sub, html.dark .co-out p, html.dark .co-how li, html.dark .co-lines div, html.dark .co-facts div, html.dark .co-method small, html.dark .co-sum small { color: #cbd5c0; }
    html.dark .co-acct-row + .co-acct-row { border-color: #374151; }
    html.dark .co-seg button.is-on { background: #2c3b24; color: #f3f4f6; }
    @media (prefers-reduced-motion: reduce) {
        .co-step.is-on, .co-prev.is-on, .co-badge.ok, .co-badge.wait svg { animation: none; }
        .co-method, .co-dots i, .co-copy, .co-seg button, .co-drop, .co-lightbox { transition: none; }
    }
</style>
@endpush

@section('content')
<div class="co" id="co">
    <div class="co-dots" aria-hidden="true"><i></i><i></i><i></i><i></i></div>

    {{-- What is being bought, always in view. --}}
    @php
        $showName = $order?->itemName ?? $item['name'];
        $showPrice = $order ? (float) $order->price : (float) $item['price'];
    @endphp
    <div class="card co-sum" id="coSum">
        <span class="ic" aria-hidden="true">{{ $isPlan ? '🌾' : '🪙' }}</span>
        <div class="min-w-0">
            <b>{{ $showName }}</b>
            <small>
                @if ($isPlan && $item)
                    @if ($rankNew > $rankNow && $current !== 'libre')
                        Starts the moment it is approved: an upgrade from your {{ config('tiers.' . $current . '.name') }} plan.
                    @elseif ($rankNew === $rankNow)
                        Adds {{ $item['period'] === 'year' ? 'a year' : '30 days' }} after your current plan ends.
                    @elseif ($rankNew < $rankNow)
                        Starts when your current {{ config('tiers.' . $current . '.name') }} plan ends.
                    @else
                        Starts the moment it is approved.
                    @endif
                    @if (($item['credits'] ?? 0) > 0) Comes with {{ number_format($item['credits']) }} AI credits. @endif
                @elseif ($item)
                    AI credits never expire.
                @endif
            </small>
        </div>
        <span class="amt">{{ $money($showPrice) }}</span>
    </div>

    {{-- ============ 1. HOW WILL YOU PAY ============ --}}
    <section class="co-step" data-step="1">
        <div class="card p-5 mt-4">
            <h2 class="co-h">How will you pay?</h2>
            <p class="co-sub">{{ $isPh ? 'We do not have card payments yet. Pay by GCash or a bank transfer, then send us the receipt.' : 'Pay by PayPal, then send us the receipt.' }}</p>
            <div class="co-methods" role="radiogroup" aria-label="How you will pay">
                @foreach ($methods as $key => $m)
                    <button type="button" class="co-method" role="radio" aria-checked="false" data-method="{{ $key }}" @disabled(! $m['ready'])>
                        <span class="logo {{ $key }}" aria-hidden="true">{{ ['gcash' => 'G', 'bank' => '🏦', 'paypal' => 'P'][$key] ?? '₱' }}</span>
                        <span class="min-w-0">
                            <b>{{ $m['label'] }}</b>
                            <small>
                                @if (! $m['ready'])
                                    Not available yet. Please use {{ $isPh ? 'GCash' : 'another way' }} for now.
                                @elseif ($key === 'gcash')
                                    @if ($aiPlan) Checked by Anee in about a minute, so your plan can start right away. @else Checked by a person, usually within {{ $pay['reviewHours'] }} hours. @endif
                                    + {{ $money($gcashFee) }} processing fee.
                                @else
                                    Checked by a person, usually within {{ $pay['reviewHours'] }} hours.
                                @endif
                            </small>
                        </span>
                        <span class="tick" aria-hidden="true"><svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="3"><path stroke-linecap="round" stroke-linejoin="round" d="M5 13l4 4L19 7"/></svg></span>
                    </button>
                @endforeach
            </div>
            <div class="co-lines">
                <div><span>{{ $isPlan ? 'Plan' : 'Credits' }}</span><span>{{ $money($showPrice) }}</span></div>
                <div id="coFeeLine" hidden><span>GCash processing fee</span><span>{{ $money($gcashFee) }}</span></div>
                <div class="tot"><span>You pay</span><span id="coTotal">{{ $money($showPrice) }}</span></div>
            </div>
            <div class="co-actions">
                <button type="button" class="btn btn-primary btn-lg" id="coStart" disabled>Continue</button>
            </div>
        </div>
    </section>

    {{-- ============ 2. PAY ============ --}}
    <section class="co-step" data-step="2">
        <div class="card p-5 mt-4">
            <h2 class="co-h" id="coPayTitle">Send your payment</h2>
            <div class="co-amount">
                <p class="n" id="coPayAmount">—</p>
                <button type="button" class="co-copy mt-2" data-copy-from="coPayAmountRaw"><svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><rect x="9" y="9" width="11" height="11" rx="2"/><path d="M5 15V5a2 2 0 012-2h10"/></svg>Copy amount</button>
                <span id="coPayAmountRaw" hidden></span>
            </div>
            <div class="co-warn" role="note">
                <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.2"><path stroke-linecap="round" stroke-linejoin="round" d="M12 9v4m0 4h.01M10.3 3.9L1.8 18a2 2 0 001.7 3h17a2 2 0 001.7-3L13.7 3.9a2 2 0 00-3.4 0z"/></svg>
                <span><b>Send exactly this amount.</b> A wrong payment is not refunded, so check the amount and the number twice before you send.</span>
            </div>

            {{-- GCash --}}
            <div data-pay="gcash" hidden>
                <div class="co-acct">
                    <div class="co-acct-row"><div class="min-w-0"><small>GCash number</small><b>{{ \App\Support\ManualPay::spacedNumber() }}</b></div>
                        <button type="button" class="co-copy" data-copy="{{ preg_replace('/\D/', '', $pay['gcashNumber']) }}"><svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><rect x="9" y="9" width="11" height="11" rx="2"/><path d="M5 15V5a2 2 0 012-2h10"/></svg>Copy</button></div>
                    <div class="co-acct-row"><div class="min-w-0"><small>Account name (as GCash shows it)</small><b>{{ $pay['gcashName'] }}</b></div></div>
                    <div class="co-qr">
                        <small class="text-xs font-extrabold uppercase tracking-wider text-gray-400">Or scan this QR</small>
                        <img src="{{ $qrUrl }}" alt="GCash QR code for anee.io" id="coQr" width="482" height="641" loading="lazy">
                        <a href="{{ $qrUrl }}" download="anee-gcash-qr.png" class="co-copy"><svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path stroke-linecap="round" stroke-linejoin="round" d="M12 4v11m0 0l-4-4m4 4l4-4M5 20h14"/></svg>Save the QR</a>
                    </div>
                </div>
                <ol class="co-how">
                    <li><span>Open GCash and tap <b>Send</b> › <b>Express Send</b>, or <b>QR</b> › <b>Upload QR</b> with the saved QR.</span></li>
                    <li><span>Send exactly <b data-total-text>—</b> to <b>{{ \App\Support\ManualPay::spacedNumber() }}</b>.</span></li>
                    <li><span>Keep the receipt screen: take a screenshot of it. That is what we check.</span></li>
                </ol>
            </div>

            {{-- Bank --}}
            <div data-pay="bank" hidden>
                <div class="co-acct">
                    <div class="co-acct-row"><div class="min-w-0"><small>Bank</small><b>{{ $pay['bankName'] }}</b></div></div>
                    <div class="co-acct-row"><div class="min-w-0"><small>Account name</small><b>{{ $pay['bankAccountName'] }}</b></div></div>
                    <div class="co-acct-row"><div class="min-w-0"><small>Account number</small><b>{{ $pay['bankAccountNumber'] }}</b></div>
                        <button type="button" class="co-copy" data-copy="{{ preg_replace('/\s+/', '', $pay['bankAccountNumber']) }}"><svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><rect x="9" y="9" width="11" height="11" rx="2"/><path d="M5 15V5a2 2 0 012-2h10"/></svg>Copy</button></div>
                    @if (trim($pay['bankBranch']) !== '')<div class="co-acct-row"><div class="min-w-0"><small>Branch</small><b>{{ $pay['bankBranch'] }}</b></div></div>@endif
                </div>
                @if (trim($pay['bankNote']) !== '')<p class="co-sub mt-3">{{ $pay['bankNote'] }}</p>@endif
                <ol class="co-how">
                    <li><span>Transfer exactly <b data-total-text>—</b> to the account above, from your bank app or at the branch.</span></li>
                    <li><span>Keep the receipt or the confirmation screen. That is what we check.</span></li>
                </ol>
            </div>

            {{-- PayPal --}}
            <div data-pay="paypal" hidden>
                <div class="co-acct">
                    @if (filled($paypal['email'] ?? null))<div class="co-acct-row"><div class="min-w-0"><small>PayPal</small><b>{{ $paypal['email'] }}</b></div>
                        <button type="button" class="co-copy" data-copy="{{ $paypal['email'] }}"><svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><rect x="9" y="9" width="11" height="11" rx="2"/><path d="M5 15V5a2 2 0 012-2h10"/></svg>Copy</button></div>@endif
                    @if (filled($paypal['link'] ?? null))<div class="co-acct-row"><div class="min-w-0"><small>Pay link</small><b><a href="{{ $paypal['link'] }}" target="_blank" rel="noopener" class="text-brand-700 underline">{{ $paypal['link'] }}</a></b></div></div>@endif
                </div>
                <ol class="co-how">
                    <li><span>Send exactly <b data-total-text>—</b> by PayPal ("Friends and family" if you can).</span></li>
                    <li><span>Keep the receipt or the confirmation email. That is what we check.</span></li>
                </ol>
            </div>

            <p class="co-sub mt-4">Order <b id="coPayNumber">—</b>. You can add it as the message.</p>
            <div class="co-actions">
                <button type="button" class="btn btn-primary btn-lg" data-go="3">I've sent it — next step</button>
                <button type="button" class="btn btn-white" id="coChange">Change how I pay</button>
            </div>
        </div>
    </section>

    {{-- ============ 3. THE PROOF ============ --}}
    <section class="co-step" data-step="3">
        <form class="card p-5 mt-4" id="coProof" novalidate>
            <h2 class="co-h">Show us the payment</h2>
            <p class="co-sub">Send one of these. {{ $aiPlan ? 'A screenshot of the GCash receipt is fastest: Anee checks it in about a minute.' : 'A person checks it, usually within ' . $pay['reviewHours'] . ' hours.' }}</p>
            <div class="co-seg" role="tablist">
                <button type="button" class="is-on" data-proof="image" role="tab" aria-selected="true">Screenshot</button>
                <button type="button" data-proof="pdf" role="tab" aria-selected="false">PDF receipt</button>
                <button type="button" data-proof="ref" role="tab" aria-selected="false">Reference no.</button>
            </div>

            <div data-proof-pane="file">
                <label class="co-drop" id="coDrop">
                    <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.8"><path stroke-linecap="round" stroke-linejoin="round" d="M4 16l4.6-4.6a2 2 0 012.8 0L16 16m-2-2l1.6-1.6a2 2 0 012.8 0L20 14M14 8h.01M6 20h12a2 2 0 002-2V6a2 2 0 00-2-2H6a2 2 0 00-2 2v12a2 2 0 002 2z"/></svg>
                    <b id="coDropSay">Tap to choose the screenshot</b>
                    <small id="coDropSmall">JPG, PNG or WebP, up to 8 MB</small>
                    <input type="file" id="coFile" class="sr-only" accept="image/jpeg,image/png,image/webp,image/heic">
                </label>
                <div class="co-prev" id="coPrev">
                    <img id="coPrevImg" alt="Your receipt" hidden>
                    <div class="pdf" id="coPrevPdf" hidden><span>PDF</span><b id="coPrevName"></b></div>
                    <button type="button" class="x" id="coPrevX" aria-label="Remove the file"><svg viewBox="0 0 24 24" width="16" height="16" fill="none" stroke="currentColor" stroke-width="2.4"><path stroke-linecap="round" d="M6 6l12 12M18 6L6 18"/></svg></button>
                </div>
            </div>
            <div class="co-field" data-proof-pane="ref">
                <label for="coRef" id="coRefLabel">Reference number <span class="font-medium text-gray-400">(optional)</span></label>
                <input type="text" id="coRef" class="form-input" inputmode="numeric" autocomplete="off" maxlength="40" placeholder="{{ $isPh ? 'e.g. 1023 796 539709' : 'The transaction ID' }}">
            </div>
            <div class="co-hint" id="coRefOnlyHint" hidden>
                <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path stroke-linecap="round" stroke-linejoin="round" d="M13 16h-1v-4h-1m1-4h.01M21 12a9 9 0 11-18 0 9 9 0 0118 0z"/></svg>
                <span>With only the number, a person matches it by hand, usually within {{ $pay['reviewHours'] }} hours. A screenshot is faster.</span>
            </div>
            <div class="co-field">
                <label for="coNote">A note for us <span class="font-medium text-gray-400">(optional)</span></label>
                <input type="text" id="coNote" class="form-input" maxlength="500" placeholder="e.g. paid from my wife's GCash">
            </div>
            <div class="co-actions">
                <button type="submit" class="btn btn-primary btn-lg" id="coSend">Send for checking</button>
                <button type="button" class="btn btn-white" data-go="2" data-back>Back</button>
            </div>
        </form>
    </section>

    {{-- ============ 4. THE OUTCOME ============ --}}
    <section class="co-step" data-step="4">
        <div class="card co-out mt-4" id="coOut"></div>
    </section>

    <div class="co-lightbox" id="coLightbox" role="dialog" aria-label="QR code"><img src="{{ $qrUrl }}" alt=""></div>
</div>
@endsection

@push('sheets')
    @include('sm.partials.anee-wait')
@endpush

@push('scripts')
<script>
(() => {
    const ITEM = @json($item['key'] ?? null);
    const IS_PLAN = @json($isPlan);
    const AI_PLAN = @json($aiPlan);
    const PRICE = {{ $showPrice }};
    const FEE = { gcash: {{ $gcashFee }} };
    const START_URL = @json(route('checkout.start'));
    const LINKS = {
        farm: @json(route('app.dashboard')),
        plans: @json(route('account.subscription')),
        credits: @json(route('ai.credits')),
        again: @json($item ? route('checkout', ['item' => $item['key']]) : route('account.subscription')),
    };
    const money = (n) => new Intl.NumberFormat('en-PH', { style: 'currency', currency: @json($order->currency ?? ($item['currency'] ?? 'PHP')), minimumFractionDigits: 2 }).format(n);
    const $ = (s, r = document) => r.querySelector(s);
    const $$ = (s, r = document) => [...r.querySelectorAll(s)];

    let order = @json($orderJson);
    let method = order?.method || null;
    let proofKind = 'image';
    let step = 1;
    let poll = null;

    // ---- steps
    function show(n, back = false) {
        step = n;
        $$('.co-step').forEach((s) => { const on = +s.dataset.step === n; s.classList.toggle('is-on', on); s.classList.toggle('is-back', on && back); });
        $$('.co-dots i').forEach((d, i) => { d.classList.toggle('is-on', i === n - 1); d.classList.toggle('is-done', i < n - 1); });
        // Straight to the top, not a smooth scroll: the new step starts
        // invisible, so the jump is never seen and its fade is the only move.
        if (window.scrollY > 0) window.scrollTo({ top: 0, behavior: 'auto' });
    }
    $$('[data-go]').forEach((b) => b.addEventListener('click', () => show(+b.dataset.go, b.hasAttribute('data-back'))));

    // ---- 1. how to pay
    function pick(m) {
        method = m;
        $$('.co-method').forEach((b) => { const on = b.dataset.method === m; b.classList.toggle('is-picked', on); b.setAttribute('aria-checked', on ? 'true' : 'false'); });
        const fee = FEE[m] || 0;
        $('#coFeeLine').hidden = !fee;
        $('#coTotal').textContent = money(PRICE + fee);
        $('#coStart').disabled = false;
    }
    $$('.co-method').forEach((b) => b.addEventListener('click', () => !b.disabled && pick(b.dataset.method)));
    const firstReady = $('.co-method:not(:disabled)');
    if (firstReady && !method) pick(firstReady.dataset.method);

    $('#coStart').addEventListener('click', async () => {
        const btn = $('#coStart');
        btn.disabled = true;
        btn.textContent = 'One moment…';
        try {
            const res = await window.api(START_URL, { method: 'POST', body: { item: ITEM, method } });
            order = res.data;
            paintPay();
            history.replaceState(null, '', order.urls.self);
            show(2);
        } catch (e) {
            window.toast?.(e.message, 'error');
        } finally {
            btn.disabled = false;
            btn.textContent = 'Continue';
        }
    });

    // ---- 2. pay
    function paintPay() {
        $$('[data-pay]').forEach((p) => { p.hidden = p.dataset.pay !== order.method; });
        $('#coPayAmount').textContent = order.totalText;
        $('#coPayAmountRaw').textContent = order.total.toFixed(2);
        $$('[data-total-text]').forEach((e) => { e.textContent = order.totalText; });
        $('#coPayNumber').textContent = order.number;
        $('#coPayTitle').textContent = 'Send ' + order.totalText + ' by ' + order.methodLabel;
        // The proof step speaks the method's language.
        const gcash = order.method === 'gcash';
        $('#coRef').placeholder = gcash ? 'e.g. 1023 796 539709' : (order.method === 'paypal' ? 'The PayPal transaction ID' : 'The bank reference number');
    }
    $('#coChange').addEventListener('click', async () => {
        if (order?.status === 'awaiting') {
            try { await window.api(order.urls.cancel, { method: 'POST' }); } catch (e) { /* the next start lets it go anyway */ }
            order = null;
            history.replaceState(null, '', LINKS.again);
        }
        show(1, true);
    });
    document.addEventListener('click', async (e) => {
        const c = e.target.closest('[data-copy], [data-copy-from]');
        if (!c) return;
        const text = c.dataset.copy ?? $('#' + c.dataset.copyFrom)?.textContent ?? '';
        try {
            await navigator.clipboard.writeText(text);
            const was = c.innerHTML;
            c.classList.add('is-done');
            c.lastChild.textContent = 'Copied';
            setTimeout(() => { c.classList.remove('is-done'); c.innerHTML = was; }, 1600);
        } catch (err) { window.toast?.('Copy did not work here. Please type it.', 'info'); }
    });
    $('#coQr')?.addEventListener('click', () => $('#coLightbox').classList.add('is-on'));
    $('#coLightbox').addEventListener('click', () => $('#coLightbox').classList.remove('is-on'));

    // ---- 3. the proof
    const file = $('#coFile');
    function setProof(kind) {
        proofKind = kind;
        $$('.co-seg button').forEach((b) => { const on = b.dataset.proof === kind; b.classList.toggle('is-on', on); b.setAttribute('aria-selected', on ? 'true' : 'false'); });
        $('[data-proof-pane="file"]').hidden = kind === 'ref';
        $('#coRefOnlyHint').hidden = kind !== 'ref';
        $('#coRefLabel').innerHTML = kind === 'ref' ? 'Reference number' : 'Reference number <span class="font-medium text-gray-400">(optional)</span>';
        file.accept = kind === 'pdf' ? 'application/pdf' : 'image/jpeg,image/png,image/webp,image/heic';
        $('#coDropSay').textContent = kind === 'pdf' ? 'Tap to choose the PDF receipt' : 'Tap to choose the screenshot';
        $('#coDropSmall').textContent = kind === 'pdf' ? 'PDF, up to 5 MB' : 'JPG, PNG or WebP, up to 8 MB';
        const f = file.files[0];
        if (f && ((kind === 'pdf') !== (f.type === 'application/pdf'))) clearFile();
        if (kind === 'ref') setTimeout(() => $('#coRef').focus(), 50);
    }
    $$('.co-seg button').forEach((b) => b.addEventListener('click', () => setProof(b.dataset.proof)));
    function clearFile() {
        file.value = '';
        $('#coPrev').classList.remove('is-on');
        $('#coDrop').hidden = false;
    }
    function paintFile() {
        const f = file.files[0];
        if (!f) return clearFile();
        const isPdf = f.type === 'application/pdf';
        const limit = isPdf ? 5 : 8;
        if (f.size > limit * 1048576) { window.toast?.('That file is over ' + limit + ' MB.', 'error'); return clearFile(); }
        $('#coPrevImg').hidden = isPdf;
        $('#coPrevPdf').hidden = !isPdf;
        if (isPdf) $('#coPrevName').textContent = f.name;
        else $('#coPrevImg').src = URL.createObjectURL(f);
        $('#coDrop').hidden = true;
        $('#coPrev').classList.add('is-on');
    }
    file.addEventListener('change', paintFile);
    $('#coPrevX').addEventListener('click', clearFile);
    const drop = $('#coDrop');
    ['dragenter', 'dragover'].forEach((ev) => drop.addEventListener(ev, (e) => { e.preventDefault(); drop.classList.add('is-over'); }));
    ['dragleave', 'drop'].forEach((ev) => drop.addEventListener(ev, (e) => { e.preventDefault(); drop.classList.remove('is-over'); }));
    drop.addEventListener('drop', (e) => { if (e.dataTransfer.files[0]) { file.files = e.dataTransfer.files; paintFile(); } });
    // A GCash Ref No. reads best in its own 4-3-6 groups.
    $('#coRef').addEventListener('input', (e) => {
        if (order?.method !== 'gcash') return;
        const d = e.target.value.replace(/\D/g, '').slice(0, 13);
        e.target.value = [d.slice(0, 4), d.slice(4, 7), d.slice(7)].filter(Boolean).join(' ');
    });

    $('#coProof').addEventListener('submit', async (e) => {
        e.preventDefault();
        const f = proofKind === 'ref' ? null : file.files[0];
        const ref = $('#coRef').value.trim();
        if (!f && !ref) {
            window.toast?.(proofKind === 'ref' ? 'Type the reference number.' : 'Choose the receipt first, or send the reference number instead.', 'error');
            return;
        }
        if (order?.method === 'gcash' && proofKind === 'ref' && ref.replace(/\D/g, '').length !== 13) {
            window.toast?.('A GCash reference number has 13 digits.', 'error');
            return;
        }
        const fd = new FormData();
        if (f) fd.append('file', f);
        if (ref) fd.append('refNumber', ref);
        if ($('#coNote').value.trim()) fd.append('note', $('#coNote').value.trim());

        const willCheck = AI_PLAN && order.method === 'gcash' && !!f;
        const btn = $('#coSend');
        btn.disabled = true;
        btn.textContent = 'Sending…';
        if (willCheck && window.aneeWait) {
            window.aneeWait.show({ title: 'Anee is checking your receipt', lines: ['Reading the receipt…', 'Matching the amount…', 'Checking the reference number…', 'Almost there…'], sub: 'This takes about a minute.',
                stay: 'Please keep this page open. If you leave, your payment is still safe: a person will check it instead.' });
        }
        try {
            const res = await window.api(order.urls.proof, { method: 'POST', body: fd });
            order = res.data;
            if (willCheck && window.aneeWait) {
                await window.aneeWait.done(order.status === 'approved'
                    ? { title: 'Payment confirmed', line: 'Your plan is ready.' }
                    : { title: 'Received', line: 'A person will take it from here.' });
            }
            paintOutcome();
            show(4);
        } catch (err) {
            window.aneeWait?.fail?.();
            window.toast?.(err.message, 'error');
        } finally {
            btn.disabled = false;
            btn.textContent = 'Send for checking';
        }
    });

    // ---- 4. the outcome
    const ICON = {
        ok: '<svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.6"><path stroke-linecap="round" stroke-linejoin="round" d="M5 13l4 4L19 7"/></svg>',
        wait: '<svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path stroke-linecap="round" stroke-linejoin="round" d="M6 2h12M6 22h12M7 2v4.5a5 5 0 002 4L12 12l3-1.5a5 5 0 002-4V2M7 22v-4.5a5 5 0 012-4L12 12l3 1.5a5 5 0 012 4V22"/></svg>',
        no: '<svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.4"><path stroke-linecap="round" d="M6 6l12 12M18 6L6 18"/></svg>',
    };
    const esc = (s) => String(s ?? '').replace(/[&<>"']/g, (c) => ({ '&': '&amp;', '<': '&lt;', '>': '&gt;', '"': '&quot;', "'": '&#39;' }[c]));
    function facts(rows) {
        return '<div class="co-facts">' + rows.filter((r) => r[1]).map((r) => `<div><span>${esc(r[0])}</span><b>${esc(r[1])}</b></div>`).join('') + '</div>';
    }
    function paintOutcome() {
        const o = order;
        const box = $('#coOut');
        const base = [['Order', o.number], ['For', o.itemName], ['Paid', o.totalText + ' by ' + o.methodLabel]];
        let html;
        if (o.status === 'approved') {
            const plan = o.kind === 'plan';
            html = `<div class="co-badge ok">${ICON.ok}</div>
                <h2>${plan ? (o.queued ? 'Approved — your plan is lined up' : esc(o.itemName) + ' is active') : esc(o.credits.toLocaleString()) + ' credits added'}</h2>
                <p>${plan ? (o.queued ? 'It starts ' + esc(o.startsAt) + ', right after your current plan ends.' : 'Everything in your plan is open now, until ' + esc(o.expiresAt) + '.') : 'They are in your account now and never expire.'}
                ${o.decidedByAi ? ' Anee checked your receipt.' : ''}</p>
                ${facts(base.concat(plan ? [['Active until', o.expiresAt]] : []))}
                <div class="co-actions"><a class="btn btn-primary btn-lg" href="${plan ? LINKS.farm : LINKS.credits}">${plan ? 'Open my farm' : 'See my credits'}</a></div>`;
        } else if (o.status === 'review') {
            html = `<div class="co-badge wait">${ICON.wait}</div>
                <h2>We are checking your payment</h2>
                <p>A person is looking at it now, usually within {{ $pay['reviewHours'] }} hours. We will email you and ring the bell the moment it is approved, and it switches on by itself.</p>
                ${facts(base.concat([['Sent', o.submittedAt]]))}
                <div class="co-actions"><a class="btn btn-white btn-lg" href="${o.kind === 'plan' ? LINKS.plans : LINKS.credits}">Done</a></div>`;
            startPoll();
        } else if (o.status === 'rejected' || o.status === 'revoked') {
            html = `<div class="co-badge no">${ICON.no}</div>
                <h2>${o.status === 'revoked' ? 'This purchase was revoked' : 'We could not verify this payment'}</h2>
                <p>${o.reason ? esc(o.reason) + ' ' : ''}If you did pay, write to <a class="text-brand-700 underline" href="mailto:support@anee.io">support@anee.io</a> with your receipt and a person will sort it out with you.</p>
                ${facts(base)}
                <div class="co-actions"><a class="btn btn-primary btn-lg" href="${LINKS.again}">Try again</a></div>`;
        } else {
            html = `<div class="co-badge no">${ICON.no}</div>
                <h2>This order was not paid</h2>
                <p>It was let go before any payment came in. Start again whenever you are ready.</p>
                <div class="co-actions"><a class="btn btn-primary btn-lg" href="${LINKS.again}">Start again</a></div>`;
        }
        box.innerHTML = html;
    }
    function startPoll() {
        if (poll) return;
        poll = setInterval(async () => {
            if (document.hidden) return;
            try {
                const res = await window.api(order.urls.status);
                if (res.data.status !== order.status) {
                    order = res.data;
                    clearInterval(poll);
                    poll = null;
                    paintOutcome();
                    if (order.status === 'approved') window.toast?.('Your payment was approved.', 'success');
                }
            } catch (e) { /* try again next time */ }
        }, 20000);
    }

    // ---- where to begin
    if (order) {
        method = order.method;
        if (order.status === 'awaiting') { paintPay(); show(2); } else { paintOutcome(); show(4); }
    } else {
        show(1);
    }
})();
</script>
@endpush
