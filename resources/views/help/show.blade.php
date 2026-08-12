@extends('layouts.app')

@section('title', ($page->title ?? ('How to use ' . $moduleLabel)))
@section('page-title', 'How to use')
@section('page-subtitle', $moduleLabel)
@section('back', $back ?? route('app.dashboard'))

@push('head')
    <style>
        .tut-wrap { max-width: 44rem; }
        .tut-h { font-family: var(--font-heading); font-size: 1.05rem; font-weight: 800; color: var(--color-gray-900);
            margin: 1.4rem 0 .5rem; }
        .tut-h:first-child { margin-top: 0; }
        .tut-p { font-size: .92rem; line-height: 1.65; color: var(--color-gray-600); margin-bottom: .75rem; }
        .tut-steps, .tut-tips { margin: 0 0 1rem 1.15rem; display: flex; flex-direction: column; gap: .4rem; }
        .tut-steps { list-style: decimal; }
        .tut-tips { list-style: disc; }
        .tut-steps li, .tut-tips li { font-size: .92rem; line-height: 1.55; color: var(--color-gray-700); padding-left: .2rem; }
        .tut-callout { display: flex; flex-direction: column; gap: .15rem; padding: .75rem .9rem; border-radius: .8rem;
            margin: 0 0 1rem; font-size: .88rem; line-height: 1.55;
            background: var(--color-brand-50); color: var(--color-brand-900, #14532d);
            border: 1px solid var(--color-brand-100); }
        .tut-callout strong { font-weight: 800; }
        .tut-warn { background: #fffbeb; border-color: #fde68a; color: #92400e; }
        .tut-good { background: #ecfdf5; border-color: #a7f3d0; color: #065f46; }
        .tut-figure { margin: 0 0 1rem; }
        .tut-figure img { width: 100%; border-radius: .8rem; border: 1px solid var(--color-gray-200); }
        .tut-figure figcaption { font-size: .74rem; color: var(--color-gray-400); margin-top: .35rem; text-align: center; }
        .tut-video { position: relative; padding-top: 56.25%; margin: 0 0 1rem; border-radius: .8rem; overflow: hidden;
            background: #000; }
        .tut-video iframe { position: absolute; inset: 0; width: 100%; height: 100%; border: 0; }
        .tut-hr { border: 0; border-top: 1px solid var(--color-gray-200); margin: 1.25rem 0; }
            color: var(--color-gray-500); }
        .tut-more a { display: block; padding: .55rem .7rem; border-radius: .7rem; font-size: .85rem; font-weight: 700;
            color: var(--color-gray-700); }
        .tut-more a:hover { background: var(--color-gray-50); }
    </style>
@endpush

@section('content')
    <div class="tut-wrap mx-auto">
        <div class="card mb-4">
            <div class="card-body">
                <div class="mb-1">
                    <h2 class="text-xl font-bold text-gray-900 leading-snug">
                        {{ $page->title ?? ('How to use ' . $moduleLabel) }}
                    </h2>
                    {{-- No device picker. Someone reading this is holding the
                         thing the instructions are about, and being asked to
                         choose between phone, tablet and desktop is being asked
                         a question the browser already answered. The server
                         reads the device and serves the page written for it,
                         falling back to the nearest one that exists. --}}
                </div>
                @if ($page?->summary)
                    <p class="text-sm text-gray-500">{{ $page->summary }}</p>
                @endif
            </div>
        </div>

        <div class="card">
            <div class="card-body">
                @if ($page && $page->blocks)
                    {!! \App\Support\TutorialBlocks::render($page->blocks) !!}
                @else
                    <div class="text-center py-10">
                        <div class="mx-auto w-14 h-14 rounded-2xl bg-gray-100 flex items-center justify-center mb-3">
                            <svg class="w-7 h-7 text-gray-400" fill="none" stroke="currentColor" stroke-width="1.8" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" d="M8.2 9a3.8 3.8 0 117.3 1.4c-.6 1.4-2.5 1.9-2.5 3.6M12 17.5h.01"/></svg>
                        </div>
                        <h3 class="font-bold text-gray-900 mb-1">Nothing written for this one yet</h3>
                        <p class="text-sm text-gray-500">The guide for {{ $moduleLabel }} hasn't been written yet.</p>
                    </div>
                @endif
            </div>
        </div>

        @if ($others->isNotEmpty())
            <div class="card mt-4">
                <div class="card-body">
                    <h3 class="text-xs font-bold text-gray-400 uppercase mb-1.5">Other guides</h3>
                    <div class="tut-more grid sm:grid-cols-2 gap-0.5">
                        @foreach ($others as $o)
                            <a href="{{ route('help.show', ['module' => $o->moduleKey]) }}">{{ $o->title }}</a>
                        @endforeach
                    </div>
                </div>
            </div>
        @endif
    </div>
@endsection
