{{-- A WALK FOR EVERY ROOM, not just the corridor.

     The hub's walk names each module in one line each, which tells you what
     the doors are. This is what is behind them: a short tour per module,
     pointing at the things in that module and saying what they are for.

     They live together in one file rather than one per module view for a
     reason that cost a round of debugging: a module is not a page here. It is
     a fragment the Activities shell fetches and swaps in, and a script pushed
     from inside a fragment never runs. The shell is the page, so the shell is
     where the steps have to be — all of them, keyed by module, registered
     once and started when the shell says which room you are standing in.

     A step whose target is not on screen is skipped, not pointed at. That is
     what carries these across tiers and grants: a farm without worker logins
     has no Grant access button, and the walk simply does not mention it. --}}
@if (($schedule->isDemo ?? false))
@push('scripts')
<script>
(() => {
    /* Two sentences per thing, and never more: a tour card people scroll is a
       tour card people close. Where a module's own words already say it, the
       step points and gets out of the way. */
    const T = {
        lots: [
            { title: 'Lots are the ground itself',
              body: 'Each block you farm, with its crop, its variety and the day it went in. This demo has two on purpose — rice by the river and corn up the hill.' },
            { target: '[data-lot-card], .card', title: 'One card per block',
              body: 'Size, crop and the sowing or planting date. That date is day zero, and every "DAS 24" you see on the board is counted from it.' },
            { title: 'Why two lots and not one',
              body: 'Rice counts its days from sowing and corn from planting, so the same board shows two different clocks. Change a lot here and the whole plan recounts.' },
        ],
        workers: [
            { title: 'Workers are who turns up',
              body: 'The roster for this season, and what each person costs for half a day. Mang Ben is the demo’s one hired hand.' },
            { target: '#grantAccessBtn, [data-worker-card], .card', title: 'A worker can have a login',
              body: 'Give someone an account and they see only this farm, only the modules you tick. Their own farm stays separate and stays theirs.' },
            { title: 'What the rate is for',
              body: 'Put somebody on a job and the day starts totalling what cash to bring. That is where the money on each day header comes from.' },
        ],
        inventory: [
            { title: 'Inventory is the shed',
              body: 'What you own and every movement in or out. On hand is not typed anywhere — it is the sum of the moves, so it cannot drift.' },
            { target: '#ivItemStartBtn, #ivEmpty, .card', title: 'Start with an item',
              body: 'Name the thing and its unit. After that you only ever record movements: bought, used, wasted, counted.' },
            { title: 'It joins up with the work',
              body: 'Spending stock on an activity comes out of here, and undoing that activity puts it back. The shed and the plan stay one story.' },
        ],
        notes: [
            { title: 'Notes are what you saw',
              body: 'Words, photos, a clip, a voice memo — whatever the field gave you. This is the module that keeps working with no signal.' },
            { target: '.note-card, #noteFoldAll, .card', title: 'Write one now if you like',
              body: 'A note can carry a picture, a drawing or a map, and anything tagged to it opens the note it belongs to rather than floating loose.' },
            { title: 'Out of signal it still writes',
              body: 'A note made in a dead zone waits on the phone and files itself when the signal comes back. Nothing you saw is lost to a bad line.' },
        ],
        growth: [
            { title: 'Growth stages read the crop',
              body: 'What the plant is doing at its current day count, per lot, and what it wants there — no table to memorise.' },
            { target: '.gr-card .gr-top, .gr-card, #grCards', title: 'A card per lot',
              body: 'Day 21 of rice is active tillering and wants its second nitrogen. The app says so instead of leaving you to remember.' },
        ],
        weather: [
            { title: 'Weather sits on the days',
              body: 'The forecast for this farm’s own place, landed on each day of the board rather than kept on a page of its own.' },
            { target: '#wxModuleHost .card, #wxModuleHost, .card', title: 'Why it matters on the board',
              body: 'A spray planned into the rain is obvious before anybody drives out, which is the only time noticing it is any use.' },
        ],
        settings: [
            { title: 'Settings is the season’s own shape',
              body: 'Its name, how it counts days, and who gets told what. Small screen, but everything else reads from it.' },
            { target: '.nt-picks, #saveNotifyBtn, .card', title: 'The daily word',
              body: 'The farm can send itself and its workers the day’s jobs each morning. Off by default — turn it on when the plan is real.' },
        ],
        tags: [
            { title: 'Tags are your own words',
              body: 'Not a fixed list. Coin a tag once and it is offered on every form in this season afterwards.' },
            { target: '#tgCloud, #tgShelf, #tgEmpty, .card', title: 'Everything wearing a tag',
              body: 'Tag activities, expenses and notes with the same word and this is where they all come back together.' },
        ],
        documentation: [
            { title: 'Documentation is the season’s record',
              body: 'Certificates, receipts, protocols, anything a buyer or an inspector might ask for. It belongs to the owner and no worker opens it.' },
            { target: '#docEmpty, #docFiles, .card', title: 'Files live with the season',
              body: 'Keeping them here rather than in a folder somewhere means they are still attached when you come back to this season next year.' },
        ],
        'post-harvest': [
            { title: 'Observations are what actually happened',
              body: 'The plan says what you meant to do. This says what the field did about it — and what came off at the end.' },
            { target: '#phAddTop, #phEmpty, .card', title: 'Write it while it is fresh',
              body: 'Yield, quality, what went wrong and what you would change. Next season this is the only honest thing to plan from.' },
        ],
        maps: [
            { title: 'Maps draw the ground',
              body: 'Trace a block, drop a pin, mark where the pump is. A map can attach to a lot so it opens with that block.' },
            { target: '#mpEmpty, #mpGrid, .card', title: 'Every map you have drawn',
              body: 'A map tagged onto a note or an activity opens the thing it belongs to, so it is never a picture with no context.' },
        ],
        draw: [
            { title: 'Draw is the back of an envelope',
              body: 'A sketch of a sprayer setup, a scribble of which corner flooded. Faster than words for the things words are slow at.' },
            { target: '#drEmpty, #drGrid, .card', title: 'Sketches keep their place',
              body: 'A drawing tagged to a note opens that note. Same rule as the maps — nothing here floats loose.' },
        ],
        media: [
            { title: 'The Gallery keeps every picture',
              body: 'Photos and clips from every day of this season, in albums, without hunting back through the board for them.' },
            { target: '#gaFind, .ga-tools, .ga-shelfbar, .card', title: 'Find one later',
              body: 'Search by what it was filed under. A picture taken on a day is still attached to that day when you come looking.' },
            /* A closing card with no target, so this walk is never one lonely
               step: the demo ships with no photos, so the step above it has
               nothing to point at and is skipped. */
            { title: 'It fills itself as you work',
              body: 'Every photo taken from a day on the board, from Quick Capture, or attached to a note lands here on its own. Nothing is filed twice.' },
        ],
    };
    T.gallery = T.media;

    const LABEL = {
        lots: 'Lots', workers: 'Workers', inventory: 'Inventory', notes: 'Notes',
        growth: 'Growth Stages', weather: 'Weather', settings: 'Settings', tags: 'Tags',
        documentation: 'Documentation', 'post-harvest': 'Observations', maps: 'Maps',
        draw: 'Draw', media: 'Gallery', gallery: 'Gallery',
    };

    Object.keys(T).forEach((key) => window.aneeTour?.register('demo:' + key, T[key]));

    /* The button that offers them.
     *
     * One button, re-labelled as the shell moves from room to room, rather
     * than one planted in each module's own markup — a module is a fragment
     * here, and a button pushed from a fragment is a button that is not there
     * when the fragment arrives. It hides itself in rooms with no tour
     * written, and on the board, whose tour is the hub's. */
    const btn = document.createElement('button');
    btn.type = 'button';
    btn.id = 'demoModuleTourBtn';
    btn.className = 'demo-mod-tour';
    btn.hidden = true;
    btn.innerHTML = '<svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">'
        + '<circle cx="12" cy="12" r="9"/><path stroke-linecap="round" stroke-linejoin="round" d="M9.6 9.4a2.5 2.5 0 014.9.6c0 1.2-1 1.7-1.8 2.2-.5.4-.7.8-.7 1.4M12 16.6h.01"/></svg>'
        + '<span></span>';
    document.body.appendChild(btn);

    function offer(key) {
        const has = !!T[key];
        btn.hidden = !has || window.aneeTour?.running?.();
        if (!has) return;
        btn.querySelector('span').textContent = 'Tour ' + (LABEL[key] || key);
        btn.dataset.mod = key;
    }

    document.addEventListener('sm:module-shown', (e) => offer(e.detail?.key));
    btn.addEventListener('click', () => {
        const key = btn.dataset.mod;
        if (key) { btn.hidden = true; window.aneeTour?.start('demo:' + key); }
    });
    // When a walk ends the offer comes back for whichever room is open.
    document.addEventListener('click', (e) => {
        if (!e.target.closest('[data-tour-stop], [data-tour-next]')) return;
        setTimeout(() => { if (!window.aneeTour?.running?.()) offer(btn.dataset.mod); }, 400);
    });
})();
</script>
@endpush

@push('head')
<style>
    /* Parked bottom-left: the board's own jump buttons own the right corner,
       and Anee owns the one above them. */
    .demo-mod-tour {
        position: fixed; left: .9rem; bottom: 1rem; z-index: 55;
        display: inline-flex; align-items: center; gap: .45rem;
        padding: .6rem .95rem; border-radius: 999px; cursor: pointer;
        font-size: .82rem; font-weight: 800; color: #fff;
        background: var(--color-brand-700, #3d6823);
        box-shadow: 0 10px 26px rgb(0 0 0 / .28);
        animation: app-pop-in .24s cubic-bezier(.22,1,.36,1) both;
        transition: transform .28s cubic-bezier(.22,1,.36,1), background .28s cubic-bezier(.22,1,.36,1);
    }
    .demo-mod-tour:hover { background: var(--color-brand-800, #2d5016); transform: translateY(-2px); }
    .demo-mod-tour:active { transform: translateY(0) scale(.97); }
    .demo-mod-tour svg { width: 1.05rem; height: 1.05rem; flex: none; }
    .demo-mod-tour[hidden] { display: none !important; }
    /* Clear of the phone's bottom bar and its home indicator. */
    @media (max-width: 767px) {
        .demo-mod-tour { bottom: calc(4.6rem + env(safe-area-inset-bottom, 0px)); }
    }
    @media (prefers-reduced-motion: reduce) {
        .demo-mod-tour { animation: none; transition: none; }
        .demo-mod-tour:hover { transform: none; }
    }
</style>
@endpush
@endif
