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
        const medal = '<svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.1"'
            + ' stroke-linecap="round" stroke-linejoin="round">'
            + '<path d="M6 3h12"/><path d="M8 3v5a4 4 0 0 0 8 0V3"/><path d="M12 12v3"/>'
            + '<path d="M9 21h6"/><path d="M12 15a3 3 0 0 0-3 3v3h6v-3a3 3 0 0 0-3-3z"/></svg>';
        return '<a class="topb topb-' + seat.key + '" href="' + window.AS_RANK_URL + '#rankings"'
            + ' title="' + seat.name + ' · number ' + seat.place + ' in the community">'
            + '<span class="topb-m" aria-hidden="true">' + medal + '</span>'
            + '<span class="topb-n">#' + seat.place + '</span></a>';
    };
</script>
