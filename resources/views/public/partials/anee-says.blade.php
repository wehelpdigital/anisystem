{{-- Anee says a line (2026-10-28, the owner: "landing pages should be somewhat conversational right?"): her face and a
     speech bubble that types in the first time it is seen, three dots and then her words. Used on the ads landing page
     to open its sections like a conversation.

     $say   her words (plain text; *stars* mark words in gold or green)
     $tone  'light' (default) or 'dark' (on a dark band)
     $align 'center' (default), 'left' or 'right' --}}
@php
    $asTone = ($tone ?? 'light') === 'dark' ? 'is-dark' : '';
    $asAlign = in_array($align ?? 'center', ['left', 'right'], true) ? 'is-' . $align : 'is-center';
@endphp
{{-- A blank line, or a single dot from the mother app's editor, draws no bubble. --}}
@if (! in_array(trim((string) ($say ?? '')), ['', '.'], true))
    <div class="as-say {{ $asTone }} {{ $asAlign }}" data-as-say>
        <img src="{{ asset('images/anee/avatar-160.jpg') }}" alt="" class="as-face" width="80" height="80" loading="lazy">
        <p class="as-bubble"><span class="as-dots" aria-hidden="true"><i></i><i></i><i></i></span><span class="as-words">{!! \App\Support\LandingPage::marked($say) !!}</span></p>
    </div>
@endif

@once
@push('head')
<style>
    .as-say { display: flex; align-items: flex-end; gap: .55rem; max-width: 34rem; }
    .as-say.is-center { margin-inline: auto; justify-content: center; }
    .as-say.is-right { margin-left: auto; justify-content: flex-end; }
    .as-face { flex: none; width: 2.6rem; height: 2.6rem; border-radius: 999px; object-fit: cover; box-shadow: 0 0 0 2px #fff, 0 0 0 4px #f5c518, 0 10px 20px -10px rgb(0 0 0 / .45); }
    .as-bubble { position: relative; display: grid; padding: .7rem .95rem; border-radius: 1.1rem 1.1rem 1.1rem .3rem; text-align: left;
        font-size: .98rem; font-weight: 700; line-height: 1.45; color: #1f2a17; background: #fff; box-shadow: 0 14px 30px -20px rgb(20 33 12 / .55), 0 0 0 1px #e3ecd9; }
    .as-bubble em { font-style: normal; color: #3d6823; }
    .as-say.is-dark .as-bubble { color: #fff; background: rgb(255 255 255 / .1); box-shadow: 0 0 0 1px rgb(255 255 255 / .18); backdrop-filter: blur(6px); -webkit-backdrop-filter: blur(6px); }
    .as-say.is-dark .as-bubble em { color: #f5c518; }
    /* The dots and the words share one cell: the bubble keeps the words' size while the dots show. */
    .as-dots, .as-words { grid-area: 1 / 1; }
    .as-dots { display: none; align-items: center; gap: .3rem; }
    .as-dots i { width: .45rem; height: .45rem; border-radius: 999px; background: currentColor; opacity: .45; }
    @media (prefers-reduced-motion: no-preference) {
        html.js .as-say[data-as-say] { opacity: 0; transform: translateY(10px); transition: opacity .45s cubic-bezier(.22,1,.36,1), transform .45s cubic-bezier(.22,1,.36,1); }
        html.js .as-say.is-in { opacity: 1; transform: none; }
        html.js .as-say.is-typing .as-dots { display: inline-flex; }
        html.js .as-say.is-typing .as-words { visibility: hidden; }
        html.js .as-say.is-typing .as-dots i { animation: asDot 1s ease-in-out infinite; }
        html.js .as-say.is-typing .as-dots i:nth-child(2) { animation-delay: .15s; }
        html.js .as-say.is-typing .as-dots i:nth-child(3) { animation-delay: .3s; }
        html.js .as-say .as-words { transition: opacity .35s ease; }
        html.js .as-say.is-in:not(.is-typing) .as-words { animation: asWords .4s cubic-bezier(.22,1,.36,1); }
        @keyframes asDot { 0%, 100% { transform: translateY(0); opacity: .35; } 50% { transform: translateY(-4px); opacity: .9; } }
        @keyframes asWords { from { opacity: 0; } to { opacity: 1; } }
    }
</style>
@endpush
@push('scripts')
<script>
(() => {
    // Each line types in once: in view, three dots for a moment, then her words.
    const lines = [...document.querySelectorAll('[data-as-say]')];
    if (!lines.length) return;
    const calm = matchMedia('(prefers-reduced-motion: reduce)').matches;
    const say = (el) => {
        el.classList.add('is-in');
        if (calm) return;
        el.classList.add('is-typing');
        const words = (el.querySelector('.as-words')?.textContent || '').length;
        setTimeout(() => el.classList.remove('is-typing'), Math.min(1300, 550 + words * 6));
    };
    if (!('IntersectionObserver' in window)) { lines.forEach((el) => el.classList.add('is-in')); return; }
    const io = new IntersectionObserver((es) => es.forEach((e) => { if (e.isIntersecting) { say(e.target); io.unobserve(e.target); } }), { rootMargin: '0px 0px -12% 0px' });
    lines.forEach((el) => io.observe(el));
})();
</script>
@endpush
@endonce
