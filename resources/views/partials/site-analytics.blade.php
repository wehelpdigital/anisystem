{{-- Google Analytics, through the owner's Google tag container (App\Support\SiteAnalytics says where it runs).

     The container sends the page views, scrolling, outbound clicks, downloads and form starts by itself. It is the
     place to set anything about them; what the site adds is in resources/js/site-events.js.

     $bare: the error pages, which may be drawing because the database is gone, so they ask nothing about the visitor.

     In this order, because each step needs the one before it:
     - the address is cleaned first, before Google can read it. The landing form sends the email to /signup in the
       query, and Google's rules forbid personal details in the address it keeps. And a utm tag on one of OUR OWN links
       (the signup links on Ask Anee, the landing form's) is for our signup report, which the server has already read
       (SignupSource): Google would take it as a new traffic source, and credit the signup to our own page instead of
       the ad or search that brought the visitor.
     - who this is goes in before gtag('js'), which is what sends the page view.

     The container itself loads late (2026-11-05 SEO audit): it is a 178 KB script, and on a slow phone it held the page
     back by about a second. Whatever is pushed before it arrives waits in dataLayer, so nothing is lost: it comes at the
     first touch or scroll, just after the page has loaded, or after 3.5 seconds, whichever is first. --}}
@if (\App\Support\SiteAnalytics::on())
    <script>
        (function () {
            try {
                var u = new URL(location.href), before = u.href;
                u.searchParams.delete('email');
                if (document.referrer && new URL(document.referrer).origin === location.origin) {
                    ['utm_source', 'utm_medium', 'utm_campaign', 'utm_term', 'utm_content', 'utm_id'].forEach(function (k) { u.searchParams.delete(k); });
                }
                if (u.href !== before) history.replaceState(history.state, '', u);
            } catch (e) { /* an old browser keeps the address, and the container's own filter hides the email */ }
        })();
    </script>
    <script>
        (function () {
            var loaded = false;
            function load() {
                if (loaded) return;
                loaded = true;
                var s = document.createElement('script');
                s.async = true;
                s.src = 'https://www.googletagmanager.com/gtm.js?id={{ \App\Support\SiteAnalytics::container() }}';
                document.head.appendChild(s);
            }
            ['scroll', 'touchstart', 'pointerdown', 'keydown'].forEach(function (e) { addEventListener(e, load, { once: true, passive: true }); });
            addEventListener('load', function () { setTimeout(load, 300); }, { once: true });
            setTimeout(load, 3500);
        })();
    </script>
    <script>
        window.dataLayer = window.dataLayer || [];
        function gtag(){dataLayer.push(arguments);}
        @empty($bare)
            gtag('set', 'user_properties', { member_status: '{{ \App\Support\SiteAnalytics::memberStatus() }}' });
            @if (\App\Support\SiteAnalytics::internal())
                gtag('set', 'traffic_type', 'internal');
            @endif
        @endempty
        gtag('js', new Date());
        {{-- One page view long (RegisterController): the "check your inbox" page right after an account is made. --}}
        @if (empty($bare) && session('signup.fresh'))
            gtag('event', 'sign_up', { method: 'email', plan: 'libre' });
        @endif
    </script>
@endif
