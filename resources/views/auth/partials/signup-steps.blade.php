{{-- The three steps of a signup, on the form, the "check your inbox" page and the welcome page (2026-11-16, the
     owner: "make the registration page experience better"). $at: 1, 2 or 3, the step this page is. $reading: they
     came from a members' guide, so the last step is going back to it. --}}
@php
    $at = (int) ($at ?? 1);
    $labels = ['Your details', 'Confirm your email', ! empty($reading) ? 'Back to your guide' : 'Into anee.io'];
@endphp
@once
    <style>
        .su-steps { display: flex; justify-content: center; gap: .25rem; margin: 0 auto 1.25rem; padding: 0; list-style: none; max-width: 24rem; }
        .su-step { position: relative; flex: 1 1 0; display: flex; flex-direction: column; align-items: center; gap: .4rem;
            text-align: center; font-size: .72rem; line-height: 1.25; font-weight: 700; color: #9ca3af; }
        .su-step:not(:last-child)::after { content: ""; position: absolute; top: .8rem; left: calc(50% + 1.05rem); right: calc(-50% + 1.05rem);
            height: 2px; border-radius: 2px; background: #e5e7eb; transition: background .28s cubic-bezier(.22,1,.36,1); }
        .su-step.is-done:not(:last-child)::after { background: #4a7c2a; }
        .su-dot { position: relative; z-index: 1; width: 1.65rem; height: 1.65rem; border-radius: 999px; display: grid; place-items: center;
            font-size: .74rem; font-weight: 800; background: #fff; color: #9ca3af; border: 2px solid #d1d5db;
            transition: background .28s cubic-bezier(.22,1,.36,1), border-color .28s cubic-bezier(.22,1,.36,1), box-shadow .28s cubic-bezier(.22,1,.36,1); }
        .su-dot svg { width: .85rem; height: .85rem; }
        .su-step.is-done { color: #4a7c2a; }
        .su-step.is-done .su-dot { background: #4a7c2a; border-color: #4a7c2a; color: #fff; }
        .su-step.is-now { color: #2d5016; }
        .su-step.is-now .su-dot { border-color: #4a7c2a; color: #2d5016; box-shadow: 0 0 0 4px rgb(74 124 42 / .16); animation: suPulse .28s cubic-bezier(.22,1,.36,1) both; }
        @keyframes suPulse { from { transform: scale(.85); } to { transform: none; } }
        html.dark .su-step { color: #6b7280; }
        html.dark .su-step:not(:last-child)::after { background: #374151; }
        html.dark .su-step.is-done:not(:last-child)::after { background: #6a9a45; }
        html.dark .su-dot { background: #111827; border-color: #4b5563; color: #6b7280; }
        html.dark .su-step.is-done, html.dark .su-step.is-now { color: #a8cc7e; }
        html.dark .su-step.is-done .su-dot { background: #6a9a45; border-color: #6a9a45; color: #0b1407; }
        html.dark .su-step.is-now .su-dot { border-color: #a8cc7e; color: #a8cc7e; }
        @media (prefers-reduced-motion: reduce) { .su-step.is-now .su-dot { animation: none; } .su-dot, .su-step::after { transition: none; } }
    </style>
@endonce
<ol class="su-steps" aria-label="Signing up, step {{ $at }} of 3">
    @foreach ($labels as $i => $label)
        @php $n = $i + 1; $state = $n < $at ? 'is-done' : ($n === $at ? 'is-now' : ''); @endphp
        <li class="su-step {{ $state }}" @if ($n === $at) aria-current="step" @endif>
            <span class="su-dot" aria-hidden="true">
                @if ($n < $at)
                    <svg fill="none" stroke="currentColor" stroke-width="3" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" d="M5 12.5l4.5 4.5L19 7.5"/></svg>
                @else
                    {{ $n }}
                @endif
            </span>
            <span>{{ $label }}@if ($n < $at)<span class="sr-only"> (done)</span>@endif</span>
        </li>
    @endforeach
</ol>
