{{-- The chat column measures the room it has instead of guessing at it.

     Both chats sized themselves with `100dvh` minus a stack of rem constants
     standing in for the app bar, main's paddings, the tab bar and the footer —
     nine of them between the two pages, each hand-tuned against one screen.
     Any of those change and the sum is wrong: the footer wraps onto a second
     row of links as the window narrows, and on this page the history rail is
     often the taller of the two columns, so the row is as tall as the RAIL
     rather than the chat. On a 1280x800 desktop the empty chat opened 178
     pixels past the bottom of the screen.

     So it reads the column's own top, adds up what genuinely sits below it —
     main's bottom padding, the body's, and any footer still on the page — and
     gives the column what is left. The rem constants stay behind as the
     fallback for the frame before this runs, so nothing jumps. --}}
<script>
(function () {
    function px (el, prop) { return parseFloat(getComputedStyle(el)[prop]) || 0; }

    window.aiFitColumn = function () {
        var chat = document.querySelector('.aichat');
        if (!chat) return;
        /* The history rail is the chat's sibling and can be the taller of the
           two, so the number goes on whatever holds them both. */
        var root = chat.closest('.ai-layout, .ai-shell') || chat;
        var top = root.getBoundingClientRect().top + window.scrollY;

        var below = px(document.body, 'paddingBottom');
        var main = root.closest('main');
        if (main) below += px(main, 'paddingBottom');
        /* A fixed footer or tab bar floats over the page and costs it nothing;
           one in the flow costs its height plus the gap it holds itself away
           by. */
        document.querySelectorAll('footer').forEach(function (f) {
            var cs = getComputedStyle(f);
            if (cs.display === 'none' || cs.position === 'fixed') return;
            below += f.offsetHeight + (parseFloat(cs.marginTop) || 0);
        });

        var avail = Math.round(window.innerHeight - top - below);
        /* The welcome is measured every time even when the room has not
           changed: a font or the film's poster arriving is enough to change
           whether it fits. */
        if (avail >= 240 && root.style.getPropertyValue('--ai-avail') !== avail + 'px') {
            root.style.setProperty('--ai-avail', avail + 'px');
        }
        fitWelcome();
    };

    /* A screen with nothing on it yet should not open scrolled. On a short
       window the greeting, the film and the how-to card can still outrun the
       thread, and the film is the one piece of it that loses nothing by being
       smaller — so it gives up width, down to a floor, before the scrollbar
       appears. Only while the welcome is alone in there; once there are
       messages the thread is a thread and scrolling is what it does. */
    function fitWelcome () {
        var thread = document.getElementById('aiThread');
        var welcome = document.getElementById('aiWelcome');
        if (!thread || !welcome || !thread.contains(welcome)) return;
        if (thread.querySelector('.aimsg')) return;
        var film = welcome.querySelector('.anee-hello-film');
        if (!film) return;
        welcome.style.removeProperty('--anee-film');
        var over = thread.scrollHeight - thread.clientHeight;
        if (over <= 0) return;
        /* Sixteen by nine: every pixel of width is nine sixteenths of height. */
        var next = Math.max(240, Math.round(film.getBoundingClientRect().width - over * (16 / 9)));
        welcome.style.setProperty('--anee-film', next + 'px');
    }

    var fit = window.aiFitColumn;
    fit();
    requestAnimationFrame(fit);

    if (!window.__aiFitBound) {
        window.__aiFitBound = true;
        addEventListener('resize', fit);
        addEventListener('load', fit);
        if (window.visualViewport) visualViewport.addEventListener('resize', fit);
        if (document.fonts && document.fonts.ready) document.fonts.ready.then(fit);
        /* The shell hides the footer and locks the document while a module is
           open, and the phone drops the tab bar by a body class: both change
           the answer, and neither fires a resize. */
        new MutationObserver(fit).observe(document.documentElement, { attributes: true, attributeFilter: ['class'] });
        new MutationObserver(fit).observe(document.body, { attributes: true, attributeFilter: ['class'] });
        var foot = document.querySelector('footer');
        if (foot && window.ResizeObserver) new ResizeObserver(fit).observe(foot);
    }
})();
</script>
