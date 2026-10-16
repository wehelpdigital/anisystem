{{-- The field helpers offer (2026-10-14, the owner): the Weed Control
     Helper, the Pest Finder, the Disease Finder, the Nutrient Deficiency
     Finder (2026-10-16) and the Variety Finder are locked on the free plan
     and open on Libre + Anee and every plan above it. No add-on since
     2026-10-18 (the owner: "they need to purchase libre plus to have access
     to these tools"): the sheet sells the plan. Used as a sheet on the
     dashboard (any tile with [data-field-pack] opens it) and as the body of
     the locked tool page. $mode: 'sheet' (default) or 'page'. App\Support\FieldPack. --}}
@php
    $fpMode = $mode ?? 'sheet';
    $fpPlanItem = \App\Support\ManualPay::item(\App\Support\FieldPack::PLAN . ':month');
    $fpPrice = $fpPlanItem ? \App\Support\Region::money($fpPlanItem['price'], 0) : null;
    $fpPlanName = \App\Support\Tier::planName(\App\Support\FieldPack::PLAN);
    $fpTools = [
        ['weed', 'Weed Control Helper', 'What to do first and what to spray at your crop\'s age'],
        ['pest', 'Pest Finder', 'From the damage you see to the pest and what controls it'],
        ['disease', 'Disease Finder', 'From the signs you see to the disease and what to do'],
        ['nutrient', 'Nutrient Deficiency Finder', 'From the leaves you see to the missing nutrient and its fertilizer'],
        ['rice-variety', 'Variety Finder', 'Every registered variety, compared for your farm'],
    ];
    $fpWorker = \App\Support\WorkerContext::inWorkerContext();
@endphp
@once
<style>
    .fp-card { position: relative; overflow: hidden; border-radius: 1.5rem; background: var(--color-white); }
    .fp-top { position: relative; padding: 1.3rem 1.25rem 1.15rem; color: #fff; background: radial-gradient(120% 140% at 100% 0%, #6aa33a 0%, #2d5016 55%, #142509 100%); }
    .fp-top::after { content: ''; position: absolute; inset: 0; pointer-events: none; opacity: .45; background-image: radial-gradient(circle at 18% 30%, rgb(255 255 255 / .5) 0 1px, transparent 1.6px), radial-gradient(circle at 72% 70%, rgb(255 255 255 / .35) 0 1px, transparent 1.6px); background-size: 120px 110px, 170px 150px; animation: fpDrift 30s linear infinite; }
    @keyframes fpDrift { to { background-position: 120px 110px, -170px 150px; } }
    .fp-kick { display: inline-flex; align-items: center; gap: .35rem; padding: .2rem .6rem; border-radius: 999px; font-size: .66rem; font-weight: 800; letter-spacing: .06em; text-transform: uppercase; color: #2b3a1c; background: #f5c518; }
    .fp-top h3 { margin-top: .55rem; font-family: var(--font-heading); font-weight: 800; font-size: 1.3rem; line-height: 1.2; }
    .fp-top p { margin-top: .35rem; font-size: .86rem; line-height: 1.55; color: #d6e8c2; }
    .fp-body { padding: 1rem 1.15rem 1.2rem; display: grid; gap: .9rem; }
    .fp-tools { display: grid; gap: .45rem; }
    .fp-tool { display: flex; align-items: center; gap: .7rem; padding: .55rem .65rem; border-radius: .9rem; background: var(--color-gray-50); border: 1px solid var(--color-gray-100);
        opacity: 0; transform: translateY(6px); animation: fpIn .42s cubic-bezier(.22,1,.36,1) forwards; animation-delay: calc(var(--i) * 70ms + .08s); }
    @keyframes fpIn { to { opacity: 1; transform: none; } }
    .fp-tool img { flex: none; width: 2.2rem; height: 2.2rem; padding: .3rem; border-radius: .75rem; background: #fff; box-shadow: 0 6px 14px -10px rgb(20 33 12 / .5); }
    .fp-tool b { display: block; font-size: .86rem; font-weight: 800; color: var(--color-gray-900); }
    .fp-tool small { display: block; font-size: .74rem; line-height: 1.35; color: var(--color-gray-500); }
    .fp-price { display: flex; align-items: baseline; justify-content: center; gap: .35rem; }
    .fp-price b { font-family: var(--font-heading); font-size: 2.1rem; font-weight: 800; color: #2d5016; line-height: 1; }
    .fp-price span { font-size: .9rem; font-weight: 700; color: var(--color-gray-500); }
    .fp-day { margin-top: -.55rem; text-align: center; font-size: .78rem; font-weight: 700; color: #6b7f5a; }
    .fp-acts { display: grid; gap: .5rem; }
    .fp-acts .btn { justify-content: center; width: 100%; text-align: center; text-wrap: balance; line-height: 1.25; }
    /* The important call to action is yellow (2026-10-17, the owner), its arrow nudging. */
    .fp-go { display: inline-flex; align-items: center; gap: .5rem; color: #3b2f00 !important; background: #f5c518 !important; border: 0 !important; font-weight: 800;
        box-shadow: 0 12px 24px -14px rgb(180 140 0 / .95), inset 0 -2px 0 rgb(0 0 0 / .08); transition: background-color .28s cubic-bezier(.22,1,.36,1), transform .28s cubic-bezier(.22,1,.36,1); }
    .fp-go:hover { background: #ffd23f !important; }
    .fp-go:active { transform: scale(.98); }
    .fp-go svg { flex: none; width: 1.05rem; height: 1.05rem; animation: fpNudge 1.4s cubic-bezier(.22,1,.36,1) infinite; }
    @keyframes fpNudge { 0%, 100% { transform: translateX(-2px); } 50% { transform: translateX(4px); } }
    @media (prefers-reduced-motion: reduce) { .fp-go svg { animation: none; } }
    .fp-or { text-align: center; font-size: .78rem; line-height: 1.5; color: var(--color-gray-500); }
    .fp-or a { font-weight: 800; color: var(--color-brand-700, #3d6823); text-decoration: underline; text-underline-offset: 3px; }
    html.dark .fp-card { background: #141a10; } html.dark .fp-tool { background: #1a2114; border-color: #2b3a1c; } html.dark .fp-tool b { color: #eef3e8; } html.dark .fp-tool img { background: #22301a; }
    html.dark .fp-price b { color: #a5c97e; }
    /* The sheet */
    .fp-veil { position: fixed; inset: 0; z-index: 230; display: flex; align-items: flex-end; justify-content: center; padding: 0; background: rgb(12 20 8 / .55);
        backdrop-filter: blur(3px); -webkit-backdrop-filter: blur(3px); opacity: 0; transition: opacity .28s cubic-bezier(.22,1,.36,1); }
    .fp-veil.is-on { opacity: 1; } .fp-veil[hidden] { display: none; }
    .fp-veil .fp-card { width: 100%; max-width: 28rem; max-height: 92vh; overflow-y: auto; border-radius: 1.5rem 1.5rem 0 0; transform: translateY(24px); transition: transform .34s cubic-bezier(.22,1,.36,1); }
    .fp-veil.is-on .fp-card { transform: none; }
    @media (min-width: 640px) { .fp-veil { align-items: center; padding: 1rem; } .fp-veil .fp-card { border-radius: 1.5rem; } }
    .fp-x { position: absolute; right: .7rem; top: .7rem; z-index: 2; width: 2rem; height: 2rem; border-radius: 999px; display: grid; place-items: center; color: #fff; background: rgb(255 255 255 / .16); }
    .fp-x svg { width: 1rem; height: 1rem; }
    @media (prefers-reduced-motion: reduce) { .fp-top::after, .fp-tool { animation: none; opacity: 1; transform: none; } .fp-veil, .fp-veil .fp-card { transition: none; } }
</style>
@endonce
@php
    $fpInner = function () use ($fpPrice, $fpPlanName, $fpPlanItem, $fpTools, $fpWorker, $fpMode) {
        ob_start(); ?>
        <div class="fp-top">
            <?php if ($fpMode === 'sheet'): ?><button type="button" class="fp-x" data-fp-close aria-label="Close"><svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.6"><path stroke-linecap="round" d="M6 6l12 12M18 6L6 18"/></svg></button><?php endif; ?>
            <span class="fp-kick">Field helpers</span>
            <h3>Know what is in your field, and what to do about it</h3>
            <p>The field helpers come with <?= e($fpPlanName) ?>, for every crop you grow.</p>
        </div>
        <div class="fp-body">
            <div class="fp-tools">
                <?php foreach ($fpTools as $i => [$ico, $name, $line]): ?>
                    <div class="fp-tool" style="--i: <?= $i ?>"><img src="<?= e(asset('images/icons/' . $ico . '.svg')) ?>" alt=""><span><b><?= e($name) ?></b><small><?= e($line) ?></small></span></div>
                <?php endforeach; ?>
            </div>
            <?php if ($fpWorker): ?>
                <p class="fp-or">The farm you work on does not have the field helpers yet. Ask the farm owner to upgrade to <?= e($fpPlanName) ?>.</p>
            <?php else: ?>
                <?php if ($fpPrice): ?>
                    <div class="fp-price"><b><?= e($fpPrice) ?></b><span>a month, with Anee too</span></div>
                <?php endif; ?>
                <div class="fp-acts">
                    <a href="<?= e($fpPlanItem ? route('checkout', ['item' => \App\Support\FieldPack::PLAN . ':month']) : route('account.subscription')) ?>" class="btn btn-lg fp-go">Upgrade to <?= e($fpPlanName) ?><svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.6" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true"><path d="M5 12h14M13 6l6 6-6 6"/></svg></a>
                </div>
                <p class="fp-or"><?= e($fpPlanName) ?> keeps everything you have on Libre and adds the field helpers and Anee. <a href="<?= e(route('account.subscription')) ?>">See every plan</a></p>
            <?php endif; ?>
        </div>
        <?php return ob_get_clean();
    };
@endphp
@if ($fpMode === 'page')
    <div class="fp-card" style="box-shadow: 0 30px 70px -40px rgb(20 33 12 / .55); border: 1px solid var(--color-gray-100)">{!! $fpInner() !!}</div>
@else
    <div class="fp-veil" id="fpVeil" hidden role="dialog" aria-modal="true" aria-label="Field helpers">
        <div class="fp-card">{!! $fpInner() !!}</div>
    </div>
    @once
    <script>
    (() => {
        /* Any [data-field-pack] (a locked helper tile) opens the offer instead of the page. */
        const veil = document.getElementById('fpVeil');
        if (!veil) return;
        const open = () => { veil.hidden = false; requestAnimationFrame(() => veil.classList.add('is-on')); };
        const shut = () => { veil.classList.remove('is-on'); setTimeout(() => { veil.hidden = true; }, 300); };
        document.addEventListener('click', (e) => {
            const t = e.target.closest('[data-field-pack]');
            if (t) { e.preventDefault(); e.stopPropagation(); open(); return; }
            if (e.target === veil || e.target.closest('[data-fp-close]')) shut();
        }, true);
        document.addEventListener('keydown', (e) => { if (e.key === 'Escape' && !veil.hidden) shut(); });
        window.aneeFieldPack = open;
    })();
    </script>
    @endonce
@endif
