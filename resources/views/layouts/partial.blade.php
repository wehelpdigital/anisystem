{{--
    Bare layout for SPA module loads: renders only the module's content, its
    sheets and its scripts — no app chrome. Requested with ?partial=1 and
    injected into #moduleHost by the schedule shell (sm/activities.blade.php).
--}}
<div data-module-content>
    {{-- Module <style> blocks (pushed to 'head') ride along here so SPA-injected
         modules keep their styling; <style> in the body applies fine. --}}
    @stack('head')
    @yield('content')
</div>
@stack('sheets')
@stack('scripts')
