@extends('layouts.app')

@section('title', 'Settings')
@section('page-title', 'Settings')
@section('page-subtitle', 'How the app behaves for you')

@push('head')
<style>
    /* Tabs: one today, but the shape is here for the next one. */
    .st-tabs { display: flex; gap: .35rem; border-bottom: 1px solid var(--color-gray-200); margin-bottom: 1.1rem; }
    .st-tab { padding: .55rem .9rem; font-size: .85rem; font-weight: 700; color: var(--color-gray-500);
        border-bottom: 2px solid transparent; margin-bottom: -1px; }
    .st-tab.is-on { color: #3d6823; border-bottom-color: #4a7c2a; }
    html.dark .st-tab.is-on { color: #a5c97e; }

    .st-group { margin-bottom: 1.4rem; }
    .st-group h3 { font-size: .95rem; font-weight: 800; color: var(--color-gray-900); }
    .st-group p.st-why { font-size: .8rem; color: var(--color-gray-500); margin-top: .15rem; line-height: 1.5; }
    html.dark .st-group h3 { color: #e8efe1; }

    /* A row of choices that look like what they do. */
    .st-choices { display: flex; flex-wrap: wrap; gap: .5rem; margin-top: .7rem; }
    .st-choice { display: inline-flex; flex-direction: column; align-items: center; justify-content: center;
        gap: .15rem; min-width: 4.6rem; padding: .6rem .8rem; border-radius: .8rem; cursor: pointer;
        background: var(--color-white); border: 2px solid var(--color-gray-200); color: var(--color-gray-700);
        transition: border-color .28s cubic-bezier(.22,1,.36,1), background .28s cubic-bezier(.22,1,.36,1); }
    .st-choice:hover { border-color: #a8cc7e; }
    .st-choice.is-on { border-color: #4a7c2a; background: #f0f7e8; color: #2c4d18; }
    /* The sample IS the setting: each size shows itself. */
    .st-choice b { font-weight: 800; line-height: 1; }
    .st-choice span { font-size: .66rem; font-weight: 700; text-transform: uppercase; letter-spacing: .05em; opacity: .75; }
    .st-size-sm b { font-size: .85rem; }
    .st-size-md b { font-size: 1rem; }
    .st-size-lg b { font-size: 1.2rem; }
    .st-size-xl b { font-size: 1.45rem; }
    html.dark .st-choice { background: #151b12; border-color: #2b3a1c; color: #cdd8c0; }
    html.dark .st-choice.is-on { border-color: #6b9f3d; background: rgb(107 159 61 / .22); color: #e8efe1; }

    /* A switch row for the on/off ones. */
    .st-switch { display: flex; align-items: center; gap: .9rem; width: 100%; padding: .85rem .95rem;
        border-radius: .9rem; background: var(--color-white); border: 1px solid var(--color-gray-200);
        text-align: left; cursor: pointer; margin-top: .6rem;
        transition: border-color .28s cubic-bezier(.22,1,.36,1); }
    .st-switch:hover { border-color: #a8cc7e; }
    .st-switch-txt { flex: 1 1 auto; min-width: 0; }
    .st-switch-txt b { display: block; font-size: .88rem; font-weight: 800; color: var(--color-gray-900); }
    .st-switch-txt span { display: block; font-size: .76rem; color: var(--color-gray-500); line-height: 1.45; margin-top: .1rem; }
    .st-knob { flex: 0 0 auto; width: 2.6rem; height: 1.5rem; border-radius: 999px; background: var(--color-gray-300);
        position: relative; transition: background .28s cubic-bezier(.22,1,.36,1); }
    .st-knob::after { content: ''; position: absolute; top: .2rem; left: .2rem; width: 1.1rem; height: 1.1rem;
        border-radius: 999px; background: #fff; box-shadow: 0 1px 3px rgb(0 0 0 / .3);
        transition: transform .28s cubic-bezier(.22,1,.36,1); }
    .st-switch.is-on .st-knob { background: #4a7c2a; }
    .st-switch.is-on .st-knob::after { transform: translateX(1.1rem); }
    html.dark .st-switch { background: #151b12; border-color: #2b3a1c; }
    html.dark .st-switch-txt b { color: #e8efe1; }
    html.dark .st-switch-txt span { color: #9fb08e; }
    html.dark .st-knob { background: #3f4a37; }

    /* What the choices look like, before you live with them. */
    .st-preview { margin-top: .9rem; padding: .9rem 1rem; border-radius: .9rem;
        background: var(--color-gray-50); border: 1px solid var(--color-gray-200); }
    .st-preview h4 { font-size: .95rem; font-weight: 800; color: var(--color-gray-900); }
    .st-preview p { font-size: .82rem; color: var(--color-gray-500); line-height: 1.55; margin-top: .2rem; }
    .st-preview .st-prow { display: flex; align-items: center; gap: .5rem; margin-top: .7rem; flex-wrap: wrap; }
    html.dark .st-preview { background: rgb(255 255 255 / .04); border-color: #2b3a1c; }
    html.dark .st-preview h4 { color: #e8efe1; }

    @media (prefers-reduced-motion: reduce) {
        .st-choice, .st-switch, .st-knob, .st-knob::after { transition: none; }
    }
</style>
@endpush

@section('content')
<div class="max-w-3xl">
    <div class="st-tabs">
        <span class="st-tab is-on">Accessibility</span>
    </div>

    <div class="card card-body">
        <div class="st-group">
            <h3>Text size</h3>
            <p class="st-why">Everything grows together — the board, the notes, the buttons — so nothing is left small next to something big.</p>
            <div class="st-choices" id="stFont">
                <button type="button" class="st-choice st-size-sm" data-font="sm"><b>Aa</b><span>Small</span></button>
                <button type="button" class="st-choice st-size-md" data-font="md"><b>Aa</b><span>Normal</span></button>
                <button type="button" class="st-choice st-size-lg" data-font="lg"><b>Aa</b><span>Large</span></button>
                <button type="button" class="st-choice st-size-xl" data-font="xl"><b>Aa</b><span>Largest</span></button>
            </div>
        </div>

        <div class="st-group">
            <h3>Seeing it clearly</h3>
            <p class="st-why">For bright sunlight, tired eyes, or a screen that has seen a few seasons.</p>

            <button type="button" class="st-switch" id="stContrast" role="switch" aria-checked="false">
                <span class="st-switch-txt">
                    <b>Higher contrast</b>
                    <span>Darker text and edges you can actually see, instead of soft greys.</span>
                </span>
                <span class="st-knob" aria-hidden="true"></span>
            </button>

            <button type="button" class="st-switch" id="stUnderline" role="switch" aria-checked="false">
                <span class="st-switch-txt">
                    <b>Underline links</b>
                    <span>Marks every link by its shape, not only by its colour.</span>
                </span>
                <span class="st-knob" aria-hidden="true"></span>
            </button>

            <button type="button" class="st-switch" id="stMotion" role="switch" aria-checked="false">
                <span class="st-switch-txt">
                    <b>Reduce movement</b>
                    <span>Panels and sheets appear instead of sliding. Easier on a slow phone, and on anyone movement bothers.</span>
                </span>
                <span class="st-knob" aria-hidden="true"></span>
            </button>
        </div>

        <div class="st-preview">
            <h4>The season is looking good</h4>
            <p>This is how ordinary writing will read — a note about a lot, a warning about the weather, the words under a button.</p>
            <div class="st-prow">
                <span class="badge badge-green">Active</span>
                <span class="badge badge-gray">DAS 58</span>
                <a href="{{ route('sm.index') }}">A link to somewhere</a>
                <button type="button" class="btn btn-primary btn-sm">A button</button>
            </div>
        </div>

        <p class="text-xs text-gray-400 mt-4">These are kept on this device, and they take effect straight away — on every page, not just this one.</p>
    </div>
</div>
@endsection

@push('scripts')
<script>
(() => {
    const root = document.documentElement;
    const put = (k, v) => { try { localStorage.setItem(k, v); } catch (_) { /* private mode */ } };
    const get = (k) => { try { return localStorage.getItem(k); } catch (_) { return null; } };

    /* ---- text size ---- */
    const fontBtns = Array.from(document.querySelectorAll('#stFont [data-font]'));
    function paintFont() {
        const now = root.dataset.fontScale || 'md';
        fontBtns.forEach((b) => b.classList.toggle('is-on', b.getAttribute('data-font') === now));
    }
    fontBtns.forEach((b) => b.addEventListener('click', () => {
        root.dataset.fontScale = b.getAttribute('data-font');
        put('sm-a11y-font', root.dataset.fontScale);
        paintFont();
    }));
    paintFont();

    /* ---- the switches ---- */
    function wire(id, cls, key) {
        const btn = document.getElementById(id);
        if (!btn) return;
        const paint = () => {
            const on = root.classList.contains(cls);
            btn.classList.toggle('is-on', on);
            btn.setAttribute('aria-checked', on ? 'true' : 'false');
        };
        btn.addEventListener('click', () => {
            const on = !root.classList.contains(cls);
            root.classList.toggle(cls, on);
            put(key, on ? '1' : '0');
            paint();
        });
        paint();
    }
    wire('stContrast', 'sm-contrast', 'sm-a11y-contrast');
    wire('stUnderline', 'sm-underline', 'sm-a11y-underline');
    wire('stMotion', 'sm-still', 'sm-a11y-motion');
})();
</script>
@endpush
