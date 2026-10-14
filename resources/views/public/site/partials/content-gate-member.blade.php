{{-- Where a guest stops (2026-10-14): a signed in member reads the whole
     guide, and this thin marker sits where the gate falls for a guest, with
     a link to see the page as a guest does (?guest=1). See
     App\Support\ContentGate and partials/content-gate. --}}
@php $tl = ($page->lang ?? '') === 'tl'; @endphp
@once
@push('head')
<style>
    .sp-member { display: grid; grid-template-columns: auto 1fr auto; align-items: center; gap: .35rem .75rem; margin: 1.6rem 0 1.2rem; padding: .75rem 1rem; border-radius: 1rem;
        background: #f3f9ec; border: 1px dashed #b9d69a; font-size: .85rem; line-height: 1.45; color: #31511b; }
    .sp-member svg { flex: none; width: 1.25rem; height: 1.25rem; color: #4d7f2a; }
    .sp-member b { font-weight: 800; }
    .sp-member a { font-weight: 800; color: #2d5016; text-decoration: underline; text-underline-offset: 3px; white-space: nowrap; }
    @media (max-width: 639.98px) { .sp-member { grid-template-columns: auto 1fr; align-items: start; } .sp-member a { grid-column: 2; } }
</style>
@endpush
@endonce
<div class="sp-member" role="note">
    <svg fill="none" stroke="currentColor" stroke-width="2.2" viewBox="0 0 24 24" aria-hidden="true"><rect x="5" y="11" width="14" height="10" rx="2"/><path stroke-linecap="round" d="M8 11V8a4 4 0 017.5-2"/></svg>
    <span><b>{{ $tl ? 'Para sa mga miyembro ang kasunod.' : 'The members\' part starts here.' }}</b>
        {{ $tl ? 'Bukas ito sa iyo dahil naka-sign in ka. Malabo ito sa mga bisita, sa ilalim ng libreng sign up.' : 'It is open for you because you are signed in. Guests see it blurred behind a free sign up.' }}</span>
    <a href="?guest=1#spGateCard">{{ $tl ? 'Tingnan bilang bisita ›' : 'See it as a guest ›' }}</a>
</div>
