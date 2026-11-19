{{-- The solution part of a guide, for a member on the free plan (2026-11-19, the owner: "after registration (free),
     the public gated pages should be readable. however, the solution part is still gated and advertize the module of
     anee and say to upgrade to get it"). The rest of the page reads in full; each solution part (how to control it,
     the sprays, what to apply, and the questions that ask for them) is blurred here. The first carries the card that
     names the module inside anee.io; later ones a slim bar. The module itself stays locked on the free plan
     (FieldPackGate). Needs $page, $locked (the blocks, keys kept) and $first. See App\Support\ContentGate. --}}
@php
    $G = \App\Support\ContentGate::class;
    $tl = ($page->lang ?? '') === 'tl';
    $subject = $G::subject($page);
    [$mod, $tool] = $G::MODULES[$page->section] ?? ['field helpers', 'pests'];
    $planName = \App\Support\Tier::planName($G::PLUS_PLAN);
    $planItem = \App\Support\ManualPay::item($G::PLUS_PLAN . ':month');
    $price = $planItem ? \App\Support\Region::money($planItem['price'], 0) : null;
    $buy = $planItem ? route('checkout', ['item' => $G::PLUS_PLAN . ':month']) : route('account.subscription');
    // Like the guest gate: the real words only for a proven crawler, a reader gets the same shapes with the letters swapped.
    $real = \App\Support\Crawler::trusted();
    $shown = $real ? $locked : $G::veil($locked, $page->section . '/' . $page->slug . '/solution');
    [$title, $lead] = $tl
        ? ['Nasa ' . $planName . ' ang solusyon', 'Mag-upgrade para mabasa kung paano ito pigilan at kung ano ang gagamitin, at para magamit ang ' . $mod . ' sa loob ng anee.io.']
        : match ($page->section) {
            'pests' => [($subject ? 'How to stop ' . $subject : 'How to stop each pest') . ' comes with ' . $planName,
                'Upgrade to read how to control it, when it pays to spray and the active ingredients that work. You also get the Pest Finder inside anee.io: pick your crop and the damage you see, and it shows you what to spray.'],
            'diseases' => [($subject ? 'How to stop ' . $subject : 'How to stop each disease') . ' comes with ' . $planName,
                'Upgrade to read how to control it, what to do before any spray and the active ingredients that work. You also get the Disease Finder inside anee.io: pick your crop and the signs you see, and it shows you what to spray and when.'],
            'weeds' => [($subject ? 'How to control ' . $subject : 'How to control each weed') . ' comes with ' . $planName,
                'Upgrade to read what to do and what to spray at every age of your crop. You also get the Weed Control Helper inside anee.io: pick your crop and its age, and it shows you the active ingredients that work.'],
            'nutrient-deficiencies' => ['What to apply comes with ' . $planName,
                'Upgrade to read what to apply for each deficiency and the fertilizers sold here that supply it. You also get the Nutrient Deficiency Finder inside anee.io: from the leaves you see to the missing nutrient and the fix.'],
            default => ['The solution comes with ' . $planName, 'Upgrade to read it, and to use the field helpers inside anee.io.'],
        };
@endphp
@include('public.site.partials.content-gate-assets')
@once
@push('head')
<style>
    .sp-gate.is-solution { margin-top: 1rem; margin-bottom: 1.6rem; }
    .sp-gate.is-solution .sp-gated { max-height: 15rem; }
    .sp-gate-card.is-solution { margin-top: -7rem; }
    .sp-gate.is-solution.is-more .sp-gated { max-height: 7.5rem; }
    .sp-sol-more { position: relative; z-index: 2; display: flex; flex-wrap: wrap; align-items: center; gap: .45rem .7rem; margin-top: -2.6rem; padding: .7rem 1rem;
        border-radius: 1rem; background: #fff8e1; border: 1px solid #f3dd9a; color: #5b4a12; font-size: .86rem; line-height: 1.45; font-weight: 700; }
    .sp-sol-more svg { flex: none; width: 1.1rem; height: 1.1rem; color: #b8860b; }
    .sp-sol-more span { flex: 1 1 12rem; min-width: 0; }
    .sp-sol-more a { margin-left: auto; font-weight: 800; color: #5b4a12; text-decoration: underline; text-underline-offset: 3px; white-space: nowrap;
        transition: color .28s cubic-bezier(.22,1,.36,1); }
    .sp-sol-more a:hover { color: #2d5016; }
    @media (max-width: 639.98px) { .sp-gate-card.is-solution { margin-top: -6rem; } }
</style>
@endpush
@endonce
<div class="sp-gate is-solution {{ $first ? '' : 'is-more' }}">
    <div class="sp-gated" @unless ($real) aria-hidden="true" translate="no" @endunless>
        @include('public.site.blocks', ['blocks' => $shown])
    </div>
    @if ($first)
        <div class="sp-gate-card is-solution" id="spGateCard" data-gate-card
             data-track-section="content_gate" data-track-view="content_gate_view" data-track-gate_for="solution">
            <div class="sp-gate-lock" aria-hidden="true"><svg fill="none" stroke="currentColor" stroke-width="2.2" viewBox="0 0 24 24"><rect x="5" y="11" width="14" height="10" rx="2"/><path stroke-linecap="round" d="M8 11V8a4 4 0 018 0v3"/></svg></div>
            <h2>{{ $title }}</h2>
            <p>{{ $lead }}</p>
            <div class="sp-gate-acts">
                <a href="{{ $buy }}" class="btn btn-accent">{{ $tl ? 'Mag-upgrade sa ' . $planName : 'Upgrade to ' . $planName }}</a>
                <a href="{{ route('fh.page', ['tool' => $tool]) }}" class="btn btn-outline">{{ $tl ? 'Buksan ang ' . $mod : 'Open the ' . $mod }}</a>
            </div>
            <p class="sp-gate-free">{{ $tl
                ? ($price ? $price . ' kada buwan. ' : '') . 'Naroon pa rin lahat ng nasa Libre mo, dagdag ang mga field helper at si Anee.'
                : ($price ? $price . ' a month. ' : '') . 'It keeps everything you have on Libre and adds the field helpers and Anee.' }}</p>
        </div>
    @else
        <div class="sp-sol-more" role="note">
            <svg fill="none" stroke="currentColor" stroke-width="2.2" viewBox="0 0 24 24" aria-hidden="true"><rect x="5" y="11" width="14" height="10" rx="2"/><path stroke-linecap="round" d="M8 11V8a4 4 0 018 0v3"/></svg>
            <span>{{ $tl ? 'Kasama ito sa ' . $planName . ', pati ang ' . $mod . '.' : ($page->section === 'nutrient-deficiencies' ? 'What to apply' : 'This part') . ' comes with ' . $planName . ', and so does the ' . $mod . '.' }}</span>
            <a href="{{ $buy }}">{{ $tl ? 'Mag-upgrade ›' : 'Upgrade ›' }}</a>
        </div>
    @endif
</div>
