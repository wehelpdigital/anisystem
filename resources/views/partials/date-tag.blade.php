{{-- A date field wearing a tag.

     Same input, same native picker, but the target is a pill you can see
     and hit with a thumb rather than a hairline field with a small glyph in
     the corner. The label text follows the value (see the script below), so
     it reads "Mon, Aug 18" rather than the browser's own spelling.

     Expects: $id. Optional: $value (Y-m-d), $name, $empty (what to say when
     no date is set), $class (extra classes on the pill), $attrs (raw extra
     attributes for the input). --}}
@once
    @push('scripts')
        <script>
        (() => {
            if (window.__dateTagBooted) return;
            window.__dateTagBooted = true;

            const MONTH = ['Jan', 'Feb', 'Mar', 'Apr', 'May', 'Jun', 'Jul', 'Aug', 'Sep', 'Oct', 'Nov', 'Dec'];
            const DAY = ['Sun', 'Mon', 'Tue', 'Wed', 'Thu', 'Fri', 'Sat'];

            /** "Mon, Aug 18" — and the year only when it is not this one. */
            function say(v) {
                if (!v) return null;
                const [y, m, d] = v.split('-').map(Number);
                if (!y || !m || !d) return null;
                const date = new Date(y, m - 1, d);
                const thisYear = new Date().getFullYear();
                return `${DAY[date.getDay()]}, ${MONTH[m - 1]} ${d}${y === thisYear ? '' : ', ' + y}`;
            }

            function paint(tag) {
                const input = tag.querySelector('input[type="date"]');
                const text = tag.querySelector('.dt-text');
                if (!input || !text) return;
                const pretty = say(input.value);
                text.textContent = pretty || (tag.getAttribute('data-empty') || 'Pick a date');
                text.classList.toggle('dt-empty', !pretty);
            }

            const all = (scope) => (scope || document).querySelectorAll('.date-tag');
            const scan = (scope) => all(scope).forEach(paint);

            document.addEventListener('change', (e) => {
                const tag = e.target.closest && e.target.closest('.date-tag');
                if (tag) paint(tag);
            });
            // A value written by script — a sheet opening on an existing
            // record — fires no change event, so the pills are repainted
            // shortly after any click, which is what opens those sheets.
            window.smDateTags = scan;
            document.addEventListener('sm:module-shown', () => scan());
            let soon = null;
            document.addEventListener('click', () => {
                clearTimeout(soon);
                soon = setTimeout(() => scan(), 80);
            }, true);
            // A native picker is what the pill is for; ask for it on tap
            // where the browser offers one.
            document.addEventListener('click', (e) => {
                const tag = e.target.closest && e.target.closest('.date-tag');
                if (!tag) return;
                const input = tag.querySelector('input[type="date"]');
                if (input && input.showPicker) { try { input.showPicker(); } catch (_) { /* not allowed here */ } }
            });

            if (document.readyState === 'loading') document.addEventListener('DOMContentLoaded', () => scan());
            else scan();
        })();
        </script>
    @endpush
@endonce

<label class="date-tag {{ $class ?? '' }}" data-empty="{{ $empty ?? 'Pick a date' }}">
    <svg fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24" aria-hidden="true"><path stroke-linecap="round" stroke-linejoin="round" d="M8 7V3m8 4V3m-9 8h10M5 21h14a2 2 0 002-2V7a2 2 0 00-2-2H5a2 2 0 00-2 2v12a2 2 0 002 2z"/></svg>
    <span class="dt-text">{{ $empty ?? 'Pick a date' }}</span>
    <input type="date" id="{{ $id }}" @if (! empty($name)) name="{{ $name }}" @endif
        value="{{ $value ?? '' }}" {!! $attrs ?? '' !!}>
</label>
