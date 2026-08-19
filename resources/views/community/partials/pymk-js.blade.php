@once
{{-- The suggestion strip fills itself.

     Rendered with the page it would have held the wall up behind a ranking
     that walks every connection of every connection; asked for afterwards, the
     wall paints immediately and the strip catches up under its own skeleton. --}}
<script>
(function pymk() {
    const rail = document.getElementById('pymkRail');
    if (!rail || rail.dataset.booted) return;
    rail.dataset.booted = '1';

    const wrap = document.getElementById('pymk');
    const empty = document.getElementById('pymkEmpty');

    fetch(@json(route('community.suggestions')), {
        headers: { Accept: 'application/json' },
        credentials: 'same-origin',
    })
        .then((r) => r.json())
        .then((j) => {
            const html = (j.data && j.data.html) || '';
            rail.innerHTML = html;
            if (!rail.children.length) {
                // Nothing to suggest is not an error, but an empty rail of
                // shimmering ghosts would look like one.
                rail.remove();
                empty?.classList.remove('hidden');
            }
        })
        .catch(() => {
            // A strip that cannot load is furniture nobody asked for: it goes.
            wrap?.remove();
        });
})();
</script>
@endonce
