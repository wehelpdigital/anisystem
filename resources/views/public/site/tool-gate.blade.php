{{--
    The email gate on a free tool's answer (2026-10-07): the weed control
    helper on /weeds, the finders on /pests and /diseases.

    Put it inside the wrapper that holds the answer card:
        <div class="tg" data-tool-gate="weeds"> <div class="wc-card">…</div> @include('public.site.tool-gate', ['tool' => 'weeds']) </div>

    Until a name and an email are given, the card's header stays readable
    and its body is blurred under this form. POST /tools/open checks the
    email with Reoon (ToolGateController) and a good one joins the Acumbamail
    list. The browser then remembers the opening for every tool; a signed in
    member never sees the gate. Without JavaScript nothing is hidden.
--}}
@php
    $gateWords = [
        'weeds' => ['See What to Spray at This Age', 'Type your name and email to see what to do first and the active ingredients to spray at this age of your rice.'],
        'pests' => ['See What to Spray', 'Type your name and email to see the pests that fit what you see, and the active ingredients to spray against each one.'],
        'diseases' => ['See What to Spray', 'Type your name and email to see the diseases that fit what you see, and the active ingredients to spray against each one.'],
    ][$tool];
    $gateId = 'tg' . ucfirst($tool);
@endphp
<div class="tg-gate" id="{{ $gateId }}" hidden>
    <form class="tg-panel" novalidate data-tool="{{ $tool }}" aria-labelledby="{{ $gateId }}H">
        <div class="tg-head">
            <span class="tg-lock" aria-hidden="true"><svg fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24"><rect x="4.5" y="10.5" width="15" height="10" rx="2.2"/><path stroke-linecap="round" d="M8 10.5V7.8a4 4 0 018 0v2.7"/></svg></span>
            <h3 id="{{ $gateId }}H">{{ $gateWords[0] }}</h3>
        </div>
        <p class="tg-lead">{{ $gateWords[1] }}</p>
        <label class="tg-field">
            <span>Your name</span>
            <input type="text" name="name" autocomplete="name" maxlength="60" enterkeyhint="next" required>
            <em class="tg-err" role="alert"></em>
        </label>
        <label class="tg-field">
            <span>Email address</span>
            <input type="email" name="email" autocomplete="email" inputmode="email" maxlength="190" enterkeyhint="go" required>
            <em class="tg-err" role="alert"></em>
        </label>
        {{-- A field people never see; a bot that fills it is turned away. --}}
        <label class="tg-trap" aria-hidden="true">Website <input type="text" name="website" tabindex="-1" autocomplete="off"></label>
        <button type="submit" class="btn btn-accent tg-go">
            <span class="tg-go-text">Show the results</span>
            <span class="tg-spin" aria-hidden="true"></span>
        </button>
        <p class="tg-wait" aria-live="polite"></p>
        <p class="tg-fine">
            We send farm tips by email now and then, and you can unsubscribe any time. We never share your email.
            <a href="{{ url('/legal/privacy') }}">Privacy</a>
        </p>
    </form>
    <div class="tg-thanks" hidden>
        <span aria-hidden="true"><svg fill="none" stroke="currentColor" stroke-width="3" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" d="M5 13l4 4L19 7"/></svg></span>
        <b></b>
    </div>
</div>

@once
@push('head')
<style>
    /* The gate: the card's header stays clear, its body blurs under a form. */
    .tg { position: relative; }
    .tg-gate { position: absolute; left: 0; right: 0; bottom: 0; top: var(--tg-top, 0px); z-index: 2; display: grid; place-items: start center; padding: 1.4rem 1rem 1rem;
        border-radius: 0 0 1.3rem 1.3rem; background: linear-gradient(180deg, rgb(255 255 255 / .35), rgb(247 250 243 / .78));
        transition: opacity .28s cubic-bezier(.22,1,.36,1), transform .28s cubic-bezier(.22,1,.36,1); }
    .tg-gate[hidden] { display: none; }
    /* Locked, the body is a fixed window: tall enough for the form, never a
       long blurred list to scroll past. */
    .tg.is-locked .wc-card .wc-body { box-sizing: border-box; height: 30rem; overflow: hidden; filter: blur(6px); opacity: .6; pointer-events: none; user-select: none; }
    .tg .wc-card .wc-body { transition: filter .5s cubic-bezier(.22,1,.36,1), opacity .5s cubic-bezier(.22,1,.36,1); }
    .tg.is-opening .tg-gate { opacity: 0; transform: translateY(.6rem) scale(.98); pointer-events: none; }
    .tg-panel { width: 100%; max-width: 23.5rem; display: grid; gap: .65rem; padding: 1.2rem 1.25rem 1.05rem; border-radius: 1.2rem; background: #fff;
        border: 1px solid #e1edd3; box-shadow: 0 28px 60px -30px rgb(20 33 12 / .55), 0 2px 6px rgb(20 33 12 / .06); text-align: left; }
    .tg-head { display: flex; align-items: center; gap: .7rem; }
    .tg-lock { flex: none; display: grid; place-items: center; width: 2.4rem; height: 2.4rem; border-radius: .85rem; color: #2d5016; background: #e4efd4; }
    .tg-lock svg { width: 1.2rem; height: 1.2rem; }
    .tg-panel h3 { font-family: var(--font-heading); font-weight: 800; font-size: 1.2rem; line-height: 1.2; color: #14210c; }
    .tg-lead { font-size: .88rem; line-height: 1.5; color: #4b5563; }
    .tg-field { display: grid; gap: .3rem; }
    .tg-field > span { font-size: .8rem; font-weight: 700; color: #2b3a20; }
    .tg-field input { width: 100%; border-radius: .8rem; border: 1px solid #cfdcc2; background: #fbfdf8; padding: .62rem .85rem; font-size: 1rem; color: #14210c;
        transition: border-color .28s cubic-bezier(.22,1,.36,1), box-shadow .28s cubic-bezier(.22,1,.36,1); }
    .tg-field input:focus { outline: none; border-color: #5a8f2e; box-shadow: 0 0 0 3px rgb(90 143 46 / .2); background: #fff; }
    .tg-field input[aria-invalid="true"] { border-color: #dc2626; box-shadow: 0 0 0 3px rgb(220 38 38 / .12); }
    .tg-err { font-style: normal; font-size: .8rem; line-height: 1.4; color: #b91c1c; min-height: 0; }
    .tg-err:empty { display: none; }
    .tg-trap { position: absolute; left: -9999px; width: 1px; height: 1px; overflow: hidden; }
    .tg-go { position: relative; width: 100%; justify-content: center; margin-top: .2rem; }
    .tg-spin { display: none; width: 1.05rem; height: 1.05rem; margin-left: .55rem; border-radius: 999px; border: 2px solid rgb(26 26 26 / .25); border-top-color: #1a1a1a;
        animation: tgSpin .8s linear infinite; }
    .tg-go.is-busy .tg-spin { display: inline-block; }
    @keyframes tgSpin { to { transform: rotate(360deg); } }
    .tg-wait { font-size: .8rem; color: #4b5563; }
    .tg-wait:empty { display: none; }
    .tg-fine { font-size: .74rem; line-height: 1.5; color: #6b7280; }
    .tg-fine a { color: #3d6823; font-weight: 700; text-decoration: underline; text-underline-offset: 2px; }
    .tg-thanks { display: grid; justify-items: center; gap: .6rem; padding: 1.5rem; border-radius: 1.2rem; background: #fff; border: 1px solid #e1edd3;
        box-shadow: 0 28px 60px -30px rgb(20 33 12 / .55); animation: tgPop .34s cubic-bezier(.22,1,.36,1) both; }
    .tg-thanks[hidden] { display: none; }
    .tg-thanks span { display: grid; place-items: center; width: 2.8rem; height: 2.8rem; border-radius: 999px; color: #fff; background: #3d6823; }
    .tg-thanks svg { width: 1.4rem; height: 1.4rem; }
    .tg-thanks b { font-family: var(--font-heading); font-size: 1.05rem; color: #14210c; text-align: center; }
    @keyframes tgPop { from { opacity: 0; transform: scale(.94); } }
    @media (max-width: 639.98px) {
        .tg.is-locked .wc-card .wc-body { height: 33rem; }
        .tg-gate { padding: .75rem; }
        .tg-panel { padding: 1.15rem 1rem 1rem; }
    }
    @media (prefers-reduced-motion: reduce) {
        .tg-gate, .tg .wc-card .wc-body, .tg-field input { transition: none; }
        .tg-thanks { animation: none; }
        .tg-spin { animation-duration: 2.4s; }
    }
</style>
@endpush
@push('scripts')
<script>
(() => {
    /* One opening serves every tool in this browser. A storage that throws
       (a private window) just means the form shows again next visit. */
    const KEY = 'anee.toolsOpen';
    const member = @json(auth()->check());
    const read = () => { try { return !!localStorage.getItem(KEY); } catch (_) { return false; } };
    const save = (name) => { try { localStorage.setItem(KEY, JSON.stringify({ at: Date.now(), name })); } catch (_) {} };
    let open = member || read();
    const reduce = () => matchMedia('(prefers-reduced-motion: reduce)').matches;
    const EMAIL = /^[^\s@]+@[^\s@]+\.[^\s@]{2,}$/;

    document.querySelectorAll('.tg[data-tool-gate]').forEach((wrap) => {
        const gate = wrap.querySelector('.tg-gate');
        const card = wrap.querySelector('.wc-card');
        const out = wrap.closest('[aria-live]');
        if (!gate || !card || open) return;

        // The header (the age, the crop, the count) stays readable: the gate
        // starts where the card's body does, after every redraw.
        const place = () => { const h = card.querySelector('.wc-when'); wrap.style.setProperty('--tg-top', (h ? h.offsetHeight : 0) + 'px'); };
        const watch = new MutationObserver(place);
        watch.observe(card, { childList: true });
        window.addEventListener('resize', place);
        wrap.classList.add('is-locked');
        card.inert = true;
        if (out) out.setAttribute('aria-live', 'off');
        gate.hidden = false;
        place();

        const form = gate.querySelector('form');
        const btn = form.querySelector('.tg-go');
        const wait = form.querySelector('.tg-wait');
        const field = (n) => form.elements[n];
        const say = (n, msg) => {
            const input = field(n);
            input.setAttribute('aria-invalid', msg ? 'true' : 'false');
            input.closest('.tg-field').querySelector('.tg-err').textContent = msg || '';
        };
        ['name', 'email'].forEach((n) => field(n).addEventListener('input', () => say(n, '')));

        const unlock = (first) => {
            open = true;
            const thanks = gate.querySelector('.tg-thanks');
            thanks.querySelector('b').textContent = first ? 'Thank you, ' + first + '. Here are your results.' : 'Here are your results.';
            form.hidden = true;
            thanks.hidden = false;
            setTimeout(() => {
                wrap.classList.add('is-opening');
                wrap.classList.remove('is-locked');
                card.inert = false;
                if (out) out.setAttribute('aria-live', 'polite');
                setTimeout(() => { gate.hidden = true; wrap.classList.remove('is-opening'); watch.disconnect(); }, reduce() ? 0 : 320);
            }, reduce() ? 300 : 900);
            // Every other gate on the page opens with this one.
            document.querySelectorAll('.tg.is-locked').forEach((w) => { if (w !== wrap) w.dispatchEvent(new CustomEvent('tg:open')); });
        };
        wrap.addEventListener('tg:open', () => {
            wrap.classList.remove('is-locked'); card.inert = false; gate.hidden = true; watch.disconnect();
            if (out) out.setAttribute('aria-live', 'polite');
        });

        form.addEventListener('submit', async (e) => {
            e.preventDefault();
            if (btn.disabled) return;
            const name = field('name').value.trim().replace(/\s+/g, ' ');
            const email = field('email').value.trim();
            let bad = false;
            if (name.length < 2 || !/\p{L}/u.test(name)) { say('name', 'Please type your name.'); bad = true; }
            if (!EMAIL.test(email)) { say('email', email ? 'That email address does not look right. Please check it.' : 'Please type your email address.'); bad = true; }
            if (bad) { form.querySelector('[aria-invalid="true"]')?.focus(); return; }

            btn.disabled = true;
            btn.classList.add('is-busy');
            btn.querySelector('.tg-go-text').textContent = 'Checking your email';
            const slow = setTimeout(() => { wait.textContent = 'Still checking. This can take a few seconds.'; }, 4000);
            try {
                const res = await window.api(@json(route('tools.open')), { method: 'POST', body: { name, email, tool: form.dataset.tool, website: field('website').value } });
                save(name.split(' ')[0]);
                unlock(res?.data?.firstName || name.split(' ')[0]);
            } catch (err) {
                const errs = err.errors || {};
                if (errs.name) say('name', errs.name[0]);
                if (errs.email) say('email', errs.email[0]);
                if (!errs.name && !errs.email) say('email', err.message || 'Something went wrong. Please try again.');
                form.querySelector('[aria-invalid="true"]')?.focus();
            } finally {
                clearTimeout(slow);
                wait.textContent = '';
                btn.disabled = false;
                btn.classList.remove('is-busy');
                btn.querySelector('.tg-go-text').textContent = 'Show the results';
            }
        });
    });
})();
</script>
@endpush
@endonce
