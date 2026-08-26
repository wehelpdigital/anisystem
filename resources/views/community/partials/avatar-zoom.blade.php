{{-- Tap a member's photo to actually see it.

     An avatar is drawn at 2.5rem and a face at 2.5rem is a guess. On the
     pages that include this, tapping any avatar that HAS a photo opens it
     large in the middle of the screen — still round, because that is the
     shape the face has everywhere else — over its own dimmed backdrop.
     Initial-letter avatars keep their old behaviour (usually a link to the
     profile): there is nothing bigger to show of two letters.

     Discussion rooms ride the same viewer: any face carrying data-gz-name
     (the groups list, the feed's discussion cards, the rail, the room's own
     header) opens with the room's facts instead of a member's.

     Delegated and guarded, so it works on cards that arrive by fetch and
     can be included by any page that draws members. --}}
@once
<style>
    /* The ground behind the face: a transparent dark-green tide, drifting. */
    .avz { position: fixed; inset: 0; z-index: 230; display: flex; flex-direction: column;
        align-items: center; justify-content: center; gap: .9rem;
        background: linear-gradient(135deg, rgb(12 24 5 / .86), rgb(38 66 18 / .74) 45%, rgb(8 18 4 / .88) 80%, rgb(28 48 14 / .8));
        background-size: 240% 240%;
        -webkit-backdrop-filter: blur(3px); backdrop-filter: blur(3px);
        opacity: 0; pointer-events: none; cursor: zoom-out;
        transition: opacity .28s cubic-bezier(.22, 1, .36, 1);
        animation: avzTide 10s ease-in-out infinite alternate; }
    @keyframes avzTide { from { background-position: 0% 30%; } to { background-position: 100% 70%; } }
    .avz.is-open { opacity: 1; pointer-events: auto; }
    .avz-picwrap { position: relative; display: inline-block; opacity: 1;
        transform: scale(.82);
        transition: transform .34s cubic-bezier(.22, 1, .36, 1), opacity .28s cubic-bezier(.22, 1, .36, 1); }
    .avz.is-open .avz-picwrap { transform: scale(1); }
    /* The same slow dashed ring the profile page's face wears. */
    .avz-picwrap::after { content: ''; position: absolute; inset: -9px; border-radius: 999px;
        border: 2.5px dashed #8fc267; pointer-events: none;
        animation: avzRingSpin 18s linear infinite, avzRingGlow 5s ease-in-out infinite alternate; }
    @keyframes avzRingSpin { to { transform: rotate(360deg); } }
    @keyframes avzRingGlow {
        from { border-color: #6b9f3d; filter: drop-shadow(0 0 2px rgb(107 159 61 / .4)); }
        to { border-color: #c4e0a5; filter: drop-shadow(0 0 6px rgb(169 211 131 / .6)); } }
    .avz-pic { display: block; width: min(70vw, 18rem); height: min(70vw, 18rem); border-radius: 999px;
        object-fit: cover; background: #202a1b;
        box-shadow: 0 30px 90px rgb(0 0 0 / .55), 0 0 0 4px rgb(255 255 255 / .18); }
    .avz-name { color: #fff; font-weight: 800; font-size: 1.05rem; letter-spacing: .01em;
        font-family: var(--font-heading); text-shadow: 0 1px 6px rgb(0 0 0 / .5); }
    /* Who — or what — this is, under the face: rank pill, the standing that
       matters here, the plain facts — each line optional, the column staying
       centred whatever is missing. */
    .avz-info { display: flex; flex-direction: column; align-items: center; gap: .45rem;
        max-width: min(86vw, 24rem); text-align: center; margin-top: -.25rem; opacity: 1;
        transition: opacity .28s cubic-bezier(.22, 1, .36, 1); }
    .avz-info .rankb { pointer-events: none; }
    .avz-facts { display: flex; flex-wrap: wrap; justify-content: center; gap: .35rem .9rem;
        color: rgb(255 255 255 / .85); font-size: .78rem; font-weight: 600;
        text-shadow: 0 1px 4px rgb(0 0 0 / .5); }
    .avz-counts { display: flex; flex-wrap: wrap; justify-content: center; gap: .35rem .9rem;
        color: rgb(255 255 255 / .9); font-size: .78rem; font-weight: 600;
        text-shadow: 0 1px 4px rgb(0 0 0 / .5); }
    .avz-counts b { font-weight: 800; color: #cfe8b5; }
    /* A room's one line about itself — plain words, no cloud around them. */
    .avz-desc { font-size: .8rem; font-style: italic; color: #eaf2e2; max-width: 100%;
        text-shadow: 0 1px 4px rgb(0 0 0 / .5); }
    /* Tag-like door to the profile, cut like the toolbar's bordered Search
       button but dressed for the dark. */
    .avz-visit { display: inline-flex; align-items: center; gap: .35rem;
        padding: .42rem 1.1rem; border-radius: 999px; cursor: pointer;
        border: 1.5px solid rgb(255 255 255 / .55); color: #fff;
        background: rgb(255 255 255 / .1); font-size: .82rem; font-weight: 700;
        text-decoration: none; margin-top: .15rem;
        transition: background .28s cubic-bezier(.22, 1, .36, 1), border-color .28s cubic-bezier(.22, 1, .36, 1); }
    .avz-visit:hover { background: rgb(255 255 255 / .22); border-color: #fff; }
    .avz-info > [hidden] { display: none; }
    /* While the picture and the numbers travel, the viewer is a loader:
       three dots where the face will be, everything else held back so it
       can all land at once. */
    .avz.is-loading .avz-picwrap, .avz.is-loading .avz-info { opacity: 0; }
    .avz-spin { position: absolute; inset: 0; display: flex; align-items: center;
        justify-content: center; gap: .4rem; pointer-events: none; opacity: 0;
        transition: opacity .28s cubic-bezier(.22, 1, .36, 1); }
    .avz.is-loading .avz-spin { opacity: 1; }
    .avz-spin i { display: block; width: .5rem; height: .5rem; border-radius: 999px;
        background: #cfe8b5; animation: avzDot 1s cubic-bezier(.22, 1, .36, 1) infinite; }
    .avz-spin i:nth-child(2) { animation-delay: .12s; }
    .avz-spin i:nth-child(3) { animation-delay: .24s; }
    @keyframes avzDot { 0%, 100% { opacity: .25; transform: translateY(0); } 50% { opacity: 1; transform: translateY(-.3rem); } }
    .avz-x { position: absolute; top: max(1rem, env(safe-area-inset-top)); right: 1rem;
        width: 2.5rem; height: 2.5rem; border: 0; border-radius: 999px; cursor: pointer;
        background: rgb(255 255 255 / .14); color: #fff; font-size: 1.05rem;
        display: inline-flex; align-items: center; justify-content: center;
        transition: background .28s cubic-bezier(.22, 1, .36, 1); }
    .avz-x:hover { background: rgb(255 255 255 / .26); }
    @media (prefers-reduced-motion: reduce) {
        .avz, .avz-picwrap, .avz-info, .avz-spin { transition-duration: .01s; animation: none; }
        .avz-picwrap::after { animation: none; }
        /* A loader that stops looks like a page that broke; slow it instead. */
        .avz-spin i { animation-duration: 2.6s; } }
</style>
<div class="avz" id="avatarZoom" role="dialog" aria-modal="true" aria-label="Profile photo">
    <button type="button" class="avz-x" aria-label="Close">✕</button>
    <div class="avz-spin" id="avatarZoomSpin" role="status" aria-label="Loading"><i></i><i></i><i></i></div>
    <span class="avz-picwrap"><img class="avz-pic" id="avatarZoomPic" src="" alt=""></span>
    <div class="avz-info">
        <p class="avz-name" id="avatarZoomName"></p>
        <span class="rankb" id="avatarZoomRank" hidden></span>
        <p class="avz-counts" id="avatarZoomCounts" hidden></p>
        <p class="avz-facts" id="avatarZoomFacts" hidden></p>
        <p class="avz-desc" id="avatarZoomDesc" hidden></p>
        <a class="avz-visit" id="avatarZoomVisit" href="#" hidden>Visit Profile</a>
    </div>
</div>
<script>
(function () {
    if (window.__avatarZoomBound) return;
    window.__avatarZoomBound = true;

    const box = document.getElementById('avatarZoom');
    const pic = document.getElementById('avatarZoomPic');
    const name = document.getElementById('avatarZoomName');
    let openSeq = 0;

    function close() {
        openSeq++;   // a slow answer for a closed viewer stays unsaid
        box.classList.remove('is-open', 'is-loading');
        document.removeEventListener('keydown', onKey);
    }
    function onKey(e) { if (e.key === 'Escape') close(); }

    document.addEventListener('click', (e) => {
        // Anywhere on the open viewer closes it — it is a look, not a place.
        // Except the one door it offers: Visit Profile really goes there.
        if (box.classList.contains('is-open') && e.target.closest('#avatarZoom')) {
            if (e.target.closest('.avz-visit')) return;
            e.preventDefault(); close(); return;
        }
        // Members' faces and discussion rooms' faces open the same viewer;
        // a room announces itself with data-gz-name wherever it is drawn.
        const face = e.target.closest('.avatar, [data-gz-name]');
        if (!face) return;
        const isRoom = face.hasAttribute('data-gz-name');
        const img = face.querySelector('img');
        if (!img || !img.getAttribute('src')) return;   // letters stay letters
        // The photo outranks the link the avatar may be wrapped in: the name
        // beside it still goes to the profile, the face shows the face.
        e.preventDefault();
        e.stopPropagation();
        const seq = ++openSeq;
        /* Sized to what the file can honestly fill. A 512px avatar drawn at
         * 670 device pixels is mush; the circle stops at the source's short
         * side in device pixels — one file pixel per screen pixel, no grace —
         * and never below a readable floor. Sharp-and-smaller beats
         * big-and-blurry. */
        pic.style.width = pic.style.height = '';
        const picReady = new Promise((res) => {
            pic.onload = () => {
                const dpr = window.devicePixelRatio || 1;
                const native = Math.min(pic.naturalWidth || 0, pic.naturalHeight || 0);
                if (native) {
                    const cap = Math.min(288, window.innerWidth * 0.7);
                    const size = Math.max(160, Math.min(cap, native / dpr));
                    pic.style.width = pic.style.height = Math.round(size) + 'px';
                }
                res();
            };
            pic.onerror = () => res();
        });
        pic.src = img.currentSrc || img.src;
        if (pic.complete && pic.naturalWidth) pic.onload();
        pic.alt = img.alt || '';
        name.textContent = isRoom
            ? (face.getAttribute('data-gz-name') || '')
            : (img.alt || face.getAttribute('title') || '');
        /* The rest of who — or what — this is, read off the face itself (the
         * partials write these attributes wherever a photo is drawn). For a
         * member: the rank pill in its arc's colours and where they farm.
         * For a room: its numbers and what it is about. Each line simply
         * stays away when there is nothing to say. */
        const rank = document.getElementById('avatarZoomRank');
        const facts = document.getElementById('avatarZoomFacts');
        const desc = document.getElementById('avatarZoomDesc');
        const rankText = isRoom ? '' : (face.getAttribute('data-z-rank') || '');
        rank.hidden = !rankText;
        rank.textContent = rankText;
        rank.className = 'rankb rankb-a' + (face.getAttribute('data-z-arc') || '1');
        const bits = [];
        if (isRoom) {
            const mN = Number(face.getAttribute('data-gz-members')) || 0;
            const tN = Number(face.getAttribute('data-gz-topics')) || 0;
            const rN = Number(face.getAttribute('data-gz-replies')) || 0;
            if (mN > 0) bits.push('🧑‍🌾 ' + mN + (mN === 1 ? ' member' : ' members'));
            if (tN > 0) bits.push('💬 ' + tN + (tN === 1 ? ' topic' : ' topics'));
            if (rN > 0) bits.push('↩ ' + rN + (rN === 1 ? ' reply' : ' replies'));
        } else if (face.getAttribute('data-z-place')) {
            bits.push('📍 ' + face.getAttribute('data-z-place'));
        }
        facts.hidden = !bits.length;
        facts.innerHTML = '';
        bits.forEach((b) => { const s = document.createElement('span'); s.textContent = b; facts.appendChild(s); });
        const about = isRoom ? (face.getAttribute('data-gz-desc') || '') : '';
        desc.hidden = !about;
        desc.textContent = about;
        /* The standing they hold — mutual co-farmers, co-farmers, followers —
         * fetched fresh on open (counts drift too much to bake into every
         * card) and shown only where a number means something: zeroes stay
         * silent, "mutual" never describes yourself. */
        const counts = document.getElementById('avatarZoomCounts');
        const visit = document.getElementById('avatarZoomVisit');
        const zId = isRoom ? '' : (face.getAttribute('data-z-id') || '');
        if (isRoom) {
            const roomUrl = face.getAttribute('data-gz-url') || '';
            visit.hidden = !roomUrl;   // the room's own page offers no door to itself
            visit.href = roomUrl || '#';
            visit.textContent = 'Open Discussion';
        } else {
            visit.hidden = !zId;
            visit.textContent = 'Visit Profile';
            if (zId) visit.href = @json(url('/app/community/members')) + '/' + zId;
        }
        counts.hidden = true;
        counts.innerHTML = '';
        let glance = null;
        const fillCounts = () => {
            if (!glance || seq !== openSeq) return;
            const rows = [];
            if (glance.mutual > 0) rows.push([glance.mutual, 'mutual co-farmer']);
            if (glance.coFarmers > 0) rows.push([glance.coFarmers, 'co-farmer']);
            if (glance.followers > 0) rows.push([glance.followers, 'follower']);
            counts.innerHTML = '';
            rows.forEach(([n, word]) => {
                const s = document.createElement('span');
                const b = document.createElement('b');
                b.textContent = n;
                s.appendChild(b);
                s.appendChild(document.createTextNode(' ' + word + (n === 1 ? '' : 's')));
                counts.appendChild(s);
            });
            counts.hidden = !rows.length;
        };
        const dataReady = !zId ? Promise.resolve() :
            fetch(@json(route('community.glance')) + '?userId=' + encodeURIComponent(zId), {
                headers: { 'Accept': 'application/json' }, credentials: 'same-origin'
            }).then((r) => r.ok ? r.json() : null)
              .then((j) => { glance = j && j.data ? j.data : j; fillCounts(); })
              .catch(() => {});
        /* The viewer opens as a loader and everything — face, name, numbers —
         * lands together once both the picture and the counts have arrived.
         * A stuck network unblocks after 4s with whatever made it. */
        box.classList.add('is-open', 'is-loading');
        document.addEventListener('keydown', onKey);
        Promise.race([
            Promise.all([picReady, dataReady]),
            new Promise((res) => setTimeout(res, 4000)),
        ]).then(() => {
            if (seq !== openSeq || !box.classList.contains('is-open')) return;
            box.classList.remove('is-loading');
        });
    });
})();
</script>
@endonce
