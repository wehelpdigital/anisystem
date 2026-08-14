{{-- One line saying how this module fits with the others.

     Notes, Drawings, Maps and the Gallery all hold pieces of the same
     season, and a grower who has just found the same picture in two places
     is right to wonder which one is the real one. The answer is always the
     same — the record is the note, everything else is a view onto it — and
     saying so once per module costs a line and saves the question.

     It is also a line you only need to read once. Put it away and it folds
     up into the info button beside the bell, which brings it back; what you
     put away stays away, per module, on this device.

     Expects $say (the sentence) and optionally $sayLink = ['label' => …,
     'href' => …] and $sayKey (which module this is — defaults to the path). --}}
@once
    @push('head')
        <style>
            .mod-say-wrap { display: grid; grid-template-rows: 1fr; margin-bottom: .85rem;
                transition: grid-template-rows .28s cubic-bezier(.22,1,.36,1),
                    opacity .28s cubic-bezier(.22,1,.36,1), margin-bottom .28s cubic-bezier(.22,1,.36,1); }
            .mod-say-wrap.is-away { grid-template-rows: 0fr; opacity: 0; margin-bottom: 0; }
            .mod-say-inner { overflow: hidden; min-height: 0; }
            .mod-say { display: flex; align-items: flex-start; gap: .5rem;
                padding: .6rem .75rem; border-radius: .8rem; font-size: .78rem; line-height: 1.5;
                color: #4a6b34; background: #f3f8ec; border: 1px solid #d9e8c4; }
            .mod-say > svg { width: .95rem; height: .95rem; flex: none; margin-top: .1rem; }
            .mod-say a { font-weight: 700; text-decoration: underline; }
            .mod-say-x { flex: none; width: 1.35rem; height: 1.35rem; margin: -.1rem -.2rem 0 auto;
                border-radius: 999px; display: inline-flex; align-items: center; justify-content: center;
                color: #6b8f4d; cursor: pointer; transition: background .2s ease, color .2s ease; }
            .mod-say-x:hover { background: #e4efd4; color: #2c4d18; }
            .mod-say-x svg { width: .85rem; height: .85rem; }
            html.dark .mod-say { background: rgb(107 159 61 / .12); border-color: #2b3a1c; color: #a8bd93; }
            html.dark .mod-say-x:hover { background: rgb(107 159 61 / .25); color: #cdd8c0; }
            @media (prefers-reduced-motion: reduce) { .mod-say-wrap { transition: none; } }
        </style>
    @endpush
    @push('scripts')
        <script>
        (() => {
            /* This block ships once per render, and the shell renders each
               module on its own — so without the flag the listeners below
               would be bound again for every module opened. */
            if (window.__modSayBooted) { document.dispatchEvent(new CustomEvent('sm:module-shown')); return; }
            window.__modSayBooted = true;

            /* The line and the header button are two states of one thing. */
            const btn = document.getElementById('modSayBtn');
            const key = (el) => 'modSay:' + (el.getAttribute('data-say-key') || 'x');
            const wraps = () => Array.from(document.querySelectorAll('.mod-say-wrap'));
            /* The one belonging to the module that has the screen.
             *
             * Not "the one that is visible": a module can hide its own line
             * for reasons of its own — Maps tucks it away while the map stage
             * is open — and the header button must still speak for THAT
             * module, not for whichever pane happened to load first. */
            const live = () => {
                const all = wraps();
                const onPane = all.find((w) => {
                    const pane = w.closest('[data-module]');
                    return pane ? !pane.classList.contains('module-hidden') : true;
                });
                return onPane || all.find((w) => w.offsetParent !== null) || all[0];
            };

            const remember = (el, away) => {
                try { localStorage.setItem(key(el), away ? '1' : '0'); } catch (_) { /* private mode */ }
            };
            const isAway = (el) => {
                try { return localStorage.getItem(key(el)) === '1'; } catch (_) { return false; }
            };

            function sync() {
                const el = live();
                if (!btn) return;
                btn.hidden = !el || !el.classList.contains('is-away');
            }

            function apply() {
                wraps().forEach((el) => {
                    if (el.dataset.saySet === '1') return;
                    el.dataset.saySet = '1';
                    // Restoring is not a change: no fold animation on arrival.
                    el.style.transition = 'none';
                    el.classList.toggle('is-away', isAway(el));
                    void el.offsetWidth;
                    el.style.transition = '';
                });
                sync();
            }

            document.addEventListener('click', (e) => {
                const x = e.target.closest('.mod-say-x');
                if (x) {
                    const el = x.closest('.mod-say-wrap');
                    el.classList.add('is-away');
                    remember(el, true);
                    setTimeout(sync, 260);
                    return;
                }
                if (e.target.closest('#modSayBtn')) {
                    const el = live();
                    if (!el) return;
                    el.classList.remove('is-away');
                    remember(el, false);
                    sync();
                }
            });

            if (document.readyState === 'loading') document.addEventListener('DOMContentLoaded', apply);
            else apply();
            // Modules are swapped in without a page load.
            document.addEventListener('sm:module-shown', () => setTimeout(apply, 30));
        })();
        </script>
    @endpush
@endonce

<div class="mod-say-wrap" data-say-key="{{ $sayKey ?? request()->path() }}">
    <div class="mod-say-inner">
        <p class="mod-say">
            <svg fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24"><circle cx="12" cy="12" r="9"/><path stroke-linecap="round" stroke-linejoin="round" d="M12 11v5m0-8h.01"/></svg>
            <span>{{ $say }}@if (! empty($sayLink)) <a href="{{ $sayLink['href'] }}">{{ $sayLink['label'] }}</a>@endif</span>
            <button type="button" class="mod-say-x" title="Put this away" aria-label="Put this away">
                <svg fill="none" stroke="currentColor" stroke-width="2.5" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" d="M6 6l12 12M18 6L6 18"/></svg>
            </button>
        </p>
    </div>
</div>
