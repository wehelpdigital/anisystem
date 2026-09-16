{{-- THE FLAG: which face of the public site you are reading.

     Two faces (2026-09-16): the Philippines, in Taglish and pesos, and the
     international one, in English and dollars. The visitor's address picks
     the first one shown; this pill lets anybody pick the other and is
     remembered. Wears the same pill in the desktop header and the phone
     menu; pass 'wide' to fill a row. --}}
@php
    $fsFace = \App\Support\Region::face();
    $fsTo = '/' . ltrim(request()->path(), '/');
@endphp
<div class="face-switch {{ ($wide ?? false) ? 'is-wide' : '' }}" role="group" aria-label="Site edition">
    <a href="{{ route('face.switch', ['face' => 'ph', 'to' => $fsTo]) }}" class="face-opt {{ $fsFace === 'ph' ? 'is-on' : '' }}" rel="nofollow" @if ($fsFace === 'ph') aria-current="true" @endif>
        <span class="face-flag" aria-hidden="true">🇵🇭</span><span>Philippines</span>
    </a>
    <a href="{{ route('face.switch', ['face' => 'en', 'to' => $fsTo]) }}" class="face-opt {{ $fsFace === 'en' ? 'is-on' : '' }}" rel="nofollow" @if ($fsFace === 'en') aria-current="true" @endif>
        <span class="face-flag" aria-hidden="true">🌐</span><span>International</span>
    </a>
</div>
@once
<style>
    .face-switch { display: inline-flex; padding: .2rem; gap: .15rem; border-radius: 999px; background: #f3f4f6; border: 1px solid #e5e7eb; }
    .face-switch.is-wide { display: flex; width: 100%; }
    .face-switch.is-wide .face-opt { flex: 1 1 0; justify-content: center; }
    .face-opt { display: inline-flex; align-items: center; gap: .35rem; padding: .3rem .7rem; border-radius: 999px;
        font-size: .74rem; font-weight: 800; color: #6b7280; text-decoration: none; white-space: nowrap;
        transition: background .28s cubic-bezier(.22,1,.36,1), color .28s cubic-bezier(.22,1,.36,1); }
    .face-opt:hover { color: #1f2937; }
    .face-opt.is-on { background: #fff; color: #2f5219; box-shadow: 0 1px 3px rgb(0 0 0 / .12); }
    .face-flag { font-size: .95rem; line-height: 1; }
    html.dark .face-switch { background: #151b12; border-color: #2b3a1c; }
    html.dark .face-opt { color: #93a684; }
    html.dark .face-opt.is-on { background: #22301a; color: #cfe6b8; }
    @media (prefers-reduced-motion: reduce) { .face-opt { transition: none; } }
</style>
@endonce
