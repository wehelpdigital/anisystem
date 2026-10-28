{{-- ANEE AUTO, THE DOOR (2026-10-12).

     Any button with [data-auto-start] opens it. Two steps in one card:
     how many lots (with the word on why every lot farmed this season must be
     in it), then the price confirmed as final. The credits are taken when
     the season is made, and the wizard opens on it. @once: a page that has
     two buttons pays for one door. --}}
@once
<style>
    .aus-veil { position: fixed; inset: 0; z-index: 210; display: flex; align-items: center; justify-content: center; padding: 1rem;
        background: rgb(12 20 8 / .6); backdrop-filter: blur(3px); -webkit-backdrop-filter: blur(3px); opacity: 0; transition: opacity .28s cubic-bezier(.22,1,.36,1); }
    .aus-veil.is-on { opacity: 1; }
    .aus-veil[hidden] { display: none; }
    .aus-card { position: relative; width: 100%; max-width: 27rem; overflow: hidden; border-radius: 1.4rem; background: var(--color-white);
        box-shadow: 0 30px 70px -30px rgb(0 0 0 / .6); transform: translateY(14px) scale(.98); transition: transform .34s cubic-bezier(.22,1,.36,1); }
    .aus-veil.is-on .aus-card { transform: none; }
    .aus-head { position: relative; padding: 1.1rem 1.2rem 1rem; color: #fff; background: radial-gradient(120% 160% at 100% 0%, #6aa33a 0%, #2d5016 58%, #142509 100%); }
    .aus-head h3 { font-family: var(--font-heading); font-weight: 800; font-size: 1.2rem; }
    .aus-head p { margin-top: .2rem; font-size: .82rem; line-height: 1.5; color: #d6e8c2; }
    .aus-face { position: absolute; right: .9rem; top: .8rem; width: 3.2rem; height: 3.2rem; border-radius: 999px; background: rgb(255 255 255 / .14); padding: .2rem; animation: ausBob 3.6s ease-in-out infinite; }
    .aus-face img { width: 100%; height: 100%; object-fit: contain; }
    @keyframes ausBob { 50% { transform: translateY(-4px) rotate(3deg); } }
    .aus-x { position: absolute; right: .6rem; bottom: .6rem; }
    .aus-body { padding: 1.1rem 1.2rem 1.2rem; }
    .aus-pane[hidden] { display: none; }
    .aus-pane { animation: ausIn .32s cubic-bezier(.22,1,.36,1) both; }
    @keyframes ausIn { from { opacity: 0; transform: translateX(14px); } }
    .aus-lab { font-size: .8rem; font-weight: 800; color: var(--color-gray-700); }
    .aus-count { display: flex; align-items: center; justify-content: center; gap: 1rem; margin: .7rem 0 .4rem; }
    .aus-count button { width: 3rem; height: 3rem; border-radius: 999px; font-size: 1.5rem; font-weight: 800; color: var(--color-brand-700); background: var(--color-brand-50, #f3f8ec); border: 1px solid #cfe3b8;
        transition: transform .2s cubic-bezier(.22,1,.36,1); }
    .aus-count button:active { transform: scale(.92); }
    .aus-count button:disabled { opacity: .35; }
    .aus-count b { min-width: 3.5rem; text-align: center; font-family: var(--font-heading); font-size: 2.6rem; font-weight: 800; color: var(--color-gray-900); }
    .aus-count small { display: block; text-align: center; font-size: .74rem; font-weight: 700; color: var(--color-gray-500); }
    .aus-warn { display: flex; gap: .6rem; margin-top: .8rem; padding: .75rem .85rem; border-radius: .95rem; font-size: .8rem; line-height: 1.5; color: #6b4a00; background: #fff6dd; border: 1px solid #f3d98f; }
    .aus-warn b { color: #4d3500; }
    .aus-price { margin: .3rem 0 .8rem; padding: .9rem 1rem; border-radius: 1rem; background: linear-gradient(115deg, #f3f8ec, #e4efd4); border: 1px solid #cfe3b8; }
    .aus-price b { display: block; font-family: var(--font-heading); font-size: 1.6rem; font-weight: 800; color: #2d5016; }
    .aus-price span { font-size: .8rem; color: #3d5226; }
    .aus-price small { display: block; margin-top: .35rem; font-size: .74rem; line-height: 1.45; color: #4b6236; }
    .aus-final { display: flex; gap: .6rem; align-items: flex-start; font-size: .82rem; line-height: 1.5; color: var(--color-gray-700); cursor: pointer; }
    .aus-final input { margin-top: .2rem; width: 1.1rem; height: 1.1rem; flex: none; accent-color: var(--color-brand-600); }
    .aus-bal { margin-top: .6rem; font-size: .78rem; color: var(--color-gray-500); }
    .aus-bal.is-short { color: #b45309; font-weight: 700; }
    .aus-nav { display: flex; gap: .5rem; margin-top: 1rem; }
    .aus-nav .btn { flex: 1; }
    .aus-go { position: relative; overflow: hidden; }
    .aus-go::after { content: ''; position: absolute; inset: 0; background: linear-gradient(100deg, transparent 30%, rgb(255 255 255 / .35) 50%, transparent 70%); transform: translateX(-100%); animation: ausShine 2.8s ease-in-out infinite; }
    @keyframes ausShine { 60%, 100% { transform: translateX(100%); } }
    .aus-why { margin-top: .7rem; font-size: .8rem; color: #b45309; font-weight: 700; }
    html.dark .aus-card { background: #161c12; }
    html.dark .aus-warn { background: #2a2210; border-color: #5b4716; color: #f3d9a4; }
    html.dark .aus-warn b { color: #fde7b0; }
    html.dark .aus-price { background: #17220f; border-color: #2b3a1c; }
    html.dark .aus-price b { color: #c5e09f; } html.dark .aus-price span { color: #b7c9a3; } html.dark .aus-price small { color: #a9bd94; }
    html.dark .aus-count b { color: #eef3e8; }
    html.dark .aus-count button { background: #1c2616; border-color: #2b3a1c; color: #c5e09f; }
    @media (prefers-reduced-motion: reduce) { .aus-veil, .aus-card, .aus-pane, .aus-face, .aus-go::after { animation: none; transition: none; } }
</style>
<div class="aus-veil" id="ausVeil" hidden role="dialog" aria-modal="true" aria-labelledby="ausTitle">
    <div class="aus-card">
        <div class="aus-head">
            <span class="aus-face"><img src="{{ \App\Support\AneeEmoji::url('salute') }}" alt=""></span>
            <h3 id="ausTitle">Anee Auto</h3>
            <p>Anee plans the whole season and keeps it on track: the weather, your workers, the fertilizer at the right moments. You follow, and tell her when a day cannot be done.</p>
        </div>
        <div class="aus-body">
            <div class="aus-pane" id="ausPaneCount">
                <span class="aus-lab">How many lots will Anee run this season?</span>
                <div class="aus-count">
                    <button type="button" id="ausMinus" aria-label="One less">−</button>
                    <div><b id="ausN">1</b><small id="ausNSay">lot</small></div>
                    <button type="button" id="ausPlus" aria-label="One more">+</button>
                </div>
                <div class="aus-warn"><span aria-hidden="true">⚠️</span><span><b>Put in every lot you will farm this season.</b> If Anee runs one lot while you manage the others by hand, she cannot see them: the same workers and the same days get booked twice, and the schedules clash.</span></div>
                <p class="aus-why" id="ausCapSay" hidden></p>
                <div class="aus-nav">
                    <button type="button" class="btn btn-white" data-aus-close>Not now</button>
                    <button type="button" class="btn btn-primary" id="ausNext">Continue</button>
                </div>
            </div>
            <div class="aus-pane" id="ausPaneConfirm" hidden>
                <div class="aus-price"><b id="ausTotal">{{ number_format(\App\Support\AiPrices::of('auto')) }} credits</b><span id="ausEach">{{ number_format(\App\Support\AiPrices::of('auto')) }} credits per lot, for the whole season</span><small>Each lot includes 150 questions and 45 readings of the field for the season. Past that, one more question is {{ \App\Support\AiPrices::of('autoAsk') }} credits and one more reading {{ \App\Support\AiPrices::of('autoObserve') }}.</small></div>
                <label class="aus-final"><input type="checkbox" id="ausAgree"><span>I understand the credits are taken now and <b>cannot be returned</b>, even if I stop. Anee keeps everything I type, so I can finish setting it up any time.</span></label>
                <p class="aus-bal" id="ausBal"></p>
                <p class="aus-why" id="ausWhy" hidden></p>
                <div class="aus-nav">
                    <button type="button" class="btn btn-white" id="ausBack">Back</button>
                    <button type="button" class="btn btn-primary aus-go" id="ausGo" disabled>Start Anee Auto</button>
                </div>
            </div>
        </div>
    </div>
</div>
@push('scripts')
<script>
(() => {
    const $ = (id) => document.getElementById(id);
    const veil = $('ausVeil');
    if (!veil || veil.dataset.ready) return;
    veil.dataset.ready = '1';
    const QUOTE = @json(route('auto.quote')), START = @json(route('auto.start')), BUY = @json(route('ai.credits') . '?tab=buy');
    let Q = null, n = 1;
    const fmt = (v) => Number(v).toLocaleString();
    const paint = () => {
        const cap = Q && Q.lotCap != null ? Q.lotCap : 20;
        $('ausN').textContent = n; $('ausNSay').textContent = n === 1 ? 'lot' : 'lots';
        $('ausMinus').disabled = n <= 1; $('ausPlus').disabled = n >= Math.min(20, cap);
        $('ausCapSay').hidden = !(Q && Q.lotCap != null && n >= Q.lotCap);
        if (Q && Q.lotCap != null) $('ausCapSay').textContent = 'Your plan allows up to ' + Q.lotCap + ' lot' + (Q.lotCap === 1 ? '' : 's') + ' in a season.';
        const per = Q ? Q.perLot : {{ (int) \App\Support\AiPrices::of('auto') }}, total = per * n;
        $('ausTotal').textContent = fmt(total) + ' credits';
        $('ausEach').textContent = fmt(per) + ' credits per lot, for the whole season';
        const short = Q && !Q.unlimited && Q.balance < total;
        $('ausBal').innerHTML = Q ? (Q.unlimited ? 'Your account has unlimited credits.' : 'You have ' + fmt(Math.floor(Q.balance)) + ' credits.' + (short ? ' <a class="underline" href="' + BUY + '">Buy credits</a>' : '')) : '';
        $('ausBal').classList.toggle('is-short', !!short);
        $('ausGo').disabled = !$('ausAgree').checked || !!short || !(Q && Q.canUse);
    };
    const pane = (which) => { $('ausPaneCount').hidden = which !== 'count'; $('ausPaneConfirm').hidden = which !== 'confirm'; };
    const open = async () => {
        n = 1; $('ausAgree').checked = false; $('ausWhy').hidden = true; pane('count');
        veil.hidden = false; void veil.offsetWidth; veil.classList.add('is-on');
        document.documentElement.style.overflow = 'hidden';
        try { Q = (await window.api(QUOTE)).data; } catch (e) { Q = null; }
        if (Q && !Q.canUse) { $('ausWhy').hidden = false; $('ausWhy').textContent = Q.whyNot || ''; }
        if (Q && !Q.canSchedule) { $('ausWhy').hidden = false; $('ausWhy').textContent = 'Your plan allows no more active seasons. Finish or archive one first.'; }
        paint();
    };
    const close = () => { veil.classList.remove('is-on'); document.documentElement.style.overflow = ''; setTimeout(() => { veil.hidden = true; }, 280); };
    document.addEventListener('click', (e) => { if (e.target.closest('[data-auto-start]')) { e.preventDefault(); open(); } });
    veil.addEventListener('click', (e) => { if (e.target === veil || e.target.closest('[data-aus-close]')) close(); });
    document.addEventListener('keydown', (e) => { if (e.key === 'Escape' && !veil.hidden) close(); });
    $('ausMinus').addEventListener('click', () => { n = Math.max(1, n - 1); paint(); });
    $('ausPlus').addEventListener('click', () => { n += 1; paint(); });
    $('ausNext').addEventListener('click', () => { pane('confirm'); if (Q && (!Q.canUse || !Q.canSchedule)) $('ausWhy').hidden = false; paint(); });
    $('ausBack').addEventListener('click', () => pane('count'));
    $('ausAgree').addEventListener('change', paint);
    $('ausGo').addEventListener('click', async () => {
        $('ausGo').disabled = true; $('ausGo').textContent = 'Starting…';
        try {
            const r = await window.api(START, { method: 'POST', body: { lots: n, agree: true } });
            window.toast?.(r.message, 'success');
            location.href = r.data.url;
        } catch (err) {
            $('ausWhy').hidden = false; $('ausWhy').textContent = err.message;
            $('ausGo').textContent = 'Start Anee Auto'; paint();
        }
    });
})();
</script>
@endpush
@endonce
