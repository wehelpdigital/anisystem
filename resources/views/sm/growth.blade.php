@extends(request()->boolean('partial') ? 'layouts.partial' : 'layouts.app')

@section('title', 'Growth Stages — ' . $schedule->title)
@section('page-title', 'Growth Stages')
@section('page-subtitle', $schedule->title)
@section('help-key', 'growth')
@section('back', route('sm.hub', ['id' => $schedule->id]))

@push('head')
<style>
    /* One card per lot, and each card reads top to bottom as an answer:
       where the crop is, what that means, what to do, what to watch, and
       where it sits in the season. */
    .gr-date { display: flex; align-items: center; gap: .5rem; flex-wrap: wrap; margin-bottom: .85rem; }
    .gr-date .form-input { max-width: 12rem; }
    .gr-date-lbl { font-size: .78rem; font-weight: 700; color: var(--color-gray-500); }

    .gr-card { border: 1px solid var(--color-gray-200); border-radius: 1rem; overflow: hidden;
        background: var(--color-white); margin-bottom: .9rem; }
    .gr-top { display: flex; align-items: center; gap: .7rem; padding: .8rem .9rem;
        background: linear-gradient(135deg, #f3f8ec, #e4efd4); }
    .gr-emoji { font-size: 1.7rem; line-height: 1; }
    .gr-lot { font-size: .98rem; font-weight: 800; color: var(--color-gray-900); }
    .gr-crop { font-size: .72rem; font-weight: 700; color: #3d6823; }
    .gr-age { margin-left: auto; text-align: right; flex: 0 0 auto; }
    .gr-age-n { font-size: 1.35rem; font-weight: 800; line-height: 1; color: #3d6823; }
    .gr-age-l { font-size: .62rem; font-weight: 800; letter-spacing: .06em; text-transform: uppercase; color: #6b9f3d; }

    .gr-body { padding: .85rem .9rem; }
    .gr-stage { font-size: 1.05rem; font-weight: 800; color: var(--color-gray-900); }
    .gr-what { font-size: .85rem; line-height: 1.5; color: var(--tl-text-muted, #4b5563); margin-top: .2rem; }
    .gr-bar { height: .4rem; border-radius: 999px; background: var(--color-gray-200); overflow: hidden; margin-top: .6rem; }
    .gr-bar span { display: block; height: 100%; border-radius: 999px;
        background: linear-gradient(90deg, #6b9f3d, #4a7c2a); }
    .gr-next { font-size: .72rem; color: var(--color-gray-500); margin-top: .3rem; }

    .gr-lists { display: grid; gap: .5rem; margin-top: .8rem; }
    @media (min-width: 720px) { .gr-lists { grid-template-columns: 1fr 1fr; } }
    .gr-list { border-radius: .8rem; padding: .65rem .75rem; }
    .gr-list h4 { font-size: .64rem; font-weight: 800; letter-spacing: .06em; text-transform: uppercase;
        display: flex; align-items: center; gap: .35rem; margin-bottom: .35rem; }
    .gr-list ul { display: grid; gap: .3rem; }
    .gr-list li { font-size: .8rem; line-height: 1.45; display: flex; gap: .4rem; }
    .gr-list li::before { content: ''; flex: 0 0 auto; width: .35rem; height: .35rem; border-radius: 999px;
        margin-top: .5rem; background: currentColor; opacity: .5; }
    .gr-do { background: #f0f7e8; color: #2d5016; }
    .gr-watch { background: #fff7ed; color: #9a3412; }
    html.dark .gr-do { background: rgb(61 104 35 / .22); color: #bfe19a; }
    html.dark .gr-watch { background: rgb(154 52 18 / .2); color: #fdba74; }

    .gr-steps { margin-top: .85rem; border-top: 1px dashed var(--color-gray-200); padding-top: .65rem; display: grid; gap: .3rem; }
    .gr-step { display: flex; align-items: flex-start; gap: .5rem; font-size: .78rem; color: var(--color-gray-500); }
    .gr-dot { flex: 0 0 auto; width: .6rem; height: .6rem; border-radius: 999px; margin-top: .35rem; background: var(--color-gray-300); }
    .gr-step.is-past .gr-dot { background: #a8cc7e; }
    .gr-step.is-now { color: var(--color-gray-900); font-weight: 700; }
    .gr-step.is-now .gr-dot { background: #4a7c2a; box-shadow: 0 0 0 3px rgb(74 124 42 / .2); }
    .gr-when { margin-left: auto; flex: 0 0 auto; font-variant-numeric: tabular-nums; opacity: .7; }

    .gr-blocked { padding: .9rem; font-size: .83rem; line-height: 1.5; color: var(--color-gray-500);
        background: var(--color-gray-50); border-radius: .7rem; }
    .gr-foot { font-size: .72rem; color: var(--color-gray-400); text-align: center; margin: .6rem 0 0; }

    html.dark .gr-card { background: #151b12; border-color: #2b3a1c; }
    html.dark .gr-top { background: linear-gradient(135deg, #1c2416, #24301a); }
    html.dark .gr-lot, html.dark .gr-stage, html.dark .gr-step.is-now { color: #e8efe1; }
    html.dark .gr-blocked { background: rgb(255 255 255 / .04); }
</style>
@endpush

@section('content')
@include('sm.partials.module-header', ['schedule' => $schedule, 'module' => 'growth'])

{{-- The whole page is "on this date": today by default, any date on request,
     because planning next week's spray means reading next week's stage. --}}
<form method="GET" action="{{ route('sm.growth') }}" class="gr-date">
    <input type="hidden" name="id" value="{{ $schedule->id }}">
    <span class="gr-date-lbl">Reading the crop on</span>
    <input type="date" name="on" class="form-input" value="{{ $on->toDateString() }}" onchange="this.form.submit()">
    @if (! $on->isToday())
        <a class="btn btn-white btn-sm" href="{{ route('sm.growth', ['id' => $schedule->id]) }}">Back to today</a>
    @endif
</form>

@forelse ($rows as $r)
    <div class="gr-card">
        <div class="gr-top">
            <span class="gr-emoji">{{ $r['icon'] }}</span>
            <span class="min-w-0">
                <span class="gr-lot block">{{ $r['lot']->lotName }}</span>
                <span class="gr-crop">{{ $r['cropLabel'] ?: 'No crop set' }}</span>
            </span>
            @if ($r['age'])
                <span class="gr-age">
                    <span class="gr-age-n block">{{ $r['age']['day'] }}</span>
                    <span class="gr-age-l">{{ $r['age']['counter'] }}</span>
                </span>
            @endif
        </div>

        <div class="gr-body">
            @if ($r['blocked'])
                <p class="gr-blocked">{{ $r['blocked'] }}</p>
            @else
                @php $st = $r['stage']; @endphp
                <div class="gr-stage">{{ $st['label'] }}</div>
                <p class="gr-what">{{ $st['what'] }}</p>
                @if ($st['progress'] !== null)
                    <div class="gr-bar"><span style="width: {{ round($st['progress'] * 100) }}%"></span></div>
                @endif
                <p class="gr-next">
                    Day {{ $st['dayInStage'] + 1 }} of this stage
                    @if ($st['next'])
                        · {{ $st['next']['label'] }} in about {{ $st['next']['inDays'] }} {{ \Illuminate\Support\Str::plural('day', $st['next']['inDays']) }}
                    @else
                        · the harvest window
                    @endif
                </p>

                <div class="gr-lists">
                    @if ($r['tips']['do'])
                        <div class="gr-list gr-do">
                            <h4>
                                <svg class="w-3.5 h-3.5" fill="none" stroke="currentColor" stroke-width="2.5" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" d="M5 13l4 4L19 7"/></svg>
                                What to do now
                            </h4>
                            <ul>@foreach ($r['tips']['do'] as $t)<li>{{ $t }}</li>@endforeach</ul>
                        </div>
                    @endif
                    @if ($r['tips']['watch'])
                        <div class="gr-list gr-watch">
                            <h4>
                                <svg class="w-3.5 h-3.5" fill="none" stroke="currentColor" stroke-width="2.5" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" d="M12 9v4m0 4h.01M10.3 3.9L1.8 18a2 2 0 001.7 3h17a2 2 0 001.7-3L13.7 3.9a2 2 0 00-3.4 0z"/></svg>
                                What to watch for
                            </h4>
                            <ul>@foreach ($r['tips']['watch'] as $t)<li>{{ $t }}</li>@endforeach</ul>
                        </div>
                    @endif
                </div>

                @if ($r['timeline'])
                    <div class="gr-steps">
                        @foreach ($r['timeline'] as $step)
                            <div class="gr-step{{ $step['isNow'] ? ' is-now' : ($step['isPast'] ? ' is-past' : '') }}">
                                <span class="gr-dot"></span>
                                <span class="grow">{{ $step['label'] }}</span>
                                <span class="gr-when">{{ $r['age']['counter'] }} {{ $step['from'] }}+</span>
                            </div>
                        @endforeach
                    </div>
                @endif
            @endif
        </div>
    </div>
@empty
    <div class="card card-body text-center text-gray-500 py-10">
        <p class="font-bold text-gray-800 mb-1">No lots yet</p>
        <p class="text-sm">Add a lot, say what is growing on it, and this page will read the crop for you.</p>
        <a class="btn btn-primary mt-4 inline-flex" href="{{ route('sm.lots', ['id' => $schedule->id]) }}">Open Lots</a>
    </div>
@endforelse

@if (count($rows))
    <p class="gr-foot">Stages and guidance are the usual field practice for each crop — a guide, not a rule. Your own records win.</p>
@endif
@endsection
