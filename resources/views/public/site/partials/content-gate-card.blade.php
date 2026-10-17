{{-- The members' gate's card. Needs $title and $here (the path a new member
     comes back to), and may take $lead, $tl (Tagalog) and $plus. No check
     lists and no count line ("113 weeds waiting for you", the owner: weird),
     2026-10-14. Styles in content-gate-assets.

     $plus (default true): the page's full text comes with Libre + Anee, not
     with a free account (2026-10-17, the owner: "registering does not give
     them those details because the feature is only available for libre
     plus"). A guest is asked to start free (no price or plan list on the public
     gate since 2026-10-18, the owner); a signed in member on Libre is asked
     to upgrade (ContentGate::upgradeView).
     $plus false (land preparation, crop guides): a free account opens it. --}}
@php
    $tl = $tl ?? false;
    $plus = $plus ?? true;
    // The page's main gate answers to #spGateCard; a finder's own gate has none.
    $cardId = $cardId ?? 'spGateCard';
    $G = \App\Support\ContentGate::class;
    // Signed in, not looking as a guest: the gate is only up because the plan does not open it.
    $member = auth()->check() && ! $G::previewing();
    $planItem = $plus ? \App\Support\ManualPay::item($G::PLUS_PLAN . ':month') : null;
    $planName = \App\Support\Tier::planName($G::PLUS_PLAN);
    $price = $planItem ? \App\Support\Region::money($planItem['price'], 0) : null;
    $lead = $lead ?? ($plus
        ? ($tl
            ? 'Bukas ang buong pahina sa ' . $planName . ', pati ang bawat gabay sa binhi, peste, sakit, damo at kakulangan sa sustansya, at ang mga field helper sa loob ng anee.io.'
            : 'The whole page is open with ' . $planName . ', and so is every guide on varieties, pests, diseases, weeds and nutrient deficiencies, with the field helpers inside anee.io.')
        : ($tl
            ? 'Mag-register nang libre sa anee.io para mabasa ang buong pahina, pati ang bawat gabay sa pananim at paghahanda ng lupa.'
            : 'Register for free on anee.io to read the whole page, and every guide on crops and land preparation.'));
@endphp
<div class="sp-gate-card" @if ($cardId) id="{{ $cardId }}" @endif data-gate-card>
    @if ($G::previewing())
        <div class="sp-gate-as">{{ $tl ? 'Ganito ito nakikita ng bisita.' : 'This is what a guest sees.' }}
            <a href="{{ url($here) }}">{{ $tl ? 'Bumalik sa buong pahina ›' : 'Back to the full page ›' }}</a></div>
    @endif
    <div class="sp-gate-lock" aria-hidden="true"><svg fill="none" stroke="currentColor" stroke-width="2.2" viewBox="0 0 24 24"><rect x="5" y="11" width="14" height="10" rx="2"/><path stroke-linecap="round" d="M8 11V8a4 4 0 018 0v3"/></svg></div>
    <h2>{{ $title }}</h2>
    <p>{{ $lead }}</p>
    <div class="sp-gate-acts">
        @if ($plus && $member)
            <a href="{{ $planItem ? route('checkout', ['item' => $G::PLUS_PLAN . ':month']) : route('account.subscription') }}" class="btn btn-accent">{{ $tl ? 'Mag-upgrade sa ' . $planName : 'Upgrade to ' . $planName }}</a>
            <a href="{{ route('account.subscription') }}" class="btn btn-outline">{{ $tl ? 'Tingnan ang lahat ng plan' : 'See every plan' }}</a>
        @elseif ($plus)
            <a href="{{ route('signup') }}?read={{ urlencode($here) }}" class="btn btn-accent">{{ $tl ? 'Magsimula nang libre' : 'Start free' }}</a>
            <a href="{{ route('login') }}?read={{ urlencode($here) }}" class="btn btn-outline">{{ $tl ? 'May account na ako' : 'I have an account' }}</a>
        @else
            <a href="{{ route('signup') }}?read={{ urlencode($here) }}" class="btn btn-accent">{{ $tl ? 'Mag-register nang libre' : 'Register for free' }}</a>
            <a href="{{ route('login') }}?read={{ urlencode($here) }}" class="btn btn-outline">{{ $tl ? 'May account na ako' : 'I have an account' }}</a>
        @endif
    </div>
    @if ($plus && $member)
        <p class="sp-gate-free">{{ $tl
            ? ($price ? $price . ' kada buwan. ' : '') . 'Naroon pa rin lahat ng nasa Libre mo, dagdag ang mga field helper at si Anee.'
            : ($price ? $price . ' a month. ' : '') . 'It keeps everything you have on Libre and adds the field helpers and Anee.' }}</p>
    @elseif ($plus)
        {{-- Price and plan lines off the public gate (the owner, 2026-10-18). --}}
        <p class="sp-gate-free">{{ $tl ? 'Libre ang account, walang credit card.' : 'The account is free, no credit card.' }}</p>
    @else
        <p class="sp-gate-free">{{ $tl ? 'Libre ang pag-register. Walang credit card. Gumagana sa kahit anong phone.' : 'Free to register. No credit card. Works on any phone.' }}</p>
    @endif
</div>
