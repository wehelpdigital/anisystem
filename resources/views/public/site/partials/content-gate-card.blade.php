{{-- The members' gate's card: the invitation to join anee.io free. Needs
     $title, $here (the path a new member comes back to) and may take $lead,
     $behind (what waits behind the gate), $foot (the line under that list;
     data-gate-foot lets a hub's script rewrite it) and $tl (Tagalog).
     Styles in content-gate-assets. --}}
@php
    $tl = $tl ?? false;
    $behind = $behind ?? [];
    $foot = $foot ?? '';
    $lead = $lead ?? ($tl
        ? 'Mag-sign up nang libre sa anee.io at bubukas ang buong pahina, pati ang lahat ng gabay sa pananim, binhi, paghahanda ng lupa, peste, sakit at damo.'
        : 'Sign up free to anee.io and the whole page opens, with every guide on crops, varieties, land preparation, pests, diseases and weeds.');
@endphp
<div class="sp-gate-card" id="spGateCard" data-gate-card>
    @if (\App\Support\ContentGate::previewing())
        <div class="sp-gate-as">{{ $tl ? 'Ganito ito nakikita ng bisita. Naka-sign in ka, kaya bukas sa iyo ang buong pahina.' : 'This is what a guest sees. You are signed in, so the whole page is open for you.' }}
            <a href="{{ url($here) }}">{{ $tl ? 'Bumalik sa buong pahina ›' : 'Back to the full page ›' }}</a></div>
    @endif
    <div class="sp-gate-lock" aria-hidden="true"><svg fill="none" stroke="currentColor" stroke-width="2.2" viewBox="0 0 24 24"><rect x="5" y="11" width="14" height="10" rx="2"/><path stroke-linecap="round" d="M8 11V8a4 4 0 018 0v3"/></svg></div>
    <h2>{{ $title }}</h2>
    <p>{{ $lead }}</p>
    @if ($behind || $foot !== '')
        <div class="sp-gate-in">
            @foreach ($behind as $h)<span>{{ $h }}</span>@endforeach
            <small data-gate-foot>{{ $foot }}</small>
        </div>
    @endif
    <div class="sp-gate-perks">
        <span>✓ {{ $tl ? 'Libre habambuhay sa Libre plan' : 'Free for good on the Libre plan' }}</span>
        <span>✓ {{ $tl ? 'Walang credit card' : 'No credit card' }}</span>
        <span>✓ {{ $tl ? 'Gumagana sa kahit anong phone' : 'Works on any phone' }}</span>
    </div>
    <div class="sp-gate-acts">
        <a href="{{ route('signup') }}?read={{ urlencode($here) }}" class="btn btn-accent">{{ $tl ? 'Mag-sign up nang libre' : 'Sign up free' }}</a>
        <a href="{{ route('login') }}?read={{ urlencode($here) }}" class="btn btn-outline">{{ $tl ? 'May account na ako' : 'I have an account' }}</a>
    </div>
    <p class="sp-gate-tools">{{ $tl
        ? 'Kasama rin ang mga tool: ang kalendaryo ng bawat gawain, ang pest at disease finder, ang variety finder, at si Anee, ang smart farm technician na sumasagot sa Tagalog.'
        : 'The tools come with it: a cropping calendar that dates every task, the pest, disease and variety finders, and Anee, the smart farm technician who answers in Tagalog or English.' }}</p>
</div>
