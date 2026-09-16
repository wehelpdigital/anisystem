{{-- THE FLAG: which face of the public site you are reading.

     Two faces (2026-09-16): the Philippines, in Taglish and pesos, and the
     international one, in English and dollars. The visitor's address picks
     the first one shown; this pill lets anybody pick the other and is
     remembered. Just the two marks (the owner's call): the Philippine flag
     and a globe, drawn as SVG so they look the same on a Windows desktop
     (which has no flag emoji) as on a phone. The words live in the title
     and the accessible name. --}}
@php
    $fsFace = \App\Support\Region::face();
    $fsTo = '/' . ltrim(request()->path(), '/');
@endphp
<div class="face-switch {{ ($wide ?? false) ? 'is-wide' : '' }}" role="group" aria-label="Site edition">
    <a href="{{ route('face.switch', ['face' => 'ph', 'to' => $fsTo]) }}" class="face-opt {{ $fsFace === 'ph' ? 'is-on' : '' }}" rel="nofollow" title="Philippines" aria-label="Philippines edition" @if ($fsFace === 'ph') aria-current="true" @endif>
        <svg class="face-flag" viewBox="0 0 24 24" aria-hidden="true">
            <clipPath id="fsPhClip"><circle cx="12" cy="12" r="12"/></clipPath>
            <g clip-path="url(#fsPhClip)">
                <rect x="0" y="0" width="24" height="12" fill="#0038A8"/>
                <rect x="0" y="12" width="24" height="12" fill="#CE1126"/>
                <path d="M0 0 L13 12 L0 24 Z" fill="#fff"/>
                <circle cx="5.2" cy="12" r="2.1" fill="#FCD116"/>
                <circle cx="2.2" cy="3.4" r=".8" fill="#FCD116"/>
                <circle cx="2.2" cy="20.6" r=".8" fill="#FCD116"/>
                <circle cx="10.6" cy="12" r=".8" fill="#FCD116"/>
            </g>
        </svg>
    </a>
    <a href="{{ route('face.switch', ['face' => 'en', 'to' => $fsTo]) }}" class="face-opt {{ $fsFace === 'en' ? 'is-on' : '' }}" rel="nofollow" title="International (English)" aria-label="International edition, in English" @if ($fsFace === 'en') aria-current="true" @endif>
        <svg class="face-flag face-globe" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.7" stroke-linecap="round" aria-hidden="true">
            <circle cx="12" cy="12" r="9.2"/>
            <path d="M2.8 12h18.4M12 2.8c3 3.2 3 15.2 0 18.4M12 2.8c-3 3.2-3 15.2 0 18.4M4.6 7.4h14.8M4.6 16.6h14.8"/>
        </svg>
    </a>
</div>
@once
<style>
    .face-switch { display: inline-flex; padding: .2rem; gap: .15rem; border-radius: 999px; background: #f3f4f6; border: 1px solid #e5e7eb; }
    .face-switch.is-wide { justify-content: center; }
    .face-opt { display: inline-flex; align-items: center; justify-content: center; width: 2rem; height: 2rem; border-radius: 999px;
        color: #6b7280; text-decoration: none; opacity: .55;
        transition: background .28s cubic-bezier(.22,1,.36,1), opacity .28s cubic-bezier(.22,1,.36,1), transform .28s cubic-bezier(.22,1,.36,1); }
    .face-opt:hover { opacity: .9; transform: translateY(-1px); }
    .face-opt.is-on { background: #fff; color: #2f5219; opacity: 1; box-shadow: 0 1px 3px rgb(0 0 0 / .14); }
    .face-flag { width: 1.25rem; height: 1.25rem; display: block; }
    .face-globe { color: #2563eb; }
    .face-opt:not(.is-on) .face-globe { color: #6b7280; }
    html.dark .face-switch { background: #151b12; border-color: #2b3a1c; }
    html.dark .face-opt { color: #93a684; }
    html.dark .face-opt.is-on { background: #22301a; color: #cfe6b8; }
    html.dark .face-globe { color: #93c5fd; }
    @media (prefers-reduced-motion: reduce) { .face-opt { transition: none; } .face-opt:hover { transform: none; } }
</style>
@endonce
