{{-- The podium, handed to the browser.

     Cards on these pages are drawn twice — once by Blade on first paint and
     once by JavaScript on every render after — and a chip that only the Blade
     half knows about vanishes the moment a comment is posted or a list is
     re-drawn. So the whole podium travels with the page: twenty rows, which
     is smaller than one avatar, and the JS half can answer the same question
     the Blade half answers without a round trip.

     Emits window.topBadge(userId) → HTML string (empty for the unplaced),
     matching community/partials/top-badge.blade.php. Keep the two in step. --}}
<script>
    {{-- Cast to an object: a podium of one, seated at user 0, would encode as
         a JSON array and lose the id the lookup is keyed on. --}}
    window.AS_PODIUM = @json((object) \App\Support\CommunityRank::podiumChips());
    window.AS_RANK_URL = @json(route('community.ranking'));
    window.topBadge = function (userId) {
        const seat = window.AS_PODIUM[String(userId ?? '')];
        if (!seat) return '';
        // The medal is painted by .topb-m itself, so this writes the slot and
        // nothing else. It used to build its own — a trophy, while the Blade
        // partial built a medal, which is what two copies of one drawing do.
        return '<a class="topb topb-' + seat.key + '" href="' + window.AS_RANK_URL + '#rankings"'
            + ' title="' + seat.name + ' · number ' + seat.place + ' in the community">'
            + '<span class="topb-m" aria-hidden="true"></span>'
            + '<span class="topb-n">#' + seat.place + '</span></a>';
    };
</script>
