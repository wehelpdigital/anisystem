{{-- The members' gate (2026-10-14): the rest of a field guide, in the page
     for search engines and blurred for a guest, under an invitation to
     register for free. Needs $page and $gated (the blocks behind the gate,
     keys kept so their heading anchors match the contents). The words say
     what registering opens, never that a tool is free. See
     App\Support\ContentGate. --}}
@php
    $G = \App\Support\ContentGate::class;
    $tl = ($page->lang ?? '') === 'tl';
    $name = \App\Support\SitePages::shortTitle($page);
    // A pest, a disease, a weed or a variety by its plain name; null on a
    // guide about many of them.
    $subject = $G::subject($page);
    // The field helper guides come with Libre + Anee (2026-10-17, the owner); land preparation and
    // the crop guides still open on a free account.
    $plus = $G::needsPlus($page->section);
    // Since 2026-11-19 a free account reads the whole guide; only how to stop it (the sprays, what to apply) comes with Libre + Anee.
    $guide = 'Register for free to read the whole guide: ';
    [$title, $lead] = $tl ? ['Basahin ang buong gabay', null] : match ($page->section) {
        'crop-varieties', 'rice-varieties' => $subject
            ? ['See how ' . $subject . ' will do on your farm', 'Register for free to see its full record: how it yields, its strengths and weaknesses against pests, diseases and the weather, and whether it fits your farm, your season and your soil.']
            : ['See the varieties that fit your farm', 'Register for free to compare every variety, its strengths and weaknesses, and how it will do on your farm, in your season and your soil.'],
        'pests' => $subject
            ? ['Stop ' . $subject . ' from costing you the harvest', $guide . 'how to be sure it is this pest and the damage it does. How to stop it and what to spray come with Libre + Anee.']
            : ['Read the whole guide and protect your harvest', $guide . 'how to tell the pests apart and the damage they do. How to stop them and what to spray come with Libre + Anee.'],
        'diseases' => $subject
            ? ['Stop the spread of ' . $subject, $guide . 'how to tell it from look alikes and what makes it spread. How to control it and what to spray come with Libre + Anee.']
            : ['Read the whole guide and keep your crop healthy', $guide . 'how to tell the diseases apart and what makes them spread. How to control them and what to spray come with Libre + Anee.'],
        'weeds' => $subject
            ? ['Get ' . $subject . ' out of your field', $guide . 'how to tell it from your crop and why it hurts. What to do and spray at every age of your crop comes with Libre + Anee.']
            : ['Read the whole guide and keep your field clean', $guide . 'how to tell the weeds apart and why they hurt. What to do and spray at every age of your crop comes with Libre + Anee.'],
        'nutrient-deficiencies' => ['Find the missing nutrient and fix it', $guide . 'how to tell each deficiency from look alikes and how to be sure. What to apply and the fertilizers sold here come with Libre + Anee.'],
        'land-preparation' => ['Get every step of ' . \Illuminate\Support\Str::lower($name) . ' right', 'Register for free to read every step, then date each one on your own calendar with the Land Prep Planner, counted back from your planting day.'],
        'how-to-plant' => ['See every step of planting it the traditional way', 'Register for free to read every step with its day, the spacing, water and fertilizer, the common mistakes and what to change for a higher yield. Then send it to your Protocol Builder in one tap.'],
        'crops' => ['Read the whole guide to ' . $name, 'Register for free to read the whole guide, then plan your season in anee.io with every task dated from your own planting day.'],
        default => ['Read the whole guide', null],
    };
@endphp
@php
    // The real words behind the wall go only to a proven search or AI crawler (2026-11-16, the owner: "user can
    // easily read it"); a visitor gets the same shapes with the letters swapped, under the same blur.
    $real = \App\Support\Crawler::trusted();
    $shown = $real ? $gated : $G::veil($gated, $page->section . '/' . $page->slug);
@endphp
@include('public.site.partials.content-gate-assets')
<div class="sp-gate">
    <div class="sp-gated" id="spGated" @unless ($real) aria-hidden="true" translate="no" @endunless>
        @include('public.site.blocks', ['blocks' => $shown])
    </div>
    @include('public.site.partials.content-gate-card', [
        'tl' => $tl,
        'plus' => $plus,
        'here' => '/' . $page->section . '/' . $page->slug,
        'title' => $title,
        'lead' => $lead,
    ])
</div>
