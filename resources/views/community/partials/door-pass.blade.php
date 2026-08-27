{{-- The password a shut discussion asks for.

     One sheet, shared by the list of discussions and the room's own page,
     because a farmer can meet the same locked door from either and should
     meet the same question. Resolves to the typed secret, or to null if
     they backed out — so the caller reads it as "they answered" or "they
     did not", never as an empty password.

     Emits window.askForPassword(roomName) -> Promise<string|null>. --}}
<div class="sheet hidden" id="doorPassSheet" style="--sheet-width:22rem">
    <div class="sheet-handle"></div>
    <div class="sheet-header">
        <h3 class="sheet-title">Password</h3>
        <button type="button" data-sheet-close class="btn-ghost p-2 rounded-full" aria-label="Close">✕</button>
    </div>
    <div class="sheet-body space-y-2.5">
        <div class="dp-lock" aria-hidden="true">
            <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.9" stroke-linecap="round" stroke-linejoin="round"><rect x="4" y="10.5" width="16" height="10" rx="2.5"/><path d="M8 10.5V7a4 4 0 0 1 8 0v3.5"/></svg>
        </div>
        <p class="dp-say" id="doorPassSay">This discussion asks for a password.</p>
        <div>
            <label class="form-label" for="doorPassInput">Password</label>
            <input type="text" id="doorPassInput" class="form-input" maxlength="60"
                   autocomplete="off" autocapitalize="off" autocorrect="off" spellcheck="false"
                   placeholder="Type it exactly">
            <p class="form-hint">Ask whoever runs the discussion for it.</p>
        </div>
    </div>
    <div class="sheet-footer">
        <button type="button" class="btn btn-primary" id="doorPassGo" style="margin-top:0">Unlock</button>
    </div>
</div>

<style>
    .dp-lock { display:flex; align-items:center; justify-content:center;
        width:3.2rem; height:3.2rem; margin:.2rem auto .1rem; border-radius:9999px;
        background:var(--color-brand-50); color:var(--color-brand-700); }
    .dp-lock svg { width:1.5rem; height:1.5rem; }
    .dp-say { text-align:center; font-size:.88rem; font-weight:600; color:var(--color-gray-500);
        line-height:1.4; margin-bottom:.2rem; }
    /* A wrong password shakes the field rather than replacing the sheet with
       an error: the thing to fix is right there and stays in focus. */
    .dp-wrong { animation:dpShake .34s cubic-bezier(.36,.07,.19,.97) both; }
    @keyframes dpShake {
        10%, 90% { transform:translateX(-1px); }
        20%, 80% { transform:translateX(2px); }
        30%, 50%, 70% { transform:translateX(-4px); }
        40%, 60% { transform:translateX(4px); }
    }
    html.dark .dp-lock { background:#25311b; color:#bfe19a; }
    @media (prefers-reduced-motion: reduce) { .dp-wrong { animation:none; } }
</style>

<script>
    (function doorPass() {
        const sheet = document.getElementById('doorPassSheet');
        if (!sheet) return;
        const input = document.getElementById('doorPassInput');
        const say = document.getElementById('doorPassSay');
        const go = document.getElementById('doorPassGo');
        let settle = null;

        // Backing out is an answer too. Whether they used the ✕, the
        // backdrop or Escape, the promise has to end or the caller waits
        // forever on a sheet that is no longer on screen.
        const done = (value) => {
            const f = settle;
            settle = null;
            if (f) f(value);
        };

        window.askForPassword = function askForPassword(roomName) {
            return new Promise((resolve) => {
                settle = resolve;
                input.value = '';
                say.textContent = roomName
                    ? roomName + ' asks for a password.'
                    : 'This discussion asks for a password.';
                window.openSheet('doorPassSheet');
                setTimeout(() => input.focus(), 280);
            });
        };

        /** Say the password was wrong without taking the sheet away. */
        window.passwordWasWrong = function passwordWasWrong() {
            input.classList.remove('dp-wrong');
            void input.offsetWidth;
            input.classList.add('dp-wrong');
            input.select();
        };

        const send = () => {
            const said = input.value.trim();
            if (!said) { window.passwordWasWrong(); return; }
            window.closeSheet('doorPassSheet');
            done(said);
        };

        go?.addEventListener('click', send);
        input?.addEventListener('keydown', (e) => { if (e.key === 'Enter') send(); });
        sheet.addEventListener('sheet:close', () => done(null));
    })();
</script>
