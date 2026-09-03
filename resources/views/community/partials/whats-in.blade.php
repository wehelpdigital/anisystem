{{-- WHAT'S IN COMMUNITY? — the three rooms besides the wall, introduced.

     The schedules page's Global-and-Quick-Tools panel, worn by the plaza:
     one folding heading, then a door each for Discussions, the Tech Blog
     and Members — icon, a line saying why you would tap it, an arrow, and
     a soft gradient of its own hue. The fold is remembered per person. --}}
@once
<style>
    .cw-panel { border: 1px solid var(--color-gray-200); border-radius: 1rem;
        background: var(--color-white); overflow: hidden; margin-bottom: .9rem; }
    .cw-head { display: flex; align-items: center; gap: .7rem; width: 100%;
        text-align: left; padding: .7rem .8rem; cursor: pointer; background: none; border: 0; }
    .cw-head:hover { background: var(--color-gray-50); }
    .cw-head-ico { width: 2.4rem; height: 2.4rem; border-radius: .7rem; flex: none;
        display: inline-flex; align-items: center; justify-content: center;
        background: var(--color-brand-50); color: var(--color-brand-700); }
    .cw-head-ico svg { width: 1.25rem; height: 1.25rem; }
    .cw-head-txt { min-width: 0; flex: 1 1 auto; }
    .cw-head-txt b { display: block; font-size: .875rem; font-weight: 700; color: var(--color-gray-900); }
    .cw-head-txt i { display: block; font-style: normal; font-size: .75rem; color: var(--color-gray-500);
        overflow: hidden; text-overflow: ellipsis; white-space: nowrap; }
    .cw-chev { width: 1.1rem; height: 1.1rem; flex: none; color: var(--color-gray-400);
        transition: transform .28s cubic-bezier(.22,1,.36,1); }
    .cw-panel.is-folded .cw-chev { transform: rotate(-90deg); }
    .cw-fold { display: grid; grid-template-rows: 1fr;
        transition: grid-template-rows .28s cubic-bezier(.22,1,.36,1); }
    .cw-panel.is-folded .cw-fold { grid-template-rows: 0fr; }
    .cw-fold > div { overflow: hidden; min-height: 0; }
    .cw-stack { display: grid; gap: .5rem; padding: 0 .55rem .55rem; }
    .cw-tile { display: flex; align-items: center; gap: .7rem; width: 100%; text-align: left;
        padding: .7rem .8rem; border-radius: .9rem; text-decoration: none;
        border: 1px solid var(--color-gray-100);
        transition: transform .28s cubic-bezier(.22,1,.36,1), box-shadow .28s cubic-bezier(.22,1,.36,1); }
    .cw-tile:hover { transform: translateY(-1px); box-shadow: 0 10px 24px -16px rgb(0 0 0 / .5); }
    .cw-ico { width: 2.6rem; height: 2.6rem; border-radius: .75rem; flex: none;
        display: inline-flex; align-items: center; justify-content: center;
        background: rgb(255 255 255 / .75); }
    .cw-ico svg { width: 1.3rem; height: 1.3rem; }
    .cw-txt { display: flex; flex-direction: column; gap: .1rem; min-width: 0; flex: 1 1 auto; }
    .cw-txt b { font-size: .88rem; font-weight: 800; color: var(--color-gray-900); line-height: 1.25; }
    .cw-txt i { font-style: normal; font-size: .72rem; font-weight: 500; line-height: 1.4;
        color: var(--color-gray-600); }
    .cw-go { width: .95rem; height: .95rem; flex: none; color: var(--color-gray-400);
        transition: transform .28s cubic-bezier(.22,1,.36,1); }
    .cw-tile:hover .cw-go { transform: translateX(2px); }
    /* Each door wears a soft gradient of its own hue. */
    .cw-disc { background: linear-gradient(135deg, #eef6e6, #f8fbf3 65%); }
    .cw-disc .cw-ico { color: #3d6823; }
    .cw-blog { background: linear-gradient(135deg, #eaf1fd, #f7faff 65%); }
    .cw-blog .cw-ico { color: #1d4ed8; }
    .cw-mem  { background: linear-gradient(135deg, #fdf6e6, #fffaf0 65%); }
    .cw-mem .cw-ico { color: #b45309; }
    html.dark .cw-panel { background: #151b12; border-color: #2b3a1c; }
    html.dark .cw-head:hover { background: rgb(255 255 255 / .04); }
    html.dark .cw-head-txt b { color: #e8efe1; }
    html.dark .cw-head-txt i { color: #93a684; }
    html.dark .cw-head-ico { background: rgb(61 104 35 / .25); color: #a5c97e; }
    html.dark .cw-tile { border-color: #2b3a1c; }
    html.dark .cw-txt b { color: #e8efe1; }
    html.dark .cw-txt i { color: #93a684; }
    html.dark .cw-ico { background: rgb(0 0 0 / .25); }
    html.dark .cw-disc { background: linear-gradient(135deg, #1c2913, #151b12 70%); }
    html.dark .cw-disc .cw-ico { color: #a5c97e; }
    html.dark .cw-blog { background: linear-gradient(135deg, #16202f, #151b12 70%); }
    html.dark .cw-blog .cw-ico { color: #9fc0f5; }
    html.dark .cw-mem  { background: linear-gradient(135deg, #2a2212, #151b12 70%); }
    html.dark .cw-mem .cw-ico { color: #e0b457; }
    @media (prefers-reduced-motion: reduce) {
        .cw-fold, .cw-chev, .cw-tile, .cw-go { transition: none; }
    }
</style>
@endonce

<section class="cw-panel" id="whatsInCommunity">
    <button type="button" class="cw-head" id="whatsInHead" aria-expanded="true" aria-controls="whatsInBody">
        <span class="cw-head-ico"><svg fill="none" stroke="currentColor" stroke-width="1.8" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" d="M8 10h8M8 14h5m8-2a9 9 0 11-18 0 9 9 0 0118 0z"/></svg></span>
        <span class="cw-head-txt">
            <b>What's in Community?</b>
            <i>Besides the wall: the rooms, the reading, and the people.</i>
        </span>
        <svg class="cw-chev" fill="none" stroke="currentColor" stroke-width="2.4" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" d="M19 9l-7 7-7-7"/></svg>
    </button>
    <div class="cw-fold" id="whatsInBody">
        <div>
            <div class="cw-stack">
                <a href="{{ route('community.groups.page') }}" class="cw-tile cw-disc">
                    <span class="cw-ico"><svg fill="none" stroke="currentColor" stroke-width="1.8" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" d="M8 12h8m-8 4h5M21 12a8 8 0 01-11.6 7.1L4 21l1.9-5.4A8 8 0 1121 12z"/></svg></span>
                    <span class="cw-txt">
                        <b>Discussions</b>
                        <i>Rooms for one topic at a time — join one, or open your own.</i>
                    </span>
                    <svg class="cw-go" fill="none" stroke="currentColor" stroke-width="2.4" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" d="M9 5l7 7-7 7"/></svg>
                </a>
                <a href="{{ route('community.blog') }}" class="cw-tile cw-blog">
                    <span class="cw-ico"><svg fill="none" stroke="currentColor" stroke-width="1.8" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" d="M12 6.25c-2.1-1.6-4.8-1.85-8-1.1v13.6c3.2-.75 5.9-.5 8 1.1 2.1-1.6 4.8-1.85 8-1.1V5.15c-3.2-.75-5.9-.5-8 1.1zm0 0V19.5"/></svg></span>
                    <span class="cw-txt">
                        <b>Tech Blog</b>
                        <i>Growing guides and field science, written to be used.</i>
                    </span>
                    <svg class="cw-go" fill="none" stroke="currentColor" stroke-width="2.4" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" d="M9 5l7 7-7 7"/></svg>
                </a>
                <a href="{{ route('community.connect.members') }}" class="cw-tile cw-mem">
                    <span class="cw-ico"><svg fill="none" stroke="currentColor" stroke-width="1.8" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" d="M17 20h5v-1a4 4 0 00-4-4h-1M9 11a4 4 0 100-8 4 4 0 000 8zm8 0a3 3 0 100-6M2 20v-1a5 5 0 015-5h4a5 5 0 015 5v1H2z"/></svg></span>
                    <span class="cw-txt">
                        <b>Members</b>
                        <i>Every co-farmer here — find them, follow them, connect.</i>
                    </span>
                    <svg class="cw-go" fill="none" stroke="currentColor" stroke-width="2.4" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" d="M9 5l7 7-7 7"/></svg>
                </a>
            </div>
        </div>
    </div>
</section>

@once
<script>
(function whatsInFold() {
    const panel = document.getElementById('whatsInCommunity');
    const head = document.getElementById('whatsInHead');
    if (!panel || !head) return;
    const KEY = 'communityWhatsFolded:{{ auth()->id() }}';
    const paint = (folded) => {
        panel.classList.toggle('is-folded', folded);
        head.setAttribute('aria-expanded', folded ? 'false' : 'true');
    };
    let folded = false;
    try { folded = localStorage.getItem(KEY) === '1'; } catch (_) { /* opens unfolded */ }
    paint(folded);
    head.addEventListener('click', () => {
        folded = !panel.classList.contains('is-folded');
        paint(folded);
        try { localStorage.setItem(KEY, folded ? '1' : '0'); } catch (_) { /* not remembered */ }
    });
})();
</script>
@endonce
