{{-- THE FLAG: which face of the public site you are reading.

     Two faces (2026-09-16): the Philippines, in Taglish and pesos, and the
     international one, in English and dollars. The visitor's address picks
     the first one shown; this pill lets anybody pick the other and is
     remembered. Just the two marks (the owner's call): the Philippine flag
     and a globe, drawn as SVG so they look the same on a Windows desktop
     (which has no flag emoji) as on a phone. The words live in the title
     and the accessible name.

     Only the OTHER face is a link (2026-10-07): the one you are reading is a
     plain mark, and while the international version is closed for
     maintenance its globe is a button that says so. A link there sent every
     crawler from every page to a 503 (a site audit counted 301 of them).
     Its styles are partials/face-switch-css, in the head. --}}
@php
    $fsFace = \App\Support\Region::face();
    $fsTo = '/' . ltrim(request()->path(), '/');
    $fsEnOpen = \App\Support\Region::intlOpen();
    $fsClip = 'fsPhClip' . ($GLOBALS['fsClipN'] = ($GLOBALS['fsClipN'] ?? 0) + 1);
@endphp
<div class="face-switch {{ ($wide ?? false) ? 'is-wide' : '' }}" role="group" aria-label="Site edition">
    @if ($fsFace === 'ph')
    <span class="face-opt is-on" title="Philippines" role="img" aria-label="Philippines edition, the one you are reading">
    @else
    <a href="{{ route('face.switch', ['face' => 'ph', 'to' => $fsTo]) }}" class="face-opt" rel="nofollow" title="Philippines" aria-label="Philippines edition">
    @endif
        <svg class="face-flag" viewBox="0 0 24 24" aria-hidden="true">
            <clipPath id="{{ $fsClip }}"><circle cx="12" cy="12" r="12"/></clipPath>
            <g clip-path="url(#{{ $fsClip }})">
                <rect x="0" y="0" width="24" height="12" fill="#0038A8"/>
                <rect x="0" y="12" width="24" height="12" fill="#CE1126"/>
                <path d="M0 0 L13 12 L0 24 Z" fill="#fff"/>
                <circle cx="5.2" cy="12" r="2.1" fill="#FCD116"/>
                <circle cx="2.2" cy="3.4" r=".8" fill="#FCD116"/>
                <circle cx="2.2" cy="20.6" r=".8" fill="#FCD116"/>
                <circle cx="10.6" cy="12" r=".8" fill="#FCD116"/>
            </g>
        </svg>
    {!! $fsFace === 'ph' ? '</span>' : '</a>' !!}
    @if ($fsFace === 'en')
    <span class="face-opt is-on" title="International (English)" role="img" aria-label="International edition, in English, the one you are reading">
    @elseif ($fsEnOpen)
    <a href="{{ route('face.switch', ['face' => 'en', 'to' => $fsTo]) }}" class="face-opt" rel="nofollow" title="International (English)" aria-label="International edition, in English">
    @else
    <button type="button" class="face-opt face-shut" title="International (English): closed for maintenance" aria-label="International edition, closed for maintenance" aria-expanded="false" data-face-shut>
    @endif
        <svg class="face-flag face-globe" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.7" stroke-linecap="round" aria-hidden="true">
            <circle cx="12" cy="12" r="9.2"/>
            <path d="M2.8 12h18.4M12 2.8c3 3.2 3 15.2 0 18.4M12 2.8c-3 3.2-3 15.2 0 18.4M4.6 7.4h14.8M4.6 16.6h14.8"/>
        </svg>
    {!! $fsFace === 'en' ? '</span>' : ($fsEnOpen ? '</a>' : '</button>') !!}
    @unless ($fsEnOpen || $fsFace === 'en')
        <span class="face-note" role="status"><b>International version</b>Closed for maintenance. It will open again soon.</span>
    @endunless
</div>
@once
<script>
    // The closed globe: a tap shows the note, a second tap, a tap anywhere
    // else, Escape or a few seconds put it away.
    (function () {
        var timer;
        function shut(sw) {
            if (!sw) return;
            sw.classList.remove('is-noted');
            var b = sw.querySelector('[data-face-shut]');
            if (b) b.setAttribute('aria-expanded', 'false');
        }
        document.addEventListener('click', function (e) {
            var btn = e.target.closest && e.target.closest('[data-face-shut]');
            document.querySelectorAll('.face-switch.is-noted').forEach(function (sw) {
                if (!btn || !sw.contains(btn)) shut(sw);
            });
            if (!btn) return;
            var sw = btn.closest('.face-switch');
            var on = !sw.classList.contains('is-noted');
            sw.classList.toggle('is-noted', on);
            btn.setAttribute('aria-expanded', on ? 'true' : 'false');
            clearTimeout(timer);
            if (on) timer = setTimeout(function () { shut(sw); }, 5000);
        });
        document.addEventListener('keydown', function (e) {
            if (e.key === 'Escape') document.querySelectorAll('.face-switch.is-noted').forEach(shut);
        });
    })();
</script>
@endonce
