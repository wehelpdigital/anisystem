{{-- What a wait looks like: a little scene from the farm, drawn rather than
     spun, and something to read that is not the word "Loading".

     Expects: $waitPool — rows of ['line' => …, 'scene' => …] from
     AsLoadingLine::pool(). The first is drawn server-side so the very first
     paint already says something; the rest ride along so the browser can
     re-roll without another round trip.

     Every scene is in the DOM and all but one is display:none. A hidden
     element's animations do not run, so nine sleeping farms cost nothing, and
     switching lines is a class toggle rather than a build. --}}
@php
    $waitPool = $waitPool ?? [['line' => 'Working out the day…', 'scene' => 'seedling']];
    $waitFirst = $waitPool[0];
    $waitScene = in_array($waitFirst['scene'], \App\Models\AsLoadingLine::SCENES, true)
        ? $waitFirst['scene']
        : 'seedling';
@endphp
<div class="bv-card">
    <span class="bv-scene" aria-hidden="true">
        {{-- The hens. The shell rocks, the crack opens, and a beak comes
             through it — the whole joke of "preparing the chicken eggs". --}}
        <span class="bv-s bv-egg {{ $waitScene === 'egg' ? 'is-on' : '' }}" data-scene="egg">
            <svg viewBox="0 0 56 56" fill="none">
                <g class="egg-body">
                    <path d="M28 8c8 0 14 12 14 22a14 14 0 0 1-28 0c0-10 6-22 14-22z" fill="#fdf6e3" stroke="#c9b48a" stroke-width="2"/>
                    <path class="egg-crack" d="M15 28l5-3 4 4 5-5 4 4 5-4 3 3" stroke="#c9b48a" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"/>
                    <path class="egg-beak" d="M25 22l3 3-3 3z" fill="#f0b429"/>
                </g>
            </svg>
        </span>
        {{-- The seedbed: the stem draws itself, then the leaves open. --}}
        <span class="bv-s bv-seedling {{ $waitScene === 'seedling' ? 'is-on' : '' }}" data-scene="seedling">
            <svg viewBox="0 0 56 56" fill="none">
                <path d="M8 44h40" stroke="#8a5a2b" stroke-width="3" stroke-linecap="round"/>
                <path class="sd-stem" d="M28 44V22" stroke="#4a7c2a" stroke-width="3" stroke-linecap="round"/>
                <path class="sd-leaf sd-leaf-l" d="M28 30c-8 0-11-4-11-8 5-1 11 2 11 8z" fill="#6b9f3d"/>
                <path class="sd-leaf sd-leaf-r" d="M28 26c8 0 11-4 11-8-5-1-11 2-11 8z" fill="#8fc267"/>
            </svg>
        </span>
        {{-- The machine: wheels turn, the body bounces on the ruts, and the
             exhaust goes up in puffs. --}}
        <span class="bv-s bv-tractor {{ $waitScene === 'tractor' ? 'is-on' : '' }}" data-scene="tractor">
            <svg viewBox="0 0 56 56" fill="none">
                <g class="tr-body">
                    <path d="M14 34V24h9l3 8h10v2" stroke="#4a7c2a" stroke-width="3" stroke-linecap="round" stroke-linejoin="round"/>
                    <path d="M22 24v-6h6v6" stroke="#4a7c2a" stroke-width="3" stroke-linecap="round" stroke-linejoin="round"/>
                    <path class="tr-puff tr-puff-1" d="M17 18v-3" stroke="#b7c4a8" stroke-width="3" stroke-linecap="round"/>
                    <path class="tr-puff tr-puff-2" d="M17 12V9" stroke="#b7c4a8" stroke-width="3" stroke-linecap="round"/>
                </g>
                <g class="tr-wheel tr-wheel-big">
                    <circle cx="19" cy="38" r="8" stroke="#3d6823" stroke-width="3"/>
                    <path d="M19 32v12M13 38h12" stroke="#3d6823" stroke-width="2"/>
                </g>
                <g class="tr-wheel tr-wheel-small">
                    <circle cx="39" cy="40" r="5" stroke="#3d6823" stroke-width="3"/>
                    <path d="M39 36v8M35 40h8" stroke="#3d6823" stroke-width="2"/>
                </g>
            </svg>
        </span>
        {{-- The sky, meaning it. --}}
        <span class="bv-s bv-rain {{ $waitScene === 'rain' ? 'is-on' : '' }}" data-scene="rain">
            <svg viewBox="0 0 56 56" fill="none">
                <path class="rn-cloud" d="M17 30a7 7 0 0 1 1-13.9A10 10 0 0 1 37 18a6.5 6.5 0 0 1 1 13H17z" fill="#cfd8e3" stroke="#9aa8bb" stroke-width="2"/>
                <path class="rn-drop rn-1" d="M20 36v5" stroke="#5b9bd5" stroke-width="3" stroke-linecap="round"/>
                <path class="rn-drop rn-2" d="M28 36v5" stroke="#5b9bd5" stroke-width="3" stroke-linecap="round"/>
                <path class="rn-drop rn-3" d="M36 36v5" stroke="#5b9bd5" stroke-width="3" stroke-linecap="round"/>
            </svg>
        </span>
        {{-- The sun, taking its time over the trees. --}}
        <span class="bv-s bv-sun {{ $waitScene === 'sun' ? 'is-on' : '' }}" data-scene="sun">
            <svg viewBox="0 0 56 56" fill="none">
                <g class="sn-rays">
                    <path d="M28 6v6M28 44v6M6 28h6M44 28h6M12.4 12.4l4.2 4.2M39.4 39.4l4.2 4.2M43.6 12.4l-4.2 4.2M16.6 39.4l-4.2 4.2"
                          stroke="#f0b429" stroke-width="3" stroke-linecap="round"/>
                </g>
                <circle class="sn-core" cx="28" cy="28" r="9" fill="#fbbf24" stroke="#e09b13" stroke-width="2"/>
            </svg>
        </span>
        {{-- Five more minutes. The head dips, an ear flicks. --}}
        <span class="bv-s bv-carabao {{ $waitScene === 'carabao' ? 'is-on' : '' }}" data-scene="carabao">
            <svg viewBox="0 0 56 56" fill="none">
                <g class="cb-head">
                    {{-- The horns sweep out and back the way a carabao's do —
                         wide, low and curving up at the tips. Short stubs at
                         the crown read as ears, which is a different animal. --}}
                    <path d="M20 18C13 20 7 18 4 11" stroke="#7c828b" stroke-width="4" stroke-linecap="round" fill="none"/>
                    <path d="M36 18c7 2 13 0 16-7" stroke="#7c828b" stroke-width="4" stroke-linecap="round" fill="none"/>
                    <path d="M20 16h16a6 6 0 0 1 6 6v6c0 8-6 13-14 13s-14-5-14-13v-6a6 6 0 0 1 6-6z" fill="#8b8f95" stroke="#5f636a" stroke-width="2"/>
                    <circle cx="23" cy="26" r="2" fill="#33383f"/>
                    <circle cx="33" cy="26" r="2" fill="#33383f"/>
                    {{-- The muzzle, paler than the head, with the nostrils in
                         it: the one feature that makes the shape a face. --}}
                    <ellipse cx="28" cy="35" rx="8" ry="5.5" fill="#a7acb3" stroke="#5f636a" stroke-width="1.6"/>
                    <g class="cb-nose">
                        <circle cx="25" cy="35" r="1.3" fill="#4a4f56"/>
                        <circle cx="31" cy="35" r="1.3" fill="#4a4f56"/>
                    </g>
                </g>
            </svg>
        </span>
        {{-- A head of rice, bending the way a full one does. --}}
        <span class="bv-s bv-rice {{ $waitScene === 'rice' ? 'is-on' : '' }}" data-scene="rice">
            <svg viewBox="0 0 56 56" fill="none">
                <g class="rc-stalk">
                    <path d="M28 48c0-14 2-22 6-28" stroke="#6b9f3d" stroke-width="3" stroke-linecap="round"/>
                    <g fill="#d9b23c">
                        <ellipse cx="36" cy="17" rx="2.6" ry="4.4" transform="rotate(24 36 17)"/>
                        <ellipse cx="31" cy="23" rx="2.6" ry="4.4" transform="rotate(24 31 23)"/>
                        <ellipse cx="39" cy="24" rx="2.6" ry="4.4" transform="rotate(24 39 24)"/>
                        <ellipse cx="33" cy="31" rx="2.6" ry="4.4" transform="rotate(24 33 31)"/>
                    </g>
                </g>
                <path d="M10 48h36" stroke="#8a5a2b" stroke-width="3" stroke-linecap="round"/>
            </svg>
        </span>
        {{-- The can tips, and the water comes. --}}
        <span class="bv-s bv-watering {{ $waitScene === 'watering' ? 'is-on' : '' }}" data-scene="watering">
            <svg viewBox="0 0 56 56" fill="none">
                <g class="wt-can">
                    <path d="M20 22h16v14a4 4 0 0 1-4 4H24a4 4 0 0 1-4-4V22z" fill="#8fc267" stroke="#3d6823" stroke-width="2"/>
                    <path d="M24 22v-4h8v4" stroke="#3d6823" stroke-width="2.5" stroke-linecap="round"/>
                    <path d="M20 26l-9 4" stroke="#3d6823" stroke-width="3" stroke-linecap="round"/>
                </g>
                <path class="wt-drop wt-1" d="M10 34v4" stroke="#5b9bd5" stroke-width="3" stroke-linecap="round"/>
                <path class="wt-drop wt-2" d="M14 36v4" stroke="#5b9bd5" stroke-width="3" stroke-linecap="round"/>
            </svg>
        </span>
        {{-- One bee, going about its business. --}}
        <span class="bv-s bv-bee {{ $waitScene === 'bee' ? 'is-on' : '' }}" data-scene="bee">
            <svg viewBox="0 0 56 56" fill="none">
                {{-- Wings above the body, not behind it: drawn at the body's
                     own height they were painted over by it, and a bee with
                     no wings is a striped barrel. --}}
                <g class="be-fly">
                    <ellipse class="be-wing be-wing-l" cx="21" cy="20" rx="7" ry="4" transform="rotate(-22 21 20)" fill="#dbeafe" stroke="#8fb3d4" stroke-width="1.5"/>
                    <ellipse class="be-wing be-wing-r" cx="35" cy="20" rx="7" ry="4" transform="rotate(22 35 20)" fill="#dbeafe" stroke="#8fb3d4" stroke-width="1.5"/>
                    <ellipse cx="28" cy="33" rx="10" ry="7.5" fill="#f0b429" stroke="#8a5a2b" stroke-width="2"/>
                    <path d="M25 26.4v13.2M30.5 26.4v13.2" stroke="#8a5a2b" stroke-width="2.6"/>
                    <path d="M23 25l-2-4M33 25l2-4" stroke="#8a5a2b" stroke-width="1.8" stroke-linecap="round"/>
                </g>
            </svg>
        </span>
        {{-- The night shift. --}}
        <span class="bv-s bv-moon {{ $waitScene === 'moon' ? 'is-on' : '' }}" data-scene="moon">
            <svg viewBox="0 0 56 56" fill="none">
                <path class="mn-body" d="M36 8a17 17 0 1 0 12 27A20 20 0 0 1 36 8z" fill="#e8edf5" stroke="#a9b4c6" stroke-width="2"/>
                <g fill="#f0b429">
                    <circle class="mn-star mn-s1" cx="14" cy="14" r="2"/>
                    <circle class="mn-star mn-s2" cx="44" cy="16" r="1.6"/>
                    <circle class="mn-star mn-s3" cx="18" cy="42" r="1.6"/>
                </g>
            </svg>
        </span>
    </span>
    <span class="bv-text" id="boardVeilText">{{ $waitFirst['line'] }}</span>
</div>
@once
    @push('scripts')
    <script>
    /* One line per wait, drawn at random. The pool travels with the page, so
       re-rolling costs nothing and a farmer opening the same board twice in a
       morning is not read the same joke twice. */
    (function () {
        window.WAIT_LINES = @json($waitPool);
        window.rollWaitLine = function (host) {
            const card = host || document.querySelector('.bv-card');
            const pool = window.WAIT_LINES || [];
            if (!card || pool.length < 2) return;
            const pick = pool[Math.floor(Math.random() * pool.length)];
            const text = card.querySelector('.bv-text');
            if (text) text.textContent = pick.line;
            let scene = card.querySelector('.bv-s[data-scene="' + pick.scene + '"]');
            // A line added from the admin naming a scene this build does not
            // know about still shows — it just grows a seedling.
            if (!scene) scene = card.querySelector('.bv-s[data-scene="seedling"]');
            card.querySelectorAll('.bv-s').forEach((s) => s.classList.toggle('is-on', s === scene));
        };
    })();
    </script>
    @endpush
@endonce
