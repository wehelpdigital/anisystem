{{-- The members' gate (2026-10-14): the rest of a field guide, in the page
     for search engines and blurred for a guest, under an invitation to join
     anee.io free. Needs $page and $gated (the blocks behind the gate, keys
     kept so their heading anchors match the contents). See
     App\Support\ContentGate. --}}
@php
    $G = \App\Support\ContentGate::class;
    $tl = ($page->lang ?? '') === 'tl';
    $behind = $G::behind(array_values($gated), 0);
    $mins = max(1, (int) round($G::wordsFrom(array_values($gated), 0) / 200));
    $name = \App\Support\SitePages::shortTitle($page);
    $more = count($behind) - 5;
    $foot = ($more > 0 ? ($tl ? 'At ' . $more . ' pa' : 'And ' . $more . ' more ' . ($more === 1 ? 'section' : 'sections')) . ', ' : '')
        . ($tl ? 'mga ' . $mins . ' minutong babasahin' : 'about ' . $mins . ' more minutes of reading');
@endphp
@include('public.site.partials.content-gate-assets')
<div class="sp-gate">
    <div class="sp-gated" id="spGated">
        @include('public.site.blocks', ['blocks' => $gated])
    </div>
    @include('public.site.partials.content-gate-card', [
        'tl' => $tl,
        'here' => '/' . $page->section . '/' . $page->slug,
        'title' => $tl ? 'Basahin ang buong gabay, libre' : match ($page->section) {
            'crop-varieties', 'rice-varieties' => 'See the full record of ' . $name,
            'pests' => 'Read how to stop ' . $name,
            'diseases' => 'Read how to manage ' . $name,
            'weeds' => 'Read how to control ' . $name,
            'land-preparation' => 'Read every step of ' . \Illuminate\Support\Str::lower($name),
            default => 'Read the full guide, free',
        },
        'behind' => array_slice($behind, 0, 5),
        'foot' => $behind ? $foot : '',
    ])
</div>
