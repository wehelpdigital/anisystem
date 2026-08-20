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
            let target = btn.dataset.target
                ? document.getElementById(btn.dataset.target)
                : (btn.closest('form, .reply-shell, .emoji-scope')?.querySelector('input[type=text], input:not([type]), textarea'));
            /* A rich editor is a div, not a field.
             *
             * The named target may be the editor's MOUNT, with the editable
             * somewhere inside it; take that instead so the emoji lands in
             * the words rather than nowhere. */
            if (target && !('setRangeText' in target)) {
                target = target.querySelector('[contenteditable="true"]')
                    || target.querySelector('textarea, input[type=text]')
                    || target;
            }
            if (target) openPop(btn, target);
            return;
        }
        if (e.target.closest('.emoji-pop button')) {
            const em = e.target.closest('button').textContent;
            if (popTarget) {
                if ('setRangeText' in popTarget) {
                    const start = popTarget.selectionStart ?? popTarget.value.length;
                    popTarget.setRangeText(em, start, popTarget.selectionEnd ?? start, 'end');
                    popTarget.dispatchEvent(new Event('input', { bubbles: true }));
                    popTarget.focus();
                } else {
                    // A contenteditable: put it where the caret is, and at the
                    // end when the caret is somewhere else on the page.
                    popTarget.focus();
                    const sel = window.getSelection();
                    const inside = sel && sel.rangeCount && popTarget.contains(sel.anchorNode);
                    if (!inside) {
                        const end = document.createRange();
                        end.selectNodeContents(popTarget);
                        end.collapse(false);
                        sel.removeAllRanges();
                        sel.addRange(end);
                    }
                    if (!document.execCommand('insertText', false, em)) {
                        popTarget.appendChild(document.createTextNode(em));
                    }
                    popTarget.dispatchEvent(new Event('input', { bubbles: true }));
                }
            }
            return;
        }
        if (!e.target.closest('.emoji-pop')) closePop();
    });
    document.addEventListener('keydown', (e) => { if (e.key === 'Escape') closePop(); });
})();
</script>
