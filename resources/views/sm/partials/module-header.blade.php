{{--
    Shared header for schedule module pages.
    Expects: $schedule (AsCroppingSchedule), $module (string key: settings|lots|workers|materials|services|documentation|activities|irrigations)
--}}
@php
    $modules = [
        'settings' => ['label' => 'Settings', 'route' => 'sm.settings'],
        'lots' => ['label' => 'Lots', 'route' => 'sm.lots'],
        'workers' => ['label' => 'Workers', 'route' => 'sm.workers'],
        'materials' => ['label' => 'Materials', 'route' => 'sm.materials'],
        'services' => ['label' => 'Services', 'route' => 'sm.services'],
        'documentation' => ['label' => 'Documentation', 'route' => 'sm.documentation'],
        'activities' => ['label' => 'Activities', 'route' => 'sm.activities'],
        'irrigations' => ['label' => 'Irrigation', 'route' => 'sm.irrigations'],
    ];
@endphp

<div class="mb-4 md:mb-6">
    <div class="scroll-chips">
        <a href="{{ route('sm.hub', ['id' => $schedule->id]) }}"
            class="chip chip-dashed shrink-0" data-chip-manual>
            <svg class="w-4 h-4" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" d="M4 6h16M4 12h16M4 18h16"/></svg>
            All Modules
        </a>
        @foreach ($modules as $key => $m)
            <a href="{{ route($m['route'], ['id' => $schedule->id]) }}"
                class="chip shrink-0 {{ $module === $key ? 'is-selected' : '' }}" data-chip-manual>
                {{ $m['label'] }}
            </a>
        @endforeach
    </div>
</div>
