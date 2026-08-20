{{-- "People you may know" — a band across the page, not a card on it.

     Fetched rather than rendered: the ranking walks friends-of-friends and
     the threads you have commented in, which is slower than a page should
     wait for. Skeleton cards hold the space so nothing jumps.

     Drawn on the wall and on the members page, so it lives here. --}}
<section class="pymk reco-edge" id="pymk" aria-label="People you may know">
    <div class="pymk-head">
        <h2>People you may know</h2>
        <a href="{{ route('community.connect.members') }}">See all</a>
    </div>
    <div class="pymk-rail" id="pymkRail">
        @for ($i = 0; $i < 4; $i++)
            <div class="pymk-skel" aria-hidden="true"></div>
        @endfor
    </div>
    <p class="pymk-empty hidden" id="pymkEmpty">No suggestions yet — connect with a few co-farmers and this fills up.</p>
</section>
