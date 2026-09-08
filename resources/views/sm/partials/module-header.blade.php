{{--
    Shared header for schedule module pages.
    Expects: $schedule (AsCroppingSchedule), $module (string key: settings|lots|workers|materials|services|documentation|activities|irrigations)
--}}
@php
    $modules = [
        'settings' => ['label' => 'Settings', 'route' => 'sm.settings'],
        'lots' => ['label' => 'Lots', 'route' => 'sm.lots'],
        'workers' => ['label' => 'Workers', 'route' => 'sm.workers'],
        'inventory' => ['label' => 'Inventory', 'route' => 'sm.inventory'],
        'documentation' => ['label' => 'Documentation', 'route' => 'sm.documentation'],
        'activities' => ['label' => 'Activities', 'route' => 'sm.activities'],
        'post-harvest' => ['label' => 'Observations', 'route' => 'sm.post-harvest'],
        'notes' => ['label' => 'Notes', 'route' => 'sm.notes'],
        'tags' => ['label' => 'Tags', 'route' => 'sm.tags'],
        'growth' => ['label' => 'Growth Stages', 'route' => 'sm.growth'],
        'gallery' => ['label' => 'Gallery', 'route' => 'sm.gallery'],
        'ai' => ['label' => 'AI Technician', 'route' => 'sm.ai'],
    ];
    // The two the owner closed to workers. This row is how a module page is
    // reached when it is opened on its own rather than inside the Activities
    // shell, so a chip left standing here is the door the hub tile and the
    // modules sheet already dropped — and both of these now answer 404.
    if (\App\Support\WorkerContext::activeGrant()) {
        unset($modules['workers'], $modules['ai'], $modules['documentation'], $modules['post-harvest'], $modules['inventory']);
    }
@endphp

{{-- Just the door and the address: the Modules chip (to the hub, where
     every module lives) and the name of the one you are standing in. The
     row used to spill EVERY module as its own chip past the screen's edge —
     a wall of tag-buttons nobody asked for, met whenever a help page's
     back-link landed on a module's standalone URL. One door, one name. --}}
<div class="mb-3 md:mb-4 module-chip-nav">
    <div class="scroll-chips">
        <a href="{{ route('sm.hub', ['id' => $schedule->id]) }}"
            class="chip chip-dashed shrink-0" data-chip-manual>
            <svg class="w-4 h-4" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" d="M4 6h16M4 12h16M4 18h16"/></svg>
            {{-- The same word the shell's hamburger uses. "All Modules" here
                 against "Other Modules" there read as two different doors,
                 and it is one door. --}}
            Modules
        </a>
        @if (isset($modules[$module]))
            <span class="chip shrink-0 is-selected">{{ $modules[$module]['label'] }}</span>
        @endif
    </div>
</div>
