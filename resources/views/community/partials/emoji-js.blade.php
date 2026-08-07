{{-- Shared farmer emoji popover: include ONCE per page. Any button with
     class js-emoji-btn opens it; the target input is data-target="<id>" or
     the nearest input/textarea inside the button's parent form/shell. --}}
<script>
(() => {
    if (window.__plazaEmojiBound) return;
    window.__plazaEmojiBound = true;
    const EMOJIS = ['🌱','🌾','🌽','🍚','🍅','🍆','🥒','🥬','🌶️','🥭','🍌','🥥','☀️','🌤️','🌧️','⛈️','🌈','💧','🌡️','🐛','🐌','🐜','🐔','🐖','🐃','🚜','🧺','🧑‍🌾','😀','😄','😅','🤔','😮','😢','😍','🙏','👍','👏','💪','🤝'];
    const pop = document.createElement('div');
    pop.className = 'emoji-pop';
    EMOJIS.forEach((em) => {
        const b = document.createElement('button');
        b.type = 'button'; b.textContent = em;
        pop.appendChild(b);
    });
    document.body.appendChild(pop);
    let popTarget = null;

    function openPop(anchor, target) {
        popTarget = target;
        const r = anchor.getBoundingClientRect();
        const below = r.top < 320;
        pop.style.setProperty('--pop-origin', below ? 'top left' : 'bottom left');
        pop.classList.add('is-open');
        const pw = pop.offsetWidth, ph = pop.offsetHeight;
        pop.style.left = Math.max(8, Math.min(r.left, window.innerWidth - pw - 8)) + 'px';
        pop.style.top = (below ? r.bottom + 8 : r.top - ph - 8) + 'px';
    }
    function closePop() { pop.classList.remove('is-open'); popTarget = null; }

    document.addEventListener('click', (e) => {
        const btn = e.target.closest('.js-emoji-btn');
        if (btn) {
            if (pop.classList.contains('is-open')) { closePop(); return; }
            const target = btn.dataset.target
                ? document.getElementById(btn.dataset.target)
                : (btn.closest('form, .reply-shell, .emoji-scope')?.querySelector('input[type=text], input:not([type]), textarea'));
            if (target) openPop(btn, target);
            return;
        }
        if (e.target.closest('.emoji-pop button')) {
            const em = e.target.closest('button').textContent;
            if (popTarget) {
                const start = popTarget.selectionStart ?? popTarget.value.length;
                popTarget.setRangeText(em, start, popTarget.selectionEnd ?? start, 'end');
                popTarget.dispatchEvent(new Event('input'));
                popTarget.focus();
            }
            return;
        }
        if (!e.target.closest('.emoji-pop')) closePop();
    });
    document.addEventListener('keydown', (e) => { if (e.key === 'Escape') closePop(); });
})();
</script>
