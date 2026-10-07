{{-- The flag switch's styles (partials/face-switch), in the head: a <style>
     in the body is a markup error. --}}
<style>
    .face-switch { position: relative; display: inline-flex; padding: .2rem; gap: .15rem; border-radius: 999px; background: #f3f4f6; border: 1px solid #e5e7eb; }
    button.face-opt { border: 0; background: none; padding: 0; cursor: pointer; font: inherit; }
    /* The closed globe's note: under the switch in the header, above it in
       the phone menu (the switch is the last row of a scrolling panel). */
    .face-note { position: absolute; z-index: 60; top: calc(100% + .5rem); right: 0; width: max-content; max-width: min(15rem, calc(100vw - 2rem));
        padding: .6rem .8rem; border-radius: .85rem; background: #14210c; color: #e5efdc; font-size: .8rem; font-weight: 500; line-height: 1.4; text-align: left;
        box-shadow: 0 10px 28px -10px rgb(20 33 12 / .55); opacity: 0; visibility: hidden; transform: translateY(-.3rem); pointer-events: none;
        transition: opacity .28s cubic-bezier(.22,1,.36,1), transform .28s cubic-bezier(.22,1,.36,1), visibility 0s linear .28s; }
    .face-note b { display: block; font-weight: 800; color: #fff; }
    .pm-face .face-note { top: auto; bottom: calc(100% + .5rem); transform: translateY(.3rem); }
    .face-switch.is-noted .face-note { opacity: 1; visibility: visible; transform: none; transition-delay: 0s; }
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
    html.dark .face-note { background: #22301a; color: #cfe6b8; }
    @media (prefers-reduced-motion: reduce) { .face-opt, .face-note { transition: none; } .face-opt:hover { transform: none; } .face-note { transform: none !important; } }
</style>
