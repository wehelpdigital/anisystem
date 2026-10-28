{{-- Anee misses you (2026-10-28, the owner: "if user is not active in the anee auto, user should be receiving email
     asking user reminder ... and a notice in the dashboard"): a running Anee Auto season quiet for a couple of days,
     with work waiting, as a card at the top of the dashboard (App\Support\AutoNudge::forDashboard). --}}
@php $idle = \App\Support\WorkerContext::inWorkerContext() ? [] : rescue(fn () => \App\Support\AutoNudge::forDashboard((int) auth()->id()), [], false); @endphp
@if ($idle)
<style>
    .ain { position: relative; overflow: hidden; display: flex; gap: .9rem; align-items: center; padding: .95rem 1rem; border-radius: 1.2rem; color: #3b2f00;
        background: linear-gradient(135deg, #fff6d6, #ffe9a8); border: 1px solid #f3d98f; box-shadow: 0 16px 34px -26px rgb(120 90 0 / .7); animation: ainIn .5s cubic-bezier(.22,1,.36,1) both; }
    .ain + .ain { margin-top: .6rem; }
    .ain img { flex: none; width: 3.2rem; height: 3.2rem; border-radius: .9rem; background: #fff; padding: .15rem; animation: ainWave 2.4s ease-in-out infinite; transform-origin: 70% 90%; }
    .ain > div { flex: 1; min-width: 0; }
    .ain b { display: block; font-size: .95rem; font-weight: 800; line-height: 1.3; }
    .ain p { margin: .15rem 0 0; font-size: .8rem; line-height: 1.45; color: #6b4a00; }
    .ain a { flex: none; padding: .6rem 1rem; border-radius: 999px; font-size: .85rem; font-weight: 800; color: #fff; background: #4a7c2a; box-shadow: 0 10px 20px -12px rgb(45 80 22 / .8); transition: transform .28s cubic-bezier(.22,1,.36,1); }
    .ain a:hover { transform: translateY(-2px); }
    @media (max-width: 560px) { .ain { flex-wrap: wrap; } .ain a { width: 100%; text-align: center; } }
    @keyframes ainIn { from { opacity: 0; transform: translateY(8px); } }
    @keyframes ainWave { 0%, 60%, 100% { transform: none; } 70% { transform: rotate(-10deg); } 80% { transform: rotate(8deg); } 90% { transform: rotate(-4deg); } }
    @media (prefers-reduced-motion: reduce) { .ain, .ain img { animation: none; } .ain a { transition: none; } }
    html.dark .ain { color: #fde68a; background: linear-gradient(135deg, #2a2210, #1f1a0b); border-color: #5b4716; } html.dark .ain p { color: #f3d9a4; }
</style>
<div>
    @foreach ($idle as $s)
        <div class="ain" role="status">
            <img src="{{ \App\Support\AneeEmoji::url('wave') }}" alt="">
            <div>
                <b>Anee misses you on {{ $s['title'] }}</b>
                <p>{{ $s['days'] }} {{ $s['days'] === 1 ? 'day' : 'days' }} without a word, and {{ $s['waiting'] }} {{ $s['waiting'] === 1 ? 'task is' : 'tasks are' }} waiting{{ $s['next'] ? ', like ' . mb_strtolower($s['next']) : '' }}. Tell her what was done and she plans the next days around it.</p>
            </div>
            <a href="{{ $s['url'] }}">Open the season</a>
        </div>
    @endforeach
</div>
@endif
