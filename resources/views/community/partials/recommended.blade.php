{{-- "People you may know" strip — mutual co-farmers + same-area members,
     ranked. Expects: $recommendations (collection of User with reco fields).
     Horizontally scrollable (drag-to-scroll via .scroll-chips). --}}
@if (($recommendations ?? collect())->isNotEmpty())
    <div class="card p-4 mb-4">
        <div class="flex items-center justify-between mb-3">
            <h3 class="font-bold text-gray-900" style="font-family:var(--font-heading)">
                🤝 People you may know
            </h3>
            <a href="{{ route('community.connect.members') }}" class="text-xs font-semibold text-brand-700 hover:text-brand-800">See all members</a>
        </div>
        <div class="reco-scroller">
            <button type="button" class="reco-nav reco-nav-left" data-reco-nav="-1" aria-label="Scroll left" hidden>
                <svg class="w-4 h-4" fill="none" stroke="currentColor" stroke-width="2.4" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" d="M15 19l-7-7 7-7"/></svg>
            </button>
            <div class="scroll-chips reco-track flex gap-3 overflow-x-auto pb-1">
                @foreach ($recommendations as $u)
                    @include('community.partials.reco-card', ['u' => $u])
                @endforeach
            </div>
            <button type="button" class="reco-nav reco-nav-right" data-reco-nav="1" aria-label="Scroll right" hidden>
                <svg class="w-4 h-4" fill="none" stroke="currentColor" stroke-width="2.4" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" d="M9 5l7 7-7 7"/></svg>
            </button>
        </div>
    </div>
    @once
        @push('scripts')
        <script>
        (function () {
            if (window.__plazaRecoNavBound) return;
            window.__plazaRecoNavBound = true;
            const EDGE = 2;
            function update(track) {
                const scroller = track.closest('.reco-scroller');
                if (!scroller) return;
                const max = track.scrollWidth - track.clientWidth;
                const overflow = max > EDGE;
                const left = scroller.querySelector('.reco-nav-left');
                const right = scroller.querySelector('.reco-nav-right');
                if (left) left.hidden = !overflow || track.scrollLeft <= EDGE;
                if (right) right.hidden = !overflow || track.scrollLeft >= max - EDGE;
            }
            document.addEventListener('click', (e) => {
                const btn = e.target.closest('[data-reco-nav]');
                if (!btn) return;
                const track = btn.closest('.reco-scroller')?.querySelector('.reco-track');
                if (!track) return;
                const dir = parseInt(btn.getAttribute('data-reco-nav'), 10) || 1;
                const reduce = window.matchMedia('(prefers-reduced-motion: reduce)').matches;
                track.scrollBy({ left: dir * Math.max(track.clientWidth * 0.85, 200), behavior: reduce ? 'auto' : 'smooth' });
            });
            function scan() {
                document.querySelectorAll('.reco-track').forEach((t) => {
                    if (!t.dataset.recoBound) { t.dataset.recoBound = '1'; t.addEventListener('scroll', () => update(t), { passive: true }); }
                    update(t);
                });
            }
            if (document.readyState === 'loading') document.addEventListener('DOMContentLoaded', scan); else scan();
            window.addEventListener('resize', () => document.querySelectorAll('.reco-track').forEach(update));
        })();
        </script>
        @endpush
    @endonce
@endif
