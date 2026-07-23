{{--
    Bare layout for SPA module loads: renders only the module's content, its
    sheets and its scripts — no app chrome. Requested with ?partial=1 and
    injected into #moduleHost by the schedule shell (sm/activities.blade.php).
--}}
<div data-module-content>
    @yield('content')
</div>
@stack('sheets')
@stack('scripts')
