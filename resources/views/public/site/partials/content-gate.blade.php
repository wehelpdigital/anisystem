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
    $guide = 'With Libre + Anee, read the whole guide: ';
    [$title, $lead] = $tl ? ['Basahin ang buong gabay', null] : match ($page->section) {
        'crop-varieties', 'rice-varieties' => $subject
            ? ['See how ' . $subject . ' will do on your farm', 'With Libre + Anee, see its full record: how it yields, its strengths and weaknesses against pests, diseases and the weather, and whether it fits your farm, your season and your soil.']
            : ['See the varieties that fit your farm', 'With Libre + Anee, compare every variety, its strengths and weaknesses, and how it will do on your farm, in your season and your soil.'],
        'pests' => $subject
            ? ['Stop ' . $subject . ' from costing you the harvest', $guide . 'how to be sure it is this pest, when it pays to act, and what to spray, by active ingredient.']
            : ['Read the whole guide and protect your harvest', $guide . 'how to tell the pests apart, when it pays to act, and what to spray, by active ingredient.'],
        'diseases' => $subject
            ? ['Stop the spread of ' . $subject, $guide . 'how to tell it from look alikes, what makes it spread, and what to spray and when.']
            : ['Read the whole guide and keep your crop healthy', $guide . 'how to tell the diseases apart, what makes them spread, and what to spray and when.'],
        'weeds' => $subject
            ? ['Get ' . $subject . ' out of your field', $guide . 'how to tell it from your crop, and what to do and spray at every age of your crop.']
            : ['Read the whole guide and keep your field clean', $guide . 'how to tell the weeds apart, and what to do and spray at every age of your crop.'],
        'nutrient-deficiencies' => ['Find the missing nutrient and fix it', $guide . 'how to tell the deficiency from look alikes, how to be sure, what to apply, and the fertilizers sold here that supply it.'],
        'land-preparation' => ['Get every step of ' . \Illuminate\Support\Str::lower($name) . ' right', 'Register for free to read every step, then date each one on your own calendar with the Land Prep Planner, counted back from your planting day.'],
        'crops' => ['Read the whole guide to ' . $name, 'Register for free to read the whole guide, then plan your season in anee.io with every task dated from your own planting day.'],
        default => ['Read the whole guide', null],
    };
@endphp
@include('public.site.partials.content-gate-assets')
<div class="sp-gate">
    <div class="sp-gated" id="spGated">
        @include('public.site.blocks', ['blocks' => $gated])
    </div>
    @include('public.site.partials.content-gate-card', [
        'tl' => $tl,
        'plus' => $plus,
        'here' => '/' . $page->section . '/' . $page->slug,
        'title' => $title,
        'lead' => $lead,
    ])
</div>
