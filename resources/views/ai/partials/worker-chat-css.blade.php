@once
<style>
    .wc-wrap { max-width: 44rem; margin: 0 auto; display: flex; flex-direction: column; gap: .6rem; }
    .wc-say { font-size: .82rem; line-height: 1.5; color: var(--color-gray-500); margin-bottom: .2rem; }
    .wc-none { text-align: center; padding: 2.5rem 1rem; font-size: .85rem; color: var(--color-gray-400); }
    .wc-row { display: flex; align-items: center; gap: .7rem; padding: .7rem .8rem; border-radius: .8rem; text-decoration: none;
        background: var(--color-white); border: 1px solid var(--color-gray-200);
        transition: border-color .28s cubic-bezier(.22,1,.36,1), transform .28s cubic-bezier(.22,1,.36,1); }
    .wc-row:hover { border-color: #a8cc7e; transform: translateY(-1px); }
    .wc-text { min-width: 0; flex: 1 1 auto; display: flex; flex-direction: column; gap: .1rem; }
    .wc-text b { font-size: .88rem; font-weight: 700; color: var(--color-gray-900); overflow: hidden; text-overflow: ellipsis; white-space: nowrap; }
    .wc-text i { font-style: normal; font-size: .74rem; color: var(--color-gray-500); overflow: hidden; text-overflow: ellipsis; white-space: nowrap; }
    .wc-go { width: 1rem; height: 1rem; flex: none; color: var(--color-gray-400); }
    .wc-face { width: 2.1rem; height: 2.1rem; flex: none; border-radius: 999px; overflow: hidden; display: grid; place-items: center;
        font-size: .72rem; font-weight: 800; color: #2d5016; background: #e2efd3; }
    .wc-face img { width: 100%; height: 100%; object-fit: cover; }
    .wc-turn { display: flex; align-items: flex-start; gap: .5rem; }
    .wc-turn.is-them { flex-direction: row-reverse; }
    .wc-bubble { min-width: 0; max-width: 85%; padding: .6rem .8rem; border-radius: .9rem; font-size: .88rem; line-height: 1.55; overflow-wrap: anywhere;
        color: var(--color-gray-800); background: var(--color-white); border: 1px solid var(--color-gray-200); }
    .wc-turn.is-them .wc-bubble { background: #eaf4dd; border-color: #cfe5b8; }
    .wc-bubble p { margin: .4rem 0; } .wc-bubble p:first-child { margin-top: 0; }
    .wc-bubble ul { list-style: disc; padding-left: 1.25rem; margin: .4rem 0; }
    .wc-bubble ol { list-style: decimal; padding-left: 1.4rem; margin: .4rem 0; }
    .wc-bubble strong { font-weight: 700; }
    .wc-bubble table { display: block; max-width: 100%; overflow-x: auto; border-collapse: collapse; font-size: .8rem; }
    .wc-bubble th, .wc-bubble td { border: 1px solid var(--color-gray-200); padding: .25rem .45rem; }
    .wc-shot { display: block; max-width: 100%; max-height: 16rem; border-radius: .6rem; margin-top: .4rem; }
    .wc-when { display: block; margin-top: .3rem; font-size: .66rem; color: var(--color-gray-400); }
    html.dark .wc-row, html.dark .wc-bubble { background: #151b12; border-color: #2b3a1c; }
    html.dark .wc-text b { color: #e8efe1; }
    html.dark .wc-bubble { color: #d7e2cb; }
    html.dark .wc-turn.is-them .wc-bubble { background: #22301a; border-color: #3d5427; }
    html.dark .wc-face { color: #cfe0bb; background: #2a3a1d; }
    @media (prefers-reduced-motion: reduce) { .wc-row { transition: none; } }
</style>
@endonce
