{{-- The members' gate's card: the invitation to register. Needs $title and
     $here (the path a new member comes back to), and may take $lead, $foot
     (one line of what waits: "And 459 more varieties waiting for you";
     data-gate-foot lets a hub's script rewrite it) and $tl (Tagalog). No
     check lists (the owner, 2026-10-14). Styles in content-gate-assets. --}}
@php
    $tl = $tl ?? false;
    $foot = $foot ?? '';
    // The page's main gate answers to #spGateCard; a finder's own gate has none.
    $cardId = $cardId ?? 'spGateCard';
    $lead = $lead ?? ($tl
        ? 'Mag-register nang libre sa anee.io para mabasa ang buong pahina, pati ang bawat gabay sa pananim, binhi, paghahanda ng lupa, peste, sakit at damo.'
        : 'Register for free on anee.io to read the whole page, and every guide on crops, varieties, land preparation, pests, diseases and weeds.');
@endphp
<div class="sp-gate-card" @if ($cardId) id="{{ $cardId }}" @endif data-gate-card>
    @if (\App\Support\ContentGate::previewing())
        <div class="sp-gate-as">{{ $tl ? 'Ganito ito nakikita ng bisita. Naka-sign in ka, kaya bukas sa iyo ang buong pahina.' : 'This is what a guest sees. You are signed in, so the whole page is open for you.' }}
            <a href="{{ url($here) }}">{{ $tl ? 'Bumalik sa buong pahina ›' : 'Back to the full page ›' }}</a></div>
    @endif
    <div class="sp-gate-lock" aria-hidden="true"><svg fill="none" stroke="currentColor" stroke-width="2.2" viewBox="0 0 24 24"><rect x="5" y="11" width="14" height="10" rx="2"/><path stroke-linecap="round" d="M8 11V8a4 4 0 018 0v3"/></svg></div>
    <h2>{{ $title }}</h2>
    <p>{{ $lead }}</p>
    <p class="sp-gate-foot" data-gate-foot>{{ $foot }}</p>
    <div class="sp-gate-acts">
        <a href="{{ route('signup') }}?read={{ urlencode($here) }}" class="btn btn-accent">{{ $tl ? 'Mag-register nang libre' : 'Register for free' }}</a>
        <a href="{{ route('login') }}?read={{ urlencode($here) }}" class="btn btn-outline">{{ $tl ? 'May account na ako' : 'I have an account' }}</a>
    </div>
    <p class="sp-gate-free">{{ $tl ? 'Libre ang pag-register. Walang credit card. Gumagana sa kahit anong phone.' : 'Free to register. No credit card. Works on any phone.' }}</p>
    <p class="sp-gate-tools">{{ $tl
        ? 'Nasa anee.io din ang kalendaryo na nagtatakda ng bawat gawain, ang pest, disease at variety finder, at si Anee, ang smart farm technician na sumasagot sa Tagalog.'
        : 'Inside anee.io you also find a cropping calendar that dates every task, the pest, disease and variety finders, and Anee, the smart farm technician who answers in Tagalog or English.' }}</p>
</div>
